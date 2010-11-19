<?php
function icl_upgrade_2_0_0(){
    global $wpdb, $sitepress, $current_user, $wp_post_types;
    
    if(!isset($sitepress)) $sitepress = new SitePress;
    
    $TranslationManagement = new TranslationManagement;
    
    if(defined('icl_upgrade_2_0_0_runonce')){
        return;
    }
    define('icl_upgrade_2_0_0_runonce', true);
    
    // if the tables are missing, call the plugin activation routine
    $table_name = $wpdb->prefix.'icl_translation_status';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        icl_sitepress_activate();
    }
    
    $wpdb->query("ALTER TABLE `{$wpdb->prefix}icl_translations` CHANGE `element_type` `element_type` VARCHAR( 32 ) NOT NULL DEFAULT 'post_post'");
    $wpdb->query("ALTER TABLE `{$wpdb->prefix}icl_translations` CHANGE `element_id` `element_id` BIGINT( 20 ) NULL DEFAULT NULL ");
    
    // fix source_language_code
    // assume that the lowest element_id is the source language
    ini_set('max_execution_time', 300);
    
    $post_types = array_keys($wp_post_types);
    foreach($post_types as $pt){
        $types[] = 'post_' . $pt;
    }
    
    $res = $wpdb->get_results("
        SELECT trid, count(source_language_code) c 
        FROM {$wpdb->prefix}icl_translations 
        WHERE source_language_code = '' AND element_type IN('".join("','", $types)."')
        GROUP BY trid 
        HAVING c > 1            
        "); 
    $wpdb->query("UPDATE {$wpdb->prefix}icl_translations SET source_language_code = NULL WHERE source_language_code = ''");
          
    // fix source_language_code
    // assume that the lowest element_id is the source language    
    foreach($res as $row){
        $source = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_translations WHERE trid = " . $row->trid. " ORDER BY element_id ASC LIMIT 1");            
        $wpdb->query("UPDATE {$wpdb->prefix}icl_translations 
            SET source_language_code='{$source->language_code}' 
            WHERE source_language_code='' AND language_code<>'{$source->language_code}'");
    }   
    
     
    
    
    define('ICL_TM_DISABLE_ALL_NOTIFICATIONS', true); // make sure no notifications are being sent
    
    get_currentuserinfo();
    $translator_id =  $current_user->ID;
    
    //loop existing translations
    $res = mysql_query("SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_type IN('".join("','", $types)."') AND source_language_code IS NULL");
    while($row = mysql_fetch_object($res)){
        // grab translations
        $translations = $sitepress->get_element_translations($row->trid, $row->element_type);
        
        $md5 = 0;
        $table_name = $wpdb->prefix.'icl_node';
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name){
            list($md5, $links_fixed) = $wpdb->get_row($wpdb->prepare("
                SELECT md5, links_fixed FROM {$wpdb->prefix}icl_node
                WHERE nid = %d
            ", $row->element_id), ARRAY_N);        
        }
        if(!$md5){
            $md5 = $TranslationManagement->post_md5($row->element_id);    
        }
        
        $translation_package = $TranslationManagement->create_translation_package($row->element_id);
        
        
        foreach($translations as $lang => $t){            
            if(!$t->original){
                
                // determine service and status
                $service = 'local';
                $status  = 10;
                $needs_update = 0;
                
                list($rid, $status, $current_md5) = $wpdb->get_row($wpdb->prepare("
                    SELECT c.rid, n.status , c.md5 
                    FROM {$wpdb->prefix}icl_content_status c 
                        JOIN {$wpdb->prefix}icl_core_status n ON c.rid = n.rid
                    WHERE c.nid = %d AND target = %s
                    ORDER BY rid DESC 
                    LIMIT 1
                ", $row->element_id, $lang), ARRAY_N);
                if($rid){                
                    if($current_md5 != $md5){
                        $needs_update = 1;
                    }
                    if($status == 3){
                        $status = 10;
                    }else{
                        $status = 2;
                    }
                    $service = 'icanlocalize';
                }    
                
                
                // add translation_status record        
                list($newrid, $update) = $TranslationManagement->update_translation_status(array(
                    'translation_id'        => $t->translation_id,
                    'status'                => $status,
                    'translator_id'         => $translator_id,
                    'needs_update'          => $needs_update,
                    'md5'                   => $md5,
                    'translation_service'   => $service,
                    'translation_package'   => serialize($translation_package),
                    'links_fixed'           => intval($links_fixed)
                ));
                
                $job_id = $TranslationManagement->add_translation_job($newrid, $translator_id , $translation_package);
                if($status == 10){                    
                    $post = get_post($t->element_id);                    
                    $TranslationManagement->save_job_fields_from_post($job_id, $post);    
                }
                
                
                
            }
        }
    }
    
    // removing the plugins text table; importing data into a Sitepress setting
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}icl_plugins_texts");
    if(!empty($results)){
        foreach($results as $row){
            $cft[$row->attribute_name] = $row->translate + 1;
        }
        $iclsettings['translation-management']['custom_fields_translation'] = $cft;
        $sitepress->save_settings($iclsettings);
        
        mysql_query("DROP TABLE {$wpdb->prefix}icl_plugins_texts");
    }
    
    $iclsettings['language_selector_initialized'] = 1;
    
    if(get_option('_force_mp_post_http')){
        $iclsettings['troubleshooting_options']['http_communication'] = get_option('_force_mp_post_http'); 
        delete_option('_force_mp_post_http');
    }
    
    $sitepress_settings['troubleshooting_options']['http_communication'] = intval(get_option('_force_mp_post_http'));
    
    $sitepress->save_settings($iclsettings);
    
    
}





function icl_upgrade_2_0_0_steps($step, $stepper){
    global $wpdb, $sitepress, $current_user, $wp_post_types;

    if(!isset($sitepress)) $sitepress = new SitePress;

    $TranslationManagement = new TranslationManagement;

    define('ICL_TM_DISABLE_ALL_NOTIFICATIONS', true); // make sure no notifications are being sent

//    if(defined('icl_upgrade_2_0_0_runonce')){
//        return;
//    }
//    define('icl_upgrade_2_0_0_runonce', true);

    // fix source_language_code
    // assume that the lowest element_id is the source language
    ini_set('max_execution_time', 300);

    $post_types = array_keys($wp_post_types);
    foreach($post_types as $pt){
        $types[] = 'post_' . $pt;
    }

    $temp_upgrade_data = get_option('icl_temp_upgrade_data',
            array('step' => 0, 'offset' => 0));

    switch($step) {

        case 1:
            
    // if the tables are missing, call the plugin activation routine
    $table_name = $wpdb->prefix.'icl_translation_status';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        icl_sitepress_activate();
    }

    $wpdb->query("ALTER TABLE `{$wpdb->prefix}icl_translations` CHANGE `element_type` `element_type` VARCHAR( 32 ) NOT NULL DEFAULT 'post_post'");
    $wpdb->query("ALTER TABLE `{$wpdb->prefix}icl_translations` CHANGE `element_id` `element_id` BIGINT( 20 ) NULL DEFAULT NULL ");

    $res = $wpdb->get_results("
        SELECT trid, count(source_language_code) c
        FROM {$wpdb->prefix}icl_translations
        WHERE source_language_code = '' AND element_type IN('".join("','", $types)."')
        GROUP BY trid
        HAVING c > 1
        ");
    $wpdb->query("UPDATE {$wpdb->prefix}icl_translations SET source_language_code = NULL WHERE source_language_code = ''");

    // fix source_language_code
    // assume that the lowest element_id is the source language
    foreach($res as $row){
        $source = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_translations WHERE trid = " . $row->trid. " ORDER BY element_id ASC LIMIT 1");
        $wpdb->query("UPDATE {$wpdb->prefix}icl_translations
            SET source_language_code='{$source->language_code}'
            WHERE source_language_code='' AND language_code<>'{$source->language_code}'");
    }
    $temp_upgrade_data['step'] = 2;
    update_option('icl_temp_upgrade_data', $temp_upgrade_data);

    return array('message' => __('Processing translations...', 'sitepress'));








    break;
    case 2:
        
        $limit = 100;
        $offset = $temp_upgrade_data['offset'];
        $processing = FALSE;


    get_currentuserinfo();
    $translator_id =  $current_user->ID;

    //loop existing translations
    $res = mysql_query("SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_type IN('".join("','", $types)."') AND source_language_code IS NULL LIMIT " . $limit . "  OFFSET " . $offset);
    while($row = mysql_fetch_object($res)){
        $processing = TRUE;
        // grab translations
        $translations = $sitepress->get_element_translations($row->trid, $row->element_type);

        $md5 = 0;
        $table_name = $wpdb->prefix.'icl_node';
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name){
            list($md5, $links_fixed) = $wpdb->get_row($wpdb->prepare("
                SELECT md5, links_fixed FROM {$wpdb->prefix}icl_node
                WHERE nid = %d
            ", $row->element_id), ARRAY_N);
        }
        if(!$md5){
            $md5 = $TranslationManagement->post_md5($row->element_id);
        }

        $translation_package = $TranslationManagement->create_translation_package($row->element_id);


        foreach($translations as $lang => $t){
            if(!$t->original){

                // determine service and status
                $service = 'local';
                $status  = 10;
                $needs_update = 0;

                list($rid, $status, $current_md5) = $wpdb->get_row($wpdb->prepare("
                    SELECT c.rid, n.status , c.md5
                    FROM {$wpdb->prefix}icl_content_status c
                        JOIN {$wpdb->prefix}icl_core_status n ON c.rid = n.rid
                    WHERE c.nid = %d AND target = %s
                    ORDER BY rid DESC
                    LIMIT 1
                ", $row->element_id, $lang), ARRAY_N);
                if($rid){
                    if($current_md5 != $md5){
                        $needs_update = 1;
                    }
                    if($status == 3){
                        $status = 10;
                    }else{
                        $status = 2;
                    }
                    $service = 'icanlocalize';
                }


                // add translation_status record
                list($newrid, $update) = $TranslationManagement->update_translation_status(array(
                    'translation_id'        => $t->translation_id,
                    'status'                => $status,
                    'translator_id'         => $translator_id,
                    'needs_update'          => $needs_update,
                    'md5'                   => $md5,
                    'translation_service'   => $service,
                    'translation_package'   => serialize($translation_package),
                    'links_fixed'           => intval($links_fixed)
                ));

                $job_id = $TranslationManagement->add_translation_job($newrid, $translator_id , $translation_package);
                if($status == 10){
                    $post = get_post($t->element_id);
                    $TranslationManagement->save_job_fields_from_post($job_id, $post);
                }



            }
        }
    }
    if ($processing) {
        update_option('icl_temp_upgrade_data', array('step' => 2, 'offset' => intval($offset+100)));
        $stepper->setNextStep(2);
    } else {
        update_option('icl_temp_upgrade_data', array('step' => 3, 'offset' => 99999999999999999999));
    }
    
    $message = $processing ? __('Processing translations...', 'sitepress') : __('Finalizing migration...', 'sitepress');
    return array('message' => $message);


    break;

    
    case 3:

    // removing the plugins text table; importing data into a Sitepress setting
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}icl_plugins_texts");
    if(!empty($results)){
        foreach($results as $row){
            $cft[$row->attribute_name] = $row->translate + 1;
        }
        $iclsettings['translation-management']['custom_fields_translation'] = $cft;
        $sitepress->save_settings($iclsettings);

        mysql_query("DROP TABLE {$wpdb->prefix}icl_plugins_texts");
    }

    $iclsettings['language_selector_initialized'] = 1;

    if(get_option('_force_mp_post_http')){
        $iclsettings['troubleshooting_options']['http_communication'] = get_option('_force_mp_post_http');
        delete_option('_force_mp_post_http');
    }

    $sitepress_settings['troubleshooting_options']['http_communication'] = intval(get_option('_force_mp_post_http'));
    $iclsettings['migrated_2_0_0'] = 1;
    $sitepress->save_settings($iclsettings);
    delete_option('icl_temp_upgrade_data');
    return array('message' => __('Done', 'sitepress'), 'completed' => 1);
    break;

    default:
        return array('error' => 'Missing step', 'stop' => 1);


    }
}

add_filter('admin_notices', 'icl_migrate_2_0_0');
function icl_migrate_2_0_0() {
    $txt = get_option('icl_temp_upgrade_data', FALSE) ? __('Continue process', 'sitepress') : __('Start process', 'sitepress');
    echo '<div class="message updated" id="icl-migrate"><p>WPML Migration</p>'
            . '<p><a href="admin-ajax.php?icl_ajx_action=upgrade_2_0_0" style="" id="icl-migrate-start">' . $txt . '</a></p>'
            . '<div id="icl-migrate-progress" style="display:none; margin: 10px 0 20px 0;">'
            . '</div></div>';
}
?>
