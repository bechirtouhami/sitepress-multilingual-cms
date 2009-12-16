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
        if(!isset($this->settings['translation_sync_file_loaded']) || !$this->settings['translation_sync_file_loaded']){
            global $wpdb;
            $fh = fopen($this->package_path . '/res/hs_custom_fields.csv', 'rb');
            if($fh){                
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_plugins_texts WHERE plugin_name='{$this->data['Plugin']}'");
                while($data = fgetcsv($fh)){
                    $wpdb->insert($wpdb->prefix.'icl_plugins_texts', array(   
                            'plugin_name'=>substr($data[0],0,128),
                            'attribute_type' => substr($data[1], 0, 64),
                            'attribute_name' => substr($data[2], 0, 128),
                            'description'    => $data[3],
                            'translate'      => $data[4]
                        )
                    );
                }
                fclose($fh);
            }
            if($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}icl_plugins_texts WHERE plugin_name='".$this->data['Plugin']."'")){
                $this->settings['translation_sync_file_loaded'] = true;
                $this->save_settings();
            }                        
        }
        
    }
    
    function __destruct(){
        parent::__destruct();
    }
        
}

$WP_Headspace2_SEO_compatibility = new WP_Headspace2_SEO_compatibility();
?>
