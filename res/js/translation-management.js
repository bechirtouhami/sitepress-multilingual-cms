jQuery(document).ready(function(){
    
    jQuery('#icl_tm_selected_user').change(function(){
        if(jQuery(this).val()){
            jQuery('.icl_tm_lang_pairs').slideDown();
        }else{
            jQuery('.icl_tm_lang_pairs').slideUp();
            jQuery('#icl_tm_adduser .icl_tm_lang_pairs_to').hide();
            jQuery('#icl_tm_add_user_errors span').hide();
        }
        
    });
    
    jQuery('#icl_tm_adduser .icl_tm_from_lang').change(function(){
        if(jQuery(this).attr('checked')){
           jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').slideDown();
        }else{
            jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').find(':checkbox').removeAttr('checked'); 
            jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').slideUp();
        }
    });
    
    jQuery('#icl_tm_adduser').submit(function(){
        jQuery('#icl_tm_add_user_errors span').hide();
        if(jQuery('.icl_tm_to_lang:checked').length==0){
            jQuery('#icl_tm_add_user_errors .icl_tm_no_to').show();    
            return false;    
        }
    });
    
    jQuery('a[href="#hide-advanced-filters"]').click(function(){        
        athis = jQuery(this);        
        icl_save_dashboard_setting('advanced_filters',0,function(f){
            jQuery('#icl_dashboard_advanced_filters').slideUp()
            athis.hide();
            jQuery('a[href="#show-advanced-filters"]').show();
        });
    })
    
    jQuery('a[href="#show-advanced-filters"]').click(function(){
        athis = jQuery(this);        
        icl_save_dashboard_setting('advanced_filters',1,function(f){
            jQuery('#icl_dashboard_advanced_filters').slideDown()
            athis.hide();
            jQuery('a[href="#hide-advanced-filters"]').show();
        });
    })
    
    function icl_save_dashboard_setting(setting, value, callback){
        jQuery('#icl_dashboard_ajax_working').fadeIn();
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: 'icl_ajx_action=save_dashboard_setting&setting='+setting+'&value='+value,
            success: function(msg){
                jQuery('#icl_dashboard_ajax_working').fadeOut();
                callback(msg);                
            }
        });         
    }
    
    /* word count estimate */
    jQuery('#icl-tm-translation-dashboard td :checkbox').click(icl_tm_update_word_count_estimate);
    jQuery('#icl-tm-translation-dashboard th :checkbox').click(icl_tm_select_all_documents);
    jQuery('#icl_tm_languages :checkbox').click(icl_tm_enable_submit);
    
    jQuery('.icl_tj_select_translator select').live('change', icl_tm_assign_translator);
    
    jQuery('#icl_tm_editor .icl_tm_finished').change(icl_tm_update_complete_cb_status);
    
    jQuery('.handlediv').click(function(){
        if(jQuery(this).parent().hasClass('closed')){
            jQuery(this).parent().removeClass('closed');
        }else{
            jQuery(this).parent().addClass('closed');
        }
    })
    
    jQuery('#icl_tm_toggle_visual').click(function(){
        jQuery('.icl-tj-original .html').hide();
        jQuery('.icl-tj-original .visual').show();
        jQuery('#icl_tm_orig_toggle a').removeClass('active');
        jQuery(this).addClass('active');        
        return false;
    });
    
    jQuery('#icl_tm_toggle_html').click(function(){
        jQuery('.icl-tj-original .html').show();
        jQuery('.icl-tj-original .visual').hide();
        jQuery('#icl_tm_orig_toggle a').removeClass('active');
        jQuery(this).addClass('active');
        return false;
    })
    
    jQuery('.icl_tm_finished').change(function(){        
        var field = jQuery(this).attr('name').replace(/finished/,'data');
        if(field == 'fields[body][data]'){
            var data = jQuery('*[name="'+field+'"]').val() + tinyMCE.get('fields[body][data]').getContent();
        }
        else if(jQuery(this).hasClass('icl_tmf_multiple')){
            var data = 1;
            jQuery('[name*="'+field+'"]').each(function(){
                data = data * jQuery(this).val().length;
            });
        }else{
            var data = jQuery('[name="'+field+'"]*').val();    
        }
        
        if(jQuery(this).attr('checked') && !data){
            jQuery(this).removeAttr('checked');    
        }
    });
    
    jQuery('#icl_tm_editor').submit(function(){
        jQuery('#icl_tm_validation_error').hide();
        jQuery('.icl_tm_finished:checked').each(function(){
            var field = jQuery(this).attr('name').replace(/finished/,'data');
            
            if(field == 'fields[body][data]'){
                var data = jQuery('*[name="'+field+'"]').val() + tinyMCE.get('fields[body][data]').getContent();
            }
            else if(jQuery(this).hasClass('icl_tmf_multiple')){
                var data = 1;
                jQuery('[name*="'+field+'"]').each(function(){
                    data = data * jQuery(this).val().length;
                });
            }else{
                var data = jQuery('[name="'+field+'"]*').val();    
            }
            if(!data){
                jQuery('#icl_tm_validation_error').fadeIn();
                jQuery(this).removeAttr('checked');    
                icl_tm_update_complete_cb_status();
                return false;   
            }
        });  
    });
    
            
})

function icl_tm_update_word_count_estimate(){
    icl_tm_enable_submit();
    var id = jQuery(this).val();
    var val = parseInt(jQuery('#icl-cw-'+id).html());
    var curval = parseInt(jQuery('#icl-tm-estimated-words-count').html());
    if(jQuery(this).attr('checked')){
        var newval = curval + val;        
    }else{
        var newval = curval - val;        
    }    
    jQuery('#icl-tm-estimated-words-count').html(newval);
}

function icl_tm_select_all_documents(){
    if(jQuery(this).attr('checked')){
        jQuery('#icl-tm-translation-dashboard :checkbox').attr('checked','checked');    
        jQuery('#icl-tm-estimated-words-count').html(parseInt(jQuery('#icl-cw-total').html()));
    }else{
        jQuery('#icl-tm-translation-dashboard :checkbox').removeAttr('checked');    
        jQuery('#icl-tm-estimated-words-count').html('0');
    }
    icl_tm_enable_sumit();
}

function icl_tm_enable_submit(){
    if( jQuery('#icl-tm-translation-dashboard td :checkbox:checked').length > 0 && jQuery('#icl_tm_languages :checkbox:checked').length >  0){
        jQuery('#icl_tm_jobs_submit').removeAttr('disabled');
    }else{
        jQuery('#icl_tm_jobs_submit').attr('disabled','disabled');
    }
}

function icl_tm_assign_translator(){
    var thiss = jQuery(this);
    var translator_id = thiss.val();
    var translation_controls = thiss.parent().parent().find('.icl_tj_select_translator_controls');
    var job_id = translation_controls.attr('id').replace(/^icl_tj_tc_/,'');
    translation_controls.show();    
    translation_controls.find('.icl_tj_cancel').click(function(){
            thiss.val(jQuery('#icl_tj_ov_'+job_id).val());
            translation_controls.hide()
    });
    translation_controls.find('.icl_tj_ok').unbind('click').click(function(){icl_tm_assign_translator_request(job_id, translator_id, thiss)});
    
}

function icl_tm_assign_translator_request(job_id, translator_id, select){
    var translation_controls = select.parent().parent().find('.icl_tj_select_translator_controls');
    select.attr('disabled', 'disabled');
    translation_controls.find('.icl_tj_cancel, .icl_tj_ok').attr('disabled', 'disabled');

    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: 'icl_ajx_action=assign_translator&job_id='+job_id+'&translator_id='+translator_id,
        success: function(msg){
            if(!msg.error){
                translation_controls.hide();    
                jQuery('#icl_tj_ov_'+job_id).val(translator_id);
            }else{
                //                
            }
            select.removeAttr('disabled');
            translation_controls.find('.icl_tj_cancel, .icl_tj_ok').removeAttr('disabled');
            
        }
    }); 
    
    return false;            
}

function icl_tm_update_complete_cb_status(){
    if(jQuery('#icl_tm_editor .icl_tm_finished:checked').length == jQuery('#icl_tm_editor .icl_tm_finished').length){
        jQuery('#icl_tm_editor :checkbox[name=complete]').removeAttr('disabled');
    }else{
        jQuery('#icl_tm_editor :checkbox[name=complete]').attr('disabled', 'disabled');        
    }    
}



/* MC Setup */

jQuery(document).ready(function(){
    jQuery('#icl_page_sync_options').submit(iclSaveForm);    
    jQuery('form[name="icl_plugins_texts"]').submit(iclSaveForm);
    jQuery('form[name="icl_custom_tax_sync_options"]').submit(iclSaveForm);
    jQuery('form[name="icl_custom_posts_sync_options"]').submit(iclSaveForm);
    jQuery('form[name="icl_cf_translation"]').submit(iclSaveForm);
})
