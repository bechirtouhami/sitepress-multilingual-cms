<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery('.icl-quote-get-next').click(function(){
            jQuery(this).parent().parent().fadeOut('fast', function(){
                jQuery(this).next('div').fadeIn();
            });
        });
        jQuery('.icl-quote-get-back').click(function(){
            jQuery(this).parent().parent().fadeOut('fast', function(){
                jQuery(this).prev('div').fadeIn();
            });
        });
//        jQuery('.icl-quote-get-check-data').bind('keydown focus blur', function(){
//            var enable = true;
//            jQuery('.icl-quote-get-check-empty').each(function(){
//                if (jQuery(this).val().length < 1) {
//                    enable = false;
//                }
//            });
//            jQuery('.icl-quote-get-check-mail').each(function(){
//                if (/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i.test(jQuery(this).val()) == false){
//                    enable = false;
//                }
//            });
//            if (enable) {
//                jQuery('#icl-quote-get-submit-produce').attr('disabled', 0);
//            } else {
//                jQuery('#icl-quote-get-submit-produce').attr('disabled', 1);
//            }
//        });
        iclQuoteGetCheckContentCb();
        jQuery('#icl-quote-get-form').ajaxForm({target:'#icl-quote-get-wrap'});
    });
    function iclQuoteGetSetSelectLangs() {
        if (jQuery('#icl-quote-get-from').val() == 0) {
            jQuery('.icl-quote-get-toggle-to').fadeOut();
            jQuery('.icl-quote-get-to').attr('checked', 0);
            iclQuoteGetCheckFromTo();
            return false;
        }
        jQuery('.icl-quote-get-toggle-to').fadeIn();
        jQuery('#icl-quote-get-to-'+jQuery('#icl-quote-get-from').val()).attr('checked', 0).parent().hide(0,
        function(){
            iclQuoteGetCheckFromTo();
        }
    );
        jQuery('.icl-quote-get-to').not('#icl-quote-get-to-'+jQuery('#icl-quote-get-from').val()).parent().show();
    }
    function iclQuoteGetCheckFromTo() {
        var enable = false;
        jQuery('.icl-quote-get-to').each(function(){
            if (jQuery(this).is(':checked')) {
                enable = true;
            }
        });
        if (jQuery('#icl-quote-get-from').val() == 0) {
            enable = false;
        }
        if (enable) {
            jQuery('#icl-quote-next-1').attr('disabled', 0);
        } else {
            jQuery('#icl-quote-next-1').attr('disabled', 1);
        }
    }
    function iclQuoteGetCheckContentCb() {
        var enable = false;
        jQuery('.icl-quote-get-content-checbox').each(function(){
            if (jQuery(this).is(':checked')) {
                enable = true;
            }
        });
        if (enable) {
            jQuery('#icl-quote-next-2').attr('disabled', 0);
        } else {
            jQuery('#icl-quote-next-2').attr('disabled', 1);
        }
    }
</script>

<?php

function icl_quote_get_step_one($saved) {
    global $sitepress, $sitepress_settings;
    $active_languages = $sitepress->get_active_languages();

?>
    <input type="hidden" name="step" value="1" />
    <p>
        <label><?php _e('I need translation from', 'sitepress'); ?>
            <select id="icl-quote-get-from" name="from" onchange="iclQuoteGetSetSelectLangs();">
                <option value="0"><?php _e('Select Language', 'sitepress'); ?>&nbsp;</option>
            <?php
            foreach ($active_languages as $code => $lang) {
                $selected = '';
//                $selected = $saved['from'] == $code ? ' selected="selected"' : '';

            ?>
                <option value="<?php echo $code; ?>"<?php echo $selected; ?>><?php echo $lang['native_name']; ?></option>
            <?php
            }

            ?>
        </select>
    </label>
</p>
<p>
    <?php
            foreach ($active_languages as $code => $lang) {
                $selected = is_array($saved['to']) && in_array($code, $saved['to']) ? ' checked="checked"' : '';

    ?>
            <div class="icl-quote-get-toggle-to" style="display:none;">
                <label><input type="checkbox" name="to[<?php echo $code; ?>]" onclick="iclQuoteGetCheckFromTo();" value="<?php echo $code; ?>" class="icl-quote-get-to" id="icl-quote-get-to-<?php echo $code; ?>"<?php echo $selected; ?> />&nbsp;<?php echo $lang['native_name']; ?><br /></label>
                </div>
<?php
            }

?>
            </p>
            <p>
                <input type="submit" id="icl-quote-next-1" value="<?php _e('Continue', 'sitepress'); ?>" name="next" disabled="disabled" class="button-secondary icl-quote-get-next" />
            </p>
<?php
        }

        function icl_quote_get_step_two($saved) {
            global $sitepress, $iclTranslationManagement, $wpdb;
            $iclTranslationManagement->init();
            $cf_settings = $iclTranslationManagement->settings['custom_fields_translation'];
//            echo '<pre>'; print_r($iclTranslationManagement->settings['custom_fields_translation']);
            $rows = array();
            $add = 0;
            $types = get_post_types('', 'objects');
//            echo '<pre>'; print_r($types);
            foreach ($types as $name => $type) {
                if (in_array($name, array('attachment', 'revision', 'nav_menu_item'))) {
                    continue;
                }
                $source_code = $saved['from'] == $sitepress->get_default_language() ? 'IS NULL' : "= '" . $saved['from'] . "'";
                $posts = $wpdb->get_results("SELECT p.ID, p.post_title, p.post_content
                    FROM {$wpdb->prefix}posts p
                    JOIN {$wpdb->prefix}icl_translations t
                    WHERE p.post_type = '$name'
                    AND t.element_type = 'post_$name'
                    AND t.element_id = p.ID
                    AND t.language_code = '" . $saved['from'] . "'
                    AND p.post_status = 'publish'
                ");
//                echo '<pre>';print_r($posts);
                $rows[$name]['ID'] = $name;
                $rows[$name]['title'] = $type->label;
                if (empty($posts)) {
                    $rows[$name]['words'] = 0;
                    $rows[$name]['num'] = 0;
                    continue;
                }
                foreach ($posts as $post) {
                    $meta_count = 0;
                    foreach ($cf_settings as $meta_key => $translate) {
                        if ($translate == 2) {
                            $meta = get_post_meta($post->ID, $meta_key, true);
                            if (is_string($meta)) {
                                $meta_count = str_word_count(strip_tags(
                                        get_post_meta($post->ID, $meta_key,
                                                true)));
                            }
                        }
                    }
                    $add = $meta + str_word_count(strip_tags($post->post_title)) + str_word_count(strip_tags($post->post_content));
                    $rows[$name]['words'] += $add;
                }
                $rows[$name]['num'] = count($posts);
            }
?>
            <input type="hidden" name="step" value="2" />
            <table border="0" cellpadding="5" cellspacing="15" class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th></th>
                        <th><?php _e('Type', 'sitepress'); ?></th>
                        <th><?php _e('Number of items', 'sitepress'); ?></th>
                        <th><?php _e('Number of words', 'sitepress'); ?></th>
            </tr>
        </thead>
        <tbody>
<?php
            foreach ($rows as $type => $data) {
                $selected = is_array($saved['content']) && array_key_exists($data['ID'], $saved['content']) ? ' checked="checked"' : '';

?>
                <tr>
                    <td>
                        <input type="checkbox" name="content[<?php echo $data['ID']; ?>]" value="1" class="icl-quote-get-content-checbox" onclick="iclQuoteGetCheckContentCb();"<?php echo $selected; ?> />
                        <input type="hidden" name="description[<?php echo $data['ID']; ?>][title]" value="<?php echo $data['title']; ?>" />
                        <input type="hidden" name="description[<?php echo $data['ID']; ?>][num]" value="<?php echo $data['num']; ?>" />
                        <input type="hidden" name="description[<?php echo $data['ID']; ?>][words]" value="<?php echo $data['words']; ?>" />
                    </td>
                    <td><?php echo $data['title']; ?></td>
                    <td><?php echo $data['num']; ?></td>
                    <td><?php echo $data['words']; ?></td>
                </tr>
<?php
            }

?>
            </tbody>
        </table>
        <p style="width: 280px;">
<?php printf(__('Note: to get the word count of specific documents, use the %sTranslation Dashboard%s.',
                            'sitepress'), '<a href="admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translation-management.php">', '</a>'); ?>
        </p>
        <p>
            <input type="submit" id="icl-quote-back-1" value="<?php _e('Back', 'sitepress'); ?>" name="back" class="button-secondary icl-quote-get-back" />
                <input type="submit" id="icl-quote-next-2" value="<?php _e('Continue', 'sitepress'); ?>" name="next" disabled="disabled" class="button-secondary icl-quote-get-next" />
            </p>
<?php
        }

        function icl_quote_get_step_three($saved) {
            if ($saved['content']) {
                $wc_description = array();
                foreach ($saved['content'] as $ID => $true) {
                    $wc_description[] = $saved['description'][$ID]['num'] . ' '
                            . $saved['description'][$ID]['title'] . ' with '
                            . $saved['description'][$ID]['words'] . ' words';
                }
                echo implode(', ', $wc_description);
?>
            <input type="hidden" name="step" value="3" />
<!--            <table border="0" cellspacing="5" cellpadding="10">
                <tbody>
                    <tr>
                        <td style="text-align: right;"><?php _e('First name', 'sitepress'); ?></td>
                        <td><input type="text" name="first_name" value="" class="icl-quote-get-check-data icl-quote-get-check-empty" /></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><?php _e('Last name', 'sitepress'); ?></td>
                        <td><input type="text" name="last_name" value="" class="icl-quote-get-check-data icl-quote-get-check-empty" /></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><?php _e('E-mail', 'sitepress'); ?></td>
                        <td><input type="text" name="mail" value="" class="icl-quote-get-check-data icl-quote-get-check-mail" /></td>
                    </tr>
                </tbody>
            </table>-->
            <p>
                <input type="submit" id="icl-quote-back-2" value="<?php _e('Back', 'sitepress'); ?>" name="back" class="button-secondary icl-quote-get-back" />
                <input type="submit" value="<?php _e('Produce Quote', 'sitepress'); ?>" name="submit-produce" id="icl-quote-get-submit-produce" class="button-primary" />
                <input type="submit" value="<?php _e('Save for later', 'sitepress'); ?>" name="submit-for-later" id="icl-quote-get-submit-for-later" class="button-secondary" />
            </p>
<?php
            }
        }

?>

<div id="icl-quote-get-wrap">
    <form id="icl-quote-get-form" action="" method="post">
        <input type="hidden" name="icl_ajx_action" value="quote-get-submit" />
        <?php wp_nonce_field('quote-get-submit'); ?>
        <?php
        global $sitepress, $sitepress_settings;
        $continue = FALSE;
        $saved = array();

        if (isset($sitepress_settings['quote-get'])) {
            if ($sitepress_settings['quote-get']['step'] == 3) {
                $continue = TRUE;
            }
            $saved = $sitepress_settings['quote-get'];
        }

        if (isset($data['back'])) {
            $data['step'] -= 1;
        } else if (isset($data['next'])) {
            $data['step'] += 1;
        }

        if ($continue && (!isset($data['next']) && !isset($data['back']))) {
            icl_quote_get_step_three($saved);
        } else if (!isset($data['step']) || $data['step'] == 1) {
            if (isset($data['back'])) {
                $saved['content'] = $data['content'];
                $saved['description'] = $data['description'];
            }
            $saved['step'] = 1;
            $sitepress->save_settings(array('quote-get' => $saved));
            icl_quote_get_step_one($saved);
        } else if ($data['step'] == 2) {
            if (isset($data['next'])) {
                $saved['from'] = $data['from'];
                $saved['to'] = $data['to'];
            }
            $saved['step'] = 2;
            $sitepress->save_settings(array('quote-get' => $saved));
            icl_quote_get_step_two($saved);
        } else if ($data['step'] == 3) {
            if (isset($data['next'])) {
                $saved['content'] = $data['content'];
                $saved['description'] = $data['description'];
            }
            $saved['step'] = 3;
            $sitepress->save_settings(array('quote-get' => $saved));
            icl_quote_get_step_three($saved);
        }
        ?>
    </form>
</div>