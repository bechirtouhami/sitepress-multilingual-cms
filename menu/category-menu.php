<?php $this->noscript_notice() ?>
<div id="icl_category_menu" style="display:none">
<?php echo __('Language', 'sitepress') ?>
<select name="icl_category_language">
<?php foreach($active_languages as $lang):?>   
<?php if(isset($translations[$lang['code']]->element_id) && $translations[$lang['code']]->element_id != $element_id) continue ?>     
<option value="<?php echo $lang['code'] ?>"<?php if($this_lang==$lang['code']): ?> selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
<?php endforeach; ?>
</select>

<input type="hidden" name="icl_trid" value="<?php echo $trid ?>" />

<?php if($this_lang != $default_language): ?>
    <br />
    <?php echo __('This is a translation of', 'sitepress') ?>&nbsp;
    <select name="icl_translation_of" id="icl_translation_of">
        <?php if($trid): ?>
            <option value="none"><?php echo __('--None--', 'sitepress') ?></option>
            <?php
                //get source
                $src_language_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND language_code='{$default_language}'");
                if($src_language_id) {
                    $src_language_title = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = {$src_language_id}");
                }
            ?>
            <?php if($src_language_title): ?>
                <option value="<?php echo $src_language_id ?>" selected="selected"><?php echo $src_language_title ?></option>
            <?php endif; ?>
        <?php else: ?>
            <option value="none" selected="selected"><?php echo __('--None--', 'sitepress') ?></option>
        <?php endif; ?>
        <?php foreach($untranslated_ids as $translation_of_id):?>
            <?php if ($translation_of_id != $src_language_id): ?>
                <option value="<?php echo $translation_of_id ?>"><?php echo $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = {$translation_of_id}") ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>

<?php endif; ?>


<?php if($trid): ?>

    <?php
        // count number of translated and un-translated pages.
        $translations_found = 0;
        $untranslated_found = 0;
        foreach($active_languages as $lang) {
            if($selected_language==$lang['code']) continue;
            if(isset($translations[$lang['code']]->element_id)) {
                $translations_found += 1;
            } else {
                $untranslated_found += 1;
            }
        }
    ?>
    
    <?php if($untranslated_found > 0): ?>    
        <p style="clear:both;"><b>Translate</b>
        <table>
        <?php foreach($active_languages as $lang): if($selected_language==$lang['code']) continue; ?>
        <tr>
            <?php if(!isset($translations[$lang['code']]->element_id)):?>
                <td><?php echo $lang['display_name'] ?></td>
                <td><a href="categories.php?trid=<?php echo $trid ?>&lang=<?php echo $lang['code'] ?>"><?php echo __('add','sitepress') ?></a></td>
            <?php endif; ?>        
        </tr>
        <?php endforeach; ?>
        </table>
        </p>
    <?php endif; ?>

    <?php if($translations_found > 0): ?>    
        <p style="clear:both;"><b><?php echo __('Translations', 'sitepress') ?></b> (<a href="javascript:;" 
            onclick="jQuery('#icl_translations_table').toggle();if(jQuery(this).html()=='<?php echo __('hide','sitepress')?>') jQuery(this).html('<?php echo __('show','sitepress')?>'); else jQuery(this).html('<?php echo __('hide','sitepress')?>')"><?php echo __('show','sitepress')?></a>)</p>
        <table width="100%" id="icl_translations_table" style="display:none;">
        
        <?php foreach($active_languages as $lang): if($selected_language==$lang['code']) continue; ?>
        <tr>
            <?php if(isset($translations[$lang['code']]->element_id)):?>
                <td><?php echo $lang['display_name'] ?></td>
                <td><?php echo isset($translations[$lang['code']]->name)?'<a href="'.$edit_link.'" title="'.__('Edit','sitepress').'">'.$translations[$lang['code']]->name.'</a>':__('n/a','sitepress') ?></td>
                        
            <?php endif; ?>        
        </tr>
        <?php endforeach; ?>
        </table>
        
        
        
    <?php endif; ?>
    
    <br clear="all" style="line-height:1px;" />
<?php endif; ?>

<?php if($trid && false): ?>
    <p><?php echo __('Translations', 'sitepress') ?> (<a href="javascript:;" 
        onclick="jQuery('#icl_translations_table').toggle();if(jQuery(this).html()=='<?php echo __('hide','sitepress')?>') jQuery(this).html('<?php echo __('show','sitepress')?>'); else jQuery(this).html('<?php echo __('hide','sitepress')?>')"><?php echo __('show','sitepress')?></a>)</p>
    <table width="100%" id="icl_translations_table" style="display:none">
    <th align="left"><?php echo __('Language', 'sitepress') ?></th>
    <th align="left"><?php echo __('Name', 'sitepress') ?></th>
    <th align="right"><?php echo __('Operations', 'sitepress') ?></th>
    <?php foreach($active_languages as $lang): if($this_lang==$lang['code']) continue;?>
    <tr>
        <td><?php echo $lang['display_name'] ?></td>
        <?php $edit_link = "categories.php?action=edit&amp;cat_ID=" . $translations[$lang['code']]->term_id; ?>
        <td><?php echo isset($translations[$lang['code']]->name)?'<a href="'.$edit_link.'" title="'.__('Edit','sitepress').'">'.$translations[$lang['code']]->name.'</a>':__('n/a','sitepress') ?></td>
        <td align="right">
            <?php if(!isset($translations[$lang['code']]->element_id)):?>            
            <a href="categories.php?trid=<?php echo $trid ?>&lang=<?php echo $lang['code'] ?>"><?php echo __('add','sitepress') ?></a>
            <?php else: ?>            
            <a href="<?php echo get_category_link($translations[$lang['code']]->term_id) ?>" target="_blank"><?php echo __('View','sitepress') ?></a>
            <?php endif; ?>        
        </td>
    </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>

</div>
