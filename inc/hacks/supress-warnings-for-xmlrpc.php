<?php
    if(!defined('ICL_DEBUG_MODE') || !ICL_DEBUG_MODE) return;
    if(!defined('WP_DEBUG') || !WP_DEBUG) return;
    
    $_pingback_url_parts = parse_url(get_bloginfo('pingback_url'));
    if($_SERVER['REQUEST_URI'] == $_pingback_url_parts['path']){
        error_reporting(E_NONE);
        ini_set('display_errors', '0');
        set_error_handler('__icl_void_error_handler',E_ALL);
        function __icl_void_error_handler(){return false;}
    }
?>
