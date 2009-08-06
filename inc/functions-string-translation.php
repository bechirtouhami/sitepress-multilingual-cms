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
    global $sitepress_settings, $sitepress, $wpdb, $icl_st_err_str;
    if ( get_magic_quotes_gpc() ){
        $_POST = stripslashes_deep( $_POST );         
    }
                         
    if(!isset($sitepress_settings['existing_content_language_verified'])){
        return;
    }          
    
    if(!isset($sitepress_settings['st']['sw'])){
        $sitepress_settings['st']['sw'] = array(
            'blog_title' => 1,
            'tagline' => 1,
            'widget_titles' => 1,
            'text_widgets' => 1,
            'theme_texts' => 1
        );
        $sitepress->save_settings($sitepress_settings); 
        $init_all = true;
    }
    
    if(!isset($sitepress_settings['st']['strings_per_page'])){
        $sitepress_settings['st']['strings_per_page'] = 10;
        $sitepress->save_settings($sitepress_settings); 
    }elseif(isset($_GET['strings_per_page']) && $_GET['strings_per_page'] > 0){
        $sitepress_settings['st']['strings_per_page'] = $_GET['strings_per_page'];
        $sitepress->save_settings($sitepress_settings); 
    }
    
    if(isset($_POST['iclt_st_sw_save']) || isset($init_all)){
            if(isset($_POST['icl_st_sw']['blog_title']) || isset($init_all)){
                icl_register_string('WP',__('Blog Title','sitepress'), get_option('blogname'));
            }
            if(isset($_POST['icl_st_sw']['tagline']) || isset($init_all)){
                icl_register_string('WP',__('Tagline', 'sitepress'), get_option('blogdescription'));
            }              
            if(isset($_POST['icl_st_sw']['widget_titles']) || isset($init_all)){
                __icl_st_init_register_widget_titles();
            }  
            
            if(isset($_POST['icl_st_sw']['text_widgets']) || isset($init_all)){
                // create a list of active widgets
                $active_text_widgets = array();
                $widgets = (array)get_option('sidebars_widgets');
                foreach($widgets as $k=>$w){             
                    if('wp_inactive_widgets' != $k && $k != 'array_version'){
                        foreach($widgets[$k] as $v){
                            if(preg_match('#text-([0-9]+)#i',$v, $matches)){
                                $active_text_widgets[] = $matches[1];
                            }                            
                        }
                    }
                }
                                                                
                $widget_text = get_option('widget_text');
                if(is_array($widget_text)){
                    foreach($widget_text as $k=>$w){
                        if(!empty($w) && isset($w['title']) && in_array($k, $active_text_widgets)){
                            icl_register_string('Widgets', 'widget body - ' . md5(apply_filters('widget_text',$w['text'])), apply_filters('widget_text',$w['text']));                            
                        }
                    }
                }
            }  
                    
            if(isset($_POST['iclt_st_sw_save'])){
                $sitepress_settings['st']['sw'] = $_POST['icl_st_sw'];
                $sitepress->save_settings($sitepress_settings); 
                wp_redirect($_SERVER['REQUEST_URI'].'&updated=true');
            }            
    }
    
    // handle po file upload
    if(isset($_POST['icl_po_upload'])){                
        global $icl_st_po_strings;
        if($_FILES['icl_po_file']['size']==0){
            $icl_st_err_str = __('File upload error', 'sitepress');
        }else{
            $lines = file($_FILES['icl_po_file']['tmp_name']);
            $icl_st_po_strings = array();
            
            $fuzzy = 0;    
            for($k = 0; $k < count($lines); $k++){
                if(0 === strpos($lines[$k], '#, fuzzy')){
                    $fuzzy = 1;
                    $k++;
                }                                                        
                $int = preg_match('#msgid "(.+)"#im',trim($lines[$k]), $matches);
                if($int){
                    $string = $matches[1];
                    $int = preg_match('#msgstr "(.+)"#im',trim($lines[$k+1]),$matches);
                    if($int){
                        $translation = $matches[1];
                    }else{
                        $translation = "";
                    }
                    
                    $icl_st_po_strings[] = array(     
                        'string' => $string,
                        'translation' => $translation,
                        'fuzzy' => $fuzzy
                    );
                    $k++;                        
                    
                }
                if(!trim($lines[$k])){
                    $fuzzy = 0;    
                }
            }            
            if(empty($icl_st_po_strings)){
                $icl_st_err_str = __('No string found', 'sitepress');
            }
        }
    }
    elseif(isset($_POST['icl_st_save_strings'])){
        $arr = array_intersect_key($_POST['icl_strings'], array_flip($_POST['icl_strings_selected']));
        //$arr = array_map('html_entity_decode', $arr);         
        if(isset($_POST['icl_st_po_language'])){
            $arr_t = array_intersect_key($_POST['icl_translations'], array_flip($_POST['icl_strings_selected']));
            $arr_f = array_intersect_key($_POST['icl_fuzzy'], array_flip($_POST['icl_strings_selected']));
            //$arr_t = array_map('html_entity_decode', $arr_t);         
        }   
        foreach($arr as $k=>$string){
            $string_id = icl_register_string($_POST['icl_st_strings_for'] . ' ' . $_POST['icl_st_domain_name'], md5($string), $string);
            if($string_id && isset($_POST['icl_st_po_language'])){
                if($arr_t[$k] != ""){
                    if($arr_f[$k]){
                        $_status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
                    }else{
                        $_status = ICL_STRING_TRANSLATION_COMPLETE;
                    }
                    icl_add_string_translation($string_id, $_POST['icl_st_po_language'], $arr_t[$k], ICL_STRING_TRANSLATION_COMPLETE);
                    icl_update_string_status($string_id);
                }                
            }            
        }
        
    }
    
    //handle po export
    if(isset($_POST['icl_st_pie_e'])){
        //force some filters
        $_GET['show_results']='all';
        if($_POST['icl_st_e_context']){
            $_GET['context'] = $_POST['icl_st_e_context'];
        }

        $_GET['translation_language'] = $_POST['icl_st_e_language'];
        $strings = icl_get_string_translations();
        if(!empty($strings)){
            $po = icl_st_generate_po_file($strings);
        }else{
            $po = "";  
        }
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$_GET['translation_language'].'.po'.";");
        header("Content-Length: ". strlen($po));
        echo $po;
        exit(0);
    }
    
    // handle string transkation request preview
    elseif(isset($_POST['icl_st_action']) && $_POST['icl_st_action'] == 'preview'){
        global $icl_st_preview_strings;
        $_POST = stripslashes_deep($_POST);
        if($_POST['strings']=='need'){
            $icl_st_preview_strings = $wpdb->get_results("SELECT value FROM {$wpdb->prefix}icl_strings WHERE status <> " . ICL_STRING_TRANSLATION_COMPLETE);
        }else{
            $icl_st_preview_strings = $wpdb->get_results("SELECT value FROM {$wpdb->prefix}icl_strings WHERE id IN (".$wpdb->escape($_POST['strings']).")");
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
    if($sitepress_settings['st']['sw']['text_widgets']){
        add_filter('widget_text', 'icl_sw_filters_widget_text');
    }
    if($sitepress_settings['st']['sw']['theme_texts'] && $sitepress_settings['theme_localization_type']==1){
        add_filter('gettext', 'icl_sw_filters_gettext', 9, 3);
    }
    
    $widget_groups = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget\\_%'");
    foreach($widget_groups as $w){
        add_action('update_option_' . $w->option_name, 'icl_st_update_widget_title_actions', 5, 2);
    }
    
    add_action('update_option_widget_text', 'icl_st_update_text_widgets_actions', 5, 2);
    add_action('update_option_sidebars_widgets', '__icl_st_init_register_widget_titles');
    
    if($icl_st_err_str){
        add_action('admin_notices', 'icl_st_admin_notices');
    }
    
}

function __icl_st_init_register_widget_titles(){
    global $wpdb;        
    
    // create a list of active widgets
    $active_widgets = array();
    $widgets = (array)get_option('sidebars_widgets');    
    
    foreach($widgets as $k=>$w){                     
        if('wp_inactive_widgets' != $k && $k != 'array_version'){
            foreach($widgets[$k] as $v){                
                $active_widgets[] = $v;
            }
        }
    }    
    foreach($active_widgets as $aw){        
        $int = preg_match('#-([0-9]+)$#i',$aw, $matches);
        if($int){
            $suffix = $matches[1];
        }else{
            $suffix = 1;
        }
        $name = preg_replace('#-[0-9]+#','',$aw);                
        //if($name == 'rss-links') $name = 'rss';
        
        //$w = $wpdb->get_row("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name = 'widget_{$name}'");
        //$value = unserialize($w->option_value);
        $value = get_option("widget_".$name);
        if(isset($value[$suffix]['title']) && $value[$suffix]['title']){
            $w_title = $value[$suffix]['title'];     
        }else{
            $w_title = __icl_get_default_widget_title($aw);
            $value[$suffix]['title'] = $w_title;
            update_option("widget_".$name, $value);
        }
        
        if($w_title){            
            icl_register_string('Widgets', 'widget title - ' . md5(apply_filters('widget_title',$w_title)), apply_filters('widget_title',$w_title));                                    
            
        }
    }    
}

function __icl_get_default_widget_title($id){
    if(preg_match('#archives(-[0-9]+)?$#i',$id)){                        
        $w_title = 'Archives';
    }elseif(preg_match('#categories(-[0-9]+)?$#i',$id)){
        $w_title = 'Categories';
    }elseif(preg_match('#calendar(-[0-9]+)?$#i',$id)){
        $w_title = 'Calendar';
    }elseif(preg_match('#links(-[0-9]+)?$#i',$id)){
        $w_title = 'Links';
    }elseif(preg_match('#meta(-[0-9]+)?$#i',$id)){
        $w_title = 'Meta';
    }elseif(preg_match('#pages(-[0-9]+)?$#i',$id)){
        $w_title = 'Pages';
    }elseif(preg_match('#recent-posts(-[0-9]+)?$#i',$id)){
        $w_title = 'Recent Posts';
    }elseif(preg_match('#recent-comments(-[0-9]+)?$#i',$id)){
        $w_title = 'Recent Comments';
    }elseif(preg_match('#rss-links(-[0-9]+)?$#i',$id)){
        $w_title = 'RSS';
    }elseif(preg_match('#search(-[0-9]+)?$#i',$id)){
        $w_title = 'Search';
    }elseif(preg_match('#tag-cloud(-[0-9]+)?$#i',$id)){
        $w_title = 'Tag Cloud';
    }else{
        $w_title = false;
    }  
    return $w_title;  
}

function icl_st_administration_menu(){
    global $sitepress_settings, $sitepress;
    if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
        return;
    }
    add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('String translation','sitepress'), __('String translation','sitepress'), 'manage_options', basename(ICL_PLUGIN_PATH).'/menu/string-translation.php');  
}
   
function icl_register_string($context, $name, $value){
    global $wpdb, $sitepress, $sitepress_settings;
    // if the default language is not set up return without doing anything
    if(!isset($sitepress_settings['existing_content_language_verified'])){
        return;
    }       
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
        if(!empty($value) && trim($value)){
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
    return $string_id; 
}  

function icl_rename_string($context, $old_name, $new_name){
    global $wpdb;
    $wpdb->update($wpdb->prefix.'icl_strings', array('name'=>$new_name), array('context'=>$context, 'name'=>$old_name));
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
                    $_complete = true;                            
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
        }else{
            $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
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

function __icl_unregister_string_multi($arr){
    global $wpdb; 
    $str = join(',', array_map('intval', $arr));
    $wpdb->query("
        DELETE s.*, t.* FROM {$wpdb->prefix}icl_strings s LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
        WHERE s.id IN ({$str})");
}  

function icl_t($context, $name, $original_value=false, &$has_translation=null){
    global $wpdb, $sitepress, $sitepress_settings;
    
    // if the default language is not set up return
    if(!isset($sitepress_settings['existing_content_language_verified'])){
        if(isset($has_translation)) $has_translation = false;
        return $original_value !== false ? $original_value : $name;
    }   
       
    $current_language = $sitepress->get_current_language();
    $default_language = $sitepress->get_default_language();
    if($current_language == $default_language && $original_value){
        
        $ret_val = $original_value;
        if(isset($has_translation)) $has_translation = false;
        
    }else{
        
        $result = icl_t_cache_lookup($context, $name); 

        if($result === false || !$result['translated'] && $original_value){        
            $ret_val = $original_value;    
            if(isset($has_translation)) $has_translation = false;
        }else{
            $ret_val = $result['value'];    
            if(isset($has_translation)) $has_translation = true;
        }
        
    }
    return $ret_val;
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
            'status'    => $status,
        );
        $wpdb->insert($wpdb->prefix.'icl_string_translations', $st);
        $st_id = $wpdb->insert_id;
    }    
    
    icl_update_string_status($string_id);
    
    return $st_id;
}

function icl_get_string_translations($offset=0){
    global $wpdb, $sitepress, $sitepress_settings, $wp_query, $icl_st_string_translation_statuses;
    
    $extra_cond = "";
    $status_filter = isset($_GET['status']) ? intval($_GET['status']) : false;
    if($status_filter !== false){
        if($status_filter == ICL_STRING_TRANSLATION_COMPLETE){
            $extra_cond .= " AND status = " . ICL_STRING_TRANSLATION_COMPLETE;
        }else{
            $extra_cond .= " AND status IN (" . ICL_STRING_TRANSLATION_PARTIAL . "," . ICL_STRING_TRANSLATION_NEEDS_UPDATE . "," . ICL_STRING_TRANSLATION_NOT_TRANSLATED . ")";
        }        
    }
    $context_filter = isset($_GET['context']) ? $_GET['context'] : false;
    if($context_filter !== false){
        $extra_cond .= " AND context = '" . $wpdb->escape($context_filter) . "'";
    }
    
    if(isset($_GET['show_results']) && $_GET['show_results']=='all'){
        $limit = 9999;
        $offset = 0;
    }else{       
        $limit = $sitepress_settings['st']['strings_per_page']; 
        if(!isset($_GET['paged'])) $_GET['paged'] = 1;
        $offset = ($_GET['paged']-1)*$limit;
    }

    $res = $wpdb->get_results("
        SELECT SQL_CALC_FOUND_ROWS id AS string_id, language AS string_language, context, name, value, status                
        FROM  {$wpdb->prefix}icl_strings
        WHERE 
            language = '".$sitepress->get_default_language()."'
            {$extra_cond}
        ORDER BY string_id DESC
        LIMIT {$offset},{$limit}
    ", ARRAY_A);
        
    $wp_query->found_posts = $wpdb->get_var("SELECT FOUND_ROWS()");
    $wp_query->query_vars['posts_per_page'] = $limit;
    $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit);
    
    if($res){
        $extra_cond = '';
        if(isset($_GET['translation_language'])){
            $extra_cond .= " AND language='".$wpdb->escape($_GET['translation_language'])."'";    
        }
        
        foreach($res as $row){
            $string_translations[$row['string_id']] = $row;
            $tr = $wpdb->get_results("
                SELECT id, language, status, value  
                FROM {$wpdb->prefix}icl_string_translations 
                WHERE string_id={$row['string_id']} {$extra_cond}
            ", ARRAY_A);
            if($tr){
                foreach($tr as $t){
                    $string_translations[$row['string_id']]['translations'][$t['language']] = $t;
                }                
            }
            
        }
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
    return icl_t('Widgets', 'widget title - ' . md5($val) , $val);    
}

function icl_sw_filters_widget_text($val){    
    return icl_t('Widgets', 'widget body - ' . md5($val) , $val);
}

function icl_sw_filters_gettext($translation, $text, $domain){
    global $sitepress_settings;
    $has_translation = 0;
    $ret_translation = icl_t('theme ' . $domain, md5($text), $text, $has_translation);
    if(false === $has_translation){
        $ret_translation = $translation;   
    }
    return $ret_translation;
}

function icl_st_update_string_actions($context, $name, $old_value, $new_value){
    global $wpdb;
    if($new_value != $old_value){        
        $string = $wpdb->get_row("SELECT id, value, status FROM {$wpdb->prefix}icl_strings WHERE context='{$context}' AND name='{$name}'");    
        if(!$string){
            icl_register_string($context, $name, $new_value);
            return;
        }
        $wpdb->update($wpdb->prefix . 'icl_strings', array('value'=>$new_value), array('id'=>$string->id));
        if($string->status == ICL_STRING_TRANSLATION_COMPLETE || $string->status == ICL_STRING_TRANSLATION_PARTIAL){
            $wpdb->update($wpdb->prefix . 'icl_string_translations', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('string_id'=>$string->id));
            $wpdb->update($wpdb->prefix . 'icl_strings', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('id'=>$string->id));
        }
        
        if($context == 'Widgets' && $new_value){
            if(0 === strpos($name, 'widget title - ')){
                icl_rename_string('Widgets', 'widget title - ' . md5($old_value), 'widget title - ' . md5($new_value));
            }elseif(0 === strpos($name, 'widget body - ')){
                icl_rename_string('Widgets', 'widget body - ' . md5($old_value), 'widget body - ' . md5($new_value));
            }
        }        
        
    }
    
}

function icl_st_update_blogname_actions($old, $new){
    icl_st_update_string_actions('WP', 'Blog Title', $old, $new);
}

function icl_st_update_blogdescription_actions($old, $new){
    icl_st_update_string_actions('WP', 'Tagline', $old, $new);
}

function icl_st_update_widget_title_actions($old_options, $new_options){        
    foreach($new_options as $k=>$o){
        if(isset($o['title'])){
            if(isset($old_options[$k]['title']) && $old_options[$k]['title']){
                icl_st_update_string_actions('Widgets', 'widget title - ' . md5(apply_filters('widget_title', $old_options[$k]['title'])), apply_filters('widget_title', $old_options[$k]['title']), apply_filters('widget_title', $o['title']));        
            }else{                
                if($new_options[$k]['title']){          
                    icl_register_string('Widgets', 'widget title - ' . md5(apply_filters('widget_title', $new_options[$k]['title'])), apply_filters('widget_title', $new_options[$k]['title']));
                }                
            }            
        }
    }    
}

function icl_st_update_text_widgets_actions($old_options, $new_options){
    $widget_text = get_option('widget_text');
    if(is_array($widget_text)){
        foreach($widget_text as $k=>$w){
            if(isset($old_options[$k]['text']) && trim($old_options[$k]['text']) && $old_options[$k]['text'] != $w['text']){
                icl_st_update_string_actions('Widgets', 'widget body - ' . md5(apply_filters('widget_text', $old_options[$k]['text'])), apply_filters('widget_text', $old_options[$k]['text']), apply_filters('widget_text', $w['text']));
            }elseif($new_options[$k]['text'] && $old_options[$k]['text']!=$new_options[$k]['text']){
                icl_register_string('Widgets', 'widget body - ' . md5(apply_filters('widget_text', $new_options[$k]['text'])), apply_filters('widget_text', $new_options[$k]['text']));
            }
        }
    }
}

function icl_t_cache_lookup($context, $name){
    global $sitepress_settings;
    static $icl_st_cache;
    
    if(!isset($icl_st_cache)){
        $icl_st_cache = array();
    }
    
    if(isset($icl_st_cache[$context]) && empty($icl_st_cache[$context])){  // cache semi-hit - string is not in the db
        $ret_value = false;        
    }elseif(!isset($icl_st_cache[$context][$name])){ //cache MISS
        global $sitepress, $wpdb;        
        $current_language = $sitepress->get_current_language();
        $default_language = $sitepress->get_default_language();
        $res = $wpdb->get_results("
            SELECT s.name, s.value, t.value AS translation_value, t.status
            FROM  {$wpdb->prefix}icl_strings s
            LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
            WHERE 
                s.language = '{$default_language}' AND s.context = '{$context}'
                AND (t.language = '{$current_language}' OR t.language IS NULL)
            ", ARRAY_A);
        if($res){
            foreach($res as $row){
                if($row['status'] != ICL_STRING_TRANSLATION_COMPLETE || empty($row['translation_value'])){
                    $icl_st_cache[$context][$row['name']]['translated'] = false;
                    $icl_st_cache[$context][$row['name']]['value'] = $row['value'];
                }else{
                    $icl_st_cache[$context][$row['name']]['translated'] = true;
                    $icl_st_cache[$context][$row['name']]['value'] = $row['translation_value'];
                }
            }
            $ret_value = $icl_st_cache[$context][$name];            
        }else{
            $icl_st_cache[$context] = array();    
            $ret_value = false;
        }  
    }else{ //cache HIT
        $ret_value = $icl_st_cache[$context][$name];
    }  
    
    // special case of WP strings    
    if($context == 'WP' && 
        ($name == 'Blog Title' && is_null($sitepress_settings['st']['sw']['blog_title']) 
            || $name == 'Tagline' && is_null($sitepress_settings['st']['sw']['tagline'])))
        {
            $icl_st_cache[$context] = array();
        }
    elseif($context == 'Widgets' &&
        (preg_match('#^widget title - #', $name) && is_null($sitepress_settings['st']['sw']['widget_titles']) 
            || preg_match('#^widget body - #', $name) && is_null($sitepress_settings['st']['sw']['text_widgets'])))
        {
            $icl_st_cache[$context] = array();
        }        
    return $ret_value;    
}

function icl_st_get_contexts($status){
    global $wpdb, $sitepress;    
    $extra_cond = '';
    
    if($status !== false){
        if($status == ICL_STRING_TRANSLATION_COMPLETE){
            $extra_cond .= " AND status = " . ICL_STRING_TRANSLATION_COMPLETE;
        }else{
            $extra_cond .= " AND status IN (" . ICL_STRING_TRANSLATION_PARTIAL . "," . ICL_STRING_TRANSLATION_NEEDS_UPDATE . "," . ICL_STRING_TRANSLATION_NOT_TRANSLATED . ")";
        }        
    }
    
    $results = $wpdb->get_results("
        SELECT context, COUNT(context) AS c FROM {$wpdb->prefix}icl_strings 
        WHERE language='{$sitepress->get_current_language()}' {$extra_cond}
        GROUP BY context 
        ORDER BY context ASC");
    return $results;
}

function icl_st_admin_notices(){
    global $icl_st_err_str;
    if($icl_st_err_str){
        echo '<div class="error"><p>' . $icl_st_err_str . '</p></div>';
    }    
}

function icl_st_scan_theme_files($dir = false, $recursion = 0){
    require_once ICL_PLUGIN_PATH . '/inc/potx.inc';
    static $scan_stats = false;
    static $recursion, $scanned_files = array();
    global $icl_scan_theme_found_domains, $sitepress, $sitepress_settings;
    if($dir === false){
        $dir = TEMPLATEPATH;
    }
    
    if(!$scan_stats){
        $scan_stats = sprintf(__('Scanning theme folder: %s', 'sitepress'),$dir) . PHP_EOL;
    }    
        
    $dh = opendir($dir);    
    while(false !== ($file = readdir($dh))){
        if($file=="." || $file=="..") continue;
        
        if(is_dir($dir . "/" . $file)){
            $recursion++;
            $scan_stats .= str_repeat("\t",$recursion) . sprintf(__('Opening folder: %s', 'sitepress'), $dir . "/" . $file) . PHP_EOL;
            icl_st_scan_theme_files($dir . "/" . $file, $recursion);            
            $recursion--;
        }elseif(preg_match('#(\.php|\.inc)$#i', $file)){     
            // THE potx way
            $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Scanning file: %s', 'sitepress'), $dir . "/" . $file) . PHP_EOL;
            $scanned_files[] = $dir . "/" . $file;
            _potx_process_file($dir . "/" . $file, 0, '__icl_st_scan_theme_files_store_results','_potx_save_version', POTX_API_7);
            
            /*
            // THE preg match way
            static $icl_registered_strings = array();
            $content = file_get_contents($dir . "/" . $file);
            $int = preg_match('#(__|_e)\((\'|")([^\)]+)(\'|")([, ]+)(\'|")([^\)]+)(\'|")\)#im',$content,$matches);
            if($int){
                if(!isset($icl_registered_strings[$matches[7].$matches[3]])){
                    icl_register_string('theme ' . $matches[7], md5($matches[3]), $matches[3]);
                    $icl_registered_strings[$matches[7].$matches[3]] = true;
                }                
            }
            */
        }else{
            $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Skipping file: %s', 'sitepress'), $dir . "/" . $file) . PHP_EOL;    
        }
    }
    
    if(!$recursion){
        global $__icl_registered_strings;
        $scan_stats .= __('Done scanning files', 'sitepress') . PHP_EOL;
            $sitepress_settings['st']['theme_localization_domains'] = array_keys($icl_scan_theme_found_domains);
            $sitepress->save_settings($sitepress_settings);
            closedir($dh);
            $scan_stats = __('= Your theme was scanned for texts =', 'sitepress') . '<br />' . 
                          __('The following files were processed:') . '<br />' .
                          '<ol style="font-size:10px;"><li>' . join('</li><li>', $scanned_files) . '</li></ol>' . 
                          sprintf(__('WPML found %s strings. They were added to the string translation table.'),count($__icl_registered_strings)) . 
                          '<br /><a href="#" onclick="jQuery(this).next().toggle();return false;">' . __('More details', 'sitepress') . '</a>'.
                          '<textarea style="display:none;width:100%;height:150px;font-size:10px;">' . $scan_stats . '</textarea>'; 
            return $scan_stats;
    }
    
}

function __icl_st_scan_theme_files_store_results($string, $domain){
    global $icl_scan_theme_found_domains;
    
    if(!isset($icl_scan_theme_found_domains[$domain])){
        $icl_scan_theme_found_domains[$domain] = true;
    }
    global $__icl_registered_strings;
    if(!isset($__icl_registered_strings)){
        $__icl_registered_strings = array();
    }
    if(!isset($__icl_registered_strings[$domain.'||'.$string])){
        if(!$domain){
            icl_register_string('theme', md5($string), $string);
        }else{
            icl_register_string('theme ' . $domain, md5($string), $string);
        }        
        $__icl_registered_strings[$domain.'||'.$string] = true;
    }                
    
}

function get_theme_localization_stats(){
    global $sitepress_settings, $wpdb;
    $stats = false;
    if(is_array($sitepress_settings['st']['theme_localization_domains'])){    
        foreach($sitepress_settings['st']['theme_localization_domains'] as $domain){
            $domains[] = $domain ? 'theme ' . $domain : 'theme';
        }
        $results = $wpdb->get_results("
            SELECT context, status, COUNT(id) AS c 
            FROM {$wpdb->prefix}icl_strings
            WHERE context IN ('".join("','",$domains)."')
            GROUP BY context, status            
        ");
        foreach($results as $r){
            if(!isset($stats[$r->context]['complete'])){
                $stats[$r->context]['complete'] = 0;
            }
            if(!isset($stats[$r->context]['incomplete'])){
                $stats[$r->context]['incomplete'] = 0;
            }            
            if($r->status == ICL_STRING_TRANSLATION_COMPLETE){
                $stats[$r->context]['complete'] = $r->c; 
            }else{
                $stats[$r->context]['incomplete'] += $r->c; 
            }
            
        }
    }
   return $stats; 
}

function icl_st_generate_po_file($strings){
    $po = "";
    $po .= '# This file was generated by WPML' . PHP_EOL;
    $po .= '# WPML is a WordPress plugin that can turn any WordPress or WordPressMU site into a full featured multilingual content management system.' . PHP_EOL;    
    $po .= '# http://wpml.org' . PHP_EOL;
    $po .= 'msgid ""' . PHP_EOL;
    $po .= 'msgstr ""' . PHP_EOL;
    $po .= '"Content-Type: text/plain; charset=utf-8\n"' . PHP_EOL;
    $po .= '"Content-Transfer-Encoding: 8bit\n"' . PHP_EOL;
    $po .= '"Project-Id-Version: \n"' . PHP_EOL;
    $po .= '"POT-Creation-Date: \n"' . PHP_EOL;
    $po .= '"PO-Revision-Date: \n"' . PHP_EOL;
    $po .= '"Last-Translator: \n"' . PHP_EOL;
    $po .= '"Language-Team: \n"' . PHP_EOL;
    $po .= '"MIME-Version: 1.0\n"' . PHP_EOL;    
    
    foreach($strings as $s){
        $po .= PHP_EOL;        
        if(isset($s['translations'][key($s['translations'])]['value'])){
            $translation = $s['translations'][key($s['translations'])]['value'];
            if($s['translations'][key($s['translations'])]['status'] != ICL_STRING_TRANSLATION_COMPLETE){
                $po .= '#, fuzzy' . PHP_EOL;
            }
        }else{
            $translation = false;
            $po .= '#, fuzzy' . PHP_EOL;
        }
        $po .= 'msgid "'.$s['value'].'"' . PHP_EOL;
        $po .= 'msgstr "'.$translation.'"' . PHP_EOL;
    }
    
    return $po;
}

function icl_st_debug($str){
    trigger_error($str, E_USER_WARNING);
}



?>