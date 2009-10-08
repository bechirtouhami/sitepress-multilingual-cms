<?php 
$upgrade_lines =  array(
    '1.3.1' => __('translation controls on posts and pages lists', 'sitepress'),
    '1.3.3' => __('huge speed improvements and the ability to prevent loading WPML\'s CSS and JS files', 'sitepress')
);

$short_v = implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3));
if(!isset($upgrade_lines[$short_v])) return;

?>
<br clear="all" />
<div id="message" class="updated message fade" style="clear:both;margin-top:5px;">
    <p><?php printf(__('New in WPML %s: <b>%s</b>', 'sitepress'), $short_v, $upgrade_lines[$short_v]); ?></p>
    <p>
        <a href="http://wpml.org/home/wpml-news/"><?php _e('Learn more', 'sitepress')?></a>&nbsp;|&nbsp;
        <a title="<?php _e('Stop showing this message', 'sitepress') ?>" id="icl_dismiss_upgrade_notice" href="#"><?php _e('Dismiss', 'sitepress') ?></a>
    </p>
</div>
