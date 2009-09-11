jQuery(document).ready(function(){
    if(jQuery('#category-adder').html()){
        jQuery('#category-adder').prepend('<p>'+icl_cat_adder_msg+'</p>');
    }
    jQuery('#icl_set_post_language').click(iclPostLanguageSwitch);
    jQuery('#noupdate_but input[type="button"]').click(iclSetDocumentToDate);
});

function fadeInAjxResp(spot, msg, err){
    if(err != undefined){
        col = jQuery(spot).css('color');
        jQuery(spot).css('color','red');
    }
    jQuery(spot).html('<span>'+msg+'<span>');
    jQuery(spot).fadeIn();
    window.setTimeout(fadeOutAjxResp, 3000, spot);
    if(err != undefined){
        jQuery(spot).css('color',col);
    }
}

function fadeOutAjxResp(spot){
    jQuery(spot).fadeOut();
}
var icl_ajxloaderimg_src = icl_ajxloaderimg;
icl_ajxloaderimg = '<img src="'+icl_ajxloaderimg+'" alt="loading" width="16" height="16" />';

var iclHaltSave = false; // use this for multiple 'submit events'
function iclSaveForm(){
    if(iclHaltSave){
        return false;
    }
    var formname = jQuery(this).attr('name');
    jQuery('form[name="'+formname+'"] .icl_form_errors').html('').hide();
    ajx_resp = jQuery('form[name="'+formname+'"] .icl_ajx_response').attr('id');
    fadeInAjxResp('#'+ajx_resp, icl_ajxloaderimg);
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action="+jQuery(this).attr('name')+"&"+jQuery(this).serialize(),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp('#'+ajx_resp, icl_ajx_saved);                                         
            }else{                        
                jQuery('form[name="'+formname+'"] .icl_form_errors').html(spl[1]);
                jQuery('form[name="'+formname+'"] .icl_form_errors').fadeIn()
                fadeInAjxResp('#'+ajx_resp, icl_ajx_error,true);
            }  
        }
    });
    return false;     
}

function iclPostLanguageSwitch(){
    var lang = jQuery('#icl_post_language').val();
    var ajx = location.href.replace(/#(.*)$/,'');
    var translation_of = jQuery('#icl_translation_of').val();
    if(-1 == location.href.indexOf('?')){
        url_glue='?';
    }else{
        url_glue='&';
    }
    
    if(jQuery('#parent_id').length > 0){
        jQuery('#parent_id').load(ajx+url_glue+'lang='+lang + ' #parent_id option',{lang_switch:jQuery('#post_ID').attr('value')}, function(){
            if(-1 == jQuery('#parent_id').html().indexOf('selected="selected"')){
                jQuery('#parent_id').attr('value','');
            }        
        });
    }else if(jQuery('#categorydiv').length > 0){
        var ltlhlpr = document.createElement('div');
        ltlhlpr.setAttribute('style','display:none');
        ltlhlpr.setAttribute('id','icl_ltlhlpr');
        jQuery(this).append(ltlhlpr);
        jQuery('#categorydiv').slideUp();
        jQuery('#icl_ltlhlpr').load(ajx+url_glue+'icl_action=set_post_language&translation_of='+translation_of+'&lang='+lang + ' #categorydiv',{}, function(){
            jQuery('#categorydiv').html(jQuery('#icl_ltlhlpr div').html());
            jQuery('#categorydiv').slideDown();
            jQuery('#icl_ltlhlpr').remove();    
            jQuery('#category-adder').prepend('<p>'+icl_cat_adder_msg+'</p>');
        });        
    }
}

function iclSetDocumentToDate(){
    var thisbut = jQuery(this);
    if(!confirm(jQuery('#noupdate_but_wm').html())) return;
    thisbut.attr('disabled','disabled');
    thisbut.css({'background-image':"url('"+icl_ajxloaderimg_src+"')", 'background-position':'center right', 'background-repeat':'no-repeat'});
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=set_post_to_date&post_id="+jQuery('#post_ID').val(),
            success: function(msg){
                spl = msg.split('|');
                thisbut.removeAttr('disabled');
                thisbut.css({'background-image':'none'});
                thisbut.parent().fadeOut();
                var st = jQuery('#icl_translations_statuses td.icl_translation_status_msg');
                st.each(function(){
                    jQuery(this).html(jQuery(this).html().replace(spl[0],spl[1]))                     
                })
                jQuery('#icl_minor_change_box').fadeIn();
            }
        });        
}
