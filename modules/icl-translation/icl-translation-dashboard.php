<?php 
    $active_languages = $sitepress->get_active_languages();            
    $default_language = $sitepress->get_default_language();
    $selected_language = isset($_GET['lang'])?$_GET['lang']:$default_language;
    $documents = icl_translation_get_documents();
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
    
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><strong><?php echo __('Show documents in:') ?></strong></th>
            <td>
                <?php foreach($active_languages as $lang): ?>
                    <label><input type="radio" name="filter_lang" value="<?php echo $lang['code'] ?>" <?php if($default_language==$lang['code']): ?>checked="checked"<?php endif;?>/><?php echo $lang['display_name'] ?></label>&nbsp;&nbsp;
                <?php endforeach; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><strong><?php echo __('Translation status:') ?></strong>    </th>
            <td>
                <label><input type="radio" name="filter_tstatus" value="all" checked="checked" /><?php echo __('All documents', 'sitepress') ?></label>&nbsp;&nbsp;    
                <label><input type="radio" name="filter_tstatus" value="not" /><?php echo __('Not translated or needs updating', 'sitepress') ?></label>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><strong><?php echo __('Filter furter by:') ?></strong>    </th>
            <td>
                <input type="checkbox" name="enable_filter_status">&nbsp;
                    <label>Status: 
                    <select name="filter_status">
                        <?php foreach($icl_post_statuses as $k=>$v):?>
                        <option value="<?php echo $k ?>"><?php echo $v ?></option>
                        <?php endforeach; ?>
                    </select>
                    </label>
                &nbsp;&nbsp;    
                <input type="checkbox" name="enable_filter_type">&nbsp;
                    <label>Type: 
                    <select name="filter_type">
                        <?php foreach($icl_post_types as $k=>$v):?>
                        <option value="<?php echo $k ?>"><?php echo $v ?></option>
                        <?php endforeach; ?>
                    </select>
                    </label>
            </td>
        </tr>
    </table>
    
    <br />
    
    <table class="widefat" cellspacing="0">
        <thead>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
            <th scope="col"><?php echo __('Title', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Status', 'sitepress') ?></th>        
            <th scope="col"><?php echo __('Translation', 'sitepress') ?></th>        
        </tr>        
        </thead>            
        <tfoot>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
            <th scope="col"><?php echo __('Title', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col"><?php echo __('Status', 'sitepress') ?></th>        
            <th scope="col"><?php echo __('Translation', 'sitepress') ?></th>        
        </tr>        
        </tfoot>
        <tbody>
            <?php if(!$documents):?>
            <tr>
                <td scope="col" colspan="5" align="center"><?php echo __('No documents found', 'sitepress') ?></td>
            </tr>                
            <?php else:?>
            <?php foreach($documents as $doc):?>
            <tr>
                <td scope="col"><input type="checkbox" /></td>
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