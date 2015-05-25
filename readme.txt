=== Plugin Name ===
Contributors: NoseGraze
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=L2TL7ZBVUMG9C
Tags: social, twitter, facebook, pinterest, stumbleupon, social share
Requires at least: 3.0
Tested up to: 4.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple, unstyled social share icons for theme designers.

== Description ==

Naked Social Share allows you to insert plain, unstyled social share buttons for Twitter, Facebook, Pinterest, and StumbleUpon after each post. The icons come with no styling, so that you -- the designer -- can style the buttons to match your theme.

There are a few simple options in the settings panel:

* Load default styles - This includes a simple stylesheet that applies a few bare minimum styles to the buttons.
* Load Font Awesome - Naked Social Share uses Font Awesome for the social share icons.
* Disable JavaScript - There is a small amount of JavaScript used to make the buttons open in a new popup window when clicked.
* Automatically add buttons - You can opt to automatically add the social icons below blog posts or pages.
* Twitter handle - Add your Twitter handle to include a "via @YourHandle" message in the Tweet.

If you want to display the icons manually in your theme, do so by placing this code inside your theme file where you want the icons to appear:

`<?php naked_social_share_buttons(); ?>`

== Installation ==

1. Upload `naked-social-share` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Adjust the settings in Settings -> Naked Social Share
1. If you want to display the buttons manually in your theme somewhere, insert this into your theme file where you want the buttons to appear: `<?php naked_social_share_buttons(); ?>`

== Frequently Asked Questions ==

= How can I add the icons to my theme manually? =

Open up your theme file (for example, `single.php`) and place this code exactly where you want the icons to appear: `<?php naked_social_share_buttons(); ?>`

= Why aren't my share counters updating? =

The share counters are cached for 3 hours to improve loading times and to avoid making API calls on every single page load.

== Screenshots ==

1. The view of the settings panel.
2. A screenshot of the social share icons automatically added to the Twenty Fifteen theme. This also shows the default button styles applied.

== Changelog ==

= 1.0.5 =
Made some code adjustments to the Naked_Social_Share_Buttons class so you can fetch the buttons for any post object.

= 1.0.4 =
* Fixed a problem with the caching not working properly.

= 1.0.3 =
* Fixed an undefined property notice when the post is not submitted to StumbleUpon.
* Added class names to each social button's `li` tag in case you want to style them differently.
* Tested with WordPress 4.2.

= 1.0.2 =
* Replaced `urlencode` functions with `esc_url_raw`, as urlencode was preventing the social share requests from working properly.

= 1.0.1 =
* Removed some debugging code that was left behind.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.5 =
Made some code adjustments to the Naked_Social_Share_Buttons class so you can fetch the buttons for any post object.