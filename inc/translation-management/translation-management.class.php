<?php

define ( 'ICL_TM_NOT_TRANSLATED', 0);
define ( 'ICL_TM_ASSIGNED', 1);
define ( 'ICL_TM_IN_PROGRESS', 2);
define ( 'ICL_TM_NEEDS_UPDATE', 3);
define ( 'ICL_TM_COMPLETE', 4);

  
class TranslationManagement{
    
    private $selected_translator = array('ID'=>0);
    public $messages = array();
    
    function __construct(){
        
        $current_user = wp_get_current_user();
        
        add_action('init', array($this, 'init'));
        
        add_action('admin_menu', array($this, 'menu'));
        
        if(isset($_GET['icl_tm_message'])){
            $this->messages[] = array(
                'type' => isset($_GET['icl_tm_message_type']) ? $_GET['icl_tm_message_type'] : 'updated',
                'text'  => $_GET['icl_tm_message']
            );        
        }
        
        add_action('save_post', array($this, 'save_post_actions'), 11, 2); // calling *after* the Sitepress actions
                
    }
    
    function init(){
        if(isset($_POST['icl_tm_action'])){
            $this->process_request($_POST['icl_tm_action'], $_POST);
        }elseif(isset($_GET['icl_tm_action'])){
            $this->process_request($_GET['icl_tm_action'], $_GET);
        }
        
        
        add_action('icl_tm_messages', array($this, 'show_messages'));
    }
    
    function process_request($action, $data){        
        switch($action){
            case 'add_translator':
                if(wp_create_nonce('add_translator') == $data['add_translator_nonce']){
                    $this->add_translator($data['user_id'], $data['lang_pairs']);
                }
                break;
            case 'remove_translator':
                if(wp_create_nonce('remove_translator') == $data['remove_translator_nonce']){
                    $this->remove_translator($data['user_id']);
                }
                break;
            case 'edit':
                $this->selected_translator['ID'] = intval($_GET['user_id']);
                break;
        }
    }
    
    function show_messages(){
        if(!empty($this->messages)){
            foreach($this->messages as $m){
                echo '<div class="'.$m['type'].' below-h2"><p>' . $m['text'] . '</p></div>';
            }
        }
    }
    
    function add_translator($user_id, $language_pairs){
        global $wpdb;
        
        $user = new WP_User($user_id);
        $user->add_cap('translate');
        update_user_meta($user_id, $wpdb->prefix . 'language_pairs',  $language_pairs);
        
        wp_redirect('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/translation-management.php&sm=translators&icl_tm_message='.urlencode(sprintf(__('%s has been added as a translator for this site.','sitepress'),$user->data->display_name)).'&icl_tm_message_type=updated');
        
    }
    
    function remove_translator($user_id){
        global $wpdb;
        $user = new WP_User($user_id);
        $user->remove_cap('translate');
        delete_user_meta($user_id, $wpdb->prefix . 'language_pairs');
        wp_redirect('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/translation-management.php&sm=translators&icl_tm_message='.urlencode(sprintf(__('%s has been removed as a translator for this site.','sitepress'),$user->data->display_name)).'&icl_tm_message_type=updated');
    }
    
    public function get_blog_not_translators(){
        global $wpdb;
        $sql = "SELECT u.ID, u.user_login, u.display_name, m.meta_value AS caps 
                FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.id=m.user_id AND m.meta_key LIKE '{$wpdb->prefix}capabilities'";
        $res = $wpdb->get_results($sql);
        $users = array();
        foreach($res as $row){
            $user = new WP_User($row->ID);
            $caps = @unserialize($row->caps);
            if(!isset($caps['translate'])){
                $users[] = $row;    
            }
        }
        return $users;
    }

    public function get_blog_translators(){
        global $wpdb;
        $sql = "SELECT u.ID, u.user_login, u.display_name, m.meta_value AS caps  
                FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.id=m.user_id AND m.meta_key LIKE '{$wpdb->prefix}capabilities'";
        $res = $wpdb->get_results($sql);
        $users = array();
        foreach($res as $row){
            $user = new WP_User($row->ID);
            $caps = @unserialize($row->caps);
            if(isset($caps['translate'])){
                $users[] = $row;    
            }
        }
        return $users;
    }
    
    function get_selected_translator(){
        global $wpdb;
        if($this->selected_translator['ID']){
            $user = new WP_User($this->selected_translator['ID']);
            $this->selected_translator['display_name'] =  $user->data->display_name;
            $this->selected_translator['user_login'] =  $user->data->user_login;
            $this->selected_translator['language_pairs'] = get_user_meta($this->selected_translator['ID'], $wpdb->prefix.'language_pairs', true);
        }else{
            $this->selected_translator['ID'] = 0;
        }
        return (object)$this->selected_translator;    
    }
    
    /* MENU */
    function menu(){
        global $sitepress_settings;
        if($sitepress_settings['basic_menu']){        
            $top_level_page = 'languages';
        }else{
            $top_level_page = 'overview';
        }
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/'.$top_level_page.'.php', __('Translation Management','sitepress'), __('Translation Management','sitepress'), 
            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/translation-management.php');
    }
    
    function save_post_actions($post_id, $post){
        global $wpdb, $sitepress;
        // skip revisions
        if($post->post_type == 'revision'){
            return;
        }
        // skip auto-drafts
        if($post->post_status == 'auto-draft'){
            return;
        }
        
        if($_POST['icl_trid']){
            //
            // get original document
            $translations = $sitepress->get_element_translations($_POST['icl_trid'], 'post_' . $post->post_type);
            foreach($translations as $t){
                if($t->original){
                    $origin = $t->language_code;
                }
            }
                        
            $rid = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_content_status WHERE nid = %d"), $post_id);
            if(!$rid){                
                $wpdb->insert($wpdb->prefix.'icl_content_status', array('nid' => $post_id, 'md5'=>$this->post_md5($post), 'timestamp'=>date('Y-m-d H:i:s')));                
                $rid = $wpdb->insert_id;
            }else{
                $wpdb->update($wpdb->prefix.'icl_content_status', array('md5'=>$this->post_md5($post)), array('rid'=>$rid));                
            }
            
            // add update icl_core_status entry
            $id = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_core_status WHERE rid = %d AND target= = %s"), $rid, $_POST['icl_post_language']);
            if(!$id){
                $wpdb->insert($wpdb->prefix.'icl_core_status', array('rid' => $rid, 'origin' => $origin, 'target' => $_POST['icl_post_language'], 'status' => 1 ));  //!!!!!!!               
            }else{
                $wpdb->update($wpdb->prefix.'icl_content_status', array('md5'=>$this->post_md5($post)), array('rid'=>$rid));                
            }
            
        }
        
        // if this post is a translation of another one add a icl_content_status entry
        //debug_array($post);
        //die();
    }
    
    /**
    * calculate post md5
    * 
    * @param object|int $post
    * @return string
    * 
    * @todo full support for custom posts and custom taxonomies
    */
    function post_md5($post){
        
        if(is_numeric($post)){
            $post = get_post($post);    
        }
        
        $post_type = $post->post_type;
        
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
            
            global $wpdb, $sitepress_settings;
            // get custom taxonomies
            $taxonomies = $wpdb->get_col("
                SELECT DISTINCT tx.taxonomy 
                FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->term_relationships} tr ON tx.term_taxonomy_id = tr.term_taxonomy_id
                WHERE tr.object_id = {$post_id}
            ");
            sort($taxonomies, SORT_STRING);
            foreach($taxonomies as $t){
                if($sitepress_settings['taxonomies_sync_option'][$t] == 1){
                    $taxs = array();
                    foreach(wp_get_object_terms($post_id, $t) as $trm){
                        $taxs[] = $trm->name;
                    }
                    if($taxs){
                        sort($taxs,SORT_STRING);
                        $all_taxs[] = '['.$t.']:'.join(',',$taxs);
                    }
                }
            }
        }
        
        include_once ICL_PLUGIN_PATH . '/inc/plugins-texts-functions.php';

        $custom_fields = icl_get_posts_translatable_fields();
        $custom_fields_values = array();
        foreach($custom_fields as $cf){
            if ($cf->translate) {
                $custom_fields_values[] = get_post_meta($post_id, $cf->attribute_name, true);
            }
        }
        
        $md5str =         
            $post->post_title . ';' . 
            $post->post_content . ';' . 
            join(',',(array)$post_tags).';' . 
            join(',',(array)$post_categories) . ';' . 
            join(',', $custom_fields_values);
        if(!empty($all_taxs)){
            $md5str .= ';' . join(';', $all_taxs);
        }    
        $md5 = md5($md5str);
                    
        return $md5;        
    }
    
    /**
    * get documents
    * 
    * @param string $lang
    * @param string $to_lang
    * @param srting $tstatus
    * @param string $status
    * @param string $type
    * @param string $title_match
    * @param int $limit
    * @param string $from_date
    * @param string $to_date
    */
    function get_documents($lang, $to_lang = '', $tstatus, $status=false, $type=false, $title_match = '', $limit = 20, $from_date = false,$to_date = false){
        global $wpdb, $wp_query, $sitepress;
        
        $where = "WHERE c.active = 1";
        $order = "ORDER BY p.post_date DESC";
        $join = '';
        
        if(isset($_GET['post_id'])){ // this overrides the others
            $where .= " AND p.ID=" . (int)$_GET['post_id'];  
        }else{
            
            if(!$to_lang){
                switch($tstatus){
                    case 'not':
                        $where .= " AND (c.status IS NULL OR c.status = ".ICL_TM_NOT_TRANSLATED." OR c.status = ".ICL_TM_NEEDS_UPDATE.")";
                        break;
                    case 'in_progress':
                        $where .= " AND (c.status = ".ICL_TM_IN_PROGRESS.")";
                        break;
                    case 'complete':
                        $where .= " AND (c.status = ".ICL_TM_COMPLETE.")";
                        break;                    
                }
            }else{
                $join .= "LEFT JOIN {$wpdb->prefix}icl_core_status cr ON cr.rid = c.rid";    
                $where .= " AND cr.target = '{$to_lang}' ";    
                switch($tstatus){
                    case 'not':
                        $where .= " AND (cr.status IS NULL OR cr.status = ".ICL_TM_NOT_TRANSLATED." OR cr.status = ".ICL_TM_NEEDS_UPDATE.")";
                        break;
                    case 'in_progress':
                        $where .= " AND (cr.status = ".ICL_TM_IN_PROGRESS.")";
                        break;
                    case 'complete':
                        $where .= " AND (cr.status = ".ICL_TM_COMPLETE.")";
                        break;                    
                }
            }
            
            $t_el_types = array_keys($sitepress->get_translatable_documents());
            if($type){
                $where .= " AND p.post_type = '{$type}'";
                $icl_el_type_where = " AND t.element_type = 'post_{$type}'";
            }else{
                $where .= " AND p.post_type IN ('".join("','",$t_el_types)."')";
                foreach($t_el_types as $k=>$v){
                    $t_el_types[$k] = 'post_' . $v;
                }
                $icl_el_type_where .= " AND t.element_type IN ('".join("','",$t_el_types)."')";
            }  
            
            if($title_match){
                $where .= " AND p.post_title LIKE '%".$wpdb->escape($title_match)."%'";
            }
            
            if($status){
                $where .= " AND p.post_status = '{$status}'";
            }        
            
            $where .= " AND t.language_code='{$lang}'";
            
            if($from_date and $to_date){
                $where .= " AND p.post_date > '{$from_date}' AND p.post_date < '{$to_date}'";
            }
        }
            
        if(!isset($_GET['paged'])) $_GET['paged'] = 1;
        $offset = ($_GET['paged']-1)*$limit;
        $limit_str = "LIMIT " . $offset . ',' . $limit;
        
        // exclude trashed posts
        $where .= " AND p.post_status <> 'trash'";
        
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS p.ID as post_id, p.post_title, p.post_type, p.post_status, post_content, 
                n.md5 <> c.md5 AS updated, c.rid
            FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id {$icl_el_type_where}
                LEFT JOIN {$wpdb->prefix}icl_node n ON p.ID = n.nid
                LEFT JOIN {$wpdb->prefix}icl_content_status c ON c.nid=p.ID            
                {$join}
            {$where}                
            {$order} 
            {$limit_str}
        ";    
        $results = $wpdb->get_results($sql);    
        
        $count = $wpdb->get_var("SELECT FOUND_ROWS()");
        
        if(!empty($results)){
            foreach($results as $k=>$v){
                $rids[$k] = $v->rid;
            }
            if(!$to_lang){
                $rids = array_unique($rids);
                $res = $wpdb->get_results("SELECT rid, target, status FROM {$wpdb->prefix}icl_core_status WHERE rid IN ('".join(',',$rids)."')");
            }else{
                $res = $wpdb->get_results($wpdb->prepare("SELECT rid, target, status FROM {$wpdb->prefix}icl_core_status WHERE target=%s AND rid IN (".join(',',$rids).")",$to_lang));
            }
            foreach($res as $row){
                $language_status_by_rid[$row->rid][$row->target] = $row->status;
            }
            foreach($results as $k=>$v){
                $results[$k]->language_status = $language_status_by_rid[$v->rid];
            }            
        }
        

        $wp_query->found_posts = $count;
        $wp_query->query_vars['posts_per_page'] = $limit;
        $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit);
          
          
        return $results;
        
    }   
    
    /**
    * returns icon file name according to status code
    * 
    * @param int $status
    */
    public function status2img_filename($status){
        switch($status){
            case ICL_TM_NOT_TRANSLATED: $img_file = 'not-translated.png'; break;
            case ICL_TM_IN_PROGRESS: $img_file = 'in-progress.png'; break;
            case ICL_TM_NEEDS_UPDATE: $img_file = 'needs-update.png'; break;
            case ICL_TM_COMPLETE: $img_file = 'complete.png'; break;
            default: $img_file = '';
        }
        return $img_file;
    } 
    
}
  
?>
