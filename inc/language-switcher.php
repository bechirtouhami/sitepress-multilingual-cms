<?php

class SitePressLanguageSwitcher {
	
	var $widget_preview = false;
	var $widget_css_defaults;
	
	var $footer_preview = false;
	var $footer_css_defaults;
	
	var $color_schemes = array(
			'Gray' => array(
                'font-current-normal' => '#222222',
                'font-current-hover' => '#000000',
                'background-current-normal' => '#eeeeee',
                'background-current-hover' => '#eeeeee',
                'font-other-normal' => '#222222',
                'font-other-hover' => '#000000',
                'background-other-normal' => '#e5e5e5',
                'background-other-hover' => '#eeeeee',
                'border' => '#cdcdcd',
				'background' => '#e5e5e5'
            ),
            'White' => array(
                'font-current-normal' => '#444444',
                'font-current-hover' => '#000000',
                'background-current-normal' => '#ffffff',
                'background-current-hover' => '#eeeeee',
                'font-other-normal' => '#444444',
                'font-other-hover' => '#000000',
                'background-other-normal' => '#ffffff',
                'background-other-hover' => '#eeeeee',
                'border' => '#e5e5e5',
				'background' => '#ffffff'
            ),
            'Blue' => array(
                'font-current-normal' => '#ffffff',
                'font-current-hover' => '#000000',
                'background-current-normal' => '#95bedd',
                'background-current-hover' => '#95bedd',
                'font-other-normal' => '#000000',
                'font-other-hover' => '#ffffff',
                'background-other-normal' => '#cbddeb',
                'background-other-hover' => '#95bedd',
                'border' => '#0099cc',
				'background' => '#cbddeb'
            )
	);
	
	function __construct(){
		
		$this->widget_css_defaults = $this->color_schemes['White'];
		$this->footer_css_defaults = $this->color_schemes['White'];
		
		add_action('plugins_loaded',array(&$this,'init'));
		//add_action('wp_head',array(&$this,'home_test'),0);
		if(is_admin() && $_GET['page'] == ICL_PLUGIN_FOLDER . '/menu/languages.php'){
			add_action('admin_head', array($this, 'custom_language_switcher_style'));
		}
		if(!is_admin()){
			add_action('wp_head', array($this, 'custom_language_switcher_style'));
		}
	}
	
	function home_test(){
		//add_filter('option_home',array(&$this,'option_home'));
	}
	
	function option_home($url){
		if ($this->settings['language_negotiation_type'] == 1){
			global $sitepress;
			remove_action('pre_option_home', array($sitepress,'pre_option_home'));
			//$this->home = get_option('home');
            //$url = $sitepress->convert_url($this->home, ICL_LANGUAGE_CODE);
			return rtrim($url,'/');
			//return get_bloginfo('home').'/'.ICL_LANGUAGE_CODE;
		}
		return $url;
	}
	
	function init(){
		global $sitepress_settings;
		$this->settings = $sitepress_settings;
        if ($this->settings['icl_lang_sel_footer']){
            add_action('wp_head', array($this, 'language_selector_footer_style'),19);
            add_action('wp_footer', array($this, 'language_selector_footer'),19);
		}
		if (is_admin()) add_action('icl_language_switcher_options',array(&$this,'admin'),1);
	}
	
	function language_selector_footer_style(){
		
        $add = false;
        foreach($this->footer_css_defaults as $key=>$d){
            if (isset($this->settings['icl_lang_sel_footer_config'][$key]) && $this->settings['icl_lang_sel_footer_config'][$key] != $d){
                $this->settings['icl_lang_sel_footer_config'][$key] . "\n";
                $add = true;
                break;
            }
        }
        if($add){
            echo "\n<style type=\"text/css\">";
            foreach($this->settings['icl_lang_sel_footer_config'] as $k=>$v){
                switch($k){
                    case 'font-current-normal': 
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer a, #lang_sel_footer a.lang_sel_sel, #lang_sel_footer a.lang_sel_sel:visited{color:'.$v.';}'; 
                        break;
                    case 'font-current-hover': 
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer a:hover, #lang_sel_footer a.lang_sel_sel:hover{color:'.$v.';}';
                        break;
                    case 'background-current-normal': 
                        //if($v != $this->color_schemes[$k])
							echo '#lang_sel_footer a.lang_sel_sel, #lang_sel_footer a.lang_sel_sel:visited{background-color:'.$v.';}'; 
                        break;
                    case 'background-current-hover': 
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer a.lang_sel_sel:hover{background-color:'.$v.';}'; 
                        break;
                    case 'font-other-normal':
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer ul a, #lang_sel_footer ul a:visited{color:'.$v.';}'; 
                        break;
                    case 'font-other-hover': 
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer ul a:hover{color:'.$v.';}'; 
                        break;
                    case 'background-other-normal': 
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer ul a, #lang_sel_footer ul a:visited{background-color:'.$v.';}'; 
                        break;
                    case 'background-other-hover': 
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer ul a:hover{background-color:'.$v.';}'; 
                        break;
                    case 'border': 
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer{border-color:'.$v.';}';
                        break;
                    case 'background': 
                        //if($v != $this->color_schemes[$k])
                            echo '#lang_sel_footer{background-color:'.$v.';}';
                        break;
                }
            }
            echo "</style>\n";
        }
    }
	
	function language_selector_footer() {
        $languages = icl_get_languages('orderby=id&order=asc&skip_missing=0');
        if(!empty($languages)){
            echo '
                <div id="lang_sel_footer">
                    <ul>
                    ';
                foreach($languages as $lang){
                    echo '<li>';
					echo '<a href="'.$lang['url'].'"';
                    if($lang['active']) echo ' class="lang_sel_sel"';
					echo '>';
                    if ($this->settings['icl_lso_flags'] || $this->footer_preview) echo '<img src="'.$lang['country_flag_url'].'" alt="'.$lang['language_code'].'" class="iclflag"';
					if (!$this->settings['icl_lso_flags'] && $this->footer_preview) echo ' style="display:none;"';
					if ($this->settings['icl_lso_flags'] || $this->footer_preview) echo ' />&nbsp;';
                    //if(!$l['active']) echo '</a>';
                    //if(!$l['active']) echo '<a href="'.$l['url'].'">';
                    if($this->footer_preview){
                            $lang_native = $lang['native_name'];
                            if($this->settings['icl_lso_native_lang']){
                                $lang_native_hidden = false;
                            }else{
                                $lang_native_hidden = true;
                            }
                            $lang_translated = $lang['translated_name'];
                            if($this->settings['icl_lso_display_lang']){
                                $lang_translated_hidden = false;
                            }else{
                                $lang_translated_hidden = true;
                            }                            
                        }else{
                            if($this->settings['icl_lso_native_lang']){
                                $lang_native = $lang['native_name'];
                            }else{
                                $lang_native = false;
                            }
                            if($this->settings['icl_lso_display_lang']){
                                $lang_translated = $lang['translated_name'];
                            }else{
                                $lang_translated = false;
                            }
                        }
                        echo icl_disp_language($lang_native, $lang_translated, $lang_native_hidden, $lang_translated_hidden);
                    //echo icl_disp_language( $this->settings['icl_lso_native_lang'] ? $l['native_name'] : null, $this->settings['icl_lso_display_lang'] ? $l['translated_name'] : null );
                    //if(!$l['active']) echo '</a>';
					echo '</a>';
                    echo '</li>
                    ';
                }
            echo '
                    </ul>
                </div>';
            }
    }

	function admin(){
		foreach($this->color_schemes as $key=>$val): ?>
			<?php foreach($this->widget_css_defaults as $k=>$v): ?>                                                
                                                <input type="hidden" id="icl_lang_sel_config_alt_<?php echo $key ?>_<?php echo $k ?>" value="<?php echo $this->color_schemes[$key][$k] ?>" />
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
											
		<?php if(!defined('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS') || !ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS): ?>
                                            <br />
                                            <a href="#" onclick="jQuery(this).next().slideToggle();return false;"><?php _e('Edit the language switcher widget colors', 'sitepress')?></a>                                            
                                            <div style="display:none">                                          
                                                <table id="icl_lang_preview_config" style="width:auto;">
                                                    <thead>
                                                    <tr>
                                                        <th>&nbsp;</th>
                                                        <th><?php _e('Normal', 'sitepress')?></th>
                                                        <th><?php _e('Hover', 'sitepress')?></th>
                                                    </tr>
                                                    </thead>
								                    <tbody>                                                
                                                    <tr>
                                                        <td><?php _e('Current language font color', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-font-current-normal" name="icl_lang_sel_config[font-current-normal]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['font-current-normal'])) 
                                                                echo $this->settings['icl_lang_sel_config']['font-current-normal']; 
                                                            else 
                                                                echo $this->widget_css_defaults['font-current-normal'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-font-current-normal-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-font-current-normal';cp.show('icl-font-current-normal-picker');return false;" /></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-font-current-hover" name="icl_lang_sel_config[font-current-hover]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['font-current-hover'])) 
                                                                echo $this->settings['icl_lang_sel_config']['font-current-hover']; 
                                                            else 
                                                                echo $this->widget_css_defaults['font-current-hover'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-font-current-hover-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-font-current-hover';cp.show('icl-font-current-hover-picker');return false;" /></td>
                                                    </tr>                                                
                                                    <tr>
                                                        <td><?php _e('Current language background color', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-background-current-normal" name="icl_lang_sel_config[background-current-normal]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['background-current-normal'])) 
                                                                echo $this->settings['icl_lang_sel_config']['background-current-normal']; 
                                                            else 
                                                                echo $this->widget_css_defaults['background-current-normal'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-current-normal-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-background-current-normal';cp.show('icl-background-current-normal-picker');return false;" /></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-background-current-hover" name="icl_lang_sel_config[background-current-hover]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['background-current-hover'])) 
                                                                echo $this->settings['icl_lang_sel_config']['background-current-hover']; 
                                                            else 
                                                                echo $this->widget_css_defaults['background-current-hover'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-current-hover-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-background-current-hover';cp.show('icl-background-current-hover-picker');return false;" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td><?php _e('Other languages font color', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-font-other-normal" name="icl_lang_sel_config[font-other-normal]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['font-other-normal'])) 
                                                                echo $this->settings['icl_lang_sel_config']['font-other-normal']; 
                                                            else 
                                                                echo $this->widget_css_defaults['font-other-normal'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-font-other-normal-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-font-other-normal';cp.show('icl-font-other-normal-picker');return false;" /></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-font-other-hover" name="icl_lang_sel_config[font-other-hover]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['font-other-hover'])) 
                                                                echo $this->settings['icl_lang_sel_config']['font-other-hover']; 
                                                            else 
                                                                echo $this->widget_css_defaults['font-other-hover'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-font-other-hover-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-font-other-hover';cp.show('icl-font-other-hover-picker');return false;" /></td>
                                                    </tr>                                                
                                                    <tr>
                                                        <td><?php _e('Other languages background color', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-background-other-normal" name="icl_lang_sel_config[background-other-normal]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['background-other-normal'])) 
                                                                echo $this->settings['icl_lang_sel_config']['background-other-normal']; 
                                                            else 
                                                                echo $this->widget_css_defaults['background-other-normal'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-other-normal-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-background-other-normal';cp.show('icl-background-other-normal-picker');return false;" /></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-background-other-hover" name="icl_lang_sel_config[background-other-hover]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['background-other-hover'])) 
                                                                echo $this->settings['icl_lang_sel_config']['background-other-hover']; 
                                                            else 
                                                                echo $this->widget_css_defaults['background-other-hover'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-other-hover-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-background-other-hover';cp.show('icl-background-other-hover-picker');return false;" /></td>
                                                    </tr>                                                
                                                    <tr>
                                                        <td><?php _e('Border', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-config-border" name="icl_lang_sel_config[border]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_config']['border'])) 
                                                                echo $this->settings['icl_lang_sel_config']['border']; 
                                                            else 
                                                                echo $this->widget_css_defaults['border'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-border-picker" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-config-border';cp.show('icl-border-picker');return false;" /></td>
                                                        <td>&nbsp;</td>
                                                    </tr>
                                                    </tbody>                                                
                                                    
                                                </table>

                                                <?php _e('Presets:', 'sitepress')?>
                                                <select id="icl_lang_sel_color_scheme" name="icl_lang_sel_color_scheme">
                                                    <option value=""><?php _e('--select--', 'sitepress') ?>&nbsp;</option>
                                                    <option value="Gray"><?php _e('Gray', 'sitepress') ?>&nbsp;</option>
                                                    <option value="White"><?php _e('White', 'sitepress') ?>&nbsp;</option>                                                    
                                                    <option value="Blue"><?php _e('Blue', 'sitepress') ?>&nbsp;</option>
                                                </select>
                                                <span style="display:none"><?php _e("Are you sure? The customization you may have made will be overriden once you click 'Apply'", 'sitepress')?></span>
                                            </div>   
                                            <?php else: ?>
                                            <em><?php printf(__("%s is defined in your theme. The language switcher can only be customized using the theme's CSS.", 'sitepress'), 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS') ?></em>
                                            <?php endif; ?>
                                        </li>
										
										
										
										
										
										
										<li class="icl_advanced_feature">
                                            <h4><?php echo __('How to show widget', 'sitepress')?></h4>
                                            <p><?php echo __('Select type of appearance.', 'sitepress') ?></p>
                                            <ul>
                                                <li>
                                                    <label>
                                                        <input type="radio" name="icl_lang_sel_type" value="list" <?php if($this->settings['icl_lang_sel_type'] == 'list'):?>checked="checked"<?php endif?> />
                                                        <?php echo __('List', 'sitepress') ?>
                                                    </label>
                                                </li>
                                                <li>
                                                <label>
                                                    <input type="radio" name="icl_lang_sel_type" value="dropdown" <?php if(!$this->settings['icl_lang_sel_type'] || $this->settings['icl_lang_sel_type'] == 'dropdown'):?>checked="checked"<?php endif?> />
                                                    <?php echo __('Dropdown menu', 'sitepress') ?>
                                                </label>                    
                                                </li>
                                            </ul>
                                        </li>
<?php
		if (defined('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS')) return;
?>

										<li>
                                            <h4><?php echo __('Footer language switcher style', 'sitepress')?></h4>
                                            <ul>
                                                <li>
                                                    <label>
                                                        <input type="checkbox" name="icl_lang_sel_footer" value="1" <?php if($this->settings['icl_lang_sel_footer']):?>checked="checked"<?php endif?> />
                                                        <?php echo __('Show language switcher in footer', 'sitepress') ?>
                                                    </label>
                                                </li>
                                            </ul>
                                        </li>                                         
                                            <div id="icl_lang_sel_footer_preview_wrap" style="<?php if (!$this->settings['icl_lang_sel_footer']) echo 'display:none; '; ?>height:80px">                                            
                                            <span id="icl_lang_sel_footer_preview">                                            
                                            <h4><?php _e('Footer language switcher preview', 'sitepress')?></h4>
<?php 
		$this->footer_preview = true;
		$this->language_selector_footer(); 
?>                                                                          
                                            </span>                                                                     
                                            </div>

<?php foreach($this->color_schemes as $key=>$val): ?>
                                                <?php foreach($this->footer_css_defaults as $k=>$v): ?>                                                
                                                <input type="hidden" id="icl_lang_sel_footer_config_alt_<?php echo $key ?>_<?php echo $k ?>" value="<?php echo $this->color_schemes[$key][$k] ?>" />
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>                                            
                                            
                            
                                            
                                            <a href="#" onclick="jQuery(this).next().slideToggle();return false;" id="icl_lang_sel_footer_preview_link" <?php if (!$this->settings['icl_lang_sel_footer']) echo 'style="display:none;" '; ?>><?php _e('Edit the footer language switcher colors', 'sitepress')?></a>                                            
                                            <div style="display:none" id="icl_lang_preview_config_footer_editor_wrapper">                                          
                                                <table id="icl_lang_preview_config_footer" style="width:auto;">
                                                    <thead>
                                                    <tr>
                                                        <th>&nbsp;</th>
                                                        <th><?php _e('Normal', 'sitepress')?></th>
                                                        <th><?php _e('Hover', 'sitepress')?></th>
                                                    </tr>
                                                    </thead>
								                    <tbody>                                                
                                                    <tr>
                                                        <td><?php _e('Current language font color', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-font-current-normal" name="icl_lang_sel_footer_config[font-current-normal]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['font-current-normal'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['font-current-normal']; 
                                                            else 
                                                                echo $this->footer_css_defaults['font-current-normal'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-font-current-normal-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-font-current-normal';cp.show('icl-font-current-normal-picker-footer');return false;" /></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-font-current-hover" name="icl_lang_sel_footer_config[font-current-hover]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['font-current-hover'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['font-current-hover']; 
                                                            else 
                                                                echo $this->footer_css_defaults['font-current-hover'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-font-current-hover-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-font-current-hover';cp.show('icl-font-current-hover-picker-footer');return false;" /></td>
                                                    </tr>                                                
                                                    <tr>
                                                        <td><?php _e('Current language background color', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-background-current-normal" name="icl_lang_sel_footer_config[background-current-normal]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['background-current-normal'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['background-current-normal']; 
                                                            else 
                                                                echo $this->footer_css_defaults['background-current-normal'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-current-normal-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-background-current-normal';cp.show('icl-background-current-normal-picker-footer');return false;" /></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-background-current-hover" name="icl_lang_sel_footer_config[background-current-hover]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['background-current-hover'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['background-current-hover']; 
                                                            else 
                                                                echo $this->footer_css_defaults['background-current-hover'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-current-hover-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-background-current-hover';cp.show('icl-background-current-hover-picker-footer');return false;" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td><?php _e('Other languages font color', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-font-other-normal" name="icl_lang_sel_footer_config[font-other-normal]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['font-other-normal'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['font-other-normal']; 
                                                            else 
                                                                echo $this->footer_css_defaults['font-other-normal'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-font-other-normal-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-font-other-normal';cp.show('icl-font-other-normal-picker-footer');return false;" /></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-font-other-hover" name="icl_lang_sel_footer_config[font-other-hover]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['font-other-hover'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['font-other-hover']; 
                                                            else 
                                                                echo $this->footer_css_defaults['font-other-hover'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-font-other-hover-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-font-other-hover';cp.show('icl-font-other-hover-picker-footer');return false;" /></td>
                                                    </tr>                                                
                                                    <tr>
                                                        <td><?php _e('Other languages background color', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-background-other-normal" name="icl_lang_sel_footer_config[background-other-normal]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['background-other-normal'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['background-other-normal']; 
                                                            else 
                                                                echo $this->footer_css_defaults['background-other-normal'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-other-normal-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-background-other-normal';cp.show('icl-background-other-normal-picker-footer');return false;" /></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-background-other-hover" name="icl_lang_sel_footer_config[background-other-hover]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['background-other-hover'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['background-other-hover']; 
                                                            else 
                                                                echo $this->footer_css_defaults['background-other-hover'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-other-hover-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-background-other-hover';cp.show('icl-background-other-hover-picker-footer');return false;" /></td>
                                                    </tr> 
													
													<tr>
                                                        <td><?php _e('Background', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-background" name="icl_lang_sel_footer_config[background]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['background'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['background']; 
                                                            else 
                                                                echo $this->footer_css_defaults['border'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-background-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-background';cp.show('icl-background-picker-footer');return false;" /></td>
                                                        <td>&nbsp;</td>
                                                    </tr>
													                                              
                                                    <tr>
                                                        <td><?php _e('Border', 'sitepress')?></td>
                                                        <td><input type="text" size="7" id="icl-lang-sel-footer-config-border" name="icl_lang_sel_footer_config[border]" value="<?php 
                                                            if(isset($this->settings['icl_lang_sel_footer_config']['border'])) 
                                                                echo $this->settings['icl_lang_sel_footer_config']['border']; 
                                                            else 
                                                                echo $this->footer_css_defaults['border'] ;
                                                            ?>" /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_color_picker.png" id="icl-border-picker-footer" alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" onclick="icl_cp_target='icl-lang-sel-footer-config-border';cp.show('icl-border-picker-footer');return false;" /></td>
                                                        <td>&nbsp;</td>
                                                    </tr>
                                                    </tbody>                                                
                                                    
                                                </table>

                                                <?php _e('Presets:', 'sitepress')?>
                                                <select id="icl_lang_sel_footer_color_scheme" name="icl_lang_sel_footer_color_scheme">
                                                    <option value=""><?php _e('--select--', 'sitepress') ?>&nbsp;</option>
                                                    <option value="Gray"><?php _e('Gray', 'sitepress') ?>&nbsp;</option>
                                                    <option value="White"><?php _e('White', 'sitepress') ?>&nbsp;</option>                                                    
                                                    <option value="Blue"><?php _e('Blue', 'sitepress') ?>&nbsp;</option>
                                                </select>
                                                <span style="display:none"><?php _e("Are you sure? The customization you may have made will be overriden once you click 'Apply'", 'sitepress')?></span>
                                            </div>   
                                            <br /><br />
                                        </li>
<?php
	}
	
	function widget_list(){
		global $sitepress, $w_this_lang, $icl_language_switcher_preview;
		if($w_this_lang['code']=='all'){
    		$main_language['native_name'] = __('All languages', 'sitepress');
		}
		$active_languages = icl_get_languages('orderby=id&order=asc&skip_missing=0');
		if(empty($active_languages)) return; ?>
		
<div id="lang_sel_list"<?php if($this->settings['icl_lang_sel_type'] == 'dropdown') echo ' style="display:none;"';?>>
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?><table><tr><td><?php endif ?>            
            <ul>
                <?php foreach($active_languages as $lang): ?>
                <li class="icl-<?php echo $lang['language_code'] ?>">          
                    <a href="<?php echo $lang['url']?>"<?php if ($lang['language_code'] == $sitepress->get_current_language()) echo ' class="lang_sel_sel"'; else echo ' class="lang_sel_other"'; ?>>
                    <?php if( $this->settings['icl_lso_flags'] || $icl_language_switcher_preview):?>                
                    <img <?php if( !$this->settings['icl_lso_flags'] ):?>style="display:none"<?php endif?> class="iclflag" src="<?php echo $lang['country_flag_url'] ?>" alt="<?php echo $lang['language_code'] ?>" />&nbsp;                    
                    <?php endif; ?>
                    <?php 
                        if($icl_language_switcher_preview){
                            $lang_native = $lang['native_name'];
                            if($this->settings['icl_lso_native_lang']){
                                $lang_native_hidden = false;
                            }else{
                                $lang_native_hidden = true;
                            }
                            $lang_translated = $lang['translated_name'];
                            if($this->settings['icl_lso_display_lang']){
                                $lang_translated_hidden = false;
                            }else{
                                $lang_translated_hidden = true;
                            }                            
                        }else{
                            if($this->settings['icl_lso_native_lang']){
                                $lang_native = $lang['native_name'];
                            }else{
                                $lang_native = false;
                            }
                            if($this->settings['icl_lso_display_lang']){
                                $lang_translated = $lang['translated_name'];
                            }else{
                                $lang_translated = false;
                            }
                        }
                        echo icl_disp_language($lang_native, $lang_translated, $lang_native_hidden, $lang_translated_hidden);
                         ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>            
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?></td></tr></table></a><?php endif ?>  
</div>
<?php
	}
	
	function custom_language_switcher_style(){
        if(defined('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS') && ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS){
            return;
        }
		$this->widget_css_defaults = $this->widget_css_defaults;
        $add = false;
        foreach($this->widget_css_defaults as $key=>$d){
            if(isset($this->settings['icl_lang_sel_config'][$key]) && $this->settings['icl_lang_sel_config'][$key] != $d){
                $this->settings['icl_lang_sel_config'][$key] . "\n";
                $add = true;
                break;
            }
        }
        if($add){
			$list = ($this->settings['icl_lang_sel_type'] == 'list') ? true : false;
            echo "\n<style type=\"text/css\">";
            foreach($this->settings['icl_lang_sel_config'] as $k=>$v){
                switch($k){
                    case 'font-current-normal': 
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							echo '#lang_sel_list a.lang_sel_sel, #lang_sel_list a.lang_sel_sel:visited{color:'.$v.';}';
						else
                            echo '#lang_sel a, #lang_sel a.lang_sel_sel{color:'.$v.';}'; 
                        break;
                    case 'font-current-hover': 
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							echo '#lang_sel_list a:hover, #lang_sel_list a.lang_sel_sel:hover{color:'.$v.';}';
						else
                            echo '#lang_sel a:hover, #lang_sel a.lang_sel_sel:hover{color:'.$v.';}';
                        break;
                    case 'background-current-normal': 
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							echo '#lang_sel_list a.lang_sel_sel, #lang_sel_list a.lang_sel_sel:visited{background-color:'.$v.';}';
						else
							echo '#lang_sel a.lang_sel_sel, #lang_sel a.lang_sel_sel:visited{background-color:'.$v.';}'; 
                        break;
                    case 'background-current-hover': 
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							echo '#lang_sel_list a.lang_sel_sel:hover{background-color:'.$v.';}'; 
						else
                            echo '#lang_sel a.lang_sel_sel:hover{background-color:'.$v.';}'; 
                        break;
                    case 'font-other-normal':
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							echo '#lang_sel_list ul a.lang_sel_other, #lang_sel_list ul a.lang_sel_other:visited{color:'.$v.';}'; 
						else
                            echo '#lang_sel li ul a, #lang_sel li ul a:visited{color:'.$v.';}'; 
                        break;
                    case 'font-other-hover': 
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							echo '#lang_sel_list ul a.lang_sel_other:hover{color:'.$v.';}'; 
						else
                            echo '#lang_sel li ul a:hover{color:'.$v.';}'; 
                        break;
                    case 'background-other-normal': 
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							 echo '#lang_sel_list ul a.lang_sel_other, #lang_sel_list ul a.lang_sel_other:visited{background-color:'.$v.';}'; 
						else
                            echo '#lang_sel li ul a, #lang_sel li ul a:visited{background-color:'.$v.';}'; 
                        break;
                    case 'background-other-hover': 
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							 echo '#lang_sel_list ul a.lang_sel_other:hover{background-color:'.$v.';}'; 
						else
                            echo '#lang_sel li ul a:hover{background-color:'.$v.';}'; 
                        break;
                    case 'border': 
                        //if($v != $this->widget_css_defaults[$k])
						if ($list)
							 echo '#lang_sel_list a, #lang_sel_list a:visited{border-color:'.$v.';} #lang_sel_list  ul{border-top:1px solid '.$v.';}';
						else
                            echo '#lang_sel a, #lang_sel a:visited{border-color:'.$v.';} #lang_sel ul ul{border-top:1px solid '.$v.';}';
                        break;
                    
                }
            }
            echo "</style>\n";
        }
    }
}

global $icl_language_switcher;
$icl_language_switcher = new SitePressLanguageSwitcher;