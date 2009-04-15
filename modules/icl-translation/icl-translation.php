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

//wp_enqueue_style('icl-translation-style', ICL_PLUGIN_URL . '/modules/icl-translation/css/style.css', array(), '0.1');

if($_GET['debug']):
$iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);
$pending_requests = $iclq->cms_requests();
foreach($pending_requests as $pr){
    $request_id = $pr['id'];
    $tr_details = $wpdb->get_col("SELECT target FROM {$wpdb->prefix}icl_core_status WHERE rid=".$request_id);
    $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_content_status c ON t.element_id = c.nid AND t.element_type='post' AND c.rid=".$request_id);
    foreach($tr_details as $lang){
        $translation = $iclq->cms_do_download($request_id, $lang);           
        $iclq->cms_update_request_status($request_id, CMS_REQUEST_DONE, $lang);
        //print_r($translation);
        if($translation){
            $ret = icl_add_post_translation($trid, $lang, $translation['title'], $translation['body'], $translation['categories'], $translation['tags'], $request_id);
            if($ret){
                $iclq->cms_update_request_status($request_id, CMS_REQUEST_DONE, $lang);
            }            
        }        
    }
}
endif;

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
            )            
        ),
        'target_languages' => $target_languages
    );
    
    if($post_type=='post'){
        $data['contents']['categories'] = array(
                'translate'=>1,
                'data'=> implode(',', array_map(create_function('$e', 'return \'"\'.base64_encode($e).\'"\';'), $post_categories)),
                'format'=>'csv_base64'
            );    
        $data['contents']['tags'] = array(
                'translate'=>1,
                'data'=> implode(',', array_map(create_function('$e', 'return \'"\'.base64_encode($e).\'"\';'), $post_tags)),
                'format'=>'csv_base64'
            );                
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

function icl_add_post_translation($trid, $lang, $title, $body, $categories, $tags, $rid){
    global $wpdb;
    $lang_code = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE english_name='".$wpdb->escape($lang)."'");
    if(!$lang_code){        
        return false;
    }
        
    $original_post_details = $wpdb->get_row("
        SELECT p.post_author, t.language_code
        FROM {$wpdb->prefix}icl_translations t 
        JOIN {$wpdb->posts} p ON t.element_id = p.ID
        WHERE t.element_type='post' AND trid='{$trid}' AND (source_language_code IS NULL OR source_language_code ='')
    ");

    
    // is update?
    $post_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND trid='{$trid}' AND language_code='{$lang_code}'");
    if($post_id){
        $is_update = true;
        $postarr['post_ID'] = $_POST['post_ID'] = $post_id;
    }else{
        $is_update = false;
    } 
    
    $postarr['post_title'] = $title;
    $postarr['post_content'] = $body;
    $postarr['tags_input'] = join(',',$tags);
    //$postarr['post_category'] = join(',',$tags);
    $postarr['post_author'] = $original_post_details->post_author;  
    $_POST['trid'] = $trid;
    $_POST['lang'] = $lang_code;
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
?>