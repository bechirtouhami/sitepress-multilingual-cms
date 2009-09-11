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

define('WPML_API_GET_CONTENT_ERROR' , 0);


function _wpml_api_allowed_content_type($content_type){
    $reserved_types = array(
        'post'      => 1, 
        'page'      => 1, 
        'tag'       => 1, 
        'category'  => 1,
        'comment'   => 1
    );
    return !isset($reserved_types[$content_type]) && preg_match('#([a-z0-9_\-])#i', $content_type);
}

/**
 * Add translatable content to the WPML translations table
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code. (defaults to current language)
 * @param int $trid Content trid - if a translation in a different language already exists.
 * 
 * @return int error code
 *  */
function wpml_add_translatable_content($content_type, $content_id, $language_code = false, $trid = false){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    if($trid){
        $trid_type   = $wpdb->get_var("SELECT element_type FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}'");
        if(!$trid_type || $trid_type != $content_type){
            return WPML_API_INVALID_TRID;
        }
    }
    
    if($wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type='".$wpdb->escape($content_type)."' AND element_id='{$content_id}'")){
        return WPML_API_CONTENT_EXISTS;
    }
    
    $t = $sitepress->set_element_language_details($content_id, $content_type, $trid, $language_code);        
    
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
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code.
 *  
 * @return int error code
 *  */
function wpml_update_translatable_content($content_type, $content_id, $language_code){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if(!$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }
    
    $translations = $sitepress->get_element_translations($trid);
    if(isset($translations[$language_code]) && !$translations[$language_code]->element_id != $content_id){
        return WPML_API_LANGUAGE_CODE_EXISTS;
    }
            
    $t = $sitepress->set_element_language_details($content_id, $content_type, $trid, $language_code);        
    
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
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code. (when ommitted - delete all translations associated with the respective content)
 *  
 * @return int error code
 *  */
function wpml_delete_translatable_content($content_type, $content_id, $language_code = false){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }
    
    if($language_code){
        $translations = $sitepress->get_element_translations($trid);
        if(!isset($translations[$language_code])){
            return WPML_API_TRANSLATION_NOT_FOUND;
        }
        
    }
    
    $sitepress->delete_element_translation($trid, $content_type, $language_code);
            
    return WPML_API_SUCCESS;
}

/**
 * Get trid value for a specific piece of content
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 *    
 * @return int trid or 0 for error
 *  */
function wpml_get_content_trid($content_type, $content_id){
    global $sitepress;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_GET_CONTENT_ERROR; //WPML_API_INVALID_CONTENT_TYPE;
    }
    
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    
    if(!$trid){
        return WPML_API_GET_CONTENT_ERROR;
    }else{
        return $trid;
    } 
} 

/**
 * Detects the current language and returns the language relevant content id. optionally it can return the original id if a translation is not found 
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param bool $return_original return the original id when translation not found.
 *    
 * @return int trid or 0 for error
 *  */

function wpml_get_content($content_type, $content_id, $return_original = true){
    global $sitepress;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_GET_CONTENT_ERROR; //WPML_API_INVALID_CONTENT_TYPE;
    }
    
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    
    if(!$trid){
        return WPML_API_GET_CONTENT_ERROR;
    }else{
        return icl_object_id($content_id, $content_type, $return_original);
    } 
}

/**
 * Get translations for a certain piece of content 
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param bool $return_original return the original id when translation not found.
 *    
 * @return int trid or error code
 *  */
function wpml_get_content_translations($content_type, $content_id, $skip_missing = true){
    global $sitepress;
        
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_TRANSLATION_NOT_FOUND;
    }
    
    $translations = $sitepress->get_element_translations($trid, $content_type, $skip_missing);
    
    $tr = array();
    foreach($translations as $k=>$v){
        $tr[$k] = $v->element_id;
    }
    
    return $tr;
}

/**
 *  Returns a certain translation for a piece of content 
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param bool $language_code 
 *    
 * @return error code or array('lang'=>element_id)
 *  */
function wpml_get_content_translation($content_type, $content_id, $language_code){
    global $sitepress;
        
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }
        
    $translations = $sitepress->get_element_translations($trid, $content_type, true);
    
    if(!isset($translations[$language_code])){
        return WPML_API_TRANSLATION_NOT_FOUND;
    }else{
        return array($language_code => $translations[$language_code]->element_id);
    }
    
}

/**
 *  Returns the list of active languages
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 *    
 * @return array
 *  */
function wpml_get_active_languages(){
    global $sitepress;
    $langs = $sitepress->get_active_languages();        
    return $langs;
}

/**
 *  Returns the default language
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 *    
 * @return string
 *  */
function wpml_get_default_language(){
    global $sitepress;
    return $sitepress->get_default_language();
}


/**
 *  Get current language
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 * 
 * @return string
 *  */
function wpml_get_current_language(){
    global $sitepress;
    return $sitepress->get_current_language();
}

/**
 *  Get contents of a specific type
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 *    
 * @return int or array
 *  */

function wpml_get_contents($content_type, $language_code = false){
    global $sitepress, $wpdb;
    
    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    if(!$language_code){
        $language_code = $sitepress->get_current_language();
    }
    
    $contents = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='".$wpdb->escape($content_type)."' AND language_code='{$language_code}'");
    return $contents;
    
}
?>
