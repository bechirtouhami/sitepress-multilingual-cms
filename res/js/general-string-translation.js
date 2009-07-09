jQuery(document).ready(function(){
    jQuery('a[href="#icl-st-toggle-translations"]').click(icl_st_toggler);
    jQuery('.icl-st-inline textarea').focus(icl_st_monitor_ta);
    jQuery('.icl-st-inline textarea').keyup(icl_st_monitor_ta_check_modifications);
    jQuery(".icl_st_form").submit(icl_st_submit_translation);
    jQuery('select[name="icl_st_filter"]').change(icl_st_filter);
});

function icl_st_toggler(){
    jQuery(".icl-st-inline").slideUp();
    if(jQuery(this).next().css('display') == 'none'){
        jQuery(this).next().slideDown();            
    }else{
        jQuery(this).next().slideUp();            
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
    jQuery.post(icl_ajx_url, postvars, function(){
        thisf.contents().find('textarea, input').removeAttr('disabled');
        thisf.contents().find('.icl_ajx_loader').fadeOut();
    })
    return false;
}
function icl_st_filter(){
    postvars = 'icl_ajx_action=icl_st_filter&value='+jQuery(this).val();
    jQuery.post(icl_ajx_url, postvars, function(msg){
        location.href=location.href.replace(/#(.*)$/,'');
    });
}

