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
remove_all_actions('icl_menu_footer');
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    
        <div id="icon-options-general" class="icon32"><br /></div>
        <h2><?php echo __('WPML Overview', 'sitepress') ?></h2>    
        
        <h3><?php _e('Multilingual', 'sitepress') ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th width="15%"><?php _e('Section', 'sitepress') ?></th>
                    <th><?php _e('Description', 'sitepress') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('WPML at a glance', 'sitepress')?></td>
                    <td>
                        <p><?php printf(__('WPML helps you translate your blog to other languages. You are using <b>WPML %s</b>', 'sitepress'), ICL_SITEPRESS_VERSION)?></p>
                        <?php if(!$sitepress_settings['existing_content_language_verified']): ?>          
                        <p><b><?php printf(__('You have to set the set up the language of the existing content of your blog.<br /> Click <a href="%s">here</a> to do that.', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/languages.php')?></b></p>              
                        <?php else: ?>
                        <p>
                            <?php _e('Currently configured languages:', 'sitepress')?>
                            <?php echo join(', ', (array)$alanguages_links)?>
                        </p>
                        <?php endif; ?>
                        <p>
                            <b><?php echo __('Need help?', 'sitepress')?></b><br />
                            <?php printf(__('<a href="%s">Ask WPML on Twitter</a>, visit the <a href="%s">support forum</a> or view the <a href="%s">documentation</a>.', 'sitepress'), 'http://twitter.com/wpml','http://forum.wpml.org','http://wpml.org')?>
                        </p>                        
                    </td>
                </tr>  
                <?php if(2 <= count($sitepress->get_active_languages())) :?>                      
                <tr>
                    <td><?php _e('String translation', 'sitepress')?></td>
                    <td>
                        <p><?php echo __('String translation allows you to enter translation for texts such as the site\'s title, tagline, widgets and other text not contained in posts and pages.', 'sitepress')?></p>
                        <?php if($strings_need_update==1): ?>          
                        <p><b><?php printf(__('There is <a href="%s"><b>1</b> string</a> that needs to be updated or translated. ', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php&amp;status=0')?></b></p>                                      
                        <?php elseif($strings_need_update): ?>          
                        <p><b><?php printf(__('There are <a href="%s"><b>%s</b> strings</a> that need to be updated or translated. ', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php&amp;status=0' ,$strings_need_update)?></b></p>              
                        <?php else: ?>
                        <p>
                            <?php echo __('All strings are up to date.', 'sitepress'); ?>
                        </p>
                        <?php endif; ?>
                        <p>
                        <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php' ?>"><?php echo __('Translate strings', 'sitepress') ?></a>
                        </p>                                            
                    </td>
                </tr>
                <?php endif; ?>            
                <?php if($sitepress_settings['existing_content_language_verified']): ?>                
                <tr>
                    <td><?php _e('Content translation', 'sitepress')?></td>
                    <td>
                        <?php if($sitepress_settings['enable_icl_translations']): ?>
                            <p><?php echo __('Content translation is enabled.', 'sitepress');?></p>
                            <?php if(!$sitepress->icl_account_configured()): ?>
                            <p><b><?php echo __('Content translation is not available yet because your ICanLocalize account is not set up.', 'sitepress')?></b></p>
                            <?php else: ?>
                            <p><?php echo __('Your account at ICanLocalize is set up.', 'sitepress'); ?></p>
                            <?php endif; ?>                            
                            
                            <?php if(!isset($sitepress_settings['language_pairs']) || empty($sitepress_settings['language_pairs'])):?>
                            <p><?php echo __('No translation pairs are configured', 'sitepress'); ?></p>              
                            <?php else:?>
                            <p><?php echo __('Translation pairs:', 'sitepress'); ?> <?php echo join(', ', $lpairs)?></p>              
                            <?php endif; ?>                            
                        <?php else: ?>
                            <p><?php echo __('Content Translation allows you to have all the site\'s contents professionally translated.', 'sitepress'); ?></p>
                            <p><?php printf(__('When enabled, you can use the <a href="%s">Translation Dashboard</a> to send posts and pages for translation. The entire process is completely effortless. The plugin will send the documents that need translation and then create the translated contents, ready to be published.', 'sitepress'), 'tools.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php');?></p>                        
                            <p><b><?php echo __('Content translation is disabled' , 'sitepress')?></b></p>
                        <?php endif;?>                        
                        <p>
                            <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/content-translation.php' ?>"><?php echo __('Configure content translation', 'sitepress') ?></a>
                        </p>                                
                        <?php if(!$sitepress->icl_account_configured()): ?>
                        <p><a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php' ?>"><?php echo __('Cost calculation for translation', 'sitepress'); ?></a></p> 
                        <?php else: ?>
                        <p><a href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php' ?>"><?php echo __('Send content to translation', 'sitepress'); ?></a></p> 
                        <?php endif; ?>                    
                    </td>
                </tr>  
                <?php endif; ?>                      
                <?php if($sitepress_settings['existing_content_language_verified']): ?>                
                <tr>
                    <td><?php _e('Theme localization', 'sitepress')?></td>
                    <td>
                        <p>
                            <?php 
                            echo __('Current configuration', 'sitepress');
                            echo '<br /><strong>';
                            switch($sitepress_settings['theme_localization_type']){
                                case '1': echo __('Translate the theme by WPML', 'sitepress'); break;
                                case '2': echo __('Using a .mo file in the theme directory', 'sitepress'); break;
                                default: echo __('No localization', 'sitepress'); 
                            }
                            echo '</strong>';
                            ?>
                        </p>                                                                              
                        <p><a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/theme-localization.php' ?>"><?php echo __('Manage theme localization', 'sitepress'); ?></a></p>                     
                    </td>
                </tr>                            
                <?php endif; ?>                      
            </tbody>
        </table>
        
        <h3><?php _e('CMS navigation', 'sitepress') ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th width="15%"><?php _e('Section', 'sitepress') ?></th>
                    <th><?php _e('Description', 'sitepress') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('Navigation', 'sitepress')?></td>
                    <td>
                        <p>
                            <?php echo __('WPML provides advanced menus and navigation to go with your WordPress website, including drop-down menus, breadcrumbs and sidebar navigation.', 'sitepress')?>
                        </p>
                        <p>
                            <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/navigation.php' ?>"><?php echo __('Configure navigation', 'sitepress') ?></a>
                        </p>                    
                    </td>
                </tr>            
                <tr>
                    <td><?php _e('Sticky links', 'sitepress')?></td>
                    <td>
                        <p><?php echo __('With Sticky Links, WPML can automatically ensure that all links on posts and pages are up-to-date, should their URL change.', 'sitepress'); ?></p>
                    
                        <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?>
                        <p><?php echo __('Sticky links are enabled.','sitepress') ?></p>
                        <?php else: ?>
                        <p><b><?php echo __('Sticky links are disabled.','sitepress') ?></b></p>
                        <?php endif; ?>
                        
                        <p>
                        <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/absolute-links.php' ?>"><?php echo __('Configure sticky links', 'sitepress') ?></a>
                        </p>                    
                    </td>
                </tr>                            
            </tbody>
        </table>
        
        <p><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/troubleshooting.php"><?php _e('Troubleshooting', 'sitepress')?></p>
        
        <?php do_action('icl_menu_footer'); ?>
    
</div>
