<?php
function icl_get_home_url(){
    global $sitepress;
    return $sitepress->language_url($sitepress->get_current_language());
} 


function icl_get_languages($a=''){
    if($a){
        parse_str($a, $args);        
    }
    global $sitepress, $wpdb;
    $lang = $sitepress->language_selector(true, $args);
    $langs = array();
    foreach($lang as $l){
        $flag = $wpdb->get_row("SELECT flag, from_template FROM {$wpdb->prefix}icl_flags WHERE lang_code='{$l['code']}'");
        if($flag->from_template){
            $flag_url = get_bloginfo('template_directory') . '/images/flags/'.$flag->flag;
        }else{
            $flag_url = ICL_PLUGIN_URL . '/res/flags/'.$flag->flag;
        }
        
        
        $native_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$l['code']}' AND display_language_code='{$l['code']}'");        
        if(!$native_name) $native_name = $lang['english_name'];    
        $translated_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$l['code']}' AND display_language_code='{$sitepress->get_current_language()}'");        
        if(!$translated_name) $translated_name = $l['english_name'];    
        
        $active = $sitepress->get_current_language()==$l['code'] ?'1':0;
        
        $langs[] = array(
            'active'=> $active,
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
