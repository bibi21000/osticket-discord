<?php

require_once INCLUDE_DIR . 'class.plugin.php';

class DiscordPluginConfig extends PluginConfig {

    public static $template = '%{ticket.name.full} (%{ticket.email}) in *%{ticket.dept}* _%{ticket.topic}_\n\n```%{discord_safe_message}``` ';

    public static $template = "%{ticket.name.full} (%{ticket.email}) in *%{ticket.dept}* _%{ticket.topic}_\n\n```%{discord_safe_message}```";

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
            $errors['err'] = 'Your regex was invalid, try something like "spam", it will become: "/spam/i" when we use it, or leave empty for no filter.';
            return FALSE;
        }
        if (!$config['notify-new'] && !$config['notify-replies']) {
            $errors['err'] = 'You will not get any notifications.. might as well disable the plugin mate.';
            return false;
        }


        if (!$config['discord-webhook-url']) {
            $errors['err'] = 'You need to view the Readme and configure the Discord Webhook URL before using this';
            return false;
        }

        if (!extension_loaded('curl')) {
            $errors['err'] = 'PHP curl extension missing, You need to install and enable the php_curl extension before we can use it to send notifications.';
            return false;
        }
        if (!$config['notify-new'] && !$config['notify-replies']) {
            $errors['err'] = 'You will not get any notifications.. might as well disable the plugin mate.';
            return false;
        }


        if (!$config['discord-webhook-url']) {
            $errors['err'] = 'You need to view the Readme and configure the Discord Webhook URL before using this';
            return false;
        }

        if (!extension_loaded('curl')) {
            $errors['err'] = 'PHP curl extension missing, You need to install and enable the php_curl extension before we can use it to send notifications.';
            return false;
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
            'notify-new'                 => new BooleanField([
                'label'   => $__('Notify on New Ticket'),
                'hint'    => $__('Send a discord notification to the above webhook whenever a new ticket is created.'),
                'default' => TRUE,
                    ]),
            'nm-colour'                  => new TextboxField([
                'label'   => $__('New Message Colour'),
                'hint'    => 'https://api.discord.com/docs/message-attachments#color',
                'default' => 'good',
                    ]),
            'notify-replies'             => new BooleanField([
                'label'   => $__('Notify on Reply'),
                'hint'    => $__('Send a discord notification to the above webhook whenever a ticket is replied to by a user.'),
                'default' => TRUE,
                    ]),
            'ar-colour'                  => new TextboxField([
                'label'   => $__('Agent Reply colour'),
                'default' => '#000000',
                'hint'    => 'black-ish',
                    ]),
            'post-user-messages'         => new BooleanField([
                'label'   => $__('Notify on User Replies to tickets'),
                'default' => TRUE]),
            'um-colour'                  => new TextboxField([
                'label'   => $__('User Reply Colour'),
                'default' => 'warning',
                'hint'    => 'yellow-ish']),
            'post-agent-messages'        => new BooleanField([
                'label'   => $__('Notify on Agent Replies to tickets'),
                'default' => FALSE]),
            'am-colour'                  => new TextboxField([
                'label'   => $__('Agent Reply Colour'),
                'default' => '#439FE0',
                'hint'    => 'blue-ish']),
            'post-system-messages'       => new BooleanField([
                'label'   => $__('Notify on System messages'),
                'default' => FALSE,
                'hint'    => $__('Overdue, status change, transfers etc')]),
            'sm-colour'                  => new TextboxField([
                'label'   => $__('System Message Colour'),
                'hint'    => 'red-ish',
                'default' => 'danger']),
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
                'default'       => self::$template,
                'configuration' => [
                    'html' => FALSE,
                ]
                    ])
        );
    }

}
