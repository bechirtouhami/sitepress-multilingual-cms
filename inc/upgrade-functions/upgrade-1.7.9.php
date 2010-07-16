<?php
function icl_upgrade_1_7_9(){
    global $wpdb;
    
    // if the table is missing, call the plugin activation routine
    $table_name = $wpdb->prefix.'icl_translation_status';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        icl_sitepress_activate();
    }
    
    // if pro translation tables exist
    $table_name = $wpdb->prefix.'icl_node';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
    
        $results = $wpdb->get_results("
            SELECT t.*, p.post_author FROM {$wpdb->prefix}icl_translations t
                JOIN {$wpdb->posts} p ON p.ID = t.element_id
            WHERE element_type LIKE 'post\\_%' AND source_language_code IS NULL");
        foreach($results as $r){
            $node_record = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_node WHERE nid = {$r->element_id}");
            
            $res = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}icl_translations WHERE trid = {$r->trid}");
            foreach($res as $row){
                if($row->language_code != $r->language_code){
                    $node_translations[$row->language_code] = $row;
                }
            }
            
            $node_translations_details = $wpdb->get_row("
                SELECT * 
                FROM {$wpdb->prefix}icl_content_status cs 
                    JOIN {$wpdb->prefix}icl_core_status cr ON cr.rid = cs.rid
                WHERE nid = {$r->element_id}");
            $ndt_st = array();
            foreach($node_translations_details as $ntd){
                if(!isset($ndt_st[$ntd->target]) || $ndt_st[$ntd->target] < $ndt->rid){
                    $ndt_st[$ntd->target] = $ndt;                            
                }
            }
            
            foreach($ndt_st as $lang=>$status){
                $ts_record['rid'] = $status->rid;    
                
                if($node_translations[$status->target]){
                    $translation_id = $node_translations[$status->target]->translation_id;    
                }else{
                    $new_tr = array(
                        'element_type' => $r->element_type,
                        'element_id' => 0,
                        'trid' => $r->trid,
                        'language_code' => $status->target,
                        'source_language_code' => $r->language_code
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
                $ts_record['translator_id'] = $r->post_author;    
                $ts_record['needs_update'] = intval($status->md5 != $node_record->md5);    
                $ts_record['md5'] = $status->md5;    
                $ts_record['translation_service'] = '';    
                $ts_record['translation_package'] = '';    
                $ts_record['links_fixed'] = 0;     // ????
                $ts_record['timestamp'] = strtotime($status->timestamp);    
                
                $wpdb->insert($wpdb->prefix.'icl_translation_status', $ts_record);    
            }
            
            
            
        }
        
    }
    
    
    
}  
?>
