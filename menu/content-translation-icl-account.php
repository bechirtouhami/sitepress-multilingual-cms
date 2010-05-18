<?php

require_once ICL_PLUGIN_PATH . '/menu/content-translation-icl-account-wizard.php';

$wizard = new ICL_account_wizard();

?>


            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('ICanlocalize account setup', 'sitepress') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                              
                            <?php if(!$sitepress->icl_account_configured()): ?>
                            
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
                            
                                <ul>
                                    <?php if($sitepress->icl_support_configured()): ?>
                                    <li>
                                        <label><input id="icl_existing" type="radio" value="0" onclick="<?php echo $wizard->on_click(0);?>" <?php if($sitepress->icl_support_configured()): ?>checked="checked"<?php endif; ?>/>
                                            <?php echo sprintf(__('Use my existing ICanLocalize account - <b>%s</b>', 'sitepress'), $sitepress_settings['support_icl_account_email']); ?>
                                        </label>
                                        <?php $wizard->use_existing_support_account(); ?>
                                    </li>
                                    <?php endif; ?>
                                    <li>
                                        <label><input id="icl_new" type="radio" value="1" onclick="<?php echo $wizard->on_click(1);?>" <?php if(!$sitepress->icl_support_configured()): ?>checked="checked"<?php endif;?> />
                                            <?php echo __('Create a new account in ICanLocalize', 'sitepress'); ?>
                                        </label>
                                        <?php $wizard->create_account($sitepress->icl_support_configured()); ?>
                                    </li>
                                    <?php if(!$sitepress->icl_support_configured()): ?>
                                    <li>
                                        <label><input id="icl_add" type="radio" value="2" onclick="<?php echo $wizard->on_click(2);?>" />
                                            <?php echo __('Add to an existing account at ICanLocalize', 'sitepress'); ?>
                                        </label>
                                            
                                        <?php $wizard->configure_account(); ?>
                                    </li>
                                    <?php endif; ?>
                                    <?php if($sitepress->icl_support_configured()): ?>
                                    <li>
                                        <label><input id="icl_transfer" type="radio" value="3" onclick="<?php echo $wizard->on_click(3);?>" />
                                            <?php echo __('Transfer to an existing account at ICanLocalize', 'sitepress'); ?>
                                        </label>
                                            
                                        <?php $wizard->transfer_to_account(); ?>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                                    
                                
                            <?php else: // if account configured ?>   

                                <form id="icl_create_account" method="post" action="admin.php?page=<?php echo ICL_PLUGIN_FOLDER  ?>/menu/content-translation.php#icl_create_account_form" <?php if($_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
                                <?php wp_nonce_field('icl_view_website_access_data','icl_view_website_access_data_nonce') ?>    
                                <p class="submit">                                    
                                    <?php echo __('Your ICanLocalize account is configured.', 'sitepress')?>
                                    <a href="javascript:;" onclick="jQuery('#icl_create_account').hide();jQuery('#icl_configure_account').fadeIn();"><?php echo __('Show access settings &raquo;', 'sitepress') ?></a>
                                </p>
                                </form> 
                
                                <form id="icl_configure_account" action="admin.php?page=<?php echo ICL_PLUGIN_FOLDER  ?>/menu/content-translation.php#icl_create_account_form" method="post" <?php if(!$_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
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
                                    <tr class="form-field">
                                        <th scope="row"><?php echo __('Project description', 'sitepress') ?></th>
                                        <td>
                                            <textarea name="icl_description" type="textarea" cols="60" rows="5"><?php echo  $_POST['icl_description']?$_POST['icl_description']:$sitepress_settings['icl_site_description'] ?></textarea>
                                            <p>Provide a short description of the website so that translators know what background is required from them.</p>
                                        </td>
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
         
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php if($sitepress->icl_account_configured() ): ?>
             <p class="alignright">   
             <input type="button" class="icl_account_setup_toggle button-primary" value="<?php _e('Close', 'sitepress')?>" />   
             </p>
             
            <?php if($sitepress_settings['content_translation_setup_complete']): ?>
                <p><input id="icl_disable_content_translation" type="button" class="button-secondary" 
                    value="<?php echo __('Disable professional translation','sitepress') ?>" /></p>
            <?php endif; ?>        

             <div class="clear"></div>
                          
             <?php endif; ?>
             
