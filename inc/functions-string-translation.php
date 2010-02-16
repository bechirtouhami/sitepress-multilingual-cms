<?php
define('ICL_STRING_TRANSLATION_NOT_TRANSLATED', 0);
define('ICL_STRING_TRANSLATION_COMPLETE', 1);
define('ICL_STRING_TRANSLATION_NEEDS_UPDATE', 2);
define('ICL_STRING_TRANSLATION_PARTIAL', 3);

define('ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE', 0);
define('ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE', 1);
define('ICL_STRING_TRANSLATION_STRING_TRACKING_THRESHOLD', 5);


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

if(is_admin()){
    wp_enqueue_style('thickbox');
    wp_enqueue_script('jquery');
    wp_enqueue_script('thickbox');
}

function icl_st_init(){                       
    global $sitepress_settings, $sitepress, $wpdb, $icl_st_err_str;
    
    if(isset($_GET['icl_action']) && $_GET['icl_action'] == 'view_string_in_page'){
        icl_st_string_in_page($_GET['string_id']);
        exit;
    }

    if(isset($_GET['icl_action']) && $_GET['icl_action'] == 'view_string_in_source'){
        icl_st_string_in_source($_GET['string_id']);
        exit;
    }
    
    if ( get_magic_quotes_gpc() && $_GET['page'] == ICL_PLUGIN_FOLDER . '/menu/string-translation.php'){
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
            'text_widgets' => 1
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
                    $string = str_replace('\"','"', $matches[1]);
                    $int = preg_match('#msgstr "(.+)"#im',trim($lines[$k+1]),$matches);
                    if($int){
                        $translation = str_replace('\"','"',$matches[1]);
                    }else{
                        $translation = "";
                    }
                    
                    $string_exists = $wpdb->get_var("
                        SELECT id FROM {$wpdb->prefix}icl_strings 
                        WHERE context='".$wpdb->escape($_POST['icl_st_i_context_new']?$_POST['icl_st_i_context_new']:$_POST['icl_st_i_context'])."' 
                        AND name='".md5($string)."'");
                    
                    $icl_st_po_strings[] = array(     
                        'string' => $string,
                        'translation' => $translation,
                        'fuzzy' => $fuzzy,
                        'exists' => $string_exists
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
        
        // see if the strings are already registered and have names
        // case of adding translation
        $res = $wpdb->get_results("                                                                                              
            SELECT value, name 
            FROM {$wpdb->prefix}icl_strings 
            WHERE context = '{$_POST['icl_st_domain_name']}' AND value IN ('".join("','", array_map('mysql_real_escape_string', $arr))."')  
        ");
        if(!empty($res)){
            foreach($res as $r){
                $map[$r->value] = $r->name;
            }
        }
         
        foreach($arr as $k=>$string){
            if(isset($map[$string])){
                $name = $map[$string];
            }else{
                $name = md5($string);
            }
            $string_id = icl_register_string($_POST['icl_st_domain_name'], $name, $string);
            if($string_id && isset($_POST['icl_st_po_language'])){
                if($arr_t[$k] != ""){
                    if($arr_f[$k]){
                        $_status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
                    }else{
                        $_status = ICL_STRING_TRANSLATION_COMPLETE;
                    }
                    icl_add_string_translation($string_id, $_POST['icl_st_po_language'], $arr_t[$k], $_status);
                    icl_update_string_status($string_id);
                }                
            }            
        }        
    }
    
    //handle po export
    if(isset($_POST['icl_st_pie_e'])){
        //force some filters
        if(isset($_GET['status'])) unset($_GET['status']);
        $_GET['show_results']='all';
        if($_POST['icl_st_e_context']){
            $_GET['context'] = $_POST['icl_st_e_context'];
        }
                                                    
        $_GET['translation_language'] = $_POST['icl_st_e_language'];
        $strings = icl_get_string_translations();
        if(!empty($strings)){
            $po = icl_st_generate_po_file($strings, !isset($_POST['icl_st_pe_translations']));
        }else{
            $po = "";  
        }
        if(!isset($_POST['icl_st_pe_translations'])){
            $popot = 'pot';
            $poname = $_POST['icl_st_e_context'] ? urlencode($_POST['icl_st_e_context']) : 'all_context'; 
        }else{
            $popot = 'po';
            $poname = $_GET['translation_language'];
        }
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$poname.'.'.$popot.";");
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
    if($sitepress_settings['theme_localization_type']==1){
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
    if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) /*|| 2 > count($sitepress->get_active_languages())*/){
        return;
    }
    add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/overview.php', __('String translation','sitepress'), __('String translation','sitepress'), 'edit_others_pages', basename(ICL_PLUGIN_PATH).'/menu/string-translation.php');  
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
        if(empty($translations) || max($translations) == ICL_STRING_TRANSLATION_NOT_TRANSLATED){
            $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
        }elseif(count($translations) < count($active_languages)-1){
            if(in_array(ICL_STRING_TRANSLATION_NEEDS_UPDATE,$translations)){
                $status = ICL_STRING_TRANSLATION_NEEDS_UPDATE;
            }elseif(in_array(ICL_STRING_TRANSLATION_COMPLETE,$translations)){
                $status = ICL_STRING_TRANSLATION_PARTIAL;            
            }else{
                $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
            }            
        }elseif(ICL_STRING_TRANSLATION_NEEDS_UPDATE == array_unique($translations)){            
            $status = ICL_STRING_TRANSLATION_NEEDS_UPDATE;
        }else{
            if(in_array(ICL_STRING_TRANSLATION_NEEDS_UPDATE,$translations)){
                $status = ICL_STRING_TRANSLATION_NEEDS_UPDATE;
            }elseif(in_array(ICL_STRING_TRANSLATION_NOT_TRANSLATED,$translations)){
                $status = ICL_STRING_TRANSLATION_PARTIAL;            
            }else{
                $status = ICL_STRING_TRANSLATION_COMPLETE;            
            }
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
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_strings WHERE id=" . $string_id);
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_translations WHERE string_id=" . $string_id);
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id=" . $string_id);
    }
}  

function __icl_unregister_string_multi($arr){
    global $wpdb; 
    $str = join(',', array_map('intval', $arr));
    $wpdb->query("
        DELETE s.*, t.* FROM {$wpdb->prefix}icl_strings s LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
        WHERE s.id IN ({$str})");
    $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id IN ({$str})");
}  

function icl_t($context, $name, $original_value=false, &$has_translation=null){
    global $wpdb, $sitepress, $sitepress_settings;
    
    // if the default language is not set up return
    if(!isset($sitepress_settings['existing_content_language_verified'])){
        if(isset($has_translation)) $has_translation = false;
        return $original_value !== false ? $original_value : $name;
    }   
       
    if(is_admin()){
        $current_language = $sitepress->get_admin_language();
    }else{
        $current_language = $sitepress->get_current_language();
    }    
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
        }elseif($status === ICL_STRING_TRANSLATION_NOT_TRANSLATED){
            $st_update['status'] = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
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

function icl_get_string_id($string, $context){
    global $wpdb;
    $string = $wpdb->escape($string);
    $id = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_strings WHERE value='{$string}' AND context='{$context}'");    
    return $id;
}

function icl_get_string_translations($offset=0){
    global $wpdb, $sitepress, $sitepress_settings, $wp_query, $icl_st_string_translation_statuses;
    
    $extra_cond = "";
    $status_filter = isset($_GET['status']) ? intval($_GET['status']) : false;
    $search_filter = isset($_GET['search']) ? $_GET['search'] : false;
    $exact_match   = isset($_GET['em']) ? $_GET['em'] == 1 : false;
    
    if($status_filter !== false){
        if($status_filter == ICL_STRING_TRANSLATION_COMPLETE){
            $extra_cond .= " AND status = " . ICL_STRING_TRANSLATION_COMPLETE;
        }else{
            $extra_cond .= " AND status IN (" . ICL_STRING_TRANSLATION_PARTIAL . "," . ICL_STRING_TRANSLATION_NEEDS_UPDATE . "," . ICL_STRING_TRANSLATION_NOT_TRANSLATED . ")";
        }        
    }
    
    if($search_filter != false){
        if($exact_match){
            $extra_cond .= " AND value = '". $wpdb->escape($search_filter)."' ";            
        }else{
            $extra_cond .= " AND value LIKE '%". $wpdb->escape($search_filter)."%' ";            
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

function icl_get_strigs_tracked_in_pages($string_translations){
    global $wpdb;
    // get string position in page - if found
    $found_strings = $strings_in_page = array();
    foreach(array_keys((array)$string_translations) as $string_id){
        $found_strings[] = $string_id;
    }
    if($found_strings){
        $res = $wpdb->get_results("
            SELECT kind, string_id  FROM {$wpdb->prefix}icl_string_positions 
            WHERE string_id IN (".implode(',', $found_strings).")");
        foreach($res as $row){
            $strings_in_page[$row->kind][$row->string_id] = true;
        }
    }
    return $strings_in_page;
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
    
    $dbt = debug_backtrace();    
    $dbt4 = str_replace('\\','/',$dbt[4]['file']);
    $wp_plugin_dir = str_replace('\\','/',WP_PLUGIN_DIR);
    
    if(0 === strpos($dbt4, $wp_plugin_dir)){        
        if(dirname($dbt4) == $wp_plugin_dir){
            $plugin_folder = basename(str_replace($wp_plugin_dir, '', $dbt4));    
        }else{
            $exp = explode('/', ltrim(str_replace($wp_plugin_dir, '', $dbt4),'/'));            
            $plugin_folder = $exp[0];    
        }
        $context = 'plugin ' . $plugin_folder;
    }else{
        $context = ($domain != 'default') ? 'theme ' . $domain : 'theme';
    }
    
    // track strings if the user has enabled this and if it's and editor or admin
    if($sitepress_settings['st']['track_strings'] && current_user_can('edit_others_posts')){
        icl_st_track_string($text, $context, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE);
    }   
    
    $ret_translation = icl_t($context, md5($text), $text, $has_translation);
    if(false === $has_translation){
        $ret_translation = $translation;   
    }
    
    if(isset($_GET['icl_string_track_value']) && isset($_GET['icl_string_track_context']) 
        && stripslashes($_GET['icl_string_track_context']) == $context && stripslashes($_GET['icl_string_track_value']) == $text){
            $ret_translation = '<span style="background-color:'.$sitepress_settings['st']['hl_color'].'">' . $ret_translation . '</span>';
            
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
        
        global $switched, $switched_stack;        
        if(isset($switched) && $switched){
            $prev_blog_id = $wpdb->blogid;
            $wpdb->set_blog_id($switched_stack[0]);
        }
        $res = $wpdb->get_results("
            SELECT s.name, s.value, t.value AS translation_value, t.status
            FROM  {$wpdb->prefix}icl_strings s
            LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
            WHERE 
                s.language = '{$default_language}' AND s.context = '{$context}'
                AND (t.language = '{$current_language}' OR t.language IS NULL)
            ", ARRAY_A);
        if(isset($switched) && $switched){
            $wpdb->set_blog_id($prev_blog_id);
        }            
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
        }else{
            $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Skipping file: %s', 'sitepress'), $dir . "/" . $file) . PHP_EOL;    
        }
    }
    
    if($dir == TEMPLATEPATH && TEMPLATEPATH != STYLESHEETPATH){
        static $double_scan = true;
        icl_st_scan_theme_files(STYLESHEETPATH);            
        $double_scan = false;
    }
    
    if(!$recursion && !$double_scan){
        global $__icl_registered_strings;
        $scan_stats .= __('Done scanning files', 'sitepress') . PHP_EOL;
            $sitepress_settings['st']['theme_localization_domains'] = array_keys($icl_scan_theme_found_domains);
            $sitepress->save_settings($sitepress_settings);
            closedir($dh);
            $scan_stats_all = __('= Your theme was scanned for texts =', 'sitepress') . '<br />' . 
                          __('The following files were processed:', 'sitepress') . '<br />' .
                          '<ol style="font-size:10px;"><li>' . join('</li><li>', $scanned_files) . '</li></ol>' . 
                          sprintf(__('WPML found %s strings. They were added to the string translation table.','sitepress'),count($__icl_registered_strings)) . 
                          '<br /><a href="#" onclick="jQuery(this).next().toggle();return false;">' . __('More details', 'sitepress') . '</a>'.
                          '<textarea style="display:none;width:100%;height:150px;font-size:10px;">' . $scan_stats . '</textarea>'; 
            return $scan_stats_all;
    }
    
}

function __icl_st_scan_theme_files_store_results($string, $domain, $file, $line){
    global $icl_scan_theme_found_domains;
    
    $string = str_replace(array('\"',"\\'"), array('"',"'"), $string);
    
    if(!isset($icl_scan_theme_found_domains[$domain])){
        $icl_scan_theme_found_domains[$domain] = true;
    }
    global $wpdb, $__icl_registered_strings;
    if(!isset($__icl_registered_strings)){
        $__icl_registered_strings = array();
        
        // clear existing entries (both source and page type)
        $context  = $domain ? 'theme ' . $domain : 'theme';
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id IN 
            (SELECT id FROM {$wpdb->prefix}icl_strings WHERE context = '{$context}')");        
    }
    
    if(!isset($__icl_registered_strings[$domain.'||'.$string])){
        if(!$domain){
            $context = 'theme';
        }else{
            $context = 'theme ' . $domain;            
        }        
        icl_register_string($context, md5($string), $string);
        $__icl_registered_strings[$domain.'||'.$string] = true;
    }                
    
    // store position in source
    icl_st_track_string($string, $context, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE, $file, $line);              
}



function icl_st_scan_plugin_files($plugin, $recursion = 0){
    require_once ICL_PLUGIN_PATH . '/inc/potx.inc';
    static $recursion, $scanned_files = array();
    static $scan_stats = false;
    global $icl_scan_plugin_found_domains, $icl_st_p_scan_plugin_id,
           $sitepress, $sitepress_settings;
    
    if(!$recursion){
        $icl_st_p_scan_plugin_id = str_replace(WP_PLUGIN_DIR .'/', '', $plugin);
    }
    
    if(is_file($plugin) && !$recursion){ // case of one-file plugins
        $scan_stats = sprintf(__('Scanning file: %s', 'sitepress'), $plugin);
        _potx_process_file($plugin, 0, '__icl_st_scan_plugin_files_store_results','_potx_save_version', POTX_API_7);            
    }else{
        $dh = opendir($plugin);    
        while(false !== ($file = readdir($dh))){
            if(0 === strpos($file, '.')) continue;
            if(is_dir($plugin . "/" . $file)){
                $recursion++;
                $scan_stats .= str_repeat("\t",$recursion-1) . sprintf(__('Opening folder: %s', 'sitepress'), $dir . "/" . $file) . PHP_EOL;
                icl_st_scan_plugin_files($plugin . "/" . $file, $recursion);            
                $recursion--;
            }elseif(preg_match('#(\.php|\.inc)$#i', $file)){     
                $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Scanning file: %s', 'sitepress'), $dir . "/" . $file) . PHP_EOL;
                $scanned_files[] = $dir . "/" . $file;
                _potx_process_file($plugin . "/" . $file, 0, '__icl_st_scan_plugin_files_store_results','_potx_save_version', POTX_API_7);
            }else{
                $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Skipping file: %s', 'sitepress'), $dir . "/" . $file) . PHP_EOL;    
            }
        }        
    }
    
    
    if(!$recursion){
        global $__icl_registered_strings;
        $__icl_registered_strings = array();
        $scan_stats .= __('Done scanning files', 'sitepress') . PHP_EOL;                    
        
        /*
        if(is_array($icl_scan_plugin_found_domains)){
            $existing_domains = $sitepress_settings['st']['plugins_localization_domains'];
            if(is_array($existing_domains)){
                $sitepress_settings['st']['plugins_localization_domains'] = array_unique(array_merge(array_keys($icl_scan_plugin_found_domains), $existing_domains));
            }else{
                $sitepress_settings['st']['plugins_localization_domains'] = array_keys($icl_scan_plugin_found_domains);
            }
            $sitepress->save_settings($sitepress_settings);
        }
        */
        
        unset($icl_st_p_scan_plugin_id);        
        $scan_stats = '<textarea style="width:100%;height:150px;font-size:10px;">' . $scan_stats . "\n" .
                       count($scanned_files) . ' scanned files' . "\n" .    
                       sprintf(__('WPML found %s strings. They were added to the string translation table.','sitepress'),count($__icl_registered_strings)) . "\n" .
                       '</textarea>';
        
        return $scan_stats;
    }    
    
}

function __icl_st_scan_plugin_files_store_results($string, $domain, $file, $line){
    global $icl_scan_plugin_found_domains, $icl_st_p_scan_plugin_id;
    
    $string = str_replace(array('\"',"\\'"), array('"',"'"), $string);
        
    //if(!isset($icl_scan_plugin_found_domains[$domain])){
    //    $icl_scan_plugin_found_domains[$domain] = true;
    //}    
    global $wpdb, $__icl_registered_strings;
    if(empty($__icl_registered_strings) ){
        $__icl_registered_strings = array();
        
        // clear existing entries (both source and page type)        
        $context  = $icl_st_p_scan_plugin_id ? 'plugin ' . $icl_st_p_scan_plugin_id : 'plugins';
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id IN 
            (SELECT id FROM {$wpdb->prefix}icl_strings WHERE context = '{$context}')");
    }
    
    if(!isset($__icl_registered_strings[$icl_st_p_scan_plugin_id.'||'.$string])){
        if(!$domain){
            icl_register_string('plugins', md5($string), $string);
        }else{
            icl_register_string('plugin ' . $icl_st_p_scan_plugin_id, md5($string), $string);
        }        
        $__icl_registered_strings[$icl_st_p_scan_plugin_id.'||'.$string] = true;
    }  
    
    // store position in source
    icl_st_track_string($string, 'plugin ' . $icl_st_p_scan_plugin_id, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE, $file, $line);              
    
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

function get_plugin_localization_stats(){
    global $sitepress_settings, $wpdb;
    $stats = false;

    $results = $wpdb->get_results("
        SELECT context, status, COUNT(id) AS c 
        FROM {$wpdb->prefix}icl_strings
        WHERE context LIKE ('plugin %')
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
    
    return $stats;     
}

function icl_st_generate_po_file($strings, $potonly = false){
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
        if(!$potonly && isset($s['translations']) && isset($s['translations'][key($s['translations'])]['value'])){
            $translation = $s['translations'][key($s['translations'])]['value'];
            if($translation != '' && $s['translations'][key($s['translations'])]['status'] != ICL_STRING_TRANSLATION_COMPLETE){
                $po .= '#, fuzzy' . PHP_EOL;
            }
        }else{
            $translation = '';            
        }
        $po .= 'msgid "'.str_replace('"', '\"', $s['value']).'"' . PHP_EOL;
        $po .= 'msgstr "'.str_replace('"', '\"', $translation).'"' . PHP_EOL;
    }
    
    return $po;
}

function icl_st_track_string($text, $context, $kind = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE, $file = null, $line = null){
    global $wpdb;
    // get string id
    $string_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_strings WHERE context='".$wpdb->escape($context)."' AND value='".$wpdb->escape($text)."'");    
    if($string_id){
        // get existing records
        $string_records_count = $wpdb->get_var("SELECT COUNT(id) 
                                        FROM {$wpdb->prefix}icl_string_positions 
                                        WHERE string_id = '{$string_id}' AND kind = " . $kind);
        if(ICL_STRING_TRANSLATION_STRING_TRACKING_THRESHOLD > $string_records_count){        
            if($kind == ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE){
                // get page url
                $https = $_SERVER['HTTPS'] == 'on' ? 's':'';
                $position = 'http' . $https . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            }else{
                $position = $file . '::' . $line;
            }
            
            if(!$wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_string_positions 
                                WHERE string_id='{$string_id}' AND position_in_page='".$wpdb->escape($position)."' AND kind='".$kind."'")){
                $wpdb->insert($wpdb->prefix . 'icl_string_positions', array(
                    'string_id' => $string_id,
                    'kind' => $kind,
                    'position_in_page' => $position
                ));                    
            }
            
        }
    }
}

function icl_st_string_in_page($string_id){
    global $wpdb;
    // get urls   
    $urls = $wpdb->get_col("SELECT position_in_page 
                            FROM {$wpdb->prefix}icl_string_positions 
                            WHERE string_id = '{$string_id}' AND kind = ". ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE);
    if(!empty($urls)){
        $string = $wpdb->get_row("SELECT context, value FROM {$wpdb->prefix}icl_strings WHERE id='{$string_id}'");
        echo '<div id="icl_show_source_top">';
        for($i = 0; $i < count($urls); $i++){
            $c = $i+1;
            if(strpos($urls[$i], '?') !== false){
                $urls[$i] .= '&icl_string_track_value=' . urlencode($string->value);
            }else{
                $urls[$i] .= '?icl_string_track_value=' . urlencode($string->value);
            }            
            $urls[$i] .= '&icl_string_track_context=' . urlencode($string->context);            
            echo '<a href="#" onclick="jQuery(\'#icl_string_track_frame_wrap iframe\').attr(\'src\',\''.$urls[$i].'\');jQuery(\'#icl_string_track_url a\').html(\''.$urls[$i].'\').attr(\'href\',  \''.$urls[$i].'\'); return false;">'.$c.'</a><br />';
            
        }
        echo '</div>';
        echo '<div id="icl_string_track_frame_wrap">';        
        echo '<iframe onload="iclResizeIframe()" src="'.$urls[0].'" width="10" height="10" frameborder="0" marginheight="0" marginwidth="0"></iframe>';
        echo '<div id="icl_string_track_url" class="icl_string_track_url"><a href="'.$urls[0].'">' . htmlspecialchars($urls[0]) . "</a></div>\n";
        echo '</div>';        
    }else{
        _e('No records found', 'sitepress');
    }
}

function icl_st_string_in_source($string_id){
    global $wpdb, $sitepress_settings;
    // get positions    
    $files = $wpdb->get_col("SELECT position_in_page 
                            FROM {$wpdb->prefix}icl_string_positions 
                            WHERE string_id = '{$string_id}' AND kind = ". ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE);
    if(!empty($files)){
        $string = $wpdb->get_row("SELECT context, value FROM {$wpdb->prefix}icl_strings WHERE id='{$string_id}'");        
        echo '<div id="icl_show_source_top">';
        for($i = 0; $i < count($files); $i++){            
            $c = $i+1;
            $exp = explode('::', $files[$i]);
            $line = $exp[1];
            echo '<a href="#" onclick="icl_show_in_source('.$i.','.$line.')">'.$c.'</a><br />';
        }
        echo '</div>';
        echo '<div id="icl_show_source_wrap">';
        for($i = 0; $i < count($files); $i++){            
            $exp = explode('::', $files[$i]);
            $file = $exp[0];
            $line = $exp[1];
            echo '<div class="icl_string_track_source" id="icl_string_track_source_'.$i.'"';
            if($i > 0){
                echo 'style="display:none"';
            }else{
                $first_pos = $line;                
            }
            echo '>';
            if($i == 0){
                echo '<script type="text/javascript">icl_show_in_source_scroll_once = ' . $line . '</script>';
            }
            echo '<div class="icl_string_track_filename">' . $file . "</div>\n";
            echo '<pre>';        
            $content = file($file);
            echo '<ol>';
            foreach($content as $k=>$l){
                if($k == $line-1){
                    $hl =  ' style="background-color:'.$sitepress_settings['st']['hl_color'].';"';
                }else{
                    $hl = '';   
                }
                echo '<li id="icl_source_line_'.$i.'_'.$k.'"'.$hl.'">' . htmlspecialchars($l) . '&nbsp;</li>';
            }
            echo '</ol>';
            echo '</pre>';
            echo '</div>'; 
        }
        echo '</div>';
    }else{
        _e('No records found', 'sitepress');
    }    
}

function _icl_st_hide_random($str){
    $str = preg_replace('#^((.+)( - ))?([a-z0-9]{32})$#', '$2', $str);
    return $str;
}


function icl_st_get_mo_files($path){
    static $mo_files;
    
    $dh = opendir($path);
    while($f = readdir($dh)){            
        if(0 !== strpos($f, '.')){    
            if(is_dir($path . '/' . $f)){
                icl_st_get_mo_files($path . '/' . $f);
            }else{
                if(preg_match('#\.mo$#', $f)){                    
                    $mo_files[] = $path . '/' . $f;
                }
            }
        }
    }
    
    return $mo_files;
}

function icl_st_load_translations_from_mo($mo_file){
    $translations = array();
    $mo = new MO();     
    $mo->import_from_file( $mo_file );
    foreach($mo->entries as $str=>$v){
        $str = str_replace("\n",'\n', $str);
        $translations[$str] = $v->translations[0];
    }
    return $translations;
}







function icl_st_debug($str){
    trigger_error($str, E_USER_WARNING);
}




?>