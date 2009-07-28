<?php
if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
    return;
}
$active_languages = $sitepress->get_active_languages();              
//icl_st_scan_theme_files();
?>

<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Theme localization', 'sitepress') ?></h2>    

    <h3><?php echo __('How to localize theme?','sitepress'); ?></h3>
    <form id="icl_theme_localization_type" method="post" action="">
    <input type="hidden" name="icl_ajx_action" value="icl_save_theme_localization_type" />
    <ul>
        <li><label><input type="radio" name="icl_theme_localization_type" value="0" <?php if($sitepress_settings['theme_localization_type']==0):?>checked="checked"<?php endif; ?> /> <?php echo __('No localization', 'sitepress') ?></label></li>
        <li><label><input type="radio" name="icl_theme_localization_type" value="1" <?php if($sitepress_settings['theme_localization_type']==1):?>checked="checked"<?php endif; ?> /> <?php echo __('Translate the theme by WPML', 'sitepress') ?></label></li>
        <li><label><input type="radio" name="icl_theme_localization_type" value="2" <?php if($sitepress_settings['theme_localization_type']==2):?>checked="checked"<?php endif; ?> /> <?php echo __('Using a .mo file in the theme directory', 'sitepress') ?></label></li>
    </ul>
    <p>
        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />        
    </p>
    </form>
    
    <?php if($sitepress_settings['theme_localization_type'] > 0):?>
    <div id="icl_tl">
    <h3><?php echo __('Settings', 'sitepress') ?></h3>
    <form id="icl_theme_localization" name="icl_lang_more_options" method="post" action="">
    <input type="hidden" name="icl_post_action" value="save_theme_localization" />    
    <div id="icl_theme_localization_wrap"><span id="icl_theme_localization_subwrap">    
    <table id="icl_theme_localization_table" class="widefat" cellspacing="0">
    <thead>
    <tr>
    <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Code', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Locale file name', 'sitepress') ?></th>        
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), LANGDIR) ?></th>        
    <?php if($sitepress_settings['theme_localization_type']==2):?>
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), '/wp-contents/themes/' . get_option('template')) ?></th>        
    <?php endif; ?>
    </tr>        
    </thead>        
    <tfoot>
    <tr>
    <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Code', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Locale file name', 'sitepress') ?></th>        
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), LANGDIR) ?></th>        
    <?php if($sitepress_settings['theme_localization_type']==2):?>
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), '/wp-contents/themes/' . get_option('template')) ?></th>        
    <?php endif; ?>
    </tr>        
    </tfoot>
    <tbody>
    <?php foreach($active_languages as $lang): ?>
    <tr>
    <td scope="col"><?php echo $lang['display_name'] ?></td>
    <td scope="col"><?php echo $lang['code'] ?></td>
    <td scope="col">
        <?php if($lang['code']=='en'): ?>
        <?php echo __('default', 'sitepress'); ?>
        <?php else: ?>
        <input type="text" size="10" name="locale_file_name_<?php echo $lang['code']?>" value="<?php echo $locales[$lang['code']]?>" />.mo
        <?php endif; ?>        
    </td> 
    <td>
        <?php if($lang['code']=='en'): echo '&nbsp;'; else: ?> 
            <?php if(is_readable(ABSPATH . LANGDIR . '/' . $locales[$lang['code']] . '.mo')): ?>
            <span class="icl_valid_text"><?php echo __('File exists.', 'sitepress') ?></span>                
            <?php else: ?>
            <span class="icl_error_text"><?php echo __('File not found!', 'sitepress') ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </td>
    <?php if($sitepress_settings['theme_localization_type']==2):?>       
    <td>
        <?php if($lang['code']=='en'): echo '&nbsp;'; else: ?> 
            <?php if(is_readable(TEMPLATEPATH . '/' . $locales[$lang['code']] . '.mo')): ?>
            <span class="icl_valid_text"><?php echo __('File exists.', 'sitepress') ?></span>                
            <?php else: ?>
            <span class="icl_error_text"><?php echo __('File not found!', 'sitepress') ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </td>              
    <?php endif; ?> 
    </tr>
    <?php endforeach; ?>                                                          
    </tbody>        
    </table>
    <?php if($sitepress_settings['theme_localization_type']==2):?>       
    <p>
        <?php echo __('Enter the theme\'s textdomain value:', 'sitepress')?>
        <input type="text" name="icl_domain_name" value="<?php echo $sitepress_settings['gettext_theme_domain_name'] ?>" />
        <?php if(!$sitepress_settings['gettext_theme_domain_name']): ?>
        <span class="icl_error_text"><?php echo __('Theme localization is not enabled because you didn\'t enter a text-domain.', 'sitepress'); ?></span>
        <?php endif ?>
    </p>
    <?php endif; ?>
    </div>
    <p>
        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
        <span class="icl_ajx_response" id="icl_ajx_response_fn"></span>
    </p>
    <span>
    </form>
    <br /><br />
    </div> 
    <?php endif; ?>
    
    <?php if($sitepress_settings['theme_localization_type'] == 1):?>
    <a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH) ?>/menu/string-translation.php&context=theme&status=0"><?php echo __('Translate themes texts ', 'sitepress')?></a>
    <?php endif; ?>
           
</div>
