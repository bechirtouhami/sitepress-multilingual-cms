<?php
require_once ABSPATH . WPINC . '/pluggable.php';
if(isset($_GET['debug_action']) && $_GET['nonce']==wp_create_nonce($_GET['debug_action']))
switch($_GET['debug_action']){
    case 'reset_pro_translation_configuration':
        $sitepress_settings = get_option('icl_sitepress_settings');
        
        $sitepress_settings['content_translation_languages_setup'] = false;
        $sitepress_settings['content_translation_setup_complete'] = false;        
        unset($sitepress_settings['content_translation_setup_wizard_step']);
        unset($sitepress_settings['site_id']);
        unset($sitepress_settings['access_key']);
        unset($sitepress_settings['translator_choice']);
        
        update_option('icl_sitepress_settings', $sitepress_settings);
        header("Location: admin.php?page=".basename(ICL_PLUGIN_PATH).'/menu/content-translation.php');
        exit;
    
}
  
?>