<?php 
if(version_compare(preg_replace('#-(.+)#','',$wp_version), '3.0', '<')){
    define('ICL_PRE_WP3', true);

    // redirect post-new.php?post_type='page' to page-new.php
    if(is_admin() && $pagenow=='post-new.php' && isset($_GET['post_type']) && $_GET['post_type']=='page'){
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: page-new.php?".$_SERVER['QUERY_STRING']);
        exit;
    }
    
    
}else{
    define('ICL_PRE_WP3', false);
}
?>