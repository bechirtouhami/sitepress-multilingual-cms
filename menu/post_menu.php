<p>
<label for="icl_post_language"><?php echo __('Language', 'sitepress') ?></label>
<select name="icl_post_language">
<?php foreach($active_languages as $lang):?>
<option value="<?php echo $lang['code'] ?>" <?php if($selected_language==$lang['code']): ?>selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
<?php endforeach; ?>
</select>
<input type="hidden" name="icl_trid" value="<?php echo $language_details->trid ?>" />
</p>