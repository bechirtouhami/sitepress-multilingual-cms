<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $sitepress_settings = $sitepress->get_settings();
?>

<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup SitePress', 'sitepress') ?></h2>    
    
    <h3><?php echo __('Navigation', 'sitepress') ?></h3>    
    
    <p><?php echo __('Out-of-the-box support for full CMS navigation in your WordPress site including drop down menus, breadcrumbs trail and sidebar navigation.', 'sitepress')?></p>
    
    <p>
        <label><input type="checkbox" id="icl_enable_nav" <?php if($sitepress_settings['modules']['cms-navigation']):?>checked="checked"<?php endif; ?> /> <?php echo __('Enable CMS navigation functionality', 'sitepress'); ?></label>
        <input id="icl_enable_nav_but" type="button" value="<?php echo __('Save', 'sitepress')?>" class="button" />
        <span class="icl_ajx_response" id="icl_enable_nav_ajxresp"></span>
    </p>
    
    <a href="#read-more"><?php echo __('Read more(+)', 'sitepress') ?></a>
    
    <textarea id="icl_nav_read_more" readonly="readonly"></textarea>
    
</div>