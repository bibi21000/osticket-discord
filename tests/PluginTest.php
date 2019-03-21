<?php
require_once('/home/travis/build/bibi21000/osticket-discord/upload/include/plugins/plugin/'. 'discord.php');

class PluginTests extends PHPUnit_Framework_TestCase
{
    private $calculator;
 
    protected function setUp()
    {
        $this->plugin = new DiscordPlugin();
    }
 
    protected function tearDown()
    {
        $this->plugin = NULL;
    }
 
    public function testBootstrap()
    {
        $result = $this->plugin->bootstrap();
        $this->assertEquals(3, $result);
    }
 
}
