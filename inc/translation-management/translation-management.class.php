<?php
  
class TranslationManagement{
    
    private $selected_translator = array('ID'=>0);
    public $messages = array();
    
    function __construct(){
        $current_user = wp_get_current_user();
        
        //$current_user->add_cap('translate');
        
        //debug_array($current_user);
        
        //add_filter('user_has_cap', array($this, 'user_has_cap'), 10, 3);
        
        //$current_user->has_cap('translate');
        
        add_action('admin_menu', array($this, 'menu'));
        
        //debug_array($GLOBALS['wp_filter']['admin_menu']);
        //debug_array(array_keys($GLOBALS));
        
        add_action('init', array($this, 'init'));
        
        if(isset($_GET['icl_tm_message'])){
            $this->messages[] = array(
                'type' => isset($_GET['icl_tm_message_type']) ? $_GET['icl_tm_message_type'] : 'updated',
                'text'  => $_GET['icl_tm_message']
            );        
        }
            
        
    }
    
    function init(){
        if(isset($_POST['icl_tm_action'])){
            $this->process_request($_POST['icl_tm_action'], $_POST);
        }elseif(isset($_GET['icl_tm_action'])){
            $this->process_request($_GET['icl_tm_action'], $_GET);
        }
        
        
        add_action('icl_tm_messages', array($this, 'show_messages'));
    }
    
    function process_request($action, $data){        
        switch($action){
            case 'add_translator':
                if(wp_create_nonce('add_translator') == $data['add_translator_nonce']){
                    $this->add_translator($data['user_id'], $data['lang_pairs']);
                }
                break;
            case 'remove_translator':
                if(wp_create_nonce('remove_translator') == $data['remove_translator_nonce']){
                    $this->remove_translator($data['user_id']);
                }
                break;
            case 'edit':
                $this->selected_translator['ID'] = intval($_GET['user_id']);
                break;
        }
    }
    
    function show_messages(){
        if(!empty($this->messages)){
            foreach($this->messages as $m){
                echo '<div class="'.$m['type'].' below-h2"><p>' . $m['text'] . '</p></div>';
            }
        }
    }
    
    function add_translator($user_id, $language_pairs){
        global $wpdb;
        
        $user = new WP_User($user_id);
        $user->add_cap('translate');
        update_user_meta($user_id, $wpdb->prefix . 'language_pairs',  $language_pairs);
        
        wp_redirect('admin.php?page='.ICL_PLUGIN_PATH.'/menu/translation-management.php&icl_tm_message='.urlencode(sprintf(__('%s has been added as a translator for this site.','sitepress'),$user->data->display_name)).'&icl_tm_message_type=updated');
        
    }
    
    function remove_translator($user_id){
        global $wpdb;
        $user = new WP_User($user_id);
        $user->remove_cap('translate');
        delete_user_meta($user_id, $wpdb->prefix . 'language_pairs');
        wp_redirect('admin.php?page='.ICL_PLUGIN_PATH.'/menu/translation-management.php&icl_tm_message='.urlencode(sprintf(__('%s has been removed as a translator for this site.','sitepress'),$user->data->display_name)).'&icl_tm_message_type=updated');
    }
    
    public function get_blog_not_translators(){
        global $wpdb;
        $sql = "SELECT u.ID, u.user_login, u.display_name, m.meta_value AS caps 
                FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.id=m.user_id AND m.meta_key LIKE '{$wpdb->prefix}capabilities'";
        $res = $wpdb->get_results($sql);
        $users = array();
        foreach($res as $row){
            $user = new WP_User($row->ID);
            $caps = @unserialize($row->caps);
            if(!isset($caps['translate'])){
                $users[] = $row;    
            }
        }
        return $users;
    }

    public function get_blog_translators(){
        global $wpdb;
        $sql = "SELECT u.ID, u.user_login, u.display_name, m.meta_value AS caps  
                FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.id=m.user_id AND m.meta_key LIKE '{$wpdb->prefix}capabilities'";
        $res = $wpdb->get_results($sql);
        $users = array();
        foreach($res as $row){
            $user = new WP_User($row->ID);
            $caps = @unserialize($row->caps);
            if(isset($caps['translate'])){
                $users[] = $row;    
            }
        }
        return $users;
    }
    
    function get_selected_translator(){
        global $wpdb;
        if($this->selected_translator['ID']){
            $user = new WP_User($this->selected_translator['ID']);
            $this->selected_translator['display_name'] =  $user->data->display_name;
            $this->selected_translator['user_login'] =  $user->data->user_login;
            $this->selected_translator['language_pairs'] = get_user_meta($this->selected_translator['ID'], $wpdb->prefix.'language_pairs', true);
        }else{
            $this->selected_translator['ID'] = 0;
        }
        return (object)$this->selected_translator;    
    }
    
    function user_has_cap($allcaps, $caps, $args){
        global $sitepress_settings, $wpdb;
        //$icl_translators = $sitepress['translators'][$wpdb->blogid]; 
        //debug_array(func_get_args());
    }
    
    /* MENU */
    function menu(){
        global $sitepress_settings;
        if($sitepress_settings['basic_menu']){        
            $top_level_page = 'languages';
        }else{
            $top_level_page = 'overview';
        }
        add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/'.$top_level_page.'.php', __('Translation Management','sitepress'), __('Translation Management','sitepress'), 
            'manage_options', basename(ICL_PLUGIN_PATH).'/menu/translation-management.php');
    }
    
}
  
?>
