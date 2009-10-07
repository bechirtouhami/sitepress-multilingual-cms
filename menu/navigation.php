<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $sitepress_settings = $sitepress->get_settings();
    $cms_navigation_settings = $sitepress_settings['modules']['cms-navigation'];
?>
<?php $sitepress->noscript_notice() ?>
<script type="text/javascript">        
var icl_ajx_cache_cleared = '<?php echo __('The cache has been cleared.','sitepress') ?>';
</script>        
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>    
    
    <h3><?php echo __('Navigation', 'sitepress') ?></h3>    
    
    <p><?php echo __('Out-of-the-box support for full CMS navigation in your WordPress site including drop down menus, breadcrumbs trail and sidebar navigation.', 'sitepress')?></p>

    
    <h4><?php echo __('Settings', 'sitepress')?></h4>
    <form name="icl_navigation_form"  id="icl_navigation_form" action="">
    <p class="icl_form_errors" style="display:none"></p>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="icl_navigation_page_order"><?php echo __('Page order', 'sitepress')?></label></th>
            <td>
                <select name="icl_navigation_page_order" id="icl_navigation_page_order">
                <option value="menu_order" <?php if($cms_navigation_settings['page_order']=='menu_order'): ?>selected="selected"<?php endif;?>><?php echo __('Menu order', 'sitepress')?></option>
                <option value="post_name" <?php if($cms_navigation_settings['page_order']=='post_name'): ?>selected="selected"<?php endif;?>><?php echo __('Alphabetically', 'sitepress')?></option>
                <option value="post_date" <?php if($cms_navigation_settings['page_order']=='post_date'): ?>selected="selected"<?php endif;?>><?php echo __('Creation time', 'sitepress')?></option>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo __('Categories menu', 'sitepress')?></th>
            <td>
                <label for="icl_navigation_show_cat_menu"><input type="checkbox" id="icl_navigation_show_cat_menu" name="icl_navigation_show_cat_menu" value="1" <?php if($cms_navigation_settings['show_cat_menu']): ?>checked="checked"<?php endif ?> /> <?php echo __('Show categories menu', 'sitepress')?></label>
                <label for="icl_navigation_cat_menu_title" <?php if(!$cms_navigation_settings['show_cat_menu']): ?>style="display:none"<?php endif;?>><input type="text" id="icl_navigation_cat_menu_title" name="icl_navigation_cat_menu_title" value="<?php echo $cms_navigation_settings['cat_menu_title']?$cms_navigation_settings['cat_menu_title']:__('News','sitepress'); ?>" /> <?php echo __('Categories menu title', 'sitepress')?></label><input type="text" style="visibility:hidden" />                
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo __('Sidebar pages menu', 'sitepress')?></th>
            <td>
                <label for="icl_navigation_heading_start"><?php echo __('Heading start', 'sitepress')?> <input type="text" size="6" id="icl_navigation_heading_start" name="icl_navigation_heading_start" value="<?php echo $cms_navigation_settings['heading_start'] ?>" /></label>
                <label for="icl_navigation_heading_end"><?php echo __('Heading end', 'sitepress')?> <input type="text" size="6" id="icl_navigation_heading_end" name="icl_navigation_heading_end" value="<?php echo $cms_navigation_settings['heading_end'] ?>" /></label>
            </td>
        </tr>        
        <tr valign="top">
            <th scope="row"><?php echo __('Caching', 'sitepress')?></th>
            <td>
                <label for="icl_navigation_caching"><input type="checkbox" id="icl_navigation_caching" name="icl_navigation_caching" value="1" <?php if($cms_navigation_settings['cache']): ?>checked="checked"<?php endif ?> /> <?php echo __('Cache navigation elements for super fast performance', 'sitepress')?></label>
                <br />
                <input id="icl_navigation_caching_clear" class="button" name="icl_navigation_caching_clear" value="<?php echo __('Clear cache now', 'sitepress') ?>" type="button"/>
                <span class="icl_ajx_response" id="icl_ajx_response_clear_cache"></span>
            </td>
        </tr>        
    </table>
    
    <p class="submit">
    <input class="button-primary" type="submit" value="<?php echo __('Save Changes', 'sitepress')?>" name="Submit"/>
    <span class="icl_ajx_response" id="icl_ajx_response_nav"></span>
    </p>  
    
    </form>  
    
    
    <h4><?php echo __('Instructions for adding the navigation to your theme', 'sitepress')?></h4>
    
    <table class="widefat" cellspacing="0">
    <thead>
        <tr>
            <th scope="col"><?php echo __('Navigation element', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Description', 'sitepress') ?></th>
            <th scope="col"><?php echo __('HTML to add', 'sitepress') ?></th>        
            <th scope="col"><?php echo __('Where to add', 'sitepress') ?></th>        
        </tr>        
    </thead>        
    <tbody>
        <tr>
            <td scope="col" nowrap="nowrap"><?php echo __('Top navigation', 'sitepress') ?></td>          
            <td scope="col"><?php echo __('A list of the top level pages with drop down menus for second level menus. Can optionally contain the post categories', 'sitepress') ?></td>          
            <td scope="col" nowrap="nowrap"><code>&lt;?php  do_action('icl_navigation_menu'); ?&gt;</code></td>          
            <td scope="col">header.php</td>          
        </tr>
        <tr>
            <td scope="col" nowrap="nowrap"><?php echo __('Breadcrumbs trails', 'sitepress') ?></td>          
            <td scope="col"><?php echo __('Lists the path back to the home page', 'sitepress') ?></td>          
            <td scope="col" nowrap="nowrap"><code>&lt;?php  do_action('icl_navigation_breadcrumb'); ?&gt;</code></td>          
            <td scope="col"><?php printf(__('%s or %s, %s, %s, %s and %s', 'sitepress'), 'header.php', 'single.php', 'page.php', 'archive.php', 'tag.php', 'search.php');?></td>          
        </tr>
        <tr>
            <td scope="col" nowrap="nowrap"><?php echo __('Sidebar navigation', 'sitepress'); ?> <sup>*</sup></td>          
            <td scope="col"><?php echo __('Local navigation tree with page siblings, parent and brothers', 'sitepress') ?></td>          
            <td scope="col" nowrap="nowrap"><code>&lt;?php  do_action('icl_navigation_sidebar'); ?&gt;</code></td>          
            <td scope="col">sidebar.php</td>          
        </tr>        
    </tbody>        
    </table>    
    <p><sup>*</sup> <?php echo __('You can also add the sidebar navigation as a <a href="widgets.php">widget</a>.', 'sitepress')?></p>
    
    <p><?php echo __('To customize the appearance of the navigation elements, you will need to override the styling provided in the plugin\'s CSS file.', 'sitepress')?></p>
    
    <p><?php printf(__('Visit %s for full CSS customization information.', 'sitepress'), '<a href="http://wpml.org">wpml.org</a>')?></p>
    
    <?php do_action('icl_menu_footer'); ?>
    
</div>