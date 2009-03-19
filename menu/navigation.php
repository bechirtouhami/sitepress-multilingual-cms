<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $sitepress_settings = $sitepress->get_settings();
    $cms_navigation_settings = $sitepress_settings['modules']['cms-navigation'];
?>

<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup SitePress', 'sitepress') ?></h2>    
    
    <h3><?php echo __('Navigation', 'sitepress') ?></h3>    
    
    <p><?php echo __('Out-of-the-box support for full CMS navigation in your WordPress site including drop down menus, breadcrumbs trail and sidebar navigation.', 'sitepress')?></p>

    
    <h4><?php echo __('Settings', 'sitepress')?></h4>
    <form name="icl_navigation_form"  id="icl_navigation_form">
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
            <th scope="row"><?php echo __('Categories menu', 'sitepress')?></label></th>
            <td>
                <label for="icl_navigation_show_cat_menu"><input type="checkbox" id="icl_navigation_show_cat_menu" name="icl_navigation_show_cat_menu" value="1" <?php if($cms_navigation_settings['show_cat_menu']): ?>checked="checked"<?php endif ?> /> <?php echo __('Show categories menu', 'sitepress')?></label>
                <label for="icl_navigation_cat_menu_title" <?php if(!$cms_navigation_settings['show_cat_menu']): ?>style="display:none"<?php endif;?>><input type="text" id="icl_navigation_cat_menu_title" name="icl_navigation_cat_menu_title" value="<?php echo $cms_navigation_settings['cat_menu_title']?$cms_navigation_settings['cat_menu_title']:__('News','sitepress'); ?>" /> <?php echo __('Categories menu title', 'sitepress')?></label><input type="text" style="visibility:hidden" />                
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo __('Sidebar pages menu', 'sitepress')?></label></th>
            <td>
                <label for="icl_navigation_heading_start"><?php echo __('Heading start', 'sitepress')?> <input type="text" size="6" id="icl_navigation_heading_start" name="icl_navigation_heading_start" value="<?php echo $cms_navigation_settings['heading_start'] ?>" /></label>
                <label for="icl_navigation_heading_end"><?php echo __('Heading end', 'sitepress')?> <input type="text" size="6" id="icl_navigation_heading_end" name="icl_navigation_heading_end" value="<?php echo $cms_navigation_settings['heading_end'] ?>" /></label>
            </td>
        </tr>        
    </table>
    
    <p class="submit">
    <input class="button-primary" type="submit" value="<?php echo __('Save Changes', 'sitepress')?>" name="Submit"/>
    <span class="icl_ajx_response" id="icl_ajx_response_nav"></span>
    </p>  
    
    </form>  
    
    <p>
    <code>&lt;?php do_action('icl_navigation_breadcrumb'); ?&gt;</code>    
    </p>

    <p>
    <code>&lt;?php  do_action('icl_navigation_menu'); ?&gt;</code>    
    </p>

    <p>
    <code>&lt;?php  do_action('icl_navigation_sidebar'); ?&gt;</code>    
    </p>
    
    
    
    <a href="#read-more"><?php echo __('Read more(+)', 'sitepress') ?></a>
    
    <textarea id="icl_nav_read_more" readonly="readonly"></textarea>
    
</div>