<?php
class SitePress{
   
    private $settings;
    
    function __construct(){
        
        $this->settings = get_option('icl_sitepress_settings');
        
        // Ajax feedback
        if(isset($_POST['icl_ajx_action'])){
            add_action('init', array($this,'ajax_responses'));
        }
        
        // Administration menus
        add_action('admin_menu', array($this, 'administration_menu'));
        
        // Process post requests
        add_action('init',array($this,'process_forms'));           
        
        
    }
    
    function ajax_responses(){
        global $wpdb;
        switch($_POST['icl_ajx_action']){
            case 'set_active_languages':
                $lang_codes = explode(',',$_POST['langs']);
                if($this->set_active_languages($lang_codes)){                    
                    echo '1|';
                    $active_langs = $this->get_active_languages();
                    foreach($active_langs as $lang){
                        $is_default = ($this->get_default_language()==$lang['code']);
                        ?><li <?php if($is_default):?>class="default_language"<?php endif;?>><label><input type="radio" name="default_language" value="<?php echo $lang['code'] ?>" <?php if($is_default):?>checked="checked"<?php endif;?>> <?php echo $lang['english_name'] ?><?php if($is_default):?> (<?php echo __('default') ?>)<?php endif?></label></li><?php
                    } 
                    ?>
                    <?php echo '|'; ?>
                    <?php if(count($active_langs) > 1): ?>
                        <?php foreach($active_langs as $lang): ?>            
                            <li>
                                <label><input class="icl_tr_from" type="checkbox" name="icl_lng_from_<?php echo $lang['code']?>" id="icl_lng_from_<?php echo $lang['code']?>" <?php if($this->get_icl_translation_enabled($lang['code'])): ?>checked="checked"<?php endif?> />
                                <?php printf(__('Translate from %s to these languages','sitepress'), $lang['english_name']) ?></label>
                                <ul id="icl_tr_pair_sub_<?php echo $lang['code'] ?>" <?php if(!$this->get_icl_translation_enabled($lang['code'])): ?>style="display:none"<?php endif?>>
                                <?php foreach($active_langs as $langto): if($lang['code']==$langto['code']) continue; ?>        
                                    <li>
                                        <label><input class="icl_tr_to" type="checkbox" name="icl_lng_to_<?php echo $lang['code']?>_<?php echo $langto['code']?>" id="icl_lng_from_<?php echo $lang['code']?>_<?php echo $langto['code']?>" <?php if($this->get_icl_translation_enabled($lang['code'],$langto['code'])): ?>checked="checked"<?php endif?> />
                                        <?php echo $langto['english_name'] ?></label>
                                    </li>    
                                <?php endforeach; ?>
                                </ul>
                            </li>    
                        <?php endforeach; ?>
                    <?php else:?>
                        <li><?php echo __('After you configure more languages for your blog, the translation options will show here', 'sitepress'); ?></li>
                    <?php endif; ?>
                    <?php                                       
                }else{
                    echo '0';
                }
                break;
            case 'set_default_language':
                $previous_default = $this->get_default_language();
                if($this->set_default_language($_POST['lang'])){
                    echo '1|'.$previous_default;
                }else{
                    echo'0';
                }
                break;
            case 'save_language_pairs':                
                foreach($_POST as $k=>$v){
                    if(0 !== strpos($k,'icl_lng_')) continue;
                    if(0 === strpos($k,'icl_lng_to')){
                        $t = str_replace('icl_lng_to_','',$k);
                        $exp = explode('_',$t);
                        $lang_pairs[$exp[0]][$exp[1]] = 1;
                    }
                }
                $this->settings['language_pairs'] = $lang_pairs; 
                $this->save_settings();
                
                //if the account is configured - update language pairs
                if($this->icl_account_configured()){
                    // prepare language pairs
                    $language_pairs = $lang_pairs;
                    foreach($language_pairs as $k=>$v){
                        $english_fr = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                        foreach($v as $k=>$v){
                            $incr++;
                            $english_to = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                            $lpairs['from_language'.$incr] = $english_fr; 
                            $lpairs['to_language'.$incr] = $english_to;
                        }                    
                    }
                    $data['site_id'] = $this->settings['site_id'];                    
                    $data['accesskey'] = $this->settings['access_key'];                    
                    $data['url'] = get_option('home');
                    $data['title'] = get_option('blogname');
                    $data['description'] = get_option('blogdescription');
                    $data = array_merge($data, $lpairs);
                    
                    require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
                    require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
                    require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
                    
                    $icl_query = new ICanLocalizeQuery();
                    
                    $ret = $icl_query->updateAccount($data);
                    if($ret){
                        echo '1| ('. __('Not updated on ICanLocalize: ') . $ret . ')';
                        break;
                    }
                    
                    
                    
                }
                echo "1|";
                break;
            case 'icl_more_options':
                $this->settings['notify_before_translations']=$_POST['icl_notify_before_translations']?1:0; 
                $this->settings['translate_new_content']=$_POST['icl_translate_new_content']?1:0; 
                $this->settings['interview_translators'] = $_POST['icl_interview_translators'];
                $this->save_settings();
                echo 1; 
               break;
            case 'icl_editor_account':                
                //validate user: username, password, editor
                $u = wp_authenticate($_POST['user']['cms_login'],$_POST['user']['cms_password']);
                if('WP_User' == get_class($u)){
                    $caps = array_keys($u->{$wpdb->prefix . 'capabilities'});
                    if(!in_array('editor',$caps) && !in_array('administrator',$caps)){
                        echo '0|' . __('The user has to be at least an \'Editor\'', 'sitepress');
                    }else{
                        // save settings
                        $this->settings['cms_login'] = $_POST['user']['cms_login'];
                        $this->settings['cms_password'] = $_POST['user']['cms_password'];
                        $this->save_settings();
                        echo '1|';
                    }
                }elseif('WP_Error' == get_class($u)){
                    echo '0|';
                    foreach($u->errors as $e){
                        $errs[] = $e[0];
                    }
                    echo join('<br />', $errs);                    
                }else{
                    echo '0|' . __('Incorrect user', 'sitepress');
                }
                break;
            case 'iclValidateUser':
                echo intval($this->settings['cms_login'] && $this->settings['cms_password']);
                break;
            default:
                echo __('Invalid action','sitepress');                
        }    
        exit;
    }
    
    function administration_menu(){
        add_action('admin_head', array($this,'js_scripts_setup'));
        add_action('admin_head', array($this,'css_setup'));
        add_menu_page(__('SitePress','sitepress'), __('SitePress','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php');        
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Languages','sitepress'), __('Languages','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/languages.php'); 
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Content Translation','sitepress'), __('Content Translation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/content-translation.php'); 
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Comments Translation','sitepress'), __('Comments Translation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/comments-translation.php'); 
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Navigation','sitepress'), __('Navigation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/navigation.php'); 
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('Absolute Links','sitepress'), __('Absolute Links','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/absolute-links.php'); 
    }

    function save_settings(){
        update_option('icl_sitepress_settings', $this->settings);
    }

    function get_settings(){
        return $this->settings;
    }    
    
    function get_active_languages(){
        global $wpdb;
        $res = $wpdb->get_results("
            SELECT id, code, english_name, active 
            FROM {$wpdb->prefix}icl_languages            
            WHERE active=1 ORDER BY major DESC, english_name ASC", ARRAY_A);
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
        $res = $wpdb->get_results("SELECT id, code, english_name, major, active FROM {$wpdb->prefix}icl_languages ORDER BY major DESC, english_name ASC", ARRAY_A);
        $languages = array();
        foreach($res as $r){
            $languages[] = $r;
        }
        return $languages;
    }

    function get_default_language(){        
        return $this->settings['default_language'];
    }
    
    function set_default_language($code){        
        $this->settings['default_language'] = $code;
        $this->save_settings();
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
        $this->settings['translation_enabled'] = true;
        $this->save_settings();
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
        var icl_ajx_url = '<?php echo $_SERVER['REQUEST_URI'] ?>';
        var icl_ajx_saved = '<?php echo __('Data saved') ?>';
        var icl_ajx_error = '<?php echo __('Error: data not saved') ?>';
        var icl_default_mark = '<?php echo __('default') ?>';        
        </script>
        <script type="text/javascript" src="<?php echo ICL_PLUGIN_URL ?>/res/js/scripts.js?v=0.1"></script>
        <script type="text/javascript" src="<?php echo ICL_PLUGIN_URL ?>/res/js/<?php echo $page_basename ?>.js?v=0.1"></script>        
        <?php
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
                    $this->settings['site_id'] = $site_id;
                    $this->settings['access_key'] = $access_key;
                    $this->save_settings();
                    if($user['create_account']==1){
                        $_POST['icl_form_success'] = __('Account created','sitepress');
                    }else{
                        $_POST['icl_form_success'] = __('Project added','sitepress');
                    }
                    
                }
                break;
            case $nonce_icl_logout:
                unset($this->settings['site_id']);
                unset($this->settings['access_key']);
                $this->save_settings();
                $_POST['icl_form_success'] = __('ICanLocalize account details reset','sitepress');            
                break;

            
        }
    }
    
}  
?>
