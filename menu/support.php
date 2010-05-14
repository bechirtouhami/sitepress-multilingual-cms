<?php

if (!class_exists('WP_Http')) {
	include_once(ABSPATH . WPINC. '/class-http.php');
}
require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';

class SitePress_Support {

	var $data;
	var $site_ID = 0;
	var $tickets = array();
	var $fetched_tickets = array();
	var $defaults = array();
	var $request;
	var $initial;

	function __construct() {
		global $sitepress, $sitepress_settings;
		$sp_settings = get_option('icl_sitepress_settings');
		if (!$sitepress->icl_account_configured()) {
			$this->create_account();
			return;
		}
		if (isset($_POST['icl_support_account']) && $sitepress->icl_account_configured()) {
			$sitepress->save_settings(array('support_icl_account_created' => 1));
			echo '<script type="text/javascript">location.href = "admin.php?page=sitepress-multilingual-cms/menu/support.php";</script>';
		}
		$this->request = new WP_Http;
		
		//$this->site_id = $sitepress_settings['site_id'];
		//$this->access_key = $sitepress_settings['access_key'];
		
		$this->site_id = $sp_settings['site_id'];
		$this->access_key = $sp_settings['access_key'];
		
		$this->data = get_option('icl_support', $defaults);
		if (isset($this->data['tickets'])) {
			$this->tickets = $this->data['tickets'];
		}
		if ($this->check_subscription()) {
			$url = 'websites/' . $this->site_id . '/new_ticket';
			echo '<p>' . $this->thickbox($url) . __('Create new ticket', 'sitepress') . '</a></p>';
			$this->get_tickets();
			if (!empty($this->tickets)) {
				$this->render_tickets();
			}
		}
	}

	function request($url) {
		$result = $this->request->request($url);
		if (!is_object($result)) {
			return xml2array($result['body'], 1);
		} else {
			return array();
		}
	}

	function thickbox($url, $class = null, $id = null) {
		global $sitepress;
		return $sitepress->create_icl_popup_link(ICL_API_ENDPOINT . '/' . $url, 'ICanLocalize', $class, $id);
	}

	function process_tickets($tickets) {
		if (isset($tickets['support_ticket'][0])) {
			$tickets = $tickets['support_ticket'];
		}
		foreach ($tickets as $k => $v) {
			$temp[$v['attr']['id']] = $v['attr'];
		}
		return $temp;
	}

	function check_subscription() {
		$url = ICL_API_ENDPOINT . '/subscriptions.xml?wid=' . $this->site_id . '&accesskey=' . $this->access_key;
		$result = $this->request($url);
		$subscriptions = $result['info']['subscriptions'];
		
		if (empty($subscriptions)) {
			$this->offer_subscription();
			return false;
		} else {
			if (isset($subscriptions['subscription'][0])) {
				$subscriptions = $subscriptions['subscription'];
			}
			foreach($subscriptions as $k => $v) {
				if ($v['attr']['owner_id'] == $this->site_id && $v['attr']['valid'] == 'true') {
					printf(__('Your subscription is valid until %s', 'sitepress'), date(get_option('date_format'), $v['attr']['expires_date']));
					return true;
				}
				if ($v['attr']['owner_id'] == $this->site_id && $v['attr']['valid'] == 'false') {
					$this->offer_renewal();
					return false;
				}
			}
			$this->offer_subscription();
			return false;
		}
	}

	function offer_subscription() {
		echo '<p>';
		printf(__('In order to get premium support, you need to create a support subscription.
<br />A support subscription gives you 24h response directly from WPML\'s developers.
<br /><br />
Please choose which support subscription is best for you:
<br /><br />
%s Single site support %s - $50 / year (good for this site only)
<br />
%s Developer support (unlimited sites) %s - $200 / year (good for any site you build)', 'sitepress'), $this->thickbox('subscriptions/new?wid=' . $this->site_id . '&amp;code=1'), '</a>', $this->thickbox('subscriptions/new?wid=' . $this->site_id . '&amp;code=2'), '</a>');
		echo '</p>';
	}

	function offer_renewal() {
		echo '<p>';
		printf(__('Renew your licence', 'sitepress'));
		echo '</p>';
		$this->offer_subscription();
	}

	function get_tickets() {
		$url = ICL_API_ENDPOINT . '/support.xml?wid=' . $this->site_id . '&accesskey=' . $this->access_key;
		$result = $this->request($url);
		if (isset($result['info']['support_tickets']['support_ticket'])) {
			$this->fetched_tickets = $this->process_tickets($result['info']['support_tickets']);
		} else {
			return array();
		}
		if (empty($this->tickets)) {
			$this->data['tickets'] = $this->tickets = $this->fetched_tickets;
			update_option('icl_support', $this->data);
			$this->initial = true;
		}
		foreach ($this->fetched_tickets as $id => $v) {
			if (!isset($this->tickets[$id]) && $v['status'] !== 0) {
				$this->data['tickets'][$id] = $this->tickets[$id] = $this->fetched_tickets[$id];
				update_option('icl_support', $this->data);
			}
		}
	}

	function render_tickets() {
		//[messages] [status] [subject] [create_time] [id]
?>
		<table id="icl_support_table" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th><?php _e('Subject', 'sitepress'); ?></th>
					<th><?php _e('Created', 'sitepress'); ?></th>
					<th><?php _e('Messages', 'sitepress'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th><?php _e('Subject', 'sitepress'); ?></th>
					<th><?php _e('Created', 'sitepress'); ?></th>
					<th><?php _e('Messages', 'sitepress'); ?></th>
                </tr>
            </tfoot>        
            <tbody>
<?php
		$url = 'support/show/';
		$updated_tickets = '';
		$tickets = '';
		
		foreach ($this->tickets as $id => $v) {
			if (!isset($this->fetched_tickets[$id]) || $this->fetched_tickets[$id]['status'] === 0) {
				unset($this->data['tickets'][$id]);
				$update = true;
				continue;
			}
			if (!$this->initial && $v['messages'] != $this->fetched_tickets[$id]['messages']) {
				$check_user_message = $this->request(ICL_API_ENDPOINT . '/' . $url . $v['id'] . '.xml?wid=' . $this->site_id . '&accesskey=' . $this->access_key);
				if ($check_user_message['info']['support_ticket']['attr']['last_message_by_user'] == 'true') {
					$tickets .= '<tr><td>' . $this->thickbox($url . $v['id']) . $v['subject'] . '</a></td><td>' . date(get_option('date_format'), $v['create_time']) . '</td><td>' . $this->fetched_tickets[$id]['messages'] . '</td></tr>';
					$this->data['tickets'][$id]['messages'] = $this->fetched_tickets[$id]['messages'];
					$update = true;
					continue;
				}
				$add = ' style="background-color: Yellow;"';
				$add3 = ' icl_support_viewed';
				$add2 = '<strong><span style="color: Red;">' . __('New message', 'sitepress') . '</span></strong>';
				$add4 = 'icl_support_ticket_' . $v['id'] . '_' . $this->fetched_tickets[$id]['messages'];
				$updated_tickets .= '<tr'.$add.'><td>' . $this->thickbox($url . $v['id'], $add3, $add4) . $v['subject'] . '</a></td><td>' . date(get_option('date_format'), $v['create_time']) . '</td><td>' . $this->fetched_tickets[$id]['messages'] . '&nbsp;' . $add2 . '</td></tr>';
			} else {
				$tickets .= '<tr><td>' . $this->thickbox($url . $v['id']) . $v['subject'] . '</a></td><td>' . date(get_option('date_format'), $v['create_time']) . '</td><td>' . $v['messages'] . '</td></tr>';
			}
		}
		
		echo $updated_tickets . $tickets;
		if ($update) {
			update_option('icl_support', $this->data);
		}
?>
			</tbody>
	</table>
<?php
	}

	function create_account() {
		global $sitepress, $sitepress_settings;
		//$icl_account_ready_errors = $sitepress->icl_account_reqs();
?>             

                                <?php if(isset($_POST['icl_form_errors']) || ($icl_account_ready_errors && !$sitepress->icl_account_configured() )):  ?>
                                <div class="icl_form_errors">
                                    <?php echo $_POST['icl_form_errors'] ?>
                                    <?php if($icl_account_ready_errors):  ?>
                                    <?php echo __('Before you create an ICanLocalize account you need to fix these:', 'sitepress'); ?>
                                    <ul>
                                    <?php foreach($icl_account_ready_errors as $err):?>        
                                    <li><?php echo $err ?></li>    
                                    <?php endforeach ?>
                                    </ul>   
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <!--admin.php?page=<?php echo ICL_PLUGIN_FOLDER  ?>/menu/content-translation.php#icl_create_account_form-->
                                <form id="icl_create_account" method="post" action="" <?php if($_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
                                <?php wp_nonce_field('icl_create_account', 'icl_create_account_nonce') ?>    
								<input type="hidden" name="icl_support_account" value="1" />

                                <p style="line-height:1.5"><?php _e('To get premium support, you will need to create an account at ICanLocalize.<br />WPML will use this account to create support tickets and connect you with the development team.', 'sitepress'); ?></p>
                                
                                <table class="form-table icl-account-setup">
                                    <tbody>
                                    <tr class="form-field">
                                        <th scope="row"><?php echo __('First name', 'sitepress')?></th>
                                        <td><input name="user[fname]" type="text" value="<?php echo $_POST['user']['fname']?$_POST['user']['fname']:$current_user->first_name ?>" /></td>
                                    </tr>
                                    <tr class="form-field">
                                        <th scope="row"><?php echo __('Last name', 'sitepress')?></th>
                                        <td><input name="user[lname]" type="text" value="<?php echo  $_POST['user']['lname']?$_POST['user']['lname']:$current_user->last_name ?>" /></td>
                                    </tr>        
                                    <tr class="form-field">
                                        <th scope="row"><?php echo __('Email', 'sitepress')?></th>
                                        <td><input name="user[email]" type="text" value="<?php echo  $_POST['user']['email']?$_POST['user']['email']:$current_user->data->user_email ?>" /></td>
                                    </tr>
                                    </tbody>
                                </table>
                                <?php if(!$sitepress_settings['content_translation_setup_complete']): ?>        
                                    <p class="submit">                                        
                                        <a href="javascript:;" onclick="jQuery('#icl_create_account').hide();jQuery('#icl_configure_account').fadeIn();"><?php echo __('I already have an account at ICanLocalize', 'sitepress') ?></a><br /><br />

                                            <?php //Hidden button for catching "Enter" key ?>                                            
                                            <input id="icl_content_trans_setup_finish_enter" class="button-primary" name="icl_content_trans_setup_finish_enter" value="<?php echo __('Log in to my account', 'sitepress') ?>" type="submit" style="display:none"/>
                                            <input id="icl_content_trans_setup_finish" class="button-primary" name="icl_content_trans_setup_finish" value="<?php echo __('Create account', 'sitepress') ?>" type="submit" />

                                    </p>
                                    <div class="icl_progress"><?php _e('Saving. Please wait...', 'sitepress'); ?></div>
                                <?php else: ?>
                                    <p class="submit">
                                        <input type="hidden" name="create_account" value="1" />
                                        <input class="button" name="create account" value="<?php echo __('Create account', 'sitepress') ?>" type="submit" 
                                            <?php if($icl_account_ready_errors):  ?>disabled="disabled"<?php endif; ?> />
                                        <a href="javascript:;" onclick="jQuery('#icl_create_account').hide();jQuery('#icl_configure_account').fadeIn();"><?php echo __('I already have an account at ICanLocalize', 'sitepress') ?></a>                                        
                                    </p>
                                    <div class="icl_progress"><?php _e('Saving. Please wait...', 'sitepress'); ?></div>
                                <?php endif; ?>
                                </form> 
                <!--admin.php?page=<?php echo ICL_PLUGIN_FOLDER  ?>/menu/content-translation.php#icl_create_account_form-->
                                <form id="icl_configure_account" action="" method="post" <?php if(!$_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
                                <?php wp_nonce_field('icl_configure_account','icl_configure_account_nonce') ?>
								<input type="hidden" name="icl_support_account" value="1" />    
                                <table class="form-table icl-account-setup">
                                    <tbody>
                                    <tr class="form-field">
                                        <th scope="row"><?php echo __('Email', 'sitepress')?></th>
                                        <td><input name="user[email]" type="text" value="<?php echo  $_POST['user']['email']?$_POST['user']['email']:$current_user->data->user_email ?>" /></td>
                                    </tr>
                                    <tr class="form-field">
                                        <th scope="row"><?php echo __('Password', 'sitepress')?></th>
                                        <td><input name="user[password]" type="password" /></td>
                                    </tr>        
                                    </tbody>
                                </table>
                                <?php if(!$sitepress_settings['content_translation_setup_complete']): ?>        
                                    <p class="submit">                                        
                                        <a href="javascript:;" onclick="jQuery('#icl_configure_account').hide();jQuery('#icl_create_account').fadeIn();"><?php echo __('Create account', 'sitepress') ?></a><br /><br />                                        

                                            <?php //Hidden button for catching "Enter" key ?>
                                            <input id="icl_content_trans_setup_finish_enter" class="button-primary" name="icl_content_trans_setup_finish_enter" value="<?php echo __('Log in to my account', 'sitepress') ?>" type="submit" style="display:none"/>
                                            
                                            <!--<input class="button" name="icl_content_trans_setup_cancel" value="<?php echo __('Cancel', 'sitepress') ?>" type="button" />
                                            <input id="icl_content_trans_setup_back_2" class="button-primary" name="icl_content_trans_setup_back_2" value="<?php echo __('Back', 'sitepress') ?>" type="submit" />-->
                                            <input id="icl_content_trans_setup_finish" class="button-primary" name="icl_content_trans_setup_finish" value="<?php echo __('Log in to my account', 'sitepress') ?>" type="submit" />

                                    </p>
                                    <div class="icl_progress"><?php _e('Saving. Please wait...', 'sitepress'); ?></div>                                        
                                <?php else: ?>
                                    <p class="submit">                                        
                                        <input type="hidden" name="create_account" value="0" />                                        
                                        <input class="button" name="configure account" value="<?php echo __('Log in to my account', 'sitepress') ?>" type="submit" 
                                            <?php if($icl_account_ready_errors):  ?>disabled="disabled"<?php endif; ?> />
                                        <a href="javascript:;" onclick="jQuery('#icl_configure_account').hide();jQuery('#icl_create_account').fadeIn();"><?php echo __('Create account', 'sitepress') ?></a>                                        
                                    </p>                                    
                                    <div class="icl_progress"><?php _e('Saving. Please wait...', 'sitepress'); ?></div>
                                <?php endif; ?>
                                </form>
<?php
	}
}
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32_adv" style="background: transparent url(<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_adv.png) no-repeat;"><br /></div>
    <h2><?php _e('Support', 'sitepress') ?></h2>
<?php $icl_support = new SitePress_Support; ?>
</div>