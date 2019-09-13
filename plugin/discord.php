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
                  AND DATE_ADD(created, INTERVAL " . $this->getConfig()->get('alert-delay') ."  MINUTE) < NOW() 
                ORDER BY `ticket_id` DESC LIMIT 10";
               //   AND DATE_ADD(created, INTERVAL " . $this->getConfig()->get('alert-delay') ."  MINUTE) < NOW() > 
    
            if (!($res = db_query_unbuffered($sql, $auto_create))) {
                $ost->logDebug(_S('Discord plugin'),
                        _S('Error in select for tickets'));
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

    }
}
