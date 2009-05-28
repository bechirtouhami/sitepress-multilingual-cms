<?php
//using this file to handle particular situations that would involve more ellaborate solutions

add_action('init', 'icl_load_hacks');  

function icl_load_hacks(){    
    include ICL_PLUGIN_PATH . '/inc/hacks/language-domains-preview.php';    
}
?>