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
            'reminder-surname' => new TextboxField(array(
                'label'   => 'The surname of the reminder.',
                'hint'    => 'The surname of the reminder.',
                'configuration' => array(
                    'size' => 50,
                    'length' => 50,
                ),
                'default' => "Chief Sergeant Chaudard",
            )),
            'reminder-age' => new TextboxField(array(
                'label'   => 'The age of tickets before sending notifications.',
                'hint'    => 'The age of tickets in minutes before sending notifications.',
                'configuration' => array(
                    'size' => 5,
                    'length' => 5,
                ),
                'default' => 0,
            )),
            'reminder-timer' => new TextboxField(array(
                'label'   => 'The delay between 2 runs.',
                'hint'    => 'The delay in minutes between 2 runs.',
                'configuration' => array(
                    'size' => 5,
                    'length' => 5,
                ),
                'default' => 60,
            )),
            'reminder-lastrun' => new TextboxField(array(
                'label'   => 'Datetime of the last run.',
                'hint'    => 'Date and time of the last run.',
                'configuration' => array(
                    'size' => 25,
                    'length' => 25,
                ),
            )),
            'reminder-start' => new TextboxField(array(
                'label'   => 'Hour to start the remindner.',
                'hint'    => 'Hour (hh:mm) to start the reminder.',
                'configuration' => array(
                    'size' => 10,
                    'length' => 10,
                ),
                'default' =>"9:30",
            )),
            'reminder-stop' => new TextboxField(array(
                'label'   => 'Hour to stop the remindner.',
                'hint'    => 'Hour (hh:mm) to stop the reminder.',
                'configuration' => array(
                    'size' => 10,
                    'length' => 10,
                ),
                'default' =>"18:00",
            )),
            'reminder-days' => new TextboxField(array(
                'label'   => 'Days of the week to run the reminder.',
                'hint'    => 'Days of the week (from 0 to 6, 0=Sunday, ...) to run the reminder (in a list separted by ,).',
                'configuration' => array(
                    'size' => 15,
                    'length' => 15,
                ),
                'default' =>"1,2,3,4,5",
            )),

        );
    }
}
