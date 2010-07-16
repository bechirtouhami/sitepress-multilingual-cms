<?php //included from menu translation-management.php ?>
<?php 
if(isset($_POST['translation_dashboard_filter'])){
    $icl_translation_filter = $_POST['filter'];
}
if(isset($icl_translation_filter['lang'])){
    $selected_language = $icl_translation_filter['lang']; 
}else{
    $selected_language = isset($_GET['lang'])?$_GET['lang']:$default_language;
}
$selected_language_to = $icl_translation_filter['lang_to'] ? $icl_translation_filter['lang_to'] : ''; 

if($selected_language_to == $selected_language){
    $selected_language_to = '';
}


if(isset($icl_translation_filter['tstatus'])){
    $tstatus = $icl_translation_filter['tstatus']; 
}else{
    $tstatus = isset($_GET['tstatus'])?$_GET['tstatus']:'all';
}     
if(isset($icl_translation_filter['status_on'])){
    $status = $icl_translation_filter['status'];
}else{
    if(isset($_GET['status_on']) && isset($_GET['status'])){
        $status = $_GET['status'];
    }else{
        $status = false;
        if(isset($icl_translation_filter)){
            unset($icl_translation_filter['status_on']);
            unset($icl_translation_filter['status']);                
        }
    }
}

if(isset($icl_translation_filter['type_on'])){
    $type = $icl_translation_filter['type'];
}else{
    if(isset($_GET['type_on']) && isset($_GET['type'])){
        $type = $_GET['type'];
    }else{
        $type = false;
        if(isset($icl_translation_filter)){
            unset($icl_translation_filter['type_on']);
            unset($icl_translation_filter['type']);
        }
    }
}   

if(isset($icl_translation_filter['title_on'])){
    $title = $icl_translation_filter['title'];
}else{
    if(isset($_GET['title_on']) && isset($_GET['title'])){
        $title = $_GET['title'];
    }else{
        $title = false;
        if(isset($icl_translation_filter)){
            unset($icl_translation_filter['title_on']);
            unset($icl_translation_filter['title']);                
        }
    }
}

$icl_post_statuses = array(
    'publish'   =>__('Published', 'sitepress'),
    'draft'     =>__('Draft', 'sitepress'),
    'pending'   =>__('Pending Review', 'sitepress'),
    'future'    =>__('Scheduled', 'sitepress')
);    
$icl_post_types = $sitepress->get_translatable_documents();

$icl_dashboard_settings = $sitepress_settings['dashboard'];

$icl_documents = $iclTranslationManagement->get_documents($selected_language, $selected_language_to, $tstatus, $status, $type, $title);

?>

    <form method="post" name="translation-dashboard-filter" action="">
    <table class="form-table widefat fixed">
        <thead>
        <tr>
            <th scope="col"><strong><?php _e('Select which documents to display','sitepress')?></strong></th>
        </tr>
        </thead>        
        <tr valign="top">
            <td>
                <img id="icl_dashboard_ajax_working" align="right" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="display: none;" width="16" height="16" alt="loading..." />
                <label>
                    <strong><?php echo __('Show documents in:', 'sitepress') ?></strong>
                    <select name="filter[lang]">                
                    <!--<option value=""><?php _e('All languages', 'sitepress') ?></option>-->
                    <?php foreach($sitepress->get_active_languages() as $lang): ?>                    
                        <option value="<?php echo $lang['code'] ?>" <?php if($selected_language==$lang['code']): ?>selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                </label>
                &nbsp;
                <label>
                    <strong><?php _e('Translated to:', 'sitepress');?></strong>
                    <select name="filter[lang_to]">                
                    <option value=""><?php _e('All languages', 'sitepress') ?></option>
                    <?php foreach($sitepress->get_active_languages() as $lang): ?>                    
                        <option value="<?php echo $lang['code'] ?>" <?php if($selected_language_to==$lang['code']): ?>selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                </label>
                &nbsp;
                <label>
                    <strong><?php echo __('Translation status:', 'sitepress') ?></strong>
                    <select name="filter[tstatus]">
                        <?php
                            $option_status = array(
                                                   'all' => __('All documents', 'sitepress'),
                                                   'not' => __('Not translated or needs updating', 'sitepress'),
                                                   'in_progress' => __('Translation in progress', 'sitepress'),
                                                   'complete' => __('Translation complete', 'sitepress'));
                        ?>
                        <?php foreach($option_status as $k=>$v):?>
                        <option value="<?php echo $k ?>" <?php if($tstatus==$k):?>selected="selected"<?php endif?>><?php echo $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>                
                <br />
                
                <a href="#hide-advanced-filters" <?php if(!$icl_dashboard_settings['advanced_filters']): ?>style="display: none"<?php endif; ?>><?php _e('Hide advanced filters', 'sitepress'); ?></a>
                <a href="#show-advanced-filters" <?php if($icl_dashboard_settings['advanced_filters']): ?>style="display: none"<?php endif; ?>><?php _e('Show advanced filters', 'sitepress'); ?></a>                
            </td>
        </tr>
        <tr id="icl_dashboard_advanced_filters" valign="top" <?php if(!$icl_dashboard_settings['advanced_filters']): ?>style="display: none;"<?php endif; ?>>
            <td>                
                <strong><?php echo __('Filters:', 'sitepress') ?></strong><br />
                <label><input type="checkbox" name="filter[status_on]" <?php if(isset($icl_translation_filter['status_on'])):?>checked="checked"<?php endif?> />&nbsp;
                    <?php _e('Status:', 'sitepress')?></label> 
                <select name="filter[status]">
                    <?php foreach($icl_post_statuses as $k=>$v):?>
                    <option value="<?php echo $k ?>" <?php if(isset($icl_translation_filter['status_on']) && $icl_translation_filter['status']==$k):?>selected="selected"<?php endif?>><?php echo $v ?></option>
                    <?php endforeach; ?>
                </select>
                <br />
                <label><input type="checkbox" name="filter[type_on]" <?php if(isset($icl_translation_filter['type_on'])):?>checked="checked"<?php endif?> />&nbsp;
                    <?php _e('Type:', 'sitepress')?></label> 
                <select name="filter[type]">
                    <?php foreach($icl_post_types as $k=>$v):?>
                    <option value="<?php echo $k ?>" <?php if(isset($icl_translation_filter['type_on']) && $icl_translation_filter['type']==$k):?>selected="selected"<?php endif?>><?php echo $v->labels->singular_name; ?></option>
                    <?php endforeach; ?>
                </select>                
                <br />
                <label><input type="checkbox" name="filter[title_on]" <?php if(isset($icl_translation_filter['title_on'])):?>checked="checked"<?php endif?> />&nbsp;
                    <?php _e('Title:', 'sitepress')?></label> 
                    <input type="text" name="filter[title]" value="<?php echo $icl_translation_filter['title'] ?>" />
                
            </td>
        </tr>
        <tr>
            <td align="right"><input name="translation_dashboard_filter" class="button" type="submit" value="<?php echo __('Display','sitepress')?>" /></td>
        </tr>
    </table>
    </form>
    
    <br />
    
    <table class="widefat fixed" id="icl-translation-dashboard" cellspacing="0">
        <thead>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" <?php if(isset($_GET['post_id'])) echo 'checked="checked"'?>/></th>
            <th scope="col"><?php echo __('Title', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date">
                <img title="<?php _e('Note for translators', 'sitepress') ?>" src="<?php echo ICL_PLUGIN_URL ?>/res/img/notes.png" alt="note" width="16" height="16" /></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'sitepress') ?></th>        
            <?php if($selected_language_to): ?>
            <th scope="col" class="manage-column column-cb check-column">
                <img src="<?php echo $sitepress->get_flag_url($selected_language_to) ?>" width="16" height="12" alt="<?php echo $selected_language_to ?>" />
                </th>        
            <?php else: ?> 
                <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$selected_language) continue;?>
                <th scope="col" class="manage-column column-cb check-column">
                    <img src="<?php echo $sitepress->get_flag_url($lang['code']) ?>" width="16" height="12" alt="<?php echo $lang['code'] ?>" />
                </th>        
                <?php endforeach; ?>                
            <?php endif; ?>
            
        </tr>        
        </thead>
        <tfoot>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" <?php if(isset($_GET['post_id'])) echo 'checked="checked"'?>/></th>
            <th scope="col"><?php echo __('Title', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date">
                <img title="<?php _e('Note for translators', 'sitepress') ?>" src="<?php echo ICL_PLUGIN_URL ?>/res/img/notes.png" alt="note" width="16" height="16" /></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'sitepress') ?></th>        
            <?php if($selected_language_to): ?>
            <th scope="col" class="manage-column column-cb check-column">
                <img src="<?php echo $sitepress->get_flag_url($selected_language_to) ?>" width="16" height="12" alt="<?php echo $selected_language_to ?>" />
                </th>        
            <?php else: ?> 
                <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$selected_language) continue;?>
                <th scope="col" class="manage-column column-cb check-column">
                    <img src="<?php echo $sitepress->get_flag_url($lang['code']) ?>" width="16" height="12" alt="<?php echo $lang['code'] ?>" />
                </th>        
                <?php endforeach; ?>                
            <?php endif; ?>
        </tr>        
        </tfoot>                    
        <tbody>
            <?php if(!$icl_documents): ?>
            <tr>
                <td scope="col" colspan="<?php 
                    echo 5 + ($selected_language_to ? 1 : count($sitepress->get_active_languages())-1); ?>" align="center"><?php _e('No documents found', 'sitepress') ?></td>
            </tr>                
            <?php else: $oddcolumn = false; ?>
            <?php foreach($icl_documents as $doc): $oddcolumn=!$oddcolumn; ?>
            <tr<?php if($oddcolumn): ?> class="alternate"<?php endif;?>>
                <td scope="col">
                    <input type="checkbox" value="<?php echo $doc->post_id ?>" name="post[]" <?php if(isset($_GET['post_id'])) echo 'checked="checked"'?> />                    
                </td>
                <td scope="col" class="post-title column-title">
                    <a href="<?php echo get_edit_post_link($doc->post_id) ?>"><?php echo $doc->post_title ?></a>
                    <?php
                        $wc = icl_estimate_word_count($doc, $selected_language);
                        $wc += icl_estimate_custom_field_word_count($doc->post_id, $selected_language);
                    ?>
                    <span id="icl-cw-<?php echo $doc->post_id ?>" style="display:none"><?php echo $wc; $wctotal+=$wc; ?></span>
                    <span class="icl-tr-details">&nbsp;</span>
                    <div class="icl_post_note" id="icl_post_note_<?php echo $doc->post_id ?>">
                        <?php 
                            if($wpdb->get_var("SELECT source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_type='post_{$doc->post_type}' AND element_id={$doc->post_id}")){
                                $_is_translation = true;
                            }else{
                                $_is_translation = false;
                                $note = get_post_meta($doc->post_id, '_icl_translator_note', true); 
                                if($note){
                                    $note_text = __('Edit note for the translators', 'sitepress');
                                    $note_icon = 'edit_translation.png';
                                }else{
                                    $note_text = __('Add note for the translators', 'sitepress');
                                    $note_icon = 'add_translation.png';
                                }
                            }
                        ?>
                        <?php _e('Note for the translators', 'sitepress')?> 
                        <textarea rows="5"><?php echo $note ?></textarea> 
                        <table width="100%"><tr>
                        <td style="border-bottom:none">
                            <input type="button" class="icl_tn_clear button" 
                                value="<?php _e('Clear', 'sitepress')?>" <?php if(!$note): ?>disabled="disabled"<?php endif; ?> />        
                            <input class="icl_tn_post_id" type="hidden" value="<?php echo $doc->post_id ?>" />
                        </td>
                        <td align="right" style="border-bottom:none"><input type="button" class="icl_tn_save button-primary" value="<?php _e('Save', 'sitepress')?>" /></td>
                        </tr></table>
                    </div>
                </td>
                <td scope="col" class="icl_tn_link" id="icl_tn_link_<?php echo $doc->post_id ?>">
                    <?php if($_is_translation):?>
                    &nbsp;
                    <?php else: ?>
                    <a title="<?php echo $note_text ?>" href="#"><img src="<?php echo ICL_PLUGIN_URL ?>/res/img/<?php echo $note_icon ?>" width="16" height="16" /></a>
                    <?php endif; ?>
                </td>
                <td scope="col">
                    <?php echo $icl_post_types[$doc->post_type]->labels->singular_name; ?>
                    <input class="icl_td_post_type" name="icl_post_type[<?php echo $doc->post_id ?>]" type="hidden" value="<?php echo $doc->post_type ?>" />
                </td>
                <td scope="col"><?php echo $icl_post_statuses[$doc->post_status]; ?></td>
                <?php if($selected_language_to): ?>
                <td scope="col" class="manage-column column-cb check-column">
                    <img style="margin-top:4px;" 
                        src="<?php echo ICL_PLUGIN_URL ?>/res/img/<?php echo $_st = TranslationManagement::status2img_filename($doc->language_status[$selected_language_to])?>" 
                        width="16" height="16" alt="<?php echo $_st ?>" />
                    </td>        
                <?php else: ?> 
                    <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$selected_language) continue;?>
                    <td scope="col" class="manage-column column-cb check-column">
                        <img style="margin-top:4px;" 
                            src="<?php echo ICL_PLUGIN_URL ?>/res/img/<?php echo $_st = TranslationManagement::status2img_filename($doc->language_status[$lang['code']])?>" 
                            width="16" height="16" alt="<?php echo $st ?>" />
                    </td>        
                    <?php endforeach; ?>                
                <?php endif; ?>
                
                
            </tr>                            
            <?php endforeach;?>
            <?php endif;?>
        </tbody> 
    </table>    