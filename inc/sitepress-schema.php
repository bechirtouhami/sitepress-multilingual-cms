<?php
function icl_sitepress_activate(){
    global $wpdb;
    global $EZSQL_ERROR;
    require_once(ICL_PLUGIN_PATH . '/inc/lang-data.inc');
    //defines $langs_names
    
    // languages table
    $table_name = $wpdb->prefix.'icl_languages';        
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = " 
        CREATE TABLE `{$table_name}` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `code` VARCHAR( 7 ) NOT NULL ,
            `english_name` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ,            
            `major` TINYINT NOT NULL DEFAULT '0', 
            `active` TINYINT NOT NULL ,
            UNIQUE KEY `code` (`code`),
            UNIQUE KEY `english_name` (`english_name`)
        )"; 
        mysql_query($sql);
        
        //$langs_names is defined in ICL_PLUGIN_PATH . '/inc/lang-data.inc'
        foreach($langs_names as $key=>$val){
            if(strpos($key,'Norwegian Bokm')===0){ $key = 'Norwegian Bokmål'; $lang_codes[$key] = 'nb';} // exception for norwegian
            $wpdb->insert($wpdb->prefix . 'icl_languages', array('english_name'=>$key, 'code'=>$lang_codes[$key], 'major'=>$val['major']));
        }        
    }

    // languages translations table
    $table_name = $wpdb->prefix.'icl_languages_translations';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = "
        CREATE TABLE `{$table_name}` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `language_code`  VARCHAR( 7 ) NOT NULL ,
            `display_language_code` VARCHAR( 7 ) NOT NULL ,            
            `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            UNIQUE(`language_code`, `display_language_code`)            
        )";
        mysql_query($sql);
    }else{
        mysql_query("TRUNCATE TABLE `{$table_name}`");
    }
    foreach($langs_names as $lang=>$val){        
        if(strpos($lang,'Norwegian Bokm')===0){ $lang = 'Norwegian Bokmål'; $lang_codes[$lang] = 'nb';}
        foreach($val['tr'] as $k=>$display){        
            if(!trim($display)){
                $display = $lang;
            }
            $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code'=>$lang_codes[$lang], 'display_language_code'=>$lang_codes[$k], 'name'=>$display));
        }    
    }        
    

    // translations
    $table_name = $wpdb->prefix.'icl_translations';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = "
        CREATE TABLE `{$table_name}` (
            `translation_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `element_type` ENUM( 'post', 'page', 'category', 'tag' ) NOT NULL DEFAULT 'post',
            `element_id` BIGINT NOT NULL ,
            `trid` BIGINT NOT NULL ,
            `language_code` VARCHAR( 7 ) ,
            `source_language_code` VARCHAR( 7 ),
            UNIQUE KEY `translation` (`element_type`,`element_id`,`language_code`)
        )";
        mysql_query($sql);
    } 

    // languages locale file names
    $table_name = $wpdb->prefix.'icl_locale_map';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = "
            CREATE TABLE `{$table_name}` (
                `code` VARCHAR( 8 ) NOT NULL ,
                `locale` VARCHAR( 8 ) NOT NULL ,
                UNIQUE (`code` ,`locale`)
            )";
        mysql_query($sql);
    } 
    
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
        $codes = $wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages");
        foreach($codes as $code){
            if(!$code || $wpdb->get_var("SELECT lang_code FROM {$wpdb->prefix}icl_flags WHERE lang_code='{$code}'")) continue;
            if(!file_exists(ICL_PLUGIN_PATH.'/res/flags/'.$code.'.png')){
                $file = 'nil.png';
            }else{
                $file = $code.'.png';
            }    
            $wpdb->insert($wpdb->prefix.'icl_flags', array('lang_code'=>$code, 'flag'=>$file));
        }
    } 
    
    // plugins texts table
    $table_name = $wpdb->prefix.'icl_plugins_texts';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = "
            CREATE TABLE `{$table_name}` (
            `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `plugin_name` VARCHAR( 255 ) NOT NULL ,
            `attribute_type` VARCHAR( 64 ) NOT NULL ,
            `attribute_name` VARCHAR( 128 ) NOT NULL ,
            `description` TEXT NOT NULL ,
            `translate` TINYINT NOT NULL ,
            UNIQUE KEY `plugin_name` (`plugin_name`,`attribute_type`,`attribute_name`)
            )";
       mysql_query($sql);
       $prepop  = array(
            0 => array(
                'plugin_name' => 'sitepress-multilingual-cms/sitepress.php',
                'attribute_type' => 'custom_field',
                'attribute_name' => '_top_nav_excluded',
                'description' => 'Exclude page from top navigation',
                'translate' => 0
                ),
            1 => array(
                'plugin_name' => 'sitepress-multilingual-cms/sitepress.php',
                'attribute_type' => 'custom_field',
                'attribute_name' => '_cms_nav_minihome',
                'description' => 'Sets page as a mini home in CMS Navigation',
                'translate' => 0
                ),
            2 => array(
                'plugin_name' => 'sitepress-multilingual-cms/sitepress.php',
                'attribute_type' => 'custom_field',
                'attribute_name' => '_cms_nav_section',
                'description' => 'Defines the section the page belong to',
                'translate' => 1
                ),
            3 => array(
                'plugin_name' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
                'attribute_type' => 'custom_field',
                'attribute_name' => 'title',
                'description' => 'Custom title for post/page',
                'translate' => 1
                ),
            4 => array(
                'plugin_name' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
                'attribute_type' => 'custom_field',
                'attribute_name' => 'description',
                'description' => 'Custom description for post/page',
                'translate' => 1
                ),
            5 => array(
                'plugin_name' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
                'attribute_type' => 'custom_field',
                'attribute_name' => 'keywords',
                'description' => 'Custom keywords for post/page',
                'translate' => 1
                )
       );   
       
       foreach($prepop as $pre){
           $wpdb->insert($table_name, $pre);
       }         
   }   
   
               
   delete_option('icl_sitepress_version');
   add_option('icl_sitepress_version', ICL_SITEPRESS_VERSION, '', true);
    
        
    // try to determine the blog language
    $blog_default_lang = 0;
    if($blog_lang = get_option('WPLANG')){
        $exp = explode('_',$blog_lang);
        $blog_default_lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='{$exp[0]}'");
    }
    if(!$blog_default_lang && defined('WPLANG') && WPLANG != '')
    {
        $blog_lang = WPLANG;
        $exp = explode('_',$blog_lang);
        $blog_default_lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='{$exp[0]}'");        
    }
    if(!$blog_default_lang){
        $blog_default_lang = 'en';
    }
    $wpdb->update($wpdb->prefix . 'icl_languages', array('active'=>1), array('code'=>$blog_default_lang));
    
    // plugin settings
    if($settings = get_option('icl_sitepress_settings')){
        // case of plugin already installed
        if(!$settings['default_language']){
            $settings['default_language'] = $blog_default_lang;
            update_option('icl_sitepress_settings', $settings);        
        }
    }else{
        $blog_default_cat = get_option('default_category');
        $blog_default_cat_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id='{$blog_default_cat}' AND taxonomy='category'");
        $settings = array(
            'interview_translators' => 0,
            'existing_content_language_verified' => 0,
            'language_negotiation_type' => 3,
            'icl_lso_header' => 0, 
            'icl_lso_link_empty' => 0,
            'icl_lso_flags' => 0,
            'icl_lso_native_lang' => 1,
            'icl_lso_display_lang' => 1,
            'language_home' => 1,
            'sync_page_ordering' => 1,
            'default_language'  => $blog_default_lang,
            'default_categories' => array($blog_default_lang => $blog_default_cat_tax_id),
            'translated_document_status' => 0,
            'website_kind' => 2,
            'translation_pickup_method' => 0,
            'notify_complete' => 1,
            'translated_document_status' => 1,
            'remote_management' => 0,
            'alert_delay' => 0,
            'modules' => array(
                'absolute-links' => array('enabled'=>0),
                'cms-navigation'=>array()
                )
        );        
        add_option('icl_sitepress_settings', $settings, '', true);        
    }    
       
    if(defined('ICL_DEBUG_MODE') && ICL_DEBUG_MODE){
        require_once ICL_PLUGIN_PATH . '/inc/functions.php';
        icl_display_errors_stack(true);
    }                                                              
}

function icl_sitepress_deactivate(){
    // don't do anything for now
} 

if(isset($_GET['activate'])){
    if(!isset($wpdb)) global $wpdb;
    $table_name = $wpdb->prefix.'icl_languages';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        add_action('admin_notices', 'icl_cant_create_table');
        function icl_cant_create_table(){
            echo '<div class="error"><ul><li><strong>';
            echo __('WPML cannot create the database tables! Make sure that your mysql user has the CREATE privilege', 'sitepress');
            echo '</strong></li></ul></div>';        
            $active_plugins = get_option('active_plugins');
            $icl_sitepress_idx = array_search('sitepress-multilingual-cms/sitepress.php', $active_plugins);
            if(false !== $icl_sitepress_idx){
                unset($active_plugins[$icl_sitepress_idx]);
                update_option('active_plugins', $active_plugins);
                unset($_GET['activate']);
                $recently_activated = get_option('recently_activated');
                if(!isset($recently_activated['sitepress-multilingual-cms/sitepress.php'])){
                    $recently_activated['sitepress-multilingual-cms/sitepress.php'] = time();
                    update_option('recently_activated', $recently_activated);
                }
            }                
        }
    }   
}

?>