<?php
class iclNavMenu{
    private $current_menu;
    
    function __construct(){
        add_action('init', array($this, 'init'));
        
    }
    
    function init(){
        global $pagenow;
        
        add_action('admin_head', array($this, 'get_current_menu'));
        
        // hook for saving menus
        add_action('wp_create_nav_menu', array($this, 'icl_wp_update_nav_menu'), 10, 2);
        add_action('wp_update_nav_menu', array($this, 'icl_wp_update_nav_menu'), 10, 2);
        
        // hook for saving menu items
        add_action('wp_update_nav_menu_item', array($this, 'icl_wp_update_nav_menu_item'), 10, 3);
        
        // add language controls for menus no option but javascript
        if($pagenow == 'nav-menus.php'){
            add_action('admin_footer', array($this, 'icl_nav_menu_language_controls'), 10);
        }
        
        add_action('wp_delete_nav_menu', array($this, 'icl_wp_delete_nav_menu'));
        
        wp_enqueue_script('wp_nav_menus', ICL_PLUGIN_URL . '/res/js/wp-nav-menus.js', ICL_SITEPRESS_VERSION, true);    
        
        if(isset($_POST['icl_wp_nav_menu_ajax'])){
            $this->ajax($_POST);
        }
    }
    
    function ajax($data){
        if($data['icl_wp_nav_menu_ajax'] == 'translation_of'){
            $this->_render_translation_of($data['lang'], $data['trid']);
        }
        exit;
    }
    
    function get_current_menu(){
        global $sitepress, $nav_menu_selected_id;
        $this->current_menu['id'] = $nav_menu_selected_id;
        if($this->current_menu['id']){
            $this->current_menu['trid'] = $sitepress->get_element_trid($this->current_menu['id'], 'tax_nav_menu');
            if($this->current_menu['trid']){
                $this->current_menu['translations'] = $sitepress->get_element_translations($this->current_menu['trid'], 'tax_nav_menu');    
            }else{
                $this->current_menu['translations'] = array();
            }
            foreach($this->current_menu['translations'] as $tr){
                if($this->current_menu['id'] == $tr->element_id){
                    $this->current_menu['language'] = $tr->language_code;
                }
            }
        }else{
            $this->current_menu['trid'] = isset($_GET['trid']) ? intval($_GET['trid']) : null;
            $this->current_menu['language'] = isset($_GET['lang']) ? $_GET['lang'] : $sitepress->get_default_language();
            $this->current_menu['translations'] = array();
        }        
    }
    
    function icl_wp_update_nav_menu($menu_id, $menu_data = null){
        global $sitepress;
        if($menu_data){
            if($_POST['icl_translation_of']){
                $trid = $sitepress->get_element_trid($_POST['icl_translation_of'], 'tax_nav_menu');
            }else{
                $trid = isset($_POST['icl_nav_menu_trid']) ? intval($_POST['icl_nav_menu_trid']) : null;                 
            }        
            $language_code = isset($_POST['icl_nav_menu_language']) ? $_POST['icl_nav_menu_language'] : $sitepress->get_default_language(); 
            $sitepress->set_element_language_details($menu_id, 'tax_nav_menu', $trid, $language_code);
        }
    }
    
    function icl_wp_delete_nav_menu($id){
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE element_id='$id' AND element_type='tax_nav_menu' LIMIT 1");
    }
    
    function icl_wp_update_nav_menu_item($menu_id, $menu_item_db_id, $args){
        global $sitepress;
        $trid = null;
        $language_code = $sitepress->get_default_language();
        $sitepress->set_element_language_details($menu_item_db_id, 'post_nav_menu_item', $trid, $language_code);
    }

    function icl_nav_menu_language_controls(){
        global $sitepress;
        if($this->current_menu['language'] != $sitepress->get_default_language()){
            $menus_wout_translation = $this->get_menus_without_translation($this->current_menu['language']);    
        }
        if(isset($this->current_menu['translations'][$sitepress->get_default_language()])){
            $menus_wout_translation['0'] = (object)array(
                'element_id'=>$this->current_menu['translations'][$sitepress->get_default_language()]->element_id,
                'trid'      =>'0',
                'name'      =>$this->current_menu['translations'][$sitepress->get_default_language()]->name
                );
        }
        
        $langsel = '<br class="clear" />';    
        
        // show translations links if this is not a new element              
        if($this->current_menu['id']){
            $langsel .= '<div class="howto" style="font-size:11px;font-style:normal;float:right;">';    
            $langsel .= __('Translations:', 'sitepress');    
            foreach($sitepress->get_active_languages() as $lang){            
                if($lang['code'] == $this->current_menu['language']) continue;
                if(isset($this->current_menu['translations'][$lang['code']])){
                    $tr_link = '<a style="text-decoration:none" title="'. esc_attr(__('edit translation', 'sitepress')).'" href="'.admin_url('nav-menus.php').
                        '?menu='.$this->current_menu['translations'][$lang['code']]->element_id.'">'.
                        $lang['display_name'] . '&nbsp;<img src="'.ICL_PLUGIN_URL.'/res/img/edit_translation.png" alt="'. esc_attr(__('edit', 'sitepress')).
                        '" width="12" height="12" /></a>';
                }else{
                    $tr_link = '<a style="text-decoration:none" title="'. esc_attr(__('add translation', 'sitepress')).'" href="'.admin_url('nav-menus.php').
                        '?action=edit&menu=0&trid='.$this->current_menu['trid'].'&lang='.$lang['code'].'">'. 
                        $lang['display_name'] . '&nbsp;<img src="'.ICL_PLUGIN_URL.'/res/img/add_translation.png" alt="'. esc_attr(__('add', 'sitepress')).
                        '" width="12" height="12" /></a>';
                }
                $trs[] = $tr_link ;
            }
            $langsel .= '&nbsp;' . join (', ', $trs);
            $langsel .= '</div>';    
        }
        
        // show languages dropdown                
        $langsel .= '<label class="menu-name-label howto"><span>' . __('Language', 'sitepress') . '</span>';
        $langsel .= '&nbsp;&nbsp;';    
        $langsel .= '<select name="icl_nav_menu_language" id="icl_menu_language">';    
        foreach($sitepress->get_active_languages() as $lang){
            if(isset($this->current_menu['translations'][$lang['code']]) && $this->current_menu['language'] != $lang['code']) continue;            
            $selected = $lang['code'] == $this->current_menu['language'] ? ' selected="selected"' : '';
            $langsel .= '<option value="' . $lang['code'] . '"' . $selected . '>' . $lang['display_name'] . '</option>';    
        }
        $langsel .= '</select>';
        $langsel .= '</label>';  
        
        // show translation of if this element is not in the default language and there are untranslated elements
        $langsel .= '<span id="icl_translation_of_wrap">';
        if($this->current_menu['language'] != $sitepress->get_default_language() && !empty($menus_wout_translation)){
            $langsel .= '<label class="menu-name-label howto"><span>' . __('Translation of:', 'sitepress') . '</span>';                
            $disabled = $this->current_menu['id'] ? '' : ' disabled="disabled"';
            $langsel .= '<select name="icl_translation_of" id="icl_menu_translation_of"'.$disabled.'>';    
            $langsel .= '<option value="">--' . __('none', 'sitepress') . '--</option>';                
            foreach($menus_wout_translation as $mtrid=>$m){
                if($this->current_menu['trid'] === $mtrid || $this->current_menu['translations'][$sitepress->get_default_language()]->element_id){
                    $selected = ' selected="selected"';
                }else{
                    $selected = '';
                }
                $langsel .= '<option value="' . $m->element_id . '"' . $selected . '>' . $m->name . '</option>';    
            }
            $langsel .= '</select>';
            $langsel .= '</label>';
        }
        $langsel .= '</span>';
        
        // add trid to form
        if($this->current_menu['trid']){
            $langsel .= '<input type="hidden" id="icl_nav_menu_trid" name="icl_nav_menu_trid" value="' . $this->current_menu['trid'] . '" />';
        }
        
        $langsel .= '';
        ?>
        <script type="text/javascript">
        addLoadEvent(function(){
            jQuery('#update-nav-menu .publishing-action').before('<?php echo addslashes($langsel); ?>');
        });
        </script>
        <?php            
    }
    
    function get_menus_without_translation($lang){
        global $sitepress, $wpdb;
        $res = $wpdb->get_results("
            SELECT ts.element_id, ts.trid, t.name 
            FROM {$wpdb->prefix}icl_translations ts
            JOIN {$wpdb->term_taxonomy} tx ON ts.element_id = tx.term_taxonomy_id
            JOIN {$wpdb->terms} t ON tx.term_id = t.term_id
            WHERE ts.element_type='tax_nav_menu' 
                AND ts.language_code='{$sitepress->get_default_language()}'
                AND tx.taxonomy = 'nav_menu'
        ");
        $menus = array();
        foreach($res as $row){            
            if(!$wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$row->trid}' AND language_code='{$lang}'")){
                $menus[$row->trid] = $row;
            }
        }       
        return $menus;
    }
    
    function _render_translation_of($lang, $trid = false){
        global $sitepress;
        $out = '';
        
        if($sitepress->get_default_language() != $lang){
            $menus = $this->get_menus_without_translation($lang);        
            $out .= '<label class="menu-name-label howto"><span>' . __('Translation of:', 'sitepress') . '</span>';                
            $out .= '<select name="icl_translation_of" id="icl_menu_translation_of">';    
            $out .= '<option value="">--' . __('none', 'sitepress') . '--</option>';                
            foreach($menus as $mtrid=>$m){
                if(intval($trid) === $mtrid){
                    $selected = ' selected="selected"';
                }else{
                    $selected = '';
                }
                $out .= '<option value="' . $m->element_id . '"' . $selected . '>' . $m->name . '</option>';    
            }
            $out .= '</select>';
            $out .= '</label>';
        }
                
        echo $out;
    }
    
}  
?>