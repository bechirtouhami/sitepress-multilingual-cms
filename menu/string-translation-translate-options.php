<?php 
    $troptions = icl_st_scan_options_strings();
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32_adv"><br /></div>
    <h2><?php echo __('String translation', 'sitepress') ?></h2>    
    
    <?php include ICL_PLUGIN_PATH . '/menu/basic_advanced_switch.php' ?>

    <?php if(!empty($troptions)): ?>
    <div id="icl_st_option_writes">
    <?php foreach($troptions as $option_name=>$option_value): ?>
    <?php echo icl_st_render_option_writes($option_name, $option_value); ?>
    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div align="center"><?php _e('No options found', 'sitepress') ?></div>
    <?php endif; ?>
    
</div>