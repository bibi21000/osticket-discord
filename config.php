<?php

require_once INCLUDE_DIR . 'class.plugin.php';

class DiscordPluginConfig extends PluginConfig {

    // Provide compatibility function for versions of osTicket prior to
    // translation support (v1.9.4)
    function translate() {
        if (!method_exists('Plugin', 'translate')) {
            return array(
                function ($x) {
                    return $x;
                },
                function ($x, $y, $n) {
                    return $n != 1 ? $y : $x;
                }
            );
        }
        return Plugin::translate('discord');
    }

    function pre_save($config, &$errors) {
        if ($config['discord-regex-subject-ignore'] && false === @preg_match("/{$config['discord-regex-subject-ignore']}/i", null)) {
            $errors['err'] = 'Your regex was invalid, try something like "spam", it will become: "/spam/i" when we use it.';
            return FALSE;
        }
        return TRUE;
    }

    function getOptions() {
        list ($__, $_N) = self::translate();

        return array(
            'discord'                      => new SectionBreakField(array(
                'label' => $__('discord notifier'),
                'hint'  => $__('Readme first: https://github.com/bibi21000/osticket-discord')
                    )),
            'discord-webhook-url'          => new TextboxField(array(
                'label'         => $__('Webhook URL'),
                'configuration' => array(
                    'size'   => 100,
                    'length' => 200
                ),
                    )),
            'discord-regex-subject-ignore' => new TextboxField([
                'label'         => $__('Ignore when subject equals regex'),
                'hint'          => $__('Auto delimited, always case-insensitive'),
                'configuration' => [
                    'size'   => 30,
                    'length' => 200
                ],
                    ]),
            'message-template'           => new TextareaField([
                'label'         => $__('Message Template'),
                'hint'          => $__('The main text part of the discord message, uses Ticket Variables, for what the user typed, use variable: %{discord_safe_message}'),
                // "<%{url}/scp/tickets.php?id=%{ticket.id}|%{ticket.subject}>\n" // Already included as Title
                'default'       => "%{ticket.name.full} (%{ticket.email}) in *%{ticket.dept}* _%{ticket.topic}_\n\n```%{discord_safe_message}```",
                'configuration' => [
                    'html' => FALSE,
                ]
                    ])
        );
    }

}
