=== Telegram Live Chat ===
Contributors: Nima Shafiee
Tags: telegram, live chat, customer support, chat, support
Requires at least: 6.5
Tested up to: 7.0.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect a website chat widget to Telegram so visitors can message your support team in real time.

== Description ==

Telegram Live Chat adds a lightweight chat widget to your WordPress website. Visitor messages are delivered to paired Telegram administrators, and replies sent as Telegram replies appear in the website conversation.

Features include:

* A responsive Telegram-styled chat widget.
* Real-time message polling for visitor conversations.
* Telegram bot connection and webhook setup.
* One owner and multiple removable Telegram administrators.
* Configurable welcome, offline, and availability messages.
* Optional scheduled cleanup for closed conversations.

== Installation ==

1. Upload the `telegram-live-chat` folder to the `/wp-content/plugins/` directory, or install the ZIP file from the WordPress Plugins screen.
2. Activate Telegram Live Chat through the Plugins screen in WordPress.
3. Open **Telegram Live Chat > Settings** and save the token supplied by BotFather.
4. Select **Connect Telegram** to verify the bot and register its webhook.
5. Open **Telegram Live Chat > Administrators**, generate a pairing link, and open it from the Telegram account that will be the owner.
6. Generate another pairing link for each additional administrator. Administrators must reply to a visitor message in Telegram for their response to appear on the website.

== Frequently Asked Questions ==

= Is a Telegram account required? =

Yes. A Telegram account is required to connect the plugin with your Telegram bot and manage visitor conversations.

= Does the plugin need a Telegram bot? =

Yes. Create a bot with BotFather and enter its token in the plugin settings.

= How do I add another administrator? =

Open **Telegram Live Chat > Administrators**, select **Generate Pairing Link**, and send the link to the administrator. The first paired account remains the owner; later accounts are added as administrators.

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

