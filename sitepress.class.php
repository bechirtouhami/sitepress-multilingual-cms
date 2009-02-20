<?php
class Sitepress{
   
    private $settings;
    
    function __construct(){
        
        $this->settings = get_option('icl_sitepress_settings');
        
        // Ajax feedback
        if(isset($_POST['icl_ajx_action'])){
            add_action('init', array($this,'ajax_responses'));
        }
        
        
        // Administration menus
        add_action('admin_menu', array($this, 'administration_menu'));
        
        
    }
    
    function ajax_responses(){
        switch($_POST['icl_ajx_action']){
            case 'set_active_languages':
                $lang_codes = explode(',',$_POST['langs']);
                if($this->set_active_languages($lang_codes)){                    
                    echo '1|';
                    $active_langs = $this->get_active_languages();
                    foreach($active_langs as $lang){
                        $is_default = ($this->get_default_language()==$lang['code']);
                        ?><li <?php if($is_default):?>class="default_language"<?php endif;?>><label><input type="radio" name="default_language" value="<?php echo $lang['code'] ?>" <?php if($is_default):?>checked="checked"<?php endif;?>> <?php echo $lang['english_name'] ?><?php if($is_default):?>(<?php echo __('default') ?>)<?php endif?></label></li><?php
                    }                                        
                }else{
                    echo '0';
                }
                break;
            case 'set_default_language':
                $previous_default = $this->get_default_language();
                if($this->set_default_language($_POST['lang'])){
                    echo '1|'.$previous_default;
                }else{
                    echo'0';
                }
                break;
            default:
                echo __('Invalid action','sitepress');                
        }    
        exit;
    }
    
    function administration_menu(){
        add_action('admin_head', array($this,'js_scripts_setup'));
        add_action('admin_head', array($this,'css_setup'));
        add_menu_page(__('Sitepress','sitepress'), __('Sitepress','sitepress'), 'manage_options', ICL_PLUGIN_PATH , array($this, 'administration_menu_content'));
    }
    
    function administration_menu_content(){
        
        if($this->settings['installed']){
            
        }else{
            $active_languages = $this->get_active_languages();            
            $languages = $this->get_languages();            
            include ICL_PLUGIN_PATH . '/menu/setup.php';
        }
        
    }

    function get_active_languages(){
        global $wpdb;
        $res = $wpdb->get_results("SELECT id, code, english_name, active FROM {$wpdb->prefix}icl_languages WHERE active=1", ARRAY_A);
        $languages = array();
        foreach($res as $r){
            $languages[] = $r;
        }
        return $languages;
    }
    
    function set_active_languages($arr){
        global $wpdb;
        if(!empty($arr)){
            foreach($arr as $code){
                $tmp[] = "'" . mysql_real_escape_string(trim($code)) . "'";
            }
            $codes = '(' . join(',',$tmp) . ')';
            $wpdb->update($wpdb->prefix.'icl_languages', array('active'=>0), array('1'=>'1'));
            $wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET active=1 WHERE code IN {$codes}");            
        }
        return true;
    }
    
    function get_languages(){
        global $wpdb;
        $res = $wpdb->get_results("SELECT id, code, english_name, major, active FROM {$wpdb->prefix}icl_languages ORDER BY major DESC, english_name ASC", ARRAY_A);
        $languages = array();
        foreach($res as $r){
            $languages[] = $r;
        }
        return $languages;
    }

    function get_default_language(){        
        return $this->settings['default_language'];
    }
    
    function set_default_language($code){        
        $this->settings['default_language'] = $code;
        update_option('icl_sitepress_settings', $this->settings);
        return true;
    }
    
    function js_scripts_setup(){
        ?>
        <script type="text/javascript">
        var icl_ajx_url = '<?php echo $_SERVER['REQUEST_URI'] ?>';
        var icl_ajx_saved = '<?php echo __('Data saved') ?>';
        var icl_ajx_error = '<?php echo __('Error: data not saved') ?>';
        var icl_default_mark = '<?php echo __('default') ?>';
        
        </script>
        <script type="text/javascript">
        addLoadEvent(function(){     
            jQuery('#icl_change_default_button').click(editingDefaultLanguage);
            jQuery('#icl_save_default_button').click(saveDefaultLanguage);
            jQuery('#icl_cancel_default_button').click(doneEditingDefaultLanguage);
            jQuery('#icl_add_remove_button').click(showLanguagePicker);            
            jQuery('#icl_cancel_language_selection').click(hideLanguagePicker);
            jQuery('#icl_save_language_selection').click(saveLanguageSelection);                        
            jQuery('#icl_enabled_languages input').attr('disabled','disabled');
        });
        function editingDefaultLanguage(){
            jQuery('#icl_change_default_button').hide();
            jQuery('#icl_save_default_button').show();
            jQuery('#icl_cancel_default_button').show();
            jQuery('#icl_enabled_languages input').css('visibility','visible');
            jQuery('#icl_enabled_languages input').removeAttr('disabled');
            jQuery('#icl_add_remove_button').hide();
            
        }
        function doneEditingDefaultLanguage(){
            jQuery('#icl_change_default_button').show();
            jQuery('#icl_save_default_button').hide();
            jQuery('#icl_cancel_default_button').hide();
            jQuery('#icl_enabled_languages input').css('visibility','hidden');
            jQuery('#icl_enabled_languages input').attr('disabled','disabled');
            jQuery('#icl_add_remove_button').show();
        }        
        function saveDefaultLanguage(){
            var arr = jQuery('#icl_enabled_languages input[type="radio"]');            
            var def_lang;
            jQuery.each(arr, function() {                
                if(this.checked){
                    def_lang = this.value;    
                }                
            });             
            jQuery.ajax({
                type: "POST",
                url: icl_ajx_url,
                data: "icl_ajx_action=set_default_language&lang="+def_lang,
                success: function(msg){
                    spl = msg.split('|');
                    if(spl[0]=='1'){
                        fadeInAjxResp(icl_ajx_saved);                         
                        jQuery('#icl_avail_languages_picker input[value="'+spl[1]+'"]').removeAttr('disabled');
                        jQuery('#icl_avail_languages_picker input[value="'+def_lang+'"]').attr('disabled','disabled');
                        jQuery('#icl_enabled_languages li').removeClass('default_language');
                        jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').parent().parent().attr('class','default_language');
                        jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').parent().append(' ('+icl_default_mark+')');
                        jQuery('#icl_enabled_languages li input').removeAttr('checked');
                        jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').attr('checked','checked');
                        jQuery('#icl_enabled_languages input[value="'+spl[1]+'"]').parent().html(jQuery('#icl_enabled_languages input[value="'+spl[1]+'"]').parent().html().replace('('+icl_default_mark+')',''));
                        doneEditingDefaultLanguage();                        
                    }else{                        
                        fadeInAjxResp(icl_ajx_error,true);
                    }                    
                }
            });
            
        }        
        function showLanguagePicker(){
            jQuery('#icl_avail_languages_picker').slideDown();
            jQuery('#icl_add_remove_button').fadeOut();
            jQuery('#icl_change_default_button').fadeOut();
        }
        function hideLanguagePicker(){
            jQuery('#icl_avail_languages_picker').slideUp();
            jQuery('#icl_add_remove_button').fadeIn();
            jQuery('#icl_change_default_button').fadeIn();
        } 
        function saveLanguageSelection(){
            var arr = jQuery('#icl_avail_languages_picker ul input[type="checkbox"]');            
            var sel_lang = new Array();
            jQuery.each(arr, function() {
                if(this.checked){
                    sel_lang.push(this.value);
                }                
            }); 
            jQuery.ajax({
                type: "POST",
                url: icl_ajx_url,
                data: "icl_ajx_action=set_active_languages&langs="+sel_lang.join(','),
                success: function(msg){
                    spl = msg.split('|');
                    if(spl[0]=='1'){
                        fadeInAjxResp(icl_ajx_saved);                         
                        jQuery('#icl_enabled_languages').html(spl[1]);
                    }else{                        
                        fadeInAjxResp(icl_ajx_error,true);
                    }                    
                }
            });

            hideLanguagePicker();
            
        }       

        function fadeInAjxResp(msg,err){
            if(err != undefined){
                col = jQuery('#icl_ajx_response').css('color');
                jQuery('#icl_ajx_response').css('color','red');
            }
            jQuery('#icl_ajx_response').html('<span>'+msg+'<span>');
            jQuery('#icl_ajx_response').fadeIn();
            window.setTimeout(fadeOutAjxResp, 3000);
            if(err != undefined){
                jQuery('#icl_ajx_response').css('color',col);
            }
        }
        
        function fadeOutAjxResp(){
            jQuery('#icl_ajx_response').fadeOut();
        }
        </script>
        <?php
    }
    
    function css_setup(){
        ?><link rel="stylesheet" href="<?php echo ICL_PLUGIN_URL ?>/res/css/setup.css" type="text/css" media="all" /><?php
    }
    
}  
?>
