<?php
  
abstract class WPML_Package{
    var $name;
    var $data;
    var $settings;
    var $type;
    
    
    function __construct(){
        $trace=debug_backtrace();
        $this->name = basename(dirname($trace[0]['file']));
        $this->type = basename(dirname(dirname($trace[0]['file'])));
        
        global $sitepress_settings;
        if(empty($sitepress_settings)){
            $sitepress_settings = get_option('icl_sitepress_settings'); // fail safe
        }
        
        //echo '<pre>';
        //var_dump($sitepress_settings['packages']);
        //echo '</pre>';
        
        if(isset($sitepress_settings['packages'][$this->type][$this->name])){
            $this->settings = $sitepress_settings['packages'][$this->type][$this->name];    
        }else{
            $this->settings = array();    
        }
        
    }
    
    function __destruct(){
        
    }
    
    
    function add_option_checkbox($wpml_page, $option_label, $option_name, $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
        global $ICL_Packages;
        if(!$group_name){
            $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
        }
                                
        
        $ICL_Packages->packages_options[$wpml_page][$group_name][$this->name][] = array(
            'package_type' => $this->type,
            'option_type' => 'checkbox',
            'option_label' => $option_label,
            'option_name' => $option_name,
            'default_value' => $default_value,
            'extra_attributes' => $extra_attributes
        );
        
    }
        
    function add_option_text($wpml_page, $option_label, $option_name, $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
        global $ICL_Packages;
        if(!$group_name){
            $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
        }
                                
        $ICL_Packages->packages_options[$wpml_page][$group_name][$this->name][] = array(
            'package_type' => $this->type,
            'option_type' => 'text',
            'option_label' => $option_label,
            'option_name' => $option_name,
            'default_value' => $default_value,
            'extra_attributes' => $extra_attributes
        );
        
        
    }
    
    function add_option_textarea(){
        
        // TBD
        
    }
    
    function add_option_radio(){
        
        // TBD
        
    }

    function add_option_select(){
        
        // TBD
        
    }
    
        
}
  
?>
