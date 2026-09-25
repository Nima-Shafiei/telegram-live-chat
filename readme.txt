=== TLC - Live chat with Telegram ===
Contributors: nimashafiee
Tags: telegram, live chat, customer support, chat, support
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress website chat with Telegram and reply to customer messages directly from Telegram.

== Description ==

**Read the Installation section first.** It walks you through connecting your bot and pairing your Telegram account, step by step.

TLC - Live chat with Telegram adds a chat widget to your website. Visitors send messages from your site, and your support team receives and answers them in Telegram.

Features include:

* Real-time website chat connected to your Telegram bot.
* Reply to visitors directly from Telegram.
* Pair an owner and additional Telegram administrators.
* Customize widget colors, title, icon, desktop and mobile size, position, and distance from the bottom of the screen.
* Set welcome and offline messages.
* No separate WordPress chat dashboard is needed to reply.

== Installation ==

1. Create a Telegram bot with [@BotFather](https://t.me/BotFather) and copy its token.
2. In WordPress, install and activate **TLC - Live chat with Telegram**.
3. Open **TLC > Settings**, paste the token into the **Bot Token** field, then select **Connect Telegram**.
4. Select **Save Changes**. After Telegram connects, the plugin opens the Administrators page and creates a temporary pairing link for the owner.
5. Open that link in Telegram and press **Start**. Your Telegram account is now the owner.
6. To add another administrator, select **Generate Pairing Link** and send the link to them. They must open it in Telegram and press **Start**. Pairing links expire after 10 minutes.
7. Customize the widget in **TLC > Settings** and select **Save Changes** to apply the settings.

When setup is complete, visitor messages are sent to your paired Telegram administrators. Reply to a message in Telegram to answer the visitor on your website.

== Screenshots ==

1. Enter your BotFather token and connect Telegram.
2. Save your plugin settings.
3. Open the owner pairing link in Telegram and press Start.
4. Generate a pairing link for another administrator.
5. View paired Telegram administrators and their roles.
6. Customize the widget appearance.

== External services ==

This plugin connects to the Telegram Bot API to validate the configured bot, configure its webhook, send visitor messages to paired administrators, and receive administrator replies.

When a visitor sends a message, the message, conversation ID, and page URL (when available) may be sent to Telegram. Telegram sends administrator replies and associated Telegram chat information to this site's webhook so the plugin can identify the administrator and conversation.

Telegram's terms and privacy policy:
https://telegram.org/tos
https://telegram.org/privacy

== Privacy ==

The plugin stores conversation and message data in the WordPress database. It uses a temporary visitor ID in the browser's session storage to associate messages with a conversation. The visitor's IP address may be temporarily processed for rate limiting and abuse prevention; it is not sent to Telegram. The Telegram bot token is stored in WordPress and used to authenticate Telegram Bot API requests.

== Frequently Asked Questions ==

= How do I connect my bot? =

Create a bot with [@BotFather](https://t.me/BotFather), enter its token in **TLC > Settings**, and select **Connect Telegram**. Save your settings, then open the owner pairing link in Telegram and press **Start**.

= How do I add another administrator? =

Open **TLC > Administrators**, select **Generate Pairing Link**, and send the temporary link to the administrator. They need to open it in Telegram and press **Start**.

= Can I customize the chat widget? =

Yes. The settings let you choose the position, colors, title, icon, desktop and mobile dimensions, and distance from the bottom of the screen.

== Changelog ==

= 1.1.0 =
* Added chat widget appearance settings for position, colors, title direction, desktop and mobile size, bottom spacing, and custom icon.
* Improved Telegram connection flow with automatic navigation to administrator pairing.
* Added cleanup settings for conversation data.
* Added a LinkedIn link for the plugin author.

= 1.0.0 =
* Initial stable release.

= 0.1.7 =
* Updated the chat widget and built-in messages for English-language sites.

= 0.1.6 =
* Added support for multiple Telegram administrators.
* Refined the Telegram-inspired chat interface.
* Resolved Plugin Check compatibility findings.
