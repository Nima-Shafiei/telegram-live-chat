=== TLC - Telegram Live Chat ===
Contributors: nimashafiee
Tags: telegram, live chat, customer support, chat, support
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect a website chat widget to Telegram so visitors can message your support team in real time.

== Description ==

TLC – Live Chat for Telegram lets website visitors chat with your support team directly from your website, while administrators receive and reply to conversations through Telegram.

Unlike traditional live chat plugins that require administrators to monitor a separate WordPress dashboard, TLC Chat routes visitor conversations to Telegram, allowing your support team to respond directly from Telegram.

Key features:

* Real-time website chat widget
* Receive visitor messages in Telegram
* Reply to visitors directly from Telegram
* Multiple Telegram administrators
* Online/offline chat status
* Custom welcome and offline messages
* Lightweight REST API-based communication
* No separate WordPress chat dashboard required for replying to visitors

== Installation ==

1. Upload the `telegram-live-chat` folder to the `/wp-content/plugins/` directory, or install the ZIP file from the WordPress Plugins screen.
2. Activate Telegram Live Chat through the Plugins screen in WordPress.
3. Open **TLC > Settings** and save the token supplied by BotFather.
4. Select **Connect Telegram** to verify the bot and register its webhook.
5. Open **TLC > Administrators**, generate a pairing link, and open it from the Telegram account that will be the owner.
6. Generate another pairing link for each additional administrator. Administrators must reply to a visitor message in Telegram for their response to appear on the website.

== Disclaimer ==

TLC Chat is an independent third-party plugin and is not affiliated with, endorsed by, or sponsored by Telegram.

== Why TLC Chat? ==

TLC Chat is designed for teams that already use Telegram for customer support.

Instead of requiring administrators to continuously monitor a separate WordPress chat dashboard, TLC Chat sends website conversations directly to Telegram. Administrators can read and reply to visitor messages from Telegram while visitors continue chatting through the website widget.

This approach keeps the website chat experience simple for visitors while allowing support teams to manage conversations from a communication platform they already use.

== Frequently Asked Questions ==

= Is a Telegram account required? =

Yes. A Telegram account is required to connect the plugin with your Telegram bot and manage visitor conversations.

= Does the plugin need a Telegram bot? =

Yes. Create a bot with BotFather and enter its token in the plugin settings.

= How do I add another administrator? =

Open **TLC > Administrators**, select **Generate Pairing Link**, and send the link to the administrator. The first paired account remains the owner; later accounts are added as administrators.

= Can I remove an administrator? =

Yes. On the Administrators screen, select **Remove** next to that administrator. The owner cannot be removed through this screen.

== Changelog ==

= 1.0.0 =
* Initial stable release.

= 0.1.7 =
* Updated the chat widget and built-in messages for English-language sites.

= 0.1.6 =
* Added support for multiple Telegram administrators.
* Refined the Telegram-inspired chat interface.
* Resolved Plugin Check compatibility findings.

