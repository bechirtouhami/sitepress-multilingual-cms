<?php
/*
Package Name: Extra options for the Default theme (demo)
Package URI: http://wpml.org/
Description: This is a demo package that would illustrate how these packages should be buillt. GNU Licence blah blah.. It applies to the WP default theme.
Theme: default
Theme version: 1.0
Author: Mihai G
Author URI: http://www.onthegosystems.com
Version: 1.0
*/
  
  

// Instructions: 
// 1. create your class by inheriting WPML_Package (defined in /inc/compatibility-packages/wpml-package.class.php - check it out to see what methods it has)  
// 2. instantiate the class
// Done.

// Some properties that are inherited are:
// name  (the package identifier - in this case wp-default-theme) - very handy sometimes
// data  (meta information that you can see at the top of this post - just in case ypu need it)
// settings  (your package's settings as they are saved from the options it will register)
// type (e.g. themes or plugins)  
  
class WP_Default_theme_compatibility  extends WPML_Package{
    
    // do call the constructor of the parent class
    function __construct(){
        parent::__construct();
        
        // set the page where we want these options to be displayed on
        $wpage = ICL_PLUGIN_FOLDER . '/menu/languages.php';        
           
        // add the options
        // we're adding them in two groups just to show how we can use options groups   
        // once they're added WPML will take care of rencering them and add them to the database when they're saved
        // access them then through the 'settings' property of this class which is populated automatically
        //
        // TODO - options need to be saved to the database by the user to become effective (maybe we should check wehther they exist in the database once we're adding them here)
        // 
        $this->add_option_checkbox($wpage, __("Show 'this post is also available' at the bottom of the post.", 'sitepress'), 'post_languages', __('Default theme - Language selector options'), 'checked');        
        $this->add_option_checkbox($wpage, __('Site footer horizontal language selector'), 'footer_language_selector', __('Default theme - Language selector options'), 'checked');
        $this->add_option_checkbox($wpage, __('Skip missing languages for the footer languages', 'sitepress'), 'footer_skip_languages', __('Default theme - More options'), 'checked');
        $this->add_option_checkbox($wpage, __("Skip missing languages for the 'this post is also available'", 'sitepress'), 'post_available_skip_languages', __('Default theme - More options'), 'checked');
        $this->add_option_text($wpage, __("'this post is also available' text.", 'sitepress'), 'post_available_text', __('Default theme - Language selector options'), __('This post is also available in: ', 'sitepress'), array('size'=>40));
        $this->add_option_text($wpage, __("'this post is also available' before.", 'sitepress'), 'post_available_before', __('Default theme - Language selector options'), '', array('size'=>5));
        $this->add_option_text($wpage, __("'this post is also available' after.", 'sitepress'), 'post_available_after', __('Default theme - Language selector options'), '', array('size'=>5));
        
        // the package logic starts here
        
        // if the user has enabled this option do what it's suppose to do        
        // in this case display the language picker at the bottom of the page
        if($this->settings['footer_language_selector']){
            add_action('wp_footer', array($this, 'footer_language_selector'));
        }
        
        // if the user has enabled this option do what it's suppose to do    
        // in this case display the language picker after the posts     
        if($this->settings['post_languages']){
            add_filter('the_content', array($this, 'add_post_available'));
        }
        
    }    
    
    // do call the destructor of the parent class
    function __destruct(){
        parent::__destruct();
    }    
    
    // More stuff goes down here
    
    // the function for displaying the language selector at the bottom
    function footer_language_selector(){
        $languages = icl_get_languages('skip_missing='.intval($this->settings['footer_skip_languages']));
        if(!empty($languages)){
            echo '<div id="'.$div_id.'"><ul>';
            foreach($languages as $l){
                echo '<li>';
                if(!$l['active']) echo '<a href="'.$l['url'].'">';
                echo '<img src="'.$l['country_flag_url'].'" alt="'.$l['language_code'].'" width="18" height="12" />';
                if(!$l['active']) echo '</a>';
                if(!$l['active']) echo '<a href="'.$l['url'].'">';
                echo $l['native_name'];
                if(!$l['active']) echo ' ('.$l['translated_name'].')';
                if(!$l['active']) echo '</a>';
                echo '</li>';
            }
            echo '</ul></div>';
        }
    }
    
    // the function for displaying the language selector after the post content
    function add_post_available($content){
        $out = '';
        if(is_singular()){
            $languages = icl_get_languages('skip_missing='.intval($this->settings['post_available_skip_languages']));
            if(1 < count($languages)){            
                $out .= $this->settings['post_available_text'];
                $out .= $this->settings['post_available_before'] ? $this->settings['post_available_before'] : ''; 
                foreach($languages as $l){
                    if(!$l['active']) $langs[] = '<a href="'.$l['url'].'">'.$l['translated_name'].'</a>';
                }
                $out .= join(', ', $langs);
                $out .= $this->settings['post_available_after'] ? $this->settings['post_available_after'] : '';
            }    
        }
        return $content . '<p>' . $out . '</p>';
    }
    
}


// make it happen
// instantiate the package class
$WP_Default_theme_compatibility = new WP_Default_theme_compatibility();


?>