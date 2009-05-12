<div id="lang_sel">
    <ul>
        <li><a href="#" class="lang_sel_sel icl-<?php echo $w_this_lang['code'] ?>">
            <?php if($this->settings['icl_lso_flags']):?>                
            <img class="iclflag" src="<?php echo ICL_PLUGIN_URL .'/res/flags/' . $w_this_lang['code'] . '.png' ?>" alt="<?php echo $w_this_lang['code'] ?>" width="18" height="12" />                                
            &nbsp;<?php endif; ?>
            <?php if($this->settings['icl_lso_native_lang']):?>
            <?php echo $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$w_this_lang['code']}' AND display_language_code='{$w_this_lang['code']}'"); ?>
            <?php endif ?>
            <?php if(!isset($ie_ver) || $ie_ver > 6): ?></a><?php endif; ?>
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?><table><tr><td><?php endif ?>
            <ul>
                <?php foreach($w_active_languages as $lang): if($lang['code']==$w_this_lang['code']) continue; ?>
                <?php 
                    $translated_language = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$lang['code']}' AND display_language_code='{$lang['code']}'");
                    if(!$translated_language) $translated_language = $lang['english_name'];    
                    $language_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='{$lang['code']}' AND display_language_code='{$w_this_lang['code']}'");
                    if(!$language_name) $language_name = $lang['english_name'];    
                ?>
                <li class="icl-<?php echo $lang['code'] ?>">          
                    <a href="<?php echo $lang['translated_url']?>">
                    <?php if($this->settings['icl_lso_flags']):?>                
                    <img class="iclflag" src="<?php echo ICL_PLUGIN_URL .'/res/flags/' . $lang['code'] . '.png' ?>" alt="<?php echo $lang['code'] ?>" width="18" height="12" />&nbsp;                    
                    <?php endif; ?>
                    <?php if($this->settings['icl_lso_native_lang']):?>                
                    <?php echo $translated_language;?>                                
                    <?php endif; ?>
                    <?php if($this->settings['icl_lso_display_lang']):?>                
                    <?php if($this->settings['icl_lso_native_lang']):?>(<?php endif; ?><?php echo $language_name ?><?php if($this->settings['icl_lso_native_lang']):?>)<?php endif; ?>
                    <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?></td></tr></table></a><?php endif ?> 
        </li>
    </ul>    
</div>
