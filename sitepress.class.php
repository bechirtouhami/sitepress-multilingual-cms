<?php
class SitePress{
   
    private $settings;
    
    function __construct(){
        global $wpdb;
        $this->settings = get_option('icl_sitepress_settings');
        
        // Ajax feedback
        if(isset($_POST['icl_ajx_action'])){
            add_action('init', array($this,'ajax_responses'));
        }
        
        // Administration menus
        add_action('admin_menu', array($this, 'administration_menu'));
        
        // Process post requests
        add_action('init', array($this,'process_forms'));           
        
        if($this->settings['existing_content_language_verified']){
            // Post/page language box
            add_action('admin_head', array($this,'post_edit_language_options'));        
            
            // Post/page save actions
            add_action('save_post', array($this,'save_post_actions'));        
            // Post/page delete actions
            add_action('delete_post', array($this,'delete_post_actions'));        
            
            add_filter('posts_join', array($this,'posts_join_filter'));
            global $pagenow;
            if(in_array($pagenow, array('edit-pages.php','edit.php'))){
                add_action('admin_head', array($this,'language_filter'));
            }
            
            add_filter('wp_list_pages_excludes', array($this, 'exclude_other_language_pages'));
            
            /* ?????????? */
            if(isset($_GET['lang']) && $_GET['lang'] != $this->get_default_language()){
                add_filter('the_permalink', array($this, 'permalink_filter'));   
                add_filter('page_link', array($this, 'permalink_filter'));   
            }
            
            
            add_action('create_term',  array($this, 'create_term'),1, 2);
            add_action('delete_term',  array($this, 'delete_term'),1,3);                
            // category language selection        
            add_action('edit_category',  array($this, 'create_term'),1, 2);        
            if($pagenow == 'categories.php'){
                add_action('admin_print_scripts-categories.php', array($this,'js_scripts_categories'));
                add_action('edit_category_form', array($this, 'edit_term_form'));
                add_action('admin_head', array($this,'terms_language_filter'));
                add_filter('list_terms_exclusions', array($this, 'exclude_other_terms'));
            }        
            // tags language selection
            if($pagenow == 'edit-tags.php'){
                add_action('admin_print_scripts-edit-tags.php', array($this,'js_scripts_tags'));
                add_action('add_tag_form', array($this, 'edit_term_form'));
                add_action('edit_tag_form', array($this, 'edit_term_form'));
                add_action('admin_head', array($this,'terms_language_filter'));
                add_filter('list_terms_exclusions', array($this, 'exclude_other_terms'));
            }
            
            // the language selector widget
            add_action('plugins_loaded', array($this, 'language_selector_widget_init'));
        }
                
    }
    
    function ajax_responses(){
        global $wpdb;
        // moved
    }
    
    function administration_menu(){
        add_action('admin_print_scripts', array($this,'js_scripts_setup'));
        add_action('admin_head', array($this,'css_setup'));
        add_menu_page(__('SitePress','sitepress'), __('SitePress','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php');        
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Languages','sitepress'), __('Languages','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php'); 
        if($this->settings['existing_content_language_verified']){
            add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Content Translation','sitepress'), __('Content Translation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/content-translation.php'); 
            add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Comments Translation','sitepress'), __('Comments Translation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/comments-translation.php'); 
        }
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Navigation','sitepress'), __('Navigation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/navigation.php'); 
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Absolute Links','sitepress'), __('Absolute Links','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/absolute-links.php'); 
    }

    function save_settings($settings){
        foreach($settings as $k=>$v){
            $this->settings[$k] = $v;
        }        
        update_option('icl_sitepress_settings', $this->settings);
    }

    function get_settings(){
        return $this->settings;
    }    
    
    function get_active_languages(){
        global $wpdb;
        $res = $wpdb->get_results("
            SELECT code, english_name, active, lt.name AS display_name 
            FROM {$wpdb->prefix}icl_languages l
                JOIN {$wpdb->prefix}icl_languages_translations lt ON l.code=lt.language_code           
            WHERE 
                active=1 AND lt.display_language_code = '{$this->get_default_language()}' 
            ORDER BY major DESC, english_name ASC", ARRAY_A);        
        $languages = array();
        foreach($res as $r){
            $languages[] = $r;
        }
        return $languages;
    }
    
    function set_active_languages($arr){
        global $wpdb;
        if(!empty($arr)){
            foreach($arr as $code){
                $tmp[] = "'" . mysql_real_escape_string(trim($code)) . "'";
            }
            $codes = '(' . join(',',$tmp) . ')';
            $wpdb->update($wpdb->prefix.'icl_languages', array('active'=>0), array('1'=>'1'));
            $wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET active=1 WHERE code IN {$codes}");            
        }
        return true;
    }
    
    function get_languages(){
        global $wpdb;
        $res = $wpdb->get_results("
            SELECT 
                code, english_name, major, active, lt.name AS display_name   
            FROM {$wpdb->prefix}icl_languages l
                JOIN {$wpdb->prefix}icl_languages_translations lt ON l.code=lt.language_code           
            WHERE lt.display_language_code = '{$this->get_default_language()}' 
            ORDER BY major DESC, english_name ASC", ARRAY_A);
        $languages = array();
        foreach($res as $r){
            $languages[] = $r;
        }
        return $languages;
    }
    
    function get_language_details($code){
        global $wpdb;
        $language = $wpdb->get_row("
            SELECT 
                code, english_name, major, active, lt.name AS display_name   
            FROM {$wpdb->prefix}icl_languages l
                JOIN {$wpdb->prefix}icl_languages_translations lt ON l.code=lt.language_code           
            WHERE lt.display_language_code = '{$this->get_default_language()}' AND code='{$code}'
            ORDER BY major DESC, english_name ASC", ARRAY_A);
        return $language;
    }

    function get_default_language(){        
        return $this->settings['default_language'];
    }
    
    function set_default_language($code){        
        $iclsettings['default_language'] = $code;
        $this->save_settings($iclsettings);
        return true;
    }
    
    function get_icl_translation_enabled($lang=null, $langto=null){
        if(!is_null($lang)){
            if(!is_null($langto)){
                return $this->settings['language_pairs'][$lang][$langto];
            }else{
                return !empty($this->settings['language_pairs'][$lang]);
            }            
        }else{
            return $this->settings['enable_icl_translations'];
        }
    }

    function set_icl_translation_enabled(){
        $iclsettings['translation_enabled'] = true;
        $this->save_settings($iclsettings);
    }
    
    function icl_account_reqs(){
        $errors = array();
        if(!get_option('enable_xmlrpc')){
            $errors[] = __('XML-RPC publishing protocol not enabled', 'sitepress') . 
                ' <a href="'.get_option('siteurl').'/wp-admin/options-writing.php">'.__('Fix','sitepress').'</a>';
        }
        if(!$this->settings['cms_login'] || !$this->settings['cms_password']){
            $errors[] = __('CMS user not configured', 'sitepress') . 
                ' <a href="#icleditoraccount" id="icl_user_fix">'.__('Fix','sitepress').'</a>';;                
        }        
        return $errors;        
    }
    
    function icl_account_configured(){
        return $this->settings['site_id'] && $this->settings['access_key'];
    }
    
    function js_scripts_setup(){
        $page = basename($_GET['page']);
        $page_basename = str_replace('.php','',$page);
        ?>
        <script type="text/javascript">        
        var icl_ajx_url = '<?php echo ICL_PLUGIN_URL ?>/ajax.php';
        var icl_ajx_saved = '<?php echo __('Data saved') ?>';
        var icl_ajx_error = '<?php echo __('Error: data not saved') ?>';
        var icl_default_mark = '<?php echo __('default') ?>';     
        var icl_this_lang = '<?php echo isset($_GET['lang'])?$_GET['lang']:$this->get_default_language() ?>';   
        var icl_ajxloaderimg = '<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif';
        </script>
        <?php
        wp_enqueue_script('sitepress-scripts', ICL_PLUGIN_URL . '/res/js/scripts.js', array(), '0.1');
        if($page_basename){
            wp_enqueue_script('sitepress-' . $page_basename, ICL_PLUGIN_URL . '/res/js/'.$page_basename.'.js', array(), '0.1');
        }
    }
    
    function js_scripts_categories(){
        wp_enqueue_script('sitepress-categories', ICL_PLUGIN_URL . '/res/js/categories.js', array(), '0.1');
    }
    
    function js_scripts_tags(){
        wp_enqueue_script('sitepress-tags', ICL_PLUGIN_URL . '/res/js/tags.js', array(), '0.1');
    }
    
    function css_setup(){
        ?>
        <link rel="stylesheet" href="<?php echo ICL_PLUGIN_URL ?>/res/css/languages.css?v=0.1" type="text/css" media="all" />
        <link rel="stylesheet" href="<?php echo ICL_PLUGIN_URL ?>/res/css/style.css?v=0.1" type="text/css" media="all" />
        <?php
    }
    
    function process_forms(){
        global $wpdb;
        require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
        require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
        require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
        $nonce_icl_create_account = wp_create_nonce('icl_create_account');
        $nonce_icl_configure_account = wp_create_nonce('icl_configure_account');
        $nonce_icl_logout = wp_create_nonce('icl_logout');
        $nonce_icl_initial_language = wp_create_nonce('icl_initial_language');
        switch($_POST['_wpnonce']){
            case $nonce_icl_create_account:
            case $nonce_icl_configure_account:
                $user = $_POST['user'];
                $user['platform_kind'] = 1;
                $user['blogid'] = $wpdb->blogid?$wpdb->blogid:1;
                $user['url'] = get_option('home');
                $user['title'] = get_option('blogname');
                $user['description'] = get_option('blogdescription');
                $user['cms_login'] = $this->settings['cms_login'];
                $user['cms_password'] = $this->settings['cms_password'];                
                $user['interview_translators'] = $this->settings['interview_translators'];
            
                $icl_query = new ICanLocalizeQuery();
                // prepare language pairs
                $language_pairs = $this->settings['language_pairs'];
                foreach($language_pairs as $k=>$v){
                    $english_fr = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                    foreach($v as $k=>$v){
                        $incr++;
                        $english_to = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                        $lang_pairs['from_language'.$incr] = $english_fr; 
                        $lang_pairs['to_language'.$incr] = $english_to;
                    }                    
                }
                list($site_id, $access_key) = $icl_query->createAccount(array_merge($user,$lang_pairs));                
                if(!$site_id){
                    $_POST['icl_form_errors'] = $access_key;
                }else{
                    $iclsettings['site_id'] = $site_id;
                    $iclsettings['access_key'] = $access_key;
                    $this->save_settings($iclsettings);
                    if($user['create_account']==1){
                        $_POST['icl_form_success'] = __('Account created','sitepress');
                    }else{
                        $_POST['icl_form_success'] = __('Project added','sitepress');
                    }
                    
                }
                break;
            case $nonce_icl_logout:
                $iclsettings['site_id']=null;
                $iclsettings['access_key']=null;
                $this->save_settings($iclsettings);
                $_POST['icl_form_success'] = __('ICanLocalize account details reset','sitepress');            
                break;
            case $nonce_icl_initial_language:
                $iclsettings['existing_content_language_verified'] = 1;
                $this->save_settings($iclsettings);
                $this->prepopulate_translations($_POST['icl_initial_language_code']);
                $wpdb->update($wpdb->prefix . 'icl_languages', array('active'=>'1'), array('code'=>$_POST['icl_initial_language_code']));
                break;
            
        }
    }
    
    function prepopulate_translations($lang){        
        global $wpdb;        
        if($this->settings['existing_content_language_verified']) return;
        $wpdb->query("
            INSERT INTO {$wpdb->prefix}icl_translations(element_type, element_id, trid, language_code)
            SELECT 'post', ID, ID, '{$lang}' FROM {$wpdb->posts} WHERE post_type IN ('post','page')
            ");
        $maxtrid = 1 + $wpdb->get_var("SELECT MAX(trid) FROM {$wpdb->prefix}icl_translations");
        $wpdb->query("
            INSERT INTO {$wpdb->prefix}icl_translations(element_type, element_id, trid, language_code)
            SELECT 'category', term_taxonomy_id, {$maxtrid}+term_taxonomy_id, '{$lang}' FROM {$wpdb->term_taxonomy}
            ");
        $maxtrid = 1 + $wpdb->get_var("SELECT MAX(trid) FROM {$wpdb->prefix}icl_translations");
        $wpdb->query("
            INSERT INTO {$wpdb->prefix}icl_translations(element_type, element_id, trid, language_code)
            SELECT 'tag', term_taxonomy_id, {$maxtrid}+term_taxonomy_id, '{$lang}' FROM {$wpdb->term_taxonomy}
            ");
    }
    
    function post_edit_language_options(){
        add_meta_box('icl_div', __('Language options', 'sitepress'), array($this,'meta_box'), 'post', 'normal', 'high');
        add_meta_box('icl_div', __('Language options', 'sitepress'), array($this,'meta_box'), 'page', 'normal', 'high');
    }
    
    function set_element_language_details($el_id, $el_type='post', $trid, $language_code){
        global $wpdb;
        if($trid){
            //get source
            $src_language_code = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND source_language_code IS NULL");                
            
            if($wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_translations WHERE element_type='{$el_type}' AND element_id='{$el_id}' AND trid='{$trid}'")){
                //case of language change
                $wpdb->update($wpdb->prefix.'icl_translations', 
                    array('language_code'=>$language_code, 'source_language_code'=>$src_language_code), 
                    array('trid'=>$trid, 'element_type'=>$el_type, 'element_id'=>$el_id));                
            }else{
                // case of adding a new language
                $wpdb->insert($wpdb->prefix.'icl_translations', 
                    array(
                        'trid'=>$trid, 
                        'element_type'=>$el_type, 
                        'element_id'=>$el_id, 
                        'language_code'=>$language_code,
                        'source_language_code'=>$src_language_code
                        )
                );
            }
        }else{
            $trid = 1 + $wpdb->get_var("SELECT MAX(trid) FROM {$wpdb->prefix}icl_translations");
            $wpdb->insert($wpdb->prefix.'icl_translations', 
                array(
                    'trid'=>$trid,
                    'element_type'=>$el_type, 
                    'element_id'=>$el_id,
                    'language_code'=>$language_code
                )
            );    
        }
        return $trid;
    }
        
    function save_post_actions($pidd){
        if($_POST['autosave']) return;
        $post_id = $_POST['post_ID'];
        $trid = $_POST['icl_trid'];
        $language_code = $_POST['icl_post_language'];
        
        $this->set_element_language_details($post_id, 'post', $trid, $language_code);
    }
    
    function delete_post_actions($post_id){
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND element_id='{$post_id}' LIMIT 1");
    }
    
    function get_element_translations($trid, $el_type='post'){        
        global $wpdb;  
        if($trid){            
            if($el_type=='post'){
                $sel_add = ', p.post_title';
                $join_add = " LEFT JOIN {$wpdb->posts} p ON t.element_id=p.ID";
            }elseif($el_type=='category' || $el_type='tag'){
                $sel_add = ', tm.name, tm.term_id';
                $join_add = " LEFT JOIN {$wpdb->term_taxonomy} tt ON t.element_id=tt.term_taxonomy_id
                              LEFT JOIN {$wpdb->terms} tm ON tt.term_id = tm.term_id";                
            }                         
            $where_add = " AND t.trid='{$trid}'"; 
        }   
        $query = "
            SELECT t.language_code, t.element_id {$sel_add}
            FROM {$wpdb->prefix}icl_translations t
                 {$join_add}
            WHERE 1 {$where_add}
        ";       
        $ret = $wpdb->get_results($query);        
        foreach($ret as $t){
            $translations[$t->language_code] = $t;
        }        
        return $translations;
    }
    
    function meta_box($post){
        global $wpdb;   
        $active_languages = $this->get_active_languages();
        if($post->ID){
            $res = $wpdb->get_row("SELECT trid, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id='{$post->ID}' AND element_type='post'");
            $trid = $res->trid;
            if($trid){                
                $element_lang_code = $res->language_code;
            }else{
                $trid = $this->set_element_language_details($post->ID,'post',null,$this->get_default_language);
                $element_lang_code = $this->get_default_language();
            }            
        }else{
            $trid = $_GET['trid'];
            $element_lang_code = $_GET['lang'];
        }                 
        if($trid){
            $translations = $this->get_element_translations($trid, 'post');        
        }        
        $selected_language = $element_lang_code?$element_lang_code:$this->get_default_language();
        
        include ICL_PLUGIN_PATH . '/menu/post-menu.php';
    }
    
    function posts_join_filter($join){
        global $wpdb;
        $this_lang = $_GET['lang']?$wpdb->escape($_GET['lang']):$this->get_default_language();
        if('all' != $this_lang){ 
            $cond = "AND language_code='{$this_lang}'";
            $ljoin = "";
        }else{
            $cond = '';
            $ljoin = "LEFT";
        }
        $join .= "{$ljoin} JOIN {$wpdb->prefix}icl_translations t ON {$wpdb->posts}.ID = t.element_id 
                    AND t.element_type='post' {$cond} ";
        return $join;
    }
    
    function language_filter(){
        global $wpdb, $pagenow;
        if($pagenow=='edit.php'){
            $type = 'post';
        }else{
            $type = 'page';
        }
        $this_lang = $_GET['lang']?$_GET['lang']:$this->get_default_language();
        $active_languages = $this->get_active_languages();
        
        $res = $wpdb->get_results("
            SELECT language_code, COUNT(p.ID) AS c FROM {$wpdb->prefix}icl_translations t 
            JOIN {$wpdb->posts} p ON t.element_id=p.ID
            WHERE t.element_type='post' AND p.post_type='{$type}'
            GROUP BY language_code            
            ");         
        foreach($res as $r){
            $langs[$r->language_code] = $r->c;
        } 
        $langs['all'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='{$type}'");               
        $active_languages[] = array('code'=>'all','display_name'=>__('All languages','sitepress'));
        foreach($active_languages as $lang){
            if($lang['code']== $this_lang){
                $px = '<strong>'; 
                $sx = ' ('. intval($langs[$lang['code']]) .')</strong>';
            }elseif(!isset($langs[$lang['code']])){
                $px = '<span>';
                $sx = '</span>';
            }else{
                $px = '<a href="?lang='.$lang['code'].'">';
                $sx = '</a> ('. $langs[$lang['code']] .')';
            }
            $as[] =  $px . $lang['display_name'] . $sx;
        }
        $allas = join(' | ', $as);
        ?>
        <script type="text/javascript">
        addLoadEvent(function(){        
            jQuery(".subsubsub").append('<br /> <span id="icl_subsubsub"><?php echo $allas ?></span>');
        });
        </script>
        <?php
    }
    
    function exclude_other_language_pages($s){
        global $wpdb;
        $this_lang = $_GET['lang']?$wpdb->escape($_GET['lang']):$this->get_default_language();
        $excl_pages = $wpdb->get_col("
            SELECT p.ID FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->prefix}icl_translations t ON (p.ID = t.element_id OR t.element_id IS NULL)
            WHERE t.element_type='post' AND p.post_type='page' AND t.language_code <> '{$this_lang}'
            ");
        return array_merge($s, $excl_pages);
    }
            
    function edit_term_form($term){                
        global $wpdb, $pagenow;
        $element_id = $term->term_taxonomy_id;    
        $element_type = $pagenow=='categories.php'?'category':'tag';
        
        if($element_id){
            $res = $wpdb->get_row("SELECT trid, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id='{$element_id}' AND element_type='{$element_type}'");
            $trid = $res->trid;
            if($trid){                
                $element_lang_code = $res->language_code;
            }else{
                $trid = $this->set_element_language_details($post->ID, $element_type, null, $this->get_default_language);
                $element_lang_code = $this->get_default_language();
            }                            
        }else{
            $trid = $_GET['trid'];
            $element_lang_code = $_GET['lang'];
        }
        if($trid){
            $translations = $this->get_element_translations($trid, $element_type);        
        }                                   
        $active_languages = $this->get_active_languages();
        $this_lang = $element_lang_code?$element_lang_code:$this->get_default_language();
        include ICL_PLUGIN_PATH . '/menu/'.$element_type.'-menu.php';        
    }
    
    function create_term($cat_id, $tt_id){        
        global $wpdb;
        $trid = isset($_POST['icl_trid'])?$_POST['icl_trid']:null;        
        $el_type = $wpdb->get_var("SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id={$tt_id}");
        if($el_type == 'post_tag') $el_type = 'tag'; 
        $cat_lang = $_POST['icl_'.$el_type.'_language'];        
        $this->set_element_language_details($tt_id, $el_type, $trid, $cat_lang);                
    }
    
    function delete_term($cat, $tt_id, $taxonomy){
        global $wpdb;
        if($taxonomy == 'post_tag') $taxonomy = 'tag'; 
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE element_type ='{$taxonomy}' AND element_id='{$tt_id}' LIMIT 1");
    } 
       
    function terms_language_filter(){
        global $wpdb, $pagenow;
        if($pagenow=='categories.php'){
            $element_type = $taxonomy = 'category';
        }else{
            $element_type = 'tag';
            $taxonomy = 'post_tag';
        }
        $this_lang = $_GET['lang']?$_GET['lang']:$this->get_default_language();
        $active_languages = $this->get_active_languages();
        
        $res = $wpdb->get_results("
            SELECT language_code, COUNT(tm.term_id) AS c FROM {$wpdb->prefix}icl_translations t 
            JOIN {$wpdb->term_taxonomy} tt ON t.element_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} tm ON tt.term_id = tm.term_id
            WHERE t.element_type='{$element_type}' AND tt.taxonomy='{$taxonomy}' 
            GROUP BY language_code            
            ");                 
        foreach($res as $r){
            $langs[$r->language_code] = $r->c;
        } 
        $langs['all'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy='{$taxonomy}'");               
        $active_languages[] = array('code'=>'all','display_name'=>__('All languages','sitepress'));
        foreach($active_languages as $lang){
            if($lang['code']== $this_lang){
                $px = '<strong>'; 
                $sx = ' ('. intval($langs[$lang['code']]) .')</strong>';
            }elseif(!isset($langs[$lang['code']])){
                $px = '<span>';
                $sx = '</span>';
            }else{
                $px = '<a href="?lang='.$lang['code'].'">';
                $sx = '</a> ('. $langs[$lang['code']] .')';
            }
            $as[] =  $px . $lang['display_name'] . $sx;
        }
        $allas = join(' | ', $as);
        ?>
        <script type="text/javascript">
        addLoadEvent(function(){        
            jQuery('table.widefat').before('<span id="icl_subsubsub"><?php echo $allas ?></span>');
        });
        </script>
        <?php
    }    
    
    function exclude_other_terms($exclusions){        
        global $wpdb, $pagenow;
        if($pagenow=='categories.php'){
            $element_type = $taxonomy = 'category';
        }else{
            $element_type = 'tag';
            $taxonomy = 'post_tag';
        }
        if($_GET['lang']=='all'){
            return $exclusions;
        }
        $this_lang = $_GET['lang']?$wpdb->escape($_GET['lang']):$this->get_default_language();
        $exclude =  $wpdb->get_col("
            SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt
            LEFT JOIN {$wpdb->terms} tm ON tt.term_id = tm.term_id 
            LEFT JOIN {$wpdb->prefix}icl_translations t ON (tt.term_taxonomy_id = t.element_id OR t.element_id IS NULL)
            WHERE tt.taxonomy='{$taxonomy}' AND t.element_type='{$element_type}' AND t.language_code <> '{$this_lang}'
            ");
        $exclude[] = 0;         
        $exclusions .= ' AND term_taxonomy_id NOT IN ('.join(',',$exclude).')';
        return $exclusions;
    }
    
    
    /* ?????????? */
    function language_url($code){
        return get_option('home').'?lang='.$code;
    }
    function permalink_filter($p){
        global $wpdb;
        $this_lang = $_GET['lang']?$wpdb->escape($_GET['lang']):$this->get_default_language();
        return $p . '?lang=' . $this_lang;
    }    
    
    function language_selector_widget_init(){
        
        function language_selector_widget(){
            global $sitepress;
            echo $before_widget;
            echo $before_title; 
            $w_active_languages = $sitepress->get_active_languages();
            $this_lang = $_GET['lang']?$_GET['lang']:$sitepress->get_default_language();
            $w_this_lang = $sitepress->get_language_details($this_lang);
                       
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            if(preg_match('#MSIE ([0-9]+)\.[0-9]#',$user_agent,$matches)){
                $ie_ver = $matches[1];
            }
            
            include ICL_PLUGIN_PATH . '/menu/language-selector.php';
            
        }
        wp_register_sidebar_widget('icl_languages_selector', __('Language Selector', 'sitepress'), 'language_selector_widget');
        
        
        function icl_lang_sel_nav_css($show = true){            
            $link_tag = '<link rel="stylesheet" href="'. ICL_PLUGIN_URL . '/res/css/language-selector.css?v=0.1" type="text/css" media="all" />';
            if(!$show){
                return $link_tag;
            }else{
                echo $link_tag;
            }
        }
        add_action('init','icl_lang_sel_nav_ob_start');
        add_action('wp_head','icl_lang_sel_nav_ob_end');
        function icl_lang_sel_nav_ob_start(){ ob_start('icl_lang_sel_nav_prepend_css'); }
        function icl_lang_sel_nav_ob_end(){ ob_end_flush();}
        function icl_lang_sel_nav_prepend_css($buf){
            return preg_replace('#</title>#i','</title>' . PHP_EOL . PHP_EOL . icl_lang_sel_nav_css(false), $buf);
        }    
        
    }
    
}  
?>
