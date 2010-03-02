<?php   
class SitePress{
   
    private $settings;
    private $active_languages = array();
    private $this_lang;
    private $wp_query;
    private $admin_language = null;
    
    function __construct(){
        global $wpdb;
                                         
        $this->settings = get_option('icl_sitepress_settings');                                
        
        if(false != $this->settings){
            $this->verify_settings();
        } 

        if(isset($_GET['icl_action'])){
            require_once ABSPATH . WPINC . '/pluggable.php';
            if($_GET['icl_action']=='advanced' && wp_create_nonce('icl_enable_advanced_mode')==$_GET['nonce']){
                $this->enable_advanced_mode();
            }
            elseif($_GET['icl_action']=='basic' && wp_create_nonce('icl_enable_basic_mode')==$_GET['nonce']){
                $this->enable_basic_mode();
            }                                              
            elseif($_GET['icl_action']=='reminder_popup'){
                add_action('init', array($this, 'reminders_popup'));
            }            
            elseif($_GET['icl_action']=='dismiss_help'){
                $this->settings['dont_show_help_admin_notice'] = true;
                $this->save_settings();                
            }                        
            elseif($_GET['icl_action']=='dbdump'){
                include_once ICL_PLUGIN_PATH . '/inc/functions-troubleshooting.php';
                icl_troubleshooting_dumpdb();
                exit;
            }                                    
        }
        
        if(isset($_REQUEST['icl_ajx_action'])){
            add_action('init', array($this, 'ajax_setup'));
        }
        
        add_action('plugins_loaded', array($this,'init'), 1);
        add_action('plugins_loaded', array($this,'initialize_cache'), 0);
                
        // Administration menus
        add_action('admin_menu', array($this, 'administration_menu'));
        
        // Process post requests
        if(!empty($_POST)){
            add_action('init', array($this,'process_forms'));           
        }        
        
        if($this->settings['existing_content_language_verified']){
            
            // Post/page language box
            add_action('admin_head', array($this,'post_edit_language_options'));        
            
            // Post/page save actions
            add_action('save_post', array($this,'save_post_actions')); 
                   
            // Post/page delete actions
            add_action('delete_post', array($this,'delete_post_actions'));        
            add_action('trash_post', array($this,'trash_post_actions'));        
            add_action('untrashed_post', array($this,'untrashed_post_actions'));        
            
            add_filter('posts_join', array($this,'posts_join_filter'));
            add_filter('posts_where', array($this,'posts_where_filter'));
            add_filter('comment_feed_join', array($this,'comment_feed_join'));
            
            // show untranslated posts
            if(!is_admin() && $this->settings['show_untranslated_blog_posts'] && $this->get_current_language() != $this->get_default_language()){
                add_filter('the_posts', array($this, 'the_posts'));
            }
            
            $this->queries = array();
            
            global $pagenow;
            if($pagenow == 'edit.php'){
                add_action('restrict_manage_posts', array($this,'language_filter'));                
            }elseif($pagenow == 'edit-pages.php'){
                add_action('admin_footer', array($this,'language_filter'));
            }
			
            //add_filter('wp_list_pages_excludes', array($this, 'exclude_other_language_pages'));
            add_filter('get_pages', array($this, 'exclude_other_language_pages2'));
            add_filter('wp_dropdown_pages', array($this, 'wp_dropdown_pages'));
            
            

            // posts and pages links filters            
            add_filter('post_link', array($this, 'permalink_filter'),1,2);   
            add_filter('page_link', array($this, 'permalink_filter'),1,2);   
            add_filter('category_link', array($this, 'category_permalink_filter'),1,2);   
            add_filter('tag_link', array($this, 'tag_permalink_filter'),1,2);               
            add_filter('get_comment_link', array($this, 'get_comment_link_filter'));
                        
            add_action('create_term',  array($this, 'create_term'),1, 2);
            add_action('edit_term',  array($this, 'create_term'),1, 2);
            add_action('delete_term',  array($this, 'delete_term'),1,3);       
            add_filter('list_terms_exclusions', array($this, 'exclude_other_terms'),1,2);         
            
            // allow adding terms with the same name in different languages
            add_filter("pre_term_name", array($this, 'pre_term_name'), 1, 2); 
            // allow adding categories with the same name in different languages
            add_action('admin_init', array($this, 'pre_save_category'));
            
            // category language selection        
            // add_action('edit_category',  array($this, 'create_term'),1, 2);        
            if($pagenow == 'categories.php'){
                add_action('admin_print_scripts-categories.php', array($this,'js_scripts_categories'));
                add_action('edit_category_form', array($this, 'edit_term_form'));
                add_action('admin_footer', array($this,'terms_language_filter'));
            }        
            // tags language selection
            if($pagenow == 'edit-tags.php'){
                add_action('admin_print_scripts-edit-tags.php', array($this,'js_scripts_tags'));
                add_action('add_tag_form', array($this, 'edit_term_form'));
                add_action('edit_tag_form', array($this, 'edit_term_form'));
                add_action('admin_footer', array($this,'terms_language_filter'));                
            }
            
            
            
            // custom hook for adding the language selector to the template
            add_action('icl_language_selector', array($this, 'language_selector'));
            
            // front end js
            add_action('wp_head', array($this, 'front_end_js'));            
            
            add_action('restrict_manage_posts', array($this, 'restrict_manage_posts'));
            add_action('admin_print_scripts-edit-pages.php', array($this,'restrict_manage_pages'));
            
            add_filter('get_edit_post_link', array($this, 'get_edit_post_link'), 1, 3);
        
        
            // short circuit get default category
            add_filter('pre_option_default_category', array($this, 'pre_option_default_category'));
            add_filter('the_category', array($this,'the_category_name_filter'));
            add_filter('get_terms', array($this,'get_terms_filter'));
            add_filter('single_cat_title', array($this,'the_category_name_filter'));
            add_filter('term_links-category', array($this,'the_category_name_filter'));
            
            add_filter('term_links-post_tag', array($this,'the_category_name_filter'));
            add_filter('tags_to_edit', array($this,'the_category_name_filter'));
            add_filter('single_tag_title', array($this,'the_category_name_filter'));
            
            // adiacent posts links
            add_filter('get_previous_post_join', array($this,'get_adiacent_post_join'));
            add_filter('get_next_post_join', array($this,'get_adiacent_post_join'));
            add_filter('get_previous_post_where', array($this,'get_adiacent_post_where'));
            add_filter('get_next_post_where', array($this,'get_adiacent_post_where'));
            
            // feeds links
            add_filter('feed_link', array($this,'feed_link'));
            
            // commenting links
            add_filter('post_comments_feed_link', array($this,'post_comments_feed_link'));
            add_filter('trackback_url', array($this,'trackback_url'));
            add_filter('user_trailingslashit', array($this,'user_trailingslashit'),1, 2);        
            
            // date based archives
            add_filter('year_link', array($this,'archives_link'));
            add_filter('month_link', array($this,'archives_link'));
            add_filter('day_link', array($this,'archives_link'));
            add_filter('getarchives_join', array($this,'getarchives_join'));
            add_filter('getarchives_where', array($this,'getarchives_where'));
            add_filter('pre_option_home', array($this,'pre_option_home'));            
                        
            // language negotiation
            add_action('query_vars', array($this,'query_vars'));
            
            // 
            add_filter('language_attributes', array($this, 'language_attributes'));
                        
            add_action('locale', array($this, 'locale'));
                                            
            if(isset($_GET['____icl_validate_domain'])){ echo '<!--'.get_option('home').'-->'; exit; }                        
            
            add_filter('pre_option_page_on_front', array($this,'pre_option_page_on_front'));
            add_filter('pre_option_page_for_posts', array($this,'pre_option_page_for_posts'));
            
            add_filter('option_sticky_posts', array($this,'option_sticky_posts'));
                             
            add_filter('request', array($this,'request_filter'));
            
            add_action('wp_head', array($this,'set_wp_query'));
            
            add_action('init', array($this,'plugin_localization'));            
            
            add_action('show_user_profile', array($this, 'show_user_options'));
            add_action('personal_options_update', array($this, 'save_user_options'));
            
            if(($pagenow == 'edit.php' || ($pagenow == 'admin-ajax.php' && $_POST['action']=='inline-save'))  
                && !$this->settings['hide_translation_controls_on_posts_lists']){
                add_filter('manage_posts_columns',array($this,'add_posts_management_column'));
                add_action('manage_posts_custom_column',array($this,'add_content_for_posts_management_column'));            
                add_action('admin_print_scripts', array($this, '__set_posts_management_column_width'));
            }
            if(($pagenow == 'edit-pages.php' || ($pagenow == 'admin-ajax.php' && $_POST['action']=='inline-save'))  
                && !$this->settings['hide_translation_controls_on_posts_lists']){                    
                add_filter('manage_pages_columns',array($this,'add_posts_management_column'));
                add_action('manage_pages_custom_column',array($this,'add_content_for_posts_management_column'));
                add_action('admin_print_scripts', array($this, '__set_posts_management_column_width'));
            }
            // adjust queried categories and tags ids according to the language            
            if($this->settings['auto_adjust_ids']){
                add_action('parse_query', array($this, 'parse_query'));            
                add_action('wp_list_pages_excludes', array($this, 'adjust_wp_list_pages_excludes'));            
                if(!is_admin()){
                    add_filter('get_term', array($this,'get_term_adjust_id'), 1, 1);
                    add_filter('category_link', array($this,'category_link_adjust_id'), 1, 2);
                }
            } 
            
            if(!is_admin()){
                add_action('wp_head', array($this, 'meta_generator_tag'));
            }           
            
        } //end if the initial language is set - existing_content_language_verified
        
    }
    
    
    function the_posts($posts){        
        global $wpdb, $wp_query;
        
        $db = debug_backtrace();   
        $custom_wp_query = $db[3]['object'];        
        
        //exceptions
        if( 
            ($this->get_current_language() == $this->get_default_language())  // original language
            || ($wp_query != $custom_wp_query)   // called by a custom query
            || (!$custom_wp_query->is_posts_page && !$custom_wp_query->is_home) // not the blog posts page           
            || $wp_query->is_singular //is singular
            || !empty($custom_wp_query->query_vars['category__not_in'])
            //|| !empty($custom_wp_query->query_vars['category__in'])
            //|| !empty($custom_wp_query->query_vars['category__and'])
            || !empty($custom_wp_query->query_vars['tag__not_in'])
            || !empty($custom_wp_query->query_vars['post__in'])
            || !empty($custom_wp_query->query_vars['post__not_in'])
            || !empty($custom_wp_query->query_vars['post_parent'])
        ){
            
            //$wp_query->query_vars = $this->wp_query->query_vars;            
            return $posts;                
        }
        // get the posts in the default language instead        
        $this_lang = $this->this_lang;
        $this->this_lang = $this->get_default_language(); 
               
        remove_filter('the_posts', array($this, 'the_posts')); 

        $custom_wp_query->query_vars['suppress_filters'] = 0;
        
        if(isset($custom_wp_query->query_vars['pagename']) && !empty($custom_wp_query->query_vars['pagename'])){
            $page_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name='{$custom_wp_query->query_vars['pagename']}' AND post_type='page'");            
            if($page_id){
                $tr_page_id = icl_object_id($page_id, 'page', false, $this->get_default_language());
                if($tr_page_id){
                    $custom_wp_query->query_vars['pagename'] = $wpdb->get_var("SELECT post_name FROM {$wpdb->posts} WHERE ID={$tr_page_id}");
                }
            }                        
        }
        

        
        // look for posts without translations
        if($posts){
            foreach($posts as $p){
                $pids[] = $p->ID;
            }
            $trids = $wpdb->get_col("
                SELECT trid 
                FROM {$wpdb->prefix}icl_translations 
                WHERE element_type='post' AND element_id IN (".join(',', $pids).") AND language_code = '".$this_lang."'");
            
            $posts_not_translated = $wpdb->get_col("
                SELECT element_id, COUNT(language_code) AS c
                FROM {$wpdb->prefix}icl_translations
                WHERE trid IN (".join(',', $trids).") GROUP BY trid HAVING c = 1 
            ");
            
            if($posts_not_translated){
                    $GLOBALS['__icl_the_posts_posts_not_translated'] = $posts_not_translated;
                    add_filter('posts_where', array($this, '_posts_untranslated_extra_posts_where'), 99);
            }

        }  
        
        //fix page for posts           
        unset($custom_wp_query->query_vars['page_id']); unset($custom_wp_query->query_vars['p']);
        
        $my_query = new WP_Query($custom_wp_query->query_vars);
        add_filter('the_posts', array($this, 'the_posts'));
        $this->this_lang = $this_lang;
        
        // create a map of the translated posts        
        foreach($posts as $post){
            $trans_posts[$post->ID] = $post;
        }        
        
        // loop original posts
        foreach($my_query->posts as $k=>$post){ // loop posts in the default language
            $trid = $this->get_element_trid($post->ID);
            $translations = $this->get_element_translations($trid); // get translations
            
            if(isset($translations[$this->get_current_language()])){ // if there is a translation in the current language
                if(isset($trans_posts[$translations[$this->get_current_language()]->element_id])){  //check the map of translated posts
                    $my_query->posts[$k] = $trans_posts[$translations[$this->get_current_language()]->element_id];
                }else{  // check if the translated post exists in the database still                                                                                                                
                    $_post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d AND post_status='publish' LIMIT 1", $translations[$this->get_current_language()]->element_id));                    
                    if(!empty($_post)){
                        $_post = sanitize_post($_post);
                        $my_query->posts[$k] = $_post;
                        
                    }else{
                        $my_query->posts[$k]->original_language = true;
                    }                
                } 
            }else{
                $my_query->posts[$k]->original_language = true;
            }
            
        }
        
        if($custom_wp_query == $wp_query){
            $wp_query->max_num_pages = $my_query->max_num_pages;
        }        
        
        $posts = $my_query->posts;
        
        unset($GLOBALS['__icl_the_posts_posts_not_translated']);
        remove_filter('posts_where', array($this, '_posts_untranslated_extra_posts_where'), 99);
        
        return $posts;
    }
    
    function _posts_untranslated_extra_posts_where($where){
        global $wpdb;
        $where .= ' OR ' . $wpdb->posts . '.ID IN (' . join(',', $GLOBALS['__icl_the_posts_posts_not_translated']) . ')';
        return $where;
    }
                                              
    function initialize_cache(){ 
        require_once ICL_PLUGIN_PATH . '/inc/cache.php';        
        $this->icl_translations_cache = new icl_cache();
        $this->icl_locale_cache = new icl_cache('locale', true);
        $this->icl_flag_cache = new icl_cache('flags', true);
        $this->icl_language_name_cache = new icl_cache('language_name', true);
        $this->icl_term_taxonomy_cache = new icl_cache();
        $this->icl_cms_nav_offsite_url_cache = new icl_cache('cms_nav_offsite_url', true);
    }
             
    function init(){ 

        $this->set_admin_language();
        //configure callbacks for plugin menu pages
        if(defined('WP_ADMIN') && isset($_GET['page']) && 0 === strpos($_GET['page'],basename(ICL_PLUGIN_PATH).'/')){
            add_action('icl_menu_footer', array($this, 'menu_footer'));
        }
        if($this->settings['existing_content_language_verified']){
            if(defined('WP_ADMIN')){
                if(isset($_GET['lang'])){
                    $this->this_lang = rtrim($_GET['lang'],'/');             
                }else{
                    $this->this_lang = $this->get_default_language();
                }                   
            }else{
                $al = $this->get_active_languages();
                foreach($al as $l){
                    $active_languages[] = $l['code'];
                }
                $active_languages[] = 'all';
                $s = $_SERVER['HTTPS']=='on'?'s':'';
                $request = 'http' . $s . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $home = get_option('home');
                $url_parts = parse_url($home);
                $blog_path = $url_parts['path']?$url_parts['path']:'';            
                switch($this->settings['language_negotiation_type']){
                    case 1:
                        $path  = str_replace($home,'',$request);
                        $parts = explode('?', $path);
                        $path = $parts[0];
                        $exp = explode('/',trim($path,'/'));                                        
                        if(in_array($exp[0], $active_languages)){
                            $this->this_lang = $exp[0];
                            
                            // before hijiking the SERVER[REQUEST_URI]
                            // override the canonical_redirect action
                            // keep a copy of the original request uri
                            remove_action('template_redirect', 'redirect_canonical');
                            global $_icl_server_request_uri;
                            $_icl_server_request_uri = $_SERVER['REQUEST_URI'];
                            add_action('template_redirect', 'icl_redirect_canonical_wrapper', 11);
                            function icl_redirect_canonical_wrapper(){
                                global $_icl_server_request_uri, $wp_query;
                                $requested_url  = ( !empty($_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
                                $requested_url .= $_SERVER['HTTP_HOST'];
                                $requested_url .= $_icl_server_request_uri;
                                redirect_canonical($requested_url);
                                
                                /*
                                if(isset($wp_query) && empty($wp_query->posts)){
                                    $wp_query->is_404 = true;
                                    $template = get_404_template();
                                    include($template);
                                    exit;
                                }
                                */
                                
                            }
                            //
                            
                            //deal with situations when template files need to be called directly
                            add_action('template_redirect', array($this, '_allow_calling_template_file_directly'));
                            
                            $_SERVER['REQUEST_URI'] = preg_replace('@^'. $blog_path . '/' . $this->this_lang.'@i', $blog_path ,$_SERVER['REQUEST_URI']);
                            // Check for special case of www.example.com/fr where the / is missing on the end
                            $parts = parse_url($_SERVER['REQUEST_URI']);
                            if(strlen($parts['path']) == 0){
                                $_SERVER['REQUEST_URI'] = '/' . $_SERVER['REQUEST_URI'];
                            }
                        }else{
                            $this->this_lang = $this->get_default_language();
                        }
                        break;
                    case 2:    
                        $exp = explode('.', $_SERVER['HTTP_HOST']);
                        $__l = array_search('http' . $s . '://' . $_SERVER['HTTP_HOST'] . $blog_path, $this->settings['language_domains']);
                        $this->this_lang = $__l?$__l:$this->get_default_language(); 
                        if(defined('ICL_USE_MULTIPLE_DOMAIN_LOGIN') && ICL_USE_MULTIPLE_DOMAIN_LOGIN){
                            include ICL_PLUGIN_PATH . '/modules/multiple-domains-login.php';
                        }                        
                        break;
                    case 3:
                    default:
                        if(isset($_GET['lang'])){
                            $this->this_lang = rtrim($_GET['lang'],'/');             
                        }else{
                            $this->this_lang = $this->get_default_language();
                        }                        
                }
            }
            
            //reorder active language to put 'this_lang' in front
            foreach($this->active_languages as $k=>$al){
                if($al['code']==$this->this_lang){                
                    unset($this->active_languages[$k]);
                    $this->active_languages = array_merge(array($k=>$al), $this->active_languages);
                }
            }
            
            add_filter('get_pagenum_link', array($this,'get_pagenum_link_filter'));        
            // filter some queries
            add_filter('query', array($this, 'filter_queries'));                
            
        }
        
        if(empty($this->settings['dont_show_help_admin_notice'])){
            if(count($this->get_active_languages()) < 2){
                add_action('admin_notices', array($this, 'help_admin_notice'));
            }
        }
                
        $short_v = implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3));
        if($this->settings['hide_upgrade_notice'] != $short_v){
            add_action('admin_notices', array($this, 'upgrade_notice'));
        }
        
        if ($this->icl_account_configured()) {
            add_action('admin_notices', array($this, 'icl_reminders'));
        }
        
        require ICL_PLUGIN_PATH . '/inc/template-constants.php';        
        if(defined('WPML_LOAD_API_SUPPORT')){
            require ICL_PLUGIN_PATH . '/inc/wpml-api.php';
            
        } 
        
        if($this->settings['promote_wpml']){
            add_action('wp_footer', array($this, 'display_wpml_footer'),20);
        }  
                
        if(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST){
            add_action('xmlrpc_call', array($this, 'xmlrpc_call_actions'));
            add_filter('xmlrpc_methods',array($this, 'xmlrpc_methods'));
        }
        
    }
    
    function ajax_setup(){
        require ICL_PLUGIN_PATH . '/ajax.php';
    }
                    
    function set_admin_language(){
        global $wpdb, $current_user;
        
        if(is_null($current_user) && function_exists('wp_get_current_user')){
            $u = wp_get_current_user();
            if($u->ID > 0){
                $current_user = $u;
            }
        }
                                   
        $active_languages = array_keys($wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages WHERE active=1"));   //don't use method get_active_language()
        
        $this->admin_language = $this->get_user_admin_language($current_user->data->ID);
                
        if($this->admin_language != '' && !in_array($this->admin_language, $active_languages)){
            delete_usermeta($current_user->data->ID,'icl_admin_language');
        }        
        if(!in_array($this->settings['admin_default_language'], $active_languages) || is_null($this->settings['admin_default_language'])){
            $this->settings['admin_default_language'] = '_default_';
            $this->save_settings();
        }
        
        if(!$this->admin_language){
            $this->admin_language = $this->settings['admin_default_language'];
        }
        if($this->admin_language == '_default_' && $this->get_default_language()){
            $this->admin_language = $this->get_default_language();
        }
    }
    
    function get_admin_language(){
        return $this->admin_language;
    }
      
    function get_user_admin_language($user_id) {
        static $lang = null;
        if ($lang === null) {
            $lang = get_usermeta($user_id,'icl_admin_language',true);
        }
        return $lang;
    }
    
    function administration_menu(){
        add_action('admin_print_scripts', array($this,'js_scripts_setup'));
        add_action('admin_print_styles', array($this,'css_setup'));
        if($this->settings['basic_menu']){        
            
            add_menu_page(__('WPML','sitepress'), __('WPML','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php',null, ICL_PLUGIN_URL . '/res/img/icon16.png');    
            add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Languages','sitepress'), __('Languages','sitepress'), 
                        'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php');             
            if(1 < count($this->get_active_languages())){                        
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Pro translation','sitepress'), __('Pro translation','sitepress'), 
                        'manage_options', basename(ICL_PLUGIN_PATH).'/menu/content-translation.php');                                                     
            }

        }else{
            
            add_menu_page(__('WPML','sitepress'), __('WPML','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/overview.php',null, ICL_PLUGIN_URL . '/res/img/icon16.png');              
            add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Overview','sitepress'), __('Overview','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/overview.php'); 
            add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Languages','sitepress'), __('Languages','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php'); 
            
            if(1 < count($this->get_active_languages())){                
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Theme and plugins localization','sitepress'), __('Theme and plugins localization','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/theme-localization.php'); 
                            icl_st_administration_menu();
            //if(1 < count($this->get_active_languages())){                
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Translation sync','sitepress'), __('Translation sync','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/translation-synchronization.php');                             
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Comments translation','sitepress'), __('Comments translation','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/comments-translation.php'); 
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Pro translation','sitepress'), __('Pro translation','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/content-translation.php');                 
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/content-translation.php', __('Pro translation','sitepress'), __('Pro translation','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/content-translation-how-it-works.php');
            }
            
            if($this->settings['modules']['cms-navigation']['enabled']){
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Navigation','sitepress'), __('Navigation','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/navigation.php'); 
            }
            if($this->settings['modules']['absolute-links']['enabled']){
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Sticky links','sitepress'), __('Sticky links','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/absolute-links.php'); 
            }
            if($_GET['page'] == basename(ICL_PLUGIN_PATH).'/menu/troubleshooting.php'){
                add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Troubleshooting','sitepress'), __('Troubleshooting','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/troubleshooting.php'); 
            }            
            
            add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('Compatibility packages','sitepress'), __('Compatibility packages','sitepress'), 
                            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/compatibility-packages.php');             
            
        }
    }

    function save_settings($settings=null){
        if(!is_null($settings)){
            foreach($settings as $k=>$v){
                if(is_array($v)){
                    foreach($v as $k2=>$v2){
                        $this->settings[$k][$k2] = $v2;
                    }
                }else{
                    $this->settings[$k] = $v;
                }
            }        
        }
        update_option('icl_sitepress_settings', $this->settings);
    }

    function get_settings(){
        return $this->settings;
    }    
    
    function verify_settings(){
        
        $default_settings = array(
            'basic_menu' => 0,
            'interview_translators' => 1,
            'existing_content_language_verified' => 0,
            'language_negotiation_type' => 3,
            'icl_lso_header' => 0, 
            'icl_lso_link_empty' => 0,
            'icl_lso_flags' => 0,
            'icl_lso_native_lang' => 1,
            'icl_lso_display_lang' => 1,
            'sync_page_ordering' => 1,
            'sync_page_parent' => 1,
            'sync_page_template' => 1,
            'sync_ping_status' => 1,
            'sync_comment_status' => 1,
            'sync_sticky_flag' => 1,
            'translated_document_status' => 0,
            'translation_pickup_method' => 0,
            'notify_complete' => 1,
            'translated_document_status' => 1,
            'remote_management' => 0,
            'auto_adjust_ids' => 1,
            'alert_delay' => 0,
            'modules' => array(
                'absolute-links' => array('enabled'=>0, 'sticky_links_widgets'=>1, 'sticky_links_strings'=>1),
                'cms-navigation'=>array('enabled'=>0, 'breadcrumbs_separator'=>' &raquo; ')
                )
        ); 
        
        //congigured for three levels
        $update_settings = false;
        foreach($default_settings as $key => $value){
            if(is_array($value)){
                foreach($value as $k2 => $v2){
                    if(is_array($v2)){
                        foreach($v2 as $k3 => $v3){
                            if(!isset($this->settings[$key][$k2][$k3])){
                                $this->settings[$key][$k2][$k3] = $v3;
                                $update_settings = true;
                            }                                
                        }
                    }else{
                        if(!isset($this->settings[$key][$k2])){
                            $this->settings[$key][$k2] = $v2;
                            $update_settings = true;
                        }
                    }
                }
            }else{
                if(!isset($this->settings[$key])){
                    $this->settings[$key] = $value;
                    $update_settings = true;
                }                
            }
        }
        
        if($update_settings){
            $this->save_settings();
        }          
    }

    function _validate_language_per_directory($language_code){
        if(!class_exists('WP_Http')){
           include_once ICL_PLUGIN_PATH . '/lib/http.php';
        }
        $client = new WP_Http();
        if(false === strpos($_POST['url'],'?')){$url_glue='?';}else{$url_glue='&';}                    
        //set_error_handler('trigger_error');
        $response = $client->request(get_option('home') . '/' . $language_code .'/' . $url_glue . '____icl_validate_domain=1', array('timeout'=>15, 'decompress'=>false));
        //restore_error_handler();
        return (!is_wp_error($response) && ($response['response']['code']=='200') && ($response['body'] == '<!--'.get_option('home').'-->'));
    }
    
    function update_icl_more_options() {
        $iclsettings['translator_choice'] = $_POST['icl_translator_choice'];
        switch($_POST['icl_translator_choice']) {
            case '0':
                $iclsettings['website_kind'] = 2;
                $iclsettings['interview_translators'] = 1;
                break;
                
            case '1':
                $iclsettings['website_kind'] = 2;
                $iclsettings['interview_translators'] = 1;
                break;
                
            default:
                $iclsettings['website_kind'] = 0;
                $iclsettings['interview_translators'] = 0;
                break;
                
                
        }

        $iclsettings['translation_pickup_method'] = $_POST['icl_delivery_method'];        
        if ($iclsettings['translation_pickup_method'] == 1){
            add_action('poll_for_translations', 'icl_poll_for_translations');
            wp_schedule_event(time(), 'hourly', 'poll_for_translations');
        } else {
            wp_clear_scheduled_hook('poll_for_translations');            
        }
        $iclsettings['translated_document_status'] = $_POST['icl_translated_document_status'];        
        $iclsettings['alert_delay'] = intval($_POST['icl_alert_delay']);
        $iclsettings['notify_complete'] = intval($_POST['icl_notify_complete']);
        $iclsettings['remote_management'] = intval($_POST['icl_remote_management']);
        $this->save_settings($iclsettings);
    }

    function save_language_pairs() {    
        // clear existing languages
        $lang_pairs = $this->settings['language_pairs'];
        if (is_array($lang_pairs)) {
            foreach ($lang_pairs as $from => $to) {
                $lang_pairs[$from] = array();
            }
        }
        
        // get the from languages
        $from_languages = array();
        foreach($_POST as $k=>$v){
            if(0 === strpos($k,'icl_lng_from_')){
                $f = str_replace('icl_lng_from_','',$k);
                $from_languages[] = $f;
            }
        }

        foreach($_POST as $k=>$v){
            if(0 !== strpos($k,'icl_lng_')) continue;
            if(0 === strpos($k,'icl_lng_to')){
                $t = str_replace('icl_lng_to_','',$k);
                $exp = explode('_',$t);
                if (in_array($exp[0], $from_languages)){
                    $lang_pairs[$exp[0]][$exp[1]] = 1;
                }
            }
        }

        $iclsettings['language_pairs'] = $lang_pairs; 
        $this->save_settings($iclsettings);
    }
    
    function get_active_languages($refresh = false){
        global $wpdb;   
        
        if($refresh || !$this->active_languages){
            if(defined('WP_ADMIN') && $this->admin_language){
                $in_language = $this->admin_language;
            }else{
                $in_language = $this->get_current_language()?$this->get_current_language():$this->get_default_language();    
            }  
            if (isset($this->icl_language_name_cache)) {
                $res = $this->icl_language_name_cache->get('in_language_'.$in_language);
            } else {
                $res = null;
            }
            
            if (!$res) { 
                $res = $wpdb->get_results("
                    SELECT l.id, code, english_name, active, lt.name AS display_name 
                    FROM {$wpdb->prefix}icl_languages l
                        JOIN {$wpdb->prefix}icl_languages_translations lt ON l.code=lt.language_code           
                    WHERE 
                        active=1 AND lt.display_language_code = '{$in_language}'
                    ORDER BY major DESC, english_name ASC", ARRAY_A);
                if (isset($this->icl_language_name_cache)) {
                    $this->icl_language_name_cache->set('in_language_'.$in_language, $res);
                }
            }
            
            $languages = array();
            if($res){
                foreach($res as $r){
                    $languages[$r['code']] = $r;
                }        
            } 
            
            if (isset($this->icl_language_name_cache)) {
                $res = $this->icl_language_name_cache->get('languages_'.$languages);
            } else {
                $res = null;
            }
            if (!$res) {
            
                $res = $wpdb->get_results("
                    SELECT language_code, name 
                    FROM {$wpdb->prefix}icl_languages_translations
                    WHERE language_code IN ('".join("','",array_keys($languages))."') AND language_code = display_language_code
                "); 
                if (isset($this->icl_language_name_cache)) {
                    $this->icl_language_name_cache->set('languages_'.$languages, $res);
                }
            }
                            
            foreach($res as $row){
                $languages[$row->language_code]['native_name'] = $row->name;     
            }
            
            $this->active_languages = $languages;           
        }
        
        // hide languages for front end
        global $current_user;        
        if(!is_admin() && !empty($this->settings['hidden_languages']) 
            && is_array($this->settings['hidden_languages']) && !get_usermeta($current_user->data->ID, 'icl_show_hidden_languages')){
            foreach($this->settings['hidden_languages'] as $l){
                unset($this->active_languages[$l]);
            }
        }
        return $this->active_languages;
    }
    
    function set_active_languages($arr){
        global $wpdb;
        if(!empty($arr)){
            foreach($arr as $code){
                $tmp[] = mysql_real_escape_string(trim($code));
            }
            
            // set the locale
            $current_active_languages = (array)$wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages WHERE active = 1");
            $new_languages = array_diff($tmp, $current_active_languages);

            if(!empty($new_languages)){
                foreach($new_languages as $code){                    
                    $default_locale = $wpdb->get_var("SELECT default_locale FROM {$wpdb->prefix}icl_languages WHERE code='{$code}'");
                    if($default_locale){
                        if($wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE code='{$code}'")){
                            $wpdb->update($wpdb->prefix.'icl_locale_map', array('locale'=>$default_locale), array('code'=>$code));
                        }else{
                            $wpdb->insert($wpdb->prefix.'icl_locale_map', array('code'=>$code, 'locale'=>$default_locale));
                        }
                    }                
                }
            }
            
            $codes = '(\'' . join('\',\'',$tmp) . '\')';
            $wpdb->update($wpdb->prefix.'icl_languages', array('active'=>0), array('active'=>'1'));
            $wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET active=1 WHERE code IN {$codes}");
            $this->icl_language_name_cache->clear();
        }
        
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
        $this->active_languages = $languages; 
        
        return true;
    }
    
    function get_languages($lang=false){
        global $wpdb;
        if(!$lang){
            $lang = $this->get_default_language();
        }                                           
        $res = $wpdb->get_results("
            SELECT 
                code, english_name, major, active, default_locale, lt.name AS display_name   
            FROM {$wpdb->prefix}icl_languages l
                JOIN {$wpdb->prefix}icl_languages_translations lt ON l.code=lt.language_code           
            WHERE lt.display_language_code = '{$lang}' 
            ORDER BY major DESC, english_name ASC", ARRAY_A);
        $languages = array();
        foreach((array)$res as $r){
            $languages[] = $r;
        }
        return $languages;
    }
    
    function get_language_details($code){
        global $wpdb;
        if(defined('WP_ADMIN')){
            $dcode = $this->admin_language;
        }else{
            $dcode = $code;
        }
        if (isset($this->icl_language_name_cache)){
            $details = $this->icl_language_name_cache->get('language_details_'.$code.$dcode);
        } else {
            $details = null;
        }
        if (!$details){
            $details = $wpdb->get_row("
                SELECT 
                    code, english_name, major, active, lt.name AS display_name   
                FROM {$wpdb->prefix}icl_languages l
                    JOIN {$wpdb->prefix}icl_languages_translations lt ON l.code=lt.language_code           
                WHERE lt.display_language_code = '{$dcode}' AND code='{$code}'
                ORDER BY major DESC, english_name ASC", ARRAY_A);
            if (isset($this->icl_language_name_cache)){
                $this->icl_language_name_cache->set('language_details_'.$code.$dcode, $details);
            }
        }
        
        return $details;
    }

    function get_language_code($english_name){
        global $wpdb;
        $code = $wpdb->get_row("
            SELECT 
                code
            FROM {$wpdb->prefix}icl_languages
            WHERE english_name = '{$english_name}'", ARRAY_A);
        return $code['code'];
    }

    function get_icl_translator_status(&$iclsettings, $res = NULL){
        
        if ($res == NULL) {
            // check what languages we have translators for.
            require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
            require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
            require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
            
            $icl_query = new ICanLocalizeQuery($iclsettings['site_id'], $iclsettings['access_key']);
            $res = $icl_query->get_website_details();
        }
        
        if(isset($res['translation_languages']['translation_language'])){
            $translation_languages = $res['translation_languages']['translation_language'];
            if(!isset($translation_languages[0])){
                $target = $translation_languages;
                $translation_languages = array(0 => $target);
            }
            foreach($translation_languages as $lang){
                $target[] = array('from' => $this->get_language_code(apply_filters('icl_server_languages_map', $lang['attr']['from_language_name'], true)),
                                  'to' => $this->get_language_code(apply_filters('icl_server_languages_map', $lang['attr']['to_language_name'], true)),
                                  'have_translators' => $lang['attr']['have_translators'],
                                  'available_translators' => $lang['attr']['available_translators'],
                                  'applications' => $lang['attr']['applications'],
                                  'contract_id' => $lang['attr']['contract_id'],
                                  'id' => $lang['attr']['id'],
                                  );
            }
            $iclsettings['icl_lang_status'] = $target;
        }
        
        if(isset($res['client']['attr'])){
            $iclsettings['icl_balance'] = $res['client']['attr']['balance'];
        }
        
        $iclsettings['icl_support_ticket_id'] = $res['attr']['support_ticket_id'];
    }

    function get_language_status_text($from_lang, $to_lang) {
        $lang_status = $this->settings['icl_lang_status'];        
        $response = '';
        if ($lang_status && $this->icl_account_configured() && isset($this->settings['language_pairs'][$from_lang][$to_lang])) {
            foreach ($lang_status as $lang) {                
                if ($from_lang == $lang['from'] && $to_lang == $lang['to']) {
                    if (isset($lang['available_translators'])) {
                        if (!$lang['available_translators']) {
                            if ($this->settings['icl_support_ticket_id'] == '') {
                                // No translators available on icanlocalize for this language pair.
                                $response = sprintf(__('- (No translators available - please %sprovide more information about your site%s)', 'sitepress'),
                                                    $this->create_icl_popup_link(ICL_API_ENDPOINT. '/websites/' . $this->settings['site_id'] . '/explain?after=refresh_langs', 'ICanLocalize'),
                                                    '</a>');
                            } else {
                                $response = sprintf(__('- (No translators available - %scheck progress%s)', 'sitepress'),
                                                    $this->create_icl_popup_link(ICL_API_ENDPOINT. '/support/show/' . $this->settings['icl_support_ticket_id'] . '?after=refresh_langs', 'ICanLocalize'),
                                                    '</a>');
                            }
                            
                        } else if (!$lang['applications']) {
                            // No translators have applied for this language pair.
                            //$response = sprintf(__('- (Waiting for translators to apply)', 'sitepress'));
                            $response = sprintf(__('- (Translators available)', 'sitepress')); 
                        } else if (!$lang['have_translators']) {
                            // translators have applied but none selected yet
                            $response = sprintf(__('- (%s translators applied - %schoose your translator%s)', 'sitepress'),
                                                $lang['applications'],
                                                $this->create_icl_popup_link(ICL_API_ENDPOINT. '/websites/' . $this->settings['site_id'] . '/website_translation_offers/' .  $lang['id'], 'ICanLocalize'),
                                                '</a>');
                        } else {
                            // there are translators ready to translate
                            $response = sprintf(__('- (Translator selected - %scommunicate with your translator%s)', 'sitepress'),
                                                $this->create_icl_popup_link(ICL_API_ENDPOINT. '/websites/' . $this->settings['site_id'] . '/website_translation_offers/' .  $lang['id'] . '/website_translation_contracts/' . $lang['contract_id'], 'ICanLocalzie'),
                                                '</a>');
                        }
    
                        return $response;
                        
                    }
                break;
                }
                                           
            }
        }
        // no status found        
        return '';
    }

    function are_waiting_for_translators($from_lang) {
        $lang_status = $this->settings['icl_lang_status'];        
        if ($lang_status && $this->icl_account_configured()) {
            foreach ($lang_status as $lang) {
                if ($from_lang == $lang['from']) {
                    if (isset($lang['available_translators'])) {
                        if ($lang['available_translators'] && !$lang['applications']) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
    
    function get_default_language(){        
        return $this->settings['default_language'];
    }
    
    function get_current_language(){                                  
        return apply_filters('icl_current_language' , $this->this_lang);
    }
    
    function set_default_language($code){        
        global $wpdb;
        $iclsettings['default_language'] = $code;
        $this->save_settings($iclsettings);
        
        // change WP locale
        $locale = $this->get_locale($code);
        if($locale){
            update_option('WPLANG', $locale);
        }      
        if($code != 'en' && !file_exists(ABSPATH . LANGDIR . '/' . $locale . '.mo')){
            return 1; //locale not installed
        }
        
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
        if(!$this->get_icl_translation_enabled()){
            $errors[] = __('Professional translation not enabled', 'sitepress');  
        }
        return $errors;        
    }
    
    function icl_account_configured(){
        return $this->settings['site_id'] && $this->settings['access_key'];
    }

    function reminders_popup(){
        include ICL_PLUGIN_PATH . '/modules/icl-translation/icl-reminder-popup.php';
        exit;
    }
    
    function create_icl_popup_link($link, $title = null) {
        if ($title) {
            return '<a class="icl_thickbox" title="' . $title . '" href="admin.php?page='.ICL_PLUGIN_FOLDER . "/menu/languages.php&icl_action=reminder_popup&target=" . $link .'">';
        } else {
            return '<a class="icl_thickbox" href="admin.php?page='.ICL_PLUGIN_FOLDER . "/menu/languages&icl_action=reminder_popup&target=" . $link .'">';
        }
    }
    
    function js_scripts_setup(){        
        global $pagenow, $wpdb;
        if(isset($_GET['page'])){
            $page = basename($_GET['page']);
            $page_basename = str_replace('.php','',$page);
        }
        ?>
        <script type="text/javascript">   
		// <![CDATA[     
        var icl_ajx_url = '<?php echo rtrim(get_option('siteurl'),'/') . '/wp-admin/' ?>admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php';
        var icl_ajx_saved = '<?php echo __('Data saved','sitepress') ?>';
        var icl_ajx_error = '<?php echo __('Error: data not saved','sitepress') ?>';
        var icl_default_mark = '<?php echo __('default','sitepress') ?>';     
        var icl_this_lang = '<?php echo $this->this_lang ?>';   
        var icl_ajxloaderimg_src = '<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif';
        var icl_cat_adder_msg = '<?php echo __('To add categories that already exist in other languages go to the <a href="categories.php">category management page<\/a>','sitepress')?>';
		// ]]>
		
        <?php if(!$this->settings['ajx_health_checked']): ?>
        addLoadEvent(function(){
            jQuery.ajax({type: "POST",url: icl_ajx_url,data: "icl_ajx_action=health_check", error: function(msg){
                    if(jQuery('#icl_initial_language').length){
                        jQuery('#icl_initial_language input').attr('disabled', 'disabled');
                    }
                    jQuery('.wrap').prepend('<div class="error"><p><?php 
                        echo str_replace("'","\\'",sprintf(__("WPML can't run normally. There is an installation or server configuration problem. %sShow details%s",                            'sitepress'), 
                        '<a href="#" onclick="jQuery(this).parent().next().slideToggle()">', '</a>'));
                    ?></p><p style="display:none"><?php _e('AJAX Error:', 'sitepress')?> ' + msg.statusText + ' ['+msg.status+']<br />URL:'+ icl_ajx_url +'</p></div>');
            }});
        });
        <?php endif; ?>
        </script>         
        <?php  
        wp_enqueue_script('sitepress-scripts', ICL_PLUGIN_URL . '/res/js/scripts.js', array(), ICL_SITEPRESS_VERSION);
        if(isset($page_basename) && file_exists(ICL_PLUGIN_PATH . '/res/js/'.$page_basename.'.js')){
            wp_enqueue_script('sitepress-' . $page_basename, ICL_PLUGIN_URL . '/res/js/'.$page_basename.'.js', array(), ICL_SITEPRESS_VERSION);
        }
        if('options-reading.php' == $pagenow ){
                list($warn_home, $warn_posts) = $this->verify_home_and_blog_pages_translations();
                if($warn_home || $warn_posts){ ?>
                <script type="text/javascript">        
                addLoadEvent(function(){
                jQuery('input[name="show_on_front"]').parent().parent().parent().parent().append('<?php echo $warn_home . $warn_posts ?>');
                });
                </script>
                <?php } 
        }
        
        // display correct links on the posts by status break down
        // also fix links to category and tag pages
        if( ('edit.php' == $pagenow || 'edit-pages.php' == $pagenow || 'categories.php' == $pagenow || 'edit-tags.php' == $pagenow) 
                && $this->get_current_language() != $this->get_default_language()){
                ?>
                <script type="text/javascript">        
                addLoadEvent(function(){
                    jQuery('.subsubsub li a').each(function(){
                        h = jQuery(this).attr('href');
                        if(-1 == h.indexOf('?')) urlg = '?'; else urlg = '&';
                        jQuery(this).attr('href', h + urlg + 'lang=<?php echo $this->get_current_language()?>');
                    });
                    jQuery('.column-categories a, .column-tags a, .column-posts a').each(function(){
                        jQuery(this).attr('href', jQuery(this).attr('href') + '&lang=<?php echo $this->get_current_language()?>');
                    });
                    <?php /*  needs jQuery 1.3
                    jQuery('.column-categories a, .column-tags a, .column-posts a').live('mouseover', function(){
                        if(-1 == jQuery(this).attr('href').search('lang='+icl_this_lang)){
                            h = jQuery(this).attr('href');
                            if(-1 == h.indexOf('?')) urlg = '?'; else urlg = '&';                            
                            jQuery(this).attr('href', h + urlg + 'lang='+icl_this_lang);
                        }
                    });     
                    */ ?>           
                });
                </script>
                <?php
        }
        
        if('post-new.php' == $pagenow){
            if(isset($_GET['trid'])){
                $translations = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$_GET['trid']}'");    
                remove_filter('option_sticky_posts', array($this,'option_sticky_posts')); // remove filter used to get language relevant stickies. get them all
                $sticky_posts = get_option('sticky_posts');
                add_filter('option_sticky_posts', array($this,'option_sticky_posts')); // add filter back
                $is_sticky = false;
                foreach($translations as $t){
                    if(in_array($t, $sticky_posts)){
                        $is_sticky = true;
                        break;
                    }
                }
                if(isset($_GET['trid']) && ($this->settings['sync_ping_status'] || $this->settings['sync_comment_status'])){
                    $res = $wpdb->get_row("SELECT comment_status, ping_status FROM {$wpdb->prefix}icl_translations t 
                    JOIN {$wpdb->posts} p ON t.element_id = p.ID WHERE t.trid='".intval($_GET['trid'])."'"); ?>
                    <script type="text/javascript">addLoadEvent(function(){
                    <?php if($this->settings['sync_comment_status']): ?>
                        <?php if($res->comment_status == 'open'): ?>
                        jQuery('#comment_status').attr('checked','checked');
                        <?php else: ?>
                        jQuery('#comment_status').removeAttr('checked');
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if($this->settings['sync_ping_status']): ?>
                        <?php if($res->ping_status == 'open'): ?>
                        jQuery('#ping_status').attr('checked','checked');
                        <?php else: ?>
                        jQuery('#ping_status').removeAttr('checked');
                        <?php endif; ?>                    
                    <?php endif; ?>
                    });</script><?php 
                }
                //get menu_order for page
                
            }
            ?>
            <?php if($is_sticky && $this->settings['sync_sticky_flag']): ?><script type="text/javascript">addLoadEvent(function(){jQuery('#sticky').attr('checked','checked');});</script><?php endif; ?>               
            <?php
        }elseif('page-new.php' == $pagenow){
            if(isset($_GET['trid']) && ($this->settings['sync_page_template'] || $this->settings['sync_page_ordering'])){
                $res = $wpdb->get_row("
                    SELECT p.ID, p.menu_order FROM {$wpdb->prefix}icl_translations t
                    JOIN {$wpdb->posts} p ON t.element_id = p.ID
                    WHERE t.trid='{$_GET['trid']}' AND p.post_type='page' AND t.element_type='post'
                "); 
                if($this->settings['sync_page_ordering']){
                    $menu_order = $res->menu_order;                   
                }else{
                    $menu_order = false;
                }
                if($this->settings['sync_page_template']){
                    $page_template = get_post_meta($res->ID, '_wp_page_template', true);
                }else{
                    $page_template = false;
                }                
                if($menu_order || $page_template){
                    ?><script type="text/javascript">addLoadEvent(function(){ <?php 
                    if($menu_order){ ?>
                        jQuery('#menu_order').val(<?php echo $menu_order ?>);
                    <?php }
                    if($page_template && 'default' != $page_template){ ?>
                        jQuery('#page_template').val('<?php echo $page_template ?>');
                    <?php }
                    ?>});</script><?php
                }                
            }
        }elseif('edit-comments.php' == $pagenow || 'index.php' == $pagenow || 'post.php' == $pagenow){
            wp_enqueue_script('sitepress-' . $page_basename, ICL_PLUGIN_URL . '/res/js/comments-translation.js', array(), ICL_SITEPRESS_VERSION);
        }
        
        if (is_admin() && $this->icl_account_configured()) {
            wp_enqueue_script('thickbox');
            wp_enqueue_script( 'theme-preview' );
            
            wp_enqueue_script('sitepress-icl_reminders', ICL_PLUGIN_URL . '/res/js/icl_reminders.js', array(), ICL_SITEPRESS_VERSION);
        }

        if('content-translation' == $page_basename) {        
            wp_enqueue_script('icl-sidebar-scripts', ICL_PLUGIN_URL . '/res/js/icl_sidebar.js', array(), ICL_SITEPRESS_VERSION);
        }
        if('languages' == $page_basename || 'string-translation' == $page_basename) {        
            wp_enqueue_script( 'colorpicker' );
        }
    }
       
    function front_end_js(){        
        echo '<script type="text/javascript">var icl_lang = \''.$this->this_lang.'\';var icl_home = \''.$this->language_url().'\';</script>' . PHP_EOL;        
        if(defined('ICL_DONT_LOAD_LANGUAGES_JS') && ICL_DONT_LOAD_LANGUAGES_JS){
            return;
        }
        echo '<script type="text/javascript" src="'. ICL_PLUGIN_URL . '/res/js/sitepress.js"></script>' . PHP_EOL;        
    }
    
    function js_scripts_categories(){
        wp_enqueue_script('sitepress-categories', ICL_PLUGIN_URL . '/res/js/categories.js', array(), ICL_SITEPRESS_VERSION);
    }
    
    function js_scripts_tags(){
        wp_enqueue_script('sitepress-tags', ICL_PLUGIN_URL . '/res/js/tags.js', array(), ICL_SITEPRESS_VERSION);
    }
    
    function css_setup(){
        if($this->settings['basic_menu']){
            echo '<style type="text/css">.icl_advanced_feature{display:none}</style>';       
        }
        if(isset($_GET['page'])){
            $page = basename($_GET['page']);
            $page_basename = str_replace('.php','',$page);        
        }        
        wp_enqueue_style('sitepress-style', ICL_PLUGIN_URL . '/res/css/style.css', array(), ICL_SITEPRESS_VERSION);
        if(isset($page_basename) && file_exists(ICL_PLUGIN_PATH . '/res/css/'.$page_basename.'.css')){
            wp_enqueue_style('sitepress-' . $page_basename, ICL_PLUGIN_URL . '/res/css/'.$page_basename.'.css', array(), ICL_SITEPRESS_VERSION);
        }
        
        if (is_admin() && $this->icl_account_configured()) {
            wp_enqueue_style('thickbox');
        }
        
    }
    
    function process_forms(){
        global $wpdb;
        require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
        require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
        require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
        
        if(isset($_POST['icl_post_action'])){
            switch($_POST['icl_post_action']){
                case 'save_theme_localization':
                    $locales = array();
                    foreach($_POST as $k=>$v){
                        if(0 !== strpos($k, 'locale_file_name_') || !trim($v)) continue;
                        $locales[str_replace('locale_file_name_','',$k)] = $v;                                                
                    }
                    if(!empty($locales)){
                        $this->set_locale_file_names($locales);
                    }                    
                    $this->settings['gettext_theme_domain_name'] = $_POST['icl_domain_name'];
                    $this->save_settings();
                    global $sitepress_settings;
                    $sitepress_settings = $this->get_settings();
                    break;
            }
            return;
        }
        if( (isset($_POST['icl_create_account_nonce']) && $_POST['icl_create_account_nonce']==wp_create_nonce('icl_create_account')) || (isset($_POST['icl_configure_account_nonce']) && $_POST['icl_configure_account_nonce']==wp_create_nonce('icl_configure_account'))){
            if (isset($_POST['icl_content_trans_setup_back_2'])) {
                // back button in wizard mode.
                $this->settings['content_translation_languages_setup'] = 0;
                $this->settings['content_translation_setup_wizard_step'] = 1;
                $this->save_settings();
                
            } else {
                $user = $_POST['user'];
                $user['create_account'] = isset($_POST['icl_create_account_nonce']) ? 1 : 0;
                $user['platform_kind'] = 2;
                $user['cms_kind'] = 1;
                $user['blogid'] = $wpdb->blogid?$wpdb->blogid:1;
                $user['url'] = get_option('home');
                $user['title'] = get_option('blogname');
                $user['description'] = get_option('blogdescription');
                $user['is_verified'] = 1;
                
               if($user['create_account'] && defined('ICL_AFFILIATE_ID') && defined('ICL_AFFILIATE_KEY')){
                    $user['affiliate_id'] = ICL_AFFILIATE_ID;
                    $user['affiliate_key'] = ICL_AFFILIATE_KEY;
                }
                            
                $user['interview_translators'] = $this->settings['interview_translators'];
                $user['project_kind'] = $this->settings['website_kind'];
                /*
                 if(is_null($user['project_kind']) || $user['project_kind']==''){
                    $_POST['icl_form_errors'] = __('Please select the kind of website','sitepress');               
                    return;
                }
                */
                $user['pickup_type'] = intval($this->settings['translation_pickup_method']);
                                
                $notifications = 0;
                if ( $this->settings['icl_notify_complete']){
                    $notifications += 1;
                }
                if ( $this->settings['alert_delay']){
                    $notifications += 2;
                }
                $user['notifications'] = $notifications;
    
                // prepare language pairs
                
                $pay_per_use = $this->settings['translator_choice'] == 1;
                
                $language_pairs = $this->settings['language_pairs'];
                $lang_pairs = array();
                if(isset($language_pairs)){
                    foreach($language_pairs as $k=>$v){
                        $english_fr = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                        foreach($v as $k=>$v){
                            $incr++;
                            $english_to = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                            $lang_pairs['from_language'.$incr] = apply_filters('icl_server_languages_map', $english_fr); 
                            $lang_pairs['to_language'.$incr] = apply_filters('icl_server_languages_map', $english_to);
                            if ($pay_per_use) {
                                $lang_pairs['pay_per_use'.$incr] = 1;
                            }
                        }                    
                    }
                }
                $icl_query = new ICanLocalizeQuery();                
                list($site_id, $access_key) = $icl_query->createAccount(array_merge($user,$lang_pairs));                
                if(!$site_id){
                    if ($access_key) {
                        $_POST['icl_form_errors'] = $access_key;
                    } else {
                        $_POST['icl_form_errors'] = __('An unknown error has occurred when communicating with the ICanLocalize server. Please try again.', 'sitepress');
                        // We will force the next try to be http.
                        update_option('_force_mp_post_http', 1);
                    }
                }else{                    
                    $iclsettings['site_id'] = $site_id;
                    $iclsettings['access_key'] = $access_key;
                    $iclsettings['icl_account_email'] = $user['email'];
                    $this->save_settings($iclsettings);
                    if($user['create_account']==1){
                        $_POST['icl_form_success'] = __('A project on ICanLocalize has been created.', 'sitepress') . '<br />';
                        
                    }else{
                        $_POST['icl_form_success'] = __('Project added','sitepress');
                    }
                    $this->get_icl_translator_status($iclsettings);
                    $this->save_settings($iclsettings);
                }

                if (intval($site_id) > 0 &&
                            $access_key &&
                            $this->settings['content_translation_setup_complete'] == 0 && 
                            $this->settings['content_translation_setup_wizard_step'] == 3 && 
                            !isset($_POST['icl_form_errors'])) {
                    // we are running the wizard, so we can finish it now.
                    $this->settings['content_translation_setup_complete'] = 1;
                    $this->settings['content_translation_setup_wizard_step'] = 0;
                    $this->save_settings();
                    
                }
                
            }
        }
        elseif(isset($_POST['icl_initial_languagenonce']) && $_POST['icl_initial_languagenonce']==wp_create_nonce('icl_initial_language')){
            
            $this->prepopulate_translations($_POST['icl_initial_language_code']);
            $wpdb->update($wpdb->prefix . 'icl_languages', array('active'=>'1'), array('code'=>$_POST['icl_initial_language_code']));
            $blog_default_cat = get_option('default_category');
            $blog_default_cat_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id='{$blog_default_cat}' AND taxonomy='category'");
            
            if(isset($_POST['save_one_language'])){
                $this->settings['setup_wizard_step'] = 0;
                $this->settings['setup_complete'] = 1;
            }else{
                $this->settings['setup_wizard_step'] = 2;
            }
            
            $this->settings['default_categories'] = array($_POST['icl_initial_language_code'] => $blog_default_cat_tax_id);
            $this->settings['existing_content_language_verified'] = 1;            
            $this->settings['default_language'] = $_POST['icl_initial_language_code'];            
            $this->settings['admin_default_language'] = $this->admin_language = $_POST['icl_initial_language_code'];            
            
            // set the locale in the icl_locale_map (if it's not set)
            if(!$wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE code='{$_POST['icl_initial_language_code']}'")){
                $default_locale = $wpdb->get_var("SELECT default_locale FROM {$wpdb->prefix}icl_languages WHERE code='{$_POST['icl_initial_language_code']}'");
                if($default_locale){
                    $wpdb->insert($wpdb->prefix.'icl_locale_map', array('code'=>$_POST['icl_initial_language_code'], 'locale'=>$default_locale));
                    
                }
            }
            
            $this->save_settings();                                
            global $sitepress_settings;
            $sitepress_settings = $this->settings;
            $this->get_active_languages(true); //refresh active languages list
            do_action('icl_initial_language_set');
        }elseif(isset($_POST['icl_change_website_access_data_nonce']) && $_POST['icl_change_website_access_data_nonce']==wp_create_nonce('icl_change_website_access_data')){
            $iclsettings['access_key'] = $_POST['access']['access_key'];
            $iclsettings['site_id'] = $_POST['access']['website_id'];
            $this->save_settings($iclsettings);

            // Now try to access ICL server                
            $icl_query = new ICanLocalizeQuery($iclsettings['site_id'], $iclsettings['access_key']);
            $res = $icl_query->get_website_details();
            
            if(isset($res['attr']['id']) and $res['attr']['id'] == $iclsettings['site_id']){
                $_POST['icl_form_success'] = __('Your ICanLocalize account details have been confirmed and saved','sitepress');
            } else {
                $message = __('The ICanLocalize access details are not correct.','sitepress') . '<br />';
                $message .= __('Log on to the ICanLocalize server to get your access details. ','sitepress');
                $message .= '<a href="'. ICL_API_ENDPOINT . '">' . ICL_API_ENDPOINT . '</a>';
                $_POST['icl_form_errors'] = $message;
            }            
            
        }elseif(isset($_POST['icl_language_pairs_formnounce']) && $_POST['icl_language_pairs_formnounce'] == wp_create_nonce('icl_language_pairs_form')) {
            $this->save_language_pairs();

            $this->settings['content_translation_languages_setup'] = 1;
            $this->settings['content_translation_setup_wizard_step'] = 3;
            
            $this->settings['website_kind'] = 2;
            $this->settings['interview_translators'] = 1;
            
            $this->save_settings();
            
        }elseif(isset($_POST['icl_more_options_wizardnounce']) && $_POST['icl_more_options_wizardnounce'] == wp_create_nonce('icl_more_options_wizard')) {
            if(isset($_POST['icl_content_trans_setup_back_2'])){
                // back button.
                $this->settings['content_translation_languages_setup'] = 0;
                $this->settings['content_translation_setup_wizard_step'] = 1;
                $this->save_settings();
            }elseif(isset($_POST['icl_content_trans_setup_next_2']) || isset($_POST['icl_content_trans_setup_next_2_enter'])){
                // next button.
                $this->update_icl_more_options();
                $this->settings['content_translation_setup_wizard_step'] = 3;
                $this->save_settings();
            }
        }
    }
    
    function prepopulate_translations($lang){        
        global $wpdb;        
        if($this->settings['existing_content_language_verified']) return;
        
        $this->icl_translations_cache->clear();
        
        mysql_query("TRUNCATE TABLE {$wpdb->prefix}icl_translations");
        mysql_query("
            INSERT INTO {$wpdb->prefix}icl_translations(element_type, element_id, trid, language_code, source_language_code)
            SELECT 'post', ID, ID, '{$lang}', NULL FROM {$wpdb->posts} WHERE post_type IN ('post','page')
            ");
        $maxtrid = 1 + $wpdb->get_var("SELECT MAX(trid) FROM {$wpdb->prefix}icl_translations");        
        mysql_query("
            INSERT INTO {$wpdb->prefix}icl_translations(element_type, element_id, trid, language_code, source_language_code)
            SELECT 'category', term_taxonomy_id, {$maxtrid}+term_taxonomy_id, '{$lang}', NULL FROM {$wpdb->term_taxonomy}
            ");
        $maxtrid = 1 + $wpdb->get_var("SELECT MAX(trid) FROM {$wpdb->prefix}icl_translations");
        mysql_query("
            INSERT INTO {$wpdb->prefix}icl_translations(element_type, element_id, trid, language_code, source_language_code)
            SELECT 'tag', term_taxonomy_id, {$maxtrid}+term_taxonomy_id, '{$lang}', NULL FROM {$wpdb->term_taxonomy}
            ");
        $maxtrid = 1 + $wpdb->get_var("SELECT MAX(trid) FROM {$wpdb->prefix}icl_translations");
        mysql_query("
            INSERT INTO {$wpdb->prefix}icl_translations(element_type, element_id, trid, language_code, source_language_code)
            SELECT 'comment', comment_ID, {$maxtrid}+comment_ID, '{$lang}', NULL FROM {$wpdb->comments}
            ");            
    }
    
    function post_edit_language_options(){
        global $wpdb;
        if(function_exists('add_meta_box')){
            add_meta_box('icl_div', __('Language', 'sitepress'), array($this,'meta_box'), 'post', 'side', 'high');
            add_meta_box('icl_div', __('Language', 'sitepress'), array($this,'meta_box'), 'page', 'side', 'high');
        }
        /*
        if(isset($_GET['icl_action']) && $_GET['icl_action']=='set_post_language'){
            // delete the original translation information.
            $post_id = $_GET['post'];
            $src_trid = $this->get_element_trid($_GET['translation_of'], 'post');
            $wpdb->update(
                $wpdb->prefix.'icl_translations', 
                array('trid'=>$src_trid, 'language_code'=>$_GET['lang'], 'source_language_code'=>$this->get_default_language()), 
                array('element_id'=>$post_id, 'element_type'=>'post')
            );
        }
        */
    }
    
    function set_element_language_details($el_id, $el_type='post', $trid, $language_code){
        global $wpdb;
        
        if($trid){  // it's a translation of an existing element  
                                                         
            // check whether we have an orphan translation - the same trid and language but a different element id                                                     
            $translation_id = $wpdb->get_var("
                SELECT translation_id FROM {$wpdb->prefix}icl_translations 
                WHERE   trid = '{$trid}' 
                    AND language_code = '{$language_code}' 
                    AND element_id <> '{$el_id}'
            ");            
            if($translation_id){
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id={$translation_id}");
                $this->icl_translations_cache->clear();
            }
            
            if($translation_id = $wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type='{$el_type}' AND element_id='{$el_id}' AND trid='{$trid}'")){
                //case of language change
                $wpdb->update($wpdb->prefix.'icl_translations', 
                    array('language_code'=>$language_code), 
                    array('translation_id'=>$translation_id));                
            } elseif($existing_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='{$el_type}' AND element_id='{$el_id}'")){                
                //case of changing the "translation of"
                $wpdb->update($wpdb->prefix.'icl_translations', 
                    array('trid'=>$trid, 'language_code'=>$language_code), 
                    array('element_type'=>$el_type, 'element_id'=>$el_id));
                $this->icl_translations_cache->clear();
            }else{
                //get source
                $src_language_code = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND source_language_code IS NULL"); 
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
                $this->icl_translations_cache->clear();
                
            }
        }else{ // it's a new element or we are removing it from a trid
            if($translation_id = $wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type='{$el_type}' AND element_id='{$el_id}'")){
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id={$translation_id}");    
                $this->icl_translations_cache->clear();
            } 
        
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
    
    function delete_element_translation($trid, $el_type, $language_code = false){
        global $wpdb;
        $trid = intval($trid);
        $el_type = $wpdb->escape($el_type);
        $where = '';
        if($language_code){
            $where .= " AND language_code='".$wpdb->escape($language_code)."'";
        }
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_type='{$el_type}' {$where}");
        $this->icl_translations_cache->clear();
    }
    
    function get_element_language_details($el_id, $el_type){        
        global $wpdb;
        static $pre_load_done = false;
        if (!$pre_load_done && !ICL_DISABLE_CACHE) {
            // search previous queries for a group of posts
            foreach ($this->queries as $query){
                $pos = strstr($query, 'post_id IN (');
                if ($pos !== FALSE) {
                    $group = substr($pos, 10);
                    $group = substr($group, 0, strpos($group, ')') + 1);

                    $query = 
                        "SELECT element_id, trid, language_code, source_language_code 
                        FROM {$wpdb->prefix}icl_translations
                        WHERE element_id IN {$group} AND element_type='post'";
                    $ret = $wpdb->get_results($query);        
                    foreach($ret as $details){
                        if (isset($this->icl_translations_cache)) {
                            $this->icl_translations_cache->set($details->element_id.'post', $details);
                        }
                    }
                    
                    // get the taxonomy for the posts for later use
                    // categories first
                    $query =
                        "SELECT DISTINCT(tr.term_taxonomy_id), tt.term_id, tt.taxonomy, icl.trid, icl.language_code, icl.source_language_code
                        FROM {$wpdb->prefix}term_relationships as tr
                        LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt
                        ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        LEFT JOIN {$wpdb->prefix}icl_translations as icl ON tr.term_taxonomy_id = icl.element_id
                        WHERE tr.object_id IN {$group}
                        AND (icl.element_type='category' and tt.taxonomy='category')
                        ";
                    $query .= "UNION
                    ";
                    $query .=
                        "SELECT DISTINCT(tr.term_taxonomy_id), tt.term_id, tt.taxonomy, icl.trid, icl.language_code, icl.source_language_code
                        FROM {$wpdb->prefix}term_relationships as tr
                        LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt
                        ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        LEFT JOIN {$wpdb->prefix}icl_translations as icl ON tr.term_taxonomy_id = icl.element_id
                        WHERE tr.object_id IN {$group}
                        AND (icl.element_type='tag' and tt.taxonomy='post_tag')"
                        ;
                    $ret = $wpdb->get_results($query);        
                    foreach($ret as $details){
                        // save language details
                        $lang_details = new stdClass();
                        $lang_details->trid = $details->trid;
                        $lang_details->language_code = $details->language_code;
                        $lang_details->source_language_code = $details->source_language_code;
                        if (isset($this->icl_translations_cache)) {
                            if ($details->taxonomy == 'category') {
                                $this->icl_translations_cache->set($details->term_taxonomy_id.'category', $lang_details);
                                // save the term taxonomy
                                $this->icl_term_taxonomy_cache->set('category_'.$details->term_id, $details->term_taxonomy_id);
                            } else {
                                $this->icl_translations_cache->set($details->term_taxonomy_id.'tag', $lang_details);
                                // save the term taxonomy
                                $this->icl_term_taxonomy_cache->set('post_tag_'.$details->term_id, $details->term_taxonomy_id);
                            }
                        }
                    }
                    
                    break;
                }
            }
            $pre_load_done = true;
        }

        if (isset($this->icl_translations_cache) && $this->icl_translations_cache->has_key($el_id.$el_type)) {
            return $this->icl_translations_cache->get($el_id.$el_type);
        }

        $details = $wpdb->get_row("
            SELECT trid, language_code, source_language_code 
            FROM {$wpdb->prefix}icl_translations
            WHERE element_id='{$el_id}' AND element_type='{$el_type}'");
        if (isset($this->icl_translations_cache)) {
            $this->icl_translations_cache->set($el_id.$el_type, $details);
        }

        return $details;
    }
    
    function save_post_actions($pidd){
        global $wpdb;        
        
        $post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID = " . $pidd);
        
        if($_POST['autosave'] || $_POST['skip_sitepress_actions'] || 
            (isset($_POST['post_ID']) && $_POST['post_ID']!=$pidd) || $_POST['post_type']=='revision' || $post_type == 'revision' || 
            get_post_meta($pidd, '_wp_trash_meta_status', true))
        {
            return;
        }
                
        if($_POST['action']=='post-quickpress-publish'){
            $post_id = $pidd;            
            $language_code = $this->get_default_language();
        }elseif(isset($_GET['bulk_edit'])){
            $post_id = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID={$pidd}");
        }
        else{
            $post_id = $_POST['post_ID']?$_POST['post_ID']:$pidd; //latter case for XML-RPC publishing
            $language_code = $_POST['icl_post_language']?$_POST['icl_post_language']:$this->get_default_language(); //latter case for XML-RPC publishing
        } 
        
        if($_POST['action']=='inline-save' || isset($_GET['bulk_edit']) || isset($_GET['doing_wp_cron']) || $_GET['action']=='untrash'){
            $res = $wpdb->get_row("SELECT trid, language_code FROM {$wpdb->prefix}icl_translations WHERE element_id={$post_id} AND element_type='post'");
            $trid = $res->trid;
            $language_code = $res->language_code;
        }else{
            $trid = $_POST['icl_trid'];
            // see if we have a "translation of" setting.
            if ($_POST['icl_translation_of']) {
                $src_post_id = $_POST['icl_translation_of'];
                if ($src_post_id != 'none') {
                    $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$src_post_id} AND element_type='post'"); 
                } else {
                    $trid = null;
                } 
            }
        }
        $this->set_element_language_details($post_id, 'post', $trid, $language_code);
        
        // synchronize the page order for translations
        if($trid && $_POST['post_type']=='page' && $this->settings['sync_page_ordering']){
            $menu_order = $wpdb->escape($_POST['menu_order']);
            $translated_pages = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_id<>{$post_id}");
            if(!empty($translated_pages)){
                $wpdb->query("UPDATE {$wpdb->posts} SET menu_order={$menu_order} WHERE ID IN (".join(',', $translated_pages).")");
            }            
        }
                
        // synchronize the page parent for translations
        if($trid && $_POST['post_type']=='page' && $this->settings['sync_page_parent']){
            $translations = $this->get_element_translations($trid);
            foreach($translations as $target_lang => $target_details){
                if($target_lang != $language_code){
                    $this->fix_translated_parent($post_id, $target_details->element_id, $target_lang, $language_code);
                    
                    // restore child-parent relationships
                    $children = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent={$target_details->element_id} AND post_type='page'");
                    
                    foreach($children as $ch){
                        $ch_trid = $this->get_element_trid($ch);
                        $ch_translations = $this->get_element_translations($ch_trid);
                        if(isset($ch_translations[$language_code])){
                            $wpdb->update($wpdb->posts, array('post_parent'=>$post_id), array('ID'=>$ch_translations[$language_code]->element_id));
                        }
                    }                    
                }
            }
        }        
                
        // synchronize the page template
        if($trid && $_POST['post_type']=='page' && $this->settings['sync_page_template']){
            $translated_pages = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_id<>{$post_id}");
            if(!empty($translated_pages)){
                foreach($translated_pages as $tp){
                    if($tp != $post_id){
                        update_post_meta($tp, '_wp_page_template', $_POST['page_template']);
                    }
                }
            }            
        }        

        // synchronize comment and ping status
        if($trid && $_POST['post_type']=='post' && ($this->settings['sync_ping_status'] || $this->settings['sync_comment_status'])){
            $arr = array();
            if($this->settings['sync_comment_status']){
                $arr['comment_status'] = $_POST['comment_status'];
            }
            if($this->settings['sync_ping_status']){
                $arr['ping_status'] = $_POST['ping_status'];
            }
            if(!empty($arr)){
                $translated_posts = $wpdb->get_col("
                    SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_id<>{$post_id}");
                if(!empty($translated_posts)){
                    foreach($translated_posts as $tp){
                        if($tp != $post_id){
                            $wpdb->update($wpdb->posts, $arr, array('ID'=>$tp));
                        }
                    }
                }            
            }
        }        
        
        require_once ICL_PLUGIN_PATH . '/inc/plugins-texts-functions.php';
        if(function_exists('icl_pt_sync_pugins_texts')){
            icl_pt_sync_pugins_texts($post_id, $trid);
        }
        
                
        //sync posts stcikiness
        if($_POST['post_type']=='post' && $_POST['action']!='post-quickpress-publish' && $this->settings['sync_sticly_flag']){ //not for quick press
            remove_filter('option_sticky_posts', array($this,'option_sticky_posts')); // remove filter used to get language relevant stickies. get them all
            $sticky_posts = get_option('sticky_posts');
            // get ids of othe translations
            if($trid){
                $translations = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}'");
            }else{
                $translations = array();
            }     
            if(isset($_POST['sticky']) && $_POST['sticky'] == 'sticky'){
                $sticky_posts = array_unique(array_merge($sticky_posts, $translations));                
            }else{
                //makes sure translations are not set to sticky if this posts switched from sticky to not-sticky                
                $sticky_posts = array_diff($sticky_posts, $translations);                
            }
            update_option('sticky_posts',$sticky_posts);
        }
        
        // new categories created inline go to the correct language        
        if(isset($_POST['post_category']) && $_POST['action']!='inline-save' && $_POST['icl_post_language'])
        foreach($_POST['post_category'] as $cat){
            $ttid = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$cat} AND taxonomy='category'");
            $wpdb->update($wpdb->prefix.'icl_translations', 
                array('language_code'=>$_POST['icl_post_language']), 
                array('element_id'=>$ttid, 'element_type'=>'category'));
        }
        
        if(isset($_POST['icl_tn_note'])){
            update_post_meta($post_id, '_icl_translator_note', $_POST['icl_tn_note']);
        }
        
        require_once ICL_PLUGIN_PATH . '/inc/cache.php';        
        icl_cache_clear($_POST['post_type'].'s_per_language');
    }
    
    function fix_translated_parent($original_id, $translated_id, $lang_code, $language_code){
        global $wpdb;

        $original_parent = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID = {$original_id} AND post_type = 'page'");
        
        if (!is_null($original_parent)){
            if($original_parent === '0'){
                $parent_of_translated_id = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID = {$translated_id} AND post_type = 'page'");
                $translations = $this->get_element_translations($this->get_element_trid($parent_of_translated_id));
                if(isset($translations[$language_code])){
                    $wpdb->query("UPDATE {$wpdb->posts} SET post_parent='0' WHERE ID = ".$translated_id);
                }
            }else{
                $trid = $this->get_element_trid($original_parent);
                                
                if($trid){
                    $translations = $this->get_element_translations($trid);
                    if (isset($translations[$lang_code])){
                        $current_parent = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID = ".$translated_id);
                        if ($current_parent != $translations[$lang_code]->element_id){
                            $wpdb->query("UPDATE {$wpdb->posts} SET post_parent={$translations[$lang_code]->element_id} WHERE ID = ".$translated_id);
                        }
                    }
                }
            }
        }
    }    
    
    function sync_custom_fields($post_id, $field_names, $single = true){
        global $wpdb;
        $field_names = (array)$field_names;
        $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND element_id={$post_id}");
        if(!$trid){
            return;
        }        
        $translations = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_id <> {$post_id}");        
        foreach($field_names as $field_name){
            $field_value = get_post_meta($post_id, $field_name, $single);
            foreach($translations as $t){
                if($post_id == $t->element_id) continue;
                if(!$field_value){
                    delete_post_meta($t, $field_name);
                }else{
                    update_post_meta($t, $field_name, $field_value);
                }                
            }
        }
    } 
        
    function delete_post_actions($post_id){
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND element_id='{$post_id}' LIMIT 1");
                
        require_once ICL_PLUGIN_PATH . '/inc/cache.php';        
        $post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID={$post_id}");
        icl_cache_clear($post_type.'s_per_language');        
    }

    function trash_post_actions($post_id){
        global $wpdb;
        static $trashed_posts = array();
        
        if(isset($trashed_posts[$post_id])){
            return; // avoid infinite loop
        }
        
        $trashed_posts[$post_id] = $post_id;
        
        $trid = $this->get_element_trid($post_id);
        $translations = $this->get_element_translations($trid);
        foreach($translations as $t){
            if($t->element_id != $post_id){
                wp_trash_post($t->element_id);
            }
        }
        require_once ICL_PLUGIN_PATH . '/inc/cache.php';        
        $post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID={$post_id}");
        icl_cache_clear($post_type.'s_per_language');        
    }

    function untrashed_post_actions($post_id){
        global $wpdb;
        
        static $untrashed_posts = array();
        
        if(isset($untrashed_posts[$post_id])){
            return; // avoid infinite loop
        }
        
        $untrashed_posts[$post_id] = $post_id;
        
        $trid = $this->get_element_trid($post_id);
        $translations = $this->get_element_translations($trid);
        foreach($translations as $t){
            if($t->element_id != $post_id){
                wp_untrash_post($t->element_id);
            }
        }
        require_once ICL_PLUGIN_PATH . '/inc/cache.php';        
        $post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID={$post_id}");
        icl_cache_clear($post_type.'s_per_language');        
        
        
    }
    
    function get_element_translations($trid, $el_type='post', $skip_empty = false){        
        global $wpdb;  
        if($trid){            
            if($el_type=='post'){
                $sel_add = ', p.post_title, p.post_status';
                $join_add = " LEFT JOIN {$wpdb->posts} p ON t.element_id=p.ID";
                $groupby_add = "";
            }elseif($el_type=='category' || $el_type=='tag'){
                $sel_add = ', tm.name, tm.term_id, COUNT(tr.object_id) AS instances';
                $join_add = " LEFT JOIN {$wpdb->term_taxonomy} tt ON t.element_id=tt.term_taxonomy_id
                              LEFT JOIN {$wpdb->terms} tm ON tt.term_id = tm.term_id
                              LEFT JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id=tt.term_taxonomy_id
                              ";                
                $groupby_add = "GROUP BY tm.name";
            }                         
            $where_add = " AND t.trid='{$trid}'"; 
        }   
        $query = "
            SELECT t.language_code, t.element_id, t.source_language_code IS NULL AS original {$sel_add}
            FROM {$wpdb->prefix}icl_translations t
                 {$join_add}                 
            WHERE 1 {$where_add}
            {$groupby_add} 
        ";       
        $ret = $wpdb->get_results($query);        
        foreach($ret as $t){
            if(($el_type=='tag' || $el_type=='category') && $t->instances==0 && $skip_empty) continue;
            $translations[$t->language_code] = $t;
        }        
        return $translations;
    }
    
    function get_element_trid($element_id, $el_type='post'){
        global $wpdb;           
        return $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$element_id}' AND element_type='{$el_type}'");
    }
    
    function get_language_for_element($element_id, $el_type='post'){
        global $wpdb;   
        return $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id='{$element_id}' AND element_type='{$el_type}'");
    }

    function get_elements_without_translations($el_type, $target_lang, $source_lang){
        global $wpdb;
        
        // first get all the trids for the target languages
        // These will be the trids that we don't want.
        $sql = "SELECT
                    trid
                FROM
                    {$wpdb->prefix}icl_translations
                WHERE
                    language_code = '{$target_lang}'";
        
        $trids_for_target = $wpdb->get_col($sql);
        if (sizeof($trids_for_target) > 0) {
            $trids_for_target = join(',', $trids_for_target);
            $not_trids = 'AND trid NOT IN (' .$trids_for_target . ')';
        } else {
            $not_trids = '';
        }
        
        // Now get all the elements that are in the source language that
        // are not already translated into the target language.
        $sql = "SELECT
                    element_id
                FROM
                    {$wpdb->prefix}icl_translations
                WHERE
                        language_code = '{$source_lang}'
                    {$not_trids}
                    AND element_type= '{$el_type}'";
        
        return $wpdb->get_col($sql);        
    }

    function get_posts_without_translations($is_page, $selected_language, $default_language) {
        global $wpdb;
        $untranslated_ids = $this->get_elements_without_translations("post", $selected_language, $default_language);
        if (sizeof($untranslated_ids)) {
            // filter for "page" or "post"
            $ids = join(',',$untranslated_ids);
            $type = $is_page?"page":"post";
            $untranslated_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE ID IN ({$ids}) AND post_type = '{$type}'");
        }
        
        $untranslated = array();
        
        foreach ($untranslated_ids as $id) {
            $untranslated[$id] = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = {$id}");
        }
        
        return $untranslated;
    }
    
    function meta_box($post){
        global $wpdb;   
        $active_languages = $this->get_active_languages();
        $default_language = $this->get_default_language();
        if($post->ID){
            $res = $this->get_element_language_details($post->ID, 'post');
            $trid = $res->trid;
            if($trid){                
                $element_lang_code = $res->language_code;
            }else{
                $trid = $this->set_element_language_details($post->ID,'post',null,$default_language);
                $element_lang_code = $default_language;
            }            
        }else{
            $trid = $_GET['trid'];
            $element_lang_code = $_GET['lang'];
        }                 
        if($trid){
            $translations = $this->get_element_translations($trid, 'post');        
        }
        $selected_language = $element_lang_code?$element_lang_code:$default_language;
        
        // determine if this is for a "post" or a "page"
        $is_page = false;
        global $pagenow;
        if ($pagenow == 'page-new.php') {
            $is_page = true;
        }elseif ($post->ID){
            $is_page = 'page' == $wpdb->get_var("SELECT post_type FROM {$wpdb->prefix}posts WHERE ID={$post->ID}");
        }
        
        if(isset($_GET['lang'])){
            $selected_language = $_GET['lang'];
        }        
        $untranslated = $this->get_posts_without_translations($is_page, $selected_language, $default_language);
        
        $source_language = $_GET['source_lang'];
        
        include ICL_PLUGIN_PATH . '/menu/post-menu.php';
    }
    
    function posts_join_filter($join){
        global $wpdb, $pagenow;
        
        //exceptions
        if(isset($_POST['wp-preview']) && $_POST['wp-preview']=='dopreview' || is_preview()){
            $is_preview = true;
        }else{
            $is_preview = false;
        }
        if($pagenow=='upload.php' || $pagenow=='media-upload.php' || is_attachment() || $is_preview){
            return $join;    
        }
        
        if('all' != $this->this_lang){ 
            //$cond = " AND t.language_code='{$wpdb->escape($this->get_current_language())}'";
            $ljoin = "";
        }else{
            //$cond = '';
            $ljoin = "LEFT";
        }
        //$join .= " {$ljoin} JOIN {$wpdb->prefix}icl_translations t ON {$wpdb->posts}.ID = t.element_id 
        //            AND t.element_type='post' {$cond} JOIN {$wpdb->prefix}icl_languages l ON t.language_code=l.code AND l.active=1";        
        $join .= " {$ljoin} JOIN {$wpdb->prefix}icl_translations t ON {$wpdb->posts}.ID = t.element_id 
                    AND t.element_type='post' {$cond} JOIN {$wpdb->prefix}icl_languages l ON t.language_code=l.code AND l.active=1";                
        return $join;
    }
    
    function posts_where_filter($where){
        global $wpdb, $pagenow;

        //exceptions
        if(isset($_POST['wp-preview']) && $_POST['wp-preview']=='dopreview' || is_preview()){
            $is_preview = true;
        }else{
            $is_preview = false;
        }
        if($pagenow=='upload.php' || $pagenow=='media-upload.php' || is_attachment() || $is_preview){
            return $where;    
        }
        
        if('all' != $this->this_lang){ 
            $cond = " AND t.language_code='{$wpdb->escape($this->get_current_language())}'";
        }else{
            $cond = '';
        }
        
        $where .= $cond;
        
        return $where;
    }

    function comment_feed_join($join){
        global $wpdb, $wp_query;        
        $wp_query->query_vars['is_comment_feed'] = true;
        $join .= " JOIN {$wpdb->prefix}icl_translations t ON {$wpdb->comments}.comment_post_ID = t.element_id 
                    AND t.element_type='post' {$cond} AND t.language_code='{$wpdb->escape($this->this_lang)}'";
        return $join;
    }
    
    function language_filter(){
        require_once ICL_PLUGIN_PATH . '/inc/cache.php';        
        global $wpdb, $pagenow;
        if($pagenow=='edit.php'){
            $type = 'post';
        }else{
            $type = 'page';
        }
        $active_languages = $this->get_active_languages();
        
        $post_status = get_query_var('post_status');
        
        $langs = icl_cache_get($type.'s_per_language#' . $post_status);
        if(!$langs){

            $extra_cond = "";
            if($post_status){
                $extra_cond .= " AND post_status = '" . $post_status . "'";
            }
            
            $res = $wpdb->get_results("
                SELECT language_code, COUNT(p.ID) AS c FROM {$wpdb->prefix}icl_translations t 
                JOIN {$wpdb->posts} p ON t.element_id=p.ID
                JOIN {$wpdb->prefix}icl_languages l ON t.language_code=l.code AND l.active = 1
                WHERE p.post_type='{$type}' AND t.element_type='post' {$extra_cond}
                GROUP BY language_code            
                ");         
            foreach($res as $r){
                $langs[$r->language_code] = $r->c;
                $langs['all'] += $r->c;
            } 
            icl_cache_set($type.'s_per_language', $langs);
            
        }
        
        $active_languages[] = array('code'=>'all','display_name'=>__('All languages','sitepress'));
        foreach($active_languages as $lang){
            if($lang['code']== $this->this_lang){
                $px = '<strong>'; 
                $sx = ' <span class="count">('. intval($langs[$lang['code']]) .')<\/span><\/strong>';
            }elseif(!isset($langs[$lang['code']])){
                $px = '<span>';
                $sx = '<\/span>';
            }else{
                if($post_status){
                    $px = '<a href="?post_status='.$post_status.'&lang='.$lang['code'].'">';
                }else{
                    $px = '<a href="?lang='.$lang['code'].'">';
                }                
                $sx = '<\/a> <span class="count">('. intval($langs[$lang['code']]) .')<\/span>';
            }
            $as[] =  $px . $lang['display_name'] . $sx;
        }
        $allas = join(' | ', $as);
        if($type == 'page' && !$this->get_icl_translation_enabled()){
            $prot_link = '<span class="button" style="padding:4px;margin-top:10px;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="http://wpml.org/?page_id=3416">' . 
            __('How to translate', 'sitepress') . '<\/a>' . '<\/span>';
        }else{
            $prot_link = '';
        }
        ?>
        <script type="text/javascript">       
            jQuery(".subsubsub").append('<br /><span id="icl_subsubsub"><?php echo $allas ?><\/span><br /><?php echo $prot_link ?>');
        </script>
        <?php
    }
    
    /*
    function exclude_other_language_pages($s){
        global $wpdb;
        $excl_pages = $wpdb->get_col("
            SELECT p.ID FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->prefix}icl_translations t ON (p.ID = t.element_id OR t.element_id IS NULL)
            WHERE t.element_type='post' AND p.post_type='page' AND t.language_code <> '{$wpdb->escape($this->this_lang)}'
            ");
        return array_merge($s, $excl_pages);
    }
    */
    
    function exclude_other_language_pages2($arr){
        global $wpdb;
        $excl_pages = $wpdb->get_col("
            SELECT p.ID FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->prefix}icl_translations t ON (p.ID = t.element_id OR t.element_id IS NULL)
            WHERE t.element_type='post' AND p.post_type='page' AND t.language_code <> '{$wpdb->escape($this->this_lang)}'
            ");
        foreach($arr as $page){
            if(!in_array($page->ID,$excl_pages)){
                $filtered_pages[] = $page;
            }
        }        
        return $filtered_pages;
    }
    
    function wp_dropdown_pages($output){        
        global $wpdb;
        if(isset($_POST['lang_switch'])){
            $post_id = $wpdb->escape($_POST['lang_switch']);            
            $lang = $wpdb->escape($_GET['lang']);
            $parent = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID={$post_id}");
            $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$parent}' AND element_type='post'");
            $translated_parent_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_type='post' AND language_code='{$lang}'");
            if($translated_parent_id){
                $output = str_replace('selected="selected"','',$output);
                $output = str_replace('value="'.$translated_parent_id.'"','value="'.$translated_parent_id.'" selected="selected"',$output);
            }
        }elseif(isset($_GET['lang']) && isset($_GET['trid'])){
            $lang = $wpdb->escape($_GET['lang']);
            $trid = $wpdb->escape($_GET['trid']);
            $elements_id = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_type='post'");
            foreach($elements_id as $element_id){
                $parent = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID={$element_id}");
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$parent}' AND element_type='post'");
                $translated_parent_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_type='post' AND language_code='{$lang}'");
                if($translated_parent_id) break;
            }
            if($translated_parent_id){
                $output = str_replace('selected="selected"','',$output);
                $output = str_replace('value="'.$translated_parent_id.'"','value="'.$translated_parent_id.'" selected="selected"',$output);
            }            
        }
        if(!$output){
            $output = '<select id="parent_id"><option value="">' . __('Main Page (no parent)','sitepress') . '</option></select>';
        }
        return $output;
    }
            
    function edit_term_form($term){   
        global $wpdb, $pagenow;
        $element_id = $term->term_taxonomy_id;    
        $element_type = $pagenow=='categories.php'?'category':'tag';
        
        $default_language = $this->get_default_language();
        
        if($element_id){
            $res = $wpdb->get_row("SELECT trid, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id='{$element_id}' AND element_type='{$element_type}'");
            $trid = $res->trid;
            if($trid){                
                $element_lang_code = $res->language_code;
            }else{
                $trid = $this->set_element_language_details($element_id, $element_type, null, $default_language);
                $element_lang_code = $default_language;
            }                            
        }else{
            $trid = $_GET['trid'];
            $element_lang_code = $_GET['lang'];
        }
        if($trid){
            $translations = $this->get_element_translations($trid, $element_type);        
        }                                   
        $active_languages = $this->get_active_languages();
        $selected_language = $element_lang_code?$element_lang_code:$default_language;
        
        $source_language = $_GET['source_lang'];
        
        $untranslated_ids = $this->get_elements_without_translations($element_type, $selected_language, $default_language);
        
        include ICL_PLUGIN_PATH . '/menu/'.$element_type.'-menu.php';        
    }

    function add_language_selector_to_page($active_languages, $selected_language, $translations, $element_id, $type) {
        ?>
        <div id="icl_<?php echo $type ?>_menu" style="display:none">
        
        <div id="dashboard-widgets" class="metabox-holder">
        <div class="postbox-container" style="width: 99%;line-height:normal;">
        
        <div id="icl_<?php echo $type ?>_lang" class="postbox" style="line-height:normal;">
            <h3 class="hndle">
                <span><?php echo __('Language', 'sitepress')?></span>
            </h3>                    
            <div class="inside" style="padding: 10px;">
        
        <select name="icl_<?php echo $type ?>_language">
        
            <?php
                foreach($active_languages as $lang){
                    if ($lang['code'] == $selected_language) {
                        ?>
                        <option value="<?php echo $selected_language ?>" selected="selected"><?php echo $lang['display_name'] ?></option>
                        <?php
                    }
                }
            ?>
            
            <?php foreach($active_languages as $lang):?>   
                <?php if($lang['code'] == $selected_language || (isset($translations[$lang['code']]->element_id) && $translations[$lang['code']]->element_id != $element_id)) continue ?>     
                    <option value="<?php echo $lang['code'] ?>"<?php if($selected_language==$lang['code']): ?> selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
            <?php endforeach; ?>
        </select>
        <?php
        }
        
    function get_category_name($id) {
        global $wpdb;
        $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id = {$id}");
        if ($term_id) {
            return $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = {$term_id}");
        } else {
            return null;
        }
    }
        
    function add_translation_of_selector_to_page($trid,
                                                 $selected_language,
                                                 $default_language,
                                                 $source_language,
                                                 $untranslated_ids,
                                                 $element_id,
                                                 $type) {
        global $wpdb;
        ?>
        <input type="hidden" name="icl_trid" value="<?php echo $trid ?>" />

        <?php if($selected_language != $default_language): ?>
            <br /><br />
            <?php echo __('This is a translation of', 'sitepress') ?><br />
            <select name="icl_translation_of" id="icl_translation_of"<?php if($_GET['action'] != 'edit' && $trid) echo " disabled"?>>
                <?php if($source_language == null || $source_language == $default_language): ?>
                    <?php if($trid): ?>
                        <option value="none"><?php echo __('--None--', 'sitepress') ?></option>
                        <?php
                            //get source
                            $src_language_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND language_code='{$default_language}'");
                            if(!$src_language_id) {
                                // select the first id found for this trid
                                $src_language_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$trid}");
                            }
                            if($src_language_id && $src_language_id != $element_id) {
                                $src_language_title = $this->get_category_name($src_language_id);
                            }
                        ?>
                        <?php if($src_language_title): ?>
                            <option value="<?php echo $src_language_id ?>" selected="selected"><?php echo $src_language_title ?></option>
                        <?php endif; ?>
                    <?php else: ?>
                        <option value="none" selected="selected"><?php echo __('--None--', 'sitepress') ?></option>
                    <?php endif; ?>
                    <?php foreach($untranslated_ids as $translation_of_id):?>
                        <?php if ($translation_of_id != $src_language_id): ?>
                            <?php $title = $this->get_category_name($translation_of_id)?>
                            <?php if ($title): ?>
                                <option value="<?php echo $translation_of_id ?>"><?php echo $title ?></option>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php if($trid): ?>
                        <?php
                            // add the source language
                            $src_language_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND language_code='{$source_language}'");
                            if($src_language_id) {
                                $src_language_title = $this->get_category_name($src_language_id);
                            }
                        ?>
                        <?php if($src_language_title): ?>
                            <option value="<?php echo $src_language_id ?>" selected="selected"><?php echo $src_language_title ?></option>
                        <?php endif; ?>
                    <?php else: ?>
                        <option value="none" selected="selected"><?php echo __('--None--', 'sitepress') ?></option>
                    <?php endif; ?>
                <?php endif; ?>
            </select>
        
        <?php endif; ?>
        
        
        <?php
    }
    
    function add_translate_options($trid,
                                   $active_languages,
                                   $selected_language,
                                   $translations,
                                   $type) {
        global $wpdb;
        ?>
        
        <?php if($trid && $_GET['action'] == 'edit'): ?>
        
            <div id="icl_translate_options">
        
            <?php
                // count number of translated and un-translated pages.
                $translations_found = 0;
                $untranslated_found = 0;
                foreach($active_languages as $lang) {
                    if($selected_language==$lang['code']) continue;
                    if(isset($translations[$lang['code']]->element_id)) {
                        $translations_found += 1;
                    } else {
                        $untranslated_found += 1;
                    }
                }
            ?>
            
            <?php if($untranslated_found > 0): ?>    
                <p style="clear:both;"><b>Translate</b>
                <table cellspacing="1">
                <?php foreach($active_languages as $lang): if($selected_language==$lang['code']) continue; ?>
                <tr>
                    <?php if(!isset($translations[$lang['code']]->element_id)):?>
                        <td style="padding: 0px;line-height:normal;"><?php echo $lang['display_name'] ?></td>
                        <?php
                            if ($type == 'tag') {
                                $add_link = "edit-tags.php?trid=" . $trid . "&amp;lang=" . $lang['code'] . "&amp;source_lang=" . $selected_language;
                            } else {
                                $add_link = "categories.php?trid=" . $trid . "&amp;lang=" . $lang['code'] . "&amp;source_lang=" . $selected_language;
                            }
                        ?>
                        <td style="padding: 0px;line-height:normal;"><a href="<?php echo $add_link ?>"><?php echo __('add','sitepress') ?></a></td>
                    <?php endif; ?>        
                </tr>
                <?php endforeach; ?>
                </table>
                </p>
            <?php endif; ?>
        
            <?php if($translations_found > 0): ?>    
            <p style="clear:both;margin:5px 0 5px 0">
                <b><?php _e('Translations', 'sitepress') ?></b> 
                (<a class="icl_toggle_show_translations" href="#" <?php if(!$this->settings['show_translations_flag']):?>style="display:none;"<?php endif;?>><?php _e('hide','sitepress')?></a><a class="icl_toggle_show_translations" href="#" <?php if($this->settings['show_translations_flag']):?>style="display:none;"<?php endif;?>><?php _e('show','sitepress')?></a>)                
                <table cellspacing="1" width="100%" id="icl_translations_table" style="<?php if(!$this->settings['show_translations_flag']):?>display:none;<?php endif;?>margin-left:0;">
                
                <?php foreach($active_languages as $lang): if($selected_language==$lang['code']) continue; ?>
                <tr>
                    <?php if(isset($translations[$lang['code']]->element_id)):?>
                        <td style="line-height:normal;"><?php echo $lang['display_name'] ?></td>
                        <?php
                            if ($type == 'tag') {
                                $edit_link = "edit-tags.php?action=edit&amp;tag_ID=" . $translations[$lang['code']]->term_id . "&amp;lang=" . $lang['code'];
                            } else {
                                $edit_link = "categories.php?action=edit&amp;cat_ID=" . $translations[$lang['code']]->term_id . "&amp;lang=" . $lang['code'];
                            }
                        ?>
                        <td align="right" width="30%" style="line-height:normal;"><?php echo isset($translations[$lang['code']]->name)?'<a href="'.$edit_link.'" title="'.__('Edit','sitepress').'">'.$translations[$lang['code']]->name.'</a>':__('n/a','sitepress') ?></td>
                                
                    <?php endif; ?>        
                </tr>
                <?php endforeach; ?>
                </table>
                
                
                
            <?php endif; ?>
            
            <br clear="all" style="line-height:1px;" />
            
            </div>
        <?php endif; ?>
        
        
        <?php
    }
    
    function create_term($cat_id, $tt_id){        
        global $wpdb;
        
        // case of ajax inline category creation
        if(isset($_POST['_ajax_nonce']) && $_POST['action']=='add-category'){
            $referer = $_SERVER['HTTP_REFERER'];
            $url_pieces = parse_url($referer);
            parse_str($url_pieces['query'], $qvars);
            if($qvars['post']>0){
                $lang_details = $this->get_element_language_details($qvars['post'],'post');
                $term_lang = $lang_details->language_code;
            }else{
                $term_lang = $this->get_default_language();
            }
        }

        $el_type = $wpdb->get_var("SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id={$tt_id}");
        if($el_type == 'post_tag') $el_type = 'tag'; 
        
        // case of adding a tag via post save
        if($_POST['action']=='editpost'){
            $term_lang = $_POST['icl_post_language'];        
        }elseif($_POST['action']=='post-quickpress-publish'){
            $term_lang = $this->get_default_language();
        }elseif($_POST['action']=='inline-save-tax'){
            $lang_details = $this->get_element_language_details($tt_id, $el_type);
            $term_lang = $lang_details->language_code;
        }elseif($_POST['action']=='inline-save'){
            $lang_details = $this->get_element_language_details($_POST['post_ID'], 'post');
            $term_lang = $lang_details->language_code;
        }

        // has trid only when it's a translation of another tag             
        $trid = isset($_POST['icl_trid']) && (isset($_POST['icl_tag_language']) || isset($_POST['icl_category_language']))?$_POST['icl_trid']:null;        
        // see if we have a "translation of" setting.
        if ($_POST['icl_translation_of']) {
            $src_term_id = $_POST['icl_translation_of'];
            if ($src_term_id != 'none') {
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$src_term_id} AND element_type='{$el_type}'"); 
            } else {
                $trid = null;
            }
        }
        
        if(!isset($term_lang)){
            $term_lang = $_POST['icl_'.$el_type.'_language'];        
        }        
        if(isset($_POST['action']) && $_POST['action']=='inline-save-tax'){
            $trid = $this->get_element_trid($tt_id,$el_type);
        }
                
        $this->set_element_language_details($tt_id, $el_type, $trid, $term_lang);                
    }
    
    function get_language_for_term($term_id, $el_type) {
        global $wpdb;
        $term_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy WHERE term_id = {$term_id}");
        if ($term_id) {
            return $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = {$term_id} AND element_type = '{$el_type}'");
        } else {
            return $this->get_default_language();
        }
        
    }
    
    function pre_term_name($value, $taxonomy){
        //allow adding terms with the same name in different languages
        global $wpdb;
        //check if term exists
        $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->terms} WHERE name='".$wpdb->escape($value)."'");
        // translate to WPML notation
        if($taxonomy=='post_tag'){
            $taxonomy = 'tag';
        }
        if(!empty($term_id)){
            if(isset($_POST['icl_'.$taxonomy.'_language'])) {
                // see if the term_id is for a different language
                $this_lang = $_POST['icl_'.$taxonomy.'_language'];
                if ($this_lang != $this->get_language_for_term($term_id, $taxonomy)) {
                    if ($this_lang != $this->get_default_language()){
                        $value .= ' @'.$_POST['icl_'.$taxonomy.'_language'];
                    }
                }
            }
        }        
        return $value;
    }
    
    function pre_save_category(){
        // allow adding categories with the same name in different languages
        global $wpdb;
        if(isset($_POST['action']) && $_POST['action']=='add-cat'){
            if(category_exists($_POST['cat_name']) && isset($_POST['icl_category_language']) && $_POST['icl_category_language'] != $this->get_default_language()){
                $_POST['cat_name'] .= ' @'.$_POST['icl_category_language'];    
            }
        }
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
        $active_languages = $this->get_active_languages();
        
        $res = $wpdb->get_results("
            SELECT language_code, COUNT(tm.term_id) AS c FROM {$wpdb->prefix}icl_translations t 
            JOIN {$wpdb->term_taxonomy} tt ON t.element_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} tm ON tt.term_id = tm.term_id
            JOIN {$wpdb->prefix}icl_languages l ON t.language_code = l.code
            WHERE t.element_type='{$element_type}' AND tt.taxonomy='{$taxonomy}' AND l.active=1
            GROUP BY language_code            
            ");                 
        foreach($res as $r){
            $langs[$r->language_code] = $r->c;
            $langs['all'] += $r->c;
        } 
        $active_languages[] = array('code'=>'all','display_name'=>__('All languages','sitepress'));
        foreach($active_languages as $lang){
            if($lang['code']== $this->this_lang){
                $px = '<strong>'; 
                $sx = ' ('. intval($langs[$lang['code']]) .')<\/strong>';
            }elseif(!isset($langs[$lang['code']])){
                $px = '<span>';
                $sx = '<\/span>';
            }else{
                $px = '<a href="?lang='.$lang['code'].'">';
                $sx = '<\/a> ('. $langs[$lang['code']] .')';
            }
            $as[] =  $px . $lang['display_name'] . $sx;
        }
        $allas = join(' | ', $as);
        ?>
        <script type="text/javascript">
            jQuery('table.widefat').before('<span id="icl_subsubsub"><?php echo $allas ?><\/span>');
        </script>
        <?php
    }    
    
    function exclude_other_terms($exclusions, $args){                
        global $wpdb, $pagenow;        
        if($args['type']=='category' || in_array($pagenow, array('post-new.php','post.php'))){
            $element_type = $taxonomy = 'category';
        }else{
            $element_type = 'tag';
            $taxonomy = 'post_tag';
        }
        if($_GET['lang']=='all'){
            return $exclusions;
        }
        if(isset($_GET['cat_ID']) && $_GET['cat_ID']){
            $element_lang_details = $this->get_element_language_details($wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id='{$_GET['cat_ID']}' AND taxonomy='category'"),'category');            
            $this_lang = $element_lang_details->language_code;
        }elseif($this->this_lang != $this->get_default_language()){
            $this_lang = $this->get_current_language();
        }elseif(isset($_GET['post'])){
            $element_lang_details = $this->get_element_language_details($_GET['post'],'post');
            $this_lang = $element_lang_details->language_code;
        }else{
            $this_lang = $this->get_default_language();
        }        
        $exclude =  $wpdb->get_col("
            SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt
            LEFT JOIN {$wpdb->terms} tm ON tt.term_id = tm.term_id 
            LEFT JOIN {$wpdb->prefix}icl_translations t ON (tt.term_taxonomy_id = t.element_id OR t.element_id IS NULL)
            WHERE tt.taxonomy='{$taxonomy}' AND t.element_type='{$element_type}' AND t.language_code <> '{$this_lang}'
            ");        
        $exclude[] = 0;         
        $exclusions .= ' AND tt.term_taxonomy_id NOT IN ('.join(',',$exclude).')';
        return $exclusions;
    }
  
    function set_wp_query(){
        global $wp_query;
        $this->wp_query = $wp_query;
    }
    
    // converts WP generated url to language specific based on plugin settings
    function convert_url($url, $code=null){
        if(is_null($code)){
            $code = $this->this_lang;
        }
        
        if($code && $code != $this->get_default_language()){
            $abshome = preg_replace('@\?lang=' . $code . '@i','',get_option('home'));
            switch($this->settings['language_negotiation_type']){
                case '1':                 
                    if($abshome==$url) $url .= '/';
                    $url = str_replace($abshome, $abshome . '/' . $code, $url);
                    
                    break;
                case '2': 
                    $url = str_replace($abshome, $this->settings['language_domains'][$code], $url);
                    break;                
                case '3':
                default:
                    if(false===strpos($url,'?')){
                        $url_glue = '?';
                    }else{
                        if(isset($_POST['comment'])){ // will be used for a redirect
                            $url_glue = '&';
                        }else{
                            $url_glue = '&amp;';
                        }                                                
                    }                    
                    $url .= $url_glue . 'lang=' . $code;
            }
        }
      return $url;  
    } 
        
    function language_url($code=null){
        if(is_null($code)) $code = $this->this_lang;
        $abshome = get_option('home');
        if($this->settings['language_negotiation_type'] == 1 || $this->settings['language_negotiation_type'] == 2){
            $url = trailingslashit($this->convert_url($abshome, $code));  
        }else{
            $url = $this->convert_url($abshome, $code);
        }        
        return $url;
    }
    
    function permalink_filter($p, $pid){ 
        global $wpdb;
        if(is_object($pid)){                        
            $pid = $pid->ID;
        }
        $element_lang_details = $this->get_element_language_details($pid,'post');        
        if($element_lang_details->language_code && $this->get_default_language() != $element_lang_details->language_code){
            $p = $this->convert_url($p, $element_lang_details->language_code);
        }elseif(isset($_POST['action']) && $_POST['action']=='sample-permalink'){ // check whether this is an autosaved draft 
            $exp = explode('?', $_SERVER["HTTP_REFERER"]);
            if(isset($exp[1])) parse_str($exp[1], $args);        
            if(isset($args['lang']) && $this->get_default_language() != $args['lang']){
                $p = $this->convert_url($p, $args['lang']);
            }
        }
        if(is_feed()){
            $p = str_replace("&lang=", "&#038;lang=", $p);
        }
        return $p;
    }    
    
    function category_permalink_filter($p, $cat_id){
        global $wpdb;
        if (isset($this->icl_term_taxonomy_cache)) {
            $term_cat_id = $this->icl_term_taxonomy_cache->get('category_'.$cat_id);
        } else {
            $term_cat_id = null;
        }
        if (!$term_cat_id) {
            $term_cat_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$cat_id} AND taxonomy='category'");
            if (isset($this->icl_term_taxonomy_cache)) {
                $this->icl_term_taxonomy_cache->set('category_'.$cat_id, $term_cat_id);
            }
        }
        $cat_id = $term_cat_id;
        
        $element_lang_details = $this->get_element_language_details($cat_id,'category');
        if($this->get_default_language() != $element_lang_details->language_code){
            $p = $this->convert_url($p, $element_lang_details->language_code);
        }
        return $p;
    }  
          
    function tag_permalink_filter($p, $tag){
        global $wpdb;        
        if(is_object($tag)){                        
            $tag_id = $tag->term_taxonomy_id;
        }else{
            if (isset($this->icl_term_taxonomy_cache)) {
                $tag_id = $this->icl_term_taxonomy_cache->get('post_tag_'.$tag);
            } else {
                $tag_id = null;
            }
            if (!$tag_id) {
                $tag_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$tag} AND taxonomy='post_tag'");
                if (isset($this->icl_term_taxonomy_cache)) {
                    $this->icl_term_taxonomy_cache->set('post_tag_'.$tag, $tag_id);
                }
            }
        }        
        $element_lang_details = $this->get_element_language_details($tag_id,'tag');
        if($this->get_default_language() != $element_lang_details->language_code){
            $p = $this->convert_url($p, $element_lang_details->language_code);
        }
        return $p;
    }            
    
    function get_comment_link_filter($link){
        // decode html characters since they are already encoded in the template for some reason
        $link = html_entity_decode($link);
        return $link;
    }
     
    function get_ls_languages($template_args=array()){
            global $wpdb, $post, $cat, $tag_id, $w_this_lang;
            
            if(is_null($this->wp_query)) $this->set_wp_query();
             
             // use original wp_query for this
             // backup current $wp_query
             global $wp_query;
             $_wp_query_back = clone $wp_query;
             $wp_query = $this->wp_query;
             
             
            $w_active_languages = $this->get_active_languages();                        
            $this_lang = $this->this_lang;
            if($this_lang=='all'){
                $w_this_lang = array(
                    'code'=>'all',
                    'english_name' => 'All languages',
                    'display_name' => __('All languages', 'sitepress')
                );                
            }else{
                $w_this_lang = $this->get_language_details($this_lang);
            }
                       
            if(isset($template_args['skip_missing'])){
                //override default setting
                $icl_lso_link_empty = !$template_args['skip_missing'];
            }else{
                $icl_lso_link_empty = $this->settings['icl_lso_link_empty'];
            }                      
                       
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            if(preg_match('#MSIE ([0-9]+)\.[0-9]#',$user_agent,$matches)){
                $ie_ver = $matches[1];
            }    
            if(is_singular() && !empty($wp_query->posts)){
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$this->wp_query->post->ID}' AND element_type='post'");                     $translations = $this->get_element_translations($trid,'post');
            }elseif(is_category() && !empty($wp_query->posts)){                
                $cat_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$cat} AND taxonomy='category'");
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$cat_id}' AND element_type='category'");                
                $skip_empty = true;
                $translations = $this->get_element_translations($trid,'category', $skip_empty);                
            }elseif(is_tag() && !empty($wp_query->posts)){                
                $tag_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$tag_id} AND taxonomy='post_tag'");
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$tag_id}' AND element_type='tag'");                
                $skip_empty = true;
                $translations = $this->get_element_translations($trid,'tag', $skip_empty);                
            }elseif(is_archive() && !empty($wp_query->posts)){                      
                $translations = array();
            }elseif( 'page' == get_option('show_on_front') && ($this->wp_query->queried_object_id == get_option('page_on_front') || $this->wp_query->queried_object_id == get_option('page_for_posts')) ){
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$this->wp_query->queried_object_id}' AND element_type='post'");                
                $translations = $this->get_element_translations($trid,'post');                                
            }else{
                $wp_query->is_singular = false;
                $wp_query->is_archive = false;
                $wp_query->is_category = false;
                $wp_query->is_404 = true;
            }
                                                                                                                                                                 
            foreach($w_active_languages as $k=>$lang){                
                $skip_lang = false;
                if(is_singular() || ($this->wp_query->queried_object_id && $this->wp_query->queried_object_id == get_option('page_for_posts'))){                 
                    $this_lang_tmp = $this->this_lang; 
                    $this->this_lang = $lang['code']; 
                    $lang_page_on_front = get_option('page_on_front');                     
                    $lang_page_for_posts = get_option('page_for_posts');                                         
                    $this->this_lang = $this_lang_tmp; 
                    if ( 'page' == get_option('show_on_front') && $translations[$lang['code']]->element_id == $lang_page_on_front ){
                        $lang['translated_url'] = $this->language_url($lang['code']); 
                    }elseif('page' == get_option('show_on_front') && $translations[$lang['code']]->element_id && $translations[$lang['code']]->element_id == $lang_page_for_posts){
                        if($lang_page_for_posts){
                            $lang['translated_url'] = get_permalink($lang_page_for_posts);
                        }else{
                            $lang['translated_url'] = $this->language_url($lang['code']);
                        } 
                    }else{                        
                        if(isset($translations[$lang['code']]->post_title)){
                            $lang['translated_url'] = get_permalink($translations[$lang['code']]->element_id);
                        }else{
                            if($icl_lso_link_empty){
                                $lang['translated_url'] = $this->language_url($lang['code']);
                            }else{
                                $skip_lang = true;
                            }                        
                        }
                    }
                }elseif(is_category()){                      
                    if(isset($translations[$lang['code']])){
                        if($this->settings['auto_adjust_ids']){
                            global $icl_adjust_id_url_filter_off;  // force  the category_link_adjust_id to not modify this
                            $icl_adjust_id_url_filter_off = true;
                        }
                        $lang['translated_url'] = get_category_link($translations[$lang['code']]->term_id);
                        if($this->settings['auto_adjust_ids']){
                            $icl_adjust_id_url_filter_off = false; // restore default bahavior
                        }                        
                    }else{  
                        if($icl_lso_link_empty){
                            $lang['translated_url'] = $this->language_url($lang['code']);
                        }else{
                            $skip_lang = true;
                        }                        
                    }
                }elseif(is_tag()){                                     
                    if(isset($translations[$lang['code']])){
                        if($this->settings['auto_adjust_ids']){
                            global $icl_adjust_id_url_filter_off;  // force  the category_link_adjust_id to not modify this
                            $icl_adjust_id_url_filter_off = true;
                        }
                        $lang['translated_url'] = get_tag_link($translations[$lang['code']]->term_id);
                        if($this->settings['auto_adjust_ids']){
                            $icl_adjust_id_url_filter_off = false; // restore default bahavior
                        }                                                
                    }else{
                        if($icl_lso_link_empty){
                            $lang['translated_url'] = $this->language_url($lang['code']);
                        }else{
                            $skip_lang = true;
                        }                        
                    }                    
                }elseif(is_archive() && !is_tag()){
                    global $icl_archive_url_filter_off;
                    $icl_archive_url_filter_off = true;
                    if($this->wp_query->is_year){
                        if(isset($this->wp_query->query_vars['m']) && !$this->wp_query->query_vars['year'] ){
                            $this->wp_query->query_vars['year'] = substr($this->wp_query->query_vars['m'], 0, 4);
                        }                        
                        $lang['translated_url'] = $this->archive_url(get_year_link( $this->wp_query->query_vars['year'] ), $lang['code']);
                    }elseif($this->wp_query->is_month){
                        if(isset($this->wp_query->query_vars['m']) && !$this->wp_query->query_vars['year'] ){
                            $this->wp_query->query_vars['year'] = substr($this->wp_query->query_vars['m'], 0, 4);
                            $this->wp_query->query_vars['monthnum'] = substr($this->wp_query->query_vars['m'], 4, 2);
                        }
                        $lang['translated_url'] = $this->archive_url(get_month_link( $this->wp_query->query_vars['year'], $this->wp_query->query_vars['monthnum'] ), $lang['code']);
                    }elseif($this->wp_query->is_day){
                        if(isset($this->wp_query->query_vars['m']) && !$this->wp_query->query_vars['year'] ){
                            $this->wp_query->query_vars['year'] = substr($this->wp_query->query_vars['m'], 0, 4);
                            $this->wp_query->query_vars['monthnum'] = substr($this->wp_query->query_vars['m'], 4, 2);
                            $this->wp_query->query_vars['day'] = substr($this->wp_query->query_vars['m'], 6, 2);
                            gmdate('Y', current_time('timestamp')); //force wp_timezone_override_offset to be called
                        }
                        $lang['translated_url'] = $this->archive_url(get_day_link( $this->wp_query->query_vars['year'], $this->wp_query->query_vars['monthnum'], $this->wp_query->query_vars['day'] ), $lang['code']);
                    }
                    $icl_archive_url_filter_off = false;
                }elseif(is_search()){                    
                    $url_glue = strpos($this->language_url($lang['code']),'?')===false ? '?' : '&';
                    $lang['translated_url'] = $this->language_url($lang['code']) . $url_glue . 's=' . htmlspecialchars($_GET['s']);                                        
                }else{                           
                    global $icl_language_switcher_preview;                   
                    if($icl_lso_link_empty || is_home() || is_404()  
                        || ('page' == get_option('show_on_front') && ($this->wp_query->queried_object_id == get_option('page_on_front') 
                        || $this->wp_query->queried_object_id == get_option('page_for_posts')))
                        || $icl_language_switcher_preview){
                        $lang['translated_url'] = $this->language_url($lang['code']);
                        $skip_lang = false;                        
                    }else{
                        $skip_lang = true; 
                        unset($w_active_languages[$k]);                       
                    }                    
                }
                if(!$skip_lang){
                    $w_active_languages[$k] = $lang;                    
                }else{
                    unset($w_active_languages[$k]); 
                }                        
            }              
                   
            foreach($w_active_languages as $k=>$v){
                $lang_code = $w_active_languages[$k]['language_code'] = $w_active_languages[$k]['code'];
                unset($w_active_languages[$k]['code']);

                $native_name = $this->get_display_language_name($lang_code, $lang_code);        
                if(!$native_name) $native_name = $w_active_languages[$k]['english_name'];    
                $w_active_languages[$k]['native_name'] = $native_name;                
                unset($w_active_languages[$k]['english_name']);
                

                $translated_name = $this->get_display_language_name($lang_code, $this->get_current_language());        
                if(!$translated_name) $translated_name = $w_active_languages[$k]['english_name'];    
                $w_active_languages[$k]['translated_name'] = $translated_name;                
                unset($w_active_languages[$k]['display_name']);
               
                $w_active_languages[$k]['url'] = $w_active_languages[$k]['translated_url']; 
                unset($w_active_languages[$k]['translated_url']);
                                
                $flag = $this->get_flag($lang_code);
                    
                if($flag->from_template){
                    $flag_url = get_bloginfo('template_directory') . '/images/flags/'.$flag->flag;
                }else{
                    $flag_url = ICL_PLUGIN_URL . '/res/flags/'.$flag->flag;
                }                    
                $w_active_languages[$k]['country_flag_url'] = $flag_url;                
                
                $w_active_languages[$k]['active'] = $this->get_current_language()==$lang_code?'1':0;;
            }     
            
            
            // restore current $wp_query
            $wp_query = clone $_wp_query_back;
            unset($_wp_query_back);             
              
            // sort languages according to parameters  
            if(isset($template_args['orderby'])){
                if(isset($template_args['order'])){
                    $order = $template_args['order'];
                }else{
                    $order = 'asc';
                }
                $comp = $order == 'asc' ? '>' : '<';
                switch($template_args['orderby']){
                    case 'id':                   
                        uasort($w_active_languages, create_function('$a,$b','return $a[\'id\'] '.$comp.' $b[\'id\'];')); 
                        break;
                    case 'code':    
                        ksort($w_active_languages);
                        if($order == 'desc'){
                            $w_active_languages = array_reverse($w_active_languages);
                        }
                        break;
                    case 'name':                                        
                    default:
                        uasort($w_active_languages, create_function('$a,$b','return $a[\'translated_name\'] '.$comp.' $b[\'translated_name\'];')); 
                }                
            }                    
            
                                
            return $w_active_languages;
            
    }

    function get_display_language_name($lang_code, $display_code) {
        global $wpdb;
        if (isset($this->icl_language_name_cache)) {
            $translated_name = $this->icl_language_name_cache->get($lang_code.$display_code);
        } else {
            $translated_name = null;
        }
        if (!$translated_name) {
            $translated_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$lang_code}' AND display_language_code='{$display_code}'");
            if (isset($this->icl_language_name_cache)) {
                $this->icl_language_name_cache->set($lang_code.$display_code, $translated_name);
            }
        }
        return $translated_name;
    }
    
    function get_flag($lang_code){
        global $wpdb;
        
        if (isset($this->icl_flag_cache)) {
            $flag = $this->icl_flag_cache->get($lang_code);
        } else {
            $flag = null;
        }
        if (!$flag) {
            $flag = $wpdb->get_row("SELECT flag, from_template FROM {$wpdb->prefix}icl_flags WHERE lang_code='{$lang_code}'");
            if (isset($this->icl_flag_cache)) {
                $this->icl_flag_cache->set($lang_code, $flag);
            }
        }
        
        return $flag;
    }
                      
    function language_selector(){
        $active_languages = $this->get_ls_languages();
        foreach($active_languages as $k=>$al){
            if($al['active']==1){
                $main_language = $al;
                unset($active_languages[$k]);
                break;
            }
        }
        include ICL_PLUGIN_PATH . '/menu/language-selector.php';            
    }
    
    function have_icl_translator($source, $target){
        // returns true if we have ICL translators for the language pair
        if (isset($this->settings['icl_lang_status'])){
            foreach($this->settings['icl_lang_status'] as $lang) {
                if ($lang['from'] == $source && $lang['to'] == $target) {
                    return $lang['have_translators'];
                }
            }
            
        }
        
        return false;
    }
    
    function get_default_categories(){
        $default_categories_all = $this->settings['default_categories'];
        
        foreach($this->active_languages as $l) $alcodes[] = $l['code'];
        foreach($default_categories_all as $c=>$v){
            if(in_array($c, $alcodes)){
                $default_categories[$c] = $v;
            }
        }
        
        return $default_categories;            
    }
    
    function set_default_categories($def_cat){
        $this->settings['default_categories'] = $def_cat;
        $this->save_settings();
    }
    
    function pre_option_default_category($setting){
        global $wpdb;
        if(isset($_POST['icl_post_language']) && $_POST['icl_post_language'] || (isset($_GET['lang']) && $_GET['lang']!='all')){
            $lang = isset($_POST['icl_post_language'])  && $_POST['icl_post_language']?$_POST['icl_post_language']:$_GET['lang'];
            $ttid = intval($this->settings['default_categories'][$lang]);
            return $tid = $wpdb->get_var("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id={$ttid} AND taxonomy='category'");
        }
        return false;
    }
    
    function the_category_name_filter($name){                    
        if(is_array($name)){
            foreach($name as $k=>$v){
                $name[$k] = $this->the_category_name_filter($v);
            }
            return $name;
        }        
        if(false === strpos($name, '@')) return $name;        
        if(false !== strpos($name, '<a')){                                                          
            $int = preg_match_all('|<a([^>]+)>([^<]+)</a>|i',$name,$matches);
            if($int && count($matches[0]) > 1){
                $originals = $filtered = array();
                foreach($matches[0] as $m){
                    $originals[] = $m;
                    $filtered[] = $this->the_category_name_filter($m);
                }
                $name = str_replace($originals, $filtered, $name);
            }else{            
                $name_sh = strip_tags($name);
                $exp = explode('@', $name_sh);
                $name = str_replace($name_sh, trim($exp[0]),$name);            
            }
        }else{
            $name = preg_replace('#(.*) @(.*)#i','$1',$name);
        }
        return $name;
    }
    
    function get_terms_filter($terms){
        foreach($terms as $k=>$v){
            if(isset($terms[$k]->name)) $terms[$k]->name = $this->the_category_name_filter($terms[$k]->name);
        }
        return $terms;
    }
    
    function get_term_adjust_id($term){
        
        global $icl_adjust_id_url_filter_off;
        if($icl_adjust_id_url_filter_off) return $term; // special cases when we need the categiry in a different language
            
        $translated_id = icl_object_id($term->term_taxonomy_id, $term->taxonomy, true);
        remove_filter('get_term', array($this,'get_term_adjust_id'), 1);
        $t_term = get_term($translated_id, $term->taxonomy); 
        if(!is_wp_error($t_term)){
            $term = $t_term;
        }        
        add_filter('get_term', array($this,'get_term_adjust_id'), 1, 1);
        return $term;
    }
    
    
    function category_link_adjust_id($catlink, $cat_id){
        global $icl_adjust_id_url_filter_off;
        if($icl_adjust_id_url_filter_off) return $catlink; // special cases when we need the categiry in a different language
         
        $translated_id = icl_object_id($cat_id, 'category', true);
        if($translated_id != $cat_id){
            remove_filter('category_link', array($this,'category_link_adjust_id'), 1);
            $catlink = get_category_link($translated_id, 'category'); 
            add_filter('category_link', array($this,'category_link_adjust_id'), 1, 2);
        }
        return $catlink;        
    } 
    
    // adiacent posts links
    function get_adiacent_post_join($join){
        global $wpdb;
        $join .= " JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type='post'";        
        return $join;
    }    
    
    function get_adiacent_post_where($where){
        global $wpdb;
        $where .= " AND language_code = '{$wpdb->escape($this->this_lang)}'";
        return $where;
    }
    
    // feeds links
    function feed_link($out){  
        return $this->convert_url($out);
    }
    
    // commenting links
    function post_comments_feed_link($out){
        return $out;
        //return $this->convert_url($out);
    }
    
    function trackback_url($out){
        return $this->convert_url($out);
    }
    
    function user_trailingslashit($string, $type_of_url){
        // fixes comment link for when the comments list pagination is enabled
        if($type_of_url=='comment'){
            $string = preg_replace('@(.*)/\?lang=([a-z-]+)/(.*)@is','$1/$3?lang=$2', $string);
        }
        return $string;
    }
    
    // archives links
    function getarchives_join($join){
        global $wpdb;
        $join .= " JOIN {$wpdb->prefix}icl_translations t ON t.element_id = {$wpdb->posts}.ID AND t.element_type='post'";        
        return $join;        
    }
    
    function getarchives_where($where){
        global $wpdb;
        $where .= " AND language_code = '{$wpdb->escape($this->this_lang)}'";
        return $where;                
    } 
       
    function archives_link($out){
        global $icl_archive_url_filter_off;                 
        if(!$icl_archive_url_filter_off){
            $out = $this->archive_url($out, $this->this_lang);
        }       
        $icl_archive_url_filter_off = false;
        return $out;
    }
    
    function archive_url($url, $lang){        
        $url = $this->convert_url($url, $lang);
        return $url;
    }
   
    // Navigation
    function get_pagenum_link_filter($url){
        return $this->convert_url($url, $this->this_lang);    
    }
    
    // TO REVISE
    function pre_option_home(){                              
        $dbbt = debug_backtrace();                                     
        
        static $inc_methods = array('include','include_once','require','require_once');
        if($dbbt['4']['function']=='get_bloginfo' && $dbbt['5']['function']=='bloginfo'){  // case of bloginfo
            $is_template_file = false !== strpos($dbbt[5]['file'], realpath(TEMPLATEPATH));
            $is_direct_call   = in_array($dbbt[6]['function'], $inc_methods) || (false !== strpos($dbbt[6]['file'], realpath(TEMPLATEPATH)));
        }elseif($dbbt['4']['function']=='get_bloginfo'){  // case of get_bloginfo
            $is_template_file = false !== strpos($dbbt[4]['file'], realpath(TEMPLATEPATH));
            $is_direct_call   = in_array($dbbt[5]['function'], $inc_methods) || (false !== strpos($dbbt[5]['file'], realpath(TEMPLATEPATH)));
        }elseif($dbbt['4']['function']=='get_settings'){  // case of get_settings
            $is_template_file = false !== strpos($dbbt[4]['file'], realpath(TEMPLATEPATH));
            $is_direct_call   = in_array($dbbt[5]['function'], $inc_methods) || (false !== strpos($dbbt[5]['file'], realpath(TEMPLATEPATH)));
        }else{ // case of get_option
            $is_template_file = false !== strpos($dbbt[3]['file'], realpath(TEMPLATEPATH));
            $is_direct_call   = in_array($dbbt[4]['function'], $inc_methods) || (false !== strpos($dbbt[4]['file'], realpath(TEMPLATEPATH)));
        }
        
        /*
        if(defined('START') && !defined('STOP')){
            foreach($dbbt as $k=>$d){
                echo $k . '<br />';
                echo $d['file'] . ":" . $d['line'] .'<br />';
                echo $d['function'] . '<br />';
                echo '<br />';
            }
            define('STOP', true);
        }
        */
        
        //if($dbbt[3]['file'] == @realpath(TEMPLATEPATH . '/header.php')){
        if($is_template_file && $is_direct_call){
            $ret = $this->language_url($this->this_lang);                                       
        }else{
            $ret = false;
        }
        return $ret;
    }
            
    function query_vars($public_query_vars){
        $public_query_vars[] = 'lang';
        global $wp_query;        
        $wp_query->query_vars['lang'] = $this->this_lang;                    
        return $public_query_vars;
    }
        
    function parse_query($q){
        global $wp_query, $wpdb;
        //if($q == $wp_query) return; // not touching the WP query
        if(is_admin()) return; 
        
        if($this->get_current_language() != $this->get_default_language()) {
            $cat_array = array();
            // cat
            if(isset($q->query_vars['cat']) && !empty($q->query_vars['cat'])){
                $cat_array = array_map('trim', explode(',', $q->query_vars['cat']));
            }
            // category_name
            if(isset($q->query_vars['category_name']) && !empty($q->query_vars['category_name'])){
                $cat = get_term_by( 'slug', $q->query_vars['category_name'], 'category' ); 
                if(!$cat){
                    $cat = get_term_by( 'name', $q->query_vars['category_name'], 'category' ); 
                }
                if($cat_id = $cat->term_taxonomy_id){
                    $cat_array = array($cat_id);            
                }else{
                    $q->query_vars['p'] = -1;
                }                
            }
            // category_and
            if(isset($q->query_vars['category__and']) && !empty($q->query_vars['category__and'])){
                $cat_array = $q->query_vars['category__and'];
            }
            // category_in
            if(isset($q->query_vars['category__in']) && !empty($q->query_vars['category__in'])){
                $cat_array = $q->query_vars['category__in'];
            }            
            // category__not_in
            if(isset($q->query_vars['category__not_in']) && !empty($q->query_vars['category__not_in'])){
                $cat_array = $q->query_vars['category__not_in'];
            }
            
            if(!empty($cat_array)){
                $translated_ids = array();
                foreach($cat_array as $c){                    
                    if(intval($c) < 0){
                        $sign = -1;
                    }else{
                        $sign = 1;
                    }
                    $translated_ids[] = $sign * intval(icl_object_id(abs($c), 'category', true));
                }
            }
            
            //cat
            if(isset($q->query_vars['cat']) && !empty($q->query_vars['cat'])){
                $q->query_vars['cat'] = join(',', $translated_ids);    
            }
            // category_name
            if(isset($q->query_vars['category_name']) && !empty($q->query_vars['category_name'])){
                $q->query_vars['cat'] = $translated_ids[0];    
                unset($q->query_vars['category_name']);
            }
            // category__and
            if(isset($q->query_vars['category__and']) && !empty($q->query_vars['category__and'])){
                $q->query_vars['category__and'] = $translated_ids;
            }
            // category__in
            if(isset($q->query_vars['category__in']) && !empty($q->query_vars['category__in'])){
                $q->query_vars['category__in'] = $translated_ids;
            }            
            // category__not_in
            if(isset($q->query_vars['category__not_in']) && !empty($q->query_vars['category__not_in'])){
                $q->query_vars['category__not_in'] = $translated_ids;
            }
            
            // TAGS
            $tag_array = array();
            // tag
            if(isset($q->query_vars['tag']) && !empty($q->query_vars['tag'])){
                if(false !== strpos($q->query_vars['tag'],' ')){
                    $tag_glue = '+';
                    $exp = explode(' ', $q->query_vars['tag']);
                }else{
                    $tag_glue = ',';
                    $exp = explode(',', $q->query_vars['tag']);                    
                } 
                $tag_ids = array();               
                foreach($exp as $e){
                    $tag_array[] = $wpdb->get_var($wpdb->prepare( "SELECT x.term_taxonomy_id FROM $wpdb->terms t 
                        JOIN $wpdb->term_taxonomy x ON t.term_id=x.term_id WHERE x.taxonomy='post_tag' AND t.slug='%s'", $wpdb->escape($e)));    
                }
                $_tmp = array_unique($tag_array);
                if(count($_tmp) == 1 && empty($_tmp[0])){
                    $tag_array = array();    
                }
            }            
            // tag_id
            if(isset($q->query_vars['tag_id']) && !empty($q->query_vars['tag_id'])){
                $tag_array = array_map('trim', explode(',', $q->query_vars['tag_id']));
            }
            
            // tag__and
            if(isset($q->query_vars['tag__and']) && !empty($q->query_vars['tag__and'])){
                $tag_array = $q->query_vars['tag__and'];
            }
            // tag__in
            if(isset($q->query_vars['tag__in']) && !empty($q->query_vars['tag__in'])){
                $tag_array = $q->query_vars['tag__in'];
            }            
            // tag__not_in
            if(isset($q->query_vars['tag__not_in']) && !empty($q->query_vars['tag__not_in'])){
                $tag_array = $q->query_vars['tag__not_in'];
            }
            // tag_slug__in
            if(isset($q->query_vars['tag_slug__in']) && !empty($q->query_vars['tag_slug__in'])){
                foreach($q->query_vars['tag_slug__in'] as $t){
                    $tag_array[] = $wpdb->get_var($wpdb->prepare( "SELECT x.term_taxonomy_id FROM $wpdb->terms t 
                        JOIN $wpdb->term_taxonomy x ON t.term_id=x.term_id WHERE x.taxonomy='post_tag' AND t.slug='%s'", $wpdb->escape($t)));    
                }
            }            
            // tag_slug__and
            if(isset($q->query_vars['tag_slug__and']) && !empty($q->query_vars['tag_slug__and'])){
                foreach($q->query_vars['tag_slug__and'] as $t){
                    $tag_array[] = $wpdb->get_var($wpdb->prepare( "SELECT x.term_taxonomy_id FROM $wpdb->terms t 
                        JOIN $wpdb->term_taxonomy x ON t.term_id=x.term_id WHERE x.taxonomy='post_tag' AND t.slug='%s'", $wpdb->escape($t)));    
                }
            }
            
            if(!empty($tag_array)){
                $translated_ids = array();
                foreach($tag_array as $c){                    
                    if(intval($c) < 0){
                        $sign = -1;
                    }else{
                        $sign = 1;
                    }
                     $_tid = intval(icl_object_id(abs($c), 'post_tag', true));
                     $translated_ids[] = $sign * $wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id=%d", $_tid));
                }
            }
            

            //tag            
            if(isset($q->query_vars['tag']) && !empty($q->query_vars['tag'])){
                if(isset($translated_ids)){
                    $slugs = $wpdb->get_col("SELECT slug FROM $wpdb->terms WHERE term_id IN (".join(',', $translated_ids).")");
                    $q->query_vars['tag'] = join($tag_glue, $slugs);    
                }                                
            }
            //tag_id
            if(isset($q->query_vars['tag_id']) && !empty($q->query_vars['tag_id'])){
                $q->query_vars['tag_id'] = join(',', $translated_ids);    
            }                        
            // tag__and
            if(isset($q->query_vars['tag__and']) && !empty($q->query_vars['tag__and'])){
                $q->query_vars['tag__and'] = $translated_ids;
            }
            // tag__in
            if(isset($q->query_vars['tag__in']) && !empty($q->query_vars['tag__in'])){
                $q->query_vars['tag__in'] = $translated_ids;
            }            
            // tag__not_in
            if(isset($q->query_vars['tag__not_in']) && !empty($q->query_vars['tag__not_in'])){
                $q->query_vars['tag__not_in'] = $translated_ids;
            }   
            // tag_slug__in
            if(isset($q->query_vars['tag_slug__in']) && !empty($q->query_vars['tag_slug__in'])){
                $q->query_vars['tag_slug__in'] = $wpdb->get_col("SELECT slug FROM $wpdb->terms WHERE term_id IN (".join(',', $translated_ids).")");
            }            
            // tag_slug__and
            if(isset($q->query_vars['tag_slug__and']) && !empty($q->query_vars['tag_slug__and'])){
                $q->query_vars['tag_slug__and'] = $wpdb->get_col("SELECT slug FROM $wpdb->terms WHERE term_id IN (".join(',', $translated_ids).")");
            }
               
               
            // POST & PAGES
            // page_id                        
            if(isset($q->query_vars['page_id']) && !empty($q->query_vars['page_id'])){
                $q->query_vars['page_id'] = icl_object_id($q->query_vars['page_id'], 'page', true);
                $q->query = preg_replace('/page_id=[0-9]+/','page_id='.$q->query_vars['page_id'], $q->query);
            }
            
            // p
            if(isset($q->query_vars['p']) && !empty($q->query_vars['p'])){
                $q->query_vars['p'] = icl_object_id($q->query_vars['p'], 'post', true);
            }
            // name
            if(isset($q->query_vars['name']) && !empty($q->query_vars['name'])){
                $pid = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name='".$wpdb->escape($q->query_vars['name'])."'");
                $q->query_vars['p'] = icl_object_id($pid, 'post', true);
                unset($q->query_vars['name']);
            }
            // pagename
            if(isset($q->query_vars['pagename']) && !empty($q->query_vars['pagename'])){
                $pid = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name='".$wpdb->escape($q->query_vars['pagename'])."'");
                $q->query_vars['page_id'] = icl_object_id($pid, 'page', true);
                if($pid != $q->query_vars['page_id']){
                    $q->query_vars['pagename'] = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE ID=" . $pid);                
                }
            }
            // post__in
            if(isset($q->query_vars['post__in']) && !empty($q->query_vars['post__in'])){
                $pid = array();
                foreach($q->query_vars['post__in'] as $p){
                    $pid[] = icl_object_id($p, 'post', true);                    
                }
                $q->query_vars['post__in'] = $pid;
            }
            // post__not_in
            if(isset($q->query_vars['post__not_in']) && !empty($q->query_vars['post__not_in'])){
                $pid = array();
                foreach($q->query_vars['post__not_in'] as $p){
                    $pid[] = icl_object_id($p, 'post', true);                    
                }
                $q->query_vars['post__not_in'] = $pid;
            }
            // post_parent
            if(isset($q->query_vars['post_parent']) && !empty($q->query_vars['post_parent']) && $q->query_vars['post_type']!='attachment'){
                $q->query_vars['post_parent'] = icl_object_id($q->query_vars['post_parent'], 'post', true);
            } 
                     
        }
        return $q;
    }
    
    function adjust_wp_list_pages_excludes($pages){
        foreach($pages as $k=>$v){
            $pages[$k] = icl_object_id($v, 'page', true);
        }
        return $pages;
    }
        
    function language_attributes($output){
        if(preg_match('#lang="[a-z-]+"#i',$output)){
            $output = preg_replace('#lang="([a-z-]+)"#i', 'lang="'.$this->this_lang.'"', $output);
        }else{
            $output .= ' lang="'.$this->this_lang.'"';
        }
        return $output;
    }
        
    // Localization
    function plugin_localization(){
        $plugins_dir = basename(dirname(ICL_PLUGIN_PATH));                      
        $plugin_dir = basename(ICL_PLUGIN_PATH);            
        load_plugin_textdomain( 'sitepress', 'wp-content/'.$plugins_dir.'/' . $plugin_dir . '/locale', $plugin_dir . '/locale');
    }
    
    function locale(){
        global $wpdb, $locale;
        
        if(defined('WP_ADMIN')){            
            $l = $this->get_locale($this->admin_language);
        }else{
            $l = $this->get_locale($this->this_lang);
        }        
        if($l){
            $locale = $l;
        }    
        
        add_filter('language_attributes', array($this, '_language_attributes'));
        
        // theme localization
        if($this->settings['gettext_theme_domain_name']){
            load_textdomain($this->settings['gettext_theme_domain_name'], TEMPLATEPATH . '/'.$locale.'.mo');
        }   
        return $locale;
    }
    
    function _language_attributes($latr){
        global $locale;
        $latr = preg_replace('#lang="(.[a-z])"#i', 'lang="'.str_replace('_','-',$locale).'"', $latr);
        return $latr;
    }
        
    function get_locale($code) {
        global $wpdb;        
        
        if (isset($this->icl_locale_cache) && $this->icl_locale_cache->has_key($code)){
            return $this->icl_locale_cache->get($code);
        }
        
        $locale = $wpdb->get_var("SELECT locale FROM {$wpdb->prefix}icl_locale_map WHERE code='{$code}'");
        if (isset($this->icl_locale_cache)){
            $this->icl_locale_cache->set($code, $locale);
        }
        return $locale;
    }
    
    function get_locale_file_names(){
        global $wpdb;
        $locales = array();
        $res = $wpdb->get_results("
            SELECT lm.code, locale 
            FROM {$wpdb->prefix}icl_locale_map lm JOIN {$wpdb->prefix}icl_languages l ON lm.code = l.code AND l.active=1");
        foreach($res as $row){
            $locales[$row->code] = $row->locale;
        }
        return $locales;        
    }
    
    function set_locale_file_names($locale_file_names_pairs){
        global $wpdb;        
        $lfn = $this->get_locale_file_names();
        
        $new = array_diff(array_keys($locale_file_names_pairs), array_keys($lfn));        
        if(!empty($new)){
            foreach($new as $code){
                $wpdb->insert($wpdb->prefix.'icl_locale_map', array('code'=>$code,'locale'=>$locale_file_names_pairs[$code]));
            }
        }        
        $remove = array_diff(array_keys($lfn), array_keys($locale_file_names_pairs));
        if(!empty($remove)){
            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_locale_map WHERE code IN (".join(',', array_map(create_function('$a','return "\'".$a."\'";'),$remove)).")");
        }
        
        $update = array_diff($locale_file_names_pairs, $lfn);
        foreach($update as $code=>$locale){
            $wpdb->update($wpdb->prefix.'icl_locale_map', array('locale'=>$locale), array('code'=>$code));
        }
        
        $this->icl_locale_cache->clear();
        
        return true;        
    }
    
    function pre_option_page_on_front(){
        global $wpdb;
        static $page_on_front_sc = array();
        if (@$page_on_front_sc[$this->this_lang] === null || ICL_DISABLE_CACHE) {
            $page_on_front_sc[$this->this_lang] = false;
            $page_on_front = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='page_on_front'");
            $trid = $this->get_element_language_details($page_on_front, 'post')->trid;
            if($trid){            
                $translations = $wpdb->get_results("SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid={$trid}");
                foreach($translations as $t){
                    if($t->language_code==$this->this_lang){
                        $page_on_front_sc[$this->this_lang] = $t->element_id;
                    }
                }        
            }
        }
        return $page_on_front_sc[$this->this_lang];
    }      
      
    function pre_option_page_for_posts(){
        global $wpdb;
        static $page_for_posts_sc = array();
        if (@$page_for_posts_sc[$this->this_lang] === null || ICL_DISABLE_CACHE) {
            $page_for_posts_sc[$this->this_lang] = false;
            $page_for_posts = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='page_for_posts'");
            $trid = $this->get_element_language_details($page_for_posts, 'post')->trid;
            if($trid){
                $translations = $wpdb->get_results("SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid={$trid}");
                foreach($translations as $t){
                    if($t->language_code==$this->this_lang){
                        $page_for_posts_sc[$this->this_lang] = $t->element_id;
                    }
                }                    
            }
        }
        return $page_for_posts_sc[$this->this_lang];
    } 
    
    function verify_home_and_blog_pages_translations(){
        global $wpdb;
        $warn_home = $warn_posts = '';
        if( 'page' == get_option('show_on_front') && get_option('page_on_front')){
            $page_on_front = get_option('page_on_front');
            $page_home_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$page_on_front} AND element_type='post'");
            $page_home_translations = $this->get_element_translations($page_home_trid, 'post');                 
            $missing_home = array();               
            foreach($this->active_languages as $lang){
             if(!isset($page_home_translations[$lang['code']])){
                 $missing_home[] = '<a href="page-new.php?trid='.$page_home_trid.'&amp;lang='.$lang['code'].'" title="'.__('add translation', 'sitepress').'">' . $lang['display_name'] . '</a>';
             }elseif($page_home_translations[$lang['code']]->post_status != 'publish'){
                 $missing_home[] = '<a href="page.php?action=edit&amp;post='.$page_home_translations[$lang['code']]->element_id.'&amp;lang='.$lang['code'].'" title="'.__('Not published - edit page', 'sitepress').'">' . $lang['display_name'] . '</a>';                 
             }
            }
            if(!empty($missing_home)){
             $warn_home  = '<div class="icl_form_errors" style="font-weight:bold">';
             $warn_home .= sprintf(__('Your home page does not exist or its translation is not published in %s', 'sitepress'), join(', ', $missing_home));
             $warn_home .= '<br />';
             $warn_home .= '<a href="page.php?action=edit&post='.$page_on_front.'">' . __('Edit this page to add translations', 'sitepress') . '</a>';
             $warn_home .= '</div>';
            }
        }
        if(get_option('page_for_posts')){
            $page_for_posts = get_option('page_for_posts');
            $page_posts_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$page_for_posts} AND element_type='post'");
            $page_posts_translations = $this->get_element_translations($page_posts_trid, 'post');                 
            $missing_posts = array();               
            foreach($this->active_languages as $lang){
             if(!isset($page_posts_translations[$lang['code']])){
                 $missing_posts[] = '<a href="page-new.php?trid='.$page_posts_trid.'&amp;lang='.$lang['code'].'" title="'.__('add translation', 'sitepress').'">' . $lang['display_name'] . '</a>';
             }elseif($page_posts_translations[$lang['code']]->post_status != 'publish'){
                 $missing_posts[] = '<a href="page.php?action=edit&amp;post='.$page_posts_translations[$lang['code']]->element_id.'&amp;lang='.$lang['code'].'" title="'.__('Not published - edit page', 'sitepress').'">' . $lang['display_name'] . '</a>';                 
             }
            }
            if(!empty($missing_posts)){
             $warn_posts  = '<div class="icl_form_errors" style="font-weight:bold">';
             $warn_posts .= sprintf(__('Your blog page does not exist or its translation is not published in %s', 'sitepress'), join(', ', $missing_posts));
             $warn_posts .= '<br />';
             $warn_posts .= '<a href="page.php?action=edit&amp;post='.$page_for_posts.'">' . __('Edit this page to add translations', 'sitepress') . '</a>';
             $warn_posts .= '</div>';
            }         
        }    
        return array($warn_home, $warn_posts);                     
    }   
    
    // adds the language parameter to the admin post filtering/search
    function restrict_manage_posts(){
        echo '<input type="hidden" name="lang" value="'.$this->this_lang.'" />';
    }
    
    // adds the language parameter to the admin pages search
    function restrict_manage_pages(){
        ?>
        <script type="text/javascript">        
        addLoadEvent(function(){jQuery('p.search-box').append('<input type="hidden" name="lang" value="<?php echo $this->this_lang ?>">');});
        </script>        
        <?php
    }
    
    function get_edit_post_link($link, $id, $context = 'display'){
        
        if ( 'display' == $context )
            $and = '&amp;';
        else
            $and = '&';
        
        if($id){
            $details = $this->get_element_language_details($id, 'post');
            $lang = $details->language_code;
            if($lang != $this->get_default_language()){
                $link .= $and . 'lang=' . $lang;
            }        
        }
        return $link;
    }
    
    function option_sticky_posts($posts){
        global $wpdb;
        if(is_array($posts) && !empty($posts)){
            $posts = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_id IN (".join(',',$posts).") AND element_type='post' AND language_code = '{$this->this_lang}'");
        }        
        return $posts;
    }
    
    function request_filter($request){
        // bug http://forum.wpml.org/topic.php?id=5
        if(!defined('WP_ADMIN') && $this->settings['language_negotiation_type']==3 && isset($request['lang']) && count($request)==1){
            unset($request['lang']);
        }
        return $request;
    }
        
    function noscript_notice(){
        ?><noscript><div class="error"><?php echo __('WPML admin screens require JavaScript in order to display. JavaScript is currently off in your browser.', 'sitepress') ?></div></noscript><?php
    }
    
    function filter_queries($sql){                                                                               
        global $wpdb, $pagenow;
        // keep a record of the queries
        $this->queries[] = $sql;

        if($pagenow=='categories.php' || $pagenow=='edit-tags.php'){
            if(preg_match('#^SELECT COUNT\(\*\) FROM '.$wpdb->term_taxonomy.' WHERE taxonomy = \'(category|post_tag)\' $#',$sql,$matches)){
                if($matches[1]=='post_tag'){
                    $element_type='tag';
                }else{
                    $element_type=$matches[1];
                }
                $sql = "
                    SELECT COUNT(*) FROM {$wpdb->term_taxonomy} tx 
                        JOIN {$wpdb->prefix}icl_translations tr ON tx.term_taxonomy_id=tr.element_id  
                    WHERE tx.taxonomy='{$matches[1]}' AND tr.element_type='{$element_type}' AND tr.language_code='".$this->get_current_language()."'";
            }
        }
        
        if($pagenow=='edit.php' || $pagenow=='edit-pages.php'){            
            if(preg_match('#SELECT post_status, COUNT\( \* \) AS num_posts FROM '.$wpdb->posts.' WHERE post_type = \'(.+)\' GROUP BY post_status#i',$sql,$matches)){
                if('all'!=$this->get_current_language()){
                    $sql = '
                    SELECT post_status, COUNT( * ) AS num_posts 
                    FROM '.$wpdb->posts.' p 
                        JOIN '.$wpdb->prefix.'icl_translations t ON p.ID = t.element_id 
                    WHERE p.post_type = \''.$matches[1].'\' 
                        AND t.element_type=\'post\' 
                        AND t.language_code=\''.$this->get_current_language().'\' 
                    GROUP BY post_status';
                }else{
                    $sql = '
                    SELECT post_status, COUNT( * ) AS num_posts 
                    FROM '.$wpdb->posts.' p 
                        JOIN '.$wpdb->prefix.'icl_translations t ON p.ID = t.element_id 
                        JOIN '.$wpdb->prefix.'icl_languages l ON t.language_code = l.code AND l.active = 1
                    WHERE p.post_type = \''.$matches[1].'\' 
                        AND t.element_type=\'post\' 
                    GROUP BY post_status';                    
                }
            }
        }        
        return $sql;
    }
    
    function get_inactive_content(){
        global $wpdb;
        $inactive = array();
        $res_p = $wpdb->get_results("
           SELECT COUNT(p.ID) AS c, p.post_type, lt.name AS language FROM {$wpdb->prefix}icl_translations t 
            JOIN {$wpdb->posts} p ON t.element_id=p.ID AND t.element_type='post'
            JOIN {$wpdb->prefix}icl_languages l ON t.language_code = l.code AND l.active = 0
            JOIN {$wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code  AND lt.display_language_code='".$this->get_current_language()."'
            GROUP BY p.post_type, t.language_code
        ");
        foreach($res_p as $r){
            $inactive[$r->language][$r->post_type] = $r->c;
        }
        $res_t = $wpdb->get_results("
           SELECT COUNT(p.term_taxonomy_id) AS c, p.taxonomy, lt.name AS language FROM {$wpdb->prefix}icl_translations t 
            JOIN {$wpdb->term_taxonomy} p ON t.element_id=p.term_taxonomy_id
            JOIN {$wpdb->prefix}icl_languages l ON t.language_code = l.code AND l.active = 0
            JOIN {$wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code  AND lt.display_language_code='".$this->get_current_language()."'
            WHERE t.element_type IN ('category','tag')
            GROUP BY p.taxonomy, t.language_code 
        ");        
        foreach($res_t as $r){
            if($r->taxonomy=='category' && $r->c == 1){
                continue; //ignore the case of just the default category that gets automatically created for a new language
            }
            $inactive[$r->language][$r->taxonomy] = $r->c;
        }        
        return $inactive;
    }
    
    function menu_footer(){
        include ICL_PLUGIN_PATH . '/menu/menu-footer.php';
    }
    
    function _allow_calling_template_file_directly(){
        if(is_404()){  
            global $wp_query, $wpdb;
            $wp_query->is_404 = false;
            $parts = parse_url(get_bloginfo('home'));
            $req = str_replace($parts['path'], '', $_SERVER['REQUEST_URI']);
            if(file_exists(ABSPATH . $req) && !is_dir(ABSPATH . $req)){
                header('HTTP/1.1 200 OK');
                include ABSPATH . $req;
                exit;
            }
        }
    }
    
    function show_user_options(){
        global $current_user;
        $active_languages = $this->get_active_languages();
        $default_language = $this->get_default_language();
        $user_language = get_usermeta($current_user->data->ID,'icl_admin_language',true);
        if($this->settings['admin_default_language'] == '_default_'){
            $this->settings['admin_default_language'] = $default_language;
        }
        $lang_details = $this->get_language_details($this->settings['admin_default_language']);
        $admin_default_language = $lang_details['display_name'];
        ?>
        <a name="wpml"></a>
        <h3><?php _e('WPML language settings','sitepress'); ?></h3>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php _e('Select your language:', 'sitepress') ?></th>
                    <td>                        
                        <select name="icl_user_admin_language">
                        <option value=""<?php if($user_language==$this->settings['admin_default_language']) echo ' selected="selected"'?>><?php printf(__('Default admin language (currently %s)','sitepress'), $admin_default_language );?>&nbsp;</option>
                        <?php foreach($active_languages as $al):?>
                        <option value="<?php echo $al['code'] ?>"<?php if($user_language==$al['code']) echo ' selected="selected"'?>><?php echo $al['display_name']; if($this->admin_language != $al['code']) echo ' ('. $al['native_name'] .')'; ?>&nbsp;</option>
                        <?php endforeach; ?>
                        </select>                        
                        <span class="description"><?php _e('this will be your admin language and will also be used for translating comments.', 'sitepress'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Hidden languages:', 'sitepress') ?></th>
                    <td>   
                        <p>                     
                        <?php if(!empty($this->settings['hidden_languages'])): ?>                        
                            <?php
                             if(1 == count($this->settings['hidden_languages'])){
                                 printf(__('%s is currently hidden to visitors.', 'sitepress'), 
                                    $active_languages[$this->settings['hidden_languages'][0]]['display_name']);
                             }else{
                                 foreach($this->settings['hidden_languages'] as $l){
                                     $_hlngs[] = $active_languages[$l]['display_name'];
                                 }                                 
                                 $hlangs = join(', ', $_hlngs);
                                 printf(__('%s are currently hidden to visitors.', 'sitepress'), $hlangs);
                             }
                            ?>                            
                        <?php else: ?>
                        <?php _e('All languages are currently displayed. Choose what to do when site languages are hidden.', 'sitepress'); ?>
                        <?php endif; ?>                        
                        </p>
                        <p>
                        <label><input name="icl_show_hidden_languages" type="checkbox" value="1" <?php 
                            if(get_usermeta($current_user->data->ID, 'icl_show_hidden_languages')):?>checked="checked"<?php endif?> />&nbsp;<?php 
                            _e('Display hidden languages', 'sitepress') ?></label>
                        </p>
                    </td>
                </tr>                
            </tbody>
        </table>        
        <?php
    }
    
    function  save_user_options(){
        $user_id = $_POST['user_id'];
        if($user_id){
            update_usermeta($user_id,'icl_admin_language',$_POST['icl_user_admin_language']);        
            update_usermeta($user_id,'icl_show_hidden_languages',intval($_POST['icl_show_hidden_languages']));        
            $this->icl_locale_cache->clear();
        }        
    }
    
    function help_admin_notice(){  
        $q = http_build_query(array(
            'name'      => 'wpml-intro',
            'iso'       => WPLANG,
            'src'    => get_option('home')
        ));
        ?>                                                                                                           
        <br clear="all" />
        <div id="message" class="updated message fade" style="clear:both;margin-top:5px;"><p>
        <?php _e('WPML is a powerful plugin with many features. Would you like to see a quick overview?', 'sitepress'); ?>
        </p>
        <p>
        <a href="<?php echo ICL_API_ENDPOINT ?>/destinations/go?<?php echo $q ?>" target="_blank" class="button-primary"><?php _e('Yes', 'sitepress')?></a>&nbsp;
        <a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH).'/menu/languages.php&icl_action=dismiss_help'; ?>"  class="button"><?php _e('No thanks, I will configure myself', 'sitepress')?></a>&nbsp;
        <a title="<?php _e('Stop showing this message', 'sitepress') ?>" id="icl_dismiss_help" href=""><?php _e('Dismiss', 'sitepress')?></a>
        </p>
        </div>
        <?php 
    }
    
    function upgrade_notice(){
        include ICL_PLUGIN_PATH . '/menu/upgrade_notice.php';
    }
    
    function icl_reminders(){
        include ICL_PLUGIN_PATH . '/menu/icl_reminders.php';
    }
    
    function add_posts_management_column($columns){
        global $posts, $wpdb, $__management_columns_posts_translations;
        
        if(count($this->get_active_languages()) <= 1 || get_query_var('post_status') == 'trash'){
            return $columns;
        }
        
        if($_POST['action']=='inline-save' && $_POST['post_ID']){
            $p = new stdClass();
            $p->ID = $_POST['post_ID'];
            $posts = array($p);
        }elseif(empty($posts)){
            return $columns;
        }         
        if(is_null($__management_columns_posts_translations)){        
            foreach($posts as $p){
                $post_ids[] = $p->ID;
            }
            // get posts translations
            // get trids
            $trids = $wpdb->get_col("
                SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND element_id IN (".join(',', $post_ids).")
            ");
            $ptrs = $wpdb->get_results("
                SELECT trid, element_id, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE trid IN (".join(',', $trids).")
            ");
            foreach($ptrs as $v){
                $by_trid[$v->trid][] = $v;
            }

            foreach($ptrs as $v){
                if(in_array($v->element_id, $post_ids)){
                    $el_trid = $v->trid;
                    foreach($ptrs as $val){
                        if($val->trid == $el_trid){
                            $__management_columns_posts_translations[$v->element_id][$val->language_code] = $val;
                        }
                    }
                }
            }
        }
        $active_languages = $this->get_active_languages();
        foreach($active_languages as $k=>$v){
            if($v['code']==$this->get_current_language()) continue;
            $langs[] = $v['code'];
        }                
        $res = $wpdb->get_results("
            SELECT f.lang_code, f.flag, f.from_template, l.name 
            FROM {$wpdb->prefix}icl_flags f 
                JOIN {$wpdb->prefix}icl_languages_translations l ON f.lang_code = l.language_code
            WHERE l.display_language_code = '".$this->admin_language."' AND f.lang_code IN('".join("','",$langs)."')
        ");
        foreach($res as $r){
            if($r->from_template){
                $fpath = get_bloginfo('template_directory') . '/images/flags/';
            }else{
            }   $fpath = ICL_PLUGIN_URL . '/res/flags/';
            $flags[$r->lang_code] = '<img src="'.$fpath.$r->flag.'" width="18" height="12" alt="'.$r->name.'" title="'.$r->name.'" />';
        }
        $colh = '';
        foreach($active_languages as $v){
            $colh .= $flags[$v['code']];
        }
        foreach($columns as $k=>$v){
            $new_columns[$k] = $v;
            if($k=='title'){
                $new_columns['icl_translations'] = $colh;
            }
        }  
        return $new_columns;
    }
    
    function add_content_for_posts_management_column($column_name){
        
        if($column_name != 'icl_translations') return;
        
        global $id, $__management_columns_posts_translations, $pagenow;
        $active_languages = $this->get_active_languages();
        foreach($active_languages as $k=>$v){
            if($v['code']==$this->get_current_language()) continue;
            if($pagenow=='edit.php' || @$_POST['post_type'] == 'post' ){
                $post_type = 'post';
            }else{
                $post_type = 'page';
            }
            
            if($__management_columns_posts_translations[$id][$v['code']]){
                $img = 'edit_translation.png';
                $alt = sprintf(__('Edit the %s translation','sitepress'), $v['display_name']);                
                $link = $post_type . '.php?action=edit&amp;post='.$__management_columns_posts_translations[$id][$v['code']]->element_id.'&amp;lang='.$v['code'];
            }else{
                $img = 'add_translation.png';
                $alt = sprintf(__('Add translation to %s','sitepress'), $v['display_name']);
                $src_lang = $this->get_current_language() == 'all' ? $this->get_default_language() : $this->get_current_language();
                $link = $post_type . '-new.php?trid=' . $__management_columns_posts_translations[$id][$this->get_current_language()]->trid.'&amp;lang='.$v['code'].'&amp;source_lang=' . $src_lang;
            }
            echo '<a href="'.$link.'" title="'.$alt.'">';
            echo '<img style="padding:1px;margin:2px;" border="0" src="'.ICL_PLUGIN_URL . '/res/img/' .$img.'" alt="'.$alt.'" width="16" height="16" />';
            echo '</a>';
        }
    }
    
    function __set_posts_management_column_width(){
        $w = 22 * count($this->get_active_languages());
        echo '<style type="text/css">.column-icl_translations{width:'.$w.'px;}.column-icl_translations img{margin:2px;}</style>';
    }
    
    function display_wpml_footer(){
        echo '<p id="wpml_credit_footer">';
        printf(__('%s is running multilingual thanks to <a href="%s">WPML</a>', 'sitepress'), get_bloginfo('blogname'), 'http://wpml.org');
        echo '</p>';
    }
    
    function enable_advanced_mode(){
        $this->settings['basic_menu'] = 0;
        $this->save_settings();
    }
    
    function enable_basic_mode(){
        $this->settings['basic_menu'] = 1;
        $this->save_settings();
    }
    
    function xmlrpc_methods($methods){
        $methods['icanlocalize.get_languages_list'] = array($this, 'xmlrpc_get_languages_list');
        return $methods;
    }
    
    function xmlrpc_call_actions($action){
        global $HTTP_RAW_POST_DATA, $wpdb;
        $params = xml2array($HTTP_RAW_POST_DATA);
        switch($action){
            case 'wp.getPage':    
            case 'blogger.getPost': // yet this doesn't return custom fields
                if(isset($params['methodCall']['params']['param'][1]['value']['int']['value'])){
                    $page_id = $params['methodCall']['params']['param'][1]['value']['int']['value'];
                    $lang_details = $this->get_element_language_details($page_id, 'post');
                    update_post_meta($page_id, '_wpml_language', $lang_details->language_code);
                    update_post_meta($page_id, '_wpml_trid', $lang_details->trid);
                    $active_languages = $this->get_active_languages();
                    $res = $this->get_element_translations($lang_details->trid);
                    $translations = array();
                    foreach($active_languages as $k=>$v){
                        if($page_id != $res[$k]->element_id){
                            $translations[$k] = isset($res[$k]->element_id) ? $res[$k]->element_id : 0;
                        }
                    }
                    update_post_meta($page_id, '_wpml_translations', json_encode($translations));
                }
                break;
            case 'metaWeblog.getPost':
                if(isset($params['methodCall']['params']['param'][0]['value']['int']['value'])){
                    $page_id = $params['methodCall']['params']['param'][0]['value']['int']['value'];
                    $lang_details = $this->get_element_language_details($page_id, 'post');
                    update_post_meta($page_id, '_wpml_language', $lang_details->language_code);
                    update_post_meta($page_id, '_wpml_trid', $lang_details->trid);
                    $active_languages = $this->get_active_languages();
                    $res = $this->get_element_translations($lang_details->trid);
                    $translations = array();
                    foreach($active_languages as $k=>$v){
                        if($page_id != $res[$k]->element_id){
                            $translations[$k] = isset($res[$k]->element_id) ? $res[$k]->element_id : 0;
                        }
                    }
                    update_post_meta($page_id, '_wpml_translations', json_encode($translations));
                }
                break;   
            case 'metaWeblog.getRecentPosts':
                $num_posts = intval($params['methodCall']['params']['param'][3]['value']['int']['value']);
                if($num_posts){
                    $posts = get_posts('suppress_filters=false&numberposts='.$num_posts);
                    foreach($posts as $p){
                        $lang_details = $this->get_element_language_details($p->ID, 'post');
                        update_post_meta($p->ID, '_wpml_language', $lang_details->language_code);
                        update_post_meta($p->ID, '_wpml_trid', $lang_details->trid);
                        $active_languages = $this->get_active_languages();
                        $res = $this->get_element_translations($lang_details->trid);
                        $translations = array();
                        foreach($active_languages as $k=>$v){
                            if($p->ID != $res[$k]->element_id){
                                $translations[$k] = isset($res[$k]->element_id) ? $res[$k]->element_id : 0;
                            }
                        }
                        update_post_meta($p->ID, '_wpml_translations', json_encode($translations));
                    }
                }
                break;         
            
            case 'metaWeblog.newPost':
                $custom_fields = $params['methodCall']['params']['param'][3]['value']['struct']['member'][3]['value']['array']['data']['value'];
                if(is_array($custom_fields)){                    
                    foreach($custom_fields as $cf){
                        if($cf['struct']['member'][0]['value']['string']['value'] == '_wpml_language'){
                            $icl_post_language = $cf['struct']['member'][1]['value']['string']['value'];
                        }elseif($cf['struct']['member'][0]['value']['string']['value'] == '_wpml_trid'){
                            $icl_trid = $cf['struct']['member'][1]['value']['string']['value'];
                        }
                    }
                    if($icl_trid && $icl_post_language && 
                        !$wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type='post' 
                            AND trid={$icl_trid} AND language_code='{$icl_post_language}'")){
                        $_POST['icl_post_language'] = $icl_post_language;
                        $_POST['icl_trid']          = $icl_trid;
                    }else{
                        $IXR_Error = new IXR_Error( 401, __('A translation for this post already exists', 'sitepress') );    
                        echo $IXR_Error->getXml();
                        exit(1);
                    }
                }
                break;
            case 'metaWeblog.editPost':
                $post_id = $params['methodCall']['params']['param'][0]['value']['int']['value'];
                if(!$post_id){
                    break;
                }                
                $custom_fields = $params['methodCall']['params']['param'][3]['value']['struct']['member'][3]['value']['array']['data']['value'];
                if(is_array($custom_fields)){                    
                    foreach($custom_fields as $cf){
                        if($cf['struct']['member'][0]['value']['string']['value'] == '_wpml_language'){
                            $icl_post_language = $cf['struct']['member'][1]['value']['string']['value'];
                        }elseif($cf['struct']['member'][0]['value']['string']['value'] == '_wpml_trid'){
                            $icl_trid = $cf['struct']['member'][1]['value']['string']['value'];
                        }
                    }
                    
                    $epost_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='post' 
                        AND trid={$icl_trid} AND language_code='{$icl_post_language}'");                    
                    if($icl_trid && $icl_post_language && (!$epost_id || $epost_id == $post_id)){
                        $_POST['icl_post_language'] = $icl_post_language;
                        $_POST['icl_trid']          = $icl_trid;                        
                    }else{
                        $IXR_Error = new IXR_Error( 401, __('A translation in this language already exists', 'sitepress') );    
                        echo $IXR_Error->getXml();
                        exit(1);
                    }
                }
                break;
        }
    }
    
    function xmlrpc_get_languages_list($lang){
        global $wpdb;                                                                          
        if(!is_null($lang)){
            if(!$wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='".mysql_real_escape_string($lang)."'")){
                $IXR_Error = new IXR_Error( 401, __('Invalid language code', 'sitepress') );    
                echo $IXR_Error->getXml();
                exit(1);
            }
            $this->admin_language = $lang;
        }                 
        define('WP_ADMIN', true); // hack - allow to force display language
        $active_languages = $this->get_active_languages(true);
        return $active_languages;
        
    }
    
    function get_current_action_step() {
        global $wpdb;
        
        if (!$this->icl_account_configured()) {
            return 0;
        }

        $cms_count = $wpdb->get_var("SELECT COUNT(rid) FROM {$wpdb->prefix}icl_core_status WHERE status=3");
        if($cms_count > 0) {
            return 5;
        }
        
        $cms_count = $wpdb->get_var("SELECT COUNT(rid) FROM {$wpdb->prefix}icl_core_status WHERE 1");
        if($cms_count == 0) {
            // No documents sent yet
            return 1;
        }
        
        $icl_lang_status = $this->settings['icl_lang_status'];
        
        $waiting_for_translators = true;
        foreach ($icl_lang_status as $lang) {
            if ($lang['have_translators'] == 0 && $lang['applications'] > 0) {
                return 2;
            }
            if ($lang['have_translators'] > 0 || $lang['applications'] > 0) {
                $waiting_for_translators = false;
            }
        }

        if ($waiting_for_translators) {
            return 2;
        }

        $balance = $this->settings['icl_balance'];
        if ($balance < 0) {
            return 3;
        }

        
        return 4;
        
    }
    
    function show_action_list() {
        $steps = array(__('Set up a project in ICanLocalize', 'sitepress'),
                        __('Send documents for translation', 'sitepress'),
                        __('Choose your translators', 'sitepress'),
                        __('Deposit payment', 'sitepress'),
                        __('Translations will be returned to your site', 'sitepress'));

        $current_step = $this->get_current_action_step();
        if ($current_step >= sizeof($steps)) {
            // everything is already setup.
            if ($this->settings['last_action_step_shown']) {
                return '';
            } else {
                $this->save_settings(array('last_action_step_shown' => 1));
            }
        }
        
        $output = '
            <h3>' . __('Setup check list', 'sitepress') . '</h3>
            <ul id="icl_check_list">';
            
        foreach($steps as $index => $step) {
            $step_data = $step;
            
            if ($index < $current_step || ($index == 4 && $this->settings['icl_balance'] > 0)) {
                $attr = ' class="icl_tick"';
            } else {
                $attr = ' class="icl_next_step"';
            }
            
            if ($index == $current_step) {
                $output .= '<li class="icl_info"><b>' . $step_data . '</b></li>';
            } else {
                $output .= '<li' . $attr. '>' . $step_data . '</li>';
            }
            $output .= "\n";
        }
                
        $output .= '
            </ul>';
        
        return $output;
    }
    
    function show_pro_sidebar() {
        $output = '<div id="icl_sidebar" class="icl_sidebar" style="display:none">';
        
        $action_list = $this->show_action_list();
        $show_minimized = $this->settings['icl_sidebar_minimized'];
        if ($action_list != '') {
            $show_minimized = false;
        }

        if ($show_minimized) {
            $output .= '<div id="icl_sidebar_full" style="display:none">';
        } else {
            $output .= '<div id="icl_sidebar_full">';
        }

        if ($action_list == '') {
            $output .= '<a id="icl_sidebar_hide" href="#">hide</a>';
        } else {
            $output .= $action_list;
        }
        
        $output .= '<h3>' . __('Help', 'sitepress') . '</h3>';
        $output .= '<div id="icl_help_links"></div>';
        $output .= '</div>';
        if ($show_minimized) {
            $output .= '<div id="icl_sidebar_hide_div">';
        } else {
            $output .= '<div id="icl_sidebar_hide_div" style="display:none">';
        }
        $output .= '<a id="icl_sidebar_show" href="#"><img width="16" height="16" src="' . ICL_PLUGIN_URL . '/res/img/question1.png' . '" alt="'.__('Get help','sitepress').'" title="'.__('Get help','sitepress').'" /></a>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
        
    }
    
    function meta_generator_tag(){
        $lids = array();
        foreach($this->get_active_languages() as $l){
            $lids[] = $l['id'];
        }
        $stt = join(",",$lids);
        $stt .= ";" . intval($this->settings['modules']['cms-navigation']['enabled']);
        $stt .= ";" . intval($this->settings['modules']['absolute-links']['enabled']);
        $stt .= ";" . intval($this->get_icl_translation_enabled());
        printf('<meta name="generator" content="WPML ver:%s stt:%s" />' . PHP_EOL, ICL_SITEPRESS_VERSION, $stt);        
    }
}
?>