<?php
/*
Package Name: Extra options for the Thematic theme framework
Package URI: http://wpml.org/
Description: This package enables basic Thematic-WPML compatibility.
Theme: thematic
Theme version: 0.9.5.1
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/


class Thematic_theme_compatibility  extends WPML_Package{
    
    function __construct(){
        parent::__construct();
        
        $wpage = ICL_PLUGIN_FOLDER . '/menu/languages.php';
		$title = 'Thematic - ';
		
			// Header switcher
		
        $this->add_option_checkbox(
			$wpage,
			__('Add a list of languages to the site\'s header','sitepress'),
			'header_language_selector',
			$title . __('Language selector options','sitepress'),
			'checked'
				);
		
        $this->add_option_checkbox(
			$wpage,
			__('Only include languages with translation in the languages list header', 'sitepress'),
			'header_skip_languages',
			$title . __('More options', 'sitepress'),
			'checked'
				);
		
		$this->add_option_checkbox(
			$wpage,
			__('Load CSS for header languages list', 'sitepress'),
			'header_load_css',
			$title . __('More options', 'sitepress'),
			'checked'
				);
		
		
			// This post is available
		
		$this->add_option_checkbox(
			$wpage,
			__('Show alternative languages for posts and pages', 'sitepress'),
			'post_languages',
			$title . __('Language selector options', 'sitepress'),
			'checked'
				);
		
        $this->add_option_text(
			$wpage,
			__('Text for alternative languages display', 'sitepress'),
			'post_available_text',
			$title . __('Language selector options','sitepress'),
			'This post is also available in: %s',
			array('size'=>40)
				);
		
		$this->add_option_select(
			$wpage,
			__('Position to display alternative languages', 'sitepress'),
			'post_available_position',
			array( 'top' => __('Above post', 'sitepress'),
			'bottom' => __('Bellow post', 'sitepress') ),
			$title . __('Language selector options','sitepress'),
			'bottom'
				);
        
		
		if($this->settings['header_language_selector']){
            add_action('thematic_aboveheader',array(&$this,'language_selector_header'));
			if($this->settings['header_load_css']) {
				$this->load_css('css/selector-header.css');
			}
			$this->check_sidebar_language_selector_widget();
        }
		
        if($this->settings['post_languages']){
            add_filter('the_content', array($this, 'add_post_available'));
        }
		
		add_filter('wp_page_menu',array(&$this,'filter_home_link'));
		add_action('thematic_header',array(&$this,'remove_thematic_blogtitle'),0);
		
		icl_register_string( 'theme '.$this->name, 'Text for alternative languages for posts', $this->settings['post_available_text'] );
		$footer_text = get_option('thm_footertext',true);
		if ($footer_text) {
			icl_register_string( 'theme '.$this->name, 'Footer text', $footer_text );
			add_filter('thematic_footertext',array(&$this,'translate_footer_text'));
		}
		
		$this->load_css('css/compatibility-package.css');
	}

	function remove_thematic_blogtitle() {
		add_action('thematic_header',array(&$this,'add_thematic_blogtitle'),3);
		remove_action('thematic_header','thematic_blogtitle',3);
	}

	function add_thematic_blogtitle() {
?>
		<div id="blog-title"><span><a href="<?php echo icl_get_home_url(); ?>" title="<?php bloginfo('name') ?>" rel="home"><?php bloginfo('name') ?></a></span></div>
		<?php 
	}

	function translate_footer_text($str) {
		return icl_t('theme '.$this->name,'Footer text',$str);
	}

    // do call the destructor of the parent class
    function __destruct(){
        parent::__destruct();
    }
}

$Thematic_theme_compatibility = new Thematic_theme_compatibility();
?>