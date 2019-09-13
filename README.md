osTicket-discord
================
A plugin for [osTicket](https://osticket.com) which posts notifications to a [Discord](https://discordapp.com/) webhook.

Originally forked from: [https://github.com/thammanna/osticket-slack](https://github.com/thammanna/osticket-slack) and
[https://github.com/clonemeagain/osticket-slack](https://github.com/clonemeagain/osticket-slack).

Info
----
- send notifications on ticket created
- manage alerts : tickets where no user interaction is needed

## Requirements
- php_curl
- osTicket-1.12
- A discord account

## Install
----------
1. Clone this repo or download the zip file and place the contents into your `include/plugins` folder.
1. Now the plugin needs to be enabled & configured, so login to osTicket, select "Admin Panel" then "Manage -> Plugins" you should be seeing the list of currently installed plugins.
1. Click on `Discord Notifier` and paste your Discord Webhook URL into the box (Discord setup instructions below). 
1. Click `Save Changes`! (If you get an error about curl, you will need to install the Curl module for PHP). 
1. After that, go back to the list of plugins and tick the checkbox next to "Discord Notifier" and select the "Enable" button.


## Discord Setup:
- Right click on your server in discord software
- Select "Server parameters / Webhooks"
- Create a new webhook
- Copy the Webhook URL entirely, paste this into the `osTicket -> Admin -> Plugin -> Discord Notifier` config admin screen.
