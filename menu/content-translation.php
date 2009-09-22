<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php';         
    $active_languages = $sitepress->get_active_languages();
    $default_language = $sitepress->get_default_language();
    // put the default language first.
    foreach ($active_languages as $index => $lang) {
        if ($lang['code'] == $default_language) {
            $default_lang_data = $lang;
            unset($active_languages[$index]);
            break;
        }
    }
    if (isset($default_lang_data)) {
        array_unshift($active_languages, $default_lang_data);
    }
    
    
    $sitepress_settings = $sitepress->get_settings();    
    $icl_account_ready_errors = $sitepress->icl_account_reqs();
        
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <?php if($sitepress->get_icl_translation_enabled() ): ?>
        <img src="<?php echo ICL_PLUGIN_URL?>/res/img/web_logo_large.png" style="float: right; border: 1pt solid #C0C0C0; margin: 16px 10px 10px 10px;" alt="ICanLocalize" />
    <?php endif; ?>
    <h2><?php _e('Professional Translation', 'sitepress') ?></h2>    
        
    <?php if(!$sitepress->get_icl_translation_enabled() ): ?>
        <img src="<?php echo ICL_PLUGIN_URL?>/res/img/web_logo_large.png" style="float: right; border: 1pt solid #C0C0C0; margin: 16px 10px 10px 10px;" alt="ICanLocalize" />
        <p style="line-height:1.5"><?php echo __('<a href="http://www.icanlocalize.com">ICanLocalize</a> can provide professional translation for your site\'s contents.', 'sitepress'); ?></p>
        <p><input id="icl_enable_content_translation" type="button" class="button-primary" value="<?php echo __('Enable professional translation','sitepress') ?>" /></p>
        <p style="line-height:1.5"><?php printf(__('When enabled, you can use the <a href="%s">Translation Dashboard</a> to send posts and pages for translation. The entire process is completely effortless. The plugin will send the documents that need translation and then create the translated contents, ready to be published.', 'sitepress'), 'tools.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php');?></p>
        
        <p style="line-height:1.5"><?php echo __('Benefits:', 'sitepress'); ?></p>
        
        <?php
            $benefits = array(
                __('Profesional translators', 'sitepress'),
                __('Easy to use', 'sitepress'),
                __('Content sent to central server', 'sitepress'),
                __('Translated content automatically returned', 'sitepress'),
                __('Menus sent for translation', 'sitepress'),
                __('Translated menus automatically created', 'sitepress'),
                __('Easy communication with translators', 'sitepress'),
                __('All translations details are managed for you', 'sitepress'),
                __('Simple theme localization', 'sitepress'),
                __('Simple string localization', 'sitepress'),
                __('Low price', 'sitepress'),
            );
        ?>
        <ul>
            <?php foreach($benefits as $item): ?>
                <li class='icl_benefits'><?php echo $item ?></li>
            <?php endforeach; ?>
        </ul>
        <br clear='all' />
        <p style="line-height:1.5"><?php echo __('All translations are done by professional translators, writing in their native languages. You\'ll be able to chat with your translator and instruct what kind of writing style you prefer and which keywords should be emphasized for search engine optimization.', 'sitepress'); ?></p>
        <p style="line-height:1.5"><b><?php printf(__('Pricing for translation by ICanLocalize is a low %s USD per word between any language pair.', 'sitepress'), '0.07'); ?></b></p>
        <p><b>Don't worry</b>, you wont need to pay anything until you decide to use ICanLocalize for your translations!</p>
        <br />
    <?php else: ?>
        <?php if($sitepress->icl_account_configured() ): ?>
            <p style="line-height:1.5">
            <?php printf(__('To send documents to translation, use the <a href="%s">Translation dashboard</a>.' , 'sitepress'), 'tools.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php'); ?>
            </p>
        <?php else: ?>
            <p style="line-height:1.5">
            <?php _e('To enable professional translation by <a href="http://www.icanlocalize.com">ICanLocalize</a> please complete this setup screen.', 'sitepress'); ?></p>            
            <p style="line-height:1.5">
            <?php _e('Creating an account in ICanLocalize is free. You will only need to pay when sending posts and pages for translation.', 'sitepress'); ?></p>            
        <?php endif; ?>
        <?php if($sitepress_settings['content_translation_setup_complete']): ?>
            <p style="line-height:1.5">
            <input id="icl_disable_content_translation" type="button" class="button-secondary" value="<?php echo __('Disable professional translation','sitepress') ?>" />
            <span id="icl_toggle_ct_confirm_message" style="display:none"><?php echo __('Are you sure you want to disable professional translation?','sitepress'); ?></span>        
            </p>
        <?php endif; ?>        
        
        
        <?php if(!$sitepress_settings['content_translation_setup_complete']): /* setup wizard */ ?>
        <?php 
            if(!$sitepress_settings['content_translation_languages_setup']){
                $sw_width = 10;
            }elseif($sitepress_settings['content_translation_setup_wizard_step'] == 2){
                $sw_width = 45;
            }else{
                $sw_width = 84;
            }
        ?>
        <div id="icl_setup_wizard_wrap">
            <h3><?php _e('Before you can start using Professional translation, it needs to be set up', 'sitepress') ?></h3>
            <div id="icl_setup_wizard">
                <div class="icl_setup_wizard_step"><strong><?php _e('1. Translation Languages', 'sitepress')?></strong></div>
                <div class="icl_setup_wizard_step"><strong><?php _e('2. Choose translators', 'sitepress')?></strong></div>
                <div class="icl_setup_wizard_step"><strong><?php _e('3. ICanLocalize account setup', 'sitepress')?></strong></div>            
            </div>        
            <br clear="all" />
            <div id="icl_setup_wizard_progress"><div id="icl_setup_wizard_progress_bar" style="width:<?php echo $sw_width ?>%">&nbsp;</div></div>
        </div>
        <br />
        <?php endif; /* setup wizard */ ?>
        
        
        
        
        <?php if(count($active_languages) > 1): ?>

            <?php if(!$sitepress_settings['content_translation_setup_complete']): /* setup wizard */ ?>
                <?php if(!$sitepress_settings['content_translation_languages_setup']): ?>
                    <?php include ICL_PLUGIN_PATH . '/menu/content-translation-langs.php';?>
                <?php elseif($sitepress_settings['content_translation_setup_wizard_step'] == 2): ?>
                    <?php include ICL_PLUGIN_PATH . '/menu/content-translation-options.php';?>
                <?php else: ?>
                    <?php include ICL_PLUGIN_PATH . '/menu/content-translation-icl-account.php';?>
                <?php endif;?>

            <?php else: ?>
            
                <?php /* Not using the setup wizard */?>
                <?php include ICL_PLUGIN_PATH . '/menu/content-translation-langs.php';?>
            
                <br clear="all" />
                
                <?php include ICL_PLUGIN_PATH . '/menu/content-translation-options.php';?>
    
                <br clear="all" />
                
                <?php include ICL_PLUGIN_PATH . '/menu/content-translation-icl-account.php';?>
            <?php endif; ?>
            
        <?php else:?>
            <p class='icl_form_errors'><?php echo __('After you configure more languages for your blog, the translation options will show here', 'sitepress'); ?></p>
        <?php endif; ?>
           
        <br />         
    <?php endif; // if Professional translation enabled ?>
     
    
    <?php do_action('icl_menu_footer'); ?>
    
</div>
