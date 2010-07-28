<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php';

    if (isset($_GET['icl_refresh_langs']) || $sitepress->are_waiting_for_translators($sitepress->get_default_language())) {
        $iclsettings = $sitepress->get_settings();
        $iclsettings['last_get_translator_status_call'] = time();
        $sitepress->get_icl_translator_status($iclsettings);
        $sitepress->save_settings($iclsettings);
    }
    
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
    
    $icl_lang_status = $sitepress_settings['icl_lang_status'];
    
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap" id="icl_wrap" style="float:left;width:98%;">
    <div id="icon-options-general" class="icon32<?php if(!$sitepress_settings['basic_menu']) echo ' icon32_adv'?>"><br /></div>
    <h2><?php _e('Professional Translation', 'sitepress') ?></h2>    

    <?php include ICL_PLUGIN_PATH . '/menu/basic_advanced_switch.php' ?>     

    <table style="width:100%; border: none;"><tr>
    <td style="vertical-align:top;">
        <div id="icl_pro_content">

                    
        <?php if(isset($_POST['icl_form_success'])):?>
        <p class="icl_form_success"><?php echo $_POST['icl_form_success'] ?></p>
        <?php endif; ?>  
                
            <h3><?php _e('Translation management', 'sitepress')?></h3>
            <?php include ICL_PLUGIN_PATH . '/modules/icl-translation/icl-translation-dashboard.php'; ?>
        
            <?php if($sitepress_settings['icl_html_status']): ?>
            <div class="icl_cyan_box">
                <h3><?php _e('ICanLocalize account status', 'sitepress')?></h3>
                <?php echo $sitepress_settings['icl_html_status']; ?>
            </div>                    
            <?php endif; ?>

            
            <div class="icl_cyan_box">
                 <h3><?php _e('Professional translation setup', 'sitepress')?></h3>
            
                <input type="button" class="icl_account_setup_toggle button-primary icl_account_setup_toggle_main" value="<?php _e('Configure professional translation', 'sitepress') ?>"/>
                
                <div id="icl_account_setup">
            
                    <?php if(defined('ICL_DEBUG_DEVELOPMENT') && ICL_DEBUG_DEVELOPMENT): ?>
                    <a style="float:right;" href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/content-translation.php&amp;debug_action=reset_pro_translation_configuration&amp;nonce=<?php echo wp_create_nonce('reset_pro_translation_configuration')?>" class="button">Reset pro translation configuration</a>
                    <?php endif; ?>
                    
                    <?php if(count($active_languages) > 1): ?>
                            <?php include ICL_PLUGIN_PATH . '/menu/content-translation-options.php';?>
                            <br clear="all" />
                    <?php else:?>                    
                        <p class='icl_form_errors'><?php echo __('After you configure more languages for your blog, the translation options will show here', 'sitepress'); ?></p>
                    <?php endif; ?>            
            
                </div> <?php // <div id="icl_account_setup"> ?>
            </div> <?php // <div class="icl_cyan_box"> ?>            
            
    </div>    

    </td><td style="vertical-align:top; padding: 21px 0 0 10px;">
        <?php echo $sitepress->show_pro_sidebar() ?>
    </td></tr></table>
    <?php remove_action('icl_menu_footer', array($sitepress, 'menu_footer')) ?>                                                       
    <?php do_action('icl_extra_options_' . $_GET['page']); ?>        
                            
    <?php do_action('icl_menu_footer'); ?>

</div>

