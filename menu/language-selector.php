<div id="lang_sel">
    <ul>
        <li><a href="#" class="lang_sel_sel"><?php echo $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$w_this_lang['code']}' AND display_language_code='{$w_this_lang['code']}'"); ?><?php if(!isset($ie_ver) || $ie_ver > 6): ?></a><?php endif; ?>
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?><table><tr><td><?php endif ?>
            <ul>
                <?php foreach($w_active_languages as $lang): if($lang['code']==$w_this_lang['code']) continue; ?>
                <li><a href="<?php echo $lang['translated_url']?>"><?php echo $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$lang['code']}' AND display_language_code='{$lang['code']}'");?> (<?php echo $lang['display_name']?>)</a></li>
                <?php endforeach; ?>
            </ul>
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?></td></tr></table></a><?php endif ?> 
        </li>
    </ul>    
</div>
