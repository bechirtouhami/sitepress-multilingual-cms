<?php
function icl_translation_admin_menu(){
    add_management_page(__('Translation Dashboard', 'sitepress'), __('Translation Dashboard', 'sitepress'), 'edit_posts', dirname(__FILE__).'/icl-translation-dashboard.php');
}

function icl_translation_js(){
    wp_enqueue_script('icl-translation-scripts', ICL_PLUGIN_URL . '/modules/icl-translation/js/icl-translation.js', array(), '0.1');
}

function icl_translation_send_post($post_id, $target_languages, $post_type='post'){
    global $sitepress_settings, $wpdb, $sitepress;
    
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
                'data'=>base64_encode($post->post_title),
                'format'=>'base64'
            ),
            'body' => array(
                'translate'=>1,
                'data'=>base64_encode($post->post_content),
                'format'=>'base64'
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
                $wpdb->insert($wpdb->prefix.'icl_core_status', array('rid'=>$res,
                                                                     'origin'=>$sitepress->get_language_code($orig_lang),
                                                                     'target'=>$sitepress->get_language_code($new_lang),
                                                                     'status'=>CMS_REQUEST_WAITING_FOR_PROJECT_CREATION));
            }
            $wpdb->update($wpdb->prefix.'icl_content_status', array('rid'=>$res, 'md5'=>$md5, 'timestamp'=>$timestamp), array('rid'=>$previous_rid)); //update rid            
            $wpdb->update($wpdb->prefix.'icl_core_status', array('rid'=>$res), array('rid'=>$previous_rid)); //update rid
            // languages added?
        }else{            
            $wpdb->insert($wpdb->prefix.'icl_content_status', array('rid'=>$res, 'nid'=>$post_id, 'timestamp'=>$timestamp, 'md5'=>$md5)); //insert rid   
            foreach($target_languages as $targ_lang){
                $wpdb->insert($wpdb->prefix.'icl_core_status', array('rid'=>$res,
                                                                     'origin'=>$sitepress->get_language_code($orig_lang),
                                                                     'target'=>$sitepress->get_language_code($targ_lang),
                                                                     'status'=>CMS_REQUEST_WAITING_FOR_PROJECT_CREATION));
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
    // minor edit - don't update MD5's
    if($_POST['icl_minor_edit']){
        return;
    } 
    if($_POST['autosave']) return;
    if($_POST['action']=='post-quickpress-publish'){
        $post_id = $p;            
        $_POST['post_type']='post';
    }elseif(isset($_POST['post_ID'])){
        $post_id = $_POST['post_ID'];
    }else{
        $post_id = $p;
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
    $pids = array(0);
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
            WHERE p.ID IN (".join(',', $pids).")
            AND status <> ".CMS_TARGET_LANGUAGE_DONE."
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
    global $wpdb, $sitepress_settings, $sitepress;
    $lang_code = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE english_name='".$wpdb->escape($lang)."'");
    if(!$lang_code){        
        return false;
    }
        
    $original_post_details = $wpdb->get_row("
        SELECT p.post_author, p.post_type, p.post_status, p.post_parent, p.menu_order, t.language_code
        FROM {$wpdb->prefix}icl_translations t 
        JOIN {$wpdb->posts} p ON t.element_id = p.ID
        WHERE t.element_type='post' AND trid='{$trid}' AND p.ID = '{$translation['original_id']}'
    ");
    
    _icl_content_fix_image_paths_in_body($translation);
    _icl_content_fix_relative_link_paths_in_body($translation);
    
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
                    // get original category parent id
                    $original_category_parent_id = $wpdb->get_var("SELECT parent FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=".$translated_cats_ids[$k]);
                    if($original_category_parent_id){                        
                        $original_category_parent_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='category' AND term_id=".$original_category_parent_id);
                        $category_parent_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='category' AND element_id=".$original_category_parent_id); 
                        // get id of the translated category parent
                        $category_parent_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE language_code='{$lang_code}' AND trid=".$category_parent_trid); 
                        if($category_parent_id){
                            $category_parent_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='category' AND term_taxonomy_id=".$category_parent_id);
                        }                        
                    }else{
                        $category_parent_id = 0;
                    }
                    $tmp = wp_insert_term($v, 'category', array('parent'=>$category_parent_id));
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
        
    }elseif($original_post_details->post_type=='page'){
        if($original_post_details->post_parent){
            $post_parent_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND element_id='{$original_post_details->post_parent}'");
            if($post_parent_trid){
                $parent_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND trid='{$post_parent_trid}' AND language_code='{$lang_code}'");
            }            
        }        
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
    $postarr['post_status'] = !$sitepress_settings['translated_document_status'] ? 'draft' : $original_post_details->post_status;  
    
    if(isset($parent_id)){
        $_POST['post_parent'] = $postarr['post_parent'] = $parent_id;  
        $_POST['parent_id'] = $postarr['parent_id'] = $parent_id;  
    }
    
    $_POST['trid'] = $trid;
    $_POST['lang'] = $lang_code;
    $_POST['skip_sitepress_actions'] = true;
        
    global $wp_rewrite;
    if(!isset($wp_rewrite)) $wp_rewrite = new WP_Rewrite();
    
    $new_post_id = wp_insert_post($postarr);

    if(!$new_post_id){
        return false;
    }
        
    // record trids
    if(!$is_update){
        $wpdb->insert($wpdb->prefix.'icl_translations', array('element_type'=>'post', 'element_id'=>$new_post_id, 'trid'=> $trid, 'language_code'=>$lang_code, 'source_language_code'=>$original_post_details->language_code));
    }
    
    _icl_content_fix_links_to_translated_content($new_post_id, $lang_code);
    
    // update translation status
    $wpdb->update($wpdb->prefix.'icl_core_status', array('status'=>CMS_TARGET_LANGUAGE_DONE), array('rid'=>$rid, 'target'=>$sitepress->get_language_code($lang)));
    // 
    
    // Now try to fix links in other translated content that may link to this post.
    $sql = "SELECT
                nid
            FROM
                {$wpdb->prefix}icl_node n
            JOIN
                {$wpdb->prefix}icl_translations t
            ON
                n.nid = t.element_id
            WHERE
                n.links_fixed = 0 AND t.language_code = '{$lang_code}'";
                
    $needs_fixing = $wpdb->get_results($sql);
    foreach($needs_fixing as $id){
        _icl_content_fix_links_to_translated_content($id->nid, $lang_code);
    }
    
    return true;
}

function icl_process_translated_document($request_id, $language){
    global $sitepress_settings, $wpdb;
    
    $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);       
    $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_content_status c ON t.element_id = c.nid AND t.element_type='post' AND c.rid=".$request_id);
    $translation = $iclq->cms_do_download($request_id, $language);                           
    if($translation){            
        $ret = icl_add_post_translation($trid, $translation, $language, $request_id);
        if($ret){
            $iclq->cms_update_request_status($request_id, CMS_TARGET_LANGUAGE_DONE, $language);
        } 
        
    }        

    // if there aren't any other unfullfilled requests send a global 'done'               
    if(0 == $wpdb->get_var("SELECT COUNT(rid) FROM {$wpdb->prefix}icl_core_status WHERE rid='{$request_id}' AND status < ".CMS_TARGET_LANGUAGE_DONE)){
        $iclq->cms_update_request_status($request_id, CMS_REQUEST_DONE, false);
    }
    return true;
}

function icl_poll_for_translations(){
    global $wpdb, $sitepress_settings, $sitepress;
    
    include dirname(__FILE__).'/icl-language-ids.inc';
    
    $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);
    $pending_requests = $iclq->cms_requests();
    foreach($pending_requests as $pr){
        
        $cms_request_xml = $iclq->cms_request_translations($pr['id']);
        if(isset($cms_request_xml['cms_target_languages']['cms_target_language'])){
            foreach($cms_request_xml['cms_target_languages']['cms_target_language'] as $target){
                if(isset($target['attr'])){
                    $status = $target['attr']['status'];
                    $lang_id = (int)$target['attr']['language_id'];
                    $language = $icl_language_id2name[$lang_id];
                    $lang_code = $sitepress->get_language_code($language);
                    $wpdb->query("UPDATE {$wpdb->prefix}icl_core_status SET status='{$status}' WHERE rid='{$pr['id']}' AND target='{$lang_code}'");
                    
                }
                
            }
        }
        
        // process translated languages
        $tr_details = $wpdb->get_col("SELECT target FROM {$wpdb->prefix}icl_core_status WHERE rid=".$pr['id']." AND status = ".CMS_TARGET_LANGUAGE_TRANSLATED);
        foreach($tr_details as $language){
            $language = $sitepress->get_language_details($language);
            icl_process_translated_document($pr['id'],$language['english_name']);
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

function icl_get_post_translation_status($post_id){
    global $wpdb;
    
    $sql = "
        SELECT  c.rid, r.target, r.status, n.md5<>c.md5 AS updated
        FROM 
            {$wpdb->prefix}icl_content_status c
            JOIN {$wpdb->prefix}icl_core_status r ON c.rid = r.rid
            JOIN {$wpdb->prefix}icl_node n ON c.nid = n.nid
        WHERE c.nid = {$post_id}
    ";
    $status = $wpdb->get_results($sql);
    return $status;
}

function icl_display_post_translation_status($post_id){
    global $wpdb, $sitepress;                                                                                                           
    $tr_info = $wpdb->get_row("
        SELECT t.trid, lt.name, t.language_code, t.source_language_code 
        FROM {$wpdb->prefix}icl_translations t LEFT JOIN {$wpdb->prefix}icl_languages_translations lt ON t.source_language_code=lt.language_code
        WHERE t.element_type='post' AND t.element_id={$post_id} AND lt.display_language_code = '".$sitepress->get_default_language()."'"
        );
    if($post_id==0){
        return;
    }
    
    if($tr_info->name && $tr_info->source_language_code && ($tr_info->source_language_code!=$tr_info->language_code) ){
        echo '<div style="text-align:center;clear:both;">'. sprintf(__('Translated from %s'),$tr_info->name).'</div>';
        echo '<div style="text-align:center;clear:both;color:#888;">'. __('This translation is maintained by ICanLocalize. Edits that you do will be overwritten when the translator does an update.').'</div>';        
        return;
    }
    
    $post_updated = $wpdb->get_var("SELECT c.md5<>n.md5 FROM {$wpdb->prefix}icl_content_status c JOIN {$wpdb->prefix}icl_node n ON c.nid=n.nid WHERE c.nid=".$post_id);
    
    $status = icl_get_post_translation_status($post_id);    
    foreach($status as $k=>$v){
        $status[$v->target] = $v;
        unset($status[$k]);
    }
    
    if(empty($status)){
        echo '<table class="widefat">';
        echo '<tr><td align="center">';
        echo __('Not translated');
        echo '</td></tr>';
        echo '</table>';
    }else{          

        echo '<p style="float:left">';
        echo __('Minor edit - don\'t update translation','sitepress');        
        echo '&nbsp;<input type="checkbox" name="icl_minor_edit" value="1" />';
        echo '</p>';
        echo '<br clear="all" />';
        
        echo '<p><strong>'.__('Translation status:','sitepress').'</strong></p>';
        echo '<table class="widefat">';        
        $oddcolumn = true;
        $active_languages = $sitepress->get_active_languages();    
        foreach($active_languages as $al){            
            if($al['code']==$sitepress->get_default_language()) continue;
            $oddcolumn = !$oddcolumn;            
            echo '<tr'; if($oddcolumn) echo ' class="alternate"'; echo '>';
            echo '<td scope="col">'.sprintf(__('Translation to %s'), $al['display_name']).'</td>';            
            echo '<td align="right" scope="col">';            
            if($status[$al['code']]->status==CMS_TARGET_LANGUAGE_DONE && $post_updated){
                echo __('translation needs update','sitepress');
            }else{
                switch($status[$al['code']]->status){
                    //case CMS_REQUEST_WAITING_FOR_PROJECT_CREATION: echo __('Waiting for project creation','sitepress');break;
                    //case CMS_REQUEST_PROJECT_CREATION_REQUESTED: echo __('Project creation requested','sitepress');break;
                    //case CMS_REQUEST_CREATING_PROJECT: echo __('Creating project','sitepress');break;
                    //case CMS_REQUEST_RELEASED_TO_TRANSLATORS: echo __('Released to translators','sitepress');break;
                    //case CMS_REQUEST_TRANSLATED: echo __('Translated on server','sitepress');break;
                    case CMS_REQUEST_WAITING_FOR_PROJECT_CREATION: echo __('Translation in progress','sitepress');break;
                    case CMS_TARGET_LANGUAGE_DONE: echo __('Translation complete','sitepress');break;
                    case CMS_REQUEST_FAILED: echo __('Request failed','sitepress');break;
                    default: echo __('Not translated','sitepress');
                }
            }
            echo '</td>';
            echo '</td>';
            echo '</tr>';            
        }        
        echo '</table>';
    }    
}

function icl_decode_translation_status_id($status){
    switch($status){
        case CMS_REQUEST_WAITING_FOR_PROJECT_CREATION: $st = __('Waiting for project creation','sitepress');break;
        case CMS_REQUEST_PROJECT_CREATION_REQUESTED: $st = __('Project creation requested','sitepress');break;
        case CMS_REQUEST_CREATING_PROJECT: $st = __('Creating project','sitepress');break;
        case CMS_REQUEST_RELEASED_TO_TRANSLATORS: $st = __('Released to translators','sitepress');break;
        case CMS_REQUEST_TRANSLATED: $st = __('Translated on server','sitepress');break;
        case CMS_REQUEST_WAITING_FOR_PROJECT_CREATION: $st = __('Translation in progress','sitepress');break;
        case CMS_REQUEST_DONE: $st = __('Translation complete','sitepress');break;
        case CMS_REQUEST_FAILED: $st = __('Request failed','sitepress');break;
        default: $st = __('Not translated','sitepress');
    }
    
    return $st;
}

function _icl_content_fix_image_paths_in_body(&$translation) {
    $body = $translation['body'];
    $image_paths = _icl_content_get_image_paths($body);
    
    $source_path = post_permalink($translation['original_id']);
  
    foreach($image_paths as $path) {
  
        $src_path = resolve_url($source_path, $path[2]);
        if ($src_path != $path[2]) {
            $search = $path[1] . $path[2] . $path[1];
            $replace = $path[1] . $src_path . $path[1];
            $new_link = str_replace($search, $replace, $path[0]);
      
            $body = str_replace($path[0], $new_link, $body);
      
          
        }
    
    }
    $translation['body'] = $body;
}

/**
 * get the paths to images in the body of the content
 */

function _icl_content_get_image_paths($body) {

  $regexp_links = array(
                      "/<img\ssrc\s*=\s*([\"\']??)([^\"]*)\".*>/siU",
                      "/&lt;script\ssrc\s*=\s*([\"\']??)([^\"]*)\".*>/siU",
                      "/<embed\ssrc\s*=\s*([\"\']??)([^\"]*)\".*>/siU",
                      );

  $links = array();

  foreach($regexp_links as $regexp) {
    if (preg_match_all($regexp, $body, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $links[] = $match;
      }
    }
  }

  return $links;
}


/**
 * Resolve a URL relative to a base path. This happens to work with POSIX
 * filenames as well. This is based on RFC 2396 section 5.2.
 */
function resolve_url($base, $url) {
        if (!strlen($base)) return $url;
        // Step 2
        if (!strlen($url)) return $base;
        // Step 3
        if (preg_match('!^[a-z]+:!i', $url)) return $url;
        $base = parse_url($base);
        if ($url{0} == "#") {
                // Step 2 (fragment)
                $base['fragment'] = substr($url, 1);
                return unparse_url($base);
        }
        unset($base['fragment']);
        unset($base['query']);
        if (substr($url, 0, 2) == "//") {
                // Step 4
                return unparse_url(array(
                        'scheme'=>$base['scheme'],
                        'path'=>$url,
                ));
        } else if ($url{0} == "/") {
                // Step 5
                $base['path'] = $url;
        } else {
                // Step 6
                $path = explode('/', $base['path']);
                $url_path = explode('/', $url);
                // Step 6a: drop file from base
                array_pop($path);
                // Step 6b, 6c, 6e: append url while removing "." and ".." from
                // the directory portion
                $end = array_pop($url_path);
                foreach ($url_path as $segment) {
                        if ($segment == '.') {
                                // skip
                        } else if ($segment == '..' && $path && $path[sizeof($path)-1] != '..') {
                                array_pop($path);
                        } else {
                                $path[] = $segment;
                        }
                }
                // Step 6d, 6f: remove "." and ".." from file portion
                if ($end == '.') {
                        $path[] = '';
                } else if ($end == '..' && $path && $path[sizeof($path)-1] != '..') {
                        $path[sizeof($path)-1] = '';
                } else {
                        $path[] = $end;
                }
                // Step 6h
                $base['path'] = join('/', $path);

        }
        // Step 7
        return unparse_url($base);
}

function unparse_url($parsed)
    {
    if (! is_array($parsed)) return false;
    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
    $uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
    $uri .= isset($parsed['host']) ? $parsed['host'] : '';
    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
    if(isset($parsed['path']))
        {
        $uri .= (substr($parsed['path'],0,1) == '/')?$parsed['path']:'/'.$parsed['path'];
        }
    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
    return $uri;
    }

function _icl_content_fix_relative_link_paths_in_body(&$translation) {
    $body = $translation['body'];
    $link_paths = _icl_content_get_link_paths($body);

    $source_path = post_permalink($translation['original_id']);

    foreach($link_paths as $path) {
      
        $src_path = resolve_url($source_path, $path[2]);
        if ($src_path != $path[2]) {
            $search = $path[1] . $path[2] . $path[1];
            $replace = $path[1] . $src_path . $path[1];
            $new_link = str_replace($search, $replace, $path[0]);
            
            $body = str_replace($path[0], $new_link, $body);
        }
      
    }
    $translation['body'] = $body;
}

function _icl_content_get_link_paths($body) {
  
    $regexp_links = array(
                        "/<a\shref\s*=\s*([\"\']??)([^\"]*)\">(.*)<\/a>/siU",
                        );
    
    $links = array();
    
    foreach($regexp_links as $regexp) {
        if (preg_match_all($regexp, $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
              $links[] = $match;
            }
        }
    }
    
    return $links;
}

function _icl_content_make_links_sticky($post_id) {
    global $iclAbsoluteLinks;
    
    $body = $translation['body'];

    if(!$sitepress_settings['modules']['absolute-links']['enabled']){
        // create the object
        require ICL_PLUGIN_PATH . '/modules/absolute-links/absolute-links-plugin.php';
        $icl_abs_links = new AbsoluteLinksPlugin();
    } else {
        // use the global object
        $icl_abs_links = $iclAbsoluteLinks;
    }
    
    $icl_abs_links->process_post($post_id);

}

function _icl_content_fix_links_to_translated_content($new_post_id, $target_lang_code){
    global $wpdb, $sitepress;
    //_icl_content_make_links_sticky($new_post_id);
    
    $post = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE ID={$new_post_id}");

    $base_url_parts = parse_url(get_option('home'));
    
    $body = $post->post_content;
    $new_body = $body;
    
    $links = _icl_content_get_link_paths($body);
    
    $all_links_fixed = 1;
    
    foreach($links as $link) {
        $path = $link[2];
        $url_parts = parse_url($path);
        
        if($base_url_parts['host'] == $url_parts['host'] and
                $base_url_parts['scheme'] == $url_parts['scheme'] and
                isset($url_parts['query'])) {
            $query_parts = split('&', $url_parts['query']);
            foreach($query_parts as $query){
                
                // find p=id or cat=id or tag=id queries
                
                list($key, $value) = split('=', $query);
                $translations = NULL;
                if($key == 'p'){
                    $kind = 'post';
                } else if($key == 'cat'){
                    $link_id = (int)$value;
                    $trid = $sitepress->get_element_trid($link_id, 'category');
                    if($trid !== NULL){
                        $translations = $sitepress->get_element_translations($trid, 'category');
                    }
                } else if($key == 'tag'){
                    $link_id = (int)$value;
                    $trid = $sitepress->get_element_trid($link_id, 'tag');
                    if($trid !== NULL){
                        $translations = $sitepress->get_element_translations($trid, 'tag');
                    }
                } else {
                    continue;
                }

                if ($sitepress->get_language_for_element($link_id, $kind) == $target_lang_code) {
                    // link already points to the target language.
                    continue;
                }

                $link_id = (int)$value;
                $trid = $sitepress->get_element_trid($link_id, $kind);
                if($trid !== NULL){
                    $translations = $sitepress->get_element_translations($trid, $kind);
                }
                
                if(isset($translations[$target_lang_code])){
                    
                    // use the new translated id in the link path.
                    
                    $translated_id = $translations[$target_lang_code]->element_id;
                    
                    $replace = $key . '=' . $translated_id;
                    
                    $new_link = str_replace($query, $replace, $link[0]);
                    
                    // replace the link in the body.
                    
                    $new_body = str_replace($link[0], $new_link, $body);
                } else {
                    // translation not found for this.
                    $all_links_fixed = 0;
                }
            }
        }
        
        
    }
    
    if ($new_body != $body){
        // save changes to the database.
        $post = $wpdb->query("UPDATE {$wpdb->posts} SET post_content='{$new_body}' WHERE ID={$new_post_id}");
    }
    
    // save the all links fixed status to the database.
    $wpdb->query("UPDATE {$wpdb->prefix}icl_node SET links_fixed='{$all_links_fixed}' WHERE nid={$new_post_id}");
}

?>
