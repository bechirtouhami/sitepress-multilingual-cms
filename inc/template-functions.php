<?php
function icl_get_home_url(){
    global $sitepress;
    return $sitepress->language_url($sitepress->get_current_language());
} 


function icl_get_languages($a=''){
    if($a){
        parse_str($a, $args);        
    }else{
        $args = '';
    }
    global $sitepress;
    $langs = $sitepress->get_ls_languages($args);
    return $langs;
} 

function icl_disp_language($native_name, $translated_name){
    if(!$native_name && !$translated_name){
        $ret = '';
    }elseif($native_name && $translated_name){
        if($native_name != $translated_name){
            $ret = $native_name . ' (' . $translated_name . ')';
        }else{
            $ret = $native_name;
        }
    }elseif($native_name){
        $ret = $native_name;
    }elseif($translated_name){
        $ret = $translated_name;
    }
    
    return $ret;    
    
}

function icl_link_to_element($element_id, $element_type='post', $link_text='', $optional_parameters=array(), $anchor=''){
    global $sitepress, $wpdb;
    
    if($element_type=='tag' || $element_type=='post_tag'){
        $element_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id= %d AND taxonomy='post_tag'",$element_id));
        $element_type=='tag'; 
    }elseif($element_type=='category'){
        $element_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id= %d AND taxonomy='category'",$element_id));        
    }elseif($element_type=='page'){
        $element_type=='post'; 
    }    
    
    if(!$element_id) return '';
    
    $trid = $sitepress->get_element_trid($element_id, $element_type);
    $translations = $sitepress->get_element_translations($trid, $element_type);
        
    // current language is ICL_LANGUAGE_CODE    
    if(isset($translations[ICL_LANGUAGE_CODE])){
        if($element_type=='post'){
            $url = get_permalink($translations[ICL_LANGUAGE_CODE]->element_id);                    
            $title = $translations[ICL_LANGUAGE_CODE]->post_title;
        }elseif($element_type=='tag'){
            list($term_id, $title) = $wpdb->get_row($wpdb->prepare("SELECT t.term_id, t.name FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='post_tag'",$translations[ICL_LANGUAGE_CODE]->element_id), ARRAY_N);            
            $url = get_tag_link($term_id);        
        }elseif($element_type=='category'){
            list($term_id, $title) = $wpdb->get_row($wpdb->prepare("SELECT t.term_id, t.name FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='category'",$translations[ICL_LANGUAGE_CODE]->element_id), ARRAY_N);            
            $url = get_category_link($term_id);        
        }        
    }else{
        if($element_type=='post'){
            $url = get_permalink($element_id);
            $title = get_the_title($element_id);
        }elseif($element_type=='tag'){
            $url = get_tag_link($element_id);     
            $my_tag = &get_term($element_id, 'post_tag', OBJECT, 'display');
            $title = apply_filters('single_tag_title', $my_tag->name);               
        }elseif($element_type=='category'){
            $url = get_category_link($element_id);        
            $my_cat = &get_term($element_id, 'category', OBJECT, 'display');
            $title = apply_filters('single_cat_title', $my_cat->name);                           
        }
    }
    
    if(!$url) return '';
    
    if(!empty($optional_parameters)){
        $url_glue = false===strpos($url,'?') ? '?' : '&';
        $url .= $url_glue . http_build_query($optional_parameters);        
    }
    
    if(isset($anchor) && $anchor){
        $url .= '#' . $anchor;
    }
    
    $link = '<a href="'.$url.'">';    
    if(isset($link_text) && $link_text){
        $link .= $link_text;   
    }else{
        $link .= $title;
    }
    $link .= '</a>';
        
    echo $link;            
}

function icl_object_id($element_id, $element_type='post', $return_original_if_missing=false){
    global $sitepress, $wpdb;
    
    $element_types = array('post', 'post_tag', 'category');
    if(!in_array($element_type, $element_types)){
        trigger_error(__('Invalid object kind','sitepress'));
        return null;
    }elseif(!$element_id){
        trigger_error(__('Invalid object id','sitepress'));
        return null;
    }
    
    if($element_type=='category' || $element_type=='post_tag'){
        $element_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id= %d AND taxonomy='{$element_type}'",$element_id));
    }
    
    if($element_type=='post_tag'){
        $icl_element_type = 'tag';
    }elseif($element_type=='page'){
        $icl_element_type = 'post';
    }else{
        $icl_element_type = $element_type;
    }
    
    $trid = $sitepress->get_element_trid($element_id, $icl_element_type);
    $translations = $sitepress->get_element_translations($trid, $icl_element_type);
    
    if(isset($translations[ICL_LANGUAGE_CODE]->element_id)){
        $ret_element_id = $translations[ICL_LANGUAGE_CODE]->element_id;
        if($element_type=='category' || $element_type=='post_tag'){
            $ret_element_id = $wpdb->get_var($wpdb->prepare("SELECT t.term_id FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='{$element_type}'", $ret_element_id));            
        }
    }else{
        $ret_element_id = $return_original_if_missing ? $element_id : null;
    }
    
    return $ret_element_id;
    
}
?>