<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $active_languages = $sitepress->get_active_languages();            
    $languages = $sitepress->get_languages();            
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup SitePress', 'sitepress') ?></h2>    
    
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
    
</div>