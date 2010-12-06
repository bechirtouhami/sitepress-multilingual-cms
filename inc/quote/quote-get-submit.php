<?php
/*
 * Thickbox form submit process
 */
global $sitepress, $sitepress_settings, $wpdb;

if (isset($data['submit-for-later'])) {
    $saved = $sitepress_settings['quote-get'];
    $saved['step'] = 3;
    $sitepress->save_settings(array('quote-get' => $saved));
    echo '<script type="text/javascript">jQuery(\'#TB_closeWindowButton\').trigger(\'click\');</script>';
} else if (isset($data['submit-produce'])) {
    $saved = $sitepress_settings['quote-get'];
    if (empty($saved['from'])
            || empty($saved['to'])
            || empty($saved['content'])
//            || empty($data['first_name'])
//            || empty($data['last_name'])
//            || !preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9][-a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,4}|museum|travel)$/i', $data['mail'])
    ) {
        die('data not valid');
    }
    $word_count = 0;
    $wc_description = array();
    foreach ($saved['content'] as $ID => $true) {
        $wc_description[] = $saved['description'][$ID]['num'] . ' '
                . $saved['description'][$ID]['title'] . ' with '
                . $saved['description'][$ID]['words'] . ' words';
        $word_count += intval($saved['description'][$ID]['words']);
    }
    $wc_description = implode(', ', $wc_description);

    if (!isset($sitepress_settings['site_id'])) {
        // create account
        $user = array();
        //    $user['fname'] = $_POST['first_name'];
        //    $user['lname'] = $_POST['last_name'];
        //    $user['email'] = $_POST['mail'];
        $user['create_account'] = 1;
        $user['anon'] = 1;
        $user['platform_kind'] = 2;
        $user['cms_kind'] = 1;
        $user['blogid'] = $wpdb->blogid ? $wpdb->blogid : 1;
        $user['url'] = get_option('home');
        $user['title'] = get_option('blogname');
        $user['description'] = $sitepress_settings['icl_site_description'];
        $user['is_verified'] = 1;
        $user['interview_translators'] = $sitepress_settings['interview_translators'];
        $user['project_kind'] = $sitepress_settings['website_kind'];
        $user['pickup_type'] = intval($sitepress_settings['translation_pickup_method']);
        $user['ignore_languages'] = 1;

        if (defined('ICL_AFFILIATE_ID') && defined('ICL_AFFILIATE_KEY')) {
            $user['affiliate_id'] = ICL_AFFILIATE_ID;
            $user['affiliate_key'] = ICL_AFFILIATE_KEY;
        }
        $notifications = 0;
        if ($sitepress_settings['icl_notify_complete']) {
            $notifications += 1;
        }
        if ($sitepress_settings['alert_delay']) {
            $notifications += 2;
        }
        $user['notifications'] = $notifications;

        // prepare language pairs

        $pay_per_use = $sitepress_settings['translator_choice'] == 1;

        $language_pairs = array($saved['from'] => $saved['to']);
        //    $language_pairs = $_POST['from'];
        $lang_pairs = array();
//        $incr = 1;
        if (isset($language_pairs)) {
            foreach ($language_pairs as $k => $v) {
                $english_fr = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                foreach ($v as $k => $v) {
                    $incr++;
                    $english_to = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                    $lang_pairs['from_language' . $incr] = ICL_Pro_Translation::server_languages_map($english_fr);
                    $lang_pairs['to_language' . $incr] = ICL_Pro_Translation::server_languages_map($english_to);
                    if ($pay_per_use) {
                        $lang_pairs['pay_per_use' . $incr] = 1;
                    }
                }
            }
        }
        require_once ICL_PLUGIN_PATH . '/lib/icl_api.php';
        $icl_query = new ICanLocalizeQuery();
        list($site_id, $access_key) = $icl_query->createAccount(array_merge($user, $lang_pairs));
        if (!$site_id) {
            if ($access_key) {
                $_POST['icl_form_errors'] = $access_key;
            } else {
                $_POST['icl_form_errors'] = __('An unknown error has occurred when communicating with the ICanLocalize server. Please try again.', 'sitepress');
                // We will force the next try to be http.
                update_option('_force_mp_post_http', 1);
            }
            die(__('An unknown error has occurred when communicating with the ICanLocalize server. Please try again.', 'sitepress'));
        } else {
            $iclsettings['site_id'] = $site_id;
            $iclsettings['access_key'] = $access_key;
            $iclsettings['icl_account_email'] = $user['email'];
            $sitepress->save_settings($iclsettings);
            $sitepress->get_icl_translator_status($iclsettings);
            $sitepress->save_settings($iclsettings);
        }
    } else {
        $site_id = $sitepress_settings['site_id'];
        $access_key = $sitepress_settings['access_key'];
    }

    $language_pairs = array($saved['from'] => $saved['to']);
    //    $language_pairs = $_POST['from'];
    $lang_pairs = array();
    $incr = 1;
    $query = '';
    if (isset($language_pairs)) {
        foreach ($language_pairs as $k => $v) {
            $english_from = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
            $query .= '&to_lang_num=' . count($v);
            $query .= '&from_language_iname=' . $english_from;
            foreach ($v as $k => $v) {
                $english_to = $wpdb->get_var("SELECT english_name FROM {$wpdb->prefix}icl_languages WHERE code='{$k}' ");
                $query .= '&to_language_name_' . $incr . '=' . ICL_Pro_Translation::server_languages_map($english_to);
                $incr++;
            }
        }
    }
    
    wp_redirect(ICL_API_ENDPOINT . '/websites/' . $site_id . '/quote?accesskey=' . $access_key . '&locale=' . $sitepress->get_default_language() . $query);
    exit;
}