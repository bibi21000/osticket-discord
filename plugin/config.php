<?php

require_once INCLUDE_DIR . 'class.plugin.php';

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
        );
    }
}
