<?php
  if(version_compare(phpversion(), '5', '<')){
      add_action('admin_notices', 'icl_php_version_warn');
      
      function icl_php_version_warn(){
          echo '<div class="error"><ul><li><strong>';
          echo __('SitePress cannot be activated becaue your version of PHP is too old. To run correctly, you must have PHP5 installed. We recommend that you contact your hosting company and request them to switch you to PHP5.', 'sitepress');
          echo '</strong></li></ul></div>';                    
      }
      
      $active_plugins = get_option('active_plugins');
      $icl_sitepress_idx = array_search('sitepress-multilingual-cms/sitepress.php', $active_plugins);
      if(false !== $icl_sitepress_idx){
          unset($active_plugins[$icl_sitepress_idx]);
          update_option('active_plugins', $active_plugins);
          unset($_GET['activate']);
          $recently_activated = get_option('recently_activated');
          if(!isset($recently_activated['sitepress-multilingual-cms/sitepress.php'])){
              $recently_activated['sitepress-multilingual-cms/sitepress.php'] = time();
              update_option('recently_activated', $recently_activated);
          }
      }  
      define('PHP_VERSION_INCOMPATIBLE', true);    
  }  
?>
