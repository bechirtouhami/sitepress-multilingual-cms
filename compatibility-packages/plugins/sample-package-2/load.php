<?php
/*
Package Name: DummyPackage #2
Package URI: http://wpml.org/
Description: Sample package description #2
Theme: default
Theme version: 1.0
Plugin: sitepress-multilingual-cms/sitepress.php
Plugin version: 1.4
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/
  
class DummyPackage2  extends WPML_Package{
    function __construct(){
        parent::__construct();
        
    }    
    
    // do call the destructor of the parent class
    function __destruct(){
        parent::__destruct();
    }    
        
}

$DummyPackage2 = new DummyPackage2();
?>
