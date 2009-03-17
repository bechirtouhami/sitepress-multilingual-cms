<?php 
/*
Plugin Name: CMS Navigation Simple
Plugin URI: http://sitepress.org/wordpress-cms-plugins/cms-navigation-plugin/
Description: Adds CMS navigation functions to WP posts and pages
Author: ICanLocalize
Author URI: http://www.icanlocalize.com
Version: 1.4
*/

/*
    This file is part of CMS Navigation.

    CMS Navigation is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CMS Navigation is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CMS Navigation.  If not, see <http://www.gnu.org/licenses/>.
*/

define('CMS_NAVIGATION_VERSION', 0.3);
if(is_admin() && !in_array($pagenow, array('page.php','page-new.php'))) return;


if(!defined('PHP_EOL')){
    define ('PHP_EOL',"\r\n");
}

$cms_nav_user_agent = $_SERVER['HTTP_USER_AGENT'];
if(preg_match('#MSIE ([0-9]+)\.[0-9]#',$cms_nav_user_agent,$matches)){
    $cms_nav_ie_ver = $matches[1];
}

function cms_navigation_breadcrumb(){
    global $post;
    
    if(0 === strpos('page', get_option('show_on_front'))){
        $page_on_front = (int)get_option('page_on_front'); 
        $page_for_posts  = (int)get_option('page_for_posts');
    }else{
        $page_on_front = 0;
        $page_for_posts  = 0;        
    }
    
    if($page_on_front!=$post->ID){ 
        if($page_on_front){
            ?><a href="<?php echo get_permalink($page_on_front); ?>"><?php echo get_the_title($page_on_front) ?></a> &raquo; <?php
        }elseif(!is_home() || (is_home() && !$page_on_front && $page_for_posts)){
            ?><a href="<?php bloginfo('home') ?>"><?php echo __('Home') ?></a> &raquo; <?php
        }
    }
    
    if(!is_page() && !is_home() && $page_for_posts){
        ?><a href="<?php echo get_permalink($page_for_posts); ?>"><?php echo get_the_title($page_for_posts) ?></a> &raquo; <?php
    }
    
    if(is_home() && $page_for_posts){
        echo get_the_title($page_for_posts);
    }elseif(is_page() && $page_on_front!=$post->ID){        
        the_post();
        if(is_array($post->ancestors)){            
            $ancestors = array_reverse($post->ancestors);
            foreach($ancestors as $anc){
                if($page_on_front==$anc) {continue;}
                ?>
                <a href="<?php echo get_permalink($anc); ?>"><?php echo get_the_title($anc) ?></a> &raquo; 
                <?php
            }            
        }    
        echo get_the_title();
        rewind_posts();
    }elseif(is_single()){
        the_post();
        $cat = get_the_category($id);
        $cat = $cat[0]->cat_ID;                
        $parents = get_category_parents($cat, TRUE, ' &raquo; ');
        if(is_string($parents)){
            echo $parents;
        }
        the_title();   
        rewind_posts();         
    }elseif (is_category()) {
        $cat = get_term(intval( get_query_var('cat')), 'category', OBJECT, 'display');
        if($cat->category_parent){
            echo get_category_parents($cat->category_parent, TRUE, ' &raquo; ');                 
        }
        single_cat_title();
    }elseif(is_tag()){
        echo __('Articles tagged ') ,'&#8216;'; 
        single_tag_title();
        echo '&#8217;';    
    }elseif (is_month()){
        echo the_time('F, Y');
    }elseif (is_search()){
        echo __('Search for: '), strip_tags(get_query_var('s'));
    /*    
    }elseif (is_404()){
        echo __('Not found');
    */
    }        
}

function cms_navigation_menu_nav($order='menu_order',$show_cat_menu=false, $cat_menu_title='News'){
    global $wpdb, $post, $cms_nav_ie_ver;    
    if(0 === strpos('page', get_option('show_on_front'))){
        $page_on_front = (int)get_option('page_on_front'); 
        $page_for_posts  = (int)get_option('page_for_posts');
    }else{
        $page_on_front = 0;
        $page_for_posts  = 0;        
    }

    // exclude some pages
    $custom_excluded = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_top_nav_excluded' AND meta_value <> ''");
    $excluded_pages = array_merge(array($page_for_posts), $custom_excluded);  
    $excluded_pages = join(',', $excluded_pages);
    
    if(!$post->ancestors){
        $post->ancestors = array();
    }       
    $pages = $wpdb->get_col("
        SELECT ID FROM {$wpdb->posts} 
        WHERE post_type='page' AND post_status='publish' AND post_parent=0 AND ID NOT IN ({$excluded_pages})
        ORDER BY {$order}");   
    if($pages){   
        ?><div id="menu-wrap"><?php
        ?><ul id="cms-nav-top-menu"><?php
        foreach($pages as $p){
            $subpages = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent={$p} AND post_status='publish' ORDER BY {$order}");
            if($p==$post->ID || in_array($p,$post->ancestors)){
                $sel = true;
            }else{
                $sel = false;
            }                        
            ?><li<?php if($sel):?> class="selected_page"<?php endif?>><a href="<?php echo $p==$post->ID?'#':get_permalink($p); ?>" class="<?php if($subpages):?>trigger<?php endif?>"><?php echo get_the_title($p) ?><?php if(!isset($cms_nav_ie_ver) || $cms_nav_ie_ver > 6): ?></a><?php endif; ?>
                <?php if($subpages):?>
                    <?php if(isset($cms_nav_ie_ver) && $cms_nav_ie_ver <= 6): ?><table><tr><td><?php endif; ?>
                    <ul>
                        <?php foreach($subpages as $sp): ?>
                        <li<?php if($sp==$post->ID):?> class="selected_subpage"<?php endif?>><?php                            
                            if($sp!=$post->ID):?><a href="<?php echo get_permalink($sp); ?>" <?php if(in_array($sp,$post->ancestors)): ?>class="selected"<?php endif;?>><?php endif?><?php echo get_the_title($sp) ?><?php if($sp!=$post->ID):?></a><?php endif                             
                        ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if(isset($cms_nav_ie_ver) && $cms_nav_ie_ver <= 6): ?></td></tr></table></a><?php endif; ?>
                <?php endif; ?>                    
            </li>
            <?php
        }
        //add categories
        if($show_cat_menu){
            if($page_for_posts){
                $blog_url = get_permalink($page_for_posts);
                $blog_name = get_the_title($page_for_posts);
            }else{
                $blog_url = get_option('home');
                $blog_name = $cat_menu_title;                
            }
            $cat_menu_selected = '';
            if(is_single() || is_category()){
                $cat_menu_selected = ' selected';
            }
            ob_start();
            wp_list_categories('title_li=<a class="trigger'.$cat_menu_selected.'" href="'.$blog_url.'">'.$blog_name.'</a>'.
                '&current_category='.intval(get_query_var('cat')).'&depth=1');
            $cont = ob_get_contents();    
            ob_end_clean();           
            $cont = preg_replace('@^<li([^>]*)><a([^>]*)>([^<]*)</a><ul>@im','<li$1><a$2>$3<!--[if IE 7]><!--></a><!--<![endif]--><!--[if lte IE 6]><table><tr><td><![endif]--><ul>',$cont);         
            $cont = preg_replace('@</li>\Z@im', '</li><!--[if lte IE 6]></td></tr></table></a><![endif]-->',  $cont);
            echo $cont;
        }
        ?></ul></div><br class="cms-nav-clearit" /><?php
    }
}

function cms_navigation_page_navigation($order='menu_order', $heading_start='<h4>', $heading_end='</h4>'){
    if(!is_page()) return;
    global $post, $wpdb;  
        
    // is home?
    $is_home = get_post_meta($post->ID,'_cms_nav_minihome',true);
    
    if($is_home || !$post->ancestors){
        $pid = $post->ID;
    }else{
        //get top level page parent or home
        $parent = $post->ancestors[0];            
        do{
            $uppost = $wpdb->get_row("
                SELECT p1.ID, p1.post_parent, p2.meta_value IS NOT NULL AS minihome 
                FROM {$wpdb->posts} p1
                    LEFT JOIN {$wpdb->postmeta} p2 ON p1.ID=p2.post_id AND (meta_key='_cms_nav_minihome' OR meta_key IS NULL)
                    WHERE post_type='page' AND p1.ID={$parent}
            ");
            $pid = $uppost->ID;
            $parent = $uppost->post_parent;
            $minihome = $uppost->minihome;        
        }while($parent!=0 && !$minihome);
    } 
              
    echo $heading_start;
    if($pid!=$post->ID){ 
        ?><a href="<?php echo get_permalink($pid); ?>"><?php 
    } 
    echo get_the_title($pid);
    if($pid!=$post->ID){
        ?></a><?php
    }
    echo $heading_end;
    ?>
    
    <?php
    $sub = $wpdb->get_results("
            SELECT p1.ID, meta_value AS section FROM {$wpdb->posts} p1 
            LEFT JOIN {$wpdb->postmeta} p2 ON p1.ID=p2.post_id AND (meta_key='_cms_nav_section' OR meta_key IS NULL)
            WHERE post_parent={$pid} AND post_status='publish' ORDER BY {$order}"); 
    if(empty($sub))  return;                   
    foreach($sub as $s){
        $sections[$s->section][] = $s->ID;    
    }
    ksort($sections);    
    foreach($sections as $sec_name=>$sec){
        ?>
        <ul class="cms-nav-left-menu">
            <?php if($sec_name): ?>
            <li class="cms-nav-sub-section"><?php echo $sec_name ?></li>
            <?php endif; ?>
            <?php foreach($sec as $s):?>
            <li<?php if($post->ID==$s):?> class="selected_page_side"<?php endif;?>><?php
                if($post->ID!=$s):?><a href="<?php echo get_permalink($s); ?>"><?php endif?><?php echo get_the_title($s) ?><?php if($post->ID!=$s):?></a><?php endif;                                
                    if(!get_post_meta($s, '_cms_nav_minihome', 1)){
                        cms_navigation_child_pages_recursive($s); 
                    }                
            ?></li>
            <?php endforeach;?>
        </ul>
        <?php
    }
}

function cms_navigation_child_pages_recursive($pid){
    global $wpdb, $post;    
    $subpages = $wpdb->get_results("
        SELECT p1.ID, p2.meta_value IS NOT NULL AS minihome FROM {$wpdb->posts} p1 
        LEFT JOIN {$wpdb->postmeta} p2 ON p1.ID=p2.post_id AND (meta_key='_cms_nav_minihome' OR meta_key IS NULL)
        WHERE post_parent={$pid} AND post_type='page' AND post_status='publish'");        
     if($subpages): ?><ul>
        <?php foreach($subpages as $s): 
        ?><li<?php if($post->ID==$s->ID):?> class="selected"<?php endif;?>><?php
            if($post->ID!=$s->ID):?><a href="<?php echo get_permalink($s->ID)?>"><?php endif;?><?php echo get_the_title($s->ID) ?><?php if($post->ID!=$s->ID):?></a><?php endif;
            if(!$s->minihome) cms_navigation_child_pages_recursive($s->ID); 
        ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; 
}

// what happens when a post is saved
add_action('save_post', 'cms_navigation_update_post_settings');        
function cms_navigation_update_post_settings($id){
    if($_POST['post_type']!='page') return;
    $post_id = $_POST['post_ID'];
    if($_POST['exclude_from_top_nav']){
        update_post_meta($post_id, '_top_nav_excluded',1);
    }else{
        delete_post_meta($post_id, '_top_nav_excluded');
    }
    if($_POST['cms_nav_minihome']){
        update_post_meta($post_id, '_cms_nav_minihome',1);
    }else{
        delete_post_meta($post_id, '_cms_nav_minihome');
    }
    if($_POST['cms_nav_section_new']){
        update_post_meta($post_id, '_cms_nav_section', $_POST['cms_nav_section_new']);
    }else{
        delete_post_meta($post_id, '_cms_nav_section');
    }    
    if(!trim($_POST['cms_nav_section_new'])){
        if($_POST['cms_nav_section']){
            update_post_meta($post_id, '_cms_nav_section', $_POST['cms_nav_section']);
        }else{
            delete_post_meta($post_id, '_cms_nav_section');
        }        
    }
}
//

// add the cms navigation inline menu for pages
add_action('admin_head', 'cms_navigation_page_edit_options');        
function cms_navigation_page_edit_options(){
    add_meta_box('cmsnavdiv', __('CMS Navigation'), 'cms_navigation_meta_box', 'page', 'normal', 'high');
}

function cms_navigation_meta_box($post){
    global $wpdb;
    // get sections
    $sections = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_cms_nav_section'");
    $post_custom = get_post_custom($post->ID);    
    $top_nav_excluded = $post_custom['_top_nav_excluded'][0];
    $cms_nav_minihome = $post_custom['_cms_nav_minihome'][0];
    $cms_nav_section = $post_custom['_cms_nav_section'][0];
    if($top_nav_excluded){ $top_nav_excluded = 'checked="checked"'; }
    if($cms_nav_minihome){ $cms_nav_minihome = 'checked="checked"'; }
    ?>
    <p>
    <label><input type="checkbox" value="1" name="exclude_from_top_nav" <?php echo $top_nav_excluded ?> />&nbsp; <?php echo __('Exclude from the top navigation') ?></label> &nbsp;
    <label><input type="checkbox" value="1" name="cms_nav_minihome" <?php echo $cms_nav_minihome ?> />&nbsp; <?php echo __('Mini home (don\'t list child pages for this page)') ?></label>
    </p>
    <p>
    <?php echo __('Section')?>
    <?php if(!empty($sections)): ?>
        <select name="cms_nav_section">    
        <option value=''><?php echo __('--none--') ?></option>
        <?php foreach($sections as $s):?>
        <option <?php if($s==$cms_nav_section) echo 'selected="selected"'?>><?php echo $s ?></option>
        <?php endforeach; ?>        
        </select>
    <?php endif; ?>    
    <input type="text" name="cms_nav_section_new" value="" <?php if(!empty($sections)): ?>style="display:none"<?php endif; ?> />
    <?php if(!empty($sections)): ?>
    <a href="javascript:;" id="cms_nav_add_section"><?php echo __('enter new') ?></a>
    <?php endif; ?>    
    </p>
    <?php
}
add_action('admin_head', cms_navigation_js);
function cms_navigation_js(){
    ?>
    <script type="text/javascript">
    addLoadEvent(function(){                   
                jQuery('#cms_nav_add_section').click(cms_nav_switch_adding_section);    
    });
    function cms_nav_switch_adding_section(){
        if('none'==jQuery("select[name='cms_nav_section']").css('display')){
            jQuery("select[name='cms_nav_section']").show();
            jQuery("input[name='cms_nav_section_new']").hide();
            jQuery("input[name='cms_nav_section_new']").attr('value','');
            jQuery(this).html('<?php echo __('enter new') ?>');                                    
        }else{
            jQuery("select[name='cms_nav_section']").hide();
            jQuery("input[name='cms_nav_section_new']").show();            
            jQuery(this).html('<?php echo __('cancel') ?>');
        }
        
    }
    </script>
    <?php
}


// 
//add_action('wp_head', cms_navigation_css); - THIS WOULD ADD THE STYLEHSEET *AFTER* THE THEME STYLESHEET


//add_action('wp_head', cms_navigation_css); - THIS WOULD ADD THE STYLEHSEET *AFTER* THE THEME STYLESHEET
function cms_navigation_css($show=true){
    //make it MU and WP compatible
    $plugins_folder = basename(dirname(dirname(__FILE__)));
    $link_tag = '<link rel="stylesheet" href="'. get_option('home') . '/wp-content/' . $plugins_folder . '/'. 
        basename(dirname(__FILE__)) . '/css/cms-navigation.css?ver=' . CMS_NAVIGATION_VERSION .'" type="text/css" media="all" />';
    if(!$show){
        return $link_tag;
    }else{
        echo $link_tag;
    }
}

// THIS WOULD ADD THE STYLEHSEET *BEFORE* THE THEME STYLESHEET
add_action('init','cms_nav_ob_start');
function cms_nav_ob_start(){
    ob_start('cms_nav_prepend_css');
}
add_action('wp_head','cms_nav_ob_end');
function cms_nav_ob_end(){
    ob_end_flush();
}
function cms_nav_prepend_css($buf){
    return preg_replace('#</title>#i','</title>' . PHP_EOL . PHP_EOL . cms_navigation_css(false), $buf);
}


// database update - should be removed on the next release
if(CMS_NAVIGATION_VERSION <= 0.3){
    $wpdb->update($wpdb->postmeta, array('meta_key'=>'_cms_nav_minihome'), array('meta_key'=>'bp_minihome'));
    $wpdb->update($wpdb->postmeta, array('meta_key'=>'_cms_nav_section'), array('meta_key'=>'bp_section'));
}
