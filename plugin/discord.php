<?php

require_once(INCLUDE_DIR.'class.signal.php');
require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class DiscordPlugin extends Plugin {
    var $config_class = 'DiscordPluginConfig';

    function bootstrap() {
        Signal::connect('ticket.created', array($this, 'onTicketCreated'));
        Signal::connect('threadentry.created', array($this, 'onTicketUpdated'));
        Signal::connect('cron', array($this, 'onCron'));
    }

    function onTicketCreated(Ticket $ticket){
        global $ost, $cfg;
        if ($this->getConfig()->get('notify-new') == False ) {
            $ost->logDebug(_S('Discord plugin'),
                           _S('notify-new is disabled. Exiting'));
            return;
        }
        try {
            
            $author['name'] = sprintf('%s (%s)', $ticket->getEmail()->getName(), $ticket->getEmail());
            $ost->logDebug(_S('Discord plugin'),
            sprintf(_S("Email %s"), $ticket->getEmail()));

            $fields[0]['name'] = 'field name';
            $fields[0]['value'] = 'field value';
            $fields[0]['inline'] = true;
           
            $embeds[0]['author'] = $author;
            $embeds[0]['type'] = 'rich';
            $embeds[0]['color'] = 0xffaa00;
            $embeds[0]['title'] = $ticket->getSubject();
            $embeds[0]['url'] = $cfg->getUrl() . 'scp/tickets.php?id=' . $ticket->getId();
            $embeds[0]['description'] = strip_tags($ticket->getLastMessage()->getBody()->getClean());
            $payload['embeds'] = $embeds;

            $this->discordMessage($payload);

            if ( $this->getConfig()->get('alert-active') and 
                 $ticket->getDeptId()== $this->getConfig()->get('alert-dept_id') and 
                 intval($this->getConfig()->get('alert-delay'))==0) {
                    $ost->logDebug(_S('Discord plugin'),
                                   sprintf(_S('Matching dept_id %s'), $ticket->getDeptId()));
                    $ticket->setStatus($this->getConfig()->get('alert-status_id'), $comments='Closed by discord-notifier');
                }
        }
        catch(Exception $e) {
            error_log(sprintf('Error onTicketCreated to Discord Webhook. %s', $e->getMessage()));
            $ost->logError(_S('Discord plugin'),
                           sprintf(_S('Error onTicketCreated to Discord Webhook. %s'), 
                           $e->getMessage()));
        }
    }

    function onTicketUpdated(ThreadEntry $entry){
        global $ost, $cfg;
        if ( $this->getConfig()->get('notify-update') == False ) {
            $ost->logDebug(_S('Discord plugin'),
                           _S('notify-update is disabled. Exiting'));
            return;
        }
        try {
            // Need to fetch the ticket from the ThreadEntry
            $ticket = $this->getTicket($entry);
            if (!$ticket instanceof Ticket) {
            // Admin created ticket's won't work here.
                $ost->logDebug("Discord ignoring message", "Because there is no associated ticket.");
                return;
            }
            // Check to make sure this entry isn't the first (ie: a New ticket)
            $first_entry = $ticket->getMessages()[0];
            if ($entry->getId() == $first_entry->getId()) {
                $ost->logDebug("Discord ignoring message", "Because we don't want to notify twice on new Tickets");
                return;
            }       
            $author['name'] = sprintf('%s (%s)', $entry->getEmail()->getName(), $entry->getEmail());
            $ost->logDebug(_S('Discord plugin'),
            sprintf(_S("Email %s"), $entry->getEmail()));

            $fields[0]['name'] = 'field name';
            $fields[0]['value'] = 'field value';
            $fields[0]['inline'] = true;
           
            $embeds[0]['author'] = $author;
            $embeds[0]['type'] = 'rich';
            $embeds[0]['color'] = 0xff0000;
            $embeds[0]['title'] = $entry->getSubject();
            $embeds[0]['url'] = $cfg->getUrl() . 'scp/tickets.php?id=' . $entry->getId();
            $embeds[0]['description'] = strip_tags($entry->getLastMessage()->getBody()->getClean());
            $payload['embeds'] = $embeds;

            $this->discordMessage($payload);

           
        }
        catch(Exception $e) {
            error_log(sprintf('Error onTicketUpdated to Discord Webhook. %s', $e->getMessage()));
            $ost->logError(_S('Discord plugin'),
                           sprintf(_S('Error onTicketUpdated to Discord Webhook. %s'), 
                           $e->getMessage()));
        }
    }

    function discordMessage($payload){
        global $ost, $cfg;

        try {
                
            $data_string = utf8_encode(json_encode($payload));
                            
            $url = $this->getConfig()->get('discord-webhook-url');

            $ost->logDebug(_S('Discord plugin'),
            sprintf(_S("Send JSON %s to %s"), $data_string,
                $url));

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                sprintf('Content-Length: %s', strlen($data_string)),
            ));

            $response = curl_exec($ch);
            $ost->logDebug(_S('Discord plugin'),
                           sprintf(_S('%s Http response: %s'), 
                           $url, $response));

            if ($response){
                $ost->logError(_S('Discord plugin'),
                               sprintf(_S('%s - %s (%s)'), 
                               $url, $response, curl_error($ch)));
                throw new Exception(sprintf('%s - %s', $url, curl_error($ch)));
            }
            else {
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($statusCode != '204'){
                    $ost->logError(_S('Discord plugin'),
                                   sprintf(_S('%s Http code: %s'), 
                                   $url, $statusCode));
                    throw new Exception(sprintf('%s Http code: %s', $url, $statusCode));
                }
            }

            curl_close($ch);
            
        }
        catch(Exception $e) {
            error_log(sprintf('Error posting to Discord Webhook. %s', $e->getMessage()));
            $ost->logError(_S('Discord plugin'),
                           sprintf(_S('Error posting to Discord Webhook. %s'), 
                           $e->getMessage()));
        }
    }

 /**
     * Cooperates with the cron system to automatically find content that is
     * not indexed in the _search table and add it to the index.
     */
    function onCron() {
        global $ost, $cfg;
        if ($this->getConfig()->get('alert-active') and
            intval($this->getConfig()->get('alert-delay'))>0) {
    
                $sql = "SELECT `ticket_id` FROM `".TICKET_TABLE."`
                    WHERE dept_id = " . $this->getConfig()->get('alert-dept_id') . "
                      AND status_id != " . $this->getConfig()->get('alert-status_id') . "  
                      AND closed != NULL
                      AND DATE_ADD(created, INTERVAL " . $this->getConfig()->get('alert-delay') ."  MINUTE) < NOW() 
                    ORDER BY `ticket_id` DESC LIMIT 10";
                   //   AND DATE_ADD(created, INTERVAL " . $this->getConfig()->get('alert-delay') ."  MINUTE) < NOW() > 

                if (!($res = db_query_unbuffered($sql, $auto_create))) {
                        $ost->logDebug(_S('Discord plugin'),
                            _S('Error in select for tickets for alert'));
                    return false;
                }

                while ($row = db_fetch_row($res)) {
                    if (!($ticket = Ticket::lookup($row[0])))
                        continue;
                    $ost->logDebug(_S('Discord plugin'),
                        sprintf(_S('Matching ticket %s'), $ticket->ticket_id));

                    $ticket->setStatus($this->getConfig()->get('alert-status_id'), $comments='Closed by discord-notifier');
                }
        }

        if ($this->getConfig()->get('reminder-active')) {

            if (in_array(strval(date('w')), explode(',', $this->getConfig()->get('reminder-days')))) {
                //A working day
                $dtz = new DateTimeZone($cfg->getTimezone());
		if ( $this->getConfig()->get('reminder-lastrun') == '') {
                    $st = new DateTime();
                    $st->setTimezone($dtz);
		    $this->getConfig()->set('reminder-lastrun', $st->format('Y-m-d H:i'));
	            $ost->logDebug(_S('Discord plugin'),
                         sprintf(_S('Cant retrieve reminder-lastrun. Set it to %s'), 
                         $st->format('Y-m-d H:i')
		    ));
 	}
                $now = new DateTime();
                $now->setTimezone($dtz);
                $start_day = new DateTime( sprintf('%s %s', $now->format('Y-m-d'), $this->getConfig()->get('reminder-start') ), $dtz );
                $stop_day = new DateTime( sprintf('%s %s', $now->format('Y-m-d'), $this->getConfig()->get('reminder-stop') ), $dtz );
                $ost->logDebug(_S('Discord plugin'),
                   sprintf(_S('Work day from %s to %s and we are %s'), 
                   $start_day->format('Y-m-d H:i'), $stop_day->format('Y-m-d H:i'), $now->format('Y-m-d H:i')
		   ));
                if ( $now > $start_day and $now<$stop_day ) {
                    $last_run = new Datetime($this->getConfig()->get('reminder-lastrun'), $dtz );
                    if ( $last_run == $start_day ) {
                        $next_run = new Datetime($this->getConfig()->get('reminder-lastrun'), $dtz );
                    } else {
                        $next_run = new Datetime($this->getConfig()->get('reminder-lastrun'), $dtz );
                        $delay = intval($this->getConfig()->get('reminder-timer'));
                        if ( $delay < 1 ) $delay = 15;
                        $next_run->add(new DateInterval(sprintf('PT%sM',$delay)));
                    }
                    $ost->logDebug(_S('Discord plugin'),
                            sprintf(_S('Last run %s and next %s and we are %s'),$last_run->format('Y-m-d H:i'), $next_run->format('Y-m-d H:i'), $now->format('Y-m-d H:i') 
	            ));
                    if ($now > $next_run) {

                       if ($last_run < $start_day) {
                            //First message of day
                            $this->onReminderHey($title='Hi guys', $description='I hope I would not have to come back today');
                            $ost->logDebug(_S('Discord plugin'),
                               sprintf( _S('Update lastrun from %s to %s'),
                                   $last_run->format('Y-m-d H:i'), $now->format('Y-m-d H:i') 
   	                    ));

                            $this->getConfig()->set('reminder-lastrun', $start_day->format('Y-m-d H:i'));
                            $ost->logDebug(_S('Discord plugin'),
                                 _S('Send hi messge') 
	                    );
                       } else if ($next_run > $stop_day and $last_run != $stop_day) {
                            //Last message of day
                            $this->onReminderHey($title='Bye guys', $description='Wish you a good evening');
                            $ost->logDebug(_S('Discord plugin'),
                               sprintf( _S('Update lastrun from %s to %s'),
                                   $last_run->format('Y-m-d H:i'), $stop_day->format('Y-m-d H:i') 
                            ));

                            $this->getConfig()->set('reminder-lastrun', $stop_day->format('Y-m-d H:i'));
                            $ost->logDebug(_S('Discord plugin'),
                                 _S('Send bye messge') 
	                    );
                       } else {
                            $ost->logDebug(_S('Discord plugin'),
                                 _S('Send ticket message') 
	                    );

                            $sql = "SELECT `ticket_id` FROM `".TICKET_TABLE."`
                                WHERE status_id IN ( SELECT id FROM `".TICKET_STATUS_TABLE."` WHERE state='open')
                                  AND DATE_ADD(created, INTERVAL " . $this->getConfig()->get('reminder-delay') ."  MINUTE) < NOW() 
                                  AND isanswered = 0  
                                ORDER BY `created` DESC LIMIT " . $this->getConfig()->get('reminder-number') . ";";
                               //   AND DATE_ADD(created, INTERVAL " . $this->getConfig()->get('alert-delay') ."  MINUTE) < NOW() > 

                            if (!($res = db_query_unbuffered($sql, $auto_create))) {
                                    $ost->logDebug(_S('Discord plugin'),
                                        _S('Error in select for tickets for reminder'));
                                return false;
                            }

                           $this->onReminderTickets($res);
                           $ost->logDebug(_S('Discord plugin'),
                               sprintf( _S('Update lastrun from %s to %s'),
                                   $last_run->format('Y-m-d H:i'), $now->format('Y-m-d H:i') 
   	                   ));

                           $this->getConfig()->set('reminder-lastrun', $now->format('Y-m-d H:i'));
                        }
                   } else {
                        $ost->logDebug(_S('Discord plugin'),
                           _S('Need wait to check') 
	                );

                    }
		}
            }
        }

    }

    function onReminderTickets($res){
        global $ost, $cfg;
        $nb = 0;
        try {
            while ($row = db_fetch_row($res)) {
                if (!($ticket = Ticket::lookup($row[0])))
                    continue;
                //$ost->logDebug(_S('Discord plugin'),
                //    sprintf(_S('Matching ticket %s for reminder'), $ticket->ticket_id));
                $fields[$nb]['name'] = sprintf('%s (%s)', $ticket->getEmail()->getName(), $ticket->getEmail());
                $fields[$nb]['value'] = sprintf('%s [show](%s)', $ticket->getSubject(), $cfg->getUrl() . 'scp/tickets.php?id=' . $ticket->getId());
                $fields[$nb]['inline'] = true;
                $nb += 1;
            }
        
            if ( $nb == 0 ) {
                $ost->logDebug(_S('Discord plugin'),
                               _S('Nothing to remind. Exiting'));
                return;
            }
            
            $author['name'] = sprintf('%s', $this->getConfig()->get('reminder-surname'));
           
            $embeds[0]['author'] = $author;
            $embeds[0]['type'] = 'rich';
            $embeds[0]['color'] = 0xff0000;
            $embeds[0]['timestamp'] = (new DateTime('now', new DateTimeZone($cfg->getTimezone()) ))->format('c');
            $embeds[0]['title'] = sprintf('You will hate me %s !!!','guys');
            $embeds[0]['url'] = $cfg->getUrl() . 'scp/tickets.php';
            $embeds[0]['fields'] = $fields;
            $payload['embeds'] = $embeds;

            $this->discordMessage($payload);

           
        } catch(Exception $e) {
            error_log(sprintf('Error onReminder to Discord Webhook. %s', $e->getMessage()));
            $ost->logError(_S('Discord plugin'),
                           sprintf(_S('Error onReminder to Discord Webhook. %s'), 
                           $e->getMessage()));
        }
    }
    
    function onReminderHey($title='Hi guys', $description='I hope I would not have to come back today'){
        global $ost, $cfg;
        try {
         
            $author['name'] = sprintf('%s', $this->getConfig()->get('reminder-surname'));
           
            $embeds[0]['author'] = $author;
            $embeds[0]['type'] = 'rich';
            $embeds[0]['color'] = 0x00ff00;
            $embeds[0]['title'] = $title;
            $embeds[0]['timestamp'] = (new DateTime('now', new DateTimeZone($cfg->getTimezone()) ))->format('c');
            $embeds[0]['url'] = $cfg->getUrl() . 'scp/tickets.php';
            $embeds[0]['description'] = $description;
            $payload['embeds'] = $embeds;

            $this->discordMessage($payload);

           
        } catch(Exception $e) {
            error_log(sprintf('Error onReminder to Discord Webhook. %s', $e->getMessage()));
            $ost->logError(_S('Discord plugin'),
                           sprintf(_S('Error onReminder to Discord Webhook. %s'), 
                           $e->getMessage()));
        }
    }

}
