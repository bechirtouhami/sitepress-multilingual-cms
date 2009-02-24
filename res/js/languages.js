addLoadEvent(function(){     
    jQuery('#icl_change_default_button').click(editingDefaultLanguage);
    jQuery('#icl_save_default_button').click(saveDefaultLanguage);
    jQuery('#icl_cancel_default_button').click(doneEditingDefaultLanguage);
    jQuery('#icl_add_remove_button').click(showLanguagePicker);            
    jQuery('#icl_cancel_language_selection').click(hideLanguagePicker);
    jQuery('#icl_save_language_selection').click(saveLanguageSelection);                        
    jQuery('#icl_enabled_languages input').attr('disabled','disabled');    
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
                jQuery('#icl_language_pairs').html(spl[2]);
            }else{                        
                fadeInAjxResp('#icl_ajx_response', icl_ajx_error,true);
            }                    
        }
    });

    hideLanguagePicker();
    
}       



