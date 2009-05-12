<?php 
    $default_language = $sitepress->get_default_language();
    
    if(isset($icl_translation_filter['lang'])){
        $selected_language = $icl_translation_filter['lang']; 
    }else{
        $selected_language = isset($_GET['lang'])?$_GET['lang']:$default_language;
    }
    if(isset($icl_translation_filter['tstatus'])){
        $tstatus = $icl_translation_filter['tstatus']; 
    }else{
        $tstatus = isset($_GET['tstatus'])?$_GET['tstatus']:'all';
    }     
    if(isset($icl_translation_filter['status_on'])){
        $status = $icl_translation_filter['status'];
    }else{
        if(isset($_GET['status_on']) && isset($_GET['status'])){
            $status = $_GET['status'];
        }else{
            $status = false;
            if(isset($icl_translation_filter)){
                unset($icl_translation_filter['status_on']);
                unset($icl_translation_filter['status']);                
            }
        }
    }

    if(isset($icl_translation_filter['type_on'])){
        $type = $icl_translation_filter['type'];
    }else{
        if(isset($_GET['type_on']) && isset($_GET['type'])){
            $type = $_GET['type'];
        }else{
            $type = false;
            if(isset($icl_translation_filter)){
                unset($icl_translation_filter['type_on']);
                unset($icl_translation_filter['type']);
            }
        }
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
<?php $sitepress->noscript_notice() ?>
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
    
    <table class="widefat fixed" id="icl-translation-dashboard" cellspacing="0">
        <thead>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
            <th scope="col"><?php echo __('Title', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'sitepress') ?></th>        
            <th scope="col" class="manage-column column-date"><?php echo __('Translation', 'sitepress') ?></th>        
        </tr>        
        </thead>            
        <tbody>
            <?php if(!$documents): ?>
            <tr>
                <td scope="col" colspan="5" align="center"><?php echo __('No documents found', 'sitepress') ?></td>
            </tr>                
            <?php else: $oddcolumn = false; ?>
            <?php foreach($documents as $doc): $oddcolumn=!$oddcolumn; ?>
            <?php 
            $not_translatable = false;
            if($doc->rid){
                if(isset($doc->in_progress) && $doc->in_progress > 0){                        
                    $tr_status = __('In progress', 'sitepress');
                    $not_translatable = true;
                }elseif($doc->updated){                            
                    $tr_status = __('Needs update', 'sitepress');
                }else{
                    $tr_status = __('Complete', 'sitepress');
                    $not_translatable = true;
                }
            }else{
                $tr_status = __('Not Translated', 'sitepress');
            }
            
            ?>            
            <tr<?php if($oddcolumn): ?> class="alternate"<?php endif;?>>
                <td scope="col">
                    <?php if(!$not_translatable): ?>
                    <input type="checkbox" value="<?php echo $doc->post_id ?>" name="post[]" />
                    <?php endif; ?>
                </td>
                <td scope="col" class="post-title column-title">
                    <a href="<?php echo get_edit_post_link($doc->post_id) ?>"><?php echo $doc->post_title ?></a>
                    <?php if(!$not_translatable): ?>
                    <span id="icl-cw-<?php echo $doc->post_id ?>" style="display:none"><?php echo $wc = count(explode(' ',$doc->post_title)) + count(explode(' ', strip_tags($doc->post_content))); $wctotal+=$wc; ?></span>
                    <?php endif; ?>
                    <span class="icl-tr-details"></span>
                    </td>
                <td scope="col"><?php echo $icl_post_types[$doc->post_type]; ?></td>
                <td scope="col"><?php echo $icl_post_statuses[$doc->post_status]; ?></td>
                <td scope="col" id="icl-tr-status-<?php echo $doc->post_id ?>">
                    <?php if($doc->rid): ?>
                    <a href="#translation-details-<?php echo $doc->rid ; ?>" class="translation_details_but">
                    <?php endif; ?>
                    <?php echo $tr_status ?>
                    <?php if($doc->rid): ?></a><?php endif; ?>
                </td>
            </tr>                            
            <?php endforeach;?>
            <?php endif;?>
        </tbody> 
        <tfoot>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
            <th scope="col"><?php echo __('Title', 'sitepress') ?><span id="icl-cw-total" style="display:none"><?php echo $wctotal; ?></span></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'sitepress') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'sitepress') ?></th>        
            <th scope="col" class="manage-column column-date"><?php echo __('Translation', 'sitepress') ?></th>        
        </tr>        
        </tfoot>
                           
    </table>
    
    <div class="tablenav">
    <div style="float:left;margin-top:4px";><strong><?php echo __('Translation Cost Estimate:', 'sitepress') ?></strong> <?php printf(__('%s words, %s USD (at 0.07 USD/word)'), '<span id="icl-estimated-words-count">0</span>', '<strong><span id="icl-estimated-quote">0.00</span></strong>')?></div>
    <?php                 
        $page_links = paginate_links( array(
            'base' => add_query_arg('paged', '%#%' ),
            'format' => '',
            'prev_text' => __('&laquo;'),
            'next_text' => __('&raquo;'),
            'total' => $wp_query->max_num_pages,
            'current' => $_GET['paged'],
            'add_args' => isset($icl_translation_filter)?$icl_translation_filter:array() 
        ));         
    ?>
    <?php if ( $page_links ) { ?>
    <div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'sitepress' ) . '</span>%s',
        number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
        number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
        number_format_i18n( $wp_query->found_posts ),
        $page_links
    ); echo $page_links_text; ?></div>
    <?php } ?>
    </div>
    
    <h3><?php echo __('Translation Options', 'sitepress') ?></h3>
    <ul id="icl-tr-opt">
        <?php foreach($active_languages as $lang): if($selected_language==$lang['code']) continue; ?>
        <li><label><input type="checkbox" name="icl-tr-to-<?php echo $lang['code']?>" value="<?php echo $lang['english_name']?>" checked="checked" />&nbsp;<?php printf(__('Translate to %s','sitepress'), $lang['display_name']); ?></li></label>
        <?php endforeach; ?>    
        <li>
            <input disabled="disabled" type="submit" class="button-primary" id="icl-tr-sel-doc" value="<?php echo __('Translate selected documents', 'sitepress') ?>"/>
            <span class="icl_ajx_response" id="icl_ajx_response"><?php echo __('Sending translation requests. Please wait!') ?>&nbsp;<img src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" /></span>
        </li>
    </ul>
    <span id="icl_message_1" style="display:none"><?php echo __('All documents sent to translation', 'sitepress')?></span>
    <span id="icl_message_2" style="display:none"><?php echo __('Translation in progress', 'sitepress')?></span>
    
    <br /><br /><br /><br /><br /><br /><a href="<?php echo $_SERVER['REQUEST_URI'] ?>&debug=1">DEBUG: FETCH TRANSLATIONS</a>    
</div>