=== WPML Multilingual CMS ===
Contributors: ICanLocalize
Donate link: http://wpml.org/home/want-to-help/
Tags: CMS, navigation, menus, menu, dropdown, css, sidebar, pages, i18n, translation, localization, language, multilingual, WPML
Requires at least: 2.6
Tested up to: 2.8.4
Stable tag: 1.2.1

Allows building complete multilingual sites with WordPress.

== Description ==

**WPML makes multilingual content management easy, just like running a site in one language.**

= Features =

 * Turns a single WordPress install into a [multilingual site](http://wpml.org/wordpress-translation/).
 * Built-in theme localization without .mo files.
 * Comment translation allows you to moderate and reply to comments in your own language.
 * Integrated [professional translation](http://wpml.org/wordpress-translation/content-translation/) (optional feature for folks who need help translating).
 * Includes [CMS navigation](http://wpml.org/wordpress-cms-plugins/cms-navigation-plugin/) elements for drop down menus, breadcrumbs trail and sidebar navigation.
 * [Robust links](http://wpml.org/wordpress-cms-plugins/absolute-links-plugin/) to posts and pages that never break.

= Highlights =

* Translations are grouped together and not mixed in the database.
* CMS navigation elements provide accurate and easy to use site-wide navigation.
* Simple CSS for easy [customization](http://wpml.org/support/drop-down-menu-customization/).
* An active [community](http://forum.wpml.org) of developers building professional multilingual sites.

WPML makes it possible to turn WordPress blogs multilingual in a few minutes with no knowledge of PHP or WordPress.
Its advanced features allow professional web developers to build full multilingual websites.

WPML's [showcase](http://wpml.org/showcase/) includes samples of blogs, full websites and even multilingual BuddyPress portals.

== Installation ==

1. Place the folder containing this file into the plugins folder
2. Activate the plugin from the admin interface

WPML needs to create tables in your database. These tables are used to hold the new language information. In order to use WPML, your MySQL user needs to have sufficient privileges to create new tables in the database.

For help, visit the [support forum](http://forum.wpml.org).

== Frequently Asked Questions ==

= Can I translate myself, or do I need to pay for it? =

You can certainly translate your site yourself. The professional translation is an optional feature intended for people who don't want to translate themselves.

If you're translating your site yourself, just ignore that option.

= Languages list is empty =

This is usually due to the languages table missing or empty. To check what's causing this problem:

1. Disable WPML.
2. Enable [debug mode](http://wpml.org/support/debugging-wpml/).
3. Activate WPML again. This time, you'll see the source of the problem.

= Theme localization not working =

Have a look at this [theme localization guide](http://wpml.org/2009/05/wordpress-theme-localization/).

= Languages per directories are disabled =

To be able to use languages in directories, you should use a non-default permlink structure.
Also, go through the [detailed description](http://wpml.org/support/cannot-activate-language-directories/).

== Screenshots ==

You can find screen shots of WPML in our [press kit](http://wpml.org/home/press-kit/).

== Changelog ==

= 1.3.0 =
* Added translation for comments.
* Allow associating existing contents as translation.
* Modified the layout of the translation boxes.
* Added a setup wizard.
* Fixed bugs that prevented WPML to work with MySQL in strict mode.
* Fixed bugs that prevented working with some other plugins.

= 1.2.1 =
* Allows specifying the locale for the default language.
* Added a theme integration file - docs/theme-integration/wpml-integration.php.
* Added an input for affiliate ID for themes.
* Simplified the setup for [professional translation](http://wpml.org/wordpress-translation/content-translation/).

= 1.2.0 =
* Adds theme localization.
* ICanLocalize translation integration for theme and widget texts.
* Added string translation import and export using .po files.
* Fix for empty language tables bug.

= 1.1.0 =
* Adds [translation for general texts](http://wpml.org/wordpress-translation/translation-for-texts-by-other-plugins-and-themes/), such as title, tagline and widgets.
* Can [translate custom fields](http://wpml.org/wordpress-translation/translating-custom-fields/) by ICanLocalize.
* Added an overview page, for quick access to all functions and a snapshot of WPML's status.

= 1.0.4 =
* Fixed the bug which caused errors when upgrading the plugin from previous versions.
* Fixed category and tag mess when using Quick-edit.
* Admin pages run much faster due to statistics caching and faster DB queries.
* Fixed name of blog page in cms-navigation section.
* Fixed compatibility with openID plugin.
* Fixed a bug that was caused when pages/posts had no title.
* Added icl_object_id which returns the ID of translated objects.
* Fixed permlinks for newly created posts (autosave by WordPress).
* Fixed bug which prevented sub-pages from being excluded from the navigation.
* Simplified the professional translation setup page.

= 1.0.3 =
* Added a hook for adding [custom HTML in menu items](http://wpml.org/wordpress-cms-plugins/cms-navigation-plugin/custom-html-for-menu-items/).
* Added a function for creating [multilingual links in themes](http://wpml.org/home/getting-started-guide/hard-coded-theme-links/).
* Cleaned up translation table, in case posts were deleted while WPML is inactive.
* Reverting to HTTP communication instead of HTTPs if a firewall is blocking us.

= 1.0.2 =
* Fixed language selector bug for some themes.
* Major improvements for translation database integrity.
* Fixed word count estimate for documents in Asian languages.
* Added a new Troubleshoot module, which allows getting translation table status and to reset the plugin.

= 1.0.1 =
* Fixed problems with all Asian languages and Norwegian.
* Fixed missing tables problem for people who upgraded from 0.9.9.
* Fixed CMS navigation drop down bug for IE6.
* Improved the display for the translation dashboard.

= 1.0.0 =
* Added the capability to translate contents, including posts, pages, tags and categories.
* Fixed HTML for the built in language selector.
* Fixed 'preview' functionality when using different domains per language.
* Fixed PHP error that popped when activating the plugin after upgrade.
* Fixed drafts count problem (the plugin didn't count correctly the number of drafts per language).

= 0.9.9 =
* Fixed problems with WordPress Gallery.
* Fixed error when using a static home page that's not translated (now, returns the 404 page).
* Fixed bug that prevented sticky links to work for pages.
* Fixed a CSS error in language-selector.css.
* Fixed a bug which created the RSS feed to have invalid XML (be an invalid feed).
* Fixed a bug which caused the default language to reset after plugin upgrade.
* Added WP-Http class for compatibility with WP 2.6.
* Added country flags as an option for the language switcher.
* Added a function that returns the languages information for [building custom language switchers](http://wpml.org/home/getting-started-guide/language-setup/custom-language-switcher/).
* Added the language name as the class for each entry in the languages selector, so that they can be styled individually.

= 0.9.8 =
* Fixed compatibility issues with Windows servers.
* Fixed bug with sticky post - mysql query error when no sticky post existed.
* Fixed search function.
* Prev/Next links for category archive pages are now working again.
* Add warning about disabled JavaScript (which is required for the plugin to work).
* Added debug information for hunting down stubborn bugs.
* Localized the admin section of the plugin to Spanish.

= 0.9.7 =
* Posts created via XML-RPC are assigned to the default language.
* Translated homepage displays correctly for blogs configured with 'language name passed as a parameter'.
* Defined a language contants that can be used in templates - `ICL_LANGUAGE_CODE`, `ICL_LANGUAGE_NAME`, `ICL_LANGUAGE_NAME_EN`.
* Split the stylesheet for the CMS Navigation into structure and design - users will be able to copy the design stylesheet and use it to override the plugin default style from their theme stylesheet.
* Fixed incorrect query when selecting categories in the admin panel, causing extra records to be added to the translation table when editing categories inline.

= 0.9.6 =
* Fixed search in different languages
* Fixed page edit links in different languages
* Custom language domains don't change back to default when switching to different language negotiation scheme.

= 0.9.4 =
* Custom domains per language work correctly (forced to WPML defaults before)
* Prevents from being activated on PHP4 (WPML only runs on PHP5)

= 0.9.3 =
* Fixed the Media Library (which the plugin disabled in the previous release).
* Checks against collision with other plugins (CMS-navigation and Absolute-links).
* Verified that per-directory language URLs can be implemented in WP.
* Split Portuguese to Brazilian and Portuguese (European) Portuguese.
* Fixed broken HTML in default category name for translations.
* Verify that the plugin can create the required database tables and warn if not.

= 0.9.2 =
* First public release
