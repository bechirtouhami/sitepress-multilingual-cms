<?php
  
function icl_pt_get_texts(){
    global $wpdb;
    $active_plugins = get_option('active_plugins');    
    $enabled_plugins = (array)get_option('icl_plugins_texts_enabled');
    foreach($active_plugins as $ap){
        $aps[] = "'" . $ap . "'";
    }
    $res = $wpdb->get_results("SELECT plugin_name, attribute_name, translate FROM {$wpdb->prefix}icl_plugins_texts WHERE plugin_name IN (". join(',', $aps).") ");
    foreach($res as $r){  
        $t = $r->translate?__('translate','sitepress'):__('synchronize','sitepress');      
        $rs[$r->plugin_name][] = $r->attribute_name . ' (' . $t . ')';
    }
    
    foreach($active_plugins as $ap){    
        $plugin_name_short = str_replace('_', ' ', dirname($ap));    
        if(isset($rs[$ap])){
            $fields_list = join(', ', $rs[$ap]);
            $active = 1;
        }else{
            $fields_list = sprintf(__('WPML doesn\'t know how to translate this plugin. If it has texts that require translation, contact us by opening an issue in our forum: %s', 'sitepress'), '<a href="http://forum.wpml.org">http://forum.wpml.org</a>');
            $active = 0;
        }                
        $texts[] = array(
            'active' => $active,
            'enabled' => intval(in_array($ap, $enabled_plugins)),
            'plugin_name' => $ap,
            'plugin_name_short' => $plugin_name_short,
            'fields_list' => $fields_list
        );
        
    }
    return $texts;
} 

function icl_get_posts_translatable_fields(){
    global $wpdb;
    $enabled_plugins = (array)get_option('icl_plugins_texts_enabled');
    foreach($enabled_plugins as $ap){
        $aps[] = "'" . $ap . "'";
    }    
    $res = $wpdb->get_results("SELECT attribute_name, attribute_type, translate FROM {$wpdb->prefix}icl_plugins_texts WHERE plugin_name IN (". join(',', $aps).") ");
    return $res;
} 
?>