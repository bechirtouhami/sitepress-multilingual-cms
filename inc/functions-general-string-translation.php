<?php

define('ICL_STRING_TRANSLATION_NOT_TRANSLATED', 0);
define('ICL_STRING_TRANSLATION_COMPLETE', 1);
define('ICL_STRING_TRANSLATION_NEEDS_UPDATE', 2);
define('ICL_STRING_TRANSLATION_PARTIAL', 3);
$icl_st_string_translation_statuses = array(
    ICL_STRING_TRANSLATION_COMPLETE => __('Translation complete','sitepress'),
    ICL_STRING_TRANSLATION_PARTIAL => __('Partial translation','sitepress'),
    ICL_STRING_TRANSLATION_NEEDS_UPDATE => __('Translation needs update','sitepress'),
    ICL_STRING_TRANSLATION_NOT_TRANSLATED => __('Not translated','sitepress')
);


//add_action('admin_menu', 'icl_st_administration_menu');
add_action('plugins_loaded', 'icl_st_init');
add_action('icl_update_active_languages', 'icl_update_string_status_all');

add_action('update_option_blogname', 'icl_st_update_blogname_actions',5,2);
add_action('update_option_blogdescription', 'icl_st_update_blogdescription_actions',5,2);


function icl_st_init(){
    global $sitepress_settings, $sitepress, $wpdb;
    
    if(!isset($sitepress_settings['st']['sw'])){
        $sitepress_settings['st']['sw'] = array(
            'blog_title' => 1,
            'tagline' => 1,
            'widget_titles' => 1,
            'text_widgets' => 1
        );
        $sitepress->save_settings($sitepress_settings); 
        $init_all = true;
    }
    
    if(isset($_POST['iclt_st_sw_save']) || isset($init_all)){
            if(isset($_POST['icl_st_sw']['blog_title']) || isset($init_all)){
                icl_register_string('WP',__('Blog Title','sitepress'), get_option('blogname'));
            }
            if(isset($_POST['icl_st_sw']['tagline']) || isset($init_all)){
                icl_register_string('WP',__('Tagline', 'sitepress'), get_option('blogdescription'));
            }              
            if(isset($_POST['icl_st_sw']['widget_titles']) || isset($init_all)){
                $widget_groups = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget\\_%'");
                foreach($widget_groups as $wg){
                    $name = str_replace('widget_','',$wg->option_name);
                    $value = unserialize($wg->option_value);
                    if(is_array($value)){                        
                        foreach($value as $w){
                            if(!empty($w) && isset($w['title'])){
                                icl_register_string('Widgets', $w['title'], $w['title']);                                    
                            }
                        }
                    }
                }
            }  
            
            if(isset($_POST['icl_st_sw']['text_widgets']) || isset($init_all)){
                $widget_text = get_option('widget_text');
                if(is_array($widget_text)){
                    foreach($widget_text as $w){
                        if(!empty($w) && isset($w['title'])){
                            icl_register_string('Widgets', $w['text'], $w['text']);
                        }
                    }
                }
            }  
                      
            $sitepress_settings['st']['sw'] = $_POST['icl_st_sw'];
            $sitepress->save_settings($sitepress_settings); 
            if(isset($_POST['iclt_st_sw_save'])){
                wp_redirect($_SERVER['REQUEST_URI'].'&updated=true');
            }            
    }
    
    // hook into blog title and tag line
    if($sitepress_settings['st']['sw']['blog_title']){
        add_filter('option_blogname', 'icl_sw_filters_blogname');
    }
    if($sitepress_settings['st']['sw']['tagline']){
        add_filter('option_blogdescription', 'icl_sw_filters_blogdescription');
    }
    if($sitepress_settings['st']['sw']['widget_titles']){
        add_filter('widget_title', 'icl_sw_filters_widget_title');
    }
    if($sitepress_settings['st']['sw']['widget_text']){
        add_filter('widget_text', 'icl_sw_filters_widget_text');
    }
    
}

function icl_st_administration_menu(){
    global $sitepress_settings, $sitepress;
    if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
        return;
    }
    add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/languages.php', __('String translation','sitepress'), __('String translation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/general-string-translation.php');  
}
   
function icl_register_string($context, $name, $value){
    global $wpdb, $sitepress;
    $language = $sitepress->get_default_language();
    $res = $wpdb->get_row("SELECT id, value, status, language FROM {$wpdb->prefix}icl_strings WHERE context='".$wpdb->escape($context)."' AND name='".$wpdb->escape($name)."'");
    if($res){
        $string_id = $res->id;
        $update_string = array();
        if($value != $res->value){
            $update_string['value'] = $value;
        }
        if($language != $res->language){
            $update_string['language'] = $language;
        }
        if(!empty($update_string)){
            $wpdb->update($wpdb->prefix.'icl_strings', $update_string, array('id'=>$string_id));
            $wpdb->update($wpdb->prefix.'icl_string_translations', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('string_id'=>$string_id));
            icl_update_string_status($string_id);
        }        
    }else{
        $string = array(
            'language' => $language,
            'context' => $context,
            'name' => $name,
            'value' => $value,
            'status' => ICL_STRING_TRANSLATION_NOT_TRANSLATED,
        );
        $wpdb->insert($wpdb->prefix.'icl_strings', $string);
        $string_id = $wpdb->insert_id;
    }
    
    
}  

function icl_update_string_status($string_id){
    global $wpdb, $sitepress;    
    $st = $wpdb->get_results("SELECT language, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id={$string_id}");    
    if($st){        
        foreach($st as $t){
            $translations[$t->language] = $t->status;
        }
        $active_languages = $sitepress->get_active_languages();
        
        $_not_translated = true;
        $_complete = true;
        $_partial = false;
        $_needs_update = false;                
        foreach($active_languages as $lang){
            if($lang['code'] == $sitepress->get_current_language()) continue; 
            switch($translations[$lang['code']]){
                case ICL_STRING_TRANSLATION_NOT_TRANSLATED:
                    $_complete = false;                            
                    break;
                case ICL_STRING_TRANSLATION_COMPLETE:
                    $_partial = true;
                    break;
                case ICL_STRING_TRANSLATION_NEEDS_UPDATE:
                    $_needs_update = true;
                    $_complete = false;
                    $_partial = false;
                    break;
                default:
                    $_complete = false;                            
            }                    
        }
        
        if($_complete){
            $status = ICL_STRING_TRANSLATION_COMPLETE;
        }elseif($_partial){
            $status = ICL_STRING_TRANSLATION_PARTIAL;
        }elseif($_needs_update){
            $status = ICL_STRING_TRANSLATION_NEEDS_UPDATE;
        }        
        
    }else{
        $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;        
    }
    
    $wpdb->update($wpdb->prefix.'icl_strings', array('status'=>$status), array('id'=>$string_id));
    return $status;
    
}

function icl_update_string_status_all(){
    global $wpdb;
    $res = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}icl_strings");
    foreach($res as $id){
        icl_update_string_status($id);
    }
}

function icl_unregister_string($context, $name){
    global $wpdb; 
    $string_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_strings WHERE context='".$wpdb->escape($context)."' AND name='".$wpdb->escape($name)."'");       
    if($string_id){
        $wpdb->query("DELETE {$wpdb->prefix}icl_strings FROM WHERE id=" . $string_id);
        $wpdb->query("DELETE {$wpdb->prefix}icl_string_translations FROM WHERE string_id=" . $string_id);
    }
}  

function icl_t($context, $name, $original_value=""){
    global $wpdb, $sitepress, $sitepress_settings;
        
    if(!$original_value) $original_value = $name;
    
    // special case of WP strings

    if($context == 'WP'){
        if($name=='blog_title' && is_null($sitepress_settings['st']['sw']['blog_title']) || $name='tagine' && is_null($sitepress_settings['st']['sw']['tagline'])){
            $value = $original_value;
        }
    }elseif($context == 'Widgets'){
        if($name=='widget_titles' && is_null($sitepress_settings['st']['sw']['widget_titles']) || $name='text_widgets' && is_null($sitepress_settings['st']['sw']['text_widgets'])){
            $value = $original_value;
        }        
    }else{
        if($sitepress->get_current_language() == $sitepress->get_default_language()){
            $value = $wpdb->get_var("SELECT value FROM {$wpdb->prefix}icl_strings  WHERE context='".$wpdb->escape($context)."' AND name='".$wpdb->escape($name)."'");
            if(!$value){
                trigger_error(__('String not found','sitepress'), E_USER_WARNING);
                $value = $original_value;
            }
        }else{
            $res = $wpdb->get_row("
                SELECT s.value AS string_value, st.value AS string_translation_value, st.status
                FROM {$wpdb->prefix}icl_string_translations st            
                    JOIN {$wpdb->prefix}icl_strings s ON st.string_id = s.id
                WHERE s.context='".$wpdb->escape($context)."' 
                    AND s.name='".$wpdb->escape($name)."'
                    AND st.language = '{$sitepress->get_current_language()}'             
            ");
            if(!$res){
                trigger_error(__('String not found','sitepress'), E_USER_WARNING);
                $value = $original_value;
            }else{
                if($res->string_translation_value && $res->status == ICL_STRING_TRANSLATION_COMPLETE){
                    $value = $res->string_translation_value;
                }else{
                    $value = $original_value;
                }
            }        
        }
       
    }
         
    return $value;            
}

function icl_add_string_translation($string_id, $language, $value, $status = false){
    global $wpdb;
    
    $res = $wpdb->get_row("SELECT id, value, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id='".$wpdb->escape($string_id)."' AND language='".$wpdb->escape($language)."'");
    if($res){
        $st_id = $res->id;
        $st_update = array();
        if($value != $res->value){
            $st_update['value'] = $value;
        }
        if($status){
            $st_update['status'] = $status;
        }else{
            $st_update['status'] = 2;
        }        
        if(!empty($st_update)){
            $wpdb->update($wpdb->prefix.'icl_string_translations', $st_update, array('id'=>$st_id));
        }        
    }else{
        if(!$status){
            $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
        }
        $st = array(
            'string_id' => $string_id,
            'language'  => $language,
            'value'     => $value,
            'status'    => $status    
        );
        $wpdb->insert($wpdb->prefix.'icl_string_translations', $st);
        $st_id = $wpdb->insert_id;
    }    
    
    icl_update_string_status($string_id);
    
    return $st_id;
}

function icl_get_string_translations($offset=0){
    global $wpdb, $sitepress, $sitepress_settings, $wp_query, $icl_st_string_translation_statuses;
    $limit = 10;
    
    $extra_cond = "";
    if(isset($sitepress_settings['st']['filter']) && $sitepress_settings['st']['filter'] != -1){
        $extra_cond .= " AND s.status = " . $sitepress_settings['st']['filter'];
    }
    
    if(!isset($_GET['paged'])) $_GET['paged'] = 1;
    $offset = ($_GET['paged']-1)*$limit;
    
    $string_translations = array();
    $res = mysql_query("
        SELECT SQL_CALC_FOUND_ROWS s.id AS string_id, s.language AS string_language, s.context AS string_context, s.name AS string_name, s.value AS string_value, s.status AS string_status,
                st.id AS string_translation_id, st.language AS string_translation_language, st.status AS string_translation_status, st.value AS string_translation_value  
        FROM  {$wpdb->prefix}icl_strings s 
        LEFT JOIN  {$wpdb->prefix}icl_string_translations st ON s.id = st.string_id
        WHERE 
            s.language = '".$sitepress->get_default_language()."'
            AND (st.language <> '".$sitepress->get_default_language()."' OR st.language IS NULL) 
            {$extra_cond}
        ORDER BY string_id DESC, string_translation_language ASC     
        LIMIT {$offset},{$limit}
    ");
    
    $wp_query->found_posts = $wpdb->get_var("SELECT FOUND_ROWS()");
    $wp_query->query_vars['posts_per_page'] = $limit;
    $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit);
    
    if($res){    
        
        while($row = mysql_fetch_array($res, MYSQL_ASSOC)){
            if(!isset($string_translations[$row['string_id']]['context'])){
                $string_translations[$row['string_id']]['context'] = $row['string_context'];
            }
            if(!isset($string_translations[$row['string_id']]['name'])){
                $string_translations[$row['string_id']]['name'] = $row['string_name'];
            }        
            if(!isset($string_translations[$row['string_id']]['value'])){
                $string_translations[$row['string_id']]['value'] = $row['string_value'];
            }  
            if(!isset($string_translations[$row['string_id']]['language'])){
                $string_translations[$row['string_id']]['language'] = $row['string_language'];
            }                      
            if(!isset($string_translations[$row['string_id']]['status'])){
                if(isset($icl_st_string_translation_statuses[$row['string_status']])){
                    $string_translations[$row['string_id']]['status'] = $icl_st_string_translation_statuses[$row['string_status']];
                }else{
                    $string_translations[$row['string_id']]['status'] = $icl_st_string_translation_statuses[ICL_STRING_TRANSLATION_NOT_TRANSLATED];
                }
            }                      
            if(isset($row['string_translation_language'])){
                $string_translations[$row['string_id']]['translations'][$row['string_translation_language']] = array(
                    'id' => $row['string_translation_id'],
                    'status' => $row['string_translation_status'],
                    'value' => $row['string_translation_value'],
                );      
            }
        }
        
        $active_languages = $sitepress->get_active_languages();
            
    }  
    return $string_translations;
}

function icl_sw_filters_blogname($val){
    return icl_t('WP', 'Blog Title', $val);
}

function icl_sw_filters_blogdescription($val){
    return icl_t('WP', 'Tagline', $val);
}

function icl_sw_filters_widget_title($val){
    //return icl_t('Widgets', 'categories ' . $val , $val);
    return icl_t('Widgets', 'widget_title_' . $val , $val);
    
}

function icl_sw_filters_widget_text($val){
    return icl_t('Widgets', 'widget_text_' . $val , $val);
}

function icl_st_update_string_actions($context, $name, $old_value, $new_value){
    global $wpdb;
    if($new_value != $old_value){        
        $string = $wpdb->get_row("SELECT id, value, status FROM {$wpdb->prefix}icl_strings WHERE context='{$context}' AND name='{$name}'");    
        $wpdb->update($wpdb->prefix . 'icl_strings', array('value'=>$new_value), array('id'=>$string->id));
        if($string->status == ICL_STRING_TRANSLATION_COMPLETE || $string->status == ICL_STRING_TRANSLATION_PARTIAL){
            $wpdb->update($wpdb->prefix . 'icl_string_translations', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('string_id'=>$string->id));
            $wpdb->update($wpdb->prefix . 'icl_strings', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('id'=>$string->id));
        }
    }        
}

function icl_st_update_blogname_actions($old, $new){
    icl_st_update_string_actions('WP', 'Blog Title', $old, $new);
}

function icl_st_update_blogdescription_actions($old, $new){
    icl_st_update_string_actions('WP', 'Tagline', $old, $new);
}

?>