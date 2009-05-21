<?php
if(file_exists(ICL_PLUGIN_PATH . '/inc/sandbox.inc')){
    require ICL_PLUGIN_PATH . '/inc/sandbox.inc';
} else {
    define('ICL_API_ENDPOINT', 'https://www.icanlocalize.com');
}
?>