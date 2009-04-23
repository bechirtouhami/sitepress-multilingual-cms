<?php
require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
require_once ICL_PLUGIN_PATH . '/modules/icl-translation/constants.inc';

if(isset($_POST['translation_dashboard_filter'])){
    $icl_translation_filter = $_POST['filter'];
}

add_action('save_post', 'icl_translation_save_md5');
add_action('delete_post', 'icl_translation_delete_post');
add_action('admin_menu', 'icl_translation_admin_menu');
add_action('admin_print_scripts', 'icl_translation_js');
add_filter('xmlrpc_methods','icl_add_custom_xmlrpc_methods');

//wp_enqueue_style('icl-translation-style', ICL_PLUGIN_URL . '/modules/icl-translation/css/style.css', array(), '0.1');

function icl_translation_admin_menu(){
    add_management_page(__('Translation Dashboard', 'sitepress'), __('Translation Dashboard', 'sitepress'), 'edit_posts', dirname(__FILE__).'/icl-translation-dashboard.php');
}

function icl_translation_js(){
    wp_enqueue_script('icl-translation-scripts', ICL_PLUGIN_URL . '/modules/icl-translation/js/icl-translation.js', array(), '0.1');
}

function icl_translation_send_post($post_id, $target_languages, $post_type='post'){
    global $sitepress_settings, $wpdb;
    
    $post = get_post($post_id);
    if(!$post){
        return false;
    }
    
    $previous_rid = $wpdb->get_var("SELECT rid FROM {$wpdb->prefix}icl_content_status WHERE nid={$post_id}");    
    if(is_null($previous_rid)) $previous_rid = false;  
      
    $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);
    
    $post_url       = get_permalink($post_id);
    
    $orig_lang = $wpdb->get_var("
        SELECT l.english_name 
        FROM {$wpdb->prefix}icl_translations t 
        JOIN {$wpdb->prefix}icl_languages l ON t.language_code=l.code 
        WHERE t.element_id={$post_id} AND t.element_type='post'"
        );
            
    if($post_type=='post'){
        foreach(wp_get_object_terms($post_id, 'post_tag') as $tag){
            $post_tags[$tag->term_taxonomy_id] = $tag->name;
        }   
        if(is_array($post_tags)){
            //only send tags that don't have a translation
            foreach($post_tags as $term_taxonomy_id=>$pc){
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$term_taxonomy_id}' AND element_type='tag'");
                foreach($target_languages as $lang){
                    $not_translated = false;
                    if($trid != $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_languages l ON l.code = t.language_code WHERE l.english_name='{$lang}' AND trid='{$trid}'")){
                        $not_translated = true;
                        break;
                    }                
                }
                if($not_translated){
                    $tags_to_translate[$term_taxonomy_id] = $pc; 
                }            
            }  
            
            sort($post_tags, SORT_STRING);
        } 
               
        foreach(wp_get_object_terms($post_id, 'category') as $cat){
            $post_categories[$cat->term_taxonomy_id] = $cat->name;
        }            
        if(is_array($post_categories)){
            //only send categories that don't have a translation
            foreach($post_categories as $term_taxonomy_id=>$pc){
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$term_taxonomy_id}' AND element_type='category'");
                foreach($target_languages as $lang){
                    $not_translated = false;
                    if($trid != $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_languages l ON l.code = t.language_code WHERE l.english_name='{$lang}' AND trid='{$trid}'")){
                        $not_translated = true;
                        break;
                    }                
                }
                if($not_translated){
                    $categories_to_translate[$term_taxonomy_id] = $pc; 
                }            
            }  
            
            sort($post_categories, SORT_STRING);
        }
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $md5 = md5($post->post_title.';'.$post->post_content.';'.join(',',(array)$post_tags).';'.join(',',(array)$post_categories));    
    
    $data = array(
        'url'=>$post_url, 
        'contents'=>array(
            'title' => array(
                'translate'=>1,
                'data'=>$post->post_title
            ),
            'body' => array(
                'translate'=>1,
                'data'=>$post->post_content
            ),
            'original_id' => array(
                'translate'=>0,
                'data'=>$post_id
            ),
                        
        ),
        'target_languages' => $target_languages
    );
    
    if($post_type=='post'){
        if(is_array($categories_to_translate)){
            $data['contents']['categories'] = array(
                    'translate'=>1,
                    'data'=> implode(',', array_map(create_function('$e', 'return \'"\'.base64_encode($e).\'"\';'), $categories_to_translate)),
                    'format'=>'csv_base64'
                );    
            $data['contents']['category_ids'] = array(
                    'translate'=>0,
                    'data'=> implode(',', array_keys($categories_to_translate)),
                    'format'=>''
                );                
        }
        if(is_array($tags_to_translate)){
            $data['contents']['tags'] = array(
                    'translate'=>1,
                    'data'=> implode(',', array_map(create_function('$e', 'return \'"\'.base64_encode($e).\'"\';'), $tags_to_translate)),
                    'format'=>'csv_base64'
                );                
            $data['contents']['tag_ids'] = array(
                    'translate'=>0,
                    'data'=> implode(',', array_keys($tags_to_translate)),
                    'format'=>''
                );                            
        }
    }
    $xml = $iclq->build_cms_request_xml($data, $orig_lang, $target_languages, $previous_rid);
    $res = $iclq->send_request($xml, $post->post_title, $target_languages, $orig_lang);
    
    if($res > 0){
        if($previous_rid){
            $languages_requests = $wpdb->get_col("SELECT target FROM {$wpdb->prefix}icl_core_status WHERE rid='{$previous_rid}'");
            $new_languages = array_diff($target_languages, $languages_requests);
            foreach($new_languages as $new_lang){
                $wpdb->insert($wpdb->prefix.'icl_core_status', array('rid'=>$res, 'origin'=>$orig_lang, 'target'=>$new_lang, 'status'=>CMS_REQUEST_WAITING_FOR_PROJECT_CREATION));
            }
            $wpdb->update($wpdb->prefix.'icl_content_status', array('rid'=>$res, 'md5'=>$md5, 'timestamp'=>$timestamp), array('rid'=>$previous_rid)); //update rid            
            $wpdb->update($wpdb->prefix.'icl_core_status', array('rid'=>$res), array('rid'=>$previous_rid)); //update rid
            // languages added?
        }else{            
            $wpdb->insert($wpdb->prefix.'icl_content_status', array('rid'=>$res, 'nid'=>$post_id, 'timestamp'=>$timestamp, 'md5'=>$md5)); //insert rid   
            foreach($target_languages as $targ_lang){
                $wpdb->insert($wpdb->prefix.'icl_core_status', array('rid'=>$res, 'origin'=>$orig_lang, 'target'=>$targ_lang, 'status'=>CMS_REQUEST_WAITING_FOR_PROJECT_CREATION));
            }
        }        
        $ret = $res;  
              
    }else{
        // sending to translation failed
        $ret = 0;
        
    } 
    return $ret;
    
}

function icl_translation_save_md5($p){
    global $wpdb;
    if($_POST['autosave']) return;
    if($_POST['action']=='post-quickpress-publish'){
        $post_id = $p;            
        $_POST['post_type']='post';
    }else{
        $post_id = $_POST['post_ID'];
    } 
    
    $post = get_post($post_id);
    $post_type = $_POST['post_type'];
    
    if($post_type=='post'){
        foreach(wp_get_object_terms($post_id, 'post_tag') as $tag){
            $post_tags[] = $tag->name;
        }
        if(is_array($post_tags)){
            sort($post_tags, SORT_STRING);
        }        
        foreach(wp_get_object_terms($post_id, 'category') as $cat){
            $post_categories[] = $cat->name;
        }    
        if(is_array($post_categories)){
            sort($post_categories, SORT_STRING);
        }
    }
    
    $md5 = md5($post->post_title.';'.$post->post_content.';'.join(',',(array)$post_tags).';'.join(',',(array)$post_categories));    
    
    if($wpdb->get_var("SELECT nid FROM {$wpdb->prefix}icl_node WHERE nid='{$post_id}'")){
        $wpdb->update($wpdb->prefix . 'icl_node', array('md5'=>$md5), array('nid'=>$post_id));
    }else{
        $wpdb->insert($wpdb->prefix . 'icl_node', array('nid'=>$post_id, 'md5'=>$md5));
    }
    
}

function icl_translation_get_documents($lang, $tstatus, $status=false, $type=false){
    global $wpdb, $wp_query;
    $limit = 20;
    
    $where = "WHERE 1";
    if($tstatus=='not'){
        $where .= " AND (c.rid IS NULL OR n.md5<>c.md5)";
    }
    if($type){
        $where .= " AND p.post_type = '{$type}'";
    }else{
        $where .= " AND p.post_type IN ('post','page')";
    }    
    if($status){
        $where .= " AND p.post_status = '{$status}'";
    }        
    $where .= " AND t.language_code='{$lang}'";
    
    if(!isset($_GET['paged'])) $_GET['paged'] = 1;
    $offset = ($_GET['paged']-1)*$limit;
    
    $sql = "
        SELECT SQL_CALC_FOUND_ROWS p.ID as post_id, p.post_title, p.post_type, p.post_status, post_content, 
            c.rid,
            n.md5<>c.md5 AS updated
        FROM {$wpdb->posts} p
            JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id AND element_type='post'
            LEFT JOIN {$wpdb->prefix}icl_node n ON p.ID = n.nid
            LEFT JOIN {$wpdb->prefix}icl_content_status c ON c.nid=p.ID
        {$where}                
        ORDER BY p.post_date DESC 
        LIMIT {$offset}, {$limit}
    ";
    $results = $wpdb->get_results($sql);
    $pids = array();
    foreach($results as $r){
        $pids[] = $r->post_id;
    }
    $wp_query->found_posts = $wpdb->get_var("SELECT FOUND_ROWS()");
    $wp_query->query_vars['posts_per_page'] = $limit;
    $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit);
    
    $sql = "
        SELECT p.ID as post_id, COUNT(r.rid) AS inprogress_count 
        FROM {$wpdb->posts} p
            JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id AND element_type='post'
            LEFT JOIN {$wpdb->prefix}icl_content_status c ON c.nid=p.ID
            LEFT JOIN {$wpdb->prefix}icl_core_status r ON c.rid = r.rid
            {$where} AND p.ID IN (".join(',', $pids).")
            AND status <> ".CMS_REQUEST_DONE."
            GROUP BY (r.rid) HAVING inprogress_count > 0 
        ORDER BY p.post_date DESC 
    ";
    
    $in_progress = $wpdb->get_results($sql);
    
    foreach($results as $r){
        $documents[$r->post_id] = $r;
    }
    
    foreach($in_progress as $v){
        $documents[$v->post_id]->in_progress = $v->inprogress_count;
    }
      
    return $documents;
    
}

function icl_translation_delete_post($post_id){
    global $wpdb;
    $wpdb->query("DELETE FORM {$wpdb->prefix}icl_node WHERE nid=".$post_id);
    $rid = $wpdb->get_var("SELECT rid FORM {$wpdb->prefix}icl_content_status WHERE nid=".$post_id);
    $wpdb->query("DELETE FORM {$wpdb->prefix}icl_content_status WHERE nid=".$post_id);
    $wpdb->query("DELETE FORM {$wpdb->prefix}icl_core_status WHERE rid=".$rid);
}

function icl_add_post_translation($trid, $translation, $lang, $rid){
    global $wpdb, $sitepress_settings;
    $lang_code = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE english_name='".$wpdb->escape($lang)."'");
    if(!$lang_code){        
        return false;
    }
    
    // todo 
    // pages hierarchy
    // Category hierarchy needs to be rebuilt by the plugin.
    // The user can select if translations should be published when received, add a translation review mode for when they are set to “Don’t Publish”.
    
    $original_post_details = $wpdb->get_row("
        SELECT p.post_author, p.post_type, p.post_status, t.language_code
        FROM {$wpdb->prefix}icl_translations t 
        JOIN {$wpdb->posts} p ON t.element_id = p.ID
        WHERE t.element_type='post' AND trid='{$trid}' AND p.ID = '{$translation['original_id']}'
    ");
    
    if($original_post_details->post_type=='post'){
        
        // deal with tags
        if(isset($translation['tags'])){
            $translated_tags = $translation['tags'];   
            $translated_tag_ids = explode(',', $translation['tag_ids']);
            foreach($translated_tags as $k=>$v){
                $tag_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$translated_tag_ids[$k]}' AND element_type='tag'");
                //tag exists?
                $term_taxonomy_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} tm ON tx.term_id = tm.term_id WHERE tm.name='".$wpdb->escape($v)."' AND taxonomy='post_tag'");
                if(!$term_taxonomy_id){  
                    $tmp = wp_insert_term($v, 'post_tag');
                    if(isset($tmp['term_taxonomy_id'])){                
                        $wpdb->update($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$tag_trid, 'source_language_code'=>$original_post_details->language_code), 
                            array('element_type'=>'tag','element_id'=>$tmp['term_taxonomy_id']));
                    }
                }else{
                    $tag_translation_id = $wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_id={$term_taxonomy_id} AND element_type='tag'");    
                    if($tag_translation_id){
                        $wpdb->update($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$tag_trid, 'source_language_code'=>$original_post_details->language_code), 
                            array('element_type'=>'tag','translation_id'=>$tag_translation_id));                
                    }else{
                        $wpdb->insert($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$tag_trid, 'element_type'=>'tag', 'element_id'=>$term_taxonomy_id, 'source_language_code'=>$original_post_details->language_code));                                
                    }
                }        
            }
        }
        
        foreach(wp_get_object_terms($translation['original_id'] , 'post_tag') as $t){
            $original_post_tags[] = $t->term_taxonomy_id;
        }    
        if($original_post_tags){
            $tag_trids = $wpdb->get_col("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='tag' AND element_id IN (".join(',',$original_post_tags).")");    
            $tag_tr_tts = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='tag' AND language_code='{$lang_code}' AND trid IN (".join(',',$tag_trids).")");    
            $translated_tags = $wpdb->get_col("SELECT t.name FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = t.term_id WHERE tx.taxonomy='post_tag' AND tx.term_taxonomy_id IN (".join(',',$tag_tr_tts).")");
        }
            
        // deal with categories
        if(isset($translation['categories'])){
            $translated_cats = $translation['categories'];   
            $translated_cats_ids = explode(',', $translation['category_ids']);    
            foreach($translated_cats as $k=>$v){
                $cat_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$translated_cats_ids[$k]}' AND element_type='category'");
                //cat exists?
                $term_taxonomy_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} tm ON tx.term_id = tm.term_id WHERE tm.name='".$wpdb->escape($v)."' AND taxonomy='category'");
                if(!$term_taxonomy_id){  
                    $tmp = wp_insert_term($v, 'category');
                    if(isset($tmp['term_taxonomy_id'])){                
                        $wpdb->update($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$cat_trid, 'source_language_code'=>$original_post_details->language_code), 
                            array('element_type'=>'category','element_id'=>$tmp['term_taxonomy_id']));
                    }
                }else{
                    $cat_translation_id = $wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_id={$term_taxonomy_id} AND element_type='category'");    
                    if($cat_translation_id){
                        $wpdb->update($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$cat_trid, 'source_language_code'=>$original_post_details->language_code), 
                            array('element_type'=>'category','translation_id'=>$cat_translation_id));                
                    }else{
                        $wpdb->insert($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$tag_trid, 'element_type'=>'category', 'element_id'=>$term_taxonomy_id, 'source_language_code'=>$original_post_details->language_code));                                
                    }            
                }        
            }
        }
            
        foreach(wp_get_object_terms($translation['original_id'] , 'category') as $t){
            $original_post_cats[] = $t->term_taxonomy_id;
        }    
        $cat_trids = $wpdb->get_col("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='category' AND element_id IN (".join(',',$original_post_cats).")");
        $cat_tr_tts = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='category' AND language_code='{$lang_code}' AND trid IN (".join(',',$cat_trids).")");
        $translated_cats_ids = $wpdb->get_col("SELECT t.term_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = t.term_id WHERE tx.taxonomy='category' AND tx.term_taxonomy_id IN (".join(',',$cat_tr_tts).")");
        
    }
    
    // is update?
    $post_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND trid='{$trid}' AND language_code='{$lang_code}'");
    if($post_id){
        $is_update = true;
        $postarr['post_ID'] = $_POST['post_ID'] = $post_id;
    }else{
        $is_update = false;
    } 
    
    $postarr['post_title'] = $translation['title'];
    $postarr['post_content'] = $translation['body'];
    if($original_post_details->post_type=='post'){
        $postarr['tags_input'] = join(',',(array)$translated_tags);
        $postarr['post_category'] = $translated_cats_ids;
    }
    $postarr['post_author'] = $original_post_details->post_author;  
    $postarr['post_type'] = $original_post_details->post_type;  
    $postarr['post_status'] = $sitepress_settings['translated_document_status'] ? 'draft' : $original_post_details->post_status;  
    $_POST['trid'] = $trid;
    $_POST['lang'] = $lang_code;
    $_POST['skip_sitepress_actions'] = true;
    $new_post_id = wp_insert_post($postarr);
    if(!$new_post_id){
        return false;
    }
    
    // record trids
    if(!$is_update){
        $wpdb->insert($wpdb->prefix.'icl_translations', array('element_type'=>'post', 'element_id'=>$new_post_id, 'trid'=> $trid, 'language_code'=>$lang_code, 'source_language_code'=>$original_post_details->language_code));
    }
    
    // update translation status
    $wpdb->update($wpdb->prefix.'icl_core_status', array('status'=>CMS_REQUEST_DONE), array('rid'=>$rid, 'target'=>$lang));
    // 
    
    return true;
}

function icl_process_translated_document($request_id, $language){
    global $sitepress_settings, $wpdb;
    
    $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);       
    $tr_details = $wpdb->get_col("SELECT target FROM {$wpdb->prefix}icl_core_status WHERE rid=".$request_id);
    $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_content_status c ON t.element_id = c.nid AND t.element_type='post' AND c.rid=".$request_id);
    $translation = $iclq->cms_do_download($request_id, $language);                           
    if($translation){            
        $ret = icl_add_post_translation($trid, $translation, $language, $request_id);
        if($ret){
            $iclq->cms_update_request_status($request_id, CMS_REQUEST_DONE, $language);
        } 
        
    }        

    // if there aren't any other unfullfilled requests send a global 'done'               
    if(0 == $wpdb->get_var("SELECT COUNT(rid) FROM {$wpdb->prefix}icl_core_status WHERE rid='{$request_id}' AND status < ".CMS_REQUEST_DONE)){
        $iclq->cms_update_request_status($request_id, CMS_REQUEST_DONE, false);
    }
    return true;
}

function icl_poll_for_translations(){
    global $wpdb, $sitepress_settings;
    $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);
    $pending_requests = $iclq->cms_requests();
    foreach($pending_requests as $pr){
        $tr_details = $wpdb->get_col("SELECT target FROM {$wpdb->prefix}icl_core_status WHERE rid=".$pr['id']);
        foreach($tr_details as $language){        
            icl_process_translated_document($pr['id'],$language);
        }
    }    
}
//icl_poll_for_translations();

function icl_add_custom_xmlrpc_methods($methods){
    $methods['icl.setTranslationStatus'] = 'setTranslationStatus';
    return $methods;
}


function setTranslationStatus($args){
        global $sitepress_settings;        
        $signature   = $args[0];
        $site_id     = $args[1];
        $request_id  = $args[2];
        $language    = $args[3];
        $status      = $args[4];
        $message     = $args[5];  
        
        //check signature
        $signature_chk = sha1($sitepress_settings['access_key'].$sitepress_settings['site_id'].$request_id.$language.$status.$message);
        if($signature_chk != $signature){
            return array('err_code'=>1, 'err_str'=>'Signature mismatch');
        }
                                                                         
        if ( !get_option( 'enable_xmlrpc' ) ) {
            return array('err_code'=>2, 'err_str'=>'XML-RPC services disabled');
        }
               
        return icl_process_translated_document($request_id, $language);

} 

if($_GET['debug']==1){
    icl_poll_for_translations();
}


?>