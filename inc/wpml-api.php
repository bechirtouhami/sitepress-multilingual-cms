<?php
/* This file includes a set of funcitons that can be used by WP plugins developers to make their plugins interract with WPML */  

/* constants */
define('WPML_API_SUCCESS' , 0);
define('WPML_API_ERROR' , 99);
define('WPML_API_INVALID_LANGUAGE_CODE' , 1);
define('WPML_API_INVALID_TRID' , 2);
define('WPML_API_LANGUAGE_CODE_EXISTS' , 3);
define('WPML_API_CONTENT_NOT_FOUND' , 4);
define('WPML_API_TRANSLATION_NOT_FOUND' , 5);
define('WPML_API_INVALID_CONTENT_TYPE' , 6);
define('WPML_API_CONTENT_EXISTS' , 7);


function _wpml_api_allowed_content_type($type){
    $reserved_types = array(
        'post'      => 1, 
        'page'      => 1, 
        'tag'       => 1, 
        'category'  => 1,
        'comment'   => 1
    );
    return !isset($reserved_types[$type]) && preg_match('#([a-z0-9_\-])#i', $type);
}

/**
 * Add translatable content to the WPML translations table
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code. (defaults to current language)
 * @param int $trid Content trid - if a translation in a different language already exists.
 * 
 * @return int error code
 *  */
function wpml_add_translatable_content($type, $content_id, $language_code = false, $trid = false){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    if($trid){
        $trid_type   = $wpdb->get_var("SELECT element_type FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}'");
        if(!$trid_type || $trid_type != $type){
            return WPML_API_INVALID_TRID;
        }
    }
    
    if($wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type='".$wpdb->escape($type)."' AND element_id='{$content_id}'")){
        return WPML_API_CONTENT_EXISTS;
    }
    
    $t = $sitepress->set_element_language_details($content_id, $type, $trid, $language_code);        
    
    if(!$t){
        return WPML_API_ERROR;
    }else{
        return WPML_API_SUCCESS;
    }
    
}



/**
 * Update translatable content in the WPML translations table
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code.
 *  
 * @return int error code
 *  */
function wpml_update_translatable_content($type, $content_id, $language_code){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if(!$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    $trid = $sitepress->get_element_trid($content_id, $type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }
    
    $translations = $sitepress->get_element_translations($trid);
    if(isset($translations[$language_code]) && !$translations[$language_code]->element_id != $content_id){
        return WPML_API_LANGUAGE_CODE_EXISTS;
    }
            
    $t = $sitepress->set_element_language_details($content_id, $type, $trid, $language_code);        
    
    if(!$t){
        return WPML_API_ERROR;
    }else{
        return WPML_API_SUCCESS;
    }
    
}

/**
 * Update translatable content in the WPML translations table
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code. (when ommitted - delete all translations associated with the respective content)
 *  
 * @return int error code
 *  */
function wpml_delete_translatable_content($type, $content_id, $language_code = false){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    $trid = $sitepress->get_element_trid($content_id, $type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }
    
    if($language_code){
        $translations = $sitepress->get_element_translations($trid);
        if(!isset($translations[$language_code])){
            return WPML_API_TRANSLATION_NOT_FOUND;
        }
        
    }
    
    $sitepress->delete_element_translation($trid, $type, $language_code);
            
    return WPML_API_SUCCESS;
}
?>
