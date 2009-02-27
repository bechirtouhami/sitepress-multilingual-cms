<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $active_languages = $sitepress->get_active_languages();            
    $languages = $sitepress->get_languages();            
    $sitepress_settings = $sitepress->get_settings();
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
                    <input id="icl_save_default_button" type="button" class="button-secondary action" value="<?php echo __('Save default language', 'sitepress') ?>">
                    <input id="icl_cancel_default_button" type="button" class="button-secondary action" value="<?php echo __('Cancel', 'sitepress') ?>">                
                    <input id="icl_change_default_button" type="button" class="button-secondary action" value="<?php echo __('Change default language', 'sitepress') ?>">
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
        
        <h3><?php echo __('Choose how to determine which language visitors see contents in', 'sitepress') ?></h3>    
        <form id="icl_save_language_negotiation_type">
        <ul>
            <li>
                <label>
                    <input type="radio" name="icl_language_negotiation_type" value="1" <?php if($sitepress_settings['language_negotiation_type']==1):?>checked="checked"<?php endif?> />
                    <?php echo sprintf(__('Different languages in directories (%s - Default language, %s/es - Spanish, etc.)', 'sitepress'), get_option('home'), get_option('home')) ?>
                </label>
            </li>
            <li>
                <label>
                    <input type="radio" name="icl_language_negotiation_type" value="2" <?php if($sitepress_settings['language_negotiation_type']==2):?>checked="checked"<?php endif?> />
                    <?php echo __('A different domain per language', 'sitepress') ?>
                </label>
            </li>
            <li>
                <label>
                    <input type="radio" name="icl_language_negotiation_type" value="3" <?php if($sitepress_settings['language_negotiation_type']==3):?>checked="checked"<?php endif?> />
                    <?php echo sprintf(__('Language name added as a parameter (%s?lang=es - Spanish)', 'sitepress'),get_option('home')) ?>
                </label>
            </li>
        </ul>
        <p>
            <input class="button" name="save" value="<?php echo __('Save') ?>" type="submit" />
            <span class="icl_ajx_response" id="icl_ajx_response2"></span>
        </p>
        </form>
    <?php endif; ?>
    
</div>