<?php
function icl_get_home_url(){
    global $sitepress;
    return $sitepress->language_url($sitepress->get_current_language());
} 


function icl_get_languages(){
    global $sitepress, $wpdb;
    $lang = $sitepress->language_selector(true);
    $langs = array();
    foreach($lang as $l){
        $flag_url = ICL_PLUGIN_URL . '/res/flags/'.$l['code'].'.png';
        
        $translated_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$l['code']}' AND display_language_code='{$l['code']}'");
        if(!$translated_name) $translated_name = $l['english_name'];    
        $native_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$l['code']}' AND display_language_code='{$sitepress->get_current_language()}'");
        if(!$native_name) $native_name = $lang['english_name'];    
        
        $langs[] = array(
            'active'=> 1,
            'native_name' => $native_name,
            'translated_name' => $translated_name,
            'language_code' => $l['code'],
            'country_flag_url' => $flag_url,
            'url'=>$l['translated_url']
        );
    }
    
    return $langs;
} 
?>
