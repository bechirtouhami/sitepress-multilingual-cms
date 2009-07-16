<?php 
if($sitepress_settings['existing_content_language_verified']){
    $active_languages = $sitepress->get_active_languages();    
    $default_language = $sitepress->get_default_language();
    foreach($active_languages as $lang){
        if($default_language != $lang['code']){$default = '';}else{$default = ' ('.__('default','sitepress').')';}
        $alanguages_links[] = $lang['display_name'] . $default;
    }
    if(isset($sitepress_settings['language_pairs']) && !empty($sitepress_settings['language_pairs'])){
        foreach($active_languages as $lang){
            foreach($active_languages as $langto){ 
                if($lang['code']==$langto['code']) continue; 
                if(isset($sitepress_settings['language_pairs'][$lang['code']][$langto['code']])){
                    $lpairs[] = sprintf('%s to %s', $lang['display_name'], $langto['display_name']);
                }
            }
        }
    }    
    
    if(2 <= count($sitepress->get_active_languages())){
        $strings_need_update = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}icl_strings WHERE status <> 1");            
    }
}
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    <div id="dashboard-widgets-wrap">
    
        <div id="icon-options-general" class="icon32"><br /></div>
        <h2><?php echo __('Overview', 'sitepress') ?></h2>    
        
        
        <div id="dashboard-widgets" class="metabox-holder">
        
        <div class="postbox-container" style="width: 49%;">
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            
                <div id="dashboard_wpml_languages" class="postbox">
                    <div class="handlediv" title="Click to toggle">
                        <br/>
                    </div>
                    <h3 class="hndle">
                        <span><?php echo __('Languages', 'sitepress')?></span>
                    </h3>                    
                    <div class="inside">
                        <?php if(!$sitepress_settings['existing_content_language_verified']): ?>          
                        <p class="sub"><span class="icl_error_text"><?php printf(__('You have to set the set up the language of the existing content of your blog.<br /> Click <a href="%s">here</a> to do that.', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/languages.php')?></span></p>              
                        <?php else: ?>
                        <p class="sub">
                            <?php echo __('Currently configured languages:', 'sitepress')?>
                            <?php echo join(', ', $alanguages_links)?>
                        </p>
                        <?php endif; ?>
                        <p class="sub">
                        <a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/languages.php' ?>"><?php echo __('Configure languages', 'sitepress') ?></a>
                        </p>                        
                    </div>
                </div>
                
                <?php if(2 <= count($sitepress->get_active_languages())) :?>
                <div id="dashboard_wpml_string_translation" class="postbox">
                    <div class="handlediv" title="Click to toggle">
                        <br/>
                    </div>
                    <h3 class="hndle">
                        <span><?php echo __('String translation', 'sitepress')?></span>
                    </h3>                    
                    <div class="inside">
                        <?php if($strings_need_update): ?>          
                        <p class="sub"><span class="icl_error_text"><?php printf(__('There are <b><a href="%s">%s</a></b> strings that need to be updated or translated. ', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php&status=0' ,$strings_need_update)?></span></p>              
                        <?php else: ?>
                        <p class="sub">
                            <?php echo __('All strings are up to date.', 'sitepress'); ?>
                        </p>
                        <?php endif; ?>
                        <p class="sub">
                        <a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php' ?>"><?php echo __('Configure string translation', 'sitepress') ?></a>
                        </p>                        
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($sitepress_settings['existing_content_language_verified']): ?>                
                <div id="dashboard_wpml_content_translation" class="postbox">
                    <div class="handlediv" title="Click to toggle">
                        <br/>
                    </div>
                    <h3 class="hndle">
                        <span><?php echo __('Content translation', 'sitepress')?></span>
                    </h3>                    
                    <div class="inside">
                        <?php if(!$sitepress->icl_account_configured()): ?>
                        <p class="sub"><span class="icl_error_text"><?php echo __('You haven\'t configured your account at ICanLocalize to enable professional translations for your blog', 'sitepress')?></span></p>
                        <?php else: ?>
                        <p class="sub"><?php echo __('Your account at ICanLocalize is set up', 'sitepress'); ?></p>
                        <?php endif; ?>
                        <?php if(!isset($sitepress_settings['language_pairs']) || empty($sitepress_settings['language_pairs'])):?>
                        <p class="sub"><?php echo __('No translation pairs are congigured', 'sitepress'); ?></p>              
                        <?php else:?>
                        <p class="sub"><?php echo __('Translation pairs:', 'sitepress'); ?> <?php echo join(', ', $lpairs)?></p>              
                        <?php endif; ?>
                        <p class="sub">
                        <a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/content-translation.php' ?>"><?php echo __('Configure content translation', 'sitepress') ?></a>
                        </p>        
                        
                        <?php if($sitepress->icl_account_configured()): ?>
                        <p class="sub"><a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php' ?>"><?php echo __('Send documents to translation', 'sitepress'); ?></a></p>                
                        <?php else: ?>
                        <p class="sub"><a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php' ?>"><?php echo __('Cost calculation for translation'); ?></a></p> 
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>

        <div class="postbox-container" style="width: 49%;">
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            
                <div id="dashboard_wpml_navigation" class="postbox">
                    <div class="handlediv" title="Click to toggle">
                        <br/>
                    </div>
                    <h3 class="hndle">
                        <span><?php echo __('Navigation', 'sitepress')?></span>
                    </h3>                    
                    <div class="inside">
                        <p class="sub"><?php echo __('Out-of-the-box support for full CMS navigation in your WordPress site including drop down menus, breadcrumbs trail and sidebar navigation.', 'sitepress')?></p>
                        <p class="sub">
                        <a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/navigation.php' ?>"><?php echo __('Configure navigation', 'sitepress') ?></a>
                        </p>
                    </div>
                </div>
                
                <div id="dashboard_wpml_stickylinks" class="postbox">
                    <div class="handlediv" title="Click to toggle">
                        <br/>
                    </div>
                    <h3 class="hndle">
                        <span><?php echo __('Sticky links', 'sitepress')?></span>
                    </h3>                    
                    <div class="inside">
                    
                        <p class="sub"><?php echo __('WPML can turn internal links to posts and pages into sticky links. What this means is that links to pages and posts will automatically update if their URL changes. There are many reasons why page URL changes:', 'sitepress'); ?></p>
                        <ul style="list-style:disc;margin-left:20px;">
                            <li><?php echo __('The slug changes.', 'sitepress'); ?></li>
                            <li><?php echo __('The page parent changes.', 'sitepress'); ?></li>
                            <li><?php echo __('Permlink structure changes.', 'sitepress'); ?></li>
                        </ul>
                    
                        <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?>
                        <p class="sub"><?php echo __('Sticky links is enabled.') ?></p>
                        <?php else: ?>
                        <p class="sub"><span class="icl_error_text"><?php echo __('Sticky links is not enabled.') ?></span></p>
                        <?php endif; ?>
                        
                        <p class="sub">
                        <a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/absolute-links.php' ?>"><?php echo __('Configure sicky links', 'sitepress') ?></a>
                        </p>
                    </div>
                </div>
                                
                            
            </div>
        </div>
        
        </div>
        <div class="clear"></div>    
    </div>    
    <?php do_action('icl_menu_footer'); ?>
    
</div>