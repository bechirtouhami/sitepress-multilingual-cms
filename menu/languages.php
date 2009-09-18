<?php
    if(!is_plugin_active(basename(dirname(dirname(__FILE__))) . "/sitepress.php")){
        ?>
        <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>
        <div class="updated fade">
        <p style="line-height:1.5"><?php echo __('The WPML Multilingual CMS plugin is not currently enabled.', 'sitepress');?></p>
        <p style="line-height:1.5"><?php printf(__('Please go to the <a href="%s">Plugins</a> page and enable the WPML Multilingual CMS plugin before trying to configure the plugin.', 'sitepress'), 'plugins.php');?></p>
        </div>
        <?php
        return;
    }
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    if(!$sitepress_settings['existing_content_language_verified']){
        // try to determine the blog language
        $blog_current_lang = 0;            
        if($blog_lang = get_option('WPLANG')){
            $exp = explode('_',$blog_lang);
            $blog_current_lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='{$exp[0]}'");
        }
        if(!$blog_current_lang && defined('WPLANG') && WPLANG != ''){
            $blog_lang = WPLANG;
            $exp = explode('_',$blog_lang);
            $blog_current_lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='{$exp[0]}'");        
        }
        if(!$blog_current_lang){
            $blog_current_lang = 'en';
        }        
        $languages = $sitepress->get_languages($blog_current_lang);            
    }else{
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
        $inactive_content = $sitepress->get_inactive_content();        
    }    
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>    

    <?php /* setup wizard */ ?>
    <?php 
        if(!$sitepress_settings['existing_content_language_verified']){
            $sw_width = 16;
        }elseif(count($sitepress->get_active_languages()) < 2){
            $sw_width = 50;
        }else{
            $sw_width = 84;
        }
    ?>
    <h3><?php _e('Before you can start using WPML, it needs to be set up', 'sitepress') ?></h3>
    <div id="icl_setup_wizard">
        <div class="icl_setup_wizard_step"><strong><?php _e('1. Language for existing contents', 'sitepress')?></strong></div>
        <div class="icl_setup_wizard_step"><strong><?php _e('2. Select languages', 'sitepress')?></strong></div>
        <div class="icl_setup_wizard_step"><strong><?php _e('3. Add a language switcher', 'sitepress')?></strong></div>            
    </div>        
    <br clear="all" />
    <div id="icl_setup_wizard_progress"><div id="icl_setup_wizard_progress_bar" style="width:<?php echo $sw_width ?>%">&nbsp;</div></div>
    <br />
    <?php /* setup wizard */ ?>
    
    <?php if(!$sitepress_settings['existing_content_language_verified']): ?>
        <h3><?php echo __('Current content language', 'sitepress') ?></h3>    
        <form id="icl_initial_language" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
        <?php wp_nonce_field('icl_initial_language','icl_initial_languagenonce') ?>            
        <p>
            <?php echo __('Before adding other languages, please select the language existing contents are written in:', 'sitepress') ?><br /><br />
            <select name="icl_initial_language_code">
            <?php foreach($languages as $lang):?>
            <option <?php if($blog_current_lang==$lang['code']):?>selected="selected"<?php endif;?> value="<?php echo $lang['code']?>"><?php echo $lang['display_name']?></option>
            <?php endforeach; ?>
            </select>            
            &nbsp;
            <input class="button" name="save" value="<?php echo __('Save', 'sitepress') ?>" type="submit" />
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
                        <li <?php if($is_default):?>class="default_language"<?php endif;?>><label><input name="default_language" type="radio" value="<?php echo $lang['code'] ?>" <?php if($is_default):?>checked="checked"<?php endif;?> /> <?php echo $lang['display_name'] ?> <?php if($is_default):?>(<?php echo __('default', 'sitepress') ?>)<?php endif?></label></li>
                        <?php endforeach ?>
                    </ul>
                    <br clear="all" />
                    <input id="icl_save_default_button" type="button" class="button-secondary action" value="<?php echo __('Save default language', 'sitepress') ?>" />
                    <input id="icl_cancel_default_button" type="button" class="button-secondary action" value="<?php echo __('Cancel', 'sitepress') ?>" />                                    
                    <input id="icl_change_default_button" type="button" class="button-secondary action" value="<?php echo __('Change default language', 'sitepress') ?>" <?php if(count($active_languages) < 2): ?>style="display:none"<?php endif ?> />
                    
                    <input id="icl_add_remove_button" type="button" class="button-secondary action" value="<?php echo __('Add / Remove languages', 'sitepress') ?>" />
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
                        <br clear="all" />
                        <div>
                            <input id="icl_save_language_selection" type="button" class="button-secondary action" value="<?php echo __('Save language selection', 'sitepress') ?>" />
                            <input id="icl_cancel_language_selection" type="button" class="button-secondary action" value="<?php echo __('Cancel', 'sitepress') ?>" />                                
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <?php if(!empty($inactive_content)): ?>
        <?php 
            foreach($inactive_content as $language=>$ic){
                $t_posts += $ic['post'];
                $t_pages += $ic['page'];
                $t_cats += $ic['category'];
                $t_tags += $ic['post_tag'];
            } 
        ?>        
        <h3><?php echo __('Inactive content', 'sitepress') ?></h3>    
        <small><?php echo __('In order to edit or delete these you need to activate the corresponding language first', 'sitepress') ?></small>
        <table id="icl_inactive_content_table" class="widefat" cellspacing="0">
        <thead>
        <tr>
        <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
        <th scope="col"><?php echo __('Posts', 'sitepress') ?></th>
        <th scope="col"><?php echo __('Pages', 'sitepress') ?></th>        
        <th scope="col"><?php echo __('Categories', 'sitepress') ?></th>
        <th scope="col"><?php echo __('Tags', 'sitepress') ?></th>                
        </tr>        
        </thead>
        <tfoot>
        <tr>
        <th scope="col"><?php echo __('Total', 'sitepress') ?></th>
        <td scope="col"><?php echo intval($t_posts) ?></td>
        <td scope="col"><?php echo intval($t_pages) ?></td>
        <td scope="col"><?php echo intval($t_cats) ?></td>
        <td scope="col"><?php echo intval($t_tags) ?></td>
        </tr>        
        </tfoot>                                
        <tbody>        
        <?php foreach($inactive_content as $language=>$ic): ?>
        <tr>
        <th scope="col"><?php echo $language ?></th>
        <td scope="col"><?php echo intval($ic['post']); ?></td>
        <td scope="col"><?php echo intval($ic['page']); ?></td>
        <td scope="col"><?php echo intval($ic['category']); ?></td>
        <td scope="col"><?php echo intval($ic['post_tag']); ?></td>
        </tr>
        <?php endforeach; ?>                                                                  
        </tbody>        
        </table>        
        <?php endif; ?> 
        
        
        <div id="icl_more_languages_wrap">
            <div id="icl_lnt">
            <?php if(count($active_languages) > 1): ?>            
                <h3><?php echo __('Choose how to determine which language visitors see contents in', 'sitepress') ?></h3>    
                <form id="icl_save_language_negotiation_type" name="icl_save_language_negotiation_type" action="">
                <ul>
                    <?php
                    if(!class_exists('WP_Http')){
                       include_once ICL_PLUGIN_PATH . '/lib/http.php';
                    }
                    $client = new WP_Http();
                    if(false === strpos($_POST['url'],'?')){$url_glue='?';}else{$url_glue='&';}                    
                    //set_error_handler('trigger_error');
                    $response = $client->request(get_option('home') . '/' . $sample_lang['code'] .'/' . $url_glue . '____icl_validate_domain=1', array('timeout'=>15, 'decompress'=>false));
                    //restore_error_handler();
                    if(!is_wp_error($response) && ($response['response']['code']=='200') && ($response['body'] == '<!--'.get_option('home').'-->')){
                        $icl_folder_url_disabled = false;
                    }else{
                        $icl_folder_url_disabled = true;
                        if($sitepress_settings['language_negotiation_type']==1){
                            $sitepress_settings['language_negotiation_type']=3;
                            $sitepress->save_settings($sitepress_settings);
                        }                        
                    }                    
                    ?>
                    <li>
                        <label>
                            <input<?php //if($icl_folder_url_disabled) echo ' disabled="disabled'; ?> type="radio" name="icl_language_negotiation_type" value="1" <?php if($sitepress_settings['language_negotiation_type']==1):?>checked="checked"<?php endif?> />                                                    
                            <?php echo sprintf(__('Different languages in directories (%s - %s, %s/%s/ - %s, etc.)', 'sitepress'), get_option('home'), $default_language['display_name'] , get_option('home'), $sample_lang['code'], $sample_lang['display_name'] ) ?>
                        </label>
                        <?php if($icl_folder_url_disabled):?>                                        
                        <div class="icl_error_text" style="margin:10px;">
                            <p>
                                <?php echo __('It looks like languages per directories will not function.', 'sitepress'); ?>
                                <a href="#" onClick="jQuery(this).parent().parent().next().toggle();return false">Details</a>
                            </p>
                        </div>                                                 
                        <div class="icl_error_text" style="display:none;margin:10px;"><?php echo __('
                            <p>This can be a result of either:</p>
                            <ul style="list-style: circle;margin-left:18px">
                            <li>WordPress is installed in a directory (not root) and you\'re using default links.</li>
                            <li>URL rewriting is not enabled in your web server.</li>
                            <li>The web server cannot write to the .htaccess file</li>
                            </ul>
                            <a href="http://wpml.org/support/cannot-activate-language-directories/">How to fix</a>
                            ', 'sitepress')?>                            
                            <p>
                                <?php printf(__('When WPML accesses <a target="_blank" href="%s">%s</a> it gets:', 'sitepress'), $__url = get_option('home').'/' . $sample_lang['code'] .'/?____icl_validate_domain=1', $__url); ?>
                                <br />
                                <?php 
                                    if(is_wp_error($response)){
                                        echo '<strong>';                                            
                                        echo $response->get_error_message();
                                        echo '</strong>';
                                    }elseif($response['response']['code']!='200'){
                                        echo '<strong>';
                                        printf(__('HTTP code: %s (%s)', 'sitepress'), $response['response']['code'], $response['response']['message']);
                                        echo '</strong>';
                                    }else{
                                        echo '<div style="width:100%;height:150px;overflow:auto;background-color:#fff;color:#000;font-family:Courier;font-style:normal;border:1px solid #aaa;">'.htmlentities($response['body']).'</div>';
                                    }
                                ?>
                            </p>
                            <p>
                                <?php printf(__('The expected value is: %s', 'sitepress'), '<br /><strong>&lt;!--'.get_option('home').'--&gt;</strong>'); ?>
                            </p>
                        </div>                            
                        <?php endif; ?>
                    </li>
                    <?php 
                    global $wpmu_version;
                    if(isset($wpmu_version)){
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
                    <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
                    <span class="icl_ajx_response" id="icl_ajx_response2"></span>
                </p>
                </form>            
            <?php endif; ?>
            </div>
            
            <div id="icl_lso">
            <?php if(count($active_languages) > 1):?>            
                <h3><?php echo __('Language switcher options', 'sitepress') ?></h3>    
                <form id="icl_save_language_switcher_options" name="icl_save_language_switcher_options" action="">
                
                <?php _e('Choose where to display the language switcher widget:', 'sitepress') ?>
                <?php 
                global $wp_registered_sidebars; 
                $swidgets = wp_get_sidebars_widgets();
                $sb = '';
                foreach($swidgets as $k=>$v){
                    if(in_array('language-selector', $v)){
                        $sb = $k;
                    }
                }
                ?>                
                <select name="icl_language_switcher_sidebar">
                <?php foreach($wp_registered_sidebars as $rs): ?>                
                <option value="<?php echo $rs['id']?>" <?php if($sb == $rs['id']) echo 'selected="SELECTED"'?>><?php echo $rs['name']?>&nbsp;</option>
                <?php endforeach;?>
                </select>
                
                <p class="icl_form_errors" style="display:none"></p>
                <ul>
                    <li>
                        <p><?php printf(__('The drop-down language switcher can be added to your theme by inserting this PHP code: %s or as a widget','sitepress'),
                        '<code class="php">&lt;?php do_action(\'icl_language_selector\'); ?&gt;</code>'); ?>.</p>
                        <p><?php echo __('You can also create custom language switchers, such as a list of languages or country flags.','sitepress'); ?>
                        <a href="http://wpml.org/home/getting-started-guide/language-setup/custom-language-switcher/"><?php echo __('Custom language switcher creation guide','sitepress')?></a>.
                        </p>
                            
                        
                        <?php /*
                        <label>
                            <input type="checkbox" name="icl_lso_header" value="1" <?php if($sitepress_settings['icl_lso_header']==1):?>checked="checked"<?php endif?> />
                            <?php echo sprintf(__('Include automatically in the header', 'sitepress')) ?>
                        </label>
                        */ ?>
                    </li>
                    <li>
                        <h4><?php echo __('How to handle languages without translation', 'sitepress')?></h4>
                        <p><?php echo __('Some pages or posts may not be translated to all languages. Select how the language selector should behave in case translation is missing.', 'sitepress') ?></p>
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
                    <li>
                        <h4><?php echo __('Language switcher style', 'sitepress')?></h4>
                        <ul>
                            <li>
                                <label>
                                    <input type="checkbox" name="icl_lso_flags" value="1" <?php if($sitepress_settings['icl_lso_flags']):?>checked="checked"<?php endif?> />
                                    <?php echo __('Flag', 'sitepress') ?>
                                </label>
                            </li>
                            <li>
                            <label>
                                <input type="checkbox" name="icl_lso_native_lang" value="1" <?php if($sitepress_settings['icl_lso_native_lang']):?>checked="checked"<?php endif?> />
                                <?php echo __('Native language name (the language name as it\'s written in that language)', 'sitepress') ?>
                            </label>                    
                            </li>
                            <li>
                            <label>
                                <input type="checkbox" name="icl_lso_display_lang" value="1" <?php if($sitepress_settings['icl_lso_display_lang']):?>checked="checked"<?php endif?> />
                                <?php echo __('Language name in display language (the language name as it\'s written in the currently displayed language)', 'sitepress') ?>
                            </label>                    
                            </li>                            
                        </ul>
                    </li>                    
                </ul>
                <p>
                    <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
                    <span class="icl_ajx_response" id="icl_ajx_response3"></span>
                </p>
                </form>            
            <?php endif; ?>
            </div>
            
            <div id="icl_mo">
            <?php if(count($active_languages) > 1): ?>                            
            <h3><?php echo __('More options', 'sitepress') ?></h3>
            <form id="icl_lang_more_options" name="icl_lang_more_options" action="">        
            <p>
                <label><input type="checkbox" id="icl_language_home" name="icl_language_home" <?php if($sitepress_settings['language_home']): ?>checked="checked"<?php endif; ?> value="1" />
                <?php echo __('Use language specific home pages.', 'sitepress') ?></label>
            </p>
            <p>
                <label><input type="checkbox" id="icl_sync_page_ordering" name="icl_sync_page_ordering" <?php if($sitepress_settings['sync_page_ordering']): ?>checked="checked"<?php endif; ?> value="1" />
                <?php echo __('Synchronize page order for translations.', 'sitepress') ?></label>                        
            </p>
            <p>
                <label><input type="checkbox" id="icl_sync_page_parent" name="icl_sync_page_parent" <?php if($sitepress_settings['sync_page_parent']): ?>checked="checked"<?php endif; ?> value="1" />
                <?php echo __('Set page parent for translation according to page parent of the original language.', 'sitepress') ?></label>                        
            </p>
            <?php if(is_admin()): ?>
            <p>
                <label>
                    <?php _e('Default admin language: '); ?>
                    <select name="icl_admin_default_language">
                    <?php foreach($active_languages as $al):?>
                    <option value="<?php echo $al['code'] ?>"<?php if($sitepress_settings['admin_default_language']==$al['code']) echo ' selected="selected"'?>><?php echo $al['display_name'] ?>&nbsp;</option>
                    <?php endforeach; ?>
                    </select>
                </label>
            </p>
            <?php endif; ?>
            <p><?php printf(__('Each user can choose the admin language. You can edit your language preferences by visiting your <a href="%s">profile page</a>.','sitepress'),'profile.php#wpml')?></p>
            <p>
                <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
                <span class="icl_ajx_response" id="icl_ajx_response_mo"></span>
            </p>
            </form>
            <?php endif; ?>                            
            </div>
            

        </div>
    <?php endif; ?>
    
    <?php do_action('icl_menu_footer'); ?>
    
</div>