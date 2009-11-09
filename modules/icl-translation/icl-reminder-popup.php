<?php

    // NOTE: this is also used for other popup links to ICanLocalize

    if (file_exists ('../../../../../wp-load.php'))
        include ('../../../../../wp-load.php');
    else
        include ('../../../../../wp-config.php');


    global $sitepress, $wpdb;
    
    $iclsettings = $sitepress->get_settings();
    $iclq = new ICanLocalizeQuery($iclsettings['site_id'], $iclsettings['access_key']);       

    $target = $_GET['target'];
    $session_id = $iclq->get_current_session(true);
    
    $admin_lang = $sitepress->get_admin_language();
    
    if (strpos($target, '?') === false) {
        $target .= '?';
    } else {
        $target .= '&';
    }
    $target .= "session=" . $session_id . "&lc=" . $admin_lang;
    

    $on_click = 'parent.dismiss_message(' . $_GET['message_id'] . ');';
    
    $can_delete = $wpdb->get_var("SELECT can_delete FROM {$wpdb->prefix}icl_reminders WHERE id={$_GET['message_id']}") == '1';

    $image_path = ICL_PLUGIN_URL . '/res/img/web_logo_small.png';
    echo '<img src="' . $image_path . '"  style="margin: 0px 0px 0px; float: left; "><br clear="all" />';
    
?>


<?php if($can_delete): ?>
    <a id="icl_reminder_dismiss" href="#" onclick="<?php echo $on_click?>">Dismiss</a>
    <br />
    <br />
<?php endif; ?>

<iframe src="<?php echo $target;?>" style="width:99%; height:90%">
