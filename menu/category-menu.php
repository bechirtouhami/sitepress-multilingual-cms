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

<?php if($trid): ?>
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
