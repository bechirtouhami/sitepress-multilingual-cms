<?php

define ( 'ICL_TM_NOT_TRANSLATED', 0);
define ( 'ICL_TM_WAITING_FOR_TRANSLATOR', 1);
define ( 'ICL_TM_IN_PROGRESS', 2);
define ( 'ICL_TM_NEEDS_UPDATE', 3);  //virt. status code (based on needs_update)
define ( 'ICL_TM_COMPLETE', 10);

$asian_languages = array('ja', 'ko', 'zh-hans', 'zh-hant', 'mn', 'ne', 'hi', 'pa', 'ta', 'th');
  
class TranslationManagement{
    
    private $selected_translator = array('ID'=>0);
    public $messages = array();    
    public $dashboard_select = array();
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'menu'));
        
        if(isset($_GET['icl_tm_message'])){
            $this->messages[] = array(
                'type' => isset($_GET['icl_tm_message_type']) ? $_GET['icl_tm_message_type'] : 'updated',
                'text'  => $_GET['icl_tm_message']
            );        
        }
        
        add_action('save_post', array($this, 'save_post_actions'), 11, 2); // calling *after* the Sitepress actions
        
        add_action('icl_ajx_custom_call', array($this, 'ajax_calls'), 10, 2);
                
        if(isset($_GET['sm']) && $_GET['sm'] == 'dashboard'){session_start();}
                
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
            case 'dashboard_filter':
                $_SESSION['translation_dashboard_filter'] = $_POST['filter'];
                debug_array($_SESSION['translation_dashboard_filter']);
                wp_redirect('admin.php?page='.ICL_PLUGIN_FOLDER . '/menu/translation-management.php&sm=dashboard');
                break;  
           case 'sort':
                if(isset($_GET['sort_by'])) $_SESSION['translation_dashboard_filter']['sort_by'] = $_GET['sort_by'];
                if(isset($_GET['sort_order'])) $_SESSION['translation_dashboard_filter']['sort_order'] = $_GET['sort_order'];
                break;
           case 'reset_filters':
                unset($_SESSION['translation_dashboard_filter']);
                break;          
           case 'send_jobs':
                $this->send_jobs($_POST);
                break;                                
        }
    }
    
    function ajax_calls($call, $data){
        global $sitepress, $sitepress_settings;
        switch($call){
            case 'save_dashboard_setting':
                $iclsettings['dashboard'] = $sitepress_settings['dashboard'];
                if(isset($data['setting']) && isset($data['value'])){
                    $iclsettings['dashboard'][$data['setting']] = $data['value'];
                    $sitepress->save_settings($iclsettings);    
                }
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
            $row->language_pairs = get_usermeta($row->ID, $wpdb->prefix.'language_pairs', true);
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
            foreach(wp_get_object_terms($post->ID, 'post_tag') as $tag){
                $post_tags[] = $tag->name;
            }
            if(is_array($post_tags)){
                sort($post_tags, SORT_STRING);
            }        
            foreach(wp_get_object_terms($post->ID, 'category') as $cat){
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
                WHERE tr.object_id = {$post->ID}
            ");
            sort($taxonomies, SORT_STRING);
            foreach($taxonomies as $t){
                if($sitepress_settings['taxonomies_sync_option'][$t] == 1){
                    $taxs = array();
                    foreach(wp_get_object_terms($post->ID, $t) as $trm){
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
                $custom_fields_values[] = get_post_meta($post->ID, $cf->attribute_name, true);
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
    * @param array $args
    */
    function get_documents($args){
        
        extract($args);
        
        global $wpdb, $wp_query, $sitepress;
        
        $t_el_types = array_keys($sitepress->get_translatable_documents());
        
        // SELECT
        $select = " p.ID AS post_id, p.post_title, p.post_content, p.post_type, p.post_status, p.post_date, t.source_language_code <> '' AS is_translation";
        if($to_lang){
            $select .= ", iclts.status, iclts.needs_update";
        }else{
            foreach($sitepress->get_active_languages() as $lang){
                if($lang['code'] == $from_lang) continue;
                $tbl_alias_suffix = str_replace('-','_',$lang['code']);                
                $select .= ", iclts_{$tbl_alias_suffix}.status AS status_{$tbl_alias_suffix}, iclts_{$tbl_alias_suffix}.needs_update AS needs_update_{$tbl_alias_suffix}";
            }
        }
        
        // FROM
        $from   = " {$wpdb->posts} p";
        
        // JOIN
        $join = "";        
        $join   .= " LEFT JOIN {$wpdb->prefix}icl_translations t ON t.element_id=p.ID\n";    
        if($to_lang){
            $tbl_alias_suffix = str_replace('-','_',$to_lang);
            $join .= " LEFT JOIN {$wpdb->prefix}icl_translations iclt_{$tbl_alias_suffix} 
                        ON iclt_{$tbl_alias_suffix}.trid=t.trid AND iclt_{$tbl_alias_suffix}.language_code='{$to_lang}'\n";    
            $join   .= " LEFT JOIN {$wpdb->prefix}icl_translation_status iclts ON iclts.translation_id=iclt_{$tbl_alias_suffix}.translation_id\n";    
        }else{
            foreach($sitepress->get_active_languages() as $lang){
                if($lang['code'] == $from_lang) continue;
                $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                $join .= " LEFT JOIN {$wpdb->prefix}icl_translations iclt_{$tbl_alias_suffix} 
                        ON iclt_{$tbl_alias_suffix}.trid=t.trid AND iclt_{$tbl_alias_suffix}.language_code='{$lang['code']}'\n";    
                $join   .= " LEFT JOIN {$wpdb->prefix}icl_translation_status iclts_{$tbl_alias_suffix} 
                        ON iclts_{$tbl_alias_suffix}.translation_id=iclt_{$tbl_alias_suffix}.translation_id\n";    
            }
        }
        
        
        // WHERE
        $where = " t.language_code = '{$from_lang}' AND p.post_status <> 'trash' \n";        
        if($type){
            $where .= " AND p.post_type = '{$type}'";
            $where .= " AND t.element_type = 'post_{$type}'\n";
        }else{
            $where .= " AND p.post_type IN ('".join("','",$t_el_types)."')\n";
            foreach($t_el_types as $k=>$v){
                $t_el_types[$k] = 'post_' . $v;
            }
            $where .= " AND t.element_type IN ('".join("','",$t_el_types)."')\n";
        }  
        if($title){
            $where .= " AND p.post_title LIKE '%".$wpdb->escape($title)."%'\n";
        }
        
        if($status){
            $where .= " AND p.post_status = '{$status}'\n";
        }        
        
        if($tstatus){
            if($to_lang){
                if($tstatus == 'not'){
                    $where .= " AND (iclts.status IS NULL OR iclts.needs_update = 1)\n";    
                }elseif($tstatus == 'in_progress'){
                    $where .= " AND iclts.status = ".ICL_TM_IN_PROGRESS." AND iclts.needs_update = 0\n";    
                }elseif($tstatus == 'complete'){
                    $where .= " AND iclts.status = ".ICL_TM_COMPLETE." AND iclts.needs_update = 0\n";    
                }
                
            }else{
                if($tstatus == 'not'){
                    $where .= " AND (";
                    $wheres = array();
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $from_lang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $wheres[] = "iclts_{$tbl_alias_suffix}.status IS NULL OR iclts_{$tbl_alias_suffix}.needs_update = 1\n";    
                    }
                    $where .= join(' OR ', $wheres) . ")";
                }elseif($tstatus == 'in_progress'){
                    $where .= " AND (";
                    $wheres = array();
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $from_lang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $wheres[] = "iclts_{$tbl_alias_suffix}.status = ".ICL_TM_IN_PROGRESS."\n";    
                    }
                    $where .= join(' OR ', $wheres)  . ")";
                }elseif($tstatus == 'complete'){
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $from_lang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $where .= " AND iclts_{$tbl_alias_suffix}.status = ".ICL_TM_COMPLETE." AND iclts_{$tbl_alias_suffix}.needs_update = 0\n";    
                    }
                }
            }
        }
        
        // ORDER
        if($sort_by){
            $order = " $sort_by ";    
        }else{
            $order = " p.post_date DESC";
        }
        if($sort_order){
            $order .= $sort_order;    
        }else{
            $order .= 'DESC';    
        }
        
        
        
        // LIMIT
        if(!isset($_GET['paged'])) $_GET['paged'] = 1;
        $offset = ($_GET['paged']-1)*$limit_no;
        $limit = " " . $offset . ',' . $limit_no;
        
        
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS {$select} 
            FROM {$from}
            {$join}
            WHERE {$where}
            ORDER BY {$order}
            LIMIT {$limit}
        ";
        
        //debug_array($sql);
        
        $results = $wpdb->get_results($sql);    
        
        
        $count = $wpdb->get_var("SELECT FOUND_ROWS()");

        $wp_query->found_posts = $count;
        $wp_query->query_vars['posts_per_page'] = $limit_no;
        $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit_no);
          
          
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
            case ICL_TM_WAITING_FOR_TRANSLATOR: $img_file = 'in-progress.png'; break;
            case ICL_TM_IN_PROGRESS: $img_file = 'in-progress.png'; break;
            case ICL_TM_NEEDS_UPDATE: $img_file = 'needs-update.png'; break;
            case ICL_TM_COMPLETE: $img_file = 'complete.png'; break;
            default: $img_file = '';
        }
        return $img_file;
    } 
    
    public function estimate_word_count($data, $lang_code){
        global $asian_languages;
        
        $words = 0;
        if(isset($data->post_title)){
            if(in_array($lang_code, $asian_languages)){
                $words += strlen(strip_tags($data->post_title)) / 6;
            } else {
                $words += count(explode(' ',$data->post_title));
            }
        }
        if(isset($data->post_content)){
            if(in_array($lang_code, $asian_languages)){
                $words += strlen(strip_tags($data->post_content)) / 6;
            } else {
                $words += count(explode(' ',strip_tags($data->post_content)));
            }
        }
        
        return (int)$words;
        
    }
    
    public function estimate_custom_field_word_count($post_id, $lang_code) {
        global $asian_languages;

        include_once ICL_PLUGIN_PATH . '/inc/plugins-texts-functions.php';
        
        $words = 0;
        $custom_fields = icl_get_posts_translatable_fields();
        foreach($custom_fields as $id => $cf){
            if ($cf->translate) {
                $custom_fields_value = get_post_meta($post_id, $cf->attribute_name, true);
                if ($custom_fields_value != "") {
                    if(in_array($lang_code, $asian_languages)){
                        $words += strlen(strip_tags($custom_fields_value)) / 6;
                    } else {
                        $words += count(explode(' ',strip_tags($custom_fields_value)));
                    }
                }
            }
        }
        
        return (int)$words;
    }
    
    function send_jobs($data){        
        global $wpdb, $sitepress;
        
        // no language selected ?
        if(!isset($data['translate_to']) || empty($data['translate_to'])){
            $this->messages[] = array(
                'type'=>'error',
                'text' => __('Please select at least one language to translate into.', 'sitepress')
            );
            $this->dashboard_select = $data; // prepopulate dashboard
            return;
        }
        // no post selected ?
        if(!isset($data['post']) || empty($data['post'])){
            $this->messages[] = array(
                'type'=>'error',
                'text' => __('Please select at least one document to translate.', 'sitepress')
            );
            $this->dashboard_select = $data; // prepopulate dashboard
            return;
        }
        
        $selected_posts = $data['post'];
        $selected_translators = $data['translator'];
        $selected_languages = $data['translate_to'];
        
        
        foreach($selected_posts as $post_id){
            $post = get_post($post_id); 
            $post_trid = $sitepress->get_element_trid($post->ID, 'post_' . $post->post_type);
            $post_translations = $sitepress->get_element_translations($post_trid, 'post_' . $post->post_type);            
            $md5 = $this->post_md5($post);
            
            $translation_package = $this->create_translation_package($post_id);
            
            foreach($selected_languages as $lang=>$one){
                if(empty($post_translations[$lang])){
                    $translation_id = $sitepress->set_element_language_details(0 , 'post_' . $post->post_type, $post_trid, $lang, $data['translate_from']);
                }else{
                    $translation_id = $post_translations[$lang]->translation_id;
                }     
                
                if(empty($selected_translators[$lang])){
                    $_status = ICL_TM_WAITING_FOR_TRANSLATOR;
                }else{
                    $_status = ICL_TM_IN_PROGRESS;
                    
                } 
                // add translation_status record        
                list($rid, $update) = $this->update_translation_status(array(
                    'translation_id'        => $translation_id,
                    'status'                => $_status,
                    'translator_id'         => $selected_translators[$lang],
                    'needs_update'          => 0,
                    'md5'                   => $md5,
                    'translation_service'   => 'local',
                    'translation_package'   => serialize($translation_package)
                ));
                
                if($selected_translators[$lang] && !$update){
                    $this->add_translation_job($rid, $selected_translators[$lang], $translation_package);
                }
            }                
            
        }
                
        $this->messages[] = array(
            'type'=>'updated',
            'text' => __('All documents sent to translation.', 'sitepress')
        );
        
        
    }
    
    /**
    * create translation package 
    * 
    * @param object|int $post
    */
    function create_translation_package($post){
        global $sitepress;
        
        $package = array();
        
        if(is_numeric($post)){
            $post = get_post($post);    
        }
        
        
        if($post->post_type=='page'){
            $package['url'] = htmlentities(get_option('home') . '?page_id=' . ($post->ID));
        }else{
            $package['url'] = htmlentities(get_option('home') . '?p=' . ($post->ID));
        }
        
        $package['contents']['title'] = array(
            'translate' => 1,
            'data'      => base64_encode($post->post_title),
            'format'    => 'base64'
        );
        
        $package['contents']['body'] = array(
            'translate' => 1,
            'data'      => base64_encode($post->post_content),
            'format'    => 'base64'
        );

        $package['contents']['excerpt'] = array(
            'translate' => 1,
            'data'      => base64_encode($post->post_excerpt),
            'format'    => 'base64'
        );
        
        $package['contents']['original_id'] = array(
            'translate' => 0,
            'data'      => $post->ID
        );
        
        include_once ICL_PLUGIN_PATH . '/inc/plugins-texts-functions.php';
        $custom_fields = icl_get_posts_translatable_fields();
        
        foreach($custom_fields as $id => $cf){
            if ($cf->translate) {
                $custom_fields_value = get_post_meta($post->ID, $cf->attribute_name, true);
                if ($custom_fields_value != '') {
                    $package['contents']['field-'.$id] = array(
                        'translate' => 1,
                        'data' => base64_encode($custom_fields_value),
                        'format' => 'base64'
                    );
                    $package['contents']['field-'.$id.'-name'] = array(
                        'translate' => 0,
                        'data' => $cf->attribute_name
                    );
                    $package['contents']['field-'.$id.'-type'] = array(
                        'translate' => 0,
                        'data' => $cf->attribute_type
                    );
                    $package['contents']['field-'.$id.'-plugin'] = array(
                        'translate' => 0,
                        'data' => $cf->plugin_name
                    );
                }
            }
        }                
        
        foreach($sitepress->get_translatable_taxonomies(true, $post->post_type) as $taxonomy){
            $terms = get_the_terms( $post->ID , $taxonomy );
            if(!empty($terms)){
                $_taxs = $_tax_ids = array();
                foreach($terms as $term){
                    $_taxs[] = $term->name;    
                    $_tax_ids[] = $term->term_taxonomy_id;
                }
                if($taxonomy == 'post_tag'){ 
                    $tax_package_key  = 'tags'; 
                    $tax_id_package_key  = 'tag_ids'; 
                }
                elseif($taxonomy == 'category'){
                    $tax_package_key  = 'categories'; 
                    $tax_id_package_key  = 'category_ids'; 
                }
                else{
                    $tax_package_key  = $taxonomy;
                    $tax_id_package_key  = $taxonomy . '_ids'; 
                } 
                
                $package['contents'][$tax_package_key] = array(
                    'translate' => 1,
                    'data'      => implode(',', array_map(create_function('$e', 'return \'"\'.base64_encode($e).\'"\';'), $_taxs)),
                    'format'=>'csv_base64'
                );
                
                $package['contents'][$tax_id_package_key] = array(
                    'translate' => 0,
                    'data'      => join(',', $_tax_ids)
                );
            }            
        }
        
        return $package;
    }    
    
    /**
    * add/update icl_translation_status record
    * 
    * @param array $data
    */
    function update_translation_status($data){
        global $wpdb;
        
        if(!isset($data['translation_id'])) return;
        
        if($rid = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $data['translation_id']))){
            $wpdb->update($wpdb->prefix.'icl_translation_status', $data, array('rid'=>$rid));
            $update = true;
        }else{
            $wpdb->insert($wpdb->prefix.'icl_translation_status',$data);
            $rid = $wpdb->insert_id;
            $update = false;
        }    
        
        return array($rid, $update);
    }
    
    /**
    * Adds a translation job record in icl_translate_job
    * 
    * @param mixed $rid
    * @param mixed $translator_id
    */
    function add_translation_job($rid, $translator_id, $translation_package){
        global $wpdb, $current_user;
        get_currentuserinfo();
        
        
        // IN PROGRESS
        
        
        
        $wpdb->insert($wpdb->prefix . 'icl_translate_job', array(
            'rid' => $rid,
            'translator_id' => $translator_id, 
            'translated'    => 0,
            'manager_id'    => $current_user->ID
        ));        
        $job_id = $wpdb->insert_id;
        
        foreach($translation_package['contents'] as $field => $value){
            //if($value['translate']){
                $job_translate = array(
                    'job_id'            => $job_id,
                    'content_id'        => 0,
                    'type'              => '',
                    'field_type'        => '',
                    'field_format'      => $value['format'],
                    'field_translate'   => $value['translate'],
                    'field_data'        => $value['data'],
                    'field_finished'    => 0      
                );
                $wpdb->insert($wpdb->prefix . 'icl_translate', $job_translate);    
            //}
        }
        
        
    }
    
}
  
?>
