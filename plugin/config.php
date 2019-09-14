<?php

require_once INCLUDE_DIR . 'class.plugin.php';
include_once(INCLUDE_DIR . 'class.dept.php');
include_once(INCLUDE_DIR . 'class.list.php');


class DiscordPluginConfig extends PluginConfig {
    function getOptions() {
        return array(
            'discord' => new SectionBreakField(array(
                'label' => 'Discord',
            )),
            'discord-webhook-url' => new TextboxField(array(
                'label' => 'Webhook URL',
                'configuration' => array(
                    'size' => 100,
                    'length' => 200,
                ),
            )),

             'notifs' => new SectionBreakField(array(
                'label' => 'Notifications',
            )),
            'notify-new' => new BooleanField(array(
                'label'   => 'Notify on New Ticket',
                'hint'    => 'Send a discord notification to the above webhook whenever a new ticket is created.',
                'default' => TRUE,
            )),
            'notify-update' => new BooleanField(array(
                'label'   => 'Notify on updated Ticket',
                'hint'    => 'Send a discord notification to the above webhook whenever a ticket is updated.',
                'default' => FALSE,
            )),
            'alerts' => new SectionBreakField(array(
                'label' => 'Alerts',
            )),
            'alert-active' => new BooleanField(array(
                'label'   => 'Activate the alert managment.',
                'hint'    => 'Send a discord notification and close ticket for a specific-department.',
                'default' => FALSE,
            )),
            'alert-delay' => new TextboxField(array(
                'label'   => 'The delay before changing state of the alerts.',
                'hint'    => 'The delay before changing state of the alerts. 0 for on ticket creation or a value in minutes.',
                'configuration' => array(
                    'size' => 5,
                    'length' => 5,
                ),
                'default' => 0,
            )),
            'alert-dept_id' => new ChoiceField(array(
                'default' => 0,
                'required' => true,
                'label' => 'The Department used for alerts',
                'hint' => 'All tickets in this Department are considered as alerts',
                'choices' =>
                    array(0 => '— '.'Primary Department'.' —')
                    + Dept::getDepartments(),
            )),
            'alert-status_id' => new ChoiceField(array(
                'default' => 0,
                'required' => true,
                'label' => 'Status to set alerts to',
                'hint' => 'Status to use for alerts',
                'choices' =>
                    array(0 => '— '.'Status to use'.' —')
                    + TicketStatusList::getStatuses()->all(),
            )),
            'Reminder' => new SectionBreakField(array(
                'label' => 'Reminder',
            )),
            'reminder-active' => new BooleanField(array(
                'label'   => 'Activate the reminder.',
                'hint'    => 'Send a discord notification for unclosed tickets.',
                'default' => FALSE,
            )),
            'reminder-delay' => new TextboxField(array(
                'label'   => 'The delay before sending notifications.',
                'hint'    => 'The delay in minutes before sending notifications.',
                'configuration' => array(
                    'size' => 5,
                    'length' => 5,
                ),
                'default' => 0,
            )),

        );
    }
}
