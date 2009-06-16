<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $active_languages = $sitepress->get_active_languages();            
    $languages = $sitepress->get_languages();            
    if(!$sitepress_settings['modules']['absolute-links']['enabled']){
        $total_posts_pages_processed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_alp_processed'");
    }
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>    
    
    <h3><?php echo __('About Sticky Links', 'sitepress') ?></h3>    
    
    <p><?php echo __('WPML can turn internal links to posts and pages into sticky links. What this means is that links to pages and posts will automatically update if their URL changes. There are many reasons why page URL changes:', 'sitepress'); ?></p>
    <ul style="list-style:disc;margin-left:20px;">
        <li><?php echo __('The slug changes.', 'sitepress'); ?></li>
        <li><?php echo __('The page parent changes.', 'sitepress'); ?></li>
        <li><?php echo __('Permlink structure changes.', 'sitepress'); ?></li>
    </ul>
    <p><?php echo __('If you select to enable sticky links, internal links to pages and posts will never break. When the URL changes, all links to it will automatically update.', 'sitepress'); ?></p>
    <p><?php echo __('When you edit a page (while sticky links are enabled) you will notice that links in that page change to the default WordPress links. This is a normal thing. Visitors will not see these &#8220;strange&#8221; links. Instead they will get links to the full URL.', 'sitepress'); ?></p>
    
    <span style="position:absolute;" id="icl_ajax_loader_alp"></span>
    <p>
        <label><input type="checkbox" name="icl_enable_absolute_links" <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?>checked="checked"<?php endif;?>/> <?php echo __('Enable sticky links', 'sitepress') ?></label>        
    </p>
    
    
    <div id="icl_alp_wrap">
    <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?>    
    <?php $iclAbsoluteLinks->management_page_content(); ?>    
    <?php else: ?>
    <?php 
        $total_posts_pages_processed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_alp_processed'");
        if($total_posts_pages_processed > 0){
            $fr = array('[a]','[/a]');
            $to = array('<a href="#revert-links">','</a>');
            echo str_replace($fr, $to, sprintf(__('Some links (%s) were converted to absolute. You can [a]return them to their original values[/a]', 'sitepress'),$total_posts_pages_processed));
        }
    ?>
    <?php endif; ?>
    </div>
    
    <?php do_action('icl_menu_footer'); ?>
    
</div>