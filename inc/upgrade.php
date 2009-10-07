<?php
if(version_compare(get_option('icl_sitepress_version'), ICL_SITEPRESS_VERSION, '=') 
    || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'error_scrape') || !isset($wpdb) ) return;

add_action('plugins_loaded', 'icl_plugin_upgrade' , 1);

function icl_plugin_upgrade(){
    global $wpdb, $sitepress_settings, $sitepress;

    if(defined('ICL_DEBUG_MODE') && ICL_DEBUG_MODE){
        $mig_debug = @fopen(ICL_PLUGIN_PATH . '/upgrade.log' , 'w');
    }else{
        $mig_debug = false;
    }
    
    // clear any caches
    if($mig_debug) fwrite($mig_debug, "Clearing cache \n");
    require_once ICL_PLUGIN_PATH . '/inc/cache.php';
    icl_cache_clear('locale_cache_class');
    icl_cache_clear('flags_cache_class');
    icl_cache_clear('language_name_cache_class');
    icl_cache_clear('cms_nav_offsite_url_cache_class');
    if($mig_debug) fwrite($mig_debug, "Cleared cache \n");
    
    
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '0.9.3', '<')){        
        require_once(ICL_PLUGIN_PATH . '/inc/lang-data.inc');      
        $wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET english_name='Norwegian BokmÃ¥l', code='nb' WHERE english_name='Norwegian'");      
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
        mysql_query("UPDATE {$wpdb->prefix}icl_languages SET code='nb', english_name='Norwegian BokmÃ¥l' WHERE english_name LIKE 'Norwegian Bokm%'");
        mysql_query("UPDATE {$wpdb->prefix}icl_languages_translations SET language_code='nb' WHERE language_code=''");

        
    }

    // version 1.0.1
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.0.1', '<')){
        $sitepress_settings = get_option('icl_sitepress_settings');
        if($sitepress_settings['existing_content_language_verified']){
            include ICL_PLUGIN_PATH . '/modules/icl-translation/db-scheme.php';
        }
        
    }

    // version 1.0.2
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.0.2', '<')){
        //fix norwegian records    
        $wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET code='nb', english_name='Norwegian BokmÃ¥l' WHERE english_name LIKE 'Norwegian Bokm%'");    
        $wpdb->query("UPDATE {$wpdb->prefix}icl_languages_translations SET language_code='nb' WHERE language_code=''");        
        $wpdb->query("UPDATE {$wpdb->prefix}icl_languages_translations SET display_language_code='nb' WHERE display_language_code=''");        
        
        $wpdb->query("ALTER TABLE {$wpdb->prefix}icl_translations DROP KEY translation");
        
        // get elements with duplicates
        $res = $wpdb->get_results("SELECT element_id, element_type, COUNT(translation_id) AS c FROM {$wpdb->prefix}icl_translations GROUP BY element_id, element_type HAVING c > 1");
        foreach($res as $r){
            $row_count = $r->c - 1;
            $wpdb->query("
                DELETE FROM {$wpdb->prefix}icl_translations 
                WHERE 
                    element_id={$r->element_id} AND 
                    element_type='{$r->element_type}'
                ORDER BY translation_id DESC
                LIMIT {$row_count}
            ");
        }           
        $wpdb->query("ALTER TABLE {$wpdb->prefix}icl_translations ADD UNIQUE KEY `el_type_id` (`element_type`, `element_id`)");
       
        // fix multiple languages per trid 
        $res = $wpdb->get_results("SELECT trid, language_code, COUNT(translation_id) AS c FROM {$wpdb->prefix}icl_translations GROUP BY trid, language_code HAVING c > 1");
        foreach($res as $r){
            $row_count = $r->c - 1;
            $wpdb->query("
                DELETE FROM {$wpdb->prefix}icl_translations 
                WHERE 
                    trid={$r->trid} AND 
                    language_code='{$r->language_code}'
                ORDER BY translation_id DESC
                LIMIT {$row_count}
            ");
        }           
        $wpdb->query("ALTER TABLE {$wpdb->prefix}icl_translations ADD UNIQUE KEY `trid_lang` (`trid`, `language_code`)");
        
        $res = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}icl_translations WHERE language_code='' OR language_code IS NULL");
        $sp_default_lcode = $sitepress_settings['default_language'];
        foreach($res as $r){        
            if(!$sp_default_lcode || $wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid={$r->trid} AND language_code='{$sp_default_lcode}'")){
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id={$r->translation_id}");
            }else{
                $wpdb->update($wpdb->prefix . 'icl_translations', array('language_code'=>$sp_default_lcode), array('translation_id'=>$r->translation_id));
            }
        }
        
        $wpdb->query("ALTER TABLE {$wpdb->prefix}icl_translations  CHANGE `language_code` `language_code` VARCHAR( 7 ) NOT NULL");
        
    }

    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.2.0', '<')){
        $iclsettings = get_option('icl_sitepress_settings');
        if($iclsettings['icl_interview_translators'] == 0){
            $iclsettings['icl_interview_translators'] = 1;    
            update_option('icl_sitepress_settings',$iclsettings);
        }    
    }

    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.3.0.1', '<')){
        if($mig_debug) fwrite($mig_debug, "Upgrading to 1.3.0.1 \n");
        $iclsettings = get_option('icl_sitepress_settings');
        $iclsettings['modules']['cms-navigation']['enabled'] = 1;
        $iclsettings['dont_show_help_admin_notice'] = 1;        
        $iclsettings['setup_complete'] = 1;        
        $iclsettings['setup_wizard_step'] = 0;        
        mysql_query("ALTER TABLE `{$wpdb->prefix}icl_translations` CHANGE `element_type` `element_type` VARCHAR( 32 ) NOT NULL DEFAULT 'post'");
        if(!$iclsettings['admin_default_language']){
            $iclsettings['admin_default_language'] = $iclsettings['default_language'];            
        }
        update_option('icl_sitepress_settings',$iclsettings);
        
                
        $maxtrid = 1 + $wpdb->get_var("SELECT MAX(trid) FROM {$wpdb->prefix}icl_translations");
        mysql_query("
            INSERT INTO {$wpdb->prefix}icl_translations(element_type, element_id, trid, language_code, source_language_code)
            SELECT 'comment', comment_ID, {$maxtrid}+comment_ID, t.language_code, NULL 
                FROM {$wpdb->comments} c JOIN {$wpdb->prefix}icl_translations t ON c.comment_post_id = t.element_id AND t.element_type='post'
            ");            
        if($mig_debug) fwrite($mig_debug, "Upgraded to 1.3.0.1 \n");
    }
    
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.3.0.2', '<')){
        if($mig_debug) fwrite($mig_debug, "Upgrading to 1.3.0.2 \n");
        $iclsettings = get_option('icl_sitepress_settings');        
        $wpdb->update($wpdb->prefix.'icl_translations', array('language_code'=>$iclsettings['admin_default_language']), array('language_code'=>'', 'element_type'=>'comment', 'source_language_code'=>''));
        if($mig_debug) fwrite($mig_debug, "Upgraded to 1.3.0.2 \n");
    }

    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.3.1', '<')){
        if($mig_debug) fwrite($mig_debug, "Upgrading to 1.3.1 \n");
        $iclsettings = get_option('icl_sitepress_settings');
        if ($iclsettings['site_id'] && $iclsettings['access_key']) {            
            $iclsettings['content_translation_setup_complete'] = 1;
            update_option('icl_sitepress_settings',$iclsettings);
        }
        if($mig_debug) fwrite($mig_debug, "Upgraded to 1.3.1 \n");
    }

    
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.3.2', '<')){
        if($mig_debug) fwrite($mig_debug, "Upgrading to 1.3.2 \n");
        $comment_translations = array();
        $res = mysql_query("
            SELECT element_id, language_code, trid
                FROM {$wpdb->prefix}icl_translations WHERE element_type='comment'
            ");
        while($row = mysql_fetch_assoc($res)){
            $comment_translations[$row['element_id']] = $row;
        }
        
        $res = mysql_query("
            SELECT c.comment_ID, t.language_code AS post_language
                FROM {$wpdb->comments} c 
                    JOIN {$wpdb->prefix}icl_translations t ON  c.comment_post_ID = t.element_id AND t.element_type='post'                    
            ");
        while($row = mysql_fetch_object($res)){
            if($row->post_language != $comment_translations[$row->comment_ID]['language_code']){
                //check whether we have a comment in this comment's trid that's in the post language
                if(!$wpdb->get_var("
                    SELECT translation_id 
                    FROM {$wpdb->prefix}icl_translations 
                    WHERE trid={$comment_translations[$row->comment_ID]['trid']} AND element_id<>{$row->comment_ID}
                    ")){
                    $wpdb->update($wpdb->prefix.'icl_translations', array('language_code'=>$row->post_language), array('element_id'=>$row->comment_ID, 'element_type'=>'comment'));
                }
            }
        }
        if($mig_debug) fwrite($mig_debug, "Upgraded to 1.3.2 \n");
    }
    
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.3.3', '<')){
        if($mig_debug) fwrite($mig_debug, "Upgrading to 1.3.3 \n");
        $iclsettings = get_option('icl_sitepress_settings');
        $iclsettings['modules']['cms-navigation']['cache'] = 1;
        update_option('icl_sitepress_settings',$iclsettings);
        $wpdb->update($wpdb->prefix . 'icl_languages_translations', array('name'=>'Čeština'), array('language_code'=>'cs', 'display_language_code'=>'cs'));
        if($mig_debug) fwrite($mig_debug, "Upgraded to 1.3.3 \n");
    }
    
    
    
    if(version_compare(get_option('icl_sitepress_version'), ICL_SITEPRESS_VERSION, '<')){
        if($mig_debug) fwrite($mig_debug, "Update plugin version in the database \n");
        update_option('icl_sitepress_version', ICL_SITEPRESS_VERSION);
        if($mig_debug) fwrite($mig_debug, "Updated plugin version in the database \n");
    }

    if(defined('ICL_DEBUG_MODE') && ICL_DEBUG_MODE && $mig_debug){
        @fclose($mig_debug);
    }    
    
}



?>