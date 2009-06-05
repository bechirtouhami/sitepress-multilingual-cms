<?php
require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
require_once ICL_PLUGIN_PATH . '/modules/icl-translation/constants.inc';
require_once ICL_PLUGIN_PATH . '/modules/icl-translation/icl-translation-functions.php';

if(isset($_POST['translation_dashboard_filter'])){
    $icl_translation_filter = $_POST['filter'];
}

if(isset($_REQUEST['icl_ajx_req'])){
    include dirname(__FILE__) . '/icl-ajx-requests.php';
}

add_action('save_post', 'icl_translation_save_md5', 12); //takes a lower priority - allow other actions to happen
add_action('delete_post', 'icl_translation_delete_post');
if($sitepress_settings['existing_content_language_verified']){
    add_action('admin_menu', 'icl_translation_admin_menu');
}
add_action('admin_print_scripts', 'icl_translation_js');
add_filter('xmlrpc_methods','icl_add_custom_xmlrpc_methods');

add_action('icl_post_languages_options_before', 'icl_display_post_translation_status');

add_action('post_submitbox_start', 'sh_post_submitbox_start');


wp_enqueue_style('icl-translation-style', ICL_PLUGIN_URL . '/modules/icl-translation/css/style.css');


if(isset($_POST['poll']) && $_POST['poll']==1){
    icl_poll_for_translations();
}



?>