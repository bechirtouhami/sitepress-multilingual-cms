<?php 
$icl_string_translations = icl_get_string_translations();
$active_languages = $sitepress->get_active_languages();            
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('General String Translation', 'sitepress') ?></h2>    
    
    <table id="icl_string_translations" class="widefat" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?php echo __('Context', 'sitepress') ?></th>
                <th scope="col"><?php echo __('Name', 'sitepress') ?></th>
                <th scope="col"><?php echo __('String', 'sitepress') ?></th>        
            </tr>        
        </thead>        
        <tfoot>
            <tr>
                <th scope="col"><?php echo __('Context', 'sitepress') ?></th>
                <th scope="col"><?php echo __('Name', 'sitepress') ?></th>
                <th scope="col"><?php echo __('String', 'sitepress') ?></th>        
            </tr>        
        </tfoot>                
        <tbody>
            <?php if(empty($icl_string_translations)):?> 
            <tr>
                <td colspan="3"><?php echo __('No string registered', 'sitepress')?></td>
            </tr>
            <?php else: ?>
            <?php foreach($icl_string_translations as $string_id=>$icl_string): ?> 
            <tr>
                <td><?php echo htmlentities($icl_string['context']); ?></td>
                <td><?php echo htmlentities($icl_string['name']); ?></td>
                <td width="70%">                    
                    <div class="icl-st-original">
                    <?php echo htmlentities($icl_string['value']); ?>                    
                    </div>
                    <a href="#icl-st-toggle-translations" style="float:right"><?php echo __('translations','sitepress') ?></a>
                    <div class="icl-st-inline">                        
                        <?php foreach($active_languages as $lang): if($lang['code'] == $sitepress->get_current_language()) continue;  ?>
                        <form class="icl_st_form">
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
                                        if(isset($icl_string['translations'][$lang['code']])) echo $icl_string['translations'][$lang['code']]['value']; 
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
            </tr>            
            <?php endforeach;?>
            <?php endif; ?>
        </tbody>
    </table>      
    
    
</div>