<?php
require_once('../upload/include/plugins'. 'discord.php');

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
