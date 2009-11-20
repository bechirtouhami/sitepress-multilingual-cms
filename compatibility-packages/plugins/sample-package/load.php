<?php
/*
Package Name: DummyPackage
Package URI: http://wpml.org/
Description: Sample package description
Theme: default
Theme version: 1.0
Plugin: sitepress-multilingual-cms/sitepress.php
Plugin version: 1.4
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/
  
class DummyPackage  extends WPML_Package{
    function __construct(){
        parent::__construct();
        
    }    
    
    // do call the destructor of the parent class
    function __destruct(){
        parent::__destruct();
    }    
        
}

$DummyPackage = new DummyPackage();
?>
