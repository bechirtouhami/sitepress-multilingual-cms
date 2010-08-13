<?php 

$job = $iclTranslationManagement->get_translation_job((int)$_GET['job_id']);
if(empty($job)){
    $job_checked = true;
    include ICL_PLUGIN_PATH . '/menu/translations-queue.php';
    return;
}

?>
<div class="wrap icl-translation-editor">
    <div id="icon-options-general" class="icon32" 
        style="background: transparent url(<?php echo ICL_PLUGIN_URL ?>/res/img/icon<?php if(!$sitepress_settings['basic_menu']) echo '_adv'?>.png) no-repeat"><br /></div>
    <h2><?php echo __('Translation editor', 'sitepress') ?></h2>    
    
    <?php do_action('icl_tm_messages'); ?>
    
    <p class="updated fade"><?php printf(__('You are translating %s from %s to %s.', 'sitepress'), 
        '<a href="'.get_edit_post_link($job->original_doc_id).'">' . esc_html($job->original_doc_title) . '</a>', $job->from_language, $job->to_language); ?></p>
    
    <div id="dashboard-widgets-wrap">
        <?php foreach($job->elements as $element): ?>    
        <?php $_iter = !isset($_iter) ? 1 : $_iter + 1; ?>
        <div class="metabox-holder" id="icl-tranlstion-job-elements-<?php echo $_iter ?>">
            <div class="postbox-container icl-tj-postbox-container-<?php echo $element->type ?>">
                <div class="meta-box-sortables ui-sortable" id="icl-tranlstion-job-sortables-<?php echo $_iter ?>">
                    <div class="postbox" id="icl-tranlstion-job-element-<?php echo $_iter ?>">
                        <div title="<?php _e('Click to toggle', 'sitepress')?>" class="handlediv">
                            <br />
                        </div>
                        <h3 class="hndle"><?php echo $element->type  ?></h3>
                        <div class="inside">
                            <p><label><input type="checkbox" name="" value="1" />&nbsp;<?php _e('This translation is finished.', 'sitepress')?></label></p>
                            <p>
                                <label>
                                    <?php _e('Translated content'); echo ' - ' . $job->to_language; ?><br />
                                    <?php if($element->type=='body'): ?>
                                    <?php the_editor(''); ?>
                                    <?php else: ?>
                                    <input type="text" name="" value="" />
                                    <?php endif; ?>
                                </label>
                            </p>
                            <p>
                                <?php _e('Original content'); echo ' - ' . $job->from_language; ?><br />
                                <?php if($element->type=='body'): ?>
                                <?php the_editor('Something beautiful'); ?>
                                <?php else: ?>
                                <div class="icl-tj-original">Something beautiful</div>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <label><input type="checkbox" />&nbsp;<?php _e('Translation of this document is complete', 'sitepress')?></label>
    
    <p class="submit-buttons">
        <input type="submit" class="button-primary" value="<?php _e('Save translation', 'sitepress')?>" />&nbsp;
        <input type="submit" class="button-secondary" value="<?php _e('Cancel', 'sitepress')?>" />
    </p>
    
</div>