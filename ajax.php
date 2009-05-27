<?php
if (file_exists ('../../../wp-load.php'))
    include ('../../../wp-load.php');
else
    include ('../../../wp-config.php');

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Sat, 16 Aug 1980 05:00:00 GMT"); 

if(!isset($sitepress) && class_exists('SitePress')) $sitepress = new SitePress();

function update_icl_account(){
    global $sitepress, $wpdb;

    //if the account is configured - update language pairs
    if($sitepress->icl_account_configured()){
        $iclsettings = $sitepress->get_settings();

        // prepare language pairs
        
        $language_pairs = $iclsettings['language_pairs'];
        $lang_pairs = array();
        foreach($language_pairs as $k=>$v){
            $english_fr = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
            foreach($v as $k=>$v){
                $incr++;
                $english_to = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                $lpairs['from_language'.$incr] = $english_fr; 
                $lpairs['to_language'.$incr] = $english_to;
            }                    
        }
        $data['site_id'] = $iclsettings['site_id'];                    
        $data['accesskey'] = $iclsettings['access_key'];
        $data['create_account'] = 0;
        $data['url'] = get_option('home');
        $data['title'] = get_option('blogname');
        $data['description'] = get_option('blogdescription');
        $data['project_kind'] = $iclsettings['website_kind'];
        $data['pickup_type'] = $iclsettings['translation_pickup_method'];
        $data['interview_translators'] = $iclsettings['interview_translators'];

        $notifications = 0;
        if ($iclsettings['icl_notify_complete']){
            $notifications += 1;
        }
        if ($iclsettings['icl_alert_delay']){
            $notifications += 2;
        }
        $data['notifications'] = $notifications;
        
        $data = array_merge($data, $lpairs);
        
        require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
        require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
        require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
        
        $icl_query = new ICanLocalizeQuery();
        
        return $icl_query->updateAccount($data);
    } else {
        return 0;
    }

        
}

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
        if($response = $sitepress->set_default_language($_POST['lang'])){
            echo '1|'.$previous_default.'|';
        }else{
            echo'0||' ;
        }
        if(1 === $response){
            echo __('Wordpress language file (.mo) is missing. Keeping existing display language.', 'sitepress');
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
        
        $ret = update_icl_account();
        if($ret){
            echo '1| ('. __('Not updated on ICanLocalize: ') . $ret . ')';
            break;
        }
        echo "1|";
        break;
    case 'icl_more_options':
        $iclsettings['website_kind'] = $_POST['icl_website_kind'];
        $iclsettings['interview_translators'] = $_POST['icl_interview_translators'];
        $iclsettings['translation_pickup_method'] = $_POST['icl_delivery_method'];        
        $iclsettings['translated_document_status'] = $_POST['icl_translation_document_status'];        
        $iclsettings['icl_alert_delay'] = intval($_POST['icl_alert_delay']);
        $iclsettings['icl_notify_complete'] = intval($_POST['icl_notify_complete']);
        $iclsettings['icl_remote_management'] = intval($_POST['icl_remote_management']);
        $sitepress->save_settings($iclsettings);

        $ret = update_icl_account();
        if($ret){
            echo '1| ('. __('Not updated on ICanLocalize: ') . $ret . ')';
            break;
        }
        echo 1; 
       break;
    case 'icl_save_language_negotiation_type':
        $iclsettings['language_negotiation_type'] = $_POST['icl_language_negotiation_type'];
        if($_POST['language_domains']){
            $iclsettings['language_domains'] = $_POST['language_domains'];
        }        
        $sitepress->save_settings($iclsettings);
        echo 1;
        break;
    case 'icl_save_language_switcher_options':

        //$iclsettings['icl_lso_header'] = intval($_POST['icl_lso_header']);
        $iclsettings['icl_lso_link_empty'] = intval($_POST['icl_lso_link_empty']);
        $iclsettings['icl_lso_link_empty'] = intval($_POST['icl_lso_link_empty']);
        
        $iclsettings['icl_lso_flags'] = intval($_POST['icl_lso_flags']);
        $iclsettings['icl_lso_native_lang'] = intval($_POST['icl_lso_native_lang']);
        $iclsettings['icl_lso_display_lang'] = intval($_POST['icl_lso_display_lang']);
        if(!$iclsettings['icl_lso_flags'] && !$iclsettings['icl_lso_native_lang'] && !$iclsettings['icl_lso_display_lang']){
            echo '0|';
            echo __('At least one of the language switcher style options needs to be checked', 'sitepress');    
        }else{
            $sitepress->save_settings($iclsettings);    
            echo 1;
        }                
        break;
    case 'icl_lang_more_options':
        $iclsettings['language_home'] = $_POST['icl_language_home'];
        $iclsettings['sync_page_ordering'] = $_POST['icl_sync_page_ordering'];        
        $iclsettings['sync_page_parent'] = $_POST['icl_sync_page_parent'];        
        $sitepress->save_settings($iclsettings);
        echo 1; 
       break;        
    case 'language_domains':
        $active_languages = $sitepress->get_active_languages();
        $default_language = $sitepress->get_default_language();
        $iclsettings = $sitepress->get_settings();
        $language_domains = $iclsettings['language_domains'];        
        echo '<table class="language_domains">';
        foreach($active_languages as $lang){
            $home = get_option('home');
            if($lang['code']!=$default_language){
                if(isset($language_domains[$lang['code']])){
                    $sugested_url = $language_domains[$lang['code']];
                }else{
                    $url_parts = parse_url($home);                    
                    $exp = explode('.' , $url_parts['host']);                    
                    if(count($exp) < 3){
                        $sugested_url = $url_parts['scheme'] . '://' . $lang['code'] . '.' . $url_parts['host'] . $url_parts['path'];    
                    }else{
                        array_shift($exp);                        
                        $sugested_url = $url_parts['scheme'] . '://' . $lang['code'] . '.' . join('.' , $exp) . $url_parts['path'];    
                    }            
                }
            }
            
            echo '<tr>';
            echo '<td>' . $lang['display_name'] . '</td>';
            if($lang['code']==$default_language){
                echo '<td id="icl_ln_home">' . $home . '</td>';
                echo '<td>&nbsp;</td>';
                echo '<td>&nbsp;</td>';
            }else{
                echo '<td><input type="text" id="language_domain_'.$lang['code'].'" name="language_domains['.$lang['code'].']" value="'.$sugested_url.'" size="40" /></td>';
                echo '<td id="icl_validation_result_'.$lang['code'].'"><label><input class="validate_language_domain" type="checkbox" name="validate_language_domains[]" value="'.$lang['code'].'" checked="checked" /> ' . __('Validate on save', 'sitepress') . '</label></td><td><span id="ajx_ld_'.$lang['code'].'"></span></td>';
            }                        
            echo '</tr>';
        }
        echo '</table>';
        break;
    case 'validate_language_domain':
        if(!class_exists('WP_Http')){
            include_once ICL_PLUGIN_PATH . '/lib/http.php';
        }
        if(false === strpos($_POST['url'],'?')){$url_glue='?';}else{$url_glue='&';}
        $url = $_POST['url'] . $url_glue . '____icl_validate_domain=1';
        $client = new WP_Http();
        $response = $client->request($url, 'timeout=15');
        if(!is_wp_error($response) && ($response['response']['code']=='200') && ($response['body'] == '<!--'.get_option('home').'-->')){
            echo 1;
        }else{
            echo 0;
        }                
        break;
    case 'icl_navigation_form':        
        $iclsettings = $sitepress->get_settings();
        $iclsettings['modules']['cms-navigation']['page_order'] = $_POST['icl_navigation_page_order'];
        $iclsettings['modules']['cms-navigation']['show_cat_menu'] = $_POST['icl_navigation_show_cat_menu'];
        $iclsettings['modules']['cms-navigation']['cat_menu_title'] = $_POST['icl_navigation_cat_menu_title'];
        $iclsettings['modules']['cms-navigation']['heading_start'] = $_POST['icl_navigation_heading_start'];
        $iclsettings['modules']['cms-navigation']['heading_end'] = $_POST['icl_navigation_heading_end'];
        $sitepress->save_settings($iclsettings);
        echo '1|';
        break;
        
    case 'send_translation_request':
        $post_id = $_POST['post_id'];
        $target_languages = explode(',', $_POST['target_languages']);
        $post_type = $_POST['type'];
        echo icl_translation_send_post($post_id, $target_languages, $post_type);
        break;
    case 'get_translator_status':
        // check what languages we have translators for.
        require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
        require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
        require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
        
        $iclsettings = $sitepress->get_settings();
        $icl_query = new ICanLocalizeQuery($iclsettings['site_id'], $iclsettings['access_key']);
        $res = $icl_query->get_website_details();
        
        if(isset($res['translation_languages']['translation_language'])){
            $translation_languages = $res['translation_languages']['translation_language'];
            if(!isset($translation_languages[0])){
                $target = $translation_languages;
                $translation_languages = array(0 => $target);
            }
            foreach($translation_languages as $lang){
                $target[] = array('from' => $sitepress->get_language_code($lang['attr']['from_language_name']),
                                  'to' => $sitepress->get_language_code($lang['attr']['to_language_name']),
                                  'have_translators' => $lang['attr']['have_translators']);
            }
            $iclsettings['icl_lang_status'] = $target;
        }
        
        if(isset($res['client']['attr'])){
            $iclsettings['icl_balance'] = $res['client']['attr']['balance'];
        }
        $sitepress->save_settings($iclsettings);
        break;
        
    default:
        echo __('Invalid action','sitepress');                
}    
exit;
  
?>
