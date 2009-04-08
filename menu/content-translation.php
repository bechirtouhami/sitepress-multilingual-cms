<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php';     
    $active_languages = $sitepress->get_active_languages();            
    $sitepress_settings = $sitepress->get_settings();
    $icl_account_ready_errors = $sitepress->icl_account_reqs();
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>    
       
    <?php if(count($active_languages) > 1): ?>
        <h3><?php echo __('Translations pairs','sitepress') ?></h3>    
        <form id="icl_language_pairs_form" name="icl_language_pairs_form" action="">
        <ul id="icl_language_pairs" >    
            <?php foreach($active_languages as $lang): ?>            
                <li>
                    <label><input class="icl_tr_from" type="checkbox" name="icl_lng_from_<?php echo $lang['code']?>" id="icl_lng_from_<?php echo $lang['code']?>" <?php if($sitepress->get_icl_translation_enabled($lang['code'])): ?>checked="checked"<?php endif?> />
                    <?php printf(__('Translate from %s to these languages','sitepress'), $lang['display_name']) ?></label>
                    <ul id="icl_tr_pair_sub_<?php echo $lang['code'] ?>" <?php if(!$sitepress->get_icl_translation_enabled($lang['code'])): ?>style="display:none"<?php endif?>>
                    <?php foreach($active_languages as $langto): if($lang['code']==$langto['code']) continue; ?>        
                        <li>
                            <label><input class="icl_tr_to" type="checkbox" name="icl_lng_to_<?php echo $lang['code']?>_<?php echo $langto['code']?>" id="icl_lng_from_<?php echo $lang['code']?>_<?php echo $langto['code']?>" <?php if($sitepress->get_icl_translation_enabled($lang['code'],$langto['code'])): ?>checked="checked"<?php endif?> />
                            <?php echo $langto['display_name'] ?></label>
                        </li>    
                    <?php endforeach; ?>
                    </ul>
                </li>    
            <?php endforeach; ?>
        </ul>    
        <input id="icl_save_language_pairs" type="button" class="button-secondary action" value="<?php echo __('Save', 'sitepress') ?>" />
        <span class="icl_ajx_response" id="icl_ajx_response"></span>    
        </form>
        <br clear="all" />    
        
        <h3><?php echo __('Translation options','sitepress') ?></h3>    
        <form name="icl_more_options" action="">
        <table class="form-table icl-account-setup">
        <tr>
            <td>
                <label><input name="icl_notify_before_translations" type="checkbox" 
                <?php if($sitepress_settings['notify_before_translations']): ?>checked="checked"<?php endif; ?> /> 
                <?php echo __('Notify before sending translation jobs', 'sitepress') ?></label>
            </td>
            <td>
                <label><input name="icl_translate_new_content" type="checkbox" 
                <?php if($sitepress_settings['translate_new_content']): ?>checked="checked"<?php endif; ?> /> 
                <?php echo __('Translate new contents when published', 'sitepress') ?></label>
            </td>
        </tr>       
        <tr>        
            <td>
                <label><input name="icl_interview_translators" type="radio" value="0" <?php if(!$sitepress_settings['interview_translators']): ?>checked="checked"<?php endif;?> /> <?php echo __('ICanLocalize will assign translators for this work', 'sitepress'); ?></label><br />
                <label><input name="icl_interview_translators" type="radio" value="1" <?php if($sitepress_settings['interview_translators']): ?>checked="checked"<?php endif;?> /> <?php echo __('I want to interview my translators', 'sitepress'); ?></label>
            </td>
        </tr>      
        </table>
        <p class="submit">
            <input class="button" name="create account" value="<?php echo __('Save', 'sitepress') ?>" type="submit" />
            <span class="icl_ajx_response" id="icl_ajx_response2"></span>    
        </p>
        </form>
        
        <h3 id="icleditoraccount"><?php echo __('Editor account for WordPress access','sitepress') ?></h3>        
        <form name="icl_editor_account" action="">
        <p class="icl_form_errors" style="display:none"></p>
        <table class="form-table icl-account-setup">
        <tr class="form-field">
            <th scope="row">CMS User</th>
            <td><input name="user[cms_login]" type="text" value="<?php echo $sitepress_settings['cms_login']?>" size="32" style="width:200px;" /></td>
        </tr>
        <tr class="form-field">
            <th scope="row">CMS Password</th>
            <td><input name="user[cms_password]" type="password" value="<?php echo $sitepress_settings['cms_password']?>"  size="32" style="width:200px;" /></td>
        </tr>  
        <tr>
            <td colspan="2"><?php echo __('* You need to supply the credentials of an editor account in order for our system to fetch contents and returns translations to WordPress','sitepress')?></td>
        </tr>          
        </table>
        <p class="submit">
            <input class="button" name="editor_account" value="<?php echo __('Save', 'sitepress') ?>" type="submit" />
            <span class="icl_ajx_response" id="icl_ajx_response3"></span>    
        </p>
        </form>    
        
        <h3 id="icl_create_account_form"><?php echo __('Configure your ICanLocalize account', 'sitepress') ?></h3>             
        <?php if($_POST['icl_form_errors'] || $icl_account_ready_errors):  ?>
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
        
        <?php if($_POST['icl_form_success']):?>
        <p class="icl_form_success"><?php echo $_POST['icl_form_success'] ?></p>
        <?php endif; ?>  
          
        <?php if(!$sitepress->icl_account_configured()): ?>
        
            <form id="icl_create_account" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>#icl_create_account_form" <?php if($_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
            <?php wp_nonce_field('icl_create_account') ?>    
            <i><?php echo __('Translation will only be available once your ICanLocalize account has been created. Complete this form and click on \'Create account\'.', 'sitepress')?></i>
            <table class="form-table icl-account-setup">
                <tbody>
                <tr class="form-field">
                    <th scope="row">First name</th>
                    <td><input name="user[fname]" type="text" value="<?php echo $_POST['user']['fname']?$_POST['user']['fname']:$current_user->first_name ?>" /></td>
                </tr>
                <tr class="form-field">
                    <th scope="row">Last name</th>
                    <td><input name="user[lname]" type="text" value="<?php echo  $_POST['user']['lname']?$_POST['user']['lname']:$current_user->last_name ?>" /></td>
                </tr>        
                <tr class="form-field">
                    <th scope="row">Email</th>
                    <td><input name="user[email]" type="text" value="<?php echo  $_POST['user']['email']?$_POST['user']['email']:$current_user->data->user_email ?>" /></td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="hidden" name="create_account" value="1" />
                <input class="button" name="create account" value="<?php echo __('Create account') ?>" type="submit" 
                    <?php if($icl_account_ready_errors):  ?>disabled="disabled"<?php endif; ?> />
                <a href="javascript:;" onclick="jQuery('#icl_create_account').hide();jQuery('#icl_configure_account').fadeIn();"><?php echo __('I already have an account at ICanLocalize', 'sitepress') ?></a>
            </p>
            </form> 

            <form id="icl_configure_account" action="<?php echo $_SERVER['REQUEST_URI'] ?>#icl_create_account_form" method="post" <?php if(!$_POST['icl_acct_option2']):?>style="display:none"<?php endif?>>
            <?php wp_nonce_field('icl_configure_account') ?>    
            <i><?php echo __('Translation will only be available once this project has been added to your ICanLocalize account. Enter your login information below and click on \'Add this project to my account\'.', 'sitepress')?></i>
            <table class="form-table icl-account-setup">
                <tbody>
                <tr class="form-field">
                    <th scope="row">Email</th>
                    <td><input name="user[email]" type="text" value="<?php echo  $_POST['user']['email']?$_POST['user']['email']:$current_user->data->user_email ?>" /></td>
                </tr>
                <tr class="form-field">
                    <th scope="row">Password</th>
                    <td><input name="user[password]" type="password" /></td>
                </tr>        
                </tbody>
            </table>
            <p class="submit">
                <input type="hidden" name="create_account" value="0" />
                <input class="button" name="configure account" value="<?php echo __('Add this project to my account') ?>" type="submit" 
                    <?php if($icl_account_ready_errors):  ?>disabled="disabled"<?php endif; ?> />
                <a href="javascript:;" onclick="jQuery('#icl_configure_account').hide();jQuery('#icl_create_account').fadeIn();"><?php echo __('Create a new ICanLocalize account', 'sitepress') ?></a>
            </p>
            </form>    
            
         <?php else: // if account configured ?>   
            <form action="<?php echo $_SERVER['REQUEST_URI'] ?>#icl_create_account_form" method="post">
            <p>
                <?php echo __('Your ICanLocalize account is configured', 'sitepress'); ?>                
                <?php wp_nonce_field('icl_logout') ?>    
                <input class="button" name="logout" value="<?php echo __('Reset ICanLocalize account configuration') ?>" type="submit" />                
            </p>
            </form>
         <?php endif; ?>
     
    <?php else:?>
        <p class='icl_form_errors'><?php echo __('After you configure more languages for your blog, the translation options will show here', 'sitepress'); ?></p>
    <?php endif; ?>
     
    
</div>