<?php //included from menu translation-management.php ?>
<?php 

$ICL_Pro_Translation->send_post(232, array('zh-hans'), 'en');

if(isset($_SESSION['translation_dashboard_filter'])){
    $icl_translation_filter = $_SESSION['translation_dashboard_filter'];
}

if(!isset($icl_translation_filter['from_lang'])){
    $icl_translation_filter['from_lang'] = isset($_GET['lang'])?$_GET['lang']:$sitepress->get_default_language();
}

if(!isset($icl_translation_filter['to_lang'])){
    $icl_translation_filter['to_lang'] = isset($_GET['to_lang'])?$_GET['to_lang']:'';
}

if($icl_translation_filter['to_lang'] == $icl_translation_filter['from_lang']){
   $icl_translation_filter['to_lang'] = false; 
}

if(!isset($icl_translation_filter['tstatus'])){
    $icl_translation_filter['tstatus'] = isset($_GET['tstatus'])?$_GET['tstatus']:'not';
}
     

if(!isset($icl_translation_filter['status_on']) || !$icl_translation_filter['status_on']){
    $icl_translation_filter['status_on'] = isset($_GET['status_on']) ? $_GET['status_on'] : false;
    if(!$icl_translation_filter['status_on']){
        unset($icl_translation_filter['status']);        
    }
}

if(!isset($icl_translation_filter['type_on']) || !$icl_translation_filter['type_on']){
    $icl_translation_filter['type_on'] = isset($_GET['type_on']) ? $_GET['type_on'] : false;
    if(!$icl_translation_filter['type_on']){
        unset($icl_translation_filter['type']);        
    }
}

if(!isset($icl_translation_filter['title_on']) || !$icl_translation_filter['title_on']){
    $icl_translation_filter['title_on'] = isset($_GET['title_on']) ? $_GET['title_on'] : false;
    if(!$icl_translation_filter['title_on']){
        unset($icl_translation_filter['title']);        
    }
}


if(!isset($icl_translation_filter['sort_by']) || !$icl_translation_filter['sort_by']){ $icl_translation_filter['sort_by'] = 'p.post_date';}
if(!isset($icl_translation_filter['sort_order']) || !$icl_translation_filter['sort_order']){ $icl_translation_filter['sort_order'] = 'DESC';}
$sort_order_next = $icl_translation_filter['sort_order'] == 'ASC' ? 'DESC' : 'ASC'; 
$title_sort_link = 'admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/translation-management.php&sm=dashboard&icl_tm_action=sort&sort_by=p.post_title&sort_order='.$sort_order_next;
$date_sort_link = 'admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/translation-management.php&sm=dashboard&icl_tm_action=sort&sort_by=p.post_date&sort_order='.$sort_order_next;

$icl_post_statuses = array(
    'publish'   =>__('Published', 'sitepress'),
    'draft'     =>__('Draft', 'sitepress'),
    'pending'   =>__('Pending Review', 'sitepress'),
    'future'    =>__('Scheduled', 'sitepress')
);    
$icl_post_types = $sitepress->get_translatable_documents();

$icl_dashboard_settings = $sitepress_settings['dashboard'];

$icl_translation_filter['limit_no'] = 20;
$icl_documents = $iclTranslationManagement->get_documents($icl_translation_filter);
$icl_translators = $iclTranslationManagement->get_blog_translators();

if(!empty($iclTranslationManagement->dashboard_select)){
    $icl_selected_posts = (array)$iclTranslationManagement->dashboard_select['post'];
    $icl_selected_languages = (array)$iclTranslationManagement->dashboard_select['translate_to'];
    $icl_selected_translators = (array)$iclTranslationManagement->dashboard_select['translator'];
}
?>


    
    <form method="post" name="translation-dashboard-filter" action="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&amp;sm=dashboard">
    <input type="hidden" name="icl_tm_action" value="dashboard_filter" />
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
                    <select name="filter[from_lang]">                
                    <!--<option value=""><?php _e('All languages', 'sitepress') ?></option>-->
                    <?php foreach($sitepress->get_active_languages() as $lang): ?>                    
                        <option value="<?php echo $lang['code'] ?>" <?php if($icl_translation_filter['from_lang']==$lang['code']): ?>selected="selected"<?php endif;?>>
                            <?php echo $lang['display_name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                </label>
                &nbsp;
                <label>
                    <strong><?php _e('Translated to:', 'sitepress');?></strong>
                    <select name="filter[to_lang]">                
                    <option value=""><?php _e('All languages', 'sitepress') ?></option>
                    <?php foreach($sitepress->get_active_languages() as $lang): ?>                    
                        <option value="<?php echo $lang['code'] ?>" <?php if($icl_translation_filter['to_lang']==$lang['code']): ?>selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
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
                        <option value="<?php echo $k ?>" <?php if($icl_translation_filter['tstatus']==$k):?>selected="selected"<?php endif?>><?php echo $v ?></option>
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
                <label><input type="checkbox" name="filter[status_on]" <?php if($icl_translation_filter['status_on']):?>checked="checked"<?php endif?> />&nbsp;
                    <?php _e('Status:', 'sitepress')?></label> 
                <select name="filter[status]">
                    <?php foreach($icl_post_statuses as $k=>$v):?>
                    <option value="<?php echo $k ?>" <?php if(isset($icl_translation_filter['status_on']) && $icl_translation_filter['status']==$k):?>selected="selected"<?php endif?>><?php echo $v ?></option>
                    <?php endforeach; ?>
                </select>
                <br />
                <label><input type="checkbox" name="filter[type_on]" <?php if($icl_translation_filter['type_on']):?>checked="checked"<?php endif?> />&nbsp;
                    <?php _e('Type:', 'sitepress')?></label> 
                <select name="filter[type]">
                    <?php foreach($icl_post_types as $k=>$v):?>
                    <option value="<?php echo $k ?>" <?php if(isset($icl_translation_filter['type_on']) && $icl_translation_filter['type']==$k):?>selected="selected"<?php endif?>><?php echo $v->labels->singular_name; ?></option>
                    <?php endforeach; ?>
                </select>                
                <br />
                <label><input type="checkbox" name="filter[title_on]" <?php if($icl_translation_filter['title_on']):?>checked="checked"<?php endif?> />&nbsp;
                    <?php _e('Title:', 'sitepress')?></label> 
                    <input type="text" name="filter[title]" value="<?php echo $icl_translation_filter['title'] ?>" />
            </td>
        </tr>
        <tr>
            <td align="right">
                <a class="submitdelete deletion" href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&sm=dashboard&icl_tm_action=reset_filters"><?php _e('Reset filters', '&nbsp;'); ?></a>&nbsp;
                <input name="translation_dashboard_filter" class="button" type="submit" value="<?php echo __('Display','sitepress')?>" />
            </td>
        </tr>
    </table>
    </form>
    
    <br />
    
    <form method="post">
    <input type="hidden" name="icl_tm_action" value="send_jobs" />
    <input type="hidden" name="translate_from" value="<?php echo $icl_translation_filter['from_lang'] ?>" />
    <table class="widefat fixed" id="icl-tm-translation-dashboard" cellspacing="0">
        <thead>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" <?php if(isset($_GET['post_id'])) echo 'checked="checked"'?>/></th>
            <th scope="col"><a href="<?php echo $title_sort_link ?>"><?php echo __('Title', 'sitepress') ?>&nbsp;
                <?php if($icl_translation_filter['sort_by']=='p.post_title') echo $icl_translation_filter['sort_order']=='ASC' ? '&uarr;' : '&darr;' ?></a></th>
            <th scope="col" class="manage-column column-date"><a href="<?php echo $date_sort_link ?>"><?php echo __('Date', 'sitepress') ?>&nbsp;
                <?php if($icl_translation_filter['sort_by']=='p.post_date') echo $icl_translation_filter['sort_order']=='ASC' ? '&uarr;' : '&darr;' ?></a></th>
            <th scope="col" class="manage-column column-date">
                <img title="<?php _e('Note for translators', 'sitepress') ?>" src="<?php echo ICL_PLUGIN_URL ?>/res/img/notes.png" alt="note" width="16" height="16" /></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'sitepress') ?></th>        
            <?php if($icl_translation_filter['to_lang']): ?>
            <th scope="col" class="manage-column column-cb check-column">
                <img src="<?php echo $sitepress->get_flag_url($icl_translation_filter['to_lang']) ?>" width="16" height="12" alt="<?php echo $icl_translation_filter['to_lang'] ?>" />
                </th>        
            <?php else: ?> 
                <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$icl_translation_filter['from_lang']) continue;?>
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
            <th scope="col"><a href="<?php echo $title_sort_link ?>"><?php echo __('Title', 'sitepress') ?>&nbsp;
                <?php if($icl_translation_filter['sort_by']=='p.post_title') echo $icl_translation_filter['sort_order']=='ASC' ? '&uarr;' : '&darr;' ?></a></th>
            <th scope="col" class="manage-column column-date"><a href="<?php echo $date_sort_link ?>"><?php echo __('Date', 'sitepress') ?>&nbsp;
                <?php if($icl_translation_filter['sort_by']=='p.post_date') echo $icl_translation_filter['sort_order']=='ASC' ? '&uarr;' : '&darr;' ?></a></th>
            <th scope="col" class="manage-column column-date">
                <img title="<?php _e('Note for translators', 'sitepress') ?>" src="<?php echo ICL_PLUGIN_URL ?>/res/img/notes.png" alt="note" width="16" height="16" /></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'sitepress') ?></th>        
            <?php if($icl_translation_filter['to_lang']): ?>
            <th scope="col" class="manage-column column-cb check-column">
                <img src="<?php echo $sitepress->get_flag_url($icl_translation_filter['to_lang']) ?>" width="16" height="12" alt="<?php echo $icl_translation_filter['to_lang'] ?>" />
                </th>        
            <?php else: ?> 
                <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$icl_translation_filter['from_lang']) continue;?>
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
                    echo 6 + ($icl_translation_filter['to_lang'] ? 1 : count($sitepress->get_active_languages())-1); ?>" align="center"><?php _e('No documents found', 'sitepress') ?></td>
            </tr>                
            <?php else: $oddcolumn = false; ?>
            <?php foreach($icl_documents as $doc): $oddcolumn=!$oddcolumn; ?>
            <tr<?php if($oddcolumn): ?> class="alternate"<?php endif;?>>
                <td scope="col">
                    <input type="checkbox" value="<?php echo $doc->post_id ?>" name="post[]" <?php 
                        if(isset($_GET['post_id']) || (is_array($icl_selected_posts) && in_array($doc->post_id, $icl_selected_posts))) echo 'checked="checked"'?> />                    
                </td>
                <td scope="col" class="post-title column-title">
                    <a href="<?php echo get_edit_post_link($doc->post_id) ?>"><?php echo $doc->post_title ?></a>
                    <?php
                        $wc = $iclTranslationManagement->estimate_word_count($doc, $icl_translation_filter['from_lang']);
                        $wc += $iclTranslationManagement->estimate_custom_field_word_count($doc->post_id, $icl_translation_filter['from_lang']);
                    ?>
                    <span id="icl-cw-<?php echo $doc->post_id ?>" style="display:none"><?php echo $wc; $wctotal+=$wc; ?></span>
                    <span class="icl-tr-details">&nbsp;</span>
                    <div class="icl_post_note" id="icl_post_note_<?php echo $doc->post_id ?>">
                        <?php 
                            if(!$doc->is_translation){
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
                <td scope="col" class="post-date column-date">
                    <?php if($doc->post_date) echo date('Y-m-d', strtotime($doc->post_date)); ?>
                </td>
                <td scope="col" class="icl_tn_link" id="icl_tn_link_<?php echo $doc->post_id ?>">
                    <?php if($doc->is_translation):?>
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
                <?php if($icl_translation_filter['to_lang']): ?>
                <?php $docst = $doc->needs_update ? ICL_TM_NEEDS_UPDATE : intval($doc->status); ?>
                <td scope="col" class="manage-column column-cb check-column">
                    <img style="margin-top:4px;" 
                        src="<?php echo ICL_PLUGIN_URL ?>/res/img/<?php echo $_st = TranslationManagement::status2img_filename($docst)?>" 
                        width="16" height="16" alt="<?php echo $_st ?>" />
                    </td>        
                <?php else: ?> 
                    <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$icl_translation_filter['from_lang']) continue;?>
                    <?php 
                        $_suffix = str_replace('-','_',$lang['code']);                        
                        $_prop_up = 'needs_update_'.$_suffix;
                        $_prop_st = 'status_'.$_suffix;
                        switch(intval($doc->$_prop_st)){
                            case ICL_TM_NOT_TRANSLATED : $tst_title = esc_attr(__('Not translated','sitepress')); break;
                            case ICL_TM_WAITING_FOR_TRANSLATOR : $tst_title = esc_attr(__('Waiting for translator','sitepress')); break;
                            case ICL_TM_IN_PROGRESS : $tst_title = esc_attr(__('In progress','sitepress')); break;
                            case ICL_TM_COMPLETE : $tst_title = esc_attr(__('Complete','sitepress')); break;
                            default: $tst_title = '';
                        }
                        $docst = ($doc->$_prop_up && $icl_translation_filter['tstatus']=='not') ? ICL_TM_NEEDS_UPDATE : intval($doc->$_prop_st); 
                        if($doc->$_prop_up){
                            $tst_title .= ' - ' . esc_attr(__('needs update','sitepress'));    
                        }
                          
                    ?>
                    <td scope="col" class="manage-column column-cb check-column">
                        <img style="margin-top:4px;" title="<?php echo $tst_title ?>"
                            src="<?php echo ICL_PLUGIN_URL ?>/res/img/<?php echo $_st = TranslationManagement::status2img_filename($docst)?>" 
                            width="16" height="16" alt="<?php echo $st ?>" />
                    </td>        
                    <?php endforeach; ?>                
                <?php endif; ?>
                
                
            </tr>                            
            <?php endforeach;?>
            <?php endif;?>
        </tbody> 
    </table>    
    
    
    <?php 
    // pagination  
    $page_links = paginate_links( array(
        'base' => add_query_arg('paged', '%#%' ),
        'format' => '',
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'total' => $wp_query->max_num_pages,
        'current' => $_GET['paged'],
        'add_args' => isset($icl_translation_filter)?$icl_translation_filter:array() 
    ));         
    ?> 
    <span id="icl-cw-total" style="display:none"><?php echo $wctotal; ?></span>       
    <div class="tablenav">    
        <div style="float:left;margin-top:4px;">
            <strong><?php echo __('Word count estimate:', 'sitepress') ?></strong> <?php printf(__('%s words', 'sitepress'), '<span id="icl-tm-estimated-words-count">0</span>')?>
        </div>    
        <?php if ( $page_links ) { ?>
        <div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'sitepress' ) . '</span>%s',
            number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
            number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
            number_format_i18n( $wp_query->found_posts ),
            $page_links
        ); echo $page_links_text; ?>
        </div>
        <?php } ?>
    </div>    
    <?php // pagination - end ?>
    

    <table class="widefat fixed" cellspacing="0" style="width:100%">
        <thead>
            <tr>
                <th><?php _e('Translation options', 'sitepress')?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <ul id="icl_tm_languages">
                    <?php foreach($sitepress->get_active_languages()as $lang):?>
                    <?php 
                        if($lang['code'] == $icl_translation_filter['from_lang']) continue;
                    ?>
                    <li>
                        <label><input type="checkbox" name="translate_to[<?php echo $lang['code'] ?>]" value="1" 
                            <?php if(isset($icl_selected_languages[$lang['code']])):?>checked="checked"<?php endif;?> />
                            &nbsp;<?php printf(__('Translate to %s', 'sitepress'),$lang['display_name'])?></label>
                        - <label><?php _e('Use translator', 'sitepress')?>
                        <?php $iclTranslationManagement->translators_dropdown(array(
                                        'from'          => $icl_translation_filter['from_lang'],
                                        'to'            => $lang['code'],
                                        'name'          => 'translator['.$lang['code'].']',
                                        'default_name'  => __('First available (Local)', 'sitepress'),
                                        'selected'      =>  $icl_selected_translators[$lang['code']]
                                        )); 
                        ?>                        
                        </label>
                        &nbsp;<a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&sm=translators"><?php _e('Manage translators', 'sitepress'); ?></a>
                    </li>
                    <?php endforeach; ?>
                    </ul>
                    <input id="icl_tm_jobs_submit" class="button-primary" type="submit" value="<?php _e('Translate documents', 'sitepress') ?>" 
                        <?php if(empty($icl_selected_languages) && empty($icl_selected_posts)):?>disabled="disabled" <?php endif; ?> />
                </td>
            </tr>
        </tbody>
    </table>
    
    </form>