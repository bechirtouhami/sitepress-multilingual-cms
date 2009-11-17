<?php
  
define('ICL_EXTRAS_DEFAULT_GROUP_NAME', __('Extra options', 'sitepress'));
  

  
function icl_extras_add_option_checkbox($wpml_page, $package_name, $option_label, $option_name, 
                                        $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
    global $icl_extras;
    if(!$group_name){
        $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
    }
    $icl_extras[$wpml_page][$group_name][$package_name][] = array(
        'option_type' => 'checkbox',
        'option_label' => $option_label,
        'option_name' => $option_name,
        'default_value' => $default_value,
        'extra_attributes' => $extra_attributes
    );
} 


function icl_extras_add_option_text($wpml_page, $package_name, $option_label, $option_name, 
                                        $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
    global $icl_extras;
    if(!$group_name){
        $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
    }
    $icl_extras[$wpml_page][$group_name][$package_name][] = array(
        'option_type' => 'text',
        'option_label' => $option_label,
        'option_name' => $option_name,
        'default_value' => $default_value,
        'extra_attributes' => $extra_attributes
    );
} 


function _icl_extras_render_form_title($group_name){
    echo '<h3>' . $group_name . '</h3>';
}

function _icl_extras_render_checkbox($package_name, $option_name, $default_value = '', $extra_attributes = array()){
    global $sitepress_settings;
    
    if(isset($sitepress_settings['packages'][$package_name][$option_name]) && $sitepress_settings['packages'][$package_name][$option_name] 
        || !isset($sitepress_settings['packages'][$package_name][$option_name]) && $default_value=='checked'){
        $checked = ' checked="checked"';
    }else{
        $checked = '';
    }
    $ea = '';
    if(!empty($extra_attributes)){
        foreach($extra_attributes as $k=>$v){
            $ea .= ' ' . $k . '=' . $v;
        }        
    }
    echo '<input type="checkbox" id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_name.']['.$option_name.']" value="1"' .$checked . $ea . ' />';
}

function _icl_extras_render_text($package_name, $option_name, $default_value = '', $extra_attributes = array()){
    global $sitepress_settings;

    if(isset($sitepress_settings['packages'][$package_name][$option_name])){
        $value = $sitepress_settings['packages'][$package_name][$option_name];
    }else{
        $value = '';
    }    
    
    $ea = '';
    if(!empty($extra_attributes)){
        foreach($extra_attributes as $k=>$v){
            $ea .= ' ' . $k . '=' . $v;
        }        
    }
    echo '<input type="text" id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_name.']['.$option_name.']" value="'.$value.'"' . $ea . ' />';
}



function icl_extras_render_forms(){
    $page = $_GET['page'];
    global $icl_extras;
    if(isset($icl_extras[$page])){
        $frm_idx = 0;
        foreach($icl_extras[$page] as $group_name => $options){        
            _icl_extras_render_form_title($group_name);
            echo '<form name="icl_extras_form_'.$frm_idx.'" method="post" action="admin.php?page='.$page.'&amp;updated=true#icl-extras-'.$frm_idx.'">';
            echo '<input type="hidden" name="icl_extras_frm_idx" value="'.$frm_idx.'" />';
            echo '<input type="hidden" name="icl_extras_group" value="'.$group_name.'" />';
            if(isset($_GET['updated']) && $_GET['updated']==$frm_idx){
                echo '<div class="icl_form_success">';
                echo __('Options saved', 'sitepress');
                echo '</div>';
            }
            echo '<table id="icl-extras-'.$frm_idx.'">';
            foreach($options as $package_name => $package){
                foreach($package as $o){
                    echo '<tr>';
                    echo '<th><label for="icl_extras_'.$package_name.'_'.$o['option_name'].'">' . $o['option_label'] . '</label></th>';
                    echo '<td>';                
                    switch($o['option_type']){                    
                        case 'checkbox':
                            _icl_extras_render_checkbox($package_name, $o['option_name'], $o['default_value'], $o['extra_attributes']);                        
                            break;    
                        case 'text':
                            _icl_extras_render_text($package_name, $o['option_name'], $o['default_value'], $o['extra_attributes']);                        
                            break;                                
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            }  
            echo '<tr><td colspan="2" align="right"><input name="icl_extras_submit" class="button" type="submit" value="'.__('Save', 'sitepress').'"></td></tr>';  
            echo '</table>';
            $frm_idx++;
            echo '</form>';
            
        }
    }
}


function icl_extras_process_forms(){
    global $sitepress, $sitepress_settings, $icl_extras;    
    
    $iclsettings['packages'] = $sitepress_settings['packages'];
    
    foreach($_POST['icl_extras'] as $package => $options){
        foreach($options as $option_name => $option_value){
            $iclsettings['packages'][$package][$option_name] = $option_value;
        }
        
    }
    
    $page = $_GET['page'];
    foreach($icl_extras[$page] as $group_name=>$options){
        foreach($options as $package_name => $package){
            foreach($package as $o){                
                switch($o['option_type']){                    
                    case 'checkbox';                                                     
                        if(!isset($_POST['icl_extras'][$package_name][$o['option_name']]) && $_POST['icl_extras_group'] == $group_name){
                            $iclsettings['packages'][$package_name][$o['option_name']] = 0;
                        }
                        break;
                }
            }
        }
    }
    
    $sitepress->save_settings($iclsettings);
    
    wp_redirect('admin.php?page='.$page.'&updated=' . $_POST['icl_extras_frm_idx']);
}
?>
