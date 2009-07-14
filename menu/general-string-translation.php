<?php 
if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
    return;
}
$icl_string_translations = icl_get_string_translations();
$active_languages = $sitepress->get_active_languages();            


?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('String translation', 'sitepress') ?></h2>    
    
    <h4 style="margin-bottom:0"><?php echo __('Filter by translation status', 'sitepress')?></h4>
    <p>
    <?php echo __('Select which strings to display:', 'sitepress')?>
    <select name="icl_st_filter">
        <option value="-1" <?php if($sitepress_settings['st']['filter']==-1):?>selected="selected"<?php endif;?>><?php echo __('All strings', 'sitepress') ?></option>
        <option value="<?php echo ICL_STRING_TRANSLATION_NOT_TRANSLATED ?>" <?php if($sitepress_settings['st']['filter']===ICL_STRING_TRANSLATION_NOT_TRANSLATED):?>selected="selected"<?php endif;?>><?php echo $icl_st_string_translation_statuses[ICL_STRING_TRANSLATION_NOT_TRANSLATED] ?></option>
        <option value="<?php echo ICL_STRING_TRANSLATION_COMPLETE ?>" <?php if($sitepress_settings['st']['filter']===ICL_STRING_TRANSLATION_COMPLETE):?>selected="selected"<?php endif;?>><?php echo $icl_st_string_translation_statuses[ICL_STRING_TRANSLATION_COMPLETE] ?></option>
        <option value="<?php echo ICL_STRING_TRANSLATION_NEEDS_UPDATE ?>" <?php if($sitepress_settings['st']['filter']===ICL_STRING_TRANSLATION_NEEDS_UPDATE):?>selected="selected"<?php endif;?>><?php echo $icl_st_string_translation_statuses[ICL_STRING_TRANSLATION_NEEDS_UPDATE] ?></option>
        <option value="<?php echo ICL_STRING_TRANSLATION_PARTIAL ?>" <?php if($sitepress_settings['st']['filter']===ICL_STRING_TRANSLATION_PARTIAL):?>selected="selected"<?php endif;?>><?php echo $icl_st_string_translation_statuses[ICL_STRING_TRANSLATION_PARTIAL] ?></option>
    </select>
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
                        <form class="icl_st_form" name="icl_st_form_<?php echo $string_id ?>">
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
                                    <img class="icl_ajx_loader" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="float:left;display:none;position:absolute;margin:5px" />
                                    <textarea name="icl_st_translation" style="width:100%" <?php if(isset($icl_string['translations'][$lang['code']])): ?>id="icl_st_ta_<?php echo $icl_string['translations'][$lang['code']]['id'] ?>"<?php endif;?>><?php 
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
                <?php echo $icl_st_string_translation_statuses[$icl_string['status']] ?>    
                </td>
            </tr>            
            <?php endforeach;?>
            <?php endif; ?>
        </tbody>
    </table>      
    
    <span class="subsubsub"><input type="button" class="button primary" id="icl_st_delete_selected" value="<?php echo __('Delete selected strings', 'sitepress') ?>" /><span style="display:none"><?php echo __("Are you sure you want to delete these strings?\nTheir translations will be deleted too.",'sitepress') ?></span></span>
    
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
    <div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'sitepress' ) . '</span>%s',
        number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
        number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
        number_format_i18n( $wp_query->found_posts ),
        $page_links
    ); echo $page_links_text; ?></div>
    <?php } ?>            
    </div>
    </div>
    
    <p>
    <h4 style="margin-bottom:0"><?php echo __('Site wide texts', 'sitepress')?></h4>
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
    </p>
    <br />    
    
</div>