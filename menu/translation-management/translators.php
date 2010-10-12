<?php //included from menu translation-management.php ?>
<?php if ( current_user_can('list_users') ):

add_filter('icl_translators_list', 'icl_icanlocalize_translators_list');
add_filter('icl_translation_services_button', 'icl_local_add_translator_button');
add_filter('icl_translation_services_button', 'icl_icanlocalize_add_translator_button');

if ($selected_translator->ID) {
  
  // Edit form
  echo '<h3>'. __('Edit translator', 'sitepress') . '</h3>';
  echo '<form id="icl_tm_adduser" method="post">' . "\r\n";
  echo icl_local_edit_translator_form('edit', $selected_translator) . "\r\n";
  echo '</form>' . "\r\n";

} else {

  // Services add translator form

  // Services hook
  $services = apply_filters('icl_translation_services_button', array());
  if (!empty($services)) {

    // Toggle button
    echo '<input type="submit" id="icl_add_translator_form_toggle" value="'. __('Add translator', 'sitepress') . ' >>" />' . "\r\n";
    // Toggle div start
    echo '<div id="icl_add_translator_form_wrapper" class="hidden">';
    // Open form
    echo '<form id="icl_tm_adduser" method="post">';

    // 'From' and 'To' languages dropdowns
    $languages = $sitepress->get_active_languages();
    $default_language = $sitepress->get_default_language();
    $from = '<label>' . __('From language:', 'sitepress') . '&nbsp;<select name="from_lang" id="edit-from">'
            . "\r\n" . '<option value="0">' . __('Choose', 'sitepress') . '</option>' . "\r\n";
    $to = '<label>' . __('To language:', 'sitepress') . '&nbsp;<select name="to_lang" id="edit-to">' . "\r\n"
            . '<option value="0">' . __('Choose', 'sitepress') . '</option>' . "\r\n";
    foreach ($languages as $language) {
//              $selected_from = ($language->code == $default->language->code) ? ' selected="selected"' : '';
//              $selected_from = ($language->code == $default->language->code) ? ' selected="selected"' : '';
      $from .= '<option value="' . $language['code'] . '"' . $selected_from . '>' . $language['display_name']
               . '</option>' . "\r\n";
      $to .= '<option value="' . $language['code'] . '"' . $selected_to . '>' . $language['display_name']
             . '</option>' . "\r\n";
    }

    echo $from . '</select></label>' . "\r\n";
    echo $to . '</select></label>' . "\r\n";

    // Services radio boxes
    echo '<h4 style="margin-bottom:5px;">' . __('Select translation service', 'sitepress') . '</h4>' . "\r\n";

    foreach ($services as $service => $button) {
      $title = array();
      echo '<div style="margin-bottom:5px;"><input type="radio" id="radio-' . $service . '" name="services" value="' . $service . '" />';
      if (isset($button['name'])) $title[] = '<label for="radio-' .$service . '">&nbsp;' . $button['name'] . '</label>';
      if (isset($button['description'])) $title[] = $button['description'];
      if (isset($button['more_link'])) $title[] = $button['more_link'];
      echo implode(' - ', $title) . "\r\n";
      echo isset($button['content']) ? $button['content'] . "\r\n" : '';
      if (isset($button['setup_url'])) echo '<input type="hidden" id="' . $service . '_setup_url" name="' . $service . '_setup_url" value="' . $button['setup_url'] . '" />' . "\r\n";
      echo '</div>';
    }
    echo '<br style="clear:both;" />';
    echo '<input id="icl_add_translator_submit" class="button-primary" type="submit" value="' . esc_attr(__('Add translator', 'sitepress')) . '" />' . "\r\n";
    echo '</form>' . "\r\n";
    echo '</div>' . "\r\n";

  } else {
    _e('No add translator interface available', 'sitepress');
  }
}

// Translators lists

// Local translators
$blog_users_nt = TranslationManagement::get_blog_not_translators();
$blog_users_t = TranslationManagement::get_blog_translators();

// Translators added via hook
$other_service_translators = array();
$other_service_translators = apply_filters('icl_translators_list', $other_service_translators);
?>
        
        <?php if(!empty($blog_users_t) || !empty($other_service_translators)): ?>
            <h3><?php _e('Current translators', 'sitepress'); ?></h3>
            <table class="widefat fixed" cellspacing="0">
            <thead>
            <tr class="thead">
                <th><?php _e('Name', 'sitepress')?></th>
                <th><?php _e('Languages', 'sitepress')?></th>
                <th><?php _e('Type', 'sitepress')?></th>
                <th><?php _e('Action', 'sitepress')?></th>
            </tr>
            </thead>

            <tfoot>
            <tr class="thead">
                <th><?php _e('Name', 'sitepress')?></th>
                <th><?php _e('Languages', 'sitepress')?></th>
                <th><?php _e('Type', 'sitepress')?></th>
                <th><?php _e('Action', 'sitepress')?></th>
            </tr>
            </tfoot>

            <tbody class="list:user user-list">    
            <?php if(!empty($blog_users_t)): foreach ($blog_users_t as $bu ): ?>
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
                        <a class="edit" 
                            href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&amp;sm=translators&amp;icl_tm_action=remove_translator&amp;remove_translator_nonce=<?php 
                            echo wp_create_nonce('remove_translator')?>&amp;user_id=<?php echo $bu->ID ?>"><?php _e('Remove', 'sitepress') ?></a>
                        | 
                        <a class="edit" href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&amp;sm=translators&icl_tm_action=edit&amp;user_id=<?php echo $bu->ID ?>">
                            <?php _e('Language pairs', 'sitepress')?></a>
                    </div>
                </td>
                <td>
                    <?php $langs = $sitepress->get_active_languages(); ?>
                    <ul>
                    <?php foreach($language_pairs as $from=>$lp): ?>
                        <?php 
                            $tos = array();
                            foreach($lp as $to=>$null){ 
                                $tos[] = $langs[$to]['display_name'];
                            }
                        ?>
                        <li><?php printf(__('%s to %s', 'sitepress'), $langs[$from]['display_name'], join(', ', $tos)); ?></li>
                    <?php endforeach; ?>
                    </ul>
                </td>
                <td>
                    Local
                </td>
                <td>
                  <a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&amp;sm=translators&icl_tm_action=edit&amp;user_id=<?php echo $bu->ID ?>"><?php _e('edit languages', 'sitepress')?></a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            <?php if(!empty($other_service_translators)): foreach ($other_service_translators as $rows): ?>
            <?php
                if(!isset($trstyle) || $trstyle){
                    $trstyle = '';
                }else{
                    $trstyle = ' class="alternate"';
                }
                $edit_link = '';
                $language_pairs = isset($rows['langs']) ? $rows['langs'] : '';
            ?>
            <tr<?php echo $trstyle?>>
                <td class="column-title">
                    <strong><?php echo isset($rows['name']) ? $rows['name'] : ''; ?></strong>
                    <div class="row-actions">
                        <?php echo isset($rows['action']) ? $rows['action'] : ''; ?>
                    </div>
                </td>
                <td>
                    <?php $langs = $sitepress->get_active_languages(); ?>
                    <ul>
                    <?php foreach($language_pairs as $from => $lp): ?>
                        <?php
                            $from = isset($langs[$from]['display_name']) ? $langs[$from]['display_name'] : $from;
                            $tos = array();
                            foreach($lp as $to){
                                $tos[] =  isset($langs[$to]['display_name']) ? $langs[$to]['display_name'] : $to;
                            }
                        ?>
                        <li><?php printf(__('%s to %s', 'sitepress'), $from, join(', ', $tos)); ?></li>
                    <?php endforeach; ?>
                    </ul>
                </td>
                <td>
                  <?php echo isset($rows['type']) ? $rows['type'] : ''; ?>
                </td>
                <td>
                  <?php echo isset($rows['action']) ? $rows['action'] : ''; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
            
            </table>
        <?php else: ?>
            <center><?php _e('No translators set.', 'sitepress'); ?></center>
        <?php endif; ?>
    
    <?php endif; //if ( current_user_can('list_users') ) ?>

<?php

/**
 * Implementation of 'icl_translation_services_button' hook
 *
 * @param array $buttons
 * @return array
 */
function icl_local_add_translator_button($buttons = array()) {
  $buttons['local'] = icl_local_edit_translator_form();
  $buttons['local']['content'] = '<div id="local_translations_add_translator_toggle" style="display:none;">' . $buttons['local']['content'] . '</div>';
  return $buttons;
}

/**
 * Add/edit local translator form
 *
 * @global object $sitepress
 * @param string $action add|edit
 * @param object $selected_translator
 * @return mixed
 */
function icl_local_edit_translator_form($action = 'add', $selected_translator = 0) {

    global $sitepress;
    $blog_users_nt = TranslationManagement::get_blog_not_translators();
    $blog_users_t = TranslationManagement::get_blog_translators();

    $output = '';
    $return['name'] = __('Local', 'sitepress');
    $return['description'] = __('Your own translators', 'sitepress');

    if (empty($blog_users_nt)) {
      $output .= '<span class="updated fade" style="padding:4px">' . __('All blog users are translators', 'sitepress') . '</span>';
      $return['content'] = $output;
      return $return;
    }

    $output .= '<div id="icl_tm_add_user_errors">
        <span class="icl_tm_no_to">' . __('Select user.', 'sitepress') . '</span>
    </div>
    <input type="hidden" name="icl_tm_action" value="add_translator" />'
    . wp_nonce_field('add_translator', 'add_translator_nonce', true, false);
    
    if (!$selected_translator):
      $output .= '<select id="icl_tm_selected_user" name="user_id">
        <option value="0">- ' . __('select user', 'sitepress') . ' -</option>';
      foreach($blog_users_nt as $bu):
        $output .= '<option value="'. $bu->ID . '">' . esc_html($bu->display_name) . ' (' . $bu->user_login . ')' . '</option>';
      endforeach;
      $output .= '</select>';
    else:
      $output .= '<span class="updated fade" style="padding:4px">' . sprintf(__('Editing language pairs for <strong>%s</strong>', 'sitepress'),
            esc_html($selected_translator->display_name) . ' ('.$selected_translator->user_login.')') . '</span>';
      $output .= '<input type="hidden" name="user_id" value="' . $selected_translator->ID . '" />';
    endif;

    if ($selected_translator) {

      $output .= '<br />

      <div class="icl_tm_lang_pairs"';
      if ($selected_translator): $output .= ' style="display:block"'; endif;
      $output .= '>
          <ul>';
      
      foreach ($sitepress->get_active_languages() as $from_lang):
        $output .= '<li>
              <label><input class="icl_tm_from_lang" type="checkbox"';
        if ($selected_translator && 0 < count($selected_translator->language_pairs[$from_lang['code']])):
          $output .= ' checked="checked"';
        endif;
        $output .= ' />&nbsp;';
        $output .= sprintf(__('From %s'), $from_lang['display_name']) . '</label>
              <div class="icl_tm_lang_pairs_to"';
        if ($selected_translator && 0 < count($selected_translator->language_pairs[$from_lang['code']])):
          $output .= ' style="display:block"';
        endif;
        $output .= '>
                  <small>' . __('to', 'sitepress') . '</small>
                  <ul>';

          foreach($sitepress->get_active_languages() as $to_lang):
            if ($from_lang['code'] == $to_lang['code']) continue;
            $output .= '<li>
                      <label><input class="icl_tm_to_lang" type="checkbox" name="lang_pairs[' . $from_lang['code'] . '][' . $to_lang['code'] . ']" value="1"';
            if ($selected_translator->ID && isset($selected_translator->language_pairs[$from_lang['code']][$to_lang['code']])):
              $output .= ' checked="checked"';
            endif;
              $output .= ' />&nbsp;';
              $output .= $to_lang['display_name'] . '</label>&nbsp;
                      </li>';
          endforeach;
          $output .= '</ul>
              </div>
              </li>';
      endforeach;

      $output .= '</ul>';
      $output .= '
      <input class="button-primary" type="submit" value="';
      $output .= $selected_translator ? esc_attr(__('Update', 'sitepress')) : esc_attr(__('Add as translator', 'sitepress'));
      $output .= '" />';
    }
    
    $return['content'] = $output;

    return ($action == 'edit') ? $output : $return;
}

/**
 * Implementation of 'icl_translation_services_button' hook
 *
 * @global object $sitepress
 * @param array $buttons
 * @return array
 */
function icl_icanlocalize_add_translator_button($buttons) {
  global $sitepress;
  $return['name'] = 'ICanLocalize';
  $return['setup_url'] = $sitepress->create_icl_popup_link('@select-translators;from_replace;to_replace@', array('ar' => 1), true);
  $buttons['icanlocalize'] = $return;

  return $buttons;
}

/**
 * Implementation of 'icl_translators_list' hook
 *
 * @global object $sitepress
 * @param array $array
 * @return array
 */
function icl_icanlocalize_translators_list($array) {
  
  global $sitepress;
  $settings = $sitepress->get_settings();

  if (!isset($settings['site_id']) || !isset($settings['access_key'])) {
    return $array;
  }
  
  $wid = $settings['site_id'];
  $access_key = $settings['access_key'];

  $icl = new ICanLocalizeQuery($wid, $access_key);
  $data = $icl->get_website_details();

  $translators = array();
  if (isset($data['translation_languages']['translation_language'])) {
    foreach ($data['translation_languages']['translation_language'] as $key => $value) {
      if (isset($value['translators']) && !empty($value['translators'])) {
        foreach ($value['translators'] as $translator) {
          $translators[$translator['attr']['id']]['name'] = $translator['attr']['nickname'];
          $translators[$translator['attr']['id']]['langs'][$value['attr']['from_language_name']][] = $value['attr']['to_language_name'];
          $translators[$translator['attr']['id']]['type'] = 'ICanLocalize';
          $translators[$translator['attr']['id']]['action'] = $sitepress->create_icl_popup_link(ICL_API_ENDPOINT . '/websites/' . $wid
            . '/website_translation_offers/' . $data['translation_languages']['translation_language'][$key]['attr']['id'] . '/website_translation_contracts/'
            . $value['attr']['contract_id'], array('title' => __('Chat with translator', 'sitepress'))) . __('Chat with translator', 'sitepress') . '</a>';
        }
      }
    }
  }

  return array_merge($translators, $array);
}
?>