<?php
if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
    return;
}
$active_languages = $sitepress->get_active_languages();              
$locales = $sitepress->get_locale_file_names();
$theme_localization_stats = get_theme_localization_stats();
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
        <?php if($theme_localization_stats): ?>
        <a name="icl_theme_localization_status"></a>
        <h3><?php echo __('Strings in the theme', 'sitepress'); ?></h3>
        <table class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col"><?php echo __('Domain', 'sitepress') ?></th>
                    <th scope="col"><?php echo __('Translation status', 'sitepress') ?></th>
                    <th scope="col" style="text-align:right"><?php echo __('Count', 'sitepress') ?></th>
                    <th scope="col">&nbsp;</th>
                </tr>
            </thead>  
            <tbody>
                <?php foreach($sitepress_settings['st']['theme_localization_domains'] as $tl_domain): ?>
                <tr scope="col">
                    <td rowspan="3"><?php echo $tl_domain ? $tl_domain : '<i>' . __('no domain','sitepress') . '</i>'; ?></td>
                    <td><?php echo __('Fully translated', 'sitepress') ?></td>
                    <td align="right"><?php echo $_tmpcomp = $theme_localization_stats[$tl_domain ? 'theme ' . $tl_domain : 'theme']['complete'] ?></td>
                    <td rowspan="3" align="right" style="padding-top:10px;">
                        <a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH) ?>/menu/string-translation.php&context=<?php echo $tl_domain ? 'theme ' . $tl_domain : 'theme' ?>" class="button-secondary"><?php echo __("View all the theme's texts",'sitepress')?></a>
                        <a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH) ?>/menu/string-translation.php&context=<?php echo $tl_domain ? 'theme ' . $tl_domain : 'theme' ?>&status=0" class="button-primary"><?php echo __("View strings that need translation",'sitepress')?></a>
                    </td>
                </tr>
                <tr scope="col">
                    <td><?php echo __('Not translated or needs update', 'sitepress') ?></td>
                    <td align="right"><?php echo $_tmpinco = $theme_localization_stats[$tl_domain ? 'theme ' . $tl_domain : 'theme']['incomplete'] ?></td>
                </tr>
                <tr scope="col" style="background-color:#f9f9f9;">
                    <td><strong><?php echo __('Total', 'sitepress') ?></strong></td>
                    <td align="right"><strong><?php echo $_tmpcomp + $_tmpinco; if(1 < count($sitepress_settings['st']['theme_localization_domains'])) { if(!isset($_tmpgt)) $_tmpgt = 0; $_tmpgt += $_tmpcomp + $_tmpinco; } ?></strong></td>
                </tr>            
                <?php endforeach  ?>
            </tbody>
            <?php if(1 < count($sitepress_settings['st']['theme_localization_domains'])): ?>
            <tfoot>
                <tr>                
                    <th scope="col"><?php echo __('Total', 'sitepress') ?></th>
                    <th scope="col">&nbsp;</th>
                    <th scope="col" style="text-align:right"><?php echo $_tmpgt ?></th>
                    <th scope="col">&nbsp;</th>
                </tr>
            </tfoot>                              
            <?php endif; ?>
        </table>    
        <br />
        
        <?php endif; ?>
        
    <p>
    <input id="icl_tl_rescan" type="button" class="button-primary" value="<?php echo __("Scan the theme for strings",'sitepress')?>" />
    <img class="icl_ajx_loader" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="display:none;" alt="" />
    </p>
    <?php endif; ?>
               
</div>
