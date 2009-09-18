jQuery(document).ready(function(){
    if(jQuery('#category-adder').html()){
        jQuery('#category-adder').prepend('<p>'+icl_cat_adder_msg+'</p>');
    }
    jQuery('select[name="icl_post_language"]').change(iclPostLanguageSwitch);
    jQuery('#noupdate_but input[type="button"]').click(iclSetDocumentToDate);
    jQuery('select[name="icl_translation_of"]').change(function(){jQuery('#icl_translate_options').fadeOut();});
    jQuery('#icl_dismiss_help').click(iclDismissHelp);
    jQuery('.icl_advanced_switch a').click(iclToggleAdvancedSetup);
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
var iclSaveForm_success_cb = new Array();
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
                for(i=0;i<iclSaveForm_success_cb.length;i++){
                    iclSaveForm_success_cb[i]();    
                }
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
    var lang = jQuery(this).attr('value');
    var ajx = location.href.replace(/#(.*)$/,'');
    if(-1 == location.href.indexOf('?')){
        url_glue='?';
    }else{
        url_glue='&';
    }
    
    if(icl_this_lang != lang){
        jQuery('#icl_translate_options').fadeOut();
    }else{
        jQuery('#icl_translate_options').fadeIn();
    }
    
    if(jQuery('#parent_id').length > 0){
        jQuery('#parent_id').load(ajx+url_glue+'lang='+lang + ' #parent_id option',{lang_switch:jQuery('#post_ID').attr('value')}, function(resp){
            tow1 = resp.indexOf('<div id="translation_of_wrap">');
            tow2 = resp.indexOf('</div><!--//translation_of_wrap-->');            
            jQuery('#translation_of_wrap').html(resp.substr(tow1+31, tow2-tow1-31));                   
            if(-1 == jQuery('#parent_id').html().indexOf('selected="selected"')){
                jQuery('#parent_id').attr('value','');
            }        
        });
    }else if(jQuery('#categorydiv').length > 0){
        var ltlhlpr = document.createElement('div');
        ltlhlpr.setAttribute('style','display:none');
        ltlhlpr.setAttribute('id','icl_ltlhlpr');
        jQuery(this).after(ltlhlpr);
        jQuery('#categorydiv').slideUp();
        jQuery('#icl_ltlhlpr').load(ajx+url_glue+'icl_ajx=1&lang='+lang + ' #categorydiv',{}, function(resp){ 
            tow1 = resp.indexOf('<div id="translation_of_wrap">');
            tow2 = resp.indexOf('</div><!--//translation_of_wrap-->');            
            jQuery('#translation_of_wrap').html(resp.substr(tow1+31, tow2-tow1-31));           
            jQuery('#icl_ltlhlpr').html(jQuery('#icl_ltlhlpr').html().replace('categorydiv',''));
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

function iclDismissHelp(){
    var thisa = jQuery(this);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=dismiss_help",
            success: function(msg){
                thisa.parent().parent().fadeOut();    
            }
    });    
    return false;
}

function iclToggleAdvancedSetup(){
    jQuery('.icl_advanced_switch span').toggle();
    if(jQuery(".icl_advanced_feature:hidden").length > 0){
        jQuery(".icl_advanced_feature").fadeIn('slow');
    }else{
        jQuery(".icl_advanced_feature").fadeOut('fast');
    }
    
    return false;
}