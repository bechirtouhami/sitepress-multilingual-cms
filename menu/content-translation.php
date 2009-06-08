<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php';     
    require_once ICL_PLUGIN_PATH . '/inc/plugins-texts-functions.php';     
    $active_languages = $sitepress->get_active_languages();            
    $sitepress_settings = $sitepress->get_settings();
    $icl_account_ready_errors = $sitepress->icl_account_reqs();
    $icl_plugins_texts = icl_pt_get_texts();
    icl_get_posts_translatable_fields();
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>    

    <p>
    <label for="icl_enable_content_translation">
    <input type="checkbox" id="icl_enable_content_translation" name="icl_enable_content_translation" value="1" <?php if($sitepress->get_icl_translation_enabled() ): ?>checked="checked"<?php endif;?>/>&nbsp;
    <?php echo __('Enable content translation', 'sitepress') ?>
    </label>
    </p>
    <span id="icl_toggle_ct_confirm_message" style="display:none"><?php echo __('Are you sure you want to disable content translation?','sitepress'); ?></span>
    
    <?php if(!$sitepress->get_icl_translation_enabled() ): ?>
        <div class="updated fade">
        <p style="line-height:1.5"><?php echo __('Content Translation allows you to have all the site\'s contents professionally translated.', 'sitepress'); ?></p>
        <p style="line-height:1.5"><?php printf(__('When enabled, you can use the <a href="%s">Translation Dashboard</a> to send posts and pages for translation. The entire process is completely effortless. The plugin will send the documents that need translation and then create the translated contents, ready to be published.', 'sitepress'), 'tools.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php');?></p>
        <p style="line-height:1.5"><?php echo __('All translations are done by professional translators, writing in their native languages. You\'ll be able to chat with your translator and instruct what kind of writing style you prefer and which keywords should be emphasized for search engine optimization.', 'sitepress'); ?></p>
        <p style="line-height:1.5"><?php echo __('Content translation is currently disabled. To enable it, click on the checkbox at the top of this page.', 'sitepress'); ?></p>
        </div>
    <?php else: ?>    
        <?php if($sitepress->icl_account_configured() ): ?>
        <div class="updated fade">
        <p><?php printf(__('To send documents to translation, use the <a href="%s">Translation dashboard</a>.' , 'sitepress'), 'tools.php?page='.basename(ICL_PLUGIN_PATH).'/modules/icl-translation/icl-translation-dashboard.php'); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if(count($active_languages) > 1): ?>
            <h3><?php echo __('Translation pairs','sitepress') ?></h3>    
            <form id="icl_language_pairs_form" name="icl_language_pairs_form" action="">
            <ul id="icl_language_pairs" >    
                <?php foreach($active_languages as $lang): ?>            
                    <li>
                        <label><input class="icl_tr_from" type="checkbox" name="icl_lng_from_<?php echo $lang['code']?>" id="icl_lng_from_<?php echo $lang['code']?>" <?php if($sitepress->get_icl_translation_enabled($lang['code'])): ?>checked="checked"<?php endif?> />
                        <?php printf(__('Translate from %s to these languages','sitepress'), $lang['display_name']) ?></label>
                        <ul id="icl_tr_pair_sub_<?php echo $lang['code'] ?>" <?php if(!$sitepress->get_icl_translation_enabled($lang['code'])): ?>style="display:none"<?php endif?>>
                        <?php foreach($active_languages as $langto): if($lang['code']==$langto['code']) continue; ?>        
                            <li>
                                <label><input class="icl_tr_to" type="checkbox" name="icl_lng_to_<?php echo $lang['code']?>_<?php echo $langto['code']?>" id="icl_lng_from_<?php echo $lang['code']?>_<?php echo $langto['code']?>" <?php if($sitepress->get_icl_translation_enabled($lang['code'],$langto['code'])): ?>checked="checked"<?php endif?> />
                                <?php echo $langto['display_name'] ?></label>
                            </li>    
                        <?php endforeach; ?>
                        </ul>
                    </li>    
                <?php endforeach; ?>
            </ul>    
            <input id="icl_save_language_pairs" type="button" class="button-secondary action" value="<?php echo __('Save', 'sitepress') ?>" />
            <span class="icl_ajx_response" id="icl_ajx_response"></span>    
            </form>
            <br clear="all" />    
            
            <form name="icl_more_options" action="">

            <h3><?php echo __('What kind of website is this?','sitepress') ?></h3>
            <ul>
                <li>
                    <?php echo __("ICanLocalize needs to assign professional translators to each website that we translate. Please help us by indicating what kind of website you're setting up.", 'sitepress') ?><br />
                </li>
                <li>
                    <ul>
                        <li>
                            <label><input name="icl_website_kind" type="radio" value="0" <?php if($sitepress_settings['website_kind'] == 0): ?>checked="checked"<?php endif;?> /> <?php echo __("Test site - I'm only testing out the system and don't need to have translators assigned yet.", 'sitepress'); ?></label><br />
                        </li>
                        <li>
                            <label><input name="icl_website_kind" type="radio" value="1" <?php if($sitepress_settings['website_kind'] == 1): ?>checked="checked"<?php endif;?> /> <?php echo __("Development site with real contents - This site includes real contents that need to be translated, but still running on a development server.", 'sitepress'); ?></label><br />
                        </li>
                        <li>
                            <label><input name="icl_website_kind" type="radio" value="2" <?php if($sitepress_settings['website_kind'] == 2): ?>checked="checked"<?php endif;?> /> <?php echo __("Production site (running on the live server) - This is the actual production site with contents that need to be translated.", 'sitepress'); ?></label><br />
                        </li>
                    </ul>
                </li>
            </ul>
        
            <h3><?php echo __('Translator selection','sitepress') ?></h3>
            <?php
                $interview_translators = $sitepress_settings['interview_translators'];
                if(!in_array($interview_translators, array(0, 1, 2))){
                    $interview_translators = 0;
                }
            ?>
            <ul>
                <li>
                    <?php echo __("Select how you want to select translators:", 'sitepress') ?><br />
                </li>
                <li>
                    <ul>
                        <li>
                            <label><input name="icl_interview_translators" type="radio" value="0" <?php if($interview_translators == 0): ?>checked="checked"<?php endif;?> /> <?php echo __('ICanLocalize will assign translators for this work.', 'sitepress'); ?></label><br />
                        </li>
                        <li>
                            <label><input name="icl_interview_translators" type="radio" value="1" <?php if($interview_translators == 1): ?>checked="checked"<?php endif;?> /> <?php echo __('I want to interview my translators.', 'sitepress'); ?></label>
                        </li>
                        <li>
                            <label><input name="icl_interview_translators" type="radio" value="2" <?php if($interview_translators == 2): ?>checked="checked"<?php endif;?> /> <?php echo __('Use my own translators.', 'sitepress'); ?></label>
                        </li>
                    </ul>

                </li>
                <li>
                    <i><?php echo __("If you want to choose translators, you will be notified by email whenever a translator applies to work on your project.", 'sitepress') ?></i><br />
                </li>
            </ul>

            <h3><?php echo __('Translation delivery','sitepress') ?></h3>    
            <ul>
                <li>
                    <?php echo __("Select the desired translation delivery mehtod:", 'sitepress') ?><br />
                </li>
                <li>
                    <ul>
                        <li>
                            <label><input name="icl_delivery_method" type="radio" value="0" <?php if((int)$sitepress_settings['translation_pickup_method'] == 0): ?>checked="checked"<?php endif;?> /> <?php echo __('Translations will be posted back to this website via XML-RPC.', 'sitepress'); ?></label><br />
                        </li>                        
                        <li>
                            <label><input name="icl_delivery_method" type="radio" value="1" <?php if($sitepress_settings['translation_pickup_method'] == 1): ?>checked="checked"<?php endif;?> disabled="disabled" /> <?php echo __('This WordPress installation will poll for translations.', 'sitepress'); ?></label><br />
                        </li>                        
                    </ul>
                </li>
                <li>
                    <i><?php echo __("Choose polling if your site is inaccessible from the Internet.", 'sitepress') ?></i><br />
                </li>
            </ul>

            <h3><?php echo __("Notification preferences:", 'sitepress') ?></h3>
            <ul>
                <li>
                    <ul>
                        <li>
                            <label><input name="icl_notify_complete" type="checkbox" value="1" <?php if($sitepress_settings['icl_notify_complete']): ?>checked="checked"<?php endif;?> /> <?php echo __('Send an email notification when translations complete.', 'sitepress'); ?></label><br />
                        </li>
                        <li>
                            <label><input name="icl_alert_delay" type="checkbox" value="1" <?php if($sitepress_settings['icl_alert_delay']): ?>checked="checked"<?php endif;?> /> <?php echo __('Send an alert when translations delay for more than 4 days.', 'sitepress'); ?></label><br />
                        </li>
                    </ul>

                </li>
                <li>
                    <i><?php echo __("ICanLocalize will send notifications messages via email of these events.", 'sitepress') ?></i><br />
                </li>
            </ul>
                
            <h3><?php echo __("Translated document status:", 'sitepress') ?></h3>
            <ul>
                <li>
                    <ul>
                        <li>
                            <label><input type="radio" name="icl_translation_document_status" value="0" <?php if(!$sitepress_settings['translated_document_status']): ?>checked="checked"<?php endif;?> /> <?php echo __('Draft', 'sitepress') ?></label>
                        </li>
                        <li>
                            <label><input type="radio" name="icl_translation_document_status" value="1" <?php if($sitepress_settings['translated_document_status']): ?>checked="checked"<?php endif;?> /> <?php echo __('Same as the original document', 'sitepress') ?></label>
                        </li>
                    </ul>

                </li>
                <li>
                    <i><?php echo __("Choose if translations should be published when received. Note: If Publish is selected, the translation will only be published if the original node is published when the translation is received.", 'sitepress') ?></i><br />
                </li>
            </ul>

            <h3><?php echo __("Remote control translation management:", 'sitepress') ?></h3>
            <ul>
                <li>
                    <ul>
                        <li>
                            <label><input name="icl_remote_management" type="checkbox" value="1" <?php if($sitepress_settings['icl_remote_management']): ?>checked="checked"<?php endif;?> /> <?php echo __('Enable remote control over the translation management.', 'sitepress'); ?></label><br />
                        </li>
                    </ul>

                </li>
                <li>
                    <i><?php echo __("Content translation can be managed remotely via xmlrpc calls.", 'sitepress') ?></i><br />
                </li>
            </ul>
            
            <p class="submit">
                <input class="button" name="create account" value="<?php echo __('Save', 'sitepress') ?>" type="submit" />
                <span class="icl_ajx_response" id="icl_ajx_response2"></span>    
            </p>        
            </form>
            
            <?php /************
            <h3><?php echo __('Plugins texts translation', 'sitepress') ?></h3>                
            <form name="icl_plugins_texts" action="">
            <table id="icl_plugins_texts" class="widefat" cellspacing="0">
            <thead>
            <tr>
            <th scope="col"><?php echo __('Enable translation', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Plugin', 'sitepress') ?></th>
            <th scope="col"><?php echo __('List of fields we translate', 'sitepress') ?></th>        
            </tr>        
            </thead>        
            <tfoot>
            <tr>
            <th scope="col"><?php echo __('Enable translation', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Plugin', 'sitepress') ?></th>
            <th scope="col"><?php echo __('List of fields we translate', 'sitepress') ?></th>        
            </tr>        
            </tfoot>                
            <tbody>        
            <?php foreach($icl_plugins_texts as $ipt): ?>
            <tr>
            <td scope="col"><input type="checkbox" name="icl_plugins_texts_enabled[]" value="<?php echo $ipt['plugin_name'] ?>" <?php if(!$ipt['active']): ?>disabled="disabled"<?php endif;?> <?php if($ipt['enabled']): ?>checked="checked"<?php endif;?>/></td>
            <td scope="col"><?php echo $ipt['plugin_name_short'] ?></td>
            <td scope="col"><?php echo $ipt['fields_list'] ?></td>
            </tr>
            <?php endforeach; ?>                                                                  
            </tbody>        
            </table>   
            <p class="submit">
                <input class="button" name="create account" value="<?php echo __('Save', 'sitepress') ?>" type="submit" />
                <span class="icl_ajx_response" id="icl_ajx_response3"></span>    
            </p>        
            </form>
            
            <form method="post" action="<?php echo ICL_PLUGIN_URL ?>/ajax.php" enctype="multipart/form-data">
            <input type="hidden" name="icl_ajx_action" value="icl_plugins_texts" />
            <input type="hidden" name="icl_pt_file_upload" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />
            <?php echo __('Upload more plugin texts definitions from a CSV file.', 'sitepress') ?> <a href="#"><?php echo __('Read more', 'sitepress') ?></a>           
            &nbsp;&nbsp;&nbsp;<input class="button" type="file" name="plugins_texts_csv" />             
            <input class="button" id="icl_pt_upload" type="submit" value="<?php echo __('Submit', 'sitepress')?>" />        
            <?php if(isset($_GET['csv_upload'])):?>&nbsp;<span class="icl_ajx_response" style="display:inline">CSV file uploaded</span><?php endif;?>    
            </form>
            *****************/ ?>
            
            <h3 id="icl_create_account_form"><?php echo __('Configure your ICanLocalize account', 'sitepress') ?></h3>             
            <?php if(isset($_POST['icl_form_errors']) || ($icl_account_ready_errors && !$sitepress->icl_account_configured() )):  ?>
            <div class="icl_form_errors">
                <?php echo $_POST['icl_form_errors'] ?>
                <?php if($icl_account_ready_errors):  ?>
                <?php echo __('Before you create an ICanLocalize account you need to fix these:', 'sitepress'); ?>
                <ul>
                <?php foreach($icl_account_ready_errors as $err):?>        
                <li><?php echo $err ?></li>    
                <?php endforeach ?>
                </ul>   
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_POST['icl_form_success'])):?>
            <p class="icl_form_success"><?php echo $_POST['icl_form_success'] ?></p>
            <?php endif; ?>  
              
            <?php if(!$sitepress->icl_account_configured()): ?>
            
                <form id="icl_create_account" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>#icl_create_account_form" <?php if($_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
                <?php wp_nonce_field('icl_create_account', 'icl_create_account_nonce') ?>    
                <i><?php echo __('Translation will only be available once your ICanLocalize account has been created. Complete this form and click on \'Create account\'.', 'sitepress')?></i>
                <table class="form-table icl-account-setup">
                    <tbody>
                    <tr class="form-field">
                        <th scope="row"><?php echo __('First name', 'sitepress')?></th>
                        <td><input name="user[fname]" type="text" value="<?php echo $_POST['user']['fname']?$_POST['user']['fname']:$current_user->first_name ?>" /></td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row"><?php echo __('Last name', 'sitepress')?></th>
                        <td><input name="user[lname]" type="text" value="<?php echo  $_POST['user']['lname']?$_POST['user']['lname']:$current_user->last_name ?>" /></td>
                    </tr>        
                    <tr class="form-field">
                        <th scope="row"><?php echo __('Email', 'sitepress')?></th>
                        <td><input name="user[email]" type="text" value="<?php echo  $_POST['user']['email']?$_POST['user']['email']:$current_user->data->user_email ?>" /></td>
                    </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="hidden" name="create_account" value="1" />
                    <input class="button" name="create account" value="<?php echo __('Create account', 'sitepress') ?>" type="submit" 
                        <?php if($icl_account_ready_errors):  ?>disabled="disabled"<?php endif; ?> />
                    <a href="javascript:;" onclick="jQuery('#icl_create_account').hide();jQuery('#icl_configure_account').fadeIn();"><?php echo __('I already have an account at ICanLocalize', 'sitepress') ?></a>
                </p>
                </form> 

                <form id="icl_configure_account" action="<?php echo $_SERVER['REQUEST_URI'] ?>#icl_create_account_form" method="post" <?php if(!$_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
                <?php wp_nonce_field('icl_configure_account','icl_configure_account_nonce') ?>    
                <i><?php echo __('Translation will only be available once this project has been added to your ICanLocalize account. Enter your login information below and click on \'Add this project to my account\'.', 'sitepress')?></i>
                <table class="form-table icl-account-setup">
                    <tbody>
                    <tr class="form-field">
                        <th scope="row"><?php echo __('Email', 'sitepress')?></th>
                        <td><input name="user[email]" type="text" value="<?php echo  $_POST['user']['email']?$_POST['user']['email']:$current_user->data->user_email ?>" /></td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row"><?php echo __('Password', 'sitepress')?></th>
                        <td><input name="user[password]" type="password" /></td>
                    </tr>        
                    </tbody>
                </table>
                <p class="submit">
                    <input type="hidden" name="create_account" value="0" />
                    <input class="button" name="configure account" value="<?php echo __('Add this project to my account', 'sitepress') ?>" type="submit" 
                        <?php if($icl_account_ready_errors):  ?>disabled="disabled"<?php endif; ?> />
                    <a href="javascript:;" onclick="jQuery('#icl_configure_account').hide();jQuery('#icl_create_account').fadeIn();"><?php echo __('Create a new ICanLocalize account', 'sitepress') ?></a>
                </p>
                </form>    
                
             <?php else: // if account configured ?>   

                <form id="icl_create_account" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>#icl_create_account_form" <?php if($_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
                <?php wp_nonce_field('icl_view_website_access_data','icl_view_website_access_data_nonce') ?>    
                <p class="submit">
                    <?php echo __('Your ICanLocalize account is configured.', 'sitepress')?>
                    <a href="javascript:;" onclick="jQuery('#icl_create_account').hide();jQuery('#icl_configure_account').fadeIn();"><?php echo __('Show access settings', 'sitepress') ?></a>
                </p>
                </form> 

                <form id="icl_configure_account" action="<?php echo $_SERVER['REQUEST_URI'] ?>#icl_create_account_form" method="post" <?php if(!$_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
                <?php wp_nonce_field('icl_change_website_access_data','icl_change_website_access_data_nonce') ?>
                <?php echo __('Your ICanLocalize account access settings:', 'sitepress')?>
                <table class="form-table icl-account-setup">
                    <tbody>
                    <tr class="form-field">
                        <th scope="row"><?php echo __('Website ID', 'sitepress') ?></th>
                        <td><input name="access[website_id]" type="text" value="<?php echo  $_POST['access']['website_id']?$_POST['access']['website_id']:$sitepress_settings['site_id'] ?>" /></td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row"><?php echo __('Access key', 'sitepress') ?></th>
                        <td><input name="access[access_key]" type="text" value="<?php echo  $_POST['access']['access_key']?$_POST['access']['access_key']:$sitepress_settings['access_key'] ?>"/></td>
                    </tr>        
                    </tbody>
                </table>
                <p class="submit">
                    <input type="hidden" name="create_account" value="0" />
                    <input class="button" name="configure account" value="<?php echo __('Save', 'sitepress') ?>" type="submit" 
                        <?php if($icl_account_ready_errors):  ?>disabled="disabled"<?php endif; ?> />
                    <a href="javascript:;" onclick="jQuery('#icl_configure_account').hide();jQuery('#icl_create_account').fadeIn();"><?php echo __('These access settings are OK.', 'sitepress') ?></a>
                </p>
                </form>    

             <?php endif; ?>
         
        <?php else:?>
            <p class='icl_form_errors'><?php echo __('After you configure more languages for your blog, the translation options will show here', 'sitepress'); ?></p>
        <?php endif; ?>
        
    <?php endif; // in content translation enabled ?>
     
    
</div>