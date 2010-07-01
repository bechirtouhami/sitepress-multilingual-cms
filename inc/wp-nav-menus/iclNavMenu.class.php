<?php
class iclNavMenu{
    private $current_menu;
    private $current_lang;
    
    function __construct(){
        global $pagenow;
        
        add_action('init', array($this, 'init'));
        
        // hook for saving menus
        add_action('wp_create_nav_menu', array($this, 'icl_wp_update_nav_menu'), 10, 2);
        add_action('wp_update_nav_menu', array($this, 'icl_wp_update_nav_menu'), 10, 2);
        
        // hook for saving menu items
        add_action('wp_update_nav_menu_item', array($this, 'icl_wp_update_nav_menu_item'), 10, 3);
        
        // add language controls for menus no option but javascript
        if($pagenow == 'nav-menus.php'){
            add_action('admin_footer', array($this, 'icl_nav_menu_language_controls'), 10);
            
            wp_enqueue_script('wp_nav_menus', ICL_PLUGIN_URL . '/res/js/wp-nav-menus.js', ICL_SITEPRESS_VERSION, true);    
            wp_enqueue_style('wp_nav_menus_css', ICL_PLUGIN_URL . '/res/css/wp-nav-menus.css', array(), ICL_SITEPRESS_VERSION,'all');    
            
            // filter posts by language
            add_action('parse_query', array($this, 'parse_query'));
            
            // filter taxonomies by language
            //add_action('get_terms', array($this, 'get_terms'));
            
            // filter menus by language
            add_filter('get_terms', array($this, 'get_terms_filter'), 1, 3);        
        }
        
        add_action('wp_delete_nav_menu', array($this, 'icl_wp_delete_nav_menu'));
        
        
        
        add_filter('theme_mod_nav_menu_locations', array($this, 'theme_mod_nav_menu_locations'));
        $theme = get_current_theme();
        add_filter('pre_update_option_mods_' . $theme, array($this, 'pre_update_theme_mods_theme'));
        
    }
    
    function parse_query($q){
        
        // not filtering nav_menu_item
        if($q->query_vars['post_type'] == 'nav_menu_item'){
            return $q;
        } 
        
        $q->query_vars['suppress_filters'] = 0;
        $q->query_vars['lang'] = 'ru';
        return $q;
    }
    
    function theme_mod_nav_menu_locations($val){
        global $sitepress;
        if($sitepress->get_default_language() != $this->current_lang){
            $val['primary'] = icl_object_id($val['primary'], 'nav_menu');   
        }
        return $val;
    }
    
    function pre_update_theme_mods_theme($val){
        global $sitepress;
        $val['nav_menu_locations']['primary'] = icl_object_id($val['nav_menu_locations']['primary'], 'nav_menu',true, $sitepress->get_default_language());   
        return $val;
    }
    
    function init(){
        global $sitepress;
        
        $this->get_current_menu();
        
        if($this->current_menu['language']){
            $this->current_lang = $this->current_menu['language'];    
        }elseif(isset($_REQUEST['lang'])){
            $this->current_lang = $_REQUEST['lang'];    
        }else{
            $this->current_lang = $sitepress->get_default_language();
        }
        
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
    
    function _get_menu_language($menu_id){
        global $sitepress;
        $lang = $sitepress->get_element_language_details($menu_id, 'tax_nav_menu');
        return $lang;
    }
    
    /**
    * gets first menu in a specific language
    * used to override nav_menu_recently_edited when a different language is selected
    * @param $lang
    * @return int
    */
    function _get_first_menu($lang){
        global $wpdb;
        $id = $wpdb->get_var("SELECT MIN(element_id) FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_nav_menu' AND language_code='".$wpdb->escape($lang)."'");    
        return (int) $id;
    }
    
    function get_current_menu(){
        global $sitepress;
        
        $nav_menu_recently_edited = get_user_option( 'nav_menu_recently_edited' );
        $nav_menu_recently_edited_lang = $this->_get_menu_language($nav_menu_recently_edited);
                
        if( !isset( $_REQUEST['menu'] ) && isset($_GET['lang']) && $nav_menu_recently_edited_lang->language_code != $_GET['lang']){
            // if no menu is specified and the language is set override nav_menu_recently_edited
            $nav_menu_selected_id = $this->_get_first_menu($_GET['lang']);    
            update_user_option(get_current_user_id(), 'nav_menu_recently_edited', $nav_menu_selected_id);
        }elseif( !isset( $_REQUEST['menu'] ) && !isset($_GET['lang']) && $nav_menu_recently_edited_lang->language_code != $sitepress->get_default_language()){
            // if no menu is specified, no language is set, override nav_menu_recently_edited if its language is different than default           
            $nav_menu_selected_id = $this->_get_first_menu($sitepress->get_default_language());    
            update_user_option(get_current_user_id(), 'nav_menu_recently_edited', $nav_menu_selected_id);
        }elseif(isset( $_REQUEST['menu'] )){
            $nav_menu_selected_id = $_REQUEST['menu'];
        }else{
            $nav_menu_selected_id = $nav_menu_recently_edited;
        }
        
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
                    $this->current_lang = $this->current_menu['language'];
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
            $langsel .= '<div class="howto icl_nav_menu_text" style="float:right;">';    
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
            jQuery('#side-sortables').before('<?php $this->languages_menu() ?>');
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
    
    function get_menus_by_language(){
        global $wpdb, $sitepress;
        $res = $wpdb->get_results("
            SELECT lt.name AS language_name, l.code AS lang, COUNT(ts.translation_id) AS c
            FROM {$wpdb->prefix}icl_languages l
                JOIN {$wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code
                JOIN {$wpdb->prefix}icl_translations ts ON l.code = ts.language_code            
            WHERE lt.display_language_code='".$sitepress->get_admin_language()."'
                AND l.active = 1
                AND ts.element_type = 'tax_nav_menu'
            GROUP BY ts.language_code
        ");
        foreach($res as $row){
            $langs[$row->lang] = $row;
        }        
        return $langs;
    }
    
    function languages_menu($echo = true){
        global $sitepress;
        $langs = $this->get_menus_by_language();
        $url = admin_url('nav-menus.php');
        foreach($langs as $l){
            $class = $l->lang == $this->current_lang ? ' class="current"' : '';
            $urlsuff = $l->lang != $sitepress->get_default_language() ? '?lang=' . $l->lang : '';
            $ls[] = '<a href="'.$url.$urlsuff.'"'.$class.'>'.esc_html($l->language_name).' ('.$l->c.')</a>';
        }
        $ls_string = '<div class="icl_lang_menu icl_nav_menu_text">';
        $ls_string .= join('&nbsp;|&nbsp;', $ls);
        $ls_string .= '</div>';
        if($echo){
            echo $ls_string;
        }else{
            return $ls_string;
        }
    }
    
    function get_terms_filter($terms, $taxonomies, $args){
        global $wpdb;
        
        if(!empty($terms)){
            //print_r($taxonomies);
            foreach($taxonomies as $t){
                $txs[] = 'tax_' . $t;
            }
            $el_types = "'".join(',',$txs)."'";
            
            // get all term_taxonomy_id's
            $tt = array();
            foreach($terms as $t){
                if(is_object($t)){
                    $tt[] = $t->term_taxonomy_id;    
                }else{
                    $tt[] = $t;
                }
            }
            // filter the one in the current language
            if(!empty($tt)){
                $ftt = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations 
                    WHERE element_type IN ({$el_types}) AND element_id IN (".join(',',$tt).") AND language_code='{$this->current_lang}'");
            }
            foreach($terms as $k=>$v){
                if(!in_array($v->term_taxonomy_id, $ftt)){
                    unset($terms[$k]);
                }
            }
        }        
        return $terms;        
    }
    
} 

?>