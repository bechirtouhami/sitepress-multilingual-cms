addLoadEvent(function(){     
    jQuery('#icl_change_default_button').click(editingDefaultLanguage);
    jQuery('#icl_save_default_button').click(saveDefaultLanguage);
    jQuery('#icl_cancel_default_button').click(doneEditingDefaultLanguage);
    jQuery('#icl_add_remove_button').click(showLanguagePicker);            
    jQuery('#icl_cancel_language_selection').click(hideLanguagePicker);
    jQuery('#icl_save_language_selection').click(saveLanguageSelection);                        
    jQuery('#icl_enabled_languages input').attr('disabled','disabled');    
    jQuery('#icl_save_language_negotiation_type').submit(iclSaveLanguageNegotiationType);    
    jQuery('#icl_save_language_switcher_options').submit(iclSaveForm);    
    jQuery('#icl_admin_language_options').submit(iclSaveForm);    
    jQuery('#icl_lang_more_options').submit(iclSaveForm);    
    jQuery('#icl_blog_posts').submit(iclSaveForm);        
    jQuery('input[name="icl_language_negotiation_type"]').change(iclLntDomains);
    jQuery('#icl_dismiss_translate_help').click(iclDismissTranslateHelp);
    jQuery('#icl_setup_back_1').click(iclSetupStep1);
    jQuery('#icl_setup_back_2').click(iclSetupStep2);
    jQuery('#icl_setup_next_1').click(saveLanguageSelection);
    
    jQuery('#icl_avail_languages_picker li input:checkbox').click(function(){             
        if(jQuery('#icl_avail_languages_picker li input:checkbox:checked').length > 1){
            jQuery('#icl_setup_next_1').removeAttr('disabled');
        }else{
            jQuery('#icl_setup_next_1').attr('disabled', 'disabled');
        }
    });
	             
    icl_lp_flag = jQuery('.iclflag:visible').length > 0;         
    
	// Switcher selector config initial values (otherwise missing - preview is wrong)
	jQuery('#icl_lang_preview_config input').each(iclUpdateLangSelQuickPreview);
	// Picker align
	jQuery(".pick-show").click(function () {
		var set = jQuery(this).offset();
   		jQuery("#colorPickerDiv").css({"top":set.top+25,"left":set.left});
	});
    jQuery('#icl_translate_help_collapsed').click(function(){jQuery(this).hide();jQuery('#icl_translate_help').fadeIn()});
    jQuery('form[name="icl_promote_form"] input[name="icl_promote"]').change(function(){
        jQuery.post(icl_ajx_url, 'icl_ajx_action=icl_promote&icl_promote='+jQuery(this).attr('checked'));
    });    
    
    jQuery('#icl_lang_preview_config input').keyup(iclUpdateLangSelQuickPreview);    
    jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_flags"]').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('#lang_sel .iclflag').show();
        }else{
			jQuery('#lang_sel .iclflag').hide();
            /*if(jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_display_lang"]').attr('checked')
                || jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_native_lang"]').attr('checked')){
                jQuery('#lang_sel .iclflag').hide();
            }else{
                jQuery(this).attr('checked','checked');
                return false;
            }*/
        }
    });
    
    jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_native_lang"]').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('.icl_lang_sel_native').show();
        }else{
            if(jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_display_lang"]').attr('checked')){
                jQuery('.icl_lang_sel_native').hide();
                if(!jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_display_lang"]').attr('checked')){
                    jQuery('.icl_lang_sel_current').hide();
                }
            }else{
                jQuery(this).attr('checked','checked');
                return false;
            }
        }
    });

    jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_display_lang"]').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('.icl_lang_sel_translated').show();
        }else{
            if(jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_native_lang"]').attr('checked')){
                jQuery('.icl_lang_sel_translated').hide();
                if(!jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_native_lang"]').attr('checked')){
                    jQuery('.icl_lang_sel_current').hide();
                }                
            }else{
                jQuery(this).attr('checked','checked');
                return false;
            }
        }
    });
    
    jQuery('#icl_lang_sel_color_scheme').change(iclUpdateLangSelColorScheme);
    
    //jQuery('#icl_lang_preview_config input').change(iclUpdateLangSelPreview);    
    
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
                    location.href = location.href.replace(/#.*/,'')+'&setup=2';
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
            }else {
                location.href = location.href.replace(/(#|&).*/,'');
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
                thisa.parent().fadeOut('fast',function(){jQuery('#icl_translate_help_collapsed').fadeIn();});                    
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
function iclSetupStep2(){
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=setup_got_to_step2",
            success: function(msg){
                location.href = location.href.replace(/#.*/,'');
            }
    });    
    return false;
}

function iclUpdateLangSelPreview(){
    jQuery('#icl_lang_sel_preview_wrap').html(icl_ajxloaderimg);
    jQuery('#icl_lang_sel_preview_wrap').load(location.href + ' #icl_lang_sel_preview');
}

var icl_lp_font_current_normal = false;
var icl_lp_font_current_hover = false;
var icl_lp_background_current_normal = false;
var icl_lp_background_current_hover = false;
var icl_lp_font_other_normal = false;
var icl_lp_font_other_hover = false;
var icl_lp_background_other_normal = false;
var icl_lp_background_other_hover = false;
var icl_lp_border = false;
var icl_lp_flag = false;


function iclUpdateLangSelQuickPreview(){
    name = jQuery(this).attr('name');
    value = jQuery(this).val();
    switch(name){
        case 'icl_lang_sel_config[font-current-normal]':
            icl_lp_font_current_normal = value;
            break;
        case 'icl_lang_sel_config[font-current-hover]':
            icl_lp_font_current_hover = value;
            break;                
        case 'icl_lang_sel_config[background-current-normal]':
            icl_lp_background_current_normal = value;
            break;
        case 'icl_lang_sel_config[background-current-hover]':
            icl_lp_background_current_hover = value;
            break;                
        case 'icl_lang_sel_config[font-other-normal]':
            icl_lp_font_other_normal = value;
            break;
        case 'icl_lang_sel_config[font-other-hover]':
            icl_lp_font_other_hover = value;
            break;                
        case 'icl_lang_sel_config[background-other-normal]':
            icl_lp_background_other_normal = value;
            break;
        case 'icl_lang_sel_config[background-other-hover]':
            icl_lp_background_other_hover = value;
            break;                
        case 'icl_lang_sel_config[border]':
            icl_lp_border = value;
            break;            
        case 'icl_lso_flags':
            icl_lp_flag = jQuery(this).attr('checked');
            break;            
            
    }
    iclRenderLangPreview();
}

function iclRenderLangPreview(){
    
    if(icl_lp_font_current_normal){                                                                          
        jQuery('#lang_sel a:first').css('color',icl_lp_font_current_normal) ; 
    }    
    if(icl_lp_font_current_hover){
        jQuery('#lang_sel a:first, #lang_sel a.lang_sel_sel').unbind('hover');
        jQuery('#lang_sel a:first, #lang_sel a.lang_sel_sel').hover(
            function(){jQuery(this).css('color',icl_lp_font_current_hover)},
            function(){
                jQuery(this).css('color',icl_lp_font_current_normal);
                jQuery('#lang_sel a.lang_sel_sel').css('color',icl_lp_font_current_normal);
                }
            );
    }
    
    if(icl_lp_background_current_normal){
        jQuery('#lang_sel a:first').css('background-color', icl_lp_background_current_normal); 
        
        jQuery('#lang_sel a:first').unbind('hover');
        jQuery('#lang_sel a:first').hover(
            function(){jQuery(this).css('background-color', '')},
            function(){jQuery(this).css('background-color', icl_lp_background_current_normal)}
            );
        
    }
    
    if(icl_lp_background_current_hover){                                                          
        jQuery('#lang_sel a:first').unbind('hover');
        jQuery('#lang_sel a:first').hover(
            function(){jQuery(this).css('background-color', icl_lp_background_current_hover)},
            function(){jQuery(this).css('background-color', icl_lp_background_current_normal)}
            );
    }
                                                                                                               
    if(icl_lp_font_other_normal){
        jQuery('#lang_sel li ul a').css('color', icl_lp_font_other_normal); 
    }    
    if(icl_lp_font_other_hover){
        jQuery('#lang_sel li ul a').unbind('hover');
        jQuery('#lang_sel li ul a').hover(
            function(){jQuery(this).css('color',icl_lp_font_other_hover)},
            function(){jQuery(this).css('color',icl_lp_font_other_normal)}
            );
    }

    if(icl_lp_background_other_normal){
        jQuery('#lang_sel li ul a').css('background-color', icl_lp_background_other_normal) ; 
        jQuery('#lang_sel li ul a').unbind('hover');
        jQuery('#lang_sel li ul a').hover(
            function(){jQuery(this).css('background-color', '')},
            function(){jQuery(this).css('background-color', icl_lp_background_other_normal)}
            );        
    }    
    if(icl_lp_background_other_hover){
        jQuery('#lang_sel li ul a').unbind('hover');
        jQuery('#lang_sel li ul a').hover(
            function(){jQuery(this).css('background-color', icl_lp_background_other_hover)},
            function(){jQuery(this).css('background-color', icl_lp_background_other_normal)}
            );
    }
    
    if(icl_lp_border){
        jQuery('#lang_sel a').css('border-color', icl_lp_border);
    }
    
    if(icl_lp_flag){
        jQuery('#lang_sel .iclflag').show();
    }else{
        jQuery('#lang_sel .iclflag').hide();
    }
    
}

function iclUpdateLangSelColorScheme(){
    scheme = jQuery(this).val();
    if(scheme && confirm(jQuery(this).next().html())){
        jQuery('#icl_lang_preview_config input[type="text"]').each(function(){
            thisn = jQuery(this).attr('name').replace('icl_lang_sel_config[','').replace(']','');
            value = jQuery('#icl_lang_sel_config_alt_'+scheme+'_'+thisn).val();
            jQuery(this).val(value);
                        
            switch(jQuery(this).attr('name')){
                case 'icl_lang_sel_config[font-current-normal]':
                    icl_lp_font_current_normal = value;
                    break;
                case 'icl_lang_sel_config[font-current-hover]':
                    icl_lp_font_current_hover = value;
                    break;                
                case 'icl_lang_sel_config[background-current-normal]':
                    icl_lp_background_current_normal = value;
                    break;
                case 'icl_lang_sel_config[background-current-hover]':
                    icl_lp_background_current_hover = value;
                    break;                
                case 'icl_lang_sel_config[font-other-normal]':
                    icl_lp_font_other_normal = value;
                    break;
                case 'icl_lang_sel_config[font-other-hover]':
                    icl_lp_font_other_hover = value;
                    break;                
                case 'icl_lang_sel_config[background-other-normal]':
                    icl_lp_background_other_normal = value;
                    break;
                case 'icl_lang_sel_config[background-other-hover]':
                    icl_lp_background_other_hover = value;
                    break;                
                case 'icl_lang_sel_config[border]':
                    icl_lp_border = value;
                    break;            
            }            
            
        });
        
        iclRenderLangPreview();
        
    }
}

	// Picker f
var cp = new ColorPicker();
function pickColor(color) {
			jQuery('#'+icl_cp_target).val(color);
			jQuery('#'+icl_cp_target).trigger('keyup');
		}
cp.writeDiv();