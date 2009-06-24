=== WPML Multilingual CMS ===
Contributors: ICanLocalize
Tags: CMS, navigation, menus, menu, dropdown, css, sidebar, pages, i18n, translation, localization, language, multilingual, WPML
Requires at least: 2.6
Tested up to: 2.8.0
Stable tag: 1.0.1

Turns any WordPress site into a fully featured multilingual content management system (CMS).

== Description ==

WPML makes it easy to build full multilingual websites with WordPress.
It integrates multilingual content management with robust navigation.

*Features*

 * **Multilingual content** support that allows selecting post/page languages and creating translations.
 * **CMS navigation** elements including drop down menus, breadcrumbs trail and sidebar navigation.
 * **Integrated content translation** makes it possible to run multilingual sites on tiny budgets and with no effort.
 * Robust links to posts and pages that never break.

*Highlights*

* When running a multilingual site, each page, post, tag or category has its own language. Translations for the same contents are grouped together, but not mixed in the database.
* CMS navigation elements work together to provide accurate and easy to use site-wide navigation.
* All drop down menus are implemented with pure CSS, are 100% HTML valid and support IE6, IE7, Firefox, Safari, Chrome, Opera and any other browser we tested on.
* Extra simple CSS for easy customization.

**Got a 140 character question? Ask [WPML on Twitter](http://twitter.com/wpml).**

= Multilingual content support =

One WordPress site will be able to run multiple languages. The plugin allows selecting which languages to include and how to arrange them (in language folders or independent domains).

To configure languages, go to WPML->Languages. Select which languages to include and the default site language (which is also the admin language).
The plugin will set the WP_LANG variable, so that the correct .mo files are loaded and the content language is set by WordPress.

Each page, post, tag or category will have a new section for translations. This section allows switching from one language to the other and adding translations to existing contents.

See more info in the [language setup howto](http://wpml.org/home/getting-started-guide/language-setup/) page.

= Content Translation =

This service is intended for people who don't want to translate the contents of their WordPress sites themselves.

**WPML's tranlsation interface can send all contents that need to be translated to professional (human) translators**. It's a paid service.

Because this translation service is highly integrated with the multilingual content management functions, it's completely effortless to use. Just one click and everything is sent to translation.
Translated contents are returned directly to WordPress and can be published immediately or stay held for review.

You can learn more about WPML's content translation in the [content translation guide](http://wpml.org/wordpress-translation/content-translation/).

**The translation service is an optional feature of the plugin**. People who need it can use it, others can translate themselves with ease.

= CMS navigation =

WPML makes it easy to add CMS navigation to your website. It will let you create:

* Top navigation with drop down menus.
* Breadcrumbs trail showing how to get from each page to the home page (and all pages in between).
* Sidebar navigation that shows pages next to the current one, arranged by their page hierarchy.

The sidebar element is widget ready. You can include it in the theme or drop as a widget. The top-navigation and breadcrumbs trail should be included in the theme.

For integration instruction in the theme, visit the [navigation usage](http://wpml.org/home/getting-started-guide/site-navigation/) page.

To style the drop down menu, you can use the [Drop down menu customization tool](http://wpml.org/support/drop-down-menu-customization/).

= Sticky links =

WPML can turn ordinary internal links into unbreakable sticky links. Sticky links track the URL of the page and update when the target page updates.

If you're linking to *example.com/about_us* and that page changes to *example.com/we_sell/company*, all Sticky links to that page will update immediately.

Learn more about it in the [Sticky links usage](http://wpml.org/home/getting-started-guide/sticky-links/) page.

= Template integration = 

Besides the hooks for adding the navigation elements the plugin defines the following constants which you can use in your template:

* `ICL_LANGUAGE_CODE` - the current language code (e.g. fr).
* `ICL_LANGUAGE_NAME` - the current language name in the current language (e.g. Français).
* `ICL_LANGUAGE_NAME_EN` - the current language name in English (e.g. French).

These constants can be used to create language dependent design for your site.

== Installation ==

1. Place the folder containing this file into the plugins folder
2. Activate the plugin from the admin interface

WPML needs to create tables in your database. These tables are used to hold the new language information. In order to use WPML, your MySQL user needs to have sufficient privileges to create new tables in the database.

For help, visit the [support forum](http://forum.wpml.org).

== Version History ==

* Version 0.9.2
	* First public release
* Version 0.9.3 - bug fix release
	* Fixed the Media Library (which the plugin disabled in the previous release).
	* Checks against collision with other plugins (CMS-navigation and Absolute-links).
	* Verified that per-directory language URLs can be implemented in WP.
	* Split Portuguese to Brazilian and Portuguese (European) Portuguese.
	* Fixed broken HTML in default category name for translations.
	* Verify that the plugin can create the required database tables and warn if not.
* Version 0.9.4 - bug fix release
	* Custom domains per language work correctly (forced to WPML defaults before)
	* Prevents from being activated on PHP4 (WPML only runs on PHP5)
* Version 0.9.6 - bug fix release
	* Fixed search in different languages
	* Fixed page edit links in different languages
	* Custom language domains don't change back to default when switching to different language negotiation scheme.
* Version 0.9.7 - bug fix release
	* Posts created via XML-RPC are assigned to the default language.
   	* Translated homepage displays correctly for blogs configured with 'language name passed as a parameter'.
	* Defined a language contants that can be used in templates - `ICL_LANGUAGE_CODE`, `ICL_LANGUAGE_NAME`, `ICL_LANGUAGE_NAME_EN`.
	* Split the stylesheet for the CMS Navigation into structure and design - users will be able to copy the design stylesheet and use it to override the plugin default style from their theme stylesheet.
	* Fixed incorrect query when selecting categories in the admin panel, causing extra records to be added to the translation table when editing categories inline.
* Version 0.9.8 - bug fix release
	* Fixed compatibility issues with Windows servers.
	* Fixed bug with sticky post - mysql query error when no sticky post existed.
	* Fixed search function.
	* Prev/Next links for category archive pages are now working again.
	* Add warning about disabled JavaScript (which is required for the plugin to work).
	* Added debug information for hunting down stubborn bugs.
	* Localized the admin section of the plugin to Spanish.
* Version 0.9.9 - bug fixes and custom language switcher
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
* Version 1.0.0 - First release with [content translation](http://wpml.org/wordpress-translation/content-translation/) ability
	* Added the capability to translate contents, including posts, pages, tags and categories.
	* Fixed HTML for the built in language selector.
	* Fixed 'preview' functionality when using different domains per language.
	* Fixed PHP error that popped when activating the plugin after upgrade.
	* Fixed drafts count problem (the plugin didn't count correctly the number of drafts per language).
* Version 1.0.1 - Bug fixes for translation interface
	* Fixed problems with all Asian languages and Norwegian.
	* Fixed missing tables problem for people who upgraded from 0.9.9.
	* Fixed CMS navigation drop down bug for IE6.
	* Improved the display for the translation dashboard.
* Version 1.0.2 - Bug fixes for multilingual system
	* Fixed language selector bug for some themes.
	* Major improvements for translation database integrity.
	* Fixed word count estimate for documents in Asian languages.
	* Added a new Troubleshoot module, which allows getting translation table status and to reset the plugin.
