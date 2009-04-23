<?php
/*
Plugin Name: WPML Multilingual CMS
Plugin URI: http://wpml.org/
Description: WPML Multilingual CMS
Author: ICanLocalize
Author URI: http://wpml.org/
Version: 0.9.7
*/

/*
    This file is part of ICanLocalize Translator.

    ICanLocalize Translator is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    ICanLocalize Translator is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with ICanLocalize Translator.  If not, see <http://www.gnu.org/licenses/>.
*/
       

define('ICL_SITEPRESS_VERSION', '0.9.7');
define('ICL_PLUGIN_PATH', dirname(__FILE__));
define('ICL_PLUGIN_URL', rtrim(get_option('siteurl'),'/') . '/wp-content/' . basename(dirname(dirname(__FILE__))) . '/' . basename(dirname(__FILE__)) );

if(defined('WP_ADMIN')){
    require ICL_PLUGIN_PATH . '/inc/php-version-check.php';
    if(defined('PHP_VERSION_INCOMPATIBLE')) return;
}
require ICL_PLUGIN_PATH . '/inc/not-compatible-plugins.php';
if(!empty($icl_ncp_plugins)){
    return;
}   
require ICL_PLUGIN_PATH . '/inc/upgrade.php';
require ICL_PLUGIN_PATH . '/inc/constants.inc';
require ICL_PLUGIN_PATH . '/inc/sitepress-schema.php';
require ICL_PLUGIN_PATH . '/inc/template-functions.php';
require ICL_PLUGIN_PATH . '/inc/icl-recent-comments-widget.php';
require ICL_PLUGIN_PATH . '/sitepress.class.php';
require ICL_PLUGIN_PATH . '/inc/functions.php';

$sitepress = new SitePress();
$sitepress_settings = $sitepress->get_settings();

// modules load
require ICL_PLUGIN_PATH . '/modules/cms-navigation/cms-navigation.php';
$iclCMSNavigation = new CMSNavigation();

if(isset($_POST['icl_enable_alp'])){
    $sitepress_settings['modules']['absolute-links']['enabled'] = intval($_POST['icl_enable_alp']);
    $sitepress->save_settings($sitepress_settings);
}
if($sitepress_settings['modules']['absolute-links']['enabled']){
    require ICL_PLUGIN_PATH . '/modules/absolute-links/absolute-links-plugin.php';
    $iclAbsoluteLinks = new AbsoluteLinksPlugin();
}

if(!empty($sitepress_settings['language_pairs'])){
    //require ICL_PLUGIN_PATH . '/modules/icl-translation/icl-translation.php';
}

// activation hook
register_activation_hook( __FILE__, 'icl_sitepress_activate' );
register_deactivation_hook(__FILE__, 'icl_sitepress_deactivate');