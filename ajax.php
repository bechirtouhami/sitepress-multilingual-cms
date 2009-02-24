<?php
if (file_exists ('../../../wp-load.php'))
    include ('../../../wp-load.php');
else
    include ('../../../wp-config.php');

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Sat, 16 Aug 1980 05:00:00 GMT"); 

if(!isset($sitepress)) $sitepress = new SitePress();

switch($_REQUEST['icl_ajx_action']){
    case 'set_active_languages':
        $lang_codes = explode(',',$_POST['langs']);
        if($sitepress->set_active_languages($lang_codes)){                    
            echo '1|';
            $active_langs = $sitepress->get_active_languages();
            foreach($active_langs as $lang){
                $is_default = ($sitepress->get_default_language()==$lang['code']);
                ?><li <?php if($is_default):?>class="default_language"<?php endif;?>><label><input type="radio" name="default_language" value="<?php echo $lang['code'] ?>" <?php if($is_default):?>checked="checked"<?php endif;?>> <?php echo $lang['display_name'] ?><?php if($is_default):?> (<?php echo __('default') ?>)<?php endif?></label></li><?php
            } 
            ?>
            <?php echo '|'; ?>
            <?php                                       
        }else{
            echo '0';
        }
        break;
    case 'set_default_language':
        $previous_default = $sitepress->get_default_language();
        if($sitepress->set_default_language($_POST['lang'])){
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
        $iclsettings['language_pairs'] = $lang_pairs; 
        $sitepress->save_settings($iclsettings);
        
        //if the account is configured - update language pairs
        if($sitepress->icl_account_configured()){
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
            $iclsettings = $sitepress->get_settings();
            $data['site_id'] = $iclsettings['site_id'];                    
            $data['accesskey'] = $iclsettings['access_key'];                    
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
        $iclsettings['notify_before_translations']=$_POST['icl_notify_before_translations']?1:0; 
        $iclsettings['translate_new_content']=$_POST['icl_translate_new_content']?1:0; 
        $iclsettings['interview_translators'] = $_POST['icl_interview_translators'];
        $sitepress->save_settings($iclsettings);
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
                $iclsettings['cms_login'] = $_POST['user']['cms_login'];
                $iclsettings['cms_password'] = $_POST['user']['cms_password'];
                $sitepress->save_settings($iclsettings);
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
        $iclsettings = $sitepress->get_settings();
        echo intval($iclsettings['cms_login'] && $iclsettings['cms_password']);
        break;
    default:
        echo __('Invalid action','sitepress');                
}    
exit;
  
?>
