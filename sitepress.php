<?php
/*
Plugin Name: SitePress Multilingual CMS
Plugin URI: http://sitepress.org/
Description: SitePress
Author: ICanLocalize
Author URI: http://sitepress.org/
Version: 0.1
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
       

define('ICL_SITEPRESS_VERSION', '1.0');
define('ICL_PLUGIN_PATH', dirname(__FILE__));
define('ICL_PLUGIN_URL', rtrim(get_option('siteurl'),'/') . '/wp-content/' . basename(dirname(dirname(__FILE__))) . '/' . basename(dirname(__FILE__)) );

require ICL_PLUGIN_PATH . '/inc/constants.inc';
require ICL_PLUGIN_PATH . '/inc/sitepress-schema.php';
require ICL_PLUGIN_PATH . '/inc/template-functions.php';
require ICL_PLUGIN_PATH . '/inc/icl-recent-comments-widget.php';
require ICL_PLUGIN_PATH . '/sitepress.class.php';
require ICL_PLUGIN_PATH . '/inc/functions.php';
if(defined('WP_ADMIN')){
    require ICL_PLUGIN_PATH . '/inc/php-version-check.php';
}

$sitepress = new SitePress();

// modules load
require ICL_PLUGIN_PATH . '/modules/cms-navigation/cms-navigation.php';
$iclCMSNavigation = new CMSNavigation();

require ICL_PLUGIN_PATH . '/modules/absolute-links/absolute-links-plugin.php';
$iclAbsoluteLinks = new AbsoluteLinksPlugin();


// activation hook
register_activation_hook( __FILE__, 'icl_sitepress_activate' );
register_deactivation_hook(__FILE__, 'icl_sitepress_deactivate');
       
            
/*
//sample hook posts filter menu
add_action('restrict_manage_posts', 'sp_posts_language_filter_menu');


function sp_posts_language_filter_menu(){
    ?>
    <select class='postform'>
        <option>English&nbsp;</option>
        <option>French&nbsp;</option>
    </select>
    <?php
}
*/