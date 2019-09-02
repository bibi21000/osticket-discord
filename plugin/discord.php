<?php

require_once(INCLUDE_DIR.'class.signal.php');
require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class DiscordPlugin extends Plugin {
    var $config_class = 'DiscordPluginConfig';

    function bootstrap() {
        Signal::connect('ticket.created', array($this, 'onTicketCreated'));
        #Signal::connect('threadentry.created', array($this, 'onTicketUpdated'));
    }

    function onTicketCreated($ticket){
        global $ost, $cfg;
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
            
        }
        catch(Exception $e) {
            error_log(sprintf('Error onTicketCreated to Discord Webhook. %s', $e->getMessage()));
            $ost->logError(_S('Discord plugin'),
                           sprintf(_S('Error onTicketCreated to Discord Webhook. %s'), 
                           $e->getMessage()));
        }
    }

    function onTicketUpdated($ticket){
        global $ost, $cfg;
        try {
            
            $author['name'] = sprintf('%s (%s)', $ticket->getEmail()->getName(), $ticket->getEmail());
            $ost->logDebug(_S('Discord plugin'),
            sprintf(_S("Email %s"), $ticket->getEmail()));

            $fields[0]['name'] = 'field name';
            $fields[0]['value'] = 'field value';
            $fields[0]['inline'] = true;
           
            $embeds[0]['author'] = $author;
            $embeds[0]['type'] = 'rich';
            $embeds[0]['color'] = 0xff0000;
            $embeds[0]['title'] = $ticket->getSubject();
            $embeds[0]['url'] = $cfg->getUrl() . 'scp/tickets.php?id=' . $ticket->getId();
            $embeds[0]['description'] = strip_tags($ticket->getLastMessage()->getBody()->getClean());
            $payload['embeds'] = $embeds;

            $this->discordMessage($payload);
            
        }
        catch(Exception $e) {
            error_log(sprintf('Error onTicketCreated to Discord Webhook. %s', $e->getMessage()));
            $ost->logError(_S('Discord plugin'),
                           sprintf(_S('Error onTicketCreated to Discord Webhook. %s'), 
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

}
