            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Translation pairs', 'sitepress') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>

                            <?php if(!$sitepress_settings['content_translation_languages_setup']): ?>        
                                <form id="icl_language_pairs_form" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
                                <?php wp_nonce_field('icl_language_pairs_form','icl_language_pairs_formnounce') ?>            
                                
                            <?php else: ?>
                                <form id="icl_language_pairs_form" name="icl_language_pairs_form" action="">
                            <?php endif; ?>
                                <?php $show_enabled_first = array(true, false) ?>
                                <?php foreach($show_enabled_first as $show_enabled): ?>
                                    <?php if($show_enabled): ?>
                                        <div id="icl_languages_enabled" >
                                        <ul class="icl_language_pairs">
                                    <?php else: ?>
                                        <p><a href="#icl-show_disabled_langs"><span><?php _e('Show more translation pairs &raquo;','sitepress') ?></span><span style="display:none;"><?php _e('&laquo; Hide additional languages','sitepress') ?></span></a></p>
                                        <div id="icl_languages_disabled" style="display:none;">
                                        <ul class="icl_language_pairs">
                                    <?php endif; ?>
                                    <?php foreach($active_languages as $lang): ?>            
                                        <?php $enabled = $sitepress->get_icl_translation_enabled($lang['code']); ?>
                                        <?php if(($show_enabled && ($enabled || $lang['code'] == $default_language)) || (!$show_enabled && !($enabled || $lang['code'] == $default_language))): ?>
                                            <li style="float:left;width:98%;">
                                                <label><input class="icl_tr_from" type="checkbox" name="icl_lng_from_<?php echo $lang['code']?>" id="icl_lng_from_<?php echo $lang['code']?>" <?php if($sitepress->get_icl_translation_enabled($lang['code'])): ?>checked="checked"<?php endif?> />
                                                <?php printf(__('Translate from %s to these languages','sitepress'), $lang['display_name']) ?></label>
                                                <ul id="icl_tr_pair_sub_<?php echo $lang['code'] ?>" <?php if(!$sitepress->get_icl_translation_enabled($lang['code'])): ?>style="display:none"<?php endif?>>
                                                <?php foreach($active_languages as $langto): if($lang['code']==$langto['code']) continue; ?>        
                                                    <li style="float:left;list-style:none;width:30%;">
                                                        <label><input class="icl_tr_to" type="checkbox" name="icl_lng_to_<?php echo $lang['code']?>_<?php echo $langto['code']?>" id="icl_lng_from_<?php echo $lang['code']?>_<?php echo $langto['code']?>" <?php if($sitepress->get_icl_translation_enabled($lang['code'],$langto['code'])): ?>checked="checked"<?php endif?> />
                                                        <?php echo $langto['display_name'] ?></label>
                                                    </li>    
                                                <?php endforeach; ?>
                                                </ul>
                                            </li>
                                            
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </ul>
                                    </div>
                                <?php endforeach; ?>
                            
                            <?php if(!$sitepress_settings['content_translation_languages_setup']): ?>        
                                <div style="text-align:right"><input class="button-primary" name="save" value="<?php echo __('Next', 'sitepress') ?>" type="submit" /></div>
                            <?php else: ?>
                                <input id="icl_save_language_pairs" type="button" class="button-secondary action" value="<?php echo __('Save', 'sitepress') ?>" />
                                <span class="icl_ajx_response" id="icl_ajx_response"></span>
                            <?php endif; ?>
                            
                            </form>

                        </td>
                    </tr>
                </tbody>
            </table>
