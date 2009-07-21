<?php 
if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
    return;
}
$icl_string_translations = icl_get_string_translations();
$active_languages = $sitepress->get_active_languages();            
$icl_contexts = icl_st_get_contexts();

$status_filter = isset($_GET['status']) ? intval($_GET['status']) : false;
$context_filter = isset($_GET['context']) ? $_GET['context'] : false;

$icl_st_translation_enabled = $sitepress->icl_account_configured() && $sitepress->get_icl_translation_enabled();

?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('String translation', 'sitepress') ?></h2>    
    
    <?php if(isset($icl_st_po_strings) && !empty($icl_st_po_strings)): ?>
    
        <p><?php printf(__('These are the strings that we found in your .po file. Please carefully review them. Then, click on the \'add\' or \'cancel\' buttons at the <a href="%s">bottom of this screen</a>. You can exclude individual strings by clearing the check boxes next to them.', 'sitepress'), '#add_po_strings_confirm'); ?></p>
        <form method="post" action="">
        <input type="hidden" name="icl_st_strings_for" value="<?php echo $_POST['icl_st_strings_for'] ?>" />
        <?php if(isset($_POST['icl_st_po_language'])): ?>
        <input type="hidden" name="icl_st_po_language" value="<?php echo $_POST['icl_st_po_language'] ?>" />
        <?php endif; ?>
        <table id="icl_po_strings" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" checked="checked" name="" /></th>
                    <th><?php echo __('String', 'sitepress') ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" checked="checked" name="" /></th>
                    <th><?php echo __('String', 'sitepress') ?></th>
                </tr>
            </tfoot>        
            <tbody>
                <?php $k = -1; foreach($icl_st_po_strings as $str): $k++; ?>
                    <tr>
                        <td><input class="icl_st_row_cb" type="checkbox" name="icl_strings_selected[]" checked="checked" value="<?php echo $k ?>" /></td>
                        <td>
                            <input type="text" name="icl_strings[]" value="<?php echo htmlentities($str['string']) ?>" readonly="readonly" style="width:100%;" size="100" />
                            <?php if(isset($_POST['icl_st_po_language'])): ?>
                            <input type="text" name="icl_translations[]" value="<?php echo htmlentities($str['translation']) ?>" readonly="readonly" style="width:100%;" size="100" />
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
            
        <a name="add_po_strings_confirm"></a>
        <p class="alignright"></p>
        <p class="aligleft"><input class="button" type="button" value="<?php echo __('Cancel', 'sitepress'); ?>" onclick="location.href='admin.php?page=<?php echo $_GET['page'] ?>'" />
        &nbsp; <input class="button-primary" type="submit" name="icl_st_save_strings" value="<?php echo __('Add selected strings', 'sitepress'); ?>" />
        </p>
        </form>
    <?php else: ?>
    
        <p>
        <?php echo __('Select which strings to display:', 'sitepress')?>
        <select name="icl_st_filter_status">
            <option value="" <?php if($status_filter === false ):?>selected="selected"<?php endif;?>><?php echo __('All strings', 'sitepress') ?></option>        
            <option value="<?php echo ICL_STRING_TRANSLATION_COMPLETE ?>" <?php if($status_filter === ICL_STRING_TRANSLATION_COMPLETE):?>selected="selected"<?php endif;?>><?php echo $icl_st_string_translation_statuses[ICL_STRING_TRANSLATION_COMPLETE] ?></option>
            <option value="<?php echo ICL_STRING_TRANSLATION_NOT_TRANSLATED ?>" <?php if($status_filter === ICL_STRING_TRANSLATION_NOT_TRANSLATED):?>selected="selected"<?php endif;?>><?php echo __('Translation needed', 'sitepress') ?></option>
        </select>
        
        <?php if(!empty($icl_contexts)): ?>
        <?php echo __('Select strings within context:', 'sitepress')?>
        <select name="icl_st_filter_context">
            <option value="" <?php if($context_filter === false ):?>selected="selected"<?php endif;?>><?php echo __('All contexts', 'sitepress') ?></option>
            <?php foreach($icl_contexts as $v):?>
            <option value="<?php echo htmlentities($v->context)?>" <?php if($context_filter == $v->context ):?>selected="selected"<?php endif;?>><?php echo $v->context . ' ('.$v->c.')'; ?></option>
            <?php endforeach; ?>
        </select>    
        <?php endif; ?>
        
        </p>
    
        <table id="icl_string_translations" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
                    <th scope="col"><?php echo __('Context', 'sitepress') ?></th>
                    <th scope="col"><?php echo __('Name', 'sitepress') ?></th>
                    <th scope="col"><?php echo __('String', 'sitepress') ?></th>        
                    <th scope="col"><?php echo __('Status', 'sitepress') ?></th>
                </tr>        
            </thead>        
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
                    <th scope="col"><?php echo __('Context', 'sitepress') ?></th>
                    <th scope="col"><?php echo __('Name', 'sitepress') ?></th>
                    <th scope="col"><?php echo __('String', 'sitepress') ?></th>
                    <th scope="col"><?php echo __('Status', 'sitepress') ?></th>        
                </tr>        
            </tfoot>                
            <tbody>
                <?php if(empty($icl_string_translations)):?> 
                <tr>
                    <td colspan="5" align="center"><?php echo __('No strings found', 'sitepress')?></td>
                </tr>
                <?php else: ?>
                <?php foreach($icl_string_translations as $string_id=>$icl_string): ?> 
                <tr valign="top">
                    <td><input class="icl_st_row_cb" type="checkbox" value="<?php echo $string_id ?>" /></td>
                    <td><?php echo htmlentities($icl_string['context']); ?></td>
                    <td><?php echo htmlentities($icl_string['name']); ?></td>
                    <td width="70%">                                        
                        <div class="icl-st-original" style="float:left;">                    
                        <?php echo $icl_string['value']; ?>                    
                        </div>                    
                        <div style="float:right;">
                            <a href="#icl-st-toggle-translations"><?php echo __('translations','sitepress') ?></a>
                        </div>
                        <br clear="all" />
                        <div class="icl-st-inline">                        
                            <?php foreach($active_languages as $lang): if($lang['code'] == $sitepress->get_current_language()) continue;  ?>
                            <form class="icl_st_form" name="icl_st_form_<?php echo $string_id ?>" action="">
                            <input type="hidden" name="icl_st_language" value="<?php echo $lang['code'] ?>" />                        
                            <input type="hidden" name="icl_st_string_id" value="<?php echo $string_id ?>" />                        
                            <table class="icl-st-table">
                                <?php                                
                                    if(isset($icl_string['translations'][$lang['code']]) && $icl_string['translations'][$lang['code']]['status'] == ICL_STRING_TRANSLATION_COMPLETE){
                                        $tr_complete_checked = 'checked="checked"';
                                    }else{
                                        $tr_complete_checked = '';
                                    }
                                ?>
                                <tr>
                                    <td style="border:none">
                                        <?php echo $lang['display_name'] ?><br />
                                        <img class="icl_ajx_loader" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="float:left;display:none;position:absolute;margin:5px" alt="" />
                                        <textarea rows="2" cols="40" name="icl_st_translation" style="width:100%" <?php if(isset($icl_string['translations'][$lang['code']])): ?>id="icl_st_ta_<?php echo $icl_string['translations'][$lang['code']]['id'] ?>"<?php endif;?>><?php 
                                            if(isset($icl_string['translations'][$lang['code']])) echo $icl_string['translations'][$lang['code']]['value']; else echo $icl_string['value']; 
                                            ?></textarea>                                        
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right" style="border:none">                                    
                                        <label><input type="checkbox" name="icl_st_translation_complete" value="1" <?php echo $tr_complete_checked ?> <?php if(isset($icl_string['translations'][$lang['code']])): ?>id="icl_st_cb_<?php echo $icl_string['translations'][$lang['code']]['id'] ?>"<?php endif;?> /> <?php echo __('Translation is complete','sitepress')?></label>&nbsp;
                                        <input type="submit" class="button-secondary action" value="<?php echo __('Save', 'sitepress')?>" />
                                    </td>
                                </tr>
                                </table>
                                </form>
                                <?php endforeach;?>
                        </div>
                    </td>
                    <td nowrap="nowrap" id="icl_st_string_status_<?php echo $string_id ?>">
                    <?php
                        $icl_status = icl_translation_get_string_translation_status($string_id);
                        if ($icl_status != "") {
                            $icl_status = __(' - ICanLocalize ', 'sitepress').$icl_status;
                        }
                        echo $icl_st_string_translation_statuses[$icl_string['status']].$icl_status;
                    ?>    
                    </td>
                </tr>            
                <?php endforeach;?>
                <?php endif; ?>
            </tbody>
        </table>      
        
        <span class="subsubsub">
            <input type="button" class="button-secondary" id="icl_st_delete_selected" value="<?php echo __('Delete selected strings', 'sitepress') ?>" disabled="disabled" />
            <span style="display:none"><?php echo __("Are you sure you want to delete these strings?\nTheir translations will be deleted too.",'sitepress') ?></span>
        </span>
        <br clear="all" />
            
        <?php if($wp_query->found_posts > 10): ?>
            <div class="tablenav">
            <?php    
            $page_links = paginate_links( array(
                'base' => add_query_arg('paged', '%#%' ),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $wp_query->max_num_pages,
                'current' => $_GET['paged'],
                'add_args' => isset($icl_translation_filter)?$icl_translation_filter:array() 
            ));         
            ?>
            <?php if ( $page_links ) { ?>
            <div class="tablenav-pages">
                <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'sitepress' ) . '</span>%s',
                    number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
                    number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
                    number_format_i18n( $wp_query->found_posts ),
                    $page_links
                    ); echo $page_links_text; 
                ?>
            </div>
            <?php } ?>            
            </div>
        <?php endif; ?>    
    
        <?php if($icl_st_translation_enabled): ?>
            <h4><?php echo __('Translation options', 'sitepress') ?></h4>            
            <ul id="icl-tr-opt">
                <?php
                    $icl_lang_status = $sitepress_settings['icl_lang_status'];
                    if (isset($icl_lang_status)){
                        foreach($icl_lang_status as $lang){
                            if($lang['from'] == $sitepress->get_current_language()) {
                                $target_status[$lang['to']] = $lang['have_translators'];
                            }
                        }
                    }
                ?>
                <?php foreach($active_languages as $lang): if($sitepress->get_current_language()==$lang['code']) continue; ?>
                    <?php if($sitepress_settings['language_pairs'] && isset($sitepress_settings['language_pairs'][$sitepress->get_current_language()][$lang['code']])): ?>
                        <?php if(isset($target_status[$lang['code']]) && $target_status[$lang['code']] == 1): ?>
                            <li><label><input type="checkbox" name="icl-tr-to-<?php echo $lang['code']?>" value="<?php echo $lang['english_name']?>" checked="checked" />&nbsp;<?php printf(__('Translate to %s','sitepress'), $lang['display_name']); ?></label></li>
                        <?php else:  ?>
                            <li><label><input type="checkbox" name="icl-tr-to-<?php echo $lang['code']?>" value="<?php echo $lang['english_name']?>" disabled="disabled" />&nbsp;<?php printf(__('Translate to %s','sitepress'), $lang['display_name'] . __(' - No translators assigned yet in ICanLocalize', 'sitepress')); ?></label></li>
                        <?php endif; ?>
                    <?php else:  ?>
                        <li><label><input type="checkbox" name="icl-tr-to-<?php echo $lang['code']?>" value="<?php echo $lang['english_name']?>" disabled="disabled" />&nbsp;<?php printf(__('Translate to %s','sitepress'), $lang['display_name'] . __(' - This language has not been selected for translation by ICanLocalize', 'sitepress')); ?></label></li>
                    <?php endif; ?>
                <?php endforeach; ?>    
            </ul>  

            <span class="subsubsub">
                <input type="button" class="button-secondary" id="icl_st_send_selected" value="<?php echo __('Send selected strings to ICanLocalize', 'sitepress') ?>" disabled="disabled" />                    
                <input type="button" class="button-primary" id="icl_st_send_need_translation" value="<?php echo __('Send all strings that need update to ICanLocalize', 'sitepress') ?>" />                     
            </span><br />
            <span id="icl_st_send_progress" class="icl_ajx_response" style="display:none;float:left;"><?php echo __('Sending translation requests. Please wait!', 'sitepress') ?>&nbsp;<img src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" alt="loading" /></span>
    
            <?php if(isset($sitepress_settings['icl_balance'])): ?>
            <br clear="all" />
            <p>
                <?php echo sprintf(__('Your balance with ICanLocalize is %s. Visit your %sICanLocalize finance%s page to deposit additional funds.'),
                                      '$'.$sitepress_settings['icl_balance'],
                                      '<a href="'.ICL_API_ENDPOINT.ICL_FINANCE_LINK.'">',
                                      '</a>',
                                      'sitepress')?>
            </p>
            <?php endif; ?>
    
        <?php else: ?>
    
            <div class="error">
            <p><?php printf(__('To send documents to translation, you first need to <a href="%s">set up content translation</a>.' , 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/content-translation.php'); ?></p>
            </div>
    
        <?php endif; ?>    
    
        <div class="colthree">
            <h4><?php echo __('Translate general settings texts', 'sitepress')?></h4>
            <p><?php echo __('WPML can translate texts entered in different admin screens. Select which texts to translate.', 'sitepress')?></p>
            <form id="icl_st_sw_form" name="icl_st_sw_form" method="post" action="">
                <p class="icl_form_errors" style="display:none"></p>
                <ul>
                    <li><label><input type="checkbox" name="icl_st_sw[blog_title]" value="1" <?php if($sitepress_settings['st']['sw']['blog_title']): ?>checked="checked"<?php endif ?> /> 
                        <?php echo __('Blog Title', 'sitepress'); ?></label></li>
                    <li><label><input type="checkbox" name="icl_st_sw[tagline]" value="1" <?php if($sitepress_settings['st']['sw']['tagline']): ?>checked="checked"<?php endif ?> /> 
                        <?php echo __('Tagline', 'sitepress'); ?></label></li>
                    <li><label><input type="checkbox" name="icl_st_sw[widget_titles]" value="1" <?php if($sitepress_settings['st']['sw']['widget_titles']): ?>checked="checked"<?php endif ?> /> 
                        <?php echo __('Widget titles', 'sitepress'); ?></label></li>
                    <li><label><input type="checkbox" name="icl_st_sw[text_widgets]" value="1" <?php if($sitepress_settings['st']['sw']['text_widgets']): ?>checked="checked"<?php endif ?> /> 
                        <?php echo __('Content for text-widgets', 'sitepress'); ?></label></li>
                </ul>
                <input class="button-secondary" type="submit" name="iclt_st_sw_save" value="<?php echo __('Save', 'sitepress')?>" />
                <span class="icl_ajx_response" style="display:inline"><?php if(isset($_GET['updated']) && $_GET['updated']=='true') echo __('Settings saved', 'sitepress') ?></span>
            </form>
        </div>
    
        <? /*
        <div class="colthree">
            <h4><?php echo __('Translate the theme or plugins', 'sitepress') ?></h4>
            <p><?php echo __("You can translate the theme's texts using this screen too. To do this, upload the theme's .po file.", 'sitepress')?></p>
            <form id="icl_st_po_form"  name="icl_st_po_form" method="post" enctype="multipart/form-data">
                <p>
                    <?php  echo __('Select what the strings are for: ', 'sitepress'); ?>
                    <select name="icl_st_strings_for">
                    <option value="theme"><?php echo __('Theme','sitepress')?></option>
                    <option value="plugin"><?php echo __('Plugin','sitepress')?></option>
                    </select>
                </p>
                <?php echo __('.po file:', 'sitepress')?>
                <input class="button primary" type="file" name="icl_po_file" />  
                <p style="line-height:2.3em">
                    <input type="checkbox" id="icl_st_po_translations" />
                    <?php echo __('Also create translations according to the .po file', 'sitepress')?>
                    <select name="icl_st_po_language" id="icl_st_po_language" style="display:none">
                    <?php foreach($active_languages as $al): if($al['code']==$sitepress->get_default_language()) continue; ?>
                    <option value="<?php echo $al['code'] ?>"><?php echo $al['display_name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                </p>
                <input class="button" name="icl_po_upload" id="icl_po_upload" type="submit" value="<?php echo __('Submit', 'sitepress')?>" />        
            </form>
        </div>
        */ ?>
        <br clear="all" /><br />
    
    <?php endif; ?>
    
    <?php do_action('icl_menu_footer'); ?>
    
</div>