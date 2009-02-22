<p>
<label for="icl_post_language"><?php echo __('Language', 'sitepress') ?></label>
<select name="icl_post_language">
<?php foreach($translations as $t):?>
<?php if($t->element_id && $t->element_id != $post->ID) continue ?>
<option value="<?php echo $t->code ?>" <?php if($selected_language==$t->code): ?>selected="selected"<?php endif;?>><?php echo $t->display_name ?></option>
<?php endforeach; ?>
</select>
<input type="hidden" name="icl_trid" value="<?php echo $trid ?>" />
</p>

<?php if($trid): ?>
    <p><?php echo __('Translations', 'sitepress') ?> (<a href="javascript:;" 
        onclick="jQuery('#icl_translations_table').toggle();if(jQuery(this).html()=='<?php echo __('hide','sitepress')?>') jQuery(this).html('<?php echo __('show','sitepress')?>'); else jQuery(this).html('<?php echo __('hide','sitepress')?>')"><?php echo __('show','sitepress')?></a>)</p>
    <table width="100%" id="icl_translations_table" style="display:none">
    <th align="left"><?php echo __('Language', 'sitepress') ?></th>
    <th align="left"><?php echo __('Title', 'sitepress') ?></th>
    <th align="right"><?php echo __('Operations', 'sitepress') ?></th>
    <?php foreach($translations as $t):?>
    <tr>
        <td><?php echo $t->display_name ?></td>
        <td><?php echo $t->post_title?'<a href="'.get_permalink($t->element_id).'" title="View">'.$t->post_title.'</a>':__('n/a','sitepress') ?></td>
        <td align="right">
            <?php if(!$t->element_id):?>
            <a href="<?php echo get_option('siteurl')?>/wp-admin/<?php echo $post->post_type ?>-new.php?trid=<?php echo $trid ?>&lang=<?php echo $t->code ?>"><?php echo __('add','sitepress') ?></a>
            <?php else: ?>
            <a href="<?php echo get_edit_post_link($t->element_id) ?>"><?php echo __('edit','sitepress') ?></a> | <a href="<?php echo wp_nonce_url("post.php?action=delete&amp;post={$t->element_id}", 'delete-post_' . $t->element_id) ?>"><?php echo __('delete','sitepress') ?></a>
            <?php endif; ?>        
        </td>
    </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>