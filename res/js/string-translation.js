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
    })
});

function icl_st_toggler(){
    jQuery(".icl-st-inline").slideUp();
    var inl = jQuery(this).parent().next().next();
    if(inl.css('display') == 'none'){
        inl.slideDown();            
    }else{
        inl.slideUp();            
    }
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
        console.log(icl_st_cb_cache);
        if(icl_st_cb_cache[id]){
            jQuery('#icl_st_cb_'+id).attr('checked','checked');
        }
    }
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
    if(delids && trlangs){
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
        thisb.attr('disabled','disabled').next().fadeIn();
        postvars = 'icl_ajx_action=icl_st_send_strings&value='+sendids.join(',')+'&langs='+trlangs.join(',');
        jQuery.post(icl_ajx_url, postvars, function(msg){
            thisb.removeAttr('disabled').next().fadeOut();
        });
    }
    return false;
}

function icl_st_send_need_translation(){
    thisb = jQuery(this);        
    thisb.attr('disabled','disabled').next().fadeIn();
    postvars = 'icl_ajx_action=icl_st_send_strings_all'+'&langs='+trlangs.join(',');
    var trlangs = [];
    jQuery('#icl-tr-opt input:checked').each(function(){trlangs.push(jQuery(this).val())});    
    if(trlangs.length){
        jQuery.post(icl_ajx_url, postvars, function(msg){
            thisb.removeAttr('disabled').next().fadeOut();
        });
    }
    return false;
}
