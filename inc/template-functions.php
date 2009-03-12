<?php
function icl_get_home_url(){
    global $sitepress;
    return $sitepress->language_url($sitepress->get_current_language());
}  
?>
