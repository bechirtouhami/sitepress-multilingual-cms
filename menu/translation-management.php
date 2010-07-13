<?php 
    global $iclTranslationManagement;
    $selected_translator = $iclTranslationManagement->get_selected_translator();
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32" 
        style="background: transparent url(<?php echo ICL_PLUGIN_URL ?>/res/img/icon<?php if(!$sitepress_settings['basic_menu']) echo '_adv'?>.png) no-repeat"><br /></div>
    <h2><?php echo __('Translation management', 'sitepress') ?></h2>    
    
    <?php do_action('icl_tm_messages'); ?>
    
    <?php if ( current_user_can('list_users') ): ?>
    <a href="#"><?php _e('Translators', 'sitepress') ?></a>    
    <?php endif;  ?>
    
    
    <?php if ( current_user_can('list_users') ): ?>
        <?php 
        $blog_users_nt = TranslationManagement::get_blog_not_translators();
        $blog_users_t = TranslationManagement::get_blog_translators();
        ?>
        
        <br />
        
        <?php if(empty($blog_users_nt)): ?>        
        
        <span class="updated fade" style="padding:4px"><?php _e('All blog users are translators', 'sitepress')?></span>
        <?php else: ?>
        <h3><?php _e('Add translator', 'sitepress'); ?></h3>
        <div id="icl_tm_add_user_errors">
            <span class="icl_tm_no_to"><?php _e('Select at least one language pair.', 'sitepress')?></span>
        </div>
        <form id="icl_tm_adduser" method="post">
        <input type="hidden" name="icl_tm_action" value="add_translator" />
        <?php wp_nonce_field('add_translator','add_translator_nonce'); ?>   
        
        <?php if(!$selected_translator->ID):?>         
        <select id="icl_tm_selected_user" name="user_id">
            <option value="">- <?php _e('select user', 'sitepress')?> -</option>
            <?php foreach($blog_users_nt as $bu): ?>
            <option value="<?php echo $bu->ID ?>"><?php echo esc_html($bu->display_name) . ' (' . $bu->user_login . ')' ?></option>
            <?php endforeach; ?>
        </select>
        <?php else: ?>
            <span class="updated fade" style="padding:4px"><?php printf(__('Editing language pairs for <strong>%s</strong>', 'sitepress'), 
                esc_html($selected_translator->display_name) . ' ('.$selected_translator->user_login.')')?></span>
            <input type="hidden" name="user_id" value="<?php echo $selected_translator->ID ?>" />
        <?php endif; ?>
        <br />
        
        <div class="icl_tm_lang_pairs" <?php if($selected_translator->ID):?>style="display:block"<?php endif;?>>
            <ul>
            <?php foreach($sitepress->get_active_languages() as $from_lang):?>
                <li>
                <label><input class="icl_tm_from_lang" type="checkbox" 
                    <?php if($selected_translator->ID && 0 < count($selected_translator->language_pairs[$from_lang['code']])):?>checked="checked"<?php endif; ?> />&nbsp;
                    <?php printf(__('From %s'), $from_lang['display_name']) ?></label>
                <div class="icl_tm_lang_pairs_to" <?php if($selected_translator->ID && 0 < count($selected_translator->language_pairs[$from_lang['code']])):?>style="display:block"<?php endif; ?>>
                    <small><?php _e('to', 'sitepress')?></small>
                    <ul>
                    <?php foreach($sitepress->get_active_languages() as $to_lang):?>
                        <?php if($from_lang['code'] == $to_lang['code']) continue; ?>
                        <li>                    
                        <label><input class="icl_tm_to_lang" type="checkbox" name="lang_pairs[<?php echo $from_lang['code'] ?>][<?php echo $to_lang['code'] ?>]" value="1" 
                            <?php if($selected_translator->ID && isset($selected_translator->language_pairs[$from_lang['code']][$to_lang['code']])):?>checked="checked"<?php endif; ?> />&nbsp;
                            <?php echo $to_lang['display_name'] ?></label>&nbsp;
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                </li>
            <?php endforeach; ?>
            </ul>
            <input class="button-primary" type="submit" value="<?php echo $selected_translator->ID ? esc_attr(__('Update', 'sitepress')) : esc_attr(__('Add as translator', 'sitepress')); ?>" />
        </div>
        </form>
        <?php endif; ?>
        
        <?php if(!empty($blog_users_t)): ?>
            <h3><?php _e('Current translators', 'sitepress'); ?></h3>
            <table class="widefat fixed" cellspacing="0">
            <thead>
            <tr class="thead">
                <th><?php _e('User login', 'sitepress')?></th>
                <th><?php _e('Display name', 'sitepress')?></th>
                <th><?php _e('Language pairs', 'sitepress')?></th>
            </tr>
            </thead>

            <tfoot>
            <tr class="thead">
                <th><?php _e('User login', 'sitepress')?></th>
                <th><?php _e('Display name', 'sitepress')?></th>
                <th><?php _e('Language pairs', 'sitepress')?></th>
            </tr>
            </tfoot>

            <tbody class="list:user user-list">    
            <?php foreach ($blog_users_t as $bu ): ?>
            <?php 
                if(!isset($trstyle) || $trstyle){
                    $trstyle = '';
                }else{
                    $trstyle = ' class="alternate"';
                }
                if ($current_user->ID == $bu->ID) {
                    $edit_link = 'profile.php';
                } else {
                    $edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( esc_url( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), "user-edit.php?user_id=$bu->ID" ) );
                } 
                $language_pairs = get_user_meta($bu->ID, $wpdb->prefix.'language_pairs', true);       
            ?>
            <tr<?php echo $trstyle?>>
                <td class="column-title">
                    <strong><a class="row-title" href="<?php echo $edit_link ?>"><?php echo $bu->user_login; ?></a></strong>
                    <div class="row-actions">
                        <a class="edit" href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&amp;icl_tm_action=remove_translator&amp;remove_translator_nonce=<?php 
                            echo wp_create_nonce('remove_translator')?>&amp;user_id=<?php echo $bu->ID ?>"><?php _e('Remove', 'sitepress') ?></a>
                        | 
                        <a class="edit" href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&icl_tm_action=edit&amp;user_id=<?php echo $bu->ID ?>">
                            <?php _e('Language pairs', 'sitepress')?></a>
                    </div>
                </td>
                <td><?php echo esc_html($bu->display_name); ?></td>
                <td>
                    <?php $langs = $sitepress->get_active_languages(); ?>
                    <ul>
                    <?php foreach($language_pairs as $from=>$lp): ?>
                        <?php foreach($lp as $to=>$null): ?>
                            <li><?php printf(__('%s to %s', 'sitepress'), $langs[$from]['display_name'], $langs[$to]['display_name']); ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            
            </table>
        <?php else: ?>
            <center><?php _e('No translators set.', 'sitepress'); ?></center>
        <?php endif; ?>
    
    <?php endif; //if ( current_user_can('list_users') ) ?>
    
    
</div>
