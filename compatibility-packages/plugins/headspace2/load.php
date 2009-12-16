<?php
/*
Package Name: Compatibility for Headspace2 SEO
Package URI: http://wpml.org/
Description: Makes Headspace2 SEO compatible with WPML
Plugin: headspace2/headspace.php
Plugin version: 3.6.32
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/
  
class WP_Headspace2_SEO_compatibility  extends WPML_Package{
    
    function __construct(){
        parent::__construct();
    }
    
    function __destruct(){
        parent::__destruct();
    }
        
}

$WP_Headspace2_SEO_compatibility = new WP_Headspace2_SEO_compatibility();
?>
