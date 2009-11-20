<?php
  
abstract class WPML_Package{
    var $name;
    var $data;
    var $settings;
    var $type;
    var $package_path;
    var $package_url;
    
    
    function __construct(){
        global $WPML_Packages;
        
        $trace=debug_backtrace();
        $this->package_path = dirname($trace[0]['file']);
        $this->name = basename($this->package_path);
        $this->type = basename(dirname(dirname($trace[0]['file'])));
        $this->package_url = get_option('home') . str_replace('\\','/',str_replace(trim(ABSPATH,'/'), '', $this->package_path));
        
        $_packages = $WPML_Packages->get_packages();
        $this->data = $_packages[$this->type][$this->name];
        
        global $sitepress_settings;
        if(empty($sitepress_settings)){
            $sitepress_settings = get_option('icl_sitepress_settings'); // fail safe
        }
        
        if(isset($sitepress_settings['packages'][$this->type][$this->name])){
            $this->settings = $sitepress_settings['packages'][$this->type][$this->name];    
        }else{
            $this->settings = array();    
        }
        
    }
    
    function __destruct(){
        
    }
    
    
    function add_option_checkbox($wpml_page, $option_label, $option_name, $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
        global $WPML_Packages;
        if(!$group_name){
            $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
        }
                                
        
        $WPML_Packages->packages_options[$wpml_page][$group_name][$this->name][] = array(
            'package_type' => $this->type,
            'option_type' => 'checkbox',
            'option_label' => $option_label,
            'option_name' => $option_name,
            'default_value' => $default_value,
            'extra_attributes' => $extra_attributes
        );
        
        // add the default value to the database        
        if($default_value && !isset($this->settings[$option_name])){
            global $sitepress;
            $this->settings[$option_name] = $default_value;
            $iclsettings['packages'][$this->type][$this->name] = $this->settings;
            $sitepress->save_settings($iclsettings);
        }
        
    }
        
    function add_option_text($wpml_page, $option_label, $option_name, $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
        global $WPML_Packages;
        if(!$group_name){
            $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
        }
                                
        $WPML_Packages->packages_options[$wpml_page][$group_name][$this->name][] = array(
            'package_type' => $this->type,
            'option_type' => 'text',
            'option_label' => $option_label,
            'option_name' => $option_name,
            'default_value' => $default_value,
            'extra_attributes' => $extra_attributes
        );
        
        // add the default value to the database        
        if($default_value && !isset($this->settings[$option_name])){
            global $sitepress;
            $this->settings[$option_name] = $default_value;
            $iclsettings['packages'][$this->type][$this->name] = $this->settings;
            $sitepress->save_settings($iclsettings);
        }
        
    }
    
    function add_option_textarea($wpml_page, $option_label, $option_name, $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
        global $WPML_Packages;
        if(!$group_name){
            $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
        }
                                
        $WPML_Packages->packages_options[$wpml_page][$group_name][$this->name][] = array(
            'package_type' => $this->type,
            'option_type' => 'textarea',
            'option_label' => $option_label,
            'option_name' => $option_name,
            'default_value' => $default_value,
            'extra_attributes' => $extra_attributes
        );
        
        // add the default value to the database        
        if($default_value && !isset($this->settings[$option_name])){
            global $sitepress;
            $this->settings[$option_name] = $default_value;
            $iclsettings['packages'][$this->type][$this->name] = $this->settings;
            $sitepress->save_settings($iclsettings);
        }
    }
    
    function add_option_radio($wpml_page, $option_label, $option_name, $option_options, $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
        global $WPML_Packages;
        if(!$group_name){
            $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
        }
                                
        $WPML_Packages->packages_options[$wpml_page][$group_name][$this->name][] = array(
            'package_type' => $this->type,
            'option_type' => 'radio',
            'option_label' => $option_label,
            'option_name' => $option_name,
            'option_options' => $option_options,
            'default_value' => $default_value,
            'extra_attributes' => $extra_attributes
        );
        
        // add the default value to the database        
        if($default_value && !isset($this->settings[$option_name])){
            global $sitepress;
            $this->settings[$option_name] = $default_value;
            $iclsettings['packages'][$this->type][$this->name] = $this->settings;
            $sitepress->save_settings($iclsettings);
        }
    }

    function add_option_select($wpml_page, $option_label, $option_name, $option_options, $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
        global $WPML_Packages;
        if(!$group_name){
            $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
        }
                                
        $WPML_Packages->packages_options[$wpml_page][$group_name][$this->name][] = array(
            'package_type' => $this->type,
            'option_type' => 'select',
            'option_label' => $option_label,
            'option_name' => $option_name,
            'option_options' => $option_options,
            'default_value' => $default_value,
            'extra_attributes' => $extra_attributes
        );
        
        // add the default value to the database        
        if($default_value && !isset($this->settings[$option_name])){
            global $sitepress;
            $this->settings[$option_name] = $default_value;
            $iclsettings['packages'][$this->type][$this->name] = $this->settings;
            $sitepress->save_settings($iclsettings);
        }
    }
    
    // $file is relative to the package root folder
    function load_js($file){
        add_action('wp_head', array($this, '_echo_js'), 30);        
    }
     function _echo_js($file){
        echo '<script type="text/javascript" src="'.$this->package_url . '/' . $file.'?v='. $this->data['Version'] .'"></script>'."\n";
    }
    
    // $file is relative to the package root folder
    function load_css($file){
        add_action('wp_head', array($this, '_echo_css'), 30);
    }
     function _echo_css($file){
        echo '<link rel="stylesheet" type="text/css" href="'.$this->package_url . '/' . $file.'?v='. $this->data['Version'] .'" />'."\n";
    }
        
}
  
?>
