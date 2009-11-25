<?php
  
abstract class WPML_Package{
    var $name;
    var $data;
    var $settings;
    var $type;
    var $package_path;
    var $package_url;
    private $_resources = array();
    
    
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
        
        add_action('wp_head', array($this, '_echo_js'), 30);
        add_action('wp_head', array($this, '_echo_css'), 30);
        
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
    
    function add_options($wpml_page, $options_array, $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME){
        if(is_array($options_array) && !empty($options_array)){
            foreach($options_array as $option_name => $option){
                switch($option['option_type']){
                    case 'checkbox':
                        $this->add_option_checkbox($wpml_page, $option['option_label'], $option_name, $group_name, $option['default_value'], $option['extra_attributes']);
                        break;
                    case 'text':
                        $this->add_option_text($wpml_page, $option['option_label'], $option_name, $group_name, $option['default_value'], $option['extra_attributes']);
                        break;
                    case 'textarea':
                        $this->add_option_textarea($wpml_page, $option['option_label'], $option_name, $group_name, $option['default_value'], $option['extra_attributes']);
                        break;
                    case 'radio':
                        $this->add_option_radio($wpml_page, $option['option_label'], $option_name, $option['values'], $group_name, $option['default_value'], $option['extra_attributes']);
                        break;
                    case 'select':
                        $this->add_option_select($wpml_page, $option['option_label'], $option_name, $option['values'], $group_name, $option['default_value'], $option['extra_attributes']);
                        break;
                }
            }
        }            
    }
    
    // $file is relative to the package root folder
    function load_js($file){
        $this->_resources['js'][] = $file;
    }
    
    function _echo_js($file){
        if(!empty($this->_resources['js']) && is_array($this->_resources['js'])){
            foreach($this->_resources['css'] as $file){
                echo '<script type="text/javascript" src="'.$this->package_url . '/' . $file.'?v='. $this->data['Version'] .'"></script>'."\n";
            }
        }    
    }
    
    // $file is relative to the package root folder
    function load_css($file){
        $this->_resources['css'][] = $file;         
    }
    
    function _echo_css($file){        
        if(!empty($this->_resources['css']) && is_array($this->_resources['css'])){
            foreach($this->_resources['css'] as $file){
                echo '<link rel="stylesheet" type="text/css" href="'.$this->package_url . '/' . $file.'?v='. $this->data['Version'] .'" />'."\n";
            }
        }            
    }
    
    
    // Theme packages functions. - start (added SJ)
    // Used to filter menu.
    function filter_home_link($menu) {
        return str_replace('href="'.get_option('home').'"','href="'.icl_get_home_url().'"',$menu);
    }

    function language_selector_header() {
        do_action('icl_language_selector');
    }

    function language_selector_footer() {
        $languages = icl_get_languages('skip_missing='.intval($this->settings['footer_skip_languages']));
        if(!empty($languages)){
            global $sitepress_settings;
            echo '
                <div id="icl_lang_selector_footer">
                    <ul>
                    ';
                foreach($languages as $l){
                    echo '<li>';
                    if(!$l['active']) echo '<a href="'.$l['url'].'">';
                    echo '<img src="'.$l['country_flag_url'].'" alt="'.$l['language_code'].'" width="18" height="12" />&nbsp;';
                    if(!$l['active']) echo '</a>';
                     if(!$l['active']) echo '<a href="'.$l['url'].'">';
                    echo icl_disp_language( $sitepress_settings['icl_lso_native_lang'] ? $l['native_name'] : null, $sitepress_settings['icl_lso_display_lang'] ? $l['translated_name'] : null );
                    if(!$l['active']) echo '</a>';
                    echo '</li>
                    ';
                }
            echo '
                    </ul>
                </div>';
            }
    }

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
         if ( $this->settings['post_available_position'] == 'top')
            return '<p>' . $out . '</p>' . $content;
        else return $content . '<p>' . $out . '</p>';
    }

        // This function should check if sidebar switcher is enabled
    function check_widget(){
    
    }
    // Theme packages functions. - end (added SJ)
        
        
}
  
?>
