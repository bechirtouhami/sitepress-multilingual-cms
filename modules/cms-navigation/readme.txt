=== CMS Navigation ===
Contributors: ICanLocalize
Tags: CMS, navigation, menus, menu, dropdown, css, sidebar, pages, SitePress
Requires at least: 2.3
Tested up to: 2.7
Stable tag: 1.4

Out-of-the-box support for full CMS navigation in your WordPress site including drop down menus, breadcrumbs trail and sidebar navigation.

== Description ==

Do you want to use WordPress to create a full website, with easy navigation and menus?
This plugin will let you add essential navigation functions to your template, including:

 * Top navigation bar, listing the top-level pages and their children (as drop-down items).
 * Breadcrumbs trail navigation that shows the path to the current page all the way from the home page.
 * Left navigation that shows where the visitor is next to the page's parent and nearby pages.

No configuration is required. To use, add the calls that create each navigation element to your template.
These calls can be added to any WP template

There are three template functions that can be used for displaying the navigation sections
 
= Drop down top menu =
Function:
`cms_navigation_menu_nav($order='menu_order', $show_cat_menu=false, $catmenu_title='News')`
 
This will display the top navigation - the top level pages with their sub pages as drop down elements.
Additionally - if specified - the menu will include the top level post categories at the end.

Normally, this function is added to your header.php file, so that it applies to the entire website.
 
Examples:

 * `<?php cms_navigation_menu_nav() ?>` - include without the categories menu.
 * `<?php cms_navigation_menu_nav('post_title') ?>` - sort items by title.
 * `<?php cms_navigation_menu_nav('menu_order',true,'News') ?>` - sort items according to the 'order' field.
 
= Breadcrumbs trail navigation =
Function:
`cms_navigation_breadcrumb()`
 
This will display a path from the current page all the way to the home page.
It's useful for visitors who land in your website to know where they are and be able to navigate to relevant pages.

__For posts__

`Home >> CATEGORY-NAME >> Post title`

__For pages__

`Home >> Parent pages... >> Page title`

All the items back to the home page will be clickable.

You can add this function to single.php and page.php so that it will produce trail navigation for every page or post.

= Sidebar navigation = 
Function:
`cms_navigation_page_navigation($order='menu_order')`
 
This will display the sidebar navigation for pages.
It will show a tree created the page parents and it's 'brother' pages (other children to the same parent).

This function should be added to page.php, as it provides local navigation between pages.

= Live example using this plugin =

[Baripedia](http://www.baripedia.com "Tourist resource site for the city of Bariloche") is using this plugin (and other CMS related plugins we've written).

= Demo theme using these plugins =
We created a [modified version of WordPress Default theme with CMS navigation](http://sitepress.org/files/2009/01/cms-default.zip). You're welcome to download and try it (the plugin should be enabled for the navigation to work).
* Note: this theme will [auto-install](http://sitepress.org/home/bundling-plugins-with-themes/) the CMS Navigatin plugin.

The files we edited are:

* style.css: added styling for the sidebar navigation.
* header.php: added the top navigation.
* sidebar.php: added the sidebar pages navigation.
* index.php, search.php, page.php and single.php: added the breakcrumbs trail navigation.
* functions.php: added error message to display in case the plugin is missing.

You can find detailed instructions and code to add to your theme in the plugin page.

= SitePress =
This plugin is part of [SitePress](http://sitepress.org) - a collection of plugins that turn WordPress into a fully featured multilingual content management system.

== Installation ==

1. Place the folder containing this file into the plugins folder
2. Activate the plugin from the admin interface

== Screenshots ==

1. All three navigation functions enabled (highlighted in red).
2. An open drop-down menu.

== Frequently Asked Questions ==

= Does the plugin work with any theme? =

You can add the navigation functions that the plugin creates to any theme. As a demo, we've added it to the WordPress Default theme.

= Where can I find detailed instructions =

Click on the plugin page. It shows detailed instructions of what PHP code needs to be added and where.

= How do I customize the drop down menus and other stuff? =

There are a `css` and `img` folders in the plugin install folder. Don't edit them.
Instead, you can provide your own CSS in your theme which will override these defaults. If you edit the plugin files, your edits will be lost when you upgrade the plugin.== Version History ==

* Version 0.1
	* First public release.
* Version 0.2
	* Removed some of unneeded formatting.
	* Added option to set the tag around sidebar navigation heading.
* Version 1.2
	* Handles setting static pages for homepage and blog page.
* Version 1.2.1
	* Added static home page to top navigation
* Version 1.3
	* Works with IE6 as well as other browsers (didn't support IE6 before)
	* Removed the gradient and simplified the CSS for customizing the menus
	* The top menu HTML has changed. If the encapsulating DIV has a background, you may need to change or remove it.
* Version 1.4
	* Added feature to exclude pages from the top navigation.
	* Added GUI for controlling page settings (instead of editing custom fields).
