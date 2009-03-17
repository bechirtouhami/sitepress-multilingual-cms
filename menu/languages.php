<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $active_languages = $sitepress->get_active_languages();            
    $languages = $sitepress->get_languages();            
    $sitepress_settings = $sitepress->get_settings();
    foreach($active_languages as $lang){
        if($lang['code']!=$sitepress->get_default_language()){
            $sample_lang = $lang;
            break;
        }
    }
    $default_language = $sitepress->get_language_details($sitepress->get_default_language());
    $locales = $sitepress->get_locale_file_names();
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup SitePress', 'sitepress') ?></h2>    
    
    <?php if(!$sitepress_settings['existing_content_language_verified']): ?>
        <h3><?php echo __('Current content language', 'sitepress') ?></h3>    
        <form id="icl_initial_language" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
        <?php wp_nonce_field('icl_initial_language') ?>            
        <p>
            <?php echo __('Before adding other languages, please select the language existing contents are written in:') ?><br /><br />
            <select name="icl_initial_language_code">
            <?php foreach($languages as $lang): $is_default = ($sitepress->get_default_language()==$lang['code']);?>
            <option <?php if($is_default):?>selected="selected"<?php endif;?> value="<?php echo $lang['code']?>"><?php echo $lang['display_name']?></option>
            <?php endforeach; ?>
            </select>            
            &nbsp;
            <input class="button" name="save" value="<?php echo __('Save') ?>" type="submit" />
        </p>
        </form>        
    <?php else: ?>
    
        <h3><?php echo __('Site Languages', 'sitepress') ?></h3>    
        <table id="icl_setup_table" class="form-table">
            <tr valign="top">            
                <td>
                    <?php echo __('This list shows the languages that are enabled for this site. Select the default language for contents.','sitepress'); ?><br />
                    <ul id="icl_enabled_languages">
                            <?php foreach($active_languages as $lang): $is_default = ($sitepress->get_default_language()==$lang['code']); ?>
                        <li <?php if($is_default):?>class="default_language"<?php endif;?>><label><input name="default_language" type="radio" value="<?php echo $lang['code'] ?>" <?php if($is_default):?>checked="checked"<?php endif;?> /> <?php echo $lang['display_name'] ?> <?php if($is_default):?>(<?php echo __('default') ?>)<?php endif?></label></li>
                        <?php endforeach ?>
                    </ul>
                    <br clear="all" />
                    <input id="icl_save_default_button" type="button" class="button-secondary action" value="<?php echo __('Save default language', 'sitepress') ?>" />
                    <input id="icl_cancel_default_button" type="button" class="button-secondary action" value="<?php echo __('Cancel', 'sitepress') ?>" />                                    
                    <input id="icl_change_default_button" type="button" class="button-secondary action" value="<?php echo __('Change default language', 'sitepress') ?>" <?php if(count($active_languages) < 2): ?>style="display:none"<?php endif ?> />
                    
                    <input id="icl_add_remove_button" type="button" class="button-secondary action" value="<?php echo __('Add / Remove languages', 'sitepress') ?>">
                    <span class="icl_ajx_response" id="icl_ajx_response"></span>
                    <br clear="all" />
                    <div id="icl_avail_languages_picker">                
                        <ul>
                        <?php foreach($languages as $lang): ?>
                            <li><label><input type="checkbox" value="<?php echo $lang['code'] ?>" <?php if($lang['active']):?>checked="checked"<?php endif;?> 
                            <?php if($sitepress->get_default_language()==$lang['code']):?>disabled="disabled"<?php endif;?>/>
                                <?php if($lang['major']):?><strong><?php endif;?><?php echo $lang['display_name'] ?><?php if($lang['major']):?></strong><?php endif;?></label></li>
                        <?php endforeach ?>
                        </ul>
                        <br clear="all">
                        <div>
                            <input id="icl_save_language_selection" type="button" class="button-secondary action" value="<?php echo __('Save language selection', 'sitepress') ?>">
                            <input id="icl_cancel_language_selection" type="button" class="button-secondary action" value="<?php echo __('Cancel', 'sitepress') ?>">                                
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <span id="icl_more_languages_wrap">
            <span id="icl_lnt">
            <?php if(count($active_languages) > 1): ?>            
                <h3><?php echo __('Choose how to determine which language visitors see contents in', 'sitepress') ?></h3>    
                <form id="icl_save_language_negotiation_type" name="icl_save_language_negotiation_type">
                <ul>
                    <li>
                        <label>
                            <input type="radio" name="icl_language_negotiation_type" value="1" <?php if($sitepress_settings['language_negotiation_type']==1):?>checked="checked"<?php endif?> />
                            <?php echo sprintf(__('Different languages in directories (%s - %s, %s/%s - %s, etc.)', 'sitepress'), get_option('home'), $default_language['display_name'] , get_option('home'), $sample_lang['code'], $sample_lang['display_name'] ) ?>
                        </label>
                    </li>
                    <?php 
                    if(defined('WPMU_PLUGIN_DIR')){
                        $icl_lnt_disabled = 'disabled="disabled" ';
                    }else{
                        $icl_lnt_disabled = '';
                    } 
                    ?>
                    <li>
                        <label>
                            <input <?php echo $icl_lnt_disabled ?>id="icl_lnt_domains" type="radio" name="icl_language_negotiation_type" value="2" <?php if($sitepress_settings['language_negotiation_type']==2):?>checked="checked"<?php endif?> />
                            <?php echo __('A different domain per language', 'sitepress') ?>
                            <?php if($icl_lnt_disabled): ?>
                            <span class="icl_error_text"><?php echo __('This option is not yet available for WPMU', 'sitepress')?></span>
                            <?php endif; ?>
                        </label>
                        <?php if($sitepress_settings['language_negotiation_type']==2):?>                    
                        <div id="icl_lnt_domains_box">
                        <table class="language_domains">
                        <?php foreach($active_languages as $lang) :?>
                        <tr>
                            <td><?php echo $lang['display_name'] ?></td>
                            <?php if($lang['code']==$sitepress->get_default_language()): ?>                        
                            <td id="icl_ln_home"><?php echo get_option('home') ?></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <?php else: ?>
                            <td><input type="text" id="language_domain_<?php echo $lang['code'] ?>" name="language_domains[<?php echo $lang['code'] ?>]" value="<?php echo $sitepress_settings['language_domains'][$lang['code']] ?>" size="40" /></td>
                            <td><label><input class="validate_language_domain" type="checkbox" name="validate_language_domains[]" value="<?php echo $lang['code'] ?>" checked="checked" /> <?php echo  __('Validate on save', 'sitepress') ?></td>
                            <td><span id="ajx_ld_<?php echo $lang['code'] ?>"></span></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        </table>
                        </div>
                        <?php endif; ?>
                    </li>                
                    <li>
                        <label>
                            <input type="radio" name="icl_language_negotiation_type" value="3" <?php if($sitepress_settings['language_negotiation_type']==3):?>checked="checked"<?php endif?> />
                            <?php echo sprintf(__('Language name added as a parameter (%s?lang=%s - %s)', 'sitepress'),get_option('home'),$sample_lang['code'],$sample_lang['display_name']) ?>
                        </label>
                    </li>
                </ul>
                <p>
                    <input class="button" name="save" value="<?php echo __('Save') ?>" type="submit" />
                    <span class="icl_ajx_response" id="icl_ajx_response2"></span>
                </p>
                </form>            
            <?php endif; ?>
            </span>
            
            <span id="icl_lso">
            <?php if(count($active_languages) > 1):?>            
                <h3><?php echo __('Language switcher options', 'sitepress') ?></h3>    
                <form id="icl_save_language_switcher_options" name="icl_save_language_switcher_options">
                <p class="icl_form_errors" style="display:none"></p>
                <ul>
                    <li>
                        <label>
                            <input type="checkbox" name="icl_lso_header" value="1" <?php if($sitepress_settings['icl_lso_header']==1):?>checked="checked"<?php endif?> />
                            <?php echo sprintf(__('Include automatically in the header', 'sitepress')) ?>
                        </label>
                    </li>
                    <li>
                        <p><?php echo __('When translation is missing', 'sitepress')?></p>
                        <ul>
                            <li>
                                <label>
                                    <input type="radio" name="icl_lso_link_empty" value="0" <?php if(!$sitepress_settings['icl_lso_link_empty']):?>checked="checked"<?php endif?> />
                                    <?php echo __('Skip language', 'sitepress') ?>
                                </label>
                            </li>
                            <li>
                            <label>
                                <input type="radio" name="icl_lso_link_empty" value="1" <?php if($sitepress_settings['icl_lso_link_empty']==1):?>checked="checked"<?php endif?> />
                                <?php echo __('Link to home of language for missing translations', 'sitepress') ?>
                            </label>                    
                            </li>
                        </ul>
                    </li>
                </ul>
                <p>
                    <input class="button" name="save" value="<?php echo __('Save') ?>" type="submit" />
                    <span class="icl_ajx_response" id="icl_ajx_response3"></span>
                </p>
                </form>            
            <?php endif; ?>
            </span>
            
            <span id="icl_mo">
            <?php if(count($active_languages) > 1): ?>                            
            <h3><?php echo __('More options', 'sitepress') ?></h3>
            <form id="icl_lang_more_options" name="icl_lang_more_options">        
            <p>
                <label><input type="checkbox" id="icl_language_home" name="icl_language_home" <?php if($sitepress_settings['language_home']): ?>checked="checked"<?php endif; ?> value="1" />
                <?php echo __('Use language specific home pages', 'sitepress') ?></label>
            </p>
            <p>
                <?php echo __('What will be the display order of translated posts, pages and categories?', 'sitepress'); ?>
                <ul>
                    <li><label><input type="radio" name="icl_page_ordering_option" value="1" <?php if($sitepress_settings['page_ordering_option']==1): ?>checked="checked"<?php endif; ?> /> <?php echo __('According to the order of the default language','sitepress') ?></label></li>
                    <li><label><input type="radio" name="icl_page_ordering_option" value="2" <?php if($sitepress_settings['page_ordering_option']==2): ?>checked="checked"<?php endif; ?> /> <?php echo __('According to the order of the original language','sitepress') ?></label></li>
                    <li><label><input type="radio" name="icl_page_ordering_option" value="3" <?php if($sitepress_settings['page_ordering_option']==3): ?>checked="checked"<?php endif; ?> /> <?php echo __('Maintain independent order for each language','sitepress') ?></label></li>
                </ul>
            </p>
            <p>
                <input class="button" name="save" value="<?php echo __('Save') ?>" type="submit" />
                <span class="icl_ajx_response" id="icl_ajx_response_mo"></span>
            </p>
            </form>
            <?php endif; ?>                            
            </span>
            
            <span id="icl_tl">
            <?php if(count($active_languages) > 1): ?>                            
            <h3 style="display:inline"><?php echo __('Theme localization', 'sitepress') ?></h3>&nbsp;<a href="#toggle-theme-localization" style="text-decoration:none"><?php echo __('show','sitepress') ?></a><a href="#toggle-theme-localization" style="display:none;text-decoration:none;"><?php echo __('hide','sitepress') ?></a> <span id="icl_tl_arrow">&darr;</span>
            <form id="icl_theme_localization" name="icl_lang_more_options" method="post">
            <input type="hidden" name="icl_post_action" value="save_theme_localization" />
            <span id="icl_theme_localization_wrap">
            <table id="icl_theme_localization_table" class="widefat" cellspacing="0">
            <thead>
            <tr>
            <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Code', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Locale file name', 'sitepress') ?></th>        
            <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), LANGDIR) ?></th>        
            <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), '/wp-contents/themes/' . get_option('template')) ?></th>        
            </tr>        
            </thead>        
            <tfoot>
            <tr>
            <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Code', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Locale file name', 'sitepress') ?></th>        
            <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), LANGDIR) ?></th>        
            <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), '/wp-contents/themes/' . get_option('template')) ?></th>        
            </tr>        
            </tfoot>
            <tbody>
            <?php foreach($active_languages as $lang): ?>
            <tr>
            <td scope="col"><?php echo $lang['display_name'] ?></td>
            <td scope="col"><?php echo $lang['code'] ?></td>
            <td scope="col">
                <?php if($lang['code']=='en'): ?>
                <?php echo __('default', 'sitepress'); ?>
                <?php else: ?>
                <input type="text" size="10" name="locale_file_name_<?php echo $lang['code']?>" value="<?php echo $locales[$lang['code']]?>" />.mo
                <?php endif; ?>        
            </td> 
            <td>
                <?php if($lang['code']=='en'): echo '&nbsp;'; else: ?> 
                    <?php if(is_readable(ABSPATH . LANGDIR . '/' . $locales[$lang['code']] . '.mo')): ?>
                    <span class="icl_valid_text"><?php echo __('File exists.', 'sitepress') ?></span>                
                    <?php else: ?>
                    <span class="icl_error_text"><?php echo __('File not found!', 'sitepress') ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </td>       
            <td>
                <?php if($lang['code']=='en'): echo '&nbsp;'; else: ?> 
                    <?php if(is_readable(TEMPLATEPATH . '/' . $locales[$lang['code']] . '.mo')): ?>
                    <span class="icl_valid_text"><?php echo __('File exists.', 'sitepress') ?></span>                
                    <?php else: ?>
                    <span class="icl_error_text"><?php echo __('File not found!', 'sitepress') ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </td>               
            </tr>
            <?php endforeach; ?>                                                          
            </tbody>        
            </table>
            </span>
            <p>
                <input class="button" name="save" value="<?php echo __('Save') ?>" type="submit" />
                <span class="icl_ajx_response" id="icl_ajx_response_fn"></span>
            </p>
            
            </form>
            <?php endif; ?>
            <br /><br />
            </span>    
        </span>
    <?php endif; ?>
    
    

    
</div>