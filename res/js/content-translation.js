addLoadEvent(function(){         
    jQuery('.icl_language_pairs .icl_tr_from').change(toggleTranslationPairsSub);
    jQuery('.icl_language_pairs .icl_tr_from').change(iclShowNextButtonStep1);
    jQuery('.icl_tr_to').change(iclShowNextButtonStep1);
    jQuery('#icl_save_language_pairs').click(saveLanguagePairs);    
    jQuery('form[name="icl_more_options"]').submit(iclSaveForm);
    jQuery('form[name="icl_more_options"]').submit(iclSaveMoreOptions);
    jQuery('#icl_create_account, #icl_configure_account').submit(iclValidateWebsiteKind);
    jQuery('form[name="icl_editor_account"]').submit(iclSaveForm);    
    jQuery('#icl_enable_content_translation,#icl_disable_content_translation').click(iclToggleContentTranslation);
    jQuery('a[href="#icl-ct-advanced-options"]').click(iclToggleAdvancedOptions);        
    jQuery('a[href="#icl-show_disabled_langs"]').click(iclToggleMoreLanguages);        
    if(jQuery('input[name="icl_website_kind"]:checked').length==0){
        jQuery('input[name="icl_website_kind"]').click(iclQuickSaveWebsiteKind);
    }
    jQuery('input[name="icl_content_trans_setup_cancel"]').click(iclWizardCancel)
    
    jQuery('.handlediv').click(function(){
        if(jQuery(this).parent().hasClass('closed')){
            jQuery(this).parent().removeClass('closed');
        }else{
            jQuery(this).parent().addClass('closed');
        }
    })
    
    if (jQuery('input[name="icl_content_trans_setup_next_1"]').length > 0) {
        iclShowNextButtonStep1();
    }
    
    jQuery('.icl_cost_estimate_toggle').click(function(){jQuery('#icl_cost_estimate').slideToggle()});
    jQuery('.icl_account_setup_toggle').click(function(){
        if(jQuery('#icl_languages_translators_stats').is(':visible')){
            jQuery('#icl_languages_translators_stats').hide();
        }else{
            jQuery('#icl_languages_translators_stats').html(icl_ajxloaderimg).fadeIn();
            jQuery('#icl_languages_translators_stats').load(location.href + ' #icl_languages_translators_stats > *');
        }
        jQuery('#icl_account_setup').slideToggle();
        jQuery('.icl_account_setup_toggle_main').toggle();
    });
    
    
});

function iclSaveMoreOptions() {
    jQuery('input[name="icl_translator_choice"]:checked').each(function(){
        if (this.value == '1') {
            jQuery('#icl_own_translators_message').css("display", "");
        } else {
            jQuery('#icl_own_translators_message').css("display", "none");
        }
    });
}

function iclWizardCancel() {
    if(!confirm(jQuery('#icl_toggle_ct_confirm_message').html())){
        return false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&new_val=0",
        success: function(msg){
            location.href=location.href;
        }
    });         
    
}

function iclShowNextButtonStep1() {
    // See if we have a language pair selected and enable the button if we have.
    var found = false;
    
    jQuery('.icl_tr_from:checked').each(function(){
        var from = this.id.substring(13);
        jQuery('.icl_tr_to:checked').each(function(){
            if (this.id.substr(13, 2) == from){
                found = true;
            }
        })
    });
    
    if (found) {
        jQuery('input[name="icl_content_trans_setup_next_1"]').attr("disabled", "");
    } else {
        jQuery('input[name="icl_content_trans_setup_next_1"]').attr("disabled", "disabled");
    }
}

function toggleTranslationPairsSub(){
    var code = jQuery(this).attr('name').split('_').pop();
    if(jQuery(this).attr('checked')){
        jQuery('#icl_tr_pair_sub_'+code).slideDown();
    }else{
        // we should leave any to languages checked.
        //jQuery('#icl_tr_pair_sub_'+code+' input[type="checkbox"]').removeAttr('checked');
        
        //jQuery('#icl_tr_pair_sub_'+code).slideUp();
        // NOTE:
        // slideup is not working in wp2.8.4 so set display to none instead.
        jQuery('#icl_tr_pair_sub_'+code).css("display", "none");
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
                lang_result = spl[1].split("\n");
                for (lang_status in lang_result) {
                    parts = lang_result[lang_status].split('~');
                    from_lang = parts[0];
                    to_lang = parts[1];
                    status = parts[2];
                    jQuery('#icl_lng_from_status_' + from_lang + '_' + to_lang).html(status);
                    
                }
                fadeInAjxResp('#icl_ajx_response',icl_ajx_saved);                                         
            }else{                        
                fadeInAjxResp('#icl_ajx_response',icl_ajx_error + spl[1],true);
            }  
        }
    }); 
    
}

function iclToggleContentTranslation(){
    var val = jQuery(this).attr('id')=='icl_enable_content_translation'?1:0;
    if(!val && !confirm(jQuery('#icl_toggle_ct_confirm_message').html())){
        return false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&new_val="+val,
        success: function(msg){
            location.href = location.href.replace(/#.*/,'');
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

function iclToggleMoreLanguages(){    
    jqthis = jQuery(this);
    if(jQuery('#icl_languages_disabled').css('display')=='none'){
        jQuery('#icl_languages_disabled').fadeIn('fast',function(){
            jqthis.children().toggle();
        });        
    }else{
        /* NOTE:
            this fade out is not working in wp 2.8.4. set the display to none instead.
        jQuery('#icl_languages_disabled').fadeOut('fast',function(){
            jqthis.children().toggle();
        });
        */
        
        jQuery('#icl_languages_disabled').css('display', 'none');
        jqthis.children().toggle();
    }    
}

function iclValidateWebsiteKind(){
    if (JQuery('form[name="icl_more_options"]').length > 0 ) {
        
        jQuery('form[name="icl_more_options"] ul:first').css('border','none').css('padding','0');
        jQuery('form[name="icl_more_options"] .icl_form_errors').fadeOut();
        iclHaltSave = false;
        if(jQuery('input[name="icl_website_kind"]:checked').length==0){
            jQuery('form[name="icl_more_options"] ul:first').css('border','1px solid red').css('padding','2px');
            jQuery('form[name="icl_more_options"] .icl_form_errors').fadeIn();
            iclHaltSave = true;
            location.href=location.href.replace(/#(.+)$/,'') + '#icl_more_options';
            return false;
        }
    }
}

function iclQuickSaveWebsiteKind(){
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_save_website_kind&icl_website_kind="+jQuery(this).val()
    });
    return false;     
}



