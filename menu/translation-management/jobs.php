<?php //included from menu translation-management.php ?>
<?php
if(isset($_SESSION['translation_jobs_filter'])){
    $icl_translation_filter = $_SESSION['translation_jobs_filter'];
}
$translation_jobs = $iclTranslationManagement->get_translation_jobs((array)$icl_translation_filter);

?>
<br />

<form method="post" name="translation-jobs-filter" action="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&amp;sm=jobs">
<input type="hidden" name="icl_tm_action" value="jobs_filter" />
<table class="form-table widefat fixed" style="width:600px">
    <thead>
    <tr>
        <th scope="col" colspan="2"><strong><?php _e('Filter by','sitepress')?></strong></th>
    </tr>
    </thead>        
    <tr valign="top">
        <th scope="row"><?php _e('Translation jobs for:', 'sitepress')?></th>
        <td><?php $iclTranslationManagement->translators_dropdown(array(
                'name'          => 'filter[translator_id]',
                'default_name'  => __('All', 'sitepress'),
                'selected'      => $icl_translation_filter['translator_id'] 
                )
             ); ?></td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Status', 'sitepress')?></th>
        <td>
            <select name="filter[status]">
                <option value=""><?php _e('All', 'sitepress')?></option>
                <option value="<?php echo ICL_TM_NOT_TRANSLATED ?>" <?php 
                    if(strlen($icl_translation_filter['status']) 
                        && $icl_translation_filter['status']== ICL_TM_NOT_TRANSLATED):?>selected="selected"<?php endif ;?>><?php _e('Not done', 'sitepress')?></option>
                <option value="<?php echo ICL_TM_IN_PROGRESS ?>" <?php 
                    if($icl_translation_filter['status']==ICL_TM_IN_PROGRESS):?>selected="selected"<?php endif ;?>><?php _e('In progress', 'sitepress')?></option>
                <option value="<?php echo ICL_TM_COMPLETE ?>" <?php 
                    if($icl_translation_filter['status']==ICL_TM_COMPLETE):?>selected="selected"<?php endif ;?>><?php _e('Complete', 'sitepress')?></option>
            </select>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Languages:', 'sitepress')?></th>
        <td>
            <strong><?php _e('From', 'sitepress');?></strong>
                <select name="filter[from]">   
                    <option value=""><?php _e('All', 'sitepress')?></option>
                    <?php foreach($sitepress->get_active_languages() as $lang):?>
                    <option value="<?php echo $lang['code']?>"><?php echo $lang['display_name']?></option>
                    <?php endforeach; ?>
                </select>
            &nbsp;
            <strong><?php _e('To', 'sitepress');?></strong>
                <select name="filter[to]">   
                    <option value=""><?php _e('All', 'sitepress')?></option>
                    <?php foreach($sitepress->get_active_languages() as $lang):?>
                    <option value="<?php echo $lang['code']?>"><?php echo $lang['display_name']?></option>
                    <?php endforeach; ?>
                </select>            
        </td>
    </tr>    
    <tr valign="top">
        <td colspan="2" align="right">  
            <input class="button-secondary" type="submit" value="<?php _e('Display', ' sitepress')?>" />
        </td>
    </tr>
</table>
</form>

<br />

<table class="widefat fixed" id="icl-translation-jobs" cellspacing="0">
    <thead>
        <tr>
            <th scope="col"><?php _e('Title', 'sitepress')?></th>
            <th scope="col"><?php _e('Language', 'sitepress')?></th>            
            <th scope="col" class="manage-column column-date"><?php _e('Status', 'sitepress')?></th>
            <th scope="col" class="manage-column column-date"><?php _e('Translator') ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th scope="col"><?php _e('Title', 'sitepress')?></th>
            <th scope="col"><?php _e('Language', 'sitepress')?></th>
            <th scope="col"><?php _e('Status', 'sitepress')?></th>
            <th scope="col" class="manage-column column-date"><?php _e('Translator') ?></th>
        </tr>
    </tfoot>    
    <tbody>
        <?php if(empty($translation_jobs)):?>
        <tr>
            <td colspan="4" align="center"><?php _e('No translation jobs found', 'sitepress')?></td>
        </tr>
        <?php else: foreach($translation_jobs as $job):?>
        <tr>
            <td><a href="<?php echo $job->edit_link ?>"><?php echo esc_html($job->post_title) ?></a></td>
            <td><?php echo $job->lang_text ?></td>            
            <td><?php echo $job->status ?></td>
            <td>
                <?php if(!empty($job->translator_id)): ?>
                <a href="<?php echo $iclTranslationManagement->get_translator_edit_url($job->translator_id) ?>"><?php echo esc_html($job->translator_name) ?></a>
                <?php else: ?>
                <?php $iclTranslationManagement->translators_dropdown(array('from'=>$job->source_language_code,'to'=>$job->language_code));?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>    
</table>

<?/* 
<br /><br /><br />
<table class="widefat fixed" id="icl-translation-jobs" cellspacing="0">
    <thead>
        <tr>
            <th scope="col"><?php _e('Title', 'sitepress')?></th>
            <th scope="col"><?php _e('Language', 'sitepress')?></th>            
            <th scope="col" class="manage-column column-date"><?php _e('Status', 'sitepress')?></th>
            <th scope="col" class="manage-column column-date">&nbsp;</th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th scope="col"><?php _e('Title', 'sitepress')?></th>
            <th scope="col"><?php _e('Language', 'sitepress')?></th>
            <th scope="col">&nbsp;</th>
            <th scope="col"><?php _e('Status', 'sitepress')?></th>
        </tr>
    </tfoot>    
    <tbody>
        <?php if(empty($translation_jobs)):?>
        <tr>
            <td colspan="4" align="center"><?php _e('No translation jobs found', 'sitepress')?></td>
        </tr>
        <?php else: foreach($translation_jobs as $job):?>
        <tr>
            <td><a href="<?php echo $job->edit_link ?>"><?php echo $job->post_title ?></a></td>
            <td><?php echo $job->lang_text ?></td>
            <td width=""><a href="#"><?php _e('edit', 'sitepress'); ?></td>
            <td><?php echo $job->status ?></td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>    
</table>
*/?>