<?php
/*
- dir mode?
BUGS
- update flag field dont get UPDATED right away
- new translations dont add
- update translations buggg
*/

class SitePressEditLanguages {

	var $active_languages;
	var $upload_dir;
	var $is_writable = false;
	var $error = false;
	var $required_fields = array('code' => '', 'english_name' => '', 'translations' => 'array', 'flag' => '', 'default_locale' => '');
	var $add_validation_failed = false;

	function __construct() {
		
			// Set upload dir
		$wp_upload_dir = wp_upload_dir();
		$this->upload_dir = $wp_upload_dir['basedir'] . '/flags';
		
		if (!is_dir($this->upload_dir)) {
			if (!mkdir($this->upload_dir)) {
				$this->error(__('Upload directory cannot be created. Check your permissions.','sitepress'));
			}
		}
		if (!$this->is_writable = is_writable($this->upload_dir)) {
			$this->error(__('Upload dir is not writable','sitepress'));
		}
		
			// Trigger save.
		if (isset($_POST['icl_edit_languages_action'])) {
			//if ($_POST['icl_edit_languages_action'] == 'insert') $this->insert();
			if ($_POST['icl_edit_languages_action'] == 'update') {
				$this->update();
			}
		}
		
		add_action('admin_footer', array(&$this,'scripts'));
		
		global $sitepress, $wpdb;
		$this->active_languages = $sitepress->get_active_languages(true);
		foreach ($this->active_languages as $lang) {
			foreach ($this->active_languages as $lang_translation) {
				$this->active_languages[$lang['code']]['translation'][$lang_translation['id']] = $sitepress->get_display_language_name($lang['code'], $lang_translation['code']);
			}
			$flag = $sitepress->get_flag($lang['code']);
			$this->active_languages[$lang['code']]['flag'] = $flag->flag;
			$this->active_languages[$lang['code']]['default_locale'] = $wpdb->get_var("SELECT default_locale FROM {$wpdb->prefix}icl_languages WHERE code='".$lang['code']."'");
		}
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32_adv"><br /></div>
    <h2><?php _e('Edit Languages', 'sitepress') ?></h2>
<?php
	if ($this->error) {
		echo '	<div class="icl_error_text" style="margin:10px;">
    	<p>'.$this->error.'</p>
	</div>'; 
	}
?>
	<br />
	<?php $this->edit_table(); ?>
</div>
<?php
	}

	function edit_table(){
?>
	<form enctype="multipart/form-data" action="" method="post">
	<input type="hidden" name="icl_edit_languages_action" value="update" />
	<input type="hidden" name="icl_edit_languages_ignore_add" id="icl_edit_languages_ignore_add" value="<?php echo ($this->add_validation_failed) ? 'false' : 'true'; ?>" />
	<table id="icl_edit_languages_table" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <!--<th scope="col" class="manage-column column-cb check-column"><input type="checkbox" checked="checked" name="" /></th>-->
                    <th><?php _e('Language name', 'sitepress'); ?></th>
					<th><?php _e('Code', 'sitepress'); ?></th>
					<th <?php if (!$this->add_validation_failed) echo 'style="display:none;" ';?>class="icl_edit_languages_show"><?php _e('Translation (new)', 'sitepress'); ?></th>
					<?php foreach ($this->active_languages as $lang) { ?>
					<th><?php _e('Translation', 'sitepress'); ?> (<?php echo $lang['english_name']; ?>)</th>
					<?php } ?>
					<th><?php _e('Flag', 'sitepress'); ?></th>
					<th><?php _e('Default locale', 'sitepress'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <!--<th scope="col" class="manage-column column-cb check-column"><input type="checkbox" checked="checked" name="" /></th>-->
                    <th><?php _e('Language name', 'sitepress'); ?></th>
					<th><?php _e('Code', 'sitepress'); ?></th>
					<th <?php if (!$this->add_validation_failed) echo 'style="display:none;" ';?>class="icl_edit_languages_show"><?php _e('Translation (new)', 'sitepress'); ?></th>
					<?php foreach ($this->active_languages as $lang) { ?>
					<th><?php _e('Translation', 'sitepress'); ?> (<?php echo $lang['english_name']; ?>)</th>
					<?php } ?>
					<th><?php _e('Flag', 'sitepress'); ?></th>
					<th><?php _e('Default locale', 'sitepress'); ?></th>
                </tr>
            </tfoot>        
            <tbody>
<?php
		foreach ($this->active_languages as $lang) {
			$this->table_row($lang);
		}
		if ($this->add_validation_failed) {
			$_POST['icl_edit_languages']['add']['id'] = 'add';
			$new_lang = $_POST['icl_edit_languages']['add'];
		} else {
			$new_lang = array('id'=>'add');
		}
		$this->table_row($new_lang,true,true);
?>
			</tbody>
	</table>
	<p class="submit alignleft"><a href="/wp-admin/admin.php?page=sitepress-multilingual-cms/menu/languages.php" class="button-primary" /><?php _e('Back to languages', 'sitepress'); ?></a></p>

	<p class="submit alignright"><input type="button" name="icl_edit_languages_add_language_button" id="icl_edit_languages_add_language_button" value="<?php _e('Add Language', 'sitepress'); ?>" class="button-primary"<?php if ($this->add_validation_failed) { ?> style="display:none;"<?php } ?> />&nbsp;<input type="button" name="icl_edit_languages_cancel_button" id="icl_edit_languages_cancel_button" value="<?php _e('Cancel', 'sitepress'); ?>" class="button-primary icl_edit_languages_show"<?php if (!$this->add_validation_failed) { ?> style="display:none;"<?php } ?> />&nbsp;<input type="submit" value="<?php _e('Update', 'sitepress'); ?>" /></p>
    <br clear="all" />
	</form>

<?php
	}

	function table_row( $lang, $echo = true, $add = false ){ ?>
		
		<tr style="<?php if ($add && !$this->add_validation_failed) echo 'display:none; '; if ($add) echo 'background-color:yellow; '; ?>" <?php if ($add) echo '" class="icl_edit_languages_show"'; ?>>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][english_name]" value="<?php echo $lang['english_name']; ?>" /></td>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][code]" value="<?php echo $lang['code']; ?>" style="width:40px;" /></td>
					<td <?php if (!$this->add_validation_failed) echo 'style="display:none;" ';?>class="icl_edit_languages_show"><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][translations][add]" value="<?php echo  $_POST['icl_edit_languages'][$lang['id']]['translations']['add']; ?>" /></td>
					<?php foreach($this->active_languages as $translation){ 
						if ($lang['id'] == 'add') {
							$value = $_POST['icl_edit_languages']['add']['translations'][$translation['code']];
						} else {
							$value = $lang['translation'][$translation['id']];
						}
					?>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][translations][<?php echo $translation['code']; ?>]" value="<?php echo $value; ?>" /></td>
					<?php } ?>
					<td>
					<?php if ($this->is_writable) { ?>
					<input type="button" class="icl_edit_languages_switch_upload" value="<?php _e('Upload','sitepress'); ?>" style="font-size: 9px; width:55px;" /><input type="hidden" name="icl_edit_languages[<?php echo $lang['id']; ?>][flag_upload]" class="icl_edit_languages_flag_upload" value="false" /><input type="hidden" name="MAX_FILE_SIZE" value="100000" /><input name="icl_edit_languages[<?php echo $lang['id']; ?>][flag_file]" class="icl_edit_languages_flag_upload_field" style="display:none;" type="file" />&nbsp;<?php } ?><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][flag]" value="<?php echo $lang['flag']; ?>" class="icl_edit_languages_flag_enter_field" style="width:60px;" /></td>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][default_locale]" value="<?php echo $lang['default_locale']; ?>" style="width:60px;" /></td>
				</tr>
<?php
	}

	function scripts(){
?>
		<style type="text/css">#icl_edit_languages_table input { width:80px; }</style>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#icl_edit_languages_add_language_button").click(function(){
					jQuery(this).fadeOut('fast',function(){jQuery("#icl_edit_languages_table tr:last, .icl_edit_languages_show").show();});
					jQuery('#icl_edit_languages_ignore_add').val('false');
				});
				jQuery("#icl_edit_languages_cancel_button").click(function(){
					jQuery(this).fadeOut('fast',function(){
						jQuery("#icl_edit_languages_add_language_button").show();
						jQuery(".icl_edit_languages_show").hide();
						jQuery("#icl_edit_languages_table tr:last input").each(function(){
							jQuery(this).val('');
						});
						jQuery('#icl_edit_languages_ignore_add').val('true');
					});
				});
				jQuery('.icl_edit_languages_switch_upload').toggle(function() {
						jQuery(this).val('<?php _e('Use field','sitepress'); ?>');
						jQuery(this).parent().children('.icl_edit_languages_flag_upload').val('true');
						jQuery(this).parent().children('.icl_edit_languages_flag_enter_field').hide();
  						jQuery(this).parent().children('.icl_edit_languages_flag_upload_field').show();
					}, function() {
						jQuery(this).val('<?php _e('Upload','sitepress'); ?>');
						jQuery(this).parent().children('.icl_edit_languages_flag_upload').val('false');
						jQuery(this).parent().children('.icl_edit_languages_flag_upload_field').hide();
 						jQuery(this).parent().children('.icl_edit_languages_flag_enter_field').show();
					});
			});
		</script>
<?php
	}

	function update() {
			// Basic check.
		if (!isset($_POST['icl_edit_languages']) || !is_array($_POST['icl_edit_languages'])){
			$this->error(__('Please, enter valid data.','sitepress'));
			return;
		}
		
		global $sitepress,$wpdb;
		
			// First check if add and validate it.
		if (isset($_POST['icl_edit_languages']['add']) && $_POST['icl_edit_languages_ignore_add'] == 'false') {
			if ($this->validate_one('add', $_POST['icl_edit_languages']['add'])) {
				$this->insert_one($_POST['icl_edit_languages']['add']);
			}
		}
		
		foreach ($_POST['icl_edit_languages'] as $id => $data){
			
				// Ignore insert.
			if ($id == 'add') { continue; }
			
				// Validate and sanitize data.
			if (!$this->validate_one($id, $data)) continue;
			$data = $this->sanitize($data);
			
				// Update main table.
			$wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET code='".$data['code']."', english_name='".$data['english_name']."', default_locale='".$data['default_locale']."'  WHERE ID = ".$id);
			
			
			
			
				// Update translations table.
			foreach ($data['translations'] as $translation_code => $translation_value) {
				
					// If new (add language) translations are submitted.
				if ($translation_code == 'add') {
					if ($this->add_validation_failed) {
						continue;
					}
						// Check if there is new language.
					if (!isset($_POST['icl_edit_languages']['add']['code']) || empty($_POST['icl_edit_languages']['add']['code'])) {
						continue;
					} else {
						$translation_code = $_POST['icl_edit_languages']['add']['code'];
					}
				}
				
					// Check if update.
				if ($wpdb->get_var("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='".$translation_code."' AND display_language_code='".$data['code']."'")) {
					$wpdb->query("UPDATE {$wpdb->prefix}icl_languages_translations SET name='".$translation_value."' WHERE language_code = '".$data['code']."' AND display_language_code = '".$translation_code."'");
				} else {
					$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$translation_value."', '".$data['code']."', '".$translation_code."')");
				}
			}
			
			
			
			
				// Handle flag.
			if ($data['flag_upload'] == 'true' && !empty($_FILES['icl_edit_languages']['name'][$id]['flag_file'])) {
				if ($filename = $this->upload_flag($id, $data)) {
					$data['flag'] = $filename;
					$from_template = 1;
				} else {
					$from_template = 0;
				}
			} else {
				$from_template = 0;
			}
				// Update flag table.
			$wpdb->query("UPDATE {$wpdb->prefix}icl_flags SET flag='".$data['flag']."',from_template=".$from_template." WHERE lang_code = '".$data['code']."'");
		}
			// Refresh cache;
		$sitepress->icl_language_name_cache->clear();
		delete_option('_icl_cache');
	}

	function insert_one($data) {
		global $sitepress,$wpdb;
		
			// Insert main table.
		$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages (code, english_name, default_locale, major, active) VALUES('".$data['code']."', '".$data['english_name']."', '".$data['default_locale']."', 0, 1)");
		
			// Insert translations.
		$all_languages = $sitepress->get_languages();
		foreach ($all_languages as $key => $lang) {
			
				// If submitted.
			if (array_key_exists($lang['code'], $data['translations'])) {
				$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$data['translations'][$lang['code']]."', '".$data['code']."', '".$lang['code']."')");
				continue;
			}
			
				// Insert dummy translation.
			$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$data['english_name']."', '".$data['code']."', '".$lang['code']."')");
			
			/*if (!$this->added_translation[$lang['code']]){
				$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$lang['english_name']."', '".$lang['code']."', '".$data['code']."')");
			}*/
		}
		
			// Insert native name.
		if (!isset($data['translations']['add']) || empty($data['translations']['add'])) {
			$data['translations']['add'] = $data['english_name'];
		}
		$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$data['translations']['add']."', '".$data['code']."', '".$data['code']."')");
		
			// Handle flag.
		if ($data['flag_upload'] == 'true' && !empty($_FILES['icl_edit_languages']['name']['add']['flag_file'])) {
			if ($filename = $this->upload_flag('add', $data)) {
				$data['flag'] = $filename;
				$from_template = 1;
			} else {
				$from_template = 0;
			}
		} else {
			$from_template = 0;
		}
		
			// Insert flag table.
		$wpdb->query("INSERT INTO {$wpdb->prefix}icl_flags (lang_code, flag, from_template) VALUES('".$data['code']."', '".$data['flag']."', ".$from_template.")");
	}


	function validate_one($id, $data) { //print_r($_POST); 
	
		global $wpdb;
		
		// Validation
			// If insert, check if languge code (unique) exists.
		if ($exists = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='".$data['code']."'") && $id == 'add') {
			$this->error = __('Language code exists','sitepress');
			$this->add_validation_failed = true;
			return false;
			
			// Illegal change of code
		} else if ($exists && $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='".$data['code']."' AND id=".$data['id']) != $data['code']) {
			$this->error = __('Language code exists','sitepress');
			if ($id == 'add') $this->add_validation_failed = true;
			return false;
		}
		
		foreach ($this->required_fields as $name => $type) {
			if ($name == 'flag') {
				$check = ($data['flag_upload'] == 'true') ? $_FILES['icl_edit_languages']['name'][$id]['flag_file'] : $_POST['icl_edit_languages'][$id]['flag'];
				if ($this->check_extension($check)){
					continue;
				} else {
					if ($id == 'add') {
						$this->add_validation_failed = true;
					}
					return false;
				}
			}
			if (!isset($_POST['icl_edit_languages'][$id][$name]) || empty($_POST['icl_edit_languages'][$id][$name])) {
				if ($_POST['icl_edit_languages_ignore_add'] == 'true') {
					return false;
				}
				$this->error(__('Please, enter required data.','sitepress'));
				if ($id == 'add') {
					$this->add_validation_failed = true;
				}
				return false;
			}
			if ($type == 'array' && !is_array($_POST['icl_edit_languages'][$id][$name])) {
				if ($id == 'add') {
					$this->add_validation_failed = true;
				}
				$this->error(__('Please, enter valid data.','sitepress')); return false;
			}
		}
		return true;
	}

	function sanitize($data) {
		global $wpdb;
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $k => $v) {
					$data[$key][$k] = $wpdb->escape($v);
				}
			}
			$data[$key] = $wpdb->escape($value);
		}
		return $data;
	}

	function check_extension($file) {
		$extension = substr($file, strrpos($file, '.') + 1);
		if (!in_array($extension,array('png','gif','jpg'))) {
			$this->error(__('File extension not allowed.','sitepress'));
			return false;
		}
		return true;
	}

	function error($str = false) {
		if (!$this->error) {
			$this->error = '';
		}
		$this->error .= $str . '<br />';
	}

	function upload_flag($id, $data) {
		$filename = basename($_FILES['icl_edit_languages']['name'][$id]['flag_file']);
		$target_path = $this->upload_dir . '/' . $filename;
		if (move_uploaded_file($_FILES['icl_edit_languages']['tmp_name'][$id]['flag_file'], $target_path) ) {
			//echo 'success';
    		return $filename;
		} else {
    		$this->error(__('There was an error uploading the file, please try again!','sitepress'));
			return false;
		}
	}

}

$icl_edit_languages = new SitePressEditLanguages;










function icl_legacy_insert(){ // NOT USED
		if (!isset($_POST['icl_edit_languages'])) return;
		global $sitepress,$wpdb;
			// Check if languge code (unique) exists.
		if ($wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='".$_POST['icl_edit_languages']['code']."'")){
			$this->error = __('Language code exists','sitepress');
			return;
		}
			// Insert main table.
		$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages (code, english_name, default_locale, major, active) VALUES('".$_POST['icl_edit_languages']['code']."', '".$_POST['icl_edit_languages']['english_name']."', '".$_POST['icl_edit_languages']['default_locale']."', 0, 1)");
		
		// Insert dummy translations.
		$all_languages = $sitepress->get_languages();
		foreach ($all_languages as $key => $lang){
			if (array_key_exists($lang['code'],$_POST['icl_edit_languages']['translations'])){
				$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$_POST['icl_edit_languages']['translations'][$lang['code']]."', '".$_POST['icl_edit_languages']['code']."', '".$lang['code']."')");
				continue;
			}
			$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$_POST['icl_edit_languages']['english_name']."', '".$_POST['icl_edit_languages']['code']."', '".$lang['code']."')");
		}
			// Insert translations table.
		/*foreach ($_POST['icl_edit_languages']['translations'] as $translation_code => $translation_value){
			$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$translation_value."', '".$_POST['icl_edit_languages']['code']."', '".$translation_code."')");
		}*/
			// Insert translations table native name.
		/*foreach ($_POST['icl_edit_languages']['translations'] as $translation_code => $translation_value){
			$wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$translation_value."', '".$_POST['icl_edit_languages']['code']."', '".$translation_code."')");
		}*/
			// Insert flag table.
		$wpdb->query("INSERT INTO {$wpdb->prefix}icl_flags (lang_code, flag, from_template) VALUES('".$_POST['icl_edit_languages']['code']."', '".$_POST['icl_edit_languages']['flag']."', 0)");
		
		$sitepress->icl_language_name_cache->clear();
		delete_option('_icl_cache');
		// TODO:re-create cache
	}


	function icl_legacy_add_language(){
?>
		<form enctype="multipart/form-data" action="/wp-admin/admin.php?page=sitepress-multilingual-cms/menu/languages.php?trop=1" method="post">
			<input type="hidden" name="icl_edit_languages_action" value="insert" />
			<input type="text" name="icl_edit_languages[english_name]" />&nbsp;<?php _e('Language name', 'sitepress'); ?><br />
			<input type="text" name="icl_edit_languages[code]" />&nbsp;<?php _e('Language code', 'sitepress'); ?><br />
			<input type="text" name="icl_edit_languages[native_name]" />&nbsp;<?php _e('Native name', 'sitepress'); ?><br />
			<?php foreach($this->active_languages as $lang){ ?>
			<input type="text" name="icl_edit_languages[translations][<?php echo $lang['code']; ?>]" />&nbsp;<?php _e('Translation', 'sitepress'); ?> (<?php echo $lang['english_name']; ?>)<br />
			<?php } ?>
			<input type="text" name="icl_edit_languages[flag]" />&nbsp;<?php _e('Flag', 'sitepress'); ?><br />
			<input type="text" name="icl_edit_languages[default_locale]" />&nbsp;<?php _e('Default locale', 'sitepress'); ?><br />
			<p class="submit alignleft"><input type="submit" value="<?php _e('Add language', 'sitepress'); ?>" /></p>
   			 <br clear="all" />
		</form>
<?php
	}
	
			/*$return = '<tr';
		if ($hide) $return .= ' style="display:none"';
		$return .= '>
					<td><input type="text" name="icl_edit_languages['.$lang['id'].'][english_name]" value="'. $lang['english_name'].'" /></td>
					<td><input type="text" name="icl_edit_languages['.$lang['id'].'][code]" value="'.$lang['code'].'" /></td>';
		foreach($this->active_languages as $translation){
			$return .= '
					<td><input type="text" name="icl_edit_languages['.$lang['id'].'][translations]['.$translation['code'].']" value="'. $lang['translation'][$translation['id']] .'" /></td>';
		}
		$return .= '
					<td><input type="text" name="icl_edit_languages['. $lang['id'] .'][flag]" value="'. $lang['flag'] .'" /></td>
					<td><input type="text" name="icl_edit_languages[' .$lang['id'] .'][default_locale]" value="'. $lang['default_locale'] .'" /></td>
				</tr>';
		if ($echo) echo $return;
		else return $return;*/