<?php
  if(version_compare(phpversion(), '5', '<')){
      add_action('admin_notices', 'icl_php_version_warn');
      
      function icl_php_version_warn(){
          echo '<div class="error"><ul><li><strong>';
          echo __('It appears that your host isn\'t running PHP5. SitePress requires PHP5 in order to work correctly. We recommend that you contact your hosting company and request them to switch you to PHP5.', 'sitepress');
          echo '</strong></li></ul></div>';
      }
  }
  
?>
