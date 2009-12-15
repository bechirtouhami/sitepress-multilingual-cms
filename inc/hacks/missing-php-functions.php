<?php

// json_decode
if ( !function_exists('json_decode') ){
    include_once ICL_PLUGIN_PATH . '/lib/JSON.php';
    function json_decode($data, $bool) {
        if ($bool) {
            $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        } else {
            $json = new Services_JSON();
        }
        return( $json->decode($data) );
    }
}   
?>
