<?php
// included from Sitepress::reminder_popups
//

    // NOTE: this is also used for other popup links to ICanLocalize

    global $wpdb;
    
	
    $target = $_GET['target'];
    if(preg_match('|^@select-translators;([^;]+);([^;]+)@|', $target, $matches)){
        $from_lang = $matches[1];
        $to_lang = $matches[2];

        require_once ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
        require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';
        require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
        $icl_query = new ICanLocalizeQuery();                
        
        foreach($this->get_active_languages() as $lang){
            $lang_server[$lang['code']] = apply_filters('icl_server_languages_map', $lang['english_name']);
        }        
        if(!$this->icl_account_configured()){
            $user['create_account'] = 1;
            $user['anon'] = 1;
            $user['platform_kind'] = 2;
            $user['cms_kind'] = 1;
            $user['blogid'] = $wpdb->blogid?$wpdb->blogid:1;
            $user['url'] = get_option('home');
            $user['title'] = get_option('blogname');
            $user['description'] = $this->settings['icl_site_description'];
            $user['is_verified'] = 1;                
           if(defined('ICL_AFFILIATE_ID') && defined('ICL_AFFILIATE_KEY')){
                $user['affiliate_id'] = ICL_AFFILIATE_ID;
                $user['affiliate_key'] = ICL_AFFILIATE_KEY;
            }
            $user['interview_translators'] = $this->settings['interview_translators'];
            $user['project_kind'] = $this->settings['website_kind'];
            $user['pickup_type'] = intval($this->settings['translation_pickup_method']);
            $notifications = 0;
            if ( $this->settings['icl_notify_complete']){
                $notifications += 1;
            }
            if ( $this->settings['alert_delay']){
                $notifications += 2;
            }
            $user['notifications'] = $notifications;
            $user['ignore_languages'] = 1;
            
            $user['from_language1'] = $lang_server[$from_lang]; 
            $user['to_language1'] = $lang_server[$to_lang]; 
            
            list($site_id, $access_key) = $icl_query->createAccount($user);                
            if($site_id && $access_key){
                $iclsettings['site_id'] = $site_id;
                $iclsettings['access_key'] = $access_key;
                $iclsettings['language_pairs'][$from_lang][$to_lang] = 1;
                $this->save_settings($iclsettings);
            }
        }else{
            
            $iclsettings['language_pairs'] = $this->settings['language_pairs'];
            $iclsettings['language_pairs'][$from_lang][$to_lang] = 1;
            $this->save_settings($iclsettings);
            
            // update account - add language pair
            foreach($this->settings['language_pairs'] as $k=>$v){
                foreach($v as $k2=>$v2){
                    $incr++;
                    $data['from_language'.$incr] = $lang_server[$k]; 
                    $data['to_language'.$incr] = $lang_server[$k2];
                }    
            }
            
            $data['site_id'] = $this->settings['site_id'];                    
            $data['accesskey'] = $this->settings['access_key'];
            $data['create_account'] = 0;
            
            $icl_query->updateAccount($data);
            
        }
        $icl_query = new ICanLocalizeQuery($this->settings['site_id'], $this->settings['access_key']);                
        $website_details = $icl_query->get_website_details();
        $translation_languages = $website_details['translation_languages']['translation_language'];
        if(isset($translation_languages['attr'])){
            $buf = $translation_languages;
            unset($translation_languages);
            $translation_languages[0] = $buff;
            unset($buff);
        }
        
        foreach($translation_languages as $lpair){
            if($lpair['attr']['from_language_name'] == $lang_server[$from_lang] && $lpair['attr']['to_language_name'] == $lang_server[$to_lang]){
                $lang_pair_id = $lpair['attr']['id']; 
            }    
        }
        
        $target = ICL_API_ENDPOINT . '/websites/' . $this->settings['site_id'] . '/website_translation_offers/'.$lang_pair_id.'?accesskey=' . $this->settings['access_key'] . '&compact=1';
    
    }
    
    $support_mode = $_GET['support'];
    
    if ($support_mode == '1') {
        $iclq = new ICanLocalizeQuery($this->settings['support_site_id'], $this->settings['support_access_key']);
    } else {
        $iclq = new ICanLocalizeQuery($this->settings['site_id'], $this->settings['access_key']);
    }
    $session_id = $iclq->get_current_session(true, $support_mode == '1');
    
    $admin_lang = $this->get_admin_language();
    
	
	if (isset($_GET['code'])) {
		$add = '&code=' . $_GET['code'];
	}
	
    if (strpos($target, '?') === false) {
        $target .= '?';
    } else {
        $target .= '&';
    }
    $target .= "session=" . $session_id . "&lc=" . $admin_lang . $add;
    

    $on_click = 'parent.dismiss_message(' . $_GET['message_id'] . ');';
    
    $can_delete = isset($_GET['message_id']) ? $wpdb->get_var("SELECT can_delete FROM {$wpdb->prefix}icl_reminders WHERE id='{$_GET['message_id']}'") == '1' : false;

    $image_path = ICL_PLUGIN_URL . '/res/img/web_logo_small.png';
    echo '<img src="' . $image_path . '"  style="margin: 0px 0px 0px; float: left; "><br clear="all" />';
    
?>


<?php if($can_delete): ?>
    <a id="icl_reminder_dismiss" href="#" onclick="<?php echo $on_click?>">Dismiss</a>
    <br />
    <br />
    <iframe src="<?php echo $target;?>" style="width:99%; height:80%">

<?php else: ?>
    
    <iframe src="<?php echo $target;?>" style="width:99%; height:90%">
    
<?php endif; ?>

