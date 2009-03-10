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
        $resp = array();
        $old_active_languages_count = count($sitepress->get_active_languages($lang_codes));
        $lang_codes = explode(',',$_POST['langs']);
        if($sitepress->set_active_languages($lang_codes)){                    
            $resp[0] = 1;
            $active_langs = $sitepress->get_active_languages();
            $iclresponse ='';
            $default_categories = $sitepress->get_default_categories();            
            $default_category_main = $wpdb->get_var("SELECT name FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tx ON t.term_id=tx.term_id
                WHERE term_taxonomy_id='{$default_categories[$sitepress->get_default_language()]}' AND taxonomy='category'");            
            $default_category_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$default_categories[$sitepress->get_default_language()]} AND element_type='category'");
            foreach($active_langs as $lang){
                $is_default = ($sitepress->get_default_language()==$lang['code']);
                $iclresponse .= '<li ';
                if($is_default) $iclresponse .= 'class="default_language"';
                $iclresponse .= '><label><input type="radio" name="default_language" value="' . $lang['code'] .'" ';
                if($is_default) $iclresponse .= 'checked="checked"';
                $iclresponse .= '>' . $lang['display_name'];
                if($is_default) $iclresponse .= '('. __('default') . ')';
                $iclresponse .= '</label></li>';                
                
                if(!in_array($lang['code'],array_keys($default_categories))){
                   // Create category for language
                   // add it to defaults                   
                   $tr_cat = $default_category_main . ' @' . $lang['code'];
                   $tr_cat_san = sanitize_title_with_dashes($default_category_main . '-' . $lang['code']); 
                   $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->terms} WHERE name='{$tr_cat}'");
                   if(!$term_id){
                       $wpdb->query("INSERT INTO {$wpdb->terms}(name, slug) VALUES('{$tr_cat}','{$tr_cat_san}') ON DUPLICATE KEY UPDATE slug = CONCAT(slug,'".rand(1,1000)."')");
                       $term_id = mysql_insert_id();                       
                   }
                   $term_taxonomy_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$term_id} AND taxonomy='category'");
                   if(!$term_taxonomy_id){
                        $wpdb->query("INSERT INTO {$wpdb->term_taxonomy}(term_id, taxonomy) VALUES('{$term_id}','category')") ;
                        $term_taxonomy_id = mysql_insert_id();                        
                   }
                   $default_categories[$lang['code']] = $term_taxonomy_id;                   
                   $wpdb->query("INSERT INTO {$wpdb->prefix}icl_translations(element_id,element_type,trid,language_code,source_language_code) 
                    VALUES('{$term_taxonomy_id}','category','{$default_category_trid}','{$lang['code']}','{$sitepress->get_default_language()}')");
                }
            } 
            $sitepress->set_default_categories($default_categories) ;                        
            $iclresponse .= $default_blog_category;
            $resp[1] = $iclresponse;
            // response 1 - blog got more than 2 languages; -1 blog reduced to 1 language; 0 - no change            
            if(count($lang_codes) > 1){
                $resp[2] = 1;
            }elseif($old_active_languages_count > 1 && count($lang_codes) < 2){
                $resp[2] = -1;
            }else{
                $resp[2] = 0;
            }  
        }else{
            $resp[0] = 0;
        }
        echo join('|',$resp);
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
    case 'icl_save_language_negotiation_type':
        $iclsettings['language_negotiation_type'] = $_POST['icl_language_negotiation_type'];
        $sitepress->save_settings($iclsettings);
        echo 1;
        break;
    case 'icl_save_language_switcher_options':
        $iclsettings['icl_lso_header'] = intval($_POST['icl_lso_header']);
        $iclsettings['icl_lso_link_empty'] = intval($_POST['icl_lso_link_empty']);
        $sitepress->save_settings($iclsettings);    
        echo 1;
        break;
    case 'icl_lang_more_options':
        $iclsettings['language_home'] = $_POST['icl_language_home'];
        $sitepress->save_settings($iclsettings);
        echo 1; 
       break;        
    default:
        echo __('Invalid action','sitepress');                
}    
exit;
  
?>
