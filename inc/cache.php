<?php
function icl_cache_get($key){
    $icl_cache = get_option('_icl_cache');
    if(isset($icl_cache[$key])){
        return $icl_cache[$key];
    }else{
        return false;
    }
}  

function icl_cache_set($key, $value=null){
    $icl_cache = get_option('_icl_cache');
    if(!is_null($value)){
        $icl_cache[$key] = $value;    
    }else{
        if(isset($icl_cache[$key])){
            unset($icl_cache[$key]);
        }        
    }
    update_option('_icl_cache', $icl_cache);
}

function icl_cache_clear($key){
    icl_cache_set($key, null);
}

?>