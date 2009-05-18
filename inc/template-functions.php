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
?>
