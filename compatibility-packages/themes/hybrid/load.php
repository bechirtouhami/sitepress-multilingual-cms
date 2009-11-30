<?php
/*
Package Name: Extra options for the Hybrid theme framework
Package URI: http://wpml.org/
Description: This package enables basic Hybrid-WPML compatibility.
Theme: hybrid
Theme version: 0.6.1
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/

// Some properties that are inherited are:
// name  (the package identifier - in this case wp-default-theme) - very handy sometimes
// data  (meta information that you can see at the top of this post - just in case ypu need it)
// settings  (your package's settings as they are saved from the options it will register)
// type (e.g. themes or plugins)  
  
class Hybrid_theme_compatibility  extends WPML_Package{
    
    function __construct(){
        parent::__construct();
        
        $wpage = ICL_PLUGIN_FOLDER . '/menu/languages.php';
		$title = 'Hybrid - ';
		
			// Header switcher
        $this->add_option_checkbox($wpage, __('Site header horizontal language selector','sitepress'), 'header_language_selector', $title . __('Language selector options','sitepress'), 'checked');
        $this->add_option_checkbox($wpage, __('Skip missing languages for the header languages', 'sitepress'), 'header_skip_languages', $title . __('More options'), 'checked');
		$this->add_option_checkbox($wpage, __('Load CSS for header language selector', 'sitepress'), 'header_load_css', $title . __('More options'), 'checked');

			// Footer switcher
        $this->add_option_checkbox($wpage, __('Site footer horizontal language selector','sitepress'), 'footer_language_selector', $title . __('Language selector options','sitepress'), 'checked');
        $this->add_option_checkbox($wpage, __('Skip missing languages for the footer languages', 'sitepress'), 'footer_skip_languages', $title . __('More options'), 'checked');
		$this->add_option_checkbox($wpage, __('Load CSS for footer language selector', 'sitepress'), 'footer_load_css', $title . __('More options'), 'checked');
		
		
			// This post is available
		$this->add_option_checkbox($wpage, __("Show 'this post is also available'", 'sitepress'), 'post_languages', $title . __('Language selector options'), 'checked');
        $this->add_option_checkbox($wpage, __("Skip missing languages for the 'this post is also available'", 'sitepress'), 'post_available_skip_languages', $title . __('More options'), 'checked');
        $this->add_option_text($wpage, __("'this post is also available' text.", 'sitepress'), 'post_available_text', $title . __('Language selector options'), 'This post is also available in: %s', array('size'=>40));
		$this->add_option_select($wpage, __("'this post is also available' position:", 'sitepress'), 'post_available_position', array( 'top' => __('Above post', 'sitepress'), 'bottom' => __('Bellow post', 'sitepress') ),  $title . __('Language selector options','sitepress'), 'bottom');
       /* $this->add_option_text($wpage, __("'this post is also available' before.", 'sitepress'), 'post_available_before', $title . __('Language selector options'), '', array('size'=>5));
        $this->add_option_text($wpage, __("'this post is also available' after.", 'sitepress'), 'post_available_after', $title . __('Language selector options'), '', array('size'=>5));*/
        
		
		if($this->settings['header_language_selector']){
            add_action('hybrid_before_header',array(&$this,'language_selector_header'));
			if($this->settings['header_load_css']) {
				$this->load_css('css/selector-header.css');
			}
			$this->check_sidebar_language_selector_widget();
        }
		
        if($this->settings['footer_language_selector']){
            add_action('hybrid_after_footer',array(&$this,'language_selector_footer'));
			if($this->settings['footer_load_css']) {
				$this->load_css('css/selector-footer.css');
			}
        }
        
        if($this->settings['post_languages']){
            add_filter('the_content', array($this, 'add_post_available'));
        }
		
		add_filter('hybrid_site_title',array(&$this,'filter_home_link'));
		add_filter('wp_page_menu',array(&$this,'filter_home_link'));
		
		icl_register_string( 'theme '.$this->name, "'this post is also available' text", $this->settings['post_available_text'] );
		$settings = get_option('hybrid_theme_settings');
		if ( $settings && !empty($settings['footer_insert']) ) {
			icl_register_string( 'theme '.$this->name, 'Footer text', $settings['footer_insert'] );
			add_action('wp_head',array(&$this,'translate_footer_text'));
		}
    }

	function translate_footer_text() {
		global $hybrid_settings;
		$hybrid_settings['footer_insert'] = icl_t('theme '.$this->name,'Footer text',$hybrid_settings['footer_insert']);
	}

    function __destruct(){
        parent::__destruct();
    }
}

$Hybrid_theme_compatibility = new Hybrid_theme_compatibility();
?>