<?php 
    $default_language = $sitepress->get_default_language();
    
    if(isset($icl_translation_filter['lang'])){
        $selected_language = $icl_translation_filter['lang']; 
    }else{
        $selected_language = $default_language;
    }
    if(isset($icl_translation_filter['tstatus'])){
        $tstatus = $icl_translation_filter['tstatus']; 
    }else{
        $tstatus = 'all';
    } 
    if(isset($icl_translation_filter['status_on'])){
        $status = $icl_translation_filter['status'];
    }else{
        $status = false;
    }       
    if(isset($icl_translation_filter['type_on'])){
        $type = $icl_translation_filter['type'];
    }else{
        $type = false;
    }   
    
    $active_languages = $sitepress->get_active_languages();                
    $documents = icl_translation_get_documents($selected_language, $tstatus, $status, $type);
    $icl_post_statuses = array(
        'publish'   =>__('Published', 'sitepress'),
        'draft'     =>__('Draft', 'sitepress'),
        'pending'   =>__('Pending Review', 'sitepress'),
        'future'    =>__('Scheduled', 'sitepress')
    );
    $icl_post_types = array(
        'page'  =>__('Page', 'sitepress'),
        'post'  =>__('Post', 'sitepress')
    )    
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('Translation Dashboard', 'sitepress') ?></h2>    
    
    <form method="post" name="translation-dashboard-filter" action="tools.php?page=sitepress-multilingual-cms/modules/icl-translation/icl-translation-dashboard.php">
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><strong><?php echo __('Show documents in:') ?></strong></th>
            <td>
                <?php foreach($active_languages as $lang): ?>
                    <label><input type="radio" name="filter[lang]" value="<?php echo $lang['code'] ?>" <?php if($selected_language==$lang['code']): ?>checked="checked"<?php endif;?>/><?php echo $lang['display_name'] ?></label>&nbsp;&nbsp;
                <?php endforeach; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><strong><?php echo __('Translation status:') ?></strong>    </th>
            <td coslpan="2">
                <label><input type="radio" name="filter[tstatus]" value="all" <?php if($tstatus=='all'):?>checked="checked"<?php endif;?>/><?php echo __('All documents', 'sitepress') ?></label>&nbsp;&nbsp;    
                <label><input type="radio" name="filter[tstatus]" value="not" <?php if($tstatus=='not'):?>checked="checked"<?php endif;?>/><?php echo __('Not translated or needs updating', 'sitepress') ?></label>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><strong><?php echo __('Filter furter by:') ?></strong>    </th>
            <td coslpan="2">
                <label><input type="checkbox" name="filter[status_on]" <?php if(isset($icl_translation_filter['status_on'])):?>checked="checked"<?php endif?>>&nbsp;
                    Status:</label> 
                    <select name="filter[status]">
                        <?php foreach($icl_post_statuses as $k=>$v):?>
                        <option value="<?php echo $k ?>" <?php if(isset($icl_translation_filter['status_on']) && $icl_translation_filter['status']==$k):?>selected="selected"<?php endif?>><?php echo $v ?></option>
                        <?php endforeach; ?>
                    </select>
                &nbsp;&nbsp;    
                <label><input type="checkbox" name="filter[type_on]" <?php if(isset($icl_translation_filter['type_on'])):?>checked="checked"<?php endif?>>&nbsp;
                    Type:</label> 
                    <select name="filter[type]">
                        <?php foreach($icl_post_types as $k=>$v):?>
                        <option value="<?php echo $k ?>" <?php if(isset($icl_translation_filter['type_on']) && $icl_translation_filter['type']==$k):?>selected="selected"<?php endif?>><?php echo $v ?></option>
                        <?php endforeach; ?>
                    </select>
                                        
            </td>
            <td align="right"><input name="translation_dashboard_filter" class="button" type="submit" value="<?php echo __('Display','sitepress')?>"></td>
        </tr>
    </table>
    </form>
    <br />
    
    <table class="widefat fixed" cellspacing="0">
        <thead>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
            <th scope="col"><?php echo __('Title', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'sitepress') ?></th>        
            <th scope="col" class="manage-column column-date"><?php echo __('Translation', 'sitepress') ?></th>        
        </tr>        
        </thead>            
        <tfoot>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
            <th scope="col"><?php echo __('Title', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'sitepress') ?></th>        
            <th scope="col" class="manage-column column-date"><?php echo __('Translation', 'sitepress') ?></th>        
        </tr>        
        </tfoot>
        <tbody>
            <?php if(!$documents):?>
            <tr>
                <td scope="col" colspan="5" align="center"><?php echo __('No documents found', 'sitepress') ?></td>
            </tr>                
            <?php else:?>
            <?php foreach($documents as $doc): $oddcolumn=!$oddcolumn?>
            <tr<?php if($oddcolumn): ?> class="alternate"<?php endif;?>
                <td scope="col"><input type="checkbox" value="<?php echo $doc->post_id ?>" name="post[]" /></td>
                <td scope="col" class="post-title column-title"><a href="<?php echo get_edit_post_link($doc->post_id) ?>"><?php echo $doc->post_title ?></a></td>
                <td scope="col"><?php echo $icl_post_types[$doc->post_type]; ?></td>
                <td scope="col"><?php echo $icl_post_statuses[$doc->post_status]; ?></td>
                <td scope="col">
                    <?php 
                    if($doc->rid){
                        if(isset($doc->in_progress) && $doc->in_progress > 0){                        
                            echo __('Translation in progress', 'sitepress');
                        }elseif($doc->updated){                            
                            echo __('Translation needs update', 'sitepress');
                        }else{
                            echo __('Translation complete', 'sitepress');
                        }
                    }else{
                        echo __('Not Translated', 'sitepress');
                    }
                    
                    ?>
                </td>
            </tr>                            
            <?php endforeach;?>
            <?php endif;?>
        </tbody>                    
    </table>
    
</div>