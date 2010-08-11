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
    jQuery('#icl-translation-dashboard td :checkbox').click(icl_tm_update_word_count_estimate);
    jQuery('#icl-translation-dashboard th :checkbox').click(icl_tm_select_all_documents);
    jQuery('#icl_tm_languages :checkbox').click(icl_tm_enable_submit);
    
    jQuery('.icl_tj_select_translator select').live('change', icl_tm_assign_translator);
    
            
})

function icl_tm_update_word_count_estimate(){
    icl_tm_enable_sumit();
    var id = jQuery(this).val();
    var val = parseInt(jQuery('#icl-cw-'+id).html());
    var curval = parseInt(jQuery('#icl-estimated-words-count').html());
    if(jQuery(this).attr('checked')){
        var newval = curval + val;        
    }else{
        var newval = curval - val;        
    }    
    jQuery('#icl-estimated-words-count').html(newval);
}

function icl_tm_select_all_documents(){
    if(jQuery(this).attr('checked')){
        jQuery('#icl-translation-dashboard :checkbox').attr('checked','checked');    
        jQuery('#icl-estimated-words-count').html(parseInt(jQuery('#icl-cw-total').html()));
    }else{
        jQuery('#icl-translation-dashboard :checkbox').removeAttr('checked');    
        jQuery('#icl-estimated-words-count').html('0');
    }
    icl_tm_enable_sumit();
}

function icl_tm_enable_submit(){
    if( jQuery('#icl-translation-dashboard td :checkbox:checked').length > 0 && jQuery('#icl_tm_languages :checkbox:checked').length >  0){
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
    if(translator_id > 0){
        translation_controls.show();    
        translation_controls.find('.icl_tj_cancel').click(function(){translation_controls.hide()});
        translation_controls.find('.icl_tj_ok').click(function(){icl_tm_assign_translator_request(job_id, translator_id, thiss)});
    }else{
        translation_controls.hide();
    }
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
                select.parent().html(msg.message);
                jQuery('#icl_tj_job_status_'+job_id).html(msg.status);
            }else{
                select.removeAttr('disabled');
                translation_controls.find('.icl_tj_cancel, .icl_tj_ok').removeAttr('disabled');
            }
            
        }
    });             
}
