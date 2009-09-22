            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Translation options', 'sitepress') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
            
                            <?php if(!$sitepress_settings['content_translation_setup_complete']): ?>        
                                <form id="icl_more_options_wizard" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
                                <?php wp_nonce_field('icl_more_options_wizard','icl_more_options_wizardnounce') ?>            
                            <?php else: ?>
                                <form name="icl_more_options" id="icl_more_options" action="">
                            <?php endif; ?>
                
                            <h3><?php echo __('What kind of website is this?','sitepress') ?></h3>
                            <div class="icl_form_errors" style="display:none;margin-bottom:1px;"><?php echo __('Please select the kind of website','sitepress')?></div>
                            <ul>                
                                <li>
                                    <?php echo __("ICanLocalize needs to assign professional translators to each website that we translate. Please help us by indicating what kind of website you're setting up.", 'sitepress') ?><br />
                                </li>
                                <li> 
                                    <ul>
                                        <li>
                                            <label><input name="icl_website_kind" type="radio" value="0" <?php if($sitepress_settings['website_kind'] === "0"): ?>checked="checked"<?php endif;?> /> <?php echo __("Test site - translators will not be assigned and you an test the system.", 'sitepress'); ?></label><br />
                                        </li>
                                        <li>
                                            <label><input name="icl_website_kind" type="radio" value="1" <?php if($sitepress_settings['website_kind'] == 1): ?>checked="checked"<?php endif;?> /> <?php echo __("Development site with real contents", 'sitepress'); ?></label><br />
                                        </li>
                                        <li>
                                            <label><input name="icl_website_kind" type="radio" value="2" <?php if($sitepress_settings['website_kind'] == 2): ?>checked="checked"<?php endif;?> /> <?php echo __("Live site", 'sitepress'); ?></label><br />
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                            
                            <br /><p><a href="#icl-ct-advanced-options"><span><?php _e('Show advanced options &raquo;','sitepress') ?></span><span style="display:none;"><?php _e('&laquo; Hide advanced options','sitepress') ?></span></a></p>
                            <div id="icl-content-translation-advanced-options">
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
                                            <label><input name="icl_interview_translators" type="radio" value="1" <?php if($interview_translators == 1): ?>checked="checked"<?php endif;?> /> <?php echo __('Use translators from ICanLocalize.', 'sitepress'); ?></label>
                                        </li>
                                        <li>
                                            <label><input name="icl_interview_translators" type="radio" value="2" <?php if($interview_translators == 2): ?>checked="checked"<?php endif;?> /> <?php echo __('Use my own translators.', 'sitepress'); ?></label>
                                        </li>
                                    </ul>
                
                                </li>
                            </ul>
                
                            <div style="display: none;"><h3><?php echo __('Translation delivery','sitepress') ?></h3>    
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
                            </ul></div>
                
                            <h3><?php echo __("Notification preferences:", 'sitepress') ?></h3>
                            <ul>
                                <li>
                                    <ul>
                                        <li>
                                            <label><input name="icl_notify_complete" type="checkbox" value="1" <?php if($sitepress_settings['notify_complete']): ?>checked="checked"<?php endif;?> /> <?php echo __('Send an email notification when translations complete.', 'sitepress'); ?></label><br />
                                        </li>
                                        <li>
                                            <label><input name="icl_alert_delay" type="checkbox" value="1" <?php if($sitepress_settings['alert_delay']): ?>checked="checked"<?php endif;?> /> <?php echo __('Send an alert when translations delay for more than 4 days.', 'sitepress'); ?></label><br />
                                        </li>
                                    </ul>
                
                                </li>
                                <li>
                                    <i><?php echo __("ICanLocalize will send email notifications for these events.", 'sitepress') ?></i><br />
                                </li>
                            </ul>
                                
                            <h3><?php echo __("Translated document status:", 'sitepress') ?></h3>
                            <ul>
                                <li>
                                    <ul>
                                        <li>
                                            <label><input type="radio" name="icl_translated_document_status" value="0" <?php if(!$sitepress_settings['translated_document_status']): ?>checked="checked"<?php endif;?> /> <?php echo __('Draft', 'sitepress') ?></label>
                                        </li>
                                        <li>
                                            <label><input type="radio" name="icl_translated_document_status" value="1" <?php if($sitepress_settings['translated_document_status']): ?>checked="checked"<?php endif;?> /> <?php echo __('Same as the original document', 'sitepress') ?></label>
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
                                            <label><input name="icl_remote_management" type="checkbox" value="1" <?php if($sitepress_settings['remote_management']): ?>checked="checked"<?php endif;?> /> <?php echo __('Enable remote control over the translation management.', 'sitepress'); ?></label><br />
                                        </li>
                                    </ul>
                
                                </li>
                                <li>
                                    <i><?php _e("This feature is intended for blog networks. It allows controlling the translation process remotely via XML-RPC calls without going through the WordPress admin pages.<br />If you are running a single site, you don't need to enable this.", 'sitepress') ?></i><br />
                                </li>
                            </ul>
                            </div> <? // div id="icl-content-translation-advanced-options ?>
                                        
                            <?php if(!$sitepress_settings['content_translation_setup_complete']): ?>        
                                <div style="text-align:right">
                                    <input id="icl_content_trans_setup_back_1" class="button-primary" name="icl_content_trans_setup_back_1" value="<?php echo __('Back', 'sitepress') ?>" type="submit" />
                                    <input id="icl_content_trans_setup_next_1" class="button-primary" name="icl_content_trans_setup_next_1" value="<?php echo __('Next', 'sitepress') ?>" type="submit" />
                                </div>
                            <?php else: ?>
                                <p class="submit">
                                    <input class="button" name="create account" value="<?php echo __('Save', 'sitepress') ?>" type="submit" />
                                    <span class="icl_ajx_response" id="icl_ajx_response2"></span>    
                                </p>
                            <?php endif; ?>
                            </form>
            
                        </td>
                    </tr>
                </tbody>
            </table>
