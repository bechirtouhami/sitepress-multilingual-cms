<?php
global $wpdb, $current_user;
$active_languages = $this->get_active_languages();
foreach ($active_languages as $lang) {
    if ($default_language != $lang['code']) {
        $default = '';
    } else {
        $default = ' (' . __('default', 'sitepress') . ')';
    }
    $alanguages_links[] = $lang['display_name'] . $default;
}
require_once(ICL_PLUGIN_PATH . '/inc/support.php');
$SitePress_Support = new SitePress_Support;
$pss_status = $SitePress_Support->get_subscription();
if (!isset($pss_status['valid'])) {
    $pss_string_status = __('None', 'sitepress');
} else {
    if ($pss_status['valid']) {
        $pss_string_status = '<span class="icl_valid_text">' . sprintf(__('Valid! (amount: $%d - until %s)', 'sitepress'), $pss_status['amount'], date('d/m/Y', $pss_status['expires'])) . '</span>';
    } else {
        $pss_string_status = '<span class="icl_error_text">' . sprintf(__('Expired! - since %s', 'sitepress'), date('d/m/Y', $pss_status['expires'])) . '</span>';
    }
}

$docs_sent = 0;
$docs_completed = 0;
$docs_waiting = 0;
$docs_statuses = $wpdb->get_results("SELECT status FROM {$wpdb->prefix}icl_translation_status");
foreach ($docs_statuses as $doc_status) {
    $docs_sent += 1;
    if ($doc_status->status == ICL_TM_COMPLETE) {
        $docs_completed += 1;
    } elseif ($doc_status->status == ICL_TM_WAITING_FOR_TRANSLATOR
            || $doc_status->status == ICL_TM_IN_PROGRESS) {
        $docs_waiting += 1;
    }
}

?>
<p><?php echo sprintf(__('WPML version: %s'), '<strong>' . ICL_SITEPRESS_VERSION . '</strong>'); ?></p>
<?php if (!$this->settings['setup_complete']): ?>
    <p class="updated" style="padding:4px"><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php"><strong><?php _e('Finish the WPML setup.', 'sitepress') ?></strong></a></p>
<?php else: ?>
        <p><?php _e('Currently configured languages:', 'sitepress') ?> <b><?php echo join(', ', (array) $alanguages_links) ?></b> (<a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php"><?php _e('edit', 'sitepress'); ?></a>)</p>
        <p><?php if ($docs_sent)
            printf(__('%d documents sent to translation.<br />%d are complete, %d waiting for translation.', 'sitepress'), $docs_sent, $docs_completed, $docs_waiting); ?></p>
    <p><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER; ?>/menu/translation-management.php" class="button secondary"><strong><?php _e('Send documents to translation', 'sitepress'); ?></strong></a></p>

<?php if (count($active_languages) > 1) { ?>
    <div><a href="#" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px;"><?php _e('Content translation', 'sitepress') ?></a></div>
    <div class="wrapper" style="display:none;"><p>
        <?php
        $your_translators = TranslationManagement::get_blog_translators();
        if (!empty($your_translators)) {
            echo '<strong>' . __('Your translators', 'sitepress') . '</strong><br />';
            foreach ($your_translators as $your_translator) {

                if ($current_user->ID == $your_translator->ID) {
                    $edit_link = 'profile.php';
                } else {
                    $edit_link = esc_url(add_query_arg('wp_http_referer', urlencode(esc_url(stripslashes($_SERVER['REQUEST_URI']))), "user-edit.php?user_id=$your_translator->ID"));
                }
                echo '<a href="' . $edit_link . '"><strong>' . $your_translator->display_name . '</strong></a> - ';
                foreach ($your_translator->language_pairs as $from => $lp) {
                    $tos = array();
                    foreach ($lp as $to => $null) {
                        $tos[] = $active_languages[$to]['display_name'];
                    }
                    printf(__('%s to %s', 'sitepress'), $active_languages[$from]['display_name'], join(', ', $tos));
                }
                echo '<br />';
            }
        }

        ?>
        <br />
        <a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER; ?>/menu/translation-management.php&amp;sm=translators&amp;service=icanlocalize"><strong><?php _e('Add professional translators', 'sitepress'); ?></strong></a><br />
        <a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER; ?>/menu/translation-management.php&amp;sm=translators&amp;service=local"><strong><?php _e('Add your own translators', 'sitepress'); ?></strong></a><br />
        <a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER; ?>/menu/translation-management.php"><strong><?php _e('Translate contents', 'sitepress'); ?></strong></a><br />
    </p></div>
<?php } ?>

<div><a href="#" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px;"><?php _e('Theme and plugins localization', 'sitepress') ?></a></div>
<div class="wrapper" style="display:none;"><p>
        <?php
        echo __('Current configuration', 'sitepress');
        echo '<br /><strong>';
        switch ($sitepress_settings['theme_localization_type']) {
            case '1': echo __('Translate the theme by WPML', 'sitepress');
                break;
            case '2': echo __('Using a .mo file in the theme directory', 'sitepress');
                break;
            default: echo __('No localization', 'sitepress');
        }
        echo '</strong>';

        ?>
    </p>
    <p><a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/theme-localization.php' ?>"><?php echo __('Manage theme and plugins localization', 'sitepress'); ?></a></p>
</div>

<div><a href="#" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px;"><?php _e('String translation', 'sitepress') ?></a></div>
<div class="wrapper" style="display:none;"><p><?php echo __('String translation allows you to enter translation for texts such as the site\'s title, tagline, widgets and other text not contained in posts and pages.', 'sitepress') ?></p>
    <?php
        $strings_need_update = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}icl_strings WHERE status <> 1");
        if ($strings_need_update == 1):

    ?>
            <p><b><?php printf(__('There is <a href="%s"><b>1</b> string</a> that needs to be updated or translated. ', 'sitepress'), 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/string-translation.php&amp;status=0') ?></b></p>
    <?php elseif ($strings_need_update): ?>
                <p><b><?php printf(__('There are <a href="%s"><b>%s</b> strings</a> that need to be updated or translated. ', 'sitepress'), 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/string-translation.php&amp;status=0', $strings_need_update) ?></b></p>
    <?php else: ?>
                    <p>
        <?php echo __('All strings are up to date.', 'sitepress'); ?>
                </p>
    <?php endif; ?>
                    <p>
                        <a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/string-translation.php' ?>"><?php echo __('Translate strings', 'sitepress') ?></a>
                    </p>




    <?php endif; ?>
                </div>

                <div><a href="#" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px;"><?php _e('Navigation', 'sitepress') ?></a></div>
                <div class="wrapper" style="display:none;"><p>
        <?php echo __('WPML provides advanced menus and navigation to go with your WordPress website, including drop-down menus, breadcrumbs and sidebar navigation.', 'sitepress') ?>
                </p>
    <?php if (!$sitepress_settings['modules']['cms-navigation']['enabled']): ?>
                        <p><b><?php echo __('CMS Navigation is disabled.', 'sitepress') ?></b></p>
                        <p><a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/overview.php&amp;enable-cms-navigation=1' ?>"><?php echo __('Enable CMS navigation', 'sitepress') ?></a></p>
    <?php else: ?>
                            <p><b><?php echo __('CMS Navigation is enabled.', 'sitepress') ?></b></p>
                            <p>
                                <a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/navigation.php' ?>"><?php echo __('Configure navigation', 'sitepress') ?></a>
                                <a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/overview.php&amp;enable-cms-navigation=0' ?>"><?php echo __('Disable CMS navigation', 'sitepress') ?></a>
                            </p>
    <?php endif; ?>
                        </div>

                        <div><a href="#" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px;"><?php _e('Sticky links', 'sitepress') ?></a></div>

                        <div class="wrapper" style="display:none;"><p><?php echo __('With Sticky Links, WPML can automatically ensure that all links on posts and pages are up-to-date, should their URL change.', 'sitepress'); ?></p>

    <?php if ($sitepress_settings['modules']['absolute-links']['enabled']): ?>
                                <p><b><?php echo __('Sticky links are enabled.', 'sitepress') ?></b></p>
                                <p>
                                    <a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/absolute-links.php' ?>"><?php echo __('Configure sticky links', 'sitepress') ?></a>
                                    <a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/overview.php&amp;icl_enable_alp=0' ?>"><?php echo __('Disable sticky links', 'sitepress') ?></a>
                                </p>

    <?php else: ?>
                                    <p><b><?php echo __('Sticky links are disabled.', 'sitepress') ?></b></p>
                                    <p><a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/overview.php&amp;icl_enable_alp=1' ?>"><?php echo __('Enable sticky links', 'sitepress') ?></a></p>
    <?php endif; ?>
                                </div>

                                <div><a href="#" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px;"><?php _e('Support Subscription', 'sitepress'); ?></a></div>
                                <div class="wrapper" style="display:none;"><p><?php printf(__('Support Subscription - %s', 'sitepress'), $pss_string_status); ?>
        <?php if (!$pss_status['valid']): ?>(<a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/support.php"><?php _e('purchase', 'sitepress'); ?></a>)<?php endif; ?></p>
        <?php do_action('icl_dashboard_widget_content'); ?>
</div>