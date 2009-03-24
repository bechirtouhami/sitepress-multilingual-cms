<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $active_languages = $sitepress->get_active_languages();            
    $languages = $sitepress->get_languages();            
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup SitePress', 'sitepress') ?></h2>    
    
    <h3><?php echo __('Absolute links', 'sitepress') ?></h3>    
    
    <?php $iclAbsoluteLinks->management_page_content(); ?>
</div>