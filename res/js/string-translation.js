jQuery(document).ready(function(){
    jQuery('a[href="#icl-st-toggle-translations"]').click(icl_st_toggler);
    jQuery('.icl-st-inline textarea').focus(icl_st_monitor_ta);
    jQuery('.icl-st-inline textarea').keyup(icl_st_monitor_ta_check_modifications);
    jQuery(".icl_st_form").submit(icl_st_submit_translation);
    jQuery('select[name="icl_st_filter_status"]').change(icl_st_filter_status);
    jQuery('select[name="icl_st_filter_context"]').change(icl_st_filter_context);
    jQuery('.check-column input').click(icl_st_select_all);
    jQuery('#icl_st_delete_selected').click(icl_st_delete_selected);
    jQuery('#icl_st_send_selected').click(icl_st_send_selected);
    jQuery('#icl_st_send_need_translation').click(icl_st_send_need_translation);
    jQuery('#icl_st_po_translations').click(function(){
        if(jQuery(this).attr('checked')){
            jQuery('#icl_st_po_language').removeAttr('disabled').fadeIn();
        }else{
            jQuery('#icl_st_po_language').attr('disabled','disabled').fadeOut();
        }
    });
    jQuery('#icl-tr-opt :checkbox').click(icl_st_update_languages);
    jQuery('.icl_st_row_cb, .check-column :checkbox').click(icl_st_update_checked_elements);
    jQuery('.icl_htmlpreview_link').click(icl_st_show_html_preview);
    jQuery('#icl_st_po_form').submit(icl_validate_po_upload);
});

function icl_st_toggler(){
    jQuery(".icl-st-inline").slideUp();
    var inl = jQuery(this).parent().next().next();
    if(inl.css('display') == 'none'){
        inl.slideDown();            
    }else{
        inl.slideUp();            
    }
    icl_st_show_html_preview_close();
}

var icl_st_ta_cache = new Array();
var icl_st_cb_cache = new Array();
function icl_st_monitor_ta(){
    var id = jQuery(this).attr('id').replace(/^icl_st_ta_/,'');
    if(icl_st_ta_cache[id] == undefined){
        icl_st_ta_cache[id] = jQuery(this).val();        
        icl_st_cb_cache[id] = jQuery('#icl_st_cb_'+id).attr('checked');
    }    
}

function icl_st_monitor_ta_check_modifications(){
    var id = jQuery(this).attr('id').replace(/^icl_st_ta_/,'');
    if(icl_st_ta_cache[id] != jQuery(this).val()){
        jQuery('#icl_st_cb_'+id).removeAttr('checked');
    }else{
        if(icl_st_cb_cache[id]){
            jQuery('#icl_st_cb_'+id).attr('checked','checked');
        }
    }
    icl_st_show_html_preview_close();
}

function icl_st_submit_translation(){
    var thisf = jQuery(this);
    var postvars = thisf.serialize();
    postvars += '&icl_ajx_action=icl_st_save_translation';
    thisf.contents().find('textarea, input').attr('disabled','disabled');
    thisf.contents().find('.icl_ajx_loader').fadeIn();
    var string_id = thisf.find('input[name="icl_st_string_id"]').val();
    jQuery.post(icl_ajx_url, postvars, function(msg){
        thisf.contents().find('textarea, input').removeAttr('disabled');
        thisf.contents().find('.icl_ajx_loader').fadeOut();
        spl = msg.split('|');
        jQuery('#icl_st_string_status_'+string_id).html(spl[1]);
    })
    return false;
}

function icl_st_filter_status(){
    var qs = jQuery(this).val() != '' ? '&status=' + jQuery(this).val() : '';
    location.href=location.href.replace(/#(.*)$/,'').replace(/&paged=([0-9]+)/,'').replace(/&updated=true/,'').replace(/&status=([0-9])/g,'') + qs;
}
function icl_st_filter_context(){
    var qs = jQuery(this).val() != '' ? '&context=' + jQuery(this).val() : '';
    location.href=location.href.replace(/#(.*)$/,'').replace(/&paged=([0-9]+)/,'').replace(/&updated=true/,'').replace(/&context=(.*)/g,'') + qs;
}


function icl_st_select_all(){
    if(jQuery(this).attr('checked')){
        jQuery('.icl_st_row_cb, .check-column input').attr('checked','checked');
    }else{
        jQuery('.icl_st_row_cb, .check-column input').removeAttr('checked');
    }
}

function icl_st_delete_selected(){
    if(!jQuery('.icl_st_row_cb:checked').length || !confirm(jQuery(this).next().html())){
        return false;
    }
    var delids = [];
    jQuery('.icl_st_row_cb:checked').each(function(){
        delids.push(jQuery(this).val());        
    });
    if(delids){
        postvars = 'icl_ajx_action=icl_st_delete_strings&value='+delids.join(',');
        jQuery.post(icl_ajx_url, postvars, function(){
            for(i=0; i < delids.length; i++){
                jQuery('.icl_st_row_cb[value="'+delids[i]+'"]').parent().parent().fadeOut('fast', function(){jQuery(this).remove()});
            }
        })
    }
    return false;
}

function icl_st_send_selected(){
    if(!jQuery('.icl_st_row_cb:checked').length){
        return false;
    }
    thisb = jQuery(this);        
    var sendids = [];
    jQuery('.icl_st_row_cb:checked').each(function(){
        sendids.push(jQuery(this).val());        
    });
    var trlangs = [];
    jQuery('#icl-tr-opt input:checked').each(function(){trlangs.push(jQuery(this).val())});
    if(sendids.length && trlangs.length){
        thisb.attr('disabled','disabled');
        jQuery('#icl_st_send_progress').fadeIn();
        postvars = 'icl_ajx_action=icl_st_send_strings&value='+sendids.join(',')+'&langs='+trlangs.join(',');
        jQuery.post(icl_ajx_url, postvars, function(msg){
            if(msg==1) thisb.removeAttr('disabled');
            jQuery('#icl_st_send_progress').fadeOut('fast', function(){location.href=location.href.replace(/#(.*)$/,'')});
        });
    }
    return false;
}

function icl_st_send_need_translation(){
    thisb = jQuery(this);        
    thisb.attr('disabled','disabled');
    jQuery('#icl_st_send_progress').fadeIn();    
    var trlangs = [];
    jQuery('#icl-tr-opt input:checked').each(function(){trlangs.push(jQuery(this).val())});    
    postvars = 'icl_ajx_action=icl_st_send_strings_all'+'&langs='+trlangs.join(',');
    if(trlangs.length){
        jQuery.post(icl_ajx_url, postvars, function(msg){
            if(msg==1) thisb.removeAttr('disabled');
            jQuery('#icl_st_send_progress').fadeOut('fast', function(){location.href=location.href.replace(/#(.*)$/,'')});
        });
    }
    return false;
}

function icl_st_update_languages(){
    if(!jQuery('#icl-tr-opt :checkbox:checked').length){
        jQuery('#icl_st_send_selected, #icl_st_send_need_translation').attr('disabled','disabled');
    }else{
        if(jQuery('.icl_st_row_cb:checked, .check-column :checkbox:checked').length){
            jQuery('#icl_st_send_selected, #icl_st_send_need_translation').removeAttr('disabled');
        }
    }
}

function icl_st_update_checked_elements(){
    if(!jQuery('.icl_st_row_cb:checked, .check-column :checkbox:checked').length){
        jQuery('#icl_st_delete_selected, #icl_st_send_selected').attr('disabled','disabled');
    }else{
        if(!jQuery('#icl-tr-opt').length || jQuery('#icl-tr-opt :checkbox:checked').length){
            jQuery('#icl_st_delete_selected, #icl_st_send_selected').removeAttr('disabled');
        }
    }
}

function icl_st_show_html_preview(){
    var parent = jQuery(this).parent();    
    var textarea = parent.parent().prev().find('textarea[name="icl_st_translation"]');
    if(parent.find('.icl_html_preview').css('display')=='none'){
        parent.find('.icl_html_preview').html(textarea.val()).slideDown();
    }else{
        parent.find('.icl_html_preview').slideUp();
    }
    
    return false;
}

function icl_st_show_html_preview_close(){
    jQuery('.icl_html_preview').slideUp();
}

function icl_validate_po_upload(){
    cont = jQuery(this).contents();
    cont.find('.icl_error_text').hide();
    if(!jQuery('#icl_po_file').val()){
        cont.find('#icl_st_err_po').fadeIn();
        return false;
    }    
    if(!cont.find('input[name="icl_st_domain_name"]').val()){
        cont.find('#icl_st_err_domain').fadeIn();
        return false;
    }
    
}