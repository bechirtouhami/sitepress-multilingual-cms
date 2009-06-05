<?php
if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '0.9.3', '<')){
    require_once(ICL_PLUGIN_PATH . '/inc/lang-data.inc');      
    $wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET english_name='Norwegian Bokmål', code='nb' WHERE english_name='Norwegian'");      
    foreach($langs_names['Norwegian Bokm?l']['tr'] as $k=>$display){        
        if(!trim($display)){
            $display = 'Norwegian Bokm?l';
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

if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '0.9.9', '<')){
    $iclsettings = get_option('icl_sitepress_settings');
    $iclsettings['icl_lso_flags'] = 0;
    $iclsettings['icl_lso_native_lang'] = 1;
    $iclsettings['icl_lso_display_lang'] = 1;    
    update_option('icl_sitepress_settings',$iclsettings);
    
    // flags table
   $table_name = $wpdb->prefix.'icl_flags';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = "
            CREATE TABLE `{$table_name}` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `lang_code` VARCHAR( 10 ) NOT NULL ,
            `flag` VARCHAR( 32 ) NOT NULL ,
            `from_template` TINYINT NOT NULL DEFAULT '0',
            UNIQUE (`lang_code`)
            )      
        ";
        mysql_query($sql);
    } 
    
    $codes = $wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages");
    foreach($codes as $code){
        if(!$code) continue;
        if(!file_exists(ICL_PLUGIN_PATH.'/res/flags/'.$code.'.png')){
            $file = 'nil.png';
        }else{
            $file = $code.'.png';
        }
        $wpdb->insert($wpdb->prefix.'icl_flags', array(
            'lang_code'=>$code,
            'flag'=> $file
            ));
    }
    
    //fix norwegian records
    mysql_query("UPDATE {$wpdb->prefix}icl_languages SET code='nb', english_name='Norwegian Bokmål' WHERE english_name LIKE 'Norwegian Bokm%'");
    mysql_query("UPDATE {$wpdb->prefix}icl_languages_translations SET language_code='nb' WHERE language_code=''");

    
}

if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.0.0', '<')){
    $iclsettings = get_option('icl_sitepress_settings');
    $iclsettings['icl_website_kind'] = 2;    
    $iclsettings['icl_delivery_method'] = 0;        
    $iclsettings['icl_notify_complete'] = 1;
    $iclsettings['icl_translation_document_status'] = 1;
    $iclsettings['icl_remote_management'] = 0;    
    update_option('icl_sitepress_settings',$iclsettings);
}

if(version_compare(get_option('icl_sitepress_version'), ICL_SITEPRESS_VERSION, '<')){
    update_option('icl_sitepress_version', ICL_SITEPRESS_VERSION);
}
?>
