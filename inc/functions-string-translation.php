<?php

//echo '<pre>';
//print_r(get_option('sidebars_widgets'));
//echo '</pre>';

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
                         
    if(!isset($sitepress_settings['existing_content_language_verified'])){
        return;
    }          
    
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
            for($k = 0; $k < count($lines); $k++){
                if(0 === strpos($lines[$k], 'msgid "')){
                    if($str = substr($lines[$k],7,strlen($lines[$k])-9)){
                        $icl_st_po_strings[] = array(
                            'string' => substr($lines[$k],7,strlen($lines[$k])-9),
                            'translation' => substr($lines[$k+1], 8, strlen($lines[$k+1])-10)
                        );
                        $k++;                        
                    }                                        
                }                
            }            
            if(empty($icl_st_po_strings)){
                $icl_st_err_str = __('No string found', 'sitepress');
            }
        }
    }
    elseif(isset($_POST['icl_st_save_strings'])){
        $arr = array_intersect_key($_POST['icl_strings'], array_flip($_POST['icl_strings_selected']));
        $arr = array_map('html_entity_decode', $arr);         
        if(isset($_POST['icl_st_po_language'])){
            $arr_t = array_intersect_key($_POST['icl_translations'], array_flip($_POST['icl_strings_selected']));
            $arr_t = array_map('html_entity_decode', $arr_t);         
        }
        
        foreach($arr as $k=>$string){
            $string_id = icl_register_string($_POST['icl_st_strings_for'], md5($string), $string);
            if($string_id && isset($_POST['icl_st_po_language'])){
                icl_add_string_translation($string_id, $_POST['icl_st_po_language'], $arr_t[$k], ICL_STRING_TRANSLATION_COMPLETE);
                icl_update_string_status($string_id);
            }            
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
        $w = $wpdb->get_row("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name = 'widget_{$name}'");
        $value = unserialize($w->option_value);
        if(isset($value[$suffix]['title']) && $value[$suffix]['title']){
            $w_title = $value[$suffix]['title'];     
        }else{
            $w_title = __icl_get_default_widget_title($aw);
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

function icl_t($context, $name, $original_value=false){
    global $wpdb, $sitepress, $sitepress_settings;
    
    // if the default language is not set up return
    if(!isset($sitepress_settings['existing_content_language_verified'])){
        return $original_value !== false ? $original_value : $name;
    }   
       
    $current_language = $sitepress->get_current_language();
    $default_language = $sitepress->get_default_language();
    if($current_language == $default_language && $original_value){
        
        $ret_val = $original_value;
        
    }else{
        
        $result = icl_t_cache_lookup($context, $name); 

        if($result === false || !$result['translated'] && $original_value){        
            $ret_val = $original_value;    
        }else{
            $ret_val = $result['value'];    
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
    
    
    if(!isset($_GET['paged'])) $_GET['paged'] = 1;
    $offset = ($_GET['paged']-1)*$limit;

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
        foreach($res as $row){
            $string_translations[$row['string_id']] = $row;
            $tr = $wpdb->get_results("
                SELECT id, language, status, value  
                FROM {$wpdb->prefix}icl_string_translations WHERE string_id={$row['string_id']}
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
    }
    
    if($context == 'Widgets'){
        if(0 === strpos($name, 'widget title - ')){
            icl_rename_string('Widgets', 'widget title - ' . md5($old_value), 'widget title - ' . md5($new_value));
        }elseif(0 === strpos($name, 'widget body - ')){
            icl_rename_string('Widgets', 'widget body - ' . md5($old_value), 'widget body - ' . md5($new_value));
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
            if(isset($old_options[$k]['title'])){
                icl_st_update_string_actions('Widgets', 'widget title - ' . md5(apply_filters('widget_title', $old_options[$k]['title'])), apply_filters('widget_title', $old_options[$k]['title']), apply_filters('widget_title', $o['title']));        
            }else{                
                if(!$new_options[$k]['title']){          
                    //__icl_st_init_register_widget_titles();
                }else{
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
    //global $icl_cache_log;
    
    global $sitepress_settings;
    static $icl_st_cache;
    
    if(!isset($icl_st_cache)){
        $icl_st_cache = array();
    }
    
    if(isset($icl_st_cache[$context]) && empty($icl_st_cache[$context])){  // cache semi-hit - string is not in the db
        //$icl_cache_log[] = "SEMIHIT\t" . $context . "\t" . $name . "\n";
        $ret_value = false;        
    }elseif(!isset($icl_st_cache[$context][$name])){ //cache MISS
        //$icl_cache_log[] = "MISS\t" . $context . "\t" . $name . "\n";
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
        //$icl_cache_log[] = "HIT\t" . $context . "\t" . $name . "\n";
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

function icl_st_get_contexts(){
    global $wpdb;    
    $results = $wpdb->get_results("SELECT context, COUNT(context) AS c FROM {$wpdb->prefix}icl_strings GROUP BY context ORDER BY context ASC");
    return $results;
}

function icl_st_admin_notices(){
    global $icl_st_err_str;
    if($icl_st_err_str){
        echo '<div class="error"><p>' . $icl_st_err_str . '</p></div>';
    }    
}

function icl_st_debug($str){
    trigger_error($str, E_USER_WARNING);
}


/*
add_action('wp_footer', 'icl_debug_log');
function icl_debug_log(){
    global $icl_cache_log;
    echo '<div style="text-align:left;margin-left:10px;"><pre style="font-size:12px;>';
    echo join("", $icl_cache_log);
    echo '</pre></div>';
}
*/
?>