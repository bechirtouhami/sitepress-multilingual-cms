<?php
class SitePress{
   
    private $settings;
    private $active_languages;
    private $this_lang;
    
    function __construct(){
        global $wpdb;       
        $this->settings = get_option('icl_sitepress_settings');        
        $res = $wpdb->get_results("
            SELECT code, english_name, active, lt.name AS display_name 
            FROM {$wpdb->prefix}icl_languages l
                JOIN {$wpdb->prefix}icl_languages_translations lt ON l.code=lt.language_code           
            WHERE 
                active=1 AND lt.display_language_code = '{$this->get_default_language()}' 
            ORDER BY major DESC, english_name ASC", ARRAY_A);        
        $languages = array();
        if($res){
            foreach($res as $r){
                $languages[] = $r;
            }        
        }
        $this->active_languages = $languages; 
        
        add_action('plugins_loaded', array($this,'init'));
                
        // Ajax feedback
        if(isset($_POST['icl_ajx_action'])){
            add_action('init', array($this,'ajax_responses'));
        }
        
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
            
            add_filter('posts_join', array($this,'posts_join_filter'));
            add_filter('comment_feed_join', array($this,'comment_feed_join'));
            
            
            global $pagenow;
            if(in_array($pagenow, array('edit-pages.php','edit.php'))){
                add_action('admin_head', array($this,'language_filter'));
            }
            
            add_filter('wp_list_pages_excludes', array($this, 'exclude_other_language_pages'));
            add_filter('get_pages', array($this, 'exclude_other_language_pages2'));
            add_filter('wp_dropdown_pages', array($this, 'wp_dropdown_pages'));
            
            

            // posts and pages links filters            
            add_filter('post_link', array($this, 'permalink_filter'),1,2);   
            add_filter('page_link', array($this, 'permalink_filter'),1,2);   
            add_filter('category_link', array($this, 'category_permalink_filter'),1,2);   
            add_filter('tag_link', array($this, 'tag_permalink_filter'),1,2);   
            
            
            add_action('create_term',  array($this, 'create_term'),1, 2);
            add_action('delete_term',  array($this, 'delete_term'),1,3);       
            add_filter('list_terms_exclusions', array($this, 'exclude_other_terms'),1,2);         
            // category language selection        
            add_action('edit_category',  array($this, 'create_term'),1, 2);        
            if($pagenow == 'categories.php'){
                add_action('admin_print_scripts-categories.php', array($this,'js_scripts_categories'));
                add_action('edit_category_form', array($this, 'edit_term_form'));
                add_action('admin_head', array($this,'terms_language_filter'));
            }        
            // tags language selection
            if($pagenow == 'edit-tags.php'){
                add_action('admin_print_scripts-edit-tags.php', array($this,'js_scripts_tags'));
                add_action('add_tag_form', array($this, 'edit_term_form'));
                add_action('edit_tag_form', array($this, 'edit_term_form'));
                add_action('admin_head', array($this,'terms_language_filter'));                
            }
            
            // the language selector widget      
            add_action('plugins_loaded', array($this, 'language_selector_widget_init'));
            
            // custom hook for adding the language selector to the template
            add_action('icl_language_selector', array($this, 'language_selector'));
            
            // front end js
            add_action('wp_head', array($this, 'front_end_js'));            
            
            add_action('restrict_manage_posts', array($this, 'restrict_manage_posts'));
            add_action('admin_print_scripts-edit-pages.php', array($this,'restrict_manage_pages'));
            
            add_filter('get_edit_post_link', array($this, 'get_edit_post_link'), 1, 2);
        }
        
        // short circuit get default category
        add_filter('pre_option_default_category', array($this, 'pre_option_default_category'));
        add_filter('the_category', array($this,'the_category_name_filter'));
        add_filter('get_terms', array($this,'get_terms_filter'));
        add_filter('single_cat_title', array($this,'the_category_name_filter'));
        
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
        if($this->settings['language_home']){
            add_filter('pre_option_home', array($this,'pre_option_home'));            
        }     
        
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
        
        add_action('init', array($this,'plugin_localization'));
        
    }
                                      
    function init(){        
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
            $s = $_SERVER['HTTPS']=='on'?'s':'';
            $request = 'http' . $s . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $home = get_option('home');
            $url_parts = parse_url($home);
            $blog_path = $url_parts['path']?$url_parts['path']:'';            
            switch($this->settings['language_negotiation_type']){
                case 1:
                    $path  = str_replace($home,'',$request);                
                    $exp = explode('/',trim($path,'/'));                                        
                    if(in_array($exp[0], $active_languages)){
                        $this->this_lang = $exp[0];
                        $_SERVER['REQUEST_URI'] = preg_replace('@^'. $blog_path . '/' . $this->this_lang.'@i', $blog_path ,$_SERVER['REQUEST_URI']);
                    }else{
                        $this->this_lang = $this->get_default_language();
                    }
                    break;
                case 2:    
                    $exp = explode('.', $_SERVER['HTTP_HOST']);
                    $__l = array_search('http' . $s . '://' . $_SERVER['HTTP_HOST'] . $blog_path, $this->settings['language_domains']);
                    $this->this_lang = $__l?$__l:$this->get_default_language(); 
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
        
        add_filter('get_pagenum_link', array($this,'get_pagenum_link_filter'));
        
        require ICL_PLUGIN_PATH . '/inc/template-constants.php';        
    }
                
    function ajax_responses(){
        global $wpdb;
        // moved
    }
    
    function administration_menu(){
        add_action('admin_print_scripts', array($this,'js_scripts_setup'));
        add_action('admin_print_styles', array($this,'css_setup'));
        add_menu_page(__('WPML','sitepress'), __('WPML','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php',null, ICL_PLUGIN_URL . '/res/img/icon16.png');        
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Languages','sitepress'), __('Languages','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php'); 
        if($this->settings['existing_content_language_verified']){
            add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Content Translation','sitepress'), __('Content Translation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/content-translation.php'); 
            //add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Comments Translation','sitepress'), __('Comments Translation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/comments-translation.php'); 
        }
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Navigation','sitepress'), __('Navigation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/navigation.php'); 
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Sticky links','sitepress'), __('Sticky links','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/absolute-links.php'); 
    }

    function save_settings($settings=null){
        if(!is_null($settings)){
            foreach($settings as $k=>$v){
                $this->settings[$k] = $v;
            }        
        }
        update_option('icl_sitepress_settings', $this->settings);
    }

    function get_settings(){
        return $this->settings;
    }    
    
    function get_active_languages(){
        return $this->active_languages;
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
        foreach((array)$res as $r){
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
            WHERE lt.display_language_code = '{$this->this_lang}' AND code='{$code}'
            ORDER BY major DESC, english_name ASC", ARRAY_A);
        return $language;
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

    function get_default_language(){        
        return $this->settings['default_language'];
    }
    
    function get_current_language(){
        return $this->this_lang;
    }
    
    function set_default_language($code){        
        global $wpdb;
        $iclsettings['default_language'] = $code;
        $this->save_settings($iclsettings);
        
        // change WP locale
        $locale = $wpdb->get_var("SELECT locale FROM {$wpdb->prefix}icl_locale_map WHERE code='{$code}'");
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
        if(!get_option('enable_xmlrpc')){
            $errors[] = __('XML-RPC publishing protocol not enabled', 'sitepress') . 
                ' <a href="'.get_option('siteurl').'/wp-admin/options-writing.php">'.__('Fix','sitepress').'</a>';
        }
        return $errors;        
    }
    
    function icl_account_configured(){
        return $this->settings['site_id'] && $this->settings['access_key'];
    }
    
    function js_scripts_setup(){        
        global $pagenow, $wpdb;
        if(isset($_GET['page'])){
            $page = basename($_GET['page']);
            $page_basename = str_replace('.php','',$page);
        }
        ?>
        <script type="text/javascript">        
        var icl_ajx_url = '<?php echo ICL_PLUGIN_URL ?>/ajax.php';
        var icl_ajx_saved = '<?php echo __('Data saved') ?>';
        var icl_ajx_error = '<?php echo __('Error: data not saved') ?>';
        var icl_default_mark = '<?php echo __('default') ?>';     
        var icl_this_lang = '<?php echo $this->this_lang ?>';   
        var icl_ajxloaderimg = '<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif';
        var icl_cat_adder_msg = '<?php echo __('To add categories that already exist in other languages go to the <a href="categories.php">category management page</a>','sitepress')?>';
        </script>        
        <?php
        wp_enqueue_script('sitepress-scripts', ICL_PLUGIN_URL . '/res/js/scripts.js', array(), '0.1');
        if(isset($page_basename) && file_exists(ICL_PLUGIN_PATH . '/res/js/'.$page_basename.'.js')){
            wp_enqueue_script('sitepress-' . $page_basename, ICL_PLUGIN_URL . '/res/js/'.$page_basename.'.js', array(), '0.1');
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
    }
       
    function front_end_js(){
        echo '<script type="text/javascript">var icl_lang = \''.$this->this_lang.'\';var icl_home = \''.$this->language_url().'\';</script>';        
        echo '<script type="text/javascript" src="'. ICL_PLUGIN_URL . '/res/js/sitepress.js"></script>';        
    }
    
    function js_scripts_categories(){
        wp_enqueue_script('sitepress-categories', ICL_PLUGIN_URL . '/res/js/categories.js', array(), '0.1');
    }
    
    function js_scripts_tags(){
        wp_enqueue_script('sitepress-tags', ICL_PLUGIN_URL . '/res/js/tags.js', array(), '0.1');
    }
    
    function css_setup(){
        if(isset($_GET['page'])){
            $page = basename($_GET['page']);
            $page_basename = str_replace('.php','',$page);        
        }
        wp_enqueue_style('sitepress-style', ICL_PLUGIN_URL . '/res/css/style.css', array(), '0.1');
        if(isset($page_basename) && file_exists(ICL_PLUGIN_PATH . '/res/css/'.$page_basename.'.css')){
            wp_enqueue_style('sitepress-' . $page_basename, ICL_PLUGIN_URL . '/res/css/'.$page_basename.'.css', array(), '0.1');
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
                    foreach($_POST as $k=>$v){
                        if(0 !== strpos($k, 'locale_file_name_') || !trim($v)) continue;
                        $locales[str_replace('locale_file_name_','',$k)] = $v;                                                
                    }
                    $this->set_locale_file_names($locales);
                    break;
            }
            return;
        }
        $nonce_icl_create_account = wp_create_nonce('icl_create_account');
        $nonce_icl_configure_account = wp_create_nonce('icl_configure_account');
        $nonce_icl_logout = wp_create_nonce('icl_logout');
        $nonce_icl_initial_language = wp_create_nonce('icl_initial_language');
        $nonce_icl_change_website_access = wp_create_nonce('icl_change_website_access_data');
        switch($_POST['_wpnonce']){
            case $nonce_icl_create_account:
            case $nonce_icl_configure_account:
                $user = $_POST['user'];
                $user['platform_kind'] = 2; // TO BE CHANGED LATER
                $user['blogid'] = $wpdb->blogid?$wpdb->blogid:1;
                $user['url'] = get_option('home');
                $user['title'] = get_option('blogname');
                $user['description'] = get_option('blogdescription');
                $user['interview_translators'] = $this->settings['interview_translators'];
                 
                $user['project_kind'] = $this->settings['website_kind'];
                $user['pickup_type'] = $this->settings['translation_pickup_method'];
        
                $notifications = 0;
                if ( $this->settings['icl_notify_complete']){
                    $notifications += 1;
                }
                if ( $this->settings['icl_alert_delay']){
                    $notifications += 2;
                }
                $user['notifications'] = $notifications;

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
                $icl_query = new ICanLocalizeQuery();
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
                    include_once ICL_PLUGIN_PATH . '/modules/icl-translation/db-scheme.php';
                }
                break;
            case $nonce_icl_logout:
                $iclsettings['site_id']=null;
                $iclsettings['access_key']=null;
                $this->save_settings($iclsettings);
                $_POST['icl_form_success'] = __('ICanLocalize account details reset','sitepress');            
                break;
            case $nonce_icl_initial_language:
                $this->prepopulate_translations($_POST['icl_initial_language_code']);
                $wpdb->update($wpdb->prefix . 'icl_languages', array('active'=>'1'), array('code'=>$_POST['icl_initial_language_code']));
                $iclsettings['existing_content_language_verified'] = 1;
                $this->save_settings($iclsettings);                                
                break;
            case $nonce_icl_change_website_access:
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
            
            if($wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type='{$el_type}' AND element_id='{$el_id}' AND trid='{$trid}'")){
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
    
    function get_element_language_details($el_id, $el_type){        
        global $wpdb;
        $details = $wpdb->get_row("
            SELECT trid, language_code, source_language_code 
            FROM {$wpdb->prefix}icl_translations
            WHERE element_id='{$el_id}' AND element_type='{$el_type}'");
        return $details;
    }
        
    function save_post_actions($pidd){
        global $wpdb;
        if($_POST['autosave'] || $_POST['skip_sitepress_actions']) return;
        if($_POST['action']=='post-quickpress-publish'){
            $post_id = $pidd;            
            $language_code = $this->get_default_language();
        }else{
            $post_id = $_POST['post_ID']?$_POST['post_ID']:$pidd; //latter case for XML-RPC publishing
            $language_code = $_POST['icl_post_language']?$_POST['icl_post_language']:$this->get_default_language(); //latter case for XML-RPC publishing
        } 
        if($_POST['action']=='inline-save'){
            $res = $wpdb->get_row("SELECT trid, language_code FROM {$wpdb->prefix}icl_translations WHERE element_id={$post_id} AND element_type='post'"); 
            $trid = $res->trid;
            $language_code = $res->language_code;
        }else{
            $trid = $_POST['icl_trid'];
        }       
        if($trid && $_POST['post_type']=='page' && $this->settings['sync_page_ordering']){
            $menu_order = $wpdb->escape($_POST['menu_order']);
            $translated_pages = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_id<>{$post_id}");
            if(!empty($translated_pages)){
                $wpdb->query("UPDATE {$wpdb->posts} SET menu_order={$menu_order} WHERE ID IN (".join(',', $translated_pages).")");
            }            
        }
        
        // new categories created inline go to the correct language
        if(isset($_POST['post_category']))
        foreach($_POST['post_category'] as $cat){
            $ttid = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$cat} AND taxonomy='category'");
            $wpdb->update($wpdb->prefix.'icl_translations', 
                array('language_code'=>$_POST['icl_post_language']), 
                array('element_id'=>$ttid, 'element_type'=>'category'));
        }
        $this->set_element_language_details($post_id, 'post', $trid, $language_code);
    }
    
    function delete_post_actions($post_id){
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND element_id='{$post_id}' LIMIT 1");
    }
    
    function get_element_translations($trid, $el_type='post', $skip_empty = false){        
        global $wpdb;  
        if($trid){            
            if($el_type=='post'){
                $sel_add = ', p.post_title';
                $join_add = " LEFT JOIN {$wpdb->posts} p ON t.element_id=p.ID";
                $groupby_add = "";
            }elseif($el_type=='category' || $el_type='tag'){
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
            SELECT t.language_code, t.element_id {$sel_add}
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
        global $wpdb, $pagenow;
        //exceptions
        if($pagenow=='upload.php' || $pagenow=='media-upload.php'){
            return $join;    
        }
        
        if('all' != $this->this_lang){ 
            $cond = "AND language_code='{$wpdb->escape($this->this_lang)}'";
            $ljoin = "";
        }else{
            $cond = '';
            $ljoin = "LEFT";
        }
        $join .= "{$ljoin} JOIN {$wpdb->prefix}icl_translations t ON {$wpdb->posts}.ID = t.element_id 
                    AND t.element_type='post' {$cond} ";        
        return $join;
    }

    function comment_feed_join($join){
        global $wpdb, $wp_query;        
        $wp_query->query_vars['is_comment_feed'] = true;
        $join .= "JOIN {$wpdb->prefix}icl_translations t ON {$wpdb->comments}.comment_post_ID = t.element_id 
                    AND t.element_type='post' {$cond} AND language_code='{$wpdb->escape($this->this_lang)}'";
        return $join;
    }
    
    function language_filter(){
        global $wpdb, $pagenow;
        if($pagenow=='edit.php'){
            $type = 'post';
        }else{
            $type = 'page';
        }
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
            if($lang['code']== $this->this_lang){
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
            jQuery(".subsubsub").append('<br /><span id="icl_subsubsub"><?php echo $allas ?></span>');
        });
        </script>
        <?php
    }
    
    function exclude_other_language_pages($s){
        global $wpdb;
        $excl_pages = $wpdb->get_col("
            SELECT p.ID FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->prefix}icl_translations t ON (p.ID = t.element_id OR t.element_id IS NULL)
            WHERE t.element_type='post' AND p.post_type='page' AND t.language_code <> '{$wpdb->escape($this->this_lang)}'
            ");
        return array_merge($s, $excl_pages);
    }
    
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
            $output = '<select id="parent_id"><option value="">' . __('Main Page (no parent)') . '</option></select>';
        }
        return $output;
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
        
        // case of adding a tag via post save
        if($_POST['action']=='editpost'){
            $term_lang = $_POST['icl_post_language'];        
        }elseif($_POST['action']=='post-quickpress-publish'){
            $term_lang = $this->get_default_language();
        }
        
        // has trid only when it's a translation of another tag             
        $trid = isset($_POST['icl_trid']) && (isset($_POST['icl_tag_language']) || isset($_POST['icl_category_language']))?$_POST['icl_trid']:null;        
        $el_type = $wpdb->get_var("SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id={$tt_id}");
        if($el_type == 'post_tag') $el_type = 'tag'; 
        if(!isset($term_lang)){
            $term_lang = $_POST['icl_'.$el_type.'_language'];        
        }        
        $this->set_element_language_details($tt_id, $el_type, $trid, $term_lang);                
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
            WHERE t.element_type='{$element_type}' AND tt.taxonomy='{$taxonomy}' 
            GROUP BY language_code            
            ");                 
        foreach($res as $r){
            $langs[$r->language_code] = $r->c;
        } 
        $langs['all'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy='{$taxonomy}'");               
        $active_languages[] = array('code'=>'all','display_name'=>__('All languages','sitepress'));
        foreach($active_languages as $lang){
            if($lang['code']== $this->this_lang){
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
        if($this->this_lang != $this->get_default_language()){
            $this_lang = $wpdb->escape($this->this_lang);
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
        $exclusions .= ' AND term_taxonomy_id NOT IN ('.join(',',$exclude).')';
        return $exclusions;
    }
    
    // converts WP generated url to language specific based on plugin settings
    function convert_url($url, $code=null){
        if(is_null($code)){
            $code = $this->this_lang;
        }
        
        if($code != $this->get_default_language()){
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
                        $url_glue = '&';
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
        }
        return $p;
    }    
    
    function category_permalink_filter($p, $cat_id){
        global $wpdb;
        $cat_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$cat_id} AND taxonomy='category'");
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
            $tag_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$tag} AND taxonomy='post_tag'");
        }        
        $element_lang_details = $this->get_element_language_details($tag_id,'tag');
        if($this->get_default_language() != $element_lang_details->language_code){
            $p = $this->convert_url($p, $element_lang_details->language_code);
        }
        return $p;
    }            
    
    function language_selector_widget_init(){ 
        
        function language_selector_widget($args){            
            global $sitepress;
            extract($args, EXTR_SKIP);
            echo $before_widget;
            $sitepress->language_selector();
            echo $after_widget;
        }        
        register_sidebar_widget(__('Language Selector', 'sitepress'), 'language_selector_widget', 'icl_languages_selector');
        
        function icl_lang_sel_nav_css($show = true){            
            $link_tag = '<link rel="stylesheet" href="'. ICL_PLUGIN_URL . '/res/css/language-selector.css?v=0.2" type="text/css" media="all" />';
            if(!$show){
                return $link_tag;
            }else{
                echo $link_tag;
            }
        }
        add_action('template_redirect','icl_lang_sel_nav_ob_start');
        add_action('wp_head','icl_lang_sel_nav_ob_end');
        
        function icl_lang_sel_nav_ob_start(){ 
            if(is_feed()) return;
            ob_start('icl_lang_sel_nav_prepend_css'); 
        }
        
        function icl_lang_sel_nav_ob_end(){ ob_end_flush();}
        
        function icl_lang_sel_nav_prepend_css($buf){
            return preg_replace('#</title>#i','</title>' . PHP_EOL . PHP_EOL . icl_lang_sel_nav_css(false), $buf);
        }    
        
    }
        
    function language_selector($ret_array=false, $template_args=array()){
            global $wpdb, $post, $cat, $tag_id, $wp_query;
            $w_active_languages = $this->get_active_languages();
            $this_lang = $this->this_lang;
            $w_this_lang = $this->get_language_details($this_lang);
                       
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
            if(is_singular()){
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$wp_query->queried_object_id}' AND element_type='post'");                
                $translations = $this->get_element_translations($trid,'post');
            }elseif(is_category()){
                $cat_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$cat} AND taxonomy='category'");
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$cat_id}' AND element_type='category'");                
                $skip_empty = true;
                $translations = $this->get_element_translations($trid,'category', $skip_empty);                
            }elseif(is_tag()){
                $tag_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$tag_id} AND taxonomy='post_tag'");
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$tag_id}' AND element_type='tag'");                
                $skip_empty = true;
                $translations = $this->get_element_translations($trid,'tag', $skip_empty);                
            }elseif(is_archive()){      
                $translations = array();
            }elseif( 'page' == get_option('show_on_front') && ($wp_query->queried_object_id == get_option('page_on_front') || $wp_query->queried_object_id == get_option('page_for_posts')) ){
                $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$wp_query->queried_object_id}' AND element_type='post'");                
                $translations = $this->get_element_translations($trid,'post');                
            }
                                       
            foreach($w_active_languages as $k=>$lang){
                $skip_lang = false;
                if(is_singular() || ($wp_query->queried_object_id && $wp_query->queried_object_id == get_option('page_for_posts'))){                    
                    $this_lang_tmp = $this->this_lang; 
                    $this->this_lang = $lang['code']; 
                    $lang_page_on_front = get_option('page_on_front');                     
                    $lang_page_for_posts = get_option('page_for_posts');                                         
                    $this->this_lang = $this_lang_tmp; 
                    if ( 'page' == get_option('show_on_front') && $translations[$lang['code']]->element_id == $lang_page_on_front ){
                        $lang['translated_url'] = $this->language_url($lang['code']); 
                    }elseif('page' == get_option('show_on_front') && $translations[$lang['code']]->element_id == $lang_page_for_posts){
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
                        $lang['translated_url'] = get_category_link($translations[$lang['code']]->term_id);
                    }else{  
                        if($icl_lso_link_empty){
                            $lang['translated_url'] = $this->language_url($lang['code']);
                        }else{
                            $skip_lang = true;
                        }                        
                    }
                }elseif(is_tag()){                                                                  
                    if(isset($translations[$lang['code']])){
                        $lang['translated_url'] = get_tag_link($translations[$lang['code']]->term_id);
                    }else{
                        if($icl_lso_link_empty){
                            $lang['translated_url'] = $this->language_url($lang['code']);
                        }else{
                            $skip_lang = true;
                        }                        
                    }                    
                }elseif(is_archive() && !is_tag()){
                    global $wp_query, $icl_archive_url_filter_off;
                    $icl_archive_url_filter_off = true;
                    if($wp_query->is_year){
                        $lang['translated_url'] = $this->archive_url(get_year_link( $wp_query->query_vars['year'] ), $lang['code']);
                    }elseif($wp_query->is_month){
                        $lang['translated_url'] = $this->archive_url(get_month_link( $wp_query->query_vars['year'], $wp_query->query_vars['monthnum'] ), $lang['code']);
                    }elseif($wp_query->is_day){
                        $lang['translated_url'] = $this->archive_url(get_day_link( $wp_query->query_vars['year'], $wp_query->query_vars['monthnum'], $wp_query->query_vars['day'] ), $lang['code']);
                    }
                    $icl_archive_url_filter_off = false;
                }elseif(is_search()){
                    $url_glue = strpos($this->language_url($lang['code']),'?')===false ? '?' : '&';
                    $lang['translated_url'] = $this->language_url($lang['code']) . $url_glue . 's=' . $_GET['s'];                                        
                }else{                    
                    if($icl_lso_link_empty || is_home() || is_404() 
                        || ('page' == get_option('show_on_front') && ($wp_query->queried_object_id == get_option('page_on_front') || $wp_query->queried_object_id == get_option('page_for_posts')))
                        ){
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
                        
            if($ret_array){  
                return $w_active_languages;
            }else{
                include ICL_PLUGIN_PATH . '/menu/language-selector.php';
            }
            
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
            $ttid = $this->settings['default_categories'][$lang];
            return $tid = $wpdb->get_var("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id={$ttid} AND taxonomy='category'");
        }
        return false;
    }
    
    function the_category_name_filter($name){            
        if(false === strpos($name, '@')) return $name;
        if(false !== strpos($name, '<a')){
            $name_sh = strip_tags($name);
            $exp = explode('@', $name_sh);
            $name = str_replace($name_sh, trim($exp[0]),$name);
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
        if($dbbt[3]['file'] == realpath(TEMPLATEPATH . '/header.php')){
            $ret = $this->language_url($this->this_lang);                                       
        }else{
            $ret = false;
        }
        return $ret;
    }
    
    function query_vars($public_query_vars){
        $public_query_vars[] = 'lang';
        global $wp_query;        
        //$_GET['lang'] = $this->this_lang;
        $wp_query->query_vars['lang'] = $this->this_lang;                    
        return $public_query_vars;
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
            $l = $wpdb->get_var("SELECT locale FROM {$wpdb->prefix}icl_locale_map WHERE code='{$this->get_default_language()}'");
        }else{
            $l = $wpdb->get_var("SELECT locale FROM {$wpdb->prefix}icl_locale_map WHERE code='{$this->this_lang}'");
        }        
        if($l){
            $locale = $l;
        }    
        // theme localization
        load_textdomain('sitepress', TEMPLATEPATH . '/'.$locale.'.mo');
        return $locale;
    }
        
    function get_locale_file_names(){
        global $wpdb;
        $locales = array();
        $res = $wpdb->get_results("
            SELECT lm.code, locale 
            FROM {$wpdb->prefix}icl_locale_map lm JOIN {$wpdb->prefix}icl_languages l ON lm.code = l.code AND l.active=1");
        foreach($res as $row){
            if($row->code=='en') continue;
            $locales[$row->code] = $row->locale;
        }
        return $locales;        
    }
    
    function set_locale_file_names($locale_file_names_pairs){
        global $wpdb;
        $lfn = $this->get_locale_file_names();
        
        $new = array_diff($locale_file_names_pairs, $lfn);        
        if(!empty($new)){
            foreach($new as $code=>$locale){
                $wpdb->insert($wpdb->prefix.'icl_locale_map', array('code'=>$code,'locale'=>$locale));
            }
        }
        
        $remove = array_diff($lfn, $locale_file_names_pairs);
        if(!empty($remove)){
            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_locale_map WHERE code IN (".join(',', array_map(create_function('$a','return "\'".$a."\'";'),array_keys($remove))).")");
        }
        
        $update = array_diff($locale_file_names_pairs, $remove);
        foreach($update as $code=>$locale){
            $wpdb->update($wpdb->prefix.'icl_locale_map', array('locale'=>$locale), array('code'=>$code));
        }
        
        return true;        
    }
    
    function pre_option_page_on_front(){
        global $wpdb;
        $page_on_front_sc = false;
        $page_on_front = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='page_on_front'");
        $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$page_on_front}' AND element_type='post'");
        if($trid){            
            $translations = $wpdb->get_results("SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid={$trid}");
            foreach($translations as $t){
                if($t->language_code==$this->this_lang){
                    $page_on_front_sc = $t->element_id;
                }
            }        
        }
        return $page_on_front_sc;
    }      
      
    function pre_option_page_for_posts(){
        global $wpdb;
        $page_for_posts_sc = false;
        $page_for_posts = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='page_for_posts'");
        $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$page_for_posts}' AND element_type='post'");
        if($trid){
            $translations = $wpdb->get_results("SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid={$trid}");
            foreach($translations as $t){
                if($t->language_code==$this->this_lang){
                    $page_for_posts_sc = $t->element_id;
                }
            }                    
        }
        return $page_for_posts_sc;
    } 
    
    function verify_home_and_blog_pages_translations(){
        global $wpdb;
        $warn_home = $warn_posts = '';
        if( 'page' == get_option('show_on_front')){
            $page_on_front = get_option('page_on_front');
            $page_home_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$page_on_front} AND element_type='post'");
            $page_home_translations = $this->get_element_translations($page_home_trid, 'post');                 
            $missing_home = array();               
            foreach($this->active_languages as $lang){
             if(!isset($page_home_translations[$lang['code']])){
                 $missing_home[] = '<a href="page-new.php?trid='.$page_home_trid.'&lang='.$lang['code'].'" title="'.__('add translation', 'sitepress').'">' . $lang['display_name'] . '</a>';
             }
            }
            if(!empty($missing_home)){
             $warn_home  = '<div class="icl_form_errors" style="font-weight:bold">';
             $warn_home .= sprintf(__('Your home page does not exist in %s', 'sitepress'), join(', ', $missing_home));
             $warn_home .= '<br />';
             $warn_home .= '<a href="page.php?action=edit&post='.$page_on_front.'">' . __('Edit this page to add translations', 'sitepress') . '</a>';
             $warn_home .= '</div>';
            }

            $page_for_posts = get_option('page_for_posts');
            $page_posts_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$page_for_posts} AND element_type='post'");
            $page_posts_translations = $this->get_element_translations($page_posts_trid, 'post');                 
            $missing_posts = array();               
            foreach($this->active_languages as $lang){
             if(!isset($page_posts_translations[$lang['code']])){
                 $missing_posts[] = '<a href="page-new.php?trid='.$page_posts_trid.'&lang='.$lang['code'].'" title="'.__('add translation', 'sitepress').'">' . $lang['display_name'] . '</a>';
             }
            }
            if(!empty($missing_posts)){
             $warn_posts  = '<div class="icl_form_errors" style="font-weight:bold">';
             $warn_posts .= sprintf(__('Your blog page does not exist in %s', 'sitepress'), join(', ', $missing_posts));
             $warn_posts .= '<br />';
             $warn_posts .= '<a href="page.php?action=edit&post='.$page_for_posts.'">' . __('Edit this page to add translations', 'sitepress') . '</a>';
             $warn_posts .= '</div>';
            }         
        }    
        return array($warn_home, $warn_posts);                     
    }   
    
    // adds the language parameter to the admin post filtering/search
    function restrict_manage_posts(){
        echo '<input type="hidden" name="lang" value="'.$this->this_lang.'">';
    }
    
    // adds the language parameter to the admin pages search
    function restrict_manage_pages(){
        ?>
        <script type="text/javascript">        
        addLoadEvent(function(){jQuery('p.search-box').append('<input type="hidden" name="lang" value="<?php echo $this->this_lang ?>">');});
        </script>        
        <?php
    }
    
    function get_edit_post_link($link, $id){
        global $wpdb;
        $lang = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id={$id} AND element_type='post'");
        if($lang != $this->get_default_language()){
            $link .= '&lang=' . $lang;
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
    
}  
?>