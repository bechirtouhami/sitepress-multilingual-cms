<p>
<label for="icl_post_language"><?php echo __('Language', 'sitepress') ?></label>
<select name="icl_post_language">
<?php foreach($translations as $t):?>
<?php if($t->element_id && $t->element_id != $post->ID) continue ?>
<option value="<?php echo $t->language_code ?>" <?php if($selected_language==$t->language_code): ?>selected="selected"<?php endif;?>><?php echo $t->display_name ?></option>
<?php endforeach; ?>
</select>
<input type="hidden" name="icl_trid" value="<?php echo $trid ?>" />
</p>

<?php if($trid): ?>
    <p><?php echo __('Translations', 'sitepress') ?></p>
    <table width="100%" border="1">
    <th><?php echo __('English name', 'sitepress') ?></th>
    <th><?php echo __('Native name', 'sitepress') ?></th>
    <th><?php echo __('Operations', 'sitepress') ?></th>
    <?php foreach($translations as $t):?>
    <tr>
        <td><?php echo $t->english_name ?></td>
        <td><?php echo $t->display_name ?></td>
        <td align="right">
            <?php if(!$t->element_id):?>
            <a href="<?php echo get_option('siteurl')?>/wp-admin/<?php echo $post->post_type ?>-new.php?trid=<?php echo $trid ?>"><?php echo __('add','sitepress') ?></a>
            <?php else: ?>
            <a href="<?php echo get_edit_post_link($t->element_id) ?>"><?php echo __('edit','sitepress') ?></a> | <a href="#"><?php echo __('delete','sitepress') ?></a>
            <?php endif; ?>        
        </td>
    </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>