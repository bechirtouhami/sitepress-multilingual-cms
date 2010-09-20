<?php
function icl_upgrade_2_0_0(){
    global $wpdb, $sitepress;
    
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
    
    // if pro translation tables exist
    $table_name = $wpdb->prefix.'icl_node';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name){
        
        // fix source_language_code
        // assume that the lowest element_id is the source language
        $res = $wpdb->get_results("
            SELECT trid, count(source_language_code) c 
            FROM {$wpdb->prefix}icl_translations 
            WHERE source_language_code = '' AND element_type LIKE 'post\\_%'
            GROUP BY trid 
            HAVING c > 1            
            ");        
        foreach($res as $row){
            $source = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_translations WHERE trid = " . $row->trid. " ORDER BY element_id ASC LIMIT 1");            
            $wpdb->query("UPDATE {$wpdb->prefix}icl_translations 
                SET source_language_code='{$source->language_code}' 
                WHERE source_language_code='' AND language_code<>'{$source->language_code}'");
        }
        
        // get max rid 
        $max_rid = 1+$wpdb->get_var("SELECT MAX(rid) FROM {$wpdb->prefix}icl_content_status");
        $rid_incr = 0;
        
        $originals = $wpdb->get_results("
            SELECT t.*, p.post_author FROM {$wpdb->prefix}icl_translations t
                JOIN {$wpdb->posts} p ON p.ID = t.element_id
            WHERE element_type LIKE 'post\\_%' AND source_language_code = ''");        
        foreach($originals as $original){
            $node_record = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_node WHERE nid = {$original->element_id}");
            
            $res = $wpdb->get_results("
                SELECT t.*, p.post_date FROM {$wpdb->prefix}icl_translations t
                JOIN {$wpdb->posts} p ON t.element_id = p.ID
                WHERE trid = {$original->trid}"
            );
            
            $node_translations = array();
            foreach($res as $row){
                if($row->language_code != $original->language_code && $row->language_code){
                    $node_translations[$row->language_code] = $row;
                }
            }
            
            $node_translations_details = $wpdb->get_results("
                SELECT * 
                FROM {$wpdb->prefix}icl_content_status cs 
                    JOIN {$wpdb->prefix}icl_core_status cr ON cr.rid = cs.rid
                WHERE nid = {$original->element_id}");
                
            $ntd_st = array();            
            foreach($node_translations_details as $ntd){
                if(!isset($ntd_st[$ntd->target]) || $ntd_st[$ntd->target]->rid < $ntd->rid){
                    $ntd_st[$ntd->target] = $ntd;                            
                }
            }
            
            // match with existing translations
            // if we have translations but not icl_core_status records it means these are manual translations
            foreach($node_translations as $lang => $nt){
                if(!isset($ntd_st[$lang])){
                    $ntd_st[$lang] = (object) array(
                        'rid' => 0, // force new rid
                        'timestamp' => $nt->post_date,
                        'md5' => $node_record->md5, //force to up to date    
                        'target' => $nt->language_code,
                        'status' => 10 // force to complete
                    );    
                }
            }
            
            
            $used_rids = array(); // used for - // fix duplicate rids            
            foreach($ntd_st as $lang=>$status){
                $rid_incr++;
                $ts_record = array();
                
                
                // fix duplicate rids
                if(in_array($status->rid, $used_rids)){
                    $status->rid = 0; // force getting a new one    
                }
                $used_rids[] = $status->rid;                                        

                
                if($status->rid){
                    $ts_record['rid'] = $status->rid;    
                }else{
                    $ts_record['rid'] = $max_rid + $rid_incr;                    
                }
                                
                if($node_translations[$status->target]){
                    $translation_id = $node_translations[$status->target]->translation_id;    
                }else{
                    $new_tr = array(
                        'element_type' => $original->element_type,
                        'element_id' => 0,
                        'trid' => $r->trid,
                        'language_code' => $status->target,
                        'source_language_code' => $original->language_code
                    );
                    $wpdb->insert($wpdb->prefix.'icl_translations', $new_tr);
                    $translation_id = $wpdb->insert_id;
                } 
                
                $ts_record['translation_id'] = $translation_id;
                
                if($status->status == 3){
                    $new_status = 10;     // complete
                }elseif($status->status==1){
                    $new_status = 2;     // in progress
                }
                $ts_record['status'] = $new_status;    
                $ts_record['translator_id'] = $original->post_author;    
                $ts_record['needs_update'] = intval($status->md5 != $node_record->md5);    
                $ts_record['md5'] = $status->md5;    
                $ts_record['translation_service'] = '';    
                $ts_record['translation_package'] = '';    
                $ts_record['links_fixed'] = 0;     // ????
                $ts_record['timestamp'] = $status->timestamp;    
                
                
                $wpdb->insert($wpdb->prefix.'icl_translation_status', $ts_record);    
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
    $sitepress->save_settings($iclsettings);
    
    
}  
?>
