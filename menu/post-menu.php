<?php $this->noscript_notice() ?>
<p style="float:left;">
<?php echo __('Language', 'sitepress') ?>
<select name="icl_post_language">
<?php foreach($active_languages as $lang):?>
<?php if(isset($translations[$lang['code']]->element_id) && $translations[$lang['code']]->element_id != $post->ID) continue ?>
<option value="<?php echo $lang['code'] ?>" <?php if($selected_language==$lang['code']): ?>selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
<?php endforeach; ?>
</select>

<input type="hidden" name="icl_trid" value="<?php echo $trid ?>" />
</p>
<div style="clear:both;font-size:1px">&nbsp;</div>

<?php 
    do_action('icl_post_languages_options_before', $post->ID);
    if($this->get_icl_translation_enabled()){
        icl_display_post_translation_status($post->ID, &$post_translation_statuses);
    }
?>

<?php if($trid): ?>
    <p style="clear:both;"><?php echo __('Translations', 'sitepress') ?> (<a href="javascript:;" 
        onclick="jQuery('#icl_translations_table').toggle();if(jQuery(this).html()=='<?php echo __('hide','sitepress')?>') jQuery(this).html('<?php echo __('show','sitepress')?>'); else jQuery(this).html('<?php echo __('hide','sitepress')?>')"><?php echo __('show','sitepress')?></a>)</p>
    <table width="100%" id="icl_translations_table" style="display:none;">
    <tr>
        <th align="left"><?php echo __('Language', 'sitepress') ?></th>
        <th align="left"><?php echo __('Title', 'sitepress') ?></th>
        <th align="right"><?php echo __('Operations', 'sitepress') ?></th>
        <?php if($this->get_icl_translation_enabled()):?>
        <th align="right"><?php echo __('ICanLocalize translation', 'sitepress') ?></th>
        <?php endif; ?>
    </tr>
    
    <?php foreach($active_languages as $lang): if($selected_language==$lang['code']) continue; ?>
    <tr>
        <td><?php echo $lang['display_name'] ?></td>
        <td><?php echo isset($translations[$lang['code']]->post_title)?'<a href="'.get_edit_post_link($translations[$lang['code']]->element_id).'" title="'.__('Edit','sitepress').'">'.apply_filters('the_title', $translations[$lang['code']]->post_title?$translations[$lang['code']]->post_title:__('(no title)','sitepress')).'</a>':__('n/a','sitepress') ?></td>
        <td align="right">
            <?php if(!isset($translations[$lang['code']]->element_id)):?>
            <a href="<?php echo get_option('siteurl')?>/wp-admin/<?php echo $post->post_type ?>-new.php?trid=<?php echo $trid ?>&lang=<?php echo $lang['code'] ?>"><?php echo __('add','sitepress') ?></a>
            <?php else: ?>
            <a href="<?php echo get_permalink($translations[$lang['code']]->element_id) ?>" target="_blank"><?php echo __('View','sitepress') ?></a>
            <?php endif; ?>        
        </td>
        <?php if($this->get_icl_translation_enabled()):?>
        <td align="right">
        <?php echo isset($post_translation_statuses[$lang['code']]) ? $post_translation_statuses[$lang['code']] : __('Not translated','sitepress'); ?>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </table>
    <br clear="all" style="line-height:1px;" />
<?php endif; ?>

<?php do_action('icl_post_languages_options_after') ?>