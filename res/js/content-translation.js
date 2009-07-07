addLoadEvent(function(){         
    jQuery('#icl_language_pairs .icl_tr_from').change(toggleTranslationPairsSub);
    jQuery('#icl_save_language_pairs').click(saveLanguagePairs);    
    jQuery('form[name="icl_more_options"]').submit(iclValidateWebsiteKind).submit(iclSaveForm);
    jQuery('#icl_create_account, #icl_configure_account').submit(iclValidateWebsiteKind);
    jQuery('form[name="icl_editor_account"]').submit(iclSaveForm);
    jQuery('form[name="icl_plugins_texts"]').submit(iclSaveForm);
    jQuery('#icl_enable_content_translation').change(iclToggleContentTranslation);
    jQuery('a[href="#icl-ct-advanced-options"]').click(iclToggleAdvancedOptions);        
    jQuery('input[name="icl_website_kind"]').click(iclQuickSaveWebsiteKind);
    
});

function toggleTranslationPairsSub(){
    var code = jQuery(this).attr('name').split('_').pop();
    if(jQuery(this).attr('checked')){
        jQuery('#icl_tr_pair_sub_'+code).slideDown();
    }else{
        jQuery('#icl_tr_pair_sub_'+code+' input[type="checkbox"]').removeAttr('checked');
        jQuery('#icl_tr_pair_sub_'+code).slideUp();
    }            
}

function saveLanguagePairs(){
    fadeInAjxResp('#icl_ajx_response', icl_ajxloaderimg);
    var qargs = new Array();
    qargs.push(jQuery('#icl_language_pairs_form').serialize());
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=save_language_pairs&"+qargs.join('&'),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp('#icl_ajx_response',icl_ajx_saved + spl[1]);                                         
            }else{                        
                fadeInAjxResp('#icl_ajx_response',icl_ajx_error + spl[1],true);
            }  
        }
    }); 
    
}

function iclToggleContentTranslation(){
    var val = jQuery(this).attr('checked')?1:0;
    if(!val && !confirm(jQuery('#icl_toggle_ct_confirm_message').html())){
        jQuery(this).attr('checked','checked');
        return false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&new_val="+val,
        success: function(msg){
            location.href=location.href;
        }
    });         
}

function iclToggleAdvancedOptions(){    
    jqthis = jQuery(this);
    if(jQuery('#icl-content-translation-advanced-options').css('display')=='none'){
        jQuery('#icl-content-translation-advanced-options').fadeIn('fast',function(){
            jqthis.children().toggle();
        });        
    }else{
        jQuery('#icl-content-translation-advanced-options').fadeOut('fast',function(){
            jqthis.children().toggle();
        });
    }    
}

function iclValidateWebsiteKind(){
    jQuery('form[name="icl_more_options"] ul:first').css('border','none').css('padding','0');
    jQuery('form[name="icl_more_options"] .icl_form_errors').fadeOut();
    iclHaltSave = false;
    if(jQuery('input[name="icl_website_kind"]:checked').length==0){
        jQuery('form[name="icl_more_options"] ul:first').css('border','1px solid red').css('padding','2px');
        jQuery('form[name="icl_more_options"] .icl_form_errors').fadeIn();
        iclHaltSave = true;
        return false;
    }   
}

function iclQuickSaveWebsiteKind(){
    if(jQuery('input[name="icl_website_kind"]:checked').length==1){
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=icl_save_website_kind&icl_website_kind="+jQuery('input[name="icl_website_kind"]').val(),
        });
        return false;     
    
    }    
}



