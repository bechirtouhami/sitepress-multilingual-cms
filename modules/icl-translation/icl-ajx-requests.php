<?php
$iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);       

switch($_REQUEST['icl_ajx_req']){
    case 'get_translation_details':
        include dirname(__FILE__).'/icl-language-ids.inc';
        $rid = $_REQUEST['rid'];
        $details = $iclq->cms_request_translations($rid);
        $upload = $details['cms_uploads']['cms_upload'];
        $target_languages = $details['cms_target_languages']['cms_target_language'];
        // HACK: If we only have one target language then the $target_languages
        // array no longer has an array of languages but returns just the target language
        if(!isset($target_languages[0])){
            $target = $target_languages;
            $target_languages = array(0 => $target);
        }
        ?>
        <table class="widefat fixed">
        <thead>
        <tr>
            <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Status', 'sitepress') ?></th>        
            <th scope="col"><?php echo __('Translator', 'sitepress') ?></th>        
        </tr>  
        </thead>              
        <?php foreach($target_languages as $l): ?>
        <?php 
            $lang = $icl_language_id2name[$l['attr']['language_id']];
            $lang_loc = $wpdb->get_var("SELECT name FROM {$wpdb->icl_languages_translations} lt JOIN {$wpdb->icl_languages} l ON lt.language_code=l.code WHERE lt.display_language_code=".$sitepress->get_default_language());
            if(!$lang_loc){
                $lang_loc = $lang;
            }
            $app_wc += $l['attr']['word_count'];
        ?>
        <tr>
            <td scope="col"><?php echo $lang_loc ?></td>
            <td scope="col"><?php echo icl_decode_translation_status_id($l['attr']['status']); ?></td>        
            <td scope="col">
                <?php if($l['translator']['attr']['id']): ?>
                <a href="<?php echo ICL_API_ENDPOINT ?>/websites/<?php echo $iclq->setting('site_id')?>/cms_requests/<?php echo $rid ?>/chat?lang=<?php echo $lang ?>"><?php echo $l['translator']['attr']['nickname'] ?></a>
                <?php else: echo __('None assigned', 'sitepress'); ?>
                <?php endif; ?>
            </td>        
        </tr>             
        <?php endforeach; ?>
        <tr>                   
        <td scope="col" colspan="3">
            <a href="<?php echo ICL_API_ENDPOINT ?>/websites/<?php echo $iclq->setting('site_id')?>/cms_requests/<?php echo $rid ?>"><?php printf(__('Project page on %s'), 'ICanLocalize.com') ?></a><br />
            <?php printf(__('Sent for translation: %s'), date('m/d/Y H:i', $details['attr']['created_at'])) ?><br />
            <?php printf(__('Approximate word count: %s'), number_format($app_wc)) ?><br />
        </td>
        </tr>                   
        </table>
        <?php
        break;
}

exit;
?>
