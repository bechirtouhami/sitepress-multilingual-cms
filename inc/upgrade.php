<?php
if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '0.9.3', '<')){
    require_once(ICL_PLUGIN_PATH . '/inc/lang-data.inc');      
    $wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET english_name='Norwegian Bokmål', code='nb' WHERE english_name='Norwegian'");      
    foreach($langs_names['Norwegian Bokmål']['tr'] as $k=>$display){        
        if(!trim($display)){
            $display = 'Norwegian Bokmål';
        }
        $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code'=>'nb', 'display_language_code'=>$lang_codes[$k], 'name'=>$display));          
    }   
    
    $wpdb->insert($wpdb->prefix . 'icl_languages', array('code'=>'pa', 'english_name'=>'Punjabi'));       
    foreach($langs_names['Punjabi']['tr'] as $k=>$display){        
        if(!trim($display)){
            $display = 'Punjabi';
        }
        $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code'=>'pa', 'display_language_code'=>$lang_codes[$k], 'name'=>$display));          
    }   

    $wpdb->insert($wpdb->prefix . 'icl_languages', array('code'=>'pt-br', 'english_name'=>'Portuguese, Brazil'));       
    foreach($langs_names['Portuguese, Brazil']['tr'] as $k=>$display){        
        if(!trim($display)){
            $display = 'Portuguese, Brazil';
        }
        $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code'=>'pt-br', 'display_language_code'=>$lang_codes[$k], 'name'=>$display));          
    }   
    
    $wpdb->insert($wpdb->prefix . 'icl_languages', array('code'=>'pt-pt', 'english_name'=>'Portuguese, Portugal'));       
    foreach($langs_names['Portuguese, Portugal']['tr'] as $k=>$display){        
        if(!trim($display)){
            $display = 'Portuguese, Portugal';
        }
        $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code'=>'pt-pt', 'display_language_code'=>$lang_codes[$k], 'name'=>$display));          
    }   
    
    
}
/*
if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '0.9.9', '<')){
    $iclsettings = get_option('icl_sitepress_settings');
    $iclsettings['icl_lso_flags'] = 0;
    $iclsettings['icl_lso_native_lang'] = 1;
    $iclsettings['icl_lso_display_lang'] = 1;    
    update_option('icl_sitepress_settings',$iclsettings);
}
*/

if(version_compare(get_option('icl_sitepress_version'), ICL_SITEPRESS_VERSION, '<')){
    update_option('icl_sitepress_version', ICL_SITEPRESS_VERSION);
}
?>
