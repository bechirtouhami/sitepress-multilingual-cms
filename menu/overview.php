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
        <h2><?php echo __('WPML Overview', 'sitepress') ?></h2>    
        
        
        <div id="dashboard-widgets" class="metabox-holder">
        
        <div class="postbox-container" style="width: 49%;">
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            
                <div id="dashboard_wpml_languages" class="postbox">
                    <div class="handlediv" title="Click to toggle">
                        <br/>
                    </div>
                    <h3 class="hndle">
                        <span><?php echo __('WPML at a glance', 'sitepress')?></span>
                    </h3>                    
                    <div class="inside">
                        <p class="sub"><?php printf(__('WPML helps you translate your blog to other languages. You are using <b>WPML %s</b>', 'sitepress'), ICL_SITEPRESS_VERSION)?></p>
                        <?php if(!$sitepress_settings['existing_content_language_verified']): ?>          
                        <p class="sub"><b><?php printf(__('You have to set the set up the language of the existing content of your blog.<br /> Click <a href="%s">here</a> to do that.', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/languages.php')?></b></p>              
                        <?php else: ?>
                        <p class="sub">
                            <?php echo __('Currently configured languages:', 'sitepress')?>
                            <?php echo join(', ', $alanguages_links)?>
                        </p>
                        <?php endif; ?>                                                
                        
                        <p class="sub">
                        <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/languages.php' ?>"><?php echo __('Configure languages', 'sitepress') ?></a>
                        </p>                        
                        
                        <p class="sub">
                            <b><?php echo __('Need help?', 'sitepress')?></b><br />
                            <?php printf(__('<a href="%s">Ask WPML on Twitter</a>, visit the <a href="%s">support forum</a> or view the <a href="%s">documentation</a>.', 'sitepress'), 'http://twitter.com/wpml','http://forum.wpml.org','http://wpml.org')?>
                        </p>
                        <?php remove_all_actions('icl_menu_footer'); ?>
                        
                    </div>
                </div>
                
                <div id="dashboard_wpml_navigation" class="postbox">
                    <div class="handlediv" title="Click to toggle">
                        <br/>
                    </div>
                    <h3 class="hndle">
                        <span><?php echo __('Navigation', 'sitepress')?></span>
                    </h3>                    
                    <div class="inside">
                        <p class="sub"><?php echo __('WPML provides advanced menus and navigation to go with your WordPress website, including drop-down menus, breadcrumbs and sidebar navigation.', 'sitepress')?></p>
                        <p class="sub">
                        <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/navigation.php' ?>"><?php echo __('Configure navigation', 'sitepress') ?></a>
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
                    
                        <p class="sub"><?php echo __('With Sticky Links, WPML can automatically ensure that all links on posts and pages are up-to-date, should their URL change.', 'sitepress'); ?></p>
                    
                        <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?>
                        <p class="sub"><?php echo __('Sticky links are enabled.') ?></p>
                        <?php else: ?>
                        <p class="sub"><b><?php echo __('Sticky links are disabled.') ?></b></p>
                        <?php endif; ?>
                        
                        <p class="sub">
                        <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/absolute-links.php' ?>"><?php echo __('Configure sticky links', 'sitepress') ?></a>
                        </p>
                    </div>
                </div>
                
            </div>
        </div>

        <div class="postbox-container" style="width: 49%;">
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            
                <?php if(2 <= count($sitepress->get_active_languages())) :?>
                <div id="dashboard_wpml_string_translation" class="postbox">
                    <div class="handlediv" title="Click to toggle">
                        <br/>
                    </div>
                    <h3 class="hndle">
                        <span><?php echo __('String translation', 'sitepress')?></span>
                    </h3>                    
                    <div class="inside">
                        <p class="sub"><?php echo __('String translation allows you to enter translation for texts such as the site\'s title, tagline, widgets and other text not contained in posts and pages.', 'sitepress')?></p>
                        <?php if($strings_need_update==1): ?>          
                        <p class="sub"><b><?php printf(__('There is <a href="%s"><b>1</b> string</a> that needs to be updated or translated. ', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php&status=0')?></b></p>                                      
                        <?php elseif($strings_need_update): ?>          
                        <p class="sub"><b><?php printf(__('There are <a href="%s"><b>%s</b> strings</a> that need to be updated or translated. ', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php&status=0' ,$strings_need_update)?></b></p>              
                        <?php else: ?>
                        <p class="sub">
                            <?php echo __('All strings are up to date.', 'sitepress'); ?>
                        </p>
                        <?php endif; ?>
                        <p class="sub">
                        <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php' ?>"><?php echo __('Translate strings', 'sitepress') ?></a>
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
                        <?php if($sitepress_settings['enable_icl_translations']): ?>
                            <p class="sub"><?php echo __('Content translation is enabled.', 'sitepress');?></p>
                            <?php if(!$sitepress->icl_account_configured()): ?>
                            <p class="sub"><b><?php echo __('Content translation is not available yet because your ICanLocalize account is not set up.', 'sitepress')?></b></p>
                            <?php else: ?>
                            <p class="sub"><?php echo __('Your account at ICanLocalize is set up.', 'sitepress'); ?></p>
                            <?php endif; ?>                            
                            
                            <?php if(!isset($sitepress_settings['language_pairs']) || empty($sitepress_settings['language_pairs'])):?>
                            <p class="sub"><?php echo __('No translation pairs are configured', 'sitepress'); ?></p>              
                            <?php else:?>
                            <p class="sub"><?php echo __('Translation pairs:', 'sitepress'); ?> <?php echo join(', ', $lpairs)?></p>              
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <p class="sub"><?php echo __('Content Translation allows you to have all the site\'s contents professionally translated.', 'sitepress'); ?></p>
                            <p class="sub"><?php printf(__('When enabled, you can use the <a href="%s">Translation Dashboard</a> to send posts and pages for translation. The entire process is completely effortless. The plugin will send the documents that need translation and then create the translated contents, ready to be published.', 'sitepress'), 'tools.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php');?></p>                        
                            <p class="sub"><b><?php echo __('Content translation is disabled' , 'sitepress')?></b></p>
                        <?php endif;?>
                        
                        <p class="sub">
                        <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/content-translation.php' ?>"><?php echo __('Configure content translation', 'sitepress') ?></a>
                        </p>                                
                        
                        <?php if(!$sitepress->icl_account_configured()): ?>
                        <p class="sub"><a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php' ?>"><?php echo __('Cost calculation for translation', 'sitepress'); ?></a></p> 
                        <?php else: ?>
                        <p class="sub"><a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php' ?>"><?php echo __('Send content to translation', 'sitepress'); ?></a></p> 
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                                
                            
            </div>
        </div>
        
        </div>
        <div class="clear"></div>    
    </div>    
    <?php do_action('icl_menu_footer'); ?>
    
</div>