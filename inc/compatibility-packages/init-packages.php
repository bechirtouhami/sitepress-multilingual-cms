<?php
$ICL_Packages = new ICL_Packages();
add_action('plugins_loaded', array($ICL_Packages,'load_packages'));

add_action('icl_extra_options_' . $_GET['page'], array($ICL_Packages,'render_forms'));

if(isset($_POST['icl_extras_submit'])){
    add_action('init', array($ICL_Packages,'process_forms'));
}            

?>
