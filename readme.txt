=== Auto Links for WordPress ===
Contributors: leocolomb
Donate link: https://www.patreon.com/LeoColomb
Tags: auto links, seo, links, categories, pages, posts, tags
Requires at least: 5.0
Tested up to: 6.0
Requires PHP: 7.2
Stable tag: trunk
License: ISC
License URI: https://github.com/LeoColomb/wp-auto-links/blob/master/LICENSE

An inner links generator for WordPress.

== Description ==

This plugin adds internal and external links into post and page, based on different sources:

* Custom keywords
* Posts
* Pages
* Categories
* Tags

The plugin provides simple and standard interfaces:

* Clean & clear admin page that allow highly customizable usage
* Useful CLI commands
* Respect PHP standards, web standards and security standards

Low load impact in mind, some requirements are requested:

* PHP 7.2+ (⚠ requirement)
* WordPress 5.0+ (⚠ requirement)
* Object Cache handler (⚠ requirement)

== Installation ==

= Requirements =

* PHP 7.2+
* WordPress 5.0+
* Object Cache handler

= Manual =

1. Upload the plugin by cloning or copying this repository to the `/wp-content/plugins` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Auto Links screen to configure the plugin

= Automatic =

This plugin can be installed with Composer on a Composer-managed WordPress stack.
```
composer require leocolomb/wp-auto-links
```

== Frequently Asked Questions ==

= Amazing! How to support your work? =

Thanks!
First of all you can contribute on [the GitHub repository](https://github.com/LeoColomb/wp-auto-links).
If you prefer make a donation, [please see my Patreon page](https://www.patreon.com/LeoColomb)!

= My system runs an older version of a software specified in the plugin requirements. =

These strong requirements are required to ensure the plugin is working efficiently.
If the requirements are not satisfied, the plugin may not work as expected.

= The plugin seems to work but no link are pushed on the content. =

Check your Object Cache handler.
A valid Object Cache handler is required to manage data.

== Screenshots ==

1. Auto Links for WordPress admin interface.

== Changelog ==

[Checkout the complete changelog here](https://github.com/LeoColomb/wp-auto-links/releases).

== Upgrade Notice ==

If you upgrade from another plugin like WPA SEO Auto Linker, you can just copy and past your custom links.
Auto migration is not supported.

== Contributors ==

[Léo Colombaro](https://colombaro.fr)
