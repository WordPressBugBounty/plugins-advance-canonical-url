=== Advance Canonical URL ===
Contributors: usmanaliqureshi
Tags: canonical, canonical url, duplicate content, seo, custom canonical
Requires at least: 5.2
Tested up to: 7.1
Stable tag: 1.1.1
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Choose reliable automatic canonical URLs or set a preferred URL for individual posts, pages, and public post types.

== Description ==

Advance Canonical URL gives you a focused way to manage canonical links without turning a simple SEO task into a large settings project.

Use the automatic strategy for dependable site-wide output, or enable custom control when selected content needs to point to a different preferred URL. Empty or invalid custom values safely fall back to the automatic URL.

= Highlights =

* Outputs one canonical link on eligible public pages.
* Uses WordPress canonical URLs for singular content when available.
* Supports optional per-content overrides for posts, pages, and public custom post types.
* Removes query parameters from canonical URLs by default, with an option to preserve them.
* Skips search results, 404 pages, and WordPress admin screens.
* Validates custom URLs and accepts only complete HTTP or HTTPS URLs.
* Saves the settings screen without a page refresh, with a standard WordPress save fallback.
* Includes a compact current-policy summary and responsive admin interface.
* Keeps every plugin feature free. Optional one-time support payments do not unlock extra features.

For best results, use only one plugin or theme feature that outputs canonical tags. If an SEO plugin already manages canonical URLs, disable one of the two canonical providers to avoid duplicate tags.

== Installation ==

1. Upload the `advance-canonical-url` folder to `/wp-content/plugins/`, or install the plugin through the WordPress Plugins screen.
2. Activate **Advance Canonical URL**.
3. Open **Settings > Canonical URL**.
4. Choose **Automatic** or **Custom control**, decide how query parameters should be handled, and save.
5. When custom control is enabled, edit any supported post, page, or public custom post type to set an optional custom canonical URL.

== Frequently Asked Questions ==

= Does the plugin work without sharing usage data? =

Yes. Freemius displays an optional consent screen after activation. You can skip it and continue using every feature.

= Are there any paid or locked features? =

No. Supporter options are voluntary one-time payments. They do not change the plugin or unlock functionality.

= When is the support checkout loaded? =

The external Freemius checkout script is loaded only after an administrator opens the support chooser and selects an amount.

= What happens if a custom canonical URL is empty or invalid? =

The plugin uses the automatic canonical URL instead. Custom values must be complete HTTP or HTTPS URLs.

= Does it support custom post types? =

Yes. Custom control is available for public post types that are visible in the WordPress admin.

= Can I keep query parameters? =

Yes. Disable **Remove query parameters** in the settings screen when the query string is part of the preferred URL.

= Can I use this with an SEO plugin? =

Avoid enabling two canonical providers at the same time. If another SEO plugin or your theme already outputs canonical tags, disable that canonical feature or deactivate this plugin.

== Screenshots ==

1. Responsive settings with automatic and per-content strategies, query handling, and the current policy summary.
2. Per-content canonical controls with a clear automatic fallback.
3. More focused WordPress tools from the same developer.

== Privacy ==

The plugin includes the Freemius SDK for optional usage analytics and voluntary supporter payments. Analytics are enabled only after administrator consent. The supporter checkout is loaded only when an administrator selects a support amount. Skipping consent or payments does not limit plugin functionality.

For details about Freemius data handling, see the privacy policy linked from the consent and checkout interfaces.

== Changelog ==

= 1.1.1 =
* Updated Freemius SDK to the latest version.
* Tested with WordPress 7.1.

= 1.1.0 =
* Rebuilt the settings screen with a responsive, accessible interface.
* Added AJAX settings saves with a standard form-submit fallback.
* Added clear automatic and per-content canonical strategies.
* Added a redesigned canonical URL editor for public content types.
* Added strict HTTP and HTTPS validation with automatic fallback behavior.
* Improved singular canonical generation using WordPress core APIs.
* Added safer handling for query strings, invalid requests, search pages, and 404 pages.
* Added an optional Freemius consent flow and voluntary one-time supporter checkout.
* Added a More Plugins screen with install and activation status.
* Updated WordPress.org artwork, screenshots, translation template, and compatibility information.

= 1.0.5 =
* Tested with WordPress 7.0.
* Added Spanish, French, German, Italian, and Portuguese translations.
* Addressed issues reported by Plugin Check.

= 1.0.4 =
* Fixed canonical URL generation.
* Tested with WordPress 6.7.x.

= 1.0.3 =
* Tested with WordPress 6.7.x and current PHP versions.

= 1.0.2 =
* Fixed PHP warnings.
* Tested with WordPress 6.1.1 and PHP 8.0.

= 1.0.1 =
* Added the translation template.
* Added query string settings.

= 1.0.0 =
* Initial release.
