addLoadEvent(function(){     
    jQuery('#icl_change_default_button').click(editingDefaultLanguage);
    jQuery('#icl_save_default_button').click(saveDefaultLanguage);
    jQuery('#icl_cancel_default_button').click(doneEditingDefaultLanguage);
    jQuery('#icl_add_remove_button').click(showLanguagePicker);            
    jQuery('#icl_cancel_language_selection').click(hideLanguagePicker);
    jQuery('#icl_save_language_selection').click(saveLanguageSelection);                        
    jQuery('#icl_enabled_languages input').attr('disabled','disabled');    
    jQuery('#icl_save_language_negotiation_type').submit(iclSaveLanguageNegotiationType);    
    iclSaveForm_success_cb.push(function(){
        jQuery('#icl_setup_wizard_wrap').fadeOut();
        jQuery('#icl_translate_help').fadeIn();
    })
    jQuery('#icl_save_language_switcher_options').submit(iclSaveForm);    
    jQuery('#icl_lang_more_options').submit(iclSaveForm);    
    jQuery('input[name="icl_language_negotiation_type"]').change(iclLntDomains);
    jQuery('#icl_dismiss_translate_help').click(iclDismissTranslateHelp);
    jQuery('#icl_setup_back_1').click(iclSetupStep1);
    jQuery('#icl_setup_next_1').click(function(){location.href = location.href.replace(/#.*/,'');});
    
    
});
function editingDefaultLanguage(){
    jQuery('#icl_change_default_button').hide();
    jQuery('#icl_save_default_button').show();
    jQuery('#icl_cancel_default_button').show();
    jQuery('#icl_enabled_languages input').css('visibility','visible');
    jQuery('#icl_enabled_languages input').removeAttr('disabled');
    jQuery('#icl_add_remove_button').hide();
    
}
function doneEditingDefaultLanguage(){
    jQuery('#icl_change_default_button').show();
    jQuery('#icl_save_default_button').hide();
    jQuery('#icl_cancel_default_button').hide();
    jQuery('#icl_enabled_languages input').css('visibility','hidden');
    jQuery('#icl_enabled_languages input').attr('disabled','disabled');
    jQuery('#icl_add_remove_button').show();
}        
function saveDefaultLanguage(){
    var arr = jQuery('#icl_enabled_languages input[type="radio"]');            
    var def_lang;
    jQuery.each(arr, function() {                
        if(this.checked){
            def_lang = this.value;    
        }                
    });             
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=set_default_language&lang="+def_lang,
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp(icl_ajx_saved);                         
                jQuery('#icl_avail_languages_picker input[value="'+spl[1]+'"]').removeAttr('disabled');
                jQuery('#icl_avail_languages_picker input[value="'+def_lang+'"]').attr('disabled','disabled');
                jQuery('#icl_enabled_languages li').removeClass('default_language');
                jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').parent().parent().attr('class','default_language');
                jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').parent().append(' ('+icl_default_mark+')');
                jQuery('#icl_enabled_languages li input').removeAttr('checked');
                jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').attr('checked','checked');
                jQuery('#icl_enabled_languages input[value="'+spl[1]+'"]').parent().html(jQuery('#icl_enabled_languages input[value="'+spl[1]+'"]').parent().html().replace('('+icl_default_mark+')',''));
                doneEditingDefaultLanguage();                     
                fadeInAjxResp('#icl_ajx_response',icl_ajx_saved);                  
                if(spl[2]){
                    jQuery('#icl_ajx_response').html(spl[2]);
                }else{                    
                    location.href = location.href.replace(/#.*/,'');
                }                
            }else{                        
                fadeInAjxResp('#icl_ajx_response',icl_ajx_error);                                         
            }                    
        }
    });
    
}        
function showLanguagePicker(){
    jQuery('#icl_avail_languages_picker').slideDown();
    jQuery('#icl_add_remove_button').fadeOut();
    jQuery('#icl_change_default_button').fadeOut();
}
function hideLanguagePicker(){
    jQuery('#icl_avail_languages_picker').slideUp();
    jQuery('#icl_add_remove_button').fadeIn();
    jQuery('#icl_change_default_button').fadeIn();
} 
function saveLanguageSelection(){
    fadeInAjxResp('#icl_ajx_response', icl_ajxloaderimg);
    var arr = jQuery('#icl_avail_languages_picker ul input[type="checkbox"]');            
    var sel_lang = new Array();
    jQuery.each(arr, function() {
        if(this.checked){
            sel_lang.push(this.value);
        }                
    }); 
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=set_active_languages&langs="+sel_lang.join(','),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp('#icl_ajx_response', icl_ajx_saved);                         
                jQuery('#icl_enabled_languages').html(spl[1]);
            }else{                        
                fadeInAjxResp('#icl_ajx_response', icl_ajx_error,true);
            } 
            if(spl[2]=='1'){
                location.href = location.href.replace(/#.*/,'');
            }else if(spl[2]=='-1'){
                location.href = location.href.replace(/#.*/,'');
            }else if(spl[2]=='-2'){
                jQuery('#icl_setup_next_1').removeAttr('disabled');
            }else if(spl[2]=='-3'){
                jQuery('#icl_setup_next_1').attr('disabled','disabled');
            }                   

        }
    });
    hideLanguagePicker();
}   

function iclLntDomains(){
    if(jQuery(this).attr('checked') && jQuery(this).attr('id')=='icl_lnt_domains'){
        jQuery(this).parent().parent().append('<div id="icl_lnt_domains_box"></div>');
        jQuery('#icl_lnt_domains_box').html(icl_ajxloaderimg);
        jQuery('#icl_save_language_negotiation_type input[type="submit"]').attr('disabled','disabled');
        jQuery('#icl_lnt_domains_box').load(icl_ajx_url, {icl_ajx_action:'language_domains'}, function(resp){
            jQuery('#icl_save_language_negotiation_type input[type="submit"]').removeAttr('disabled');
        })
    }else{
        if(jQuery('#icl_lnt_domains_box').length){
            jQuery('#icl_lnt_domains_box').fadeOut('fast', function(){jQuery('#icl_lnt_domains_box').remove()});        
        }        
    }
    
}

function iclSaveLanguageNegotiationType(){
    var formname = jQuery(this).attr('name');
    var form_errors = false;
    jQuery('form[name="'+formname+'"] .icl_form_errors').html('').hide();
    jQuery('form[name="'+formname+'"] input').css('color','#000');
    ajx_resp = jQuery('form[name="'+formname+'"] .icl_ajx_response').attr('id');
    fadeInAjxResp('#'+ajx_resp, icl_ajxloaderimg);
    jQuery.ajaxSetup({async: false});
    var used_urls = new Array(jQuery('#icl_ln_home').html());
    jQuery('.validate_language_domain').each(function(){        
        if(jQuery(this).attr('checked')){
            var lang = jQuery(this).attr('value');
            jQuery('#ajx_ld_'+lang).html(icl_ajxloaderimg);
            var lang_td = jQuery('#icl_validation_result_'+lang);
            var lang_domain_input = jQuery('#language_domain_'+lang); 
            if(used_urls.indexOf(lang_domain_input.attr('value')) != -1 ){
                jQuery('#ajx_ld_'+lang).html('');
                lang_domain_input.css('color','#f00');
                form_errors = true;
            }else{
                used_urls.push(lang_domain_input.attr('value'));            
                lang_domain_input.css('color','#000');
                jQuery('#ajx_ld_'+lang).load(icl_ajx_url, 
                    {icl_ajx_action:'validate_language_domain',url:lang_domain_input.attr('value')}, 
                    function(resp){
                        jQuery('#ajx_ld_'+lang).html('');
                        if(resp=='0'){
                            lang_domain_input.css('color','#f00');
                            form_errors = true;
                            
                        }
                });
            }
        }        
    });
    jQuery.ajaxSetup({async: true});
    if(form_errors){        
        fadeInAjxResp('#'+ajx_resp, icl_ajx_error,true);
        return false;
    }    
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

function iclDismissTranslateHelp(){
    var thisa = jQuery(this);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=dismiss_translate_help",
            success: function(msg){
                thisa.parent().fadeOut();    
            }
    });    
    return false;
}

function iclSetupStep1(){
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=setup_got_to_step1",
            success: function(msg){
                location.href = location.href.replace(/#.*/,'');
            }
    });    
    return false;
}
