=== SitePress Multilingual CMS ===
Contributors: ICanLocalize
Tags: CMS, navigation, menus, menu, dropdown, css, sidebar, pages, i18n, translation, localization, language, multilingual, SitePress
Requires at least: 2.6
Tested up to: 2.7.1
Stable tag: 0.9.3

Turns any WordPress site into a fully featured multilingual content management system (CMS).

== Description ==

WordPress is a great blogging platform with a potential of being an easy to use content management system. SitePress helps it go the extra mile.

*Features*

 * **Multilingual content** support based on Drupal i18n architecture.
 * **CMS navigation** allows adding drop down menus, breadcrumbs trail and sidebar navigation (all wigetized).
 * Creates internal **Sticky Links** so that they never break.

*Highlights (in no particular order)*

* When running a multilingual site, each page, post, tag or category has its own language. Translations for the same contents are grouped together, but not mixed in the database.
* CMS navigation elements work together to provide accurate and easy to use site-wide navigation.
* All drop down menus are implemented with pure CSS, are 100% HTML valid and support IE6, IE7, Firefox, Safari, Chrome, Opera and any other browser we tested on.
* Extra simple CSS for easy customization.

= Multilingual content support =

One WordPress site will be able to run multiple languages. The plugin allows selecting which languages to include and how to arrange them (in language folders or independent domains).

To configure languages, go to SitePress->Languages. Select which languages to include and the default site language (which is also the admin language).
The plugin will set the WP_LANG variable, so that the correct .mo files are loaded and the content language is set by WordPress.

Each page, post, tag or category will have a new section for translations. This section allows switching from one language to the other and adding translations to existing contents.

See more info in the [language setup howto](http://sitepress.org/home/getting-started-guide/language-setup/) page.

= CMS navigation =

SitePress makes it easy to add CMS navigation to your website. It will let you create:

* Top navigation with drop down menus.
* Breadcrumbs trail showing how to get from each page to the home page (and all pages in between).
* Sidebar navigation that shows pages next to the current one, arranged by their page hierarchy.

All these elements are widget ready. You can include them in the theme or drop as widgets.

For integration instruction in the theme, visit the [navigation usage](http://sitepress.org/home/getting-started-guide/site-navigation/) page.

= Sticky links =

SitePress can turn ordinary internal links into unbreakable sticky links. Sticky links track the URL of the page and update when the target page updates.

If you're linking to *example.com/about_us* and that page changes to *example.com/we_sell/company*, all Sticky links to that page will update immediately.

Learn more about it in the [Sticky links usage](http://sitepress.org/home/getting-started-guide/sticky-links/) page.

== Installation ==

1. Place the folder containing this file into the plugins folder
2. Activate the plugin from the admin interface

== Version History ==

* Version 0.9
	* First public release