=== RequireWP ===
Contributors: jimbo2150
Tags: scripts, javascript
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Uses Require.js to load scripts asynchronously to increase site load speed.
Designed to work with HTTP/2.

== Description ==

This plugin extends the WP_Scripts class, adding some additional methods and filters that overload the existing script output to add Require.js syntax. It loads scripts via Require.js and by default defers until the DOMContentLoaded event fires (unless a manually added require() is called within the head/body and does not wait for the DOM to be loaded.
It is designed to work in conjunction with HTTP/2 but will function fine without it (though you may see slower load times).

**~20% decrease in page load time on a shared server with HTTP/2 and encryption!**

This plugin is design to assist developers in updating and creating JavaScript that is asynchronous - that is, scripts that can load in a different order and do not assume that their dependencies are available immediately. Use of the Asynchronous Module Definition (AMD) allows a script to define it’s dependencies and have them loaded asynchronously prior to the entire script or the initialization portion executes.

It is common for larger projects to concatenate together scripts however, they can end up a jumbled mess and smaller required functions are often duplicated (and redundant) across multiple scripts. HTTP/2 corrects this by allowing multiple resource files to be requested and downloaded within a single TCP window. As projects get larger and more diverse it is becoming more economical to load the individual components (modules) that are needed rather than download and execute one massive concatenated script file (which can also cause the browser to freeze up).

By default, RequireWP assumes all scripts (except the default WordPress scripts that are known to be AMD-compatible) are shims (not AMD-compatible) and will attempt to load them and their dependencies as such. You can define your module to not be configured as a shim via a plugin filter (you can instead define as a bundle, plugin, or simply needs requiring). See plugin website for more details.

*NOTE:* Some themes do not have their script dependencies set correctly. You may need to modify your theme and correct any missing dependencies or risk having your site load improperly or not at all (depending on what the scripts do) at various times. That is the nature of asynchronous loading.

*NOTE:* Not all plugins or themes may work with this plugin, depending on how scripts are being used, but most should. There are various "tricks" you can do to get them to work properly (filters are provided for most), however everyone should be encouraging the use of asynchronous script development.

*NOTE:* This plugin currently only replaces non-administrative (front-end) scripts with Require.js syntax.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/requirewp` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.

There are currently no user-interface settings.

== Frequently Asked Questions ==

= Why AMD? =

See Require.js’s website: http://requirejs.org/docs/whyamd.html

= How do I use Require.js? =
See Require.js’s website: http://requirejs.org/

== Changelog ==

= 1.0 =
* Initial release