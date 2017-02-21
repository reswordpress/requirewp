=== RequireWP ===
Contributors: jimbo2150
Tags: scripts, javascript
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Uses Require.js to load scripts faster and speed up WordPress / increase site load speed & performance. Designed to work with HTTP/2.

== Description ==

This plugin extends the WP_Scripts class, adding some additional methods and filters that overload the existing script output to add Require.js syntax. It loads scripts via Require.js and by default defers until the DOMContentLoaded event fires (unless a manually added require() is called within the head/body and does not wait for the DOM to be loaded.
It is designed to work in conjunction with HTTP/2 but will function fine without it (though you may see slower load times).

This plugin is design to assist developers in updating and creating JavaScript that is asynchronous - that is, scripts that can load in a different order and do not assume that their dependencies are available immediately. Use of the Asynchronous Module Definition (AMD) allows a script to define it’s dependencies and have them loaded asynchronously prior to the entire script or the initialization portion executes.

It is common for larger projects to concatenate together scripts however, they can end up a jumbled mess and smaller required functions are often duplicated (and redundant) across multiple scripts. HTTP/2 corrects this by allowing multiple resource files to be requested and downloaded within a single TCP window. As projects get larger and more diverse it is becoming more economical to load the individual components (modules) that are needed rather than download and execute one massive concatenated script file (which can also cause the browser to freeze up).

*NOTE:* This plugin currently only replaces non-administrative (front-end) scripts with Require.js syntax.

Plugin website: https://techie-jim.net/wordpress-plugins/requirewp/

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/requirewp` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.

There are currently no user-interface settings.

== Frequently Asked Questions ==

= Why AMD? =

See Require.js’s website: http://requirejs.org/docs/whyamd.html

= How do I use Require.js? =
See Require.js’s website: http://requirejs.org/

= My theme/plugins is/are not working correctly with this plugin, can it be fixed? =
A number of developers are not yet writing scripts to work asynchronously (unfortunatly). Until that happens some themes and plugins that embed scripts directly into the
body of the page or do not wait for scripts to be properly loaded may not function correctly. Additionally, some themes and plugins do not correctly mark their script
dependencies - simply assuming that since WordPress loads them synronously that simply having the register/enqueue functions in order is enough. In some cases you can
correct incorrect script dependencies. In other cases, where scripts are not waiting properly, you may not be able to correct the issue or will require modification of the
scripts - ask the theme or plugin developer of the non-working plugin to update their scripts to work with AMD! If you still feel it is a problem with this plugin,
drop a message in the support forum for this plugin.

= My theme/plugin script is showing up as a shim, but I wrote them to work with AMD, how can this be corrected? =
By default, RequireWP assumes all scripts (except the default WordPress scripts that are known to be AMD-compatible) are shims (not AMD-compatible) and will
attempt to load them and their dependencies as such. You can define your module to not be configured as a shim via a plugin filter (you can instead define
as a bundle, plugin, or simply needs requiring). See plugin website for more details.

== Changelog ==

= 1.0.4 =
- Set jQuery primary scripts to load synchronously - will fix the majority of plugin and theme issues.

= 1.0.3 =
- Removed an example script that was used to test.

= 1.0.2 =
- Corrected issue where schema/protocol-agnostic URIs were not handled properly
- Updated plugin readme files for keyword suggestions
- Moved some description notes into FAQ
- Incremented version to 1.0.2

= 1.0.1 =
Minor readme changes.

= 1.0 =
* Initial release