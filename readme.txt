=== Performant Transients ===
Contributors: peterwilsoncc
Tags: performance, transients
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.0.0
License: MIT

Reduce the number of database calls querying transients.

== Description ==
This is a utility plugin for backporting a minor performance improvement scheduled for inclusion in WordPress 6.6 to earlier versions.

In WordPress 6.4 the function `wp_prime_option_caches()` was introduced to allow for multiple options to be queried in a single database call.

On sites without a persistent cache, temporary transients are stored in two options. One containing the transient itself, the other containing the timeout. These are stored as `_transient_[transient name]` and `_transient_timeout_[transient name]`.

In WordPress 6.6 and later, these options are primed by a single database call using the priming function. This plugin can be used on WordPress 6.4 and 6.5 to backport the functionality.

While a mild improvement on sites making limited use of transients, this can significantly reduce the number of database queries on sites running plugins making heavy use of transients.


== Frequently Asked Questions ==
= I can't see this plugin's admin menu =

This plugin doesn't include any options to configure so no admin menu is required.

Simply install the plugin and activate.

== Changelog ==
= 1.0.0 =
Initial release
