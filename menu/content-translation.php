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
        <p style="line-height:1.5"><?php _e('The entire process is completely effortless. WPML will send the documents that need translation and then create the translated contents, ready to be published.', 'sitepress');?></p>
        
        <!-- p style="line-height:1.5">< ?php echo __('Benefits:', 'sitepress'); ? ></p -->
        
        <?php
            $benefits = array(
                array(__('Accurate and fluent translations', 'sitepress'), __('Professional translators, writing in their native languages will translate your site.','sitepress')),
                array(__('Effortless','sitepress'),__('You write in your language and WPML will build all the translations.','sitepress')),
                array(__('Affordable','sitepress'),__("Pay only for what needs to be translated. When you update contents, you pay only for what's changed.",'sitepress'))
            );
        ?>
        <ul>
            <?php foreach($benefits as $item): ?>
                <li class='icl_benefits'><b><?php echo $item[0] ?></b><br /><span class="icl_benefit_more"><?php echo $item[1] ?></span></li>
            <?php endforeach; ?>
        </ul>
        <br />
        <p style="line-height:1.5"><?php printf(__('Pricing for professional translation is <b>%s USD per word</b> between any language pair.', 'sitepress'), '0.07'); ?></p>
        <br /><p><input id="icl_enable_content_translation" type="button" class="button-primary" value="<?php echo __('Enable professional translation','sitepress') ?>" /> &nbsp; | &nbsp;
        <?php printf(__('<a href="%s">Cost estimate</a>', 'sitepress'), 'tools.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php');?> &nbsp; | &nbsp;
        <a href="http://wpml.org/?page_id=1169" target="_blank"><?php _e('More information','sitepress'); ?></a></p>
        <br />
    <?php else: ?>
        <?php if($sitepress->icl_account_configured() ): ?>
            <?php if(defined('ICL_DEBUG_DEVELOPMENT') && ICL_DEBUG_DEVELOPMENT): ?>
            <a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/content-translation.php&debug_action=reset_pro_translation_configuration&nonce=<?php echo wp_create_nonce('reset_pro_translation_configuration')?>" class="button">Reset pro translation configuration</a>
            <?php endif; ?>
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
            </p>
        <?php endif; ?>        
        
        <span id="icl_toggle_ct_confirm_message" style="display:none"><?php echo __('Are you sure you want to disable professional translation?','sitepress'); ?></span>        
        
        <?php if(!$sitepress_settings['content_translation_setup_complete']): /* setup wizard */ ?>
        <?php 
            if(!$sitepress_settings['content_translation_languages_setup']){
                $sw_width = 10;
            }elseif($sitepress_settings['content_translation_setup_wizard_step'] == 2){
                $sw_width = 45;
            }else{
                $sw_width = 64;
            }
        ?>
        <div id="icl_setup_wizard_wrap">
            <h3><?php _e('Before you can start using Professional translation, it needs to be set up', 'sitepress') ?></h3>
            <div id="icl_setup_wizard_2">
                <div class="icl_setup_wizard_step"><strong><?php _e('1. Translation Languages', 'sitepress')?></strong></div>
                <div class="icl_setup_wizard_step"><strong><?php _e('2. ICanLocalize account setup', 'sitepress')?></strong></div>            
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
