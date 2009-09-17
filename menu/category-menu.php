<?php $this->noscript_notice() ?>

<?php
    if ($referer = $_SERVER['HTTP_REFERER']) {
        if (false !== strpos($referer, 'action=edit')) {
            // we have come from another edit page and we don't want to
            // return there after the "Update"
            $ref = "/wp-admin/categories.php";
            echo '<input type="hidden" name="_wp_original_http_referer" value="' . attribute_escape( stripslashes( $ref ) ) . '" />';
        }
    }
?>
<div id="icl_category_menu" style="display:none">

<div id="dashboard-widgets" class="metabox-holder">
<div class="postbox-container" style="width: 99%;line-height:normal;">

<div id="icl_category_lang" class="postbox" style="line-height:normal;">
    <h3 class="hndle">
        <span><?php echo __('Language', 'sitepress')?></span>
    </h3>                    
    <div class="inside" style="padding: 10px;">

<select name="icl_category_language">
<?php foreach($active_languages as $lang):?>   
<?php if(isset($translations[$lang['code']]->element_id) && $translations[$lang['code']]->element_id != $element_id) continue ?>     
<option value="<?php echo $lang['code'] ?>"<?php if($selected_language==$lang['code']): ?> selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
<?php endforeach; ?>
</select>

<input type="hidden" name="icl_trid" value="<?php echo $trid ?>" />

<?php
function get_category_name($id) {
    global $wpdb;
    $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id = {$id}");
    if ($term_id) {
        return $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = {$term_id}");
    } else {
        return null;
    }
}
?>

<?php if($selected_language != $default_language): ?>
    <br /><br />
    <?php echo __('This is a translation of', 'sitepress') ?><br />
    <select name="icl_translation_of" id="icl_translation_of"<?php if($_GET['action'] != 'edit' && $trid) echo " disabled"?>>
        <?php if($trid): ?>
            <option value="none"><?php echo __('--None--', 'sitepress') ?></option>
            <?php
                //get source
                $src_language_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND language_code='{$default_language}'");
                if($src_language_id) {
                    $src_language_title = get_category_name($src_language_id);
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
                <?php $title = get_category_name($translation_of_id)?>
                <?php if ($title): ?>
                    <option value="<?php echo $translation_of_id ?>"><?php echo $title ?></option>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>

<?php endif; ?>


<?php if($trid && $_GET['action'] == 'edit'): ?>

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
                <td style="padding: 0px;line-height:normal;"><?php echo $lang['display_name'] ?></td>
                <td style="padding: 0px;line-height:normal;"><a href="categories.php?trid=<?php echo $trid ?>&lang=<?php echo $lang['code'] ?>"><?php echo __('add','sitepress') ?></a></td>
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
                <td style="padding: 0px;line-height:normal;"><?php echo $lang['display_name'] ?></td>
                <?php $edit_link = "categories.php?action=edit&amp;cat_ID=" . $translations[$lang['code']]->term_id; ?>
                <td style="padding: 0px;line-height:normal;"><?php echo isset($translations[$lang['code']]->name)?'<a href="'.$edit_link.'" title="'.__('Edit','sitepress').'">'.$translations[$lang['code']]->name.'</a>':__('n/a','sitepress') ?></td>
                        
            <?php endif; ?>        
        </tr>
        <?php endforeach; ?>
        </table>
        
        
        
    <?php endif; ?>
    
    <br clear="all" style="line-height:1px;" />
<?php endif; ?>

</div>
</div>
</div>
</div>
</div>