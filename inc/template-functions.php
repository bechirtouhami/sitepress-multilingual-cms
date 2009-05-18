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
?>
