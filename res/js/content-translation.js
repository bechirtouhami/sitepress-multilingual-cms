addLoadEvent(function(){         
    jQuery('#icl_language_pairs .icl_tr_from').change(toggleTranslationPairsSub);
    jQuery('#icl_save_language_pairs').click(saveLanguagePairs);
    jQuery('form[name="icl_more_options"]').submit(iclSaveForm);
    jQuery('form[name="icl_editor_account"]').submit(iclSaveForm);
    jQuery('#icl_user_fix').click(iclValidateUser);

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

function iclSaveForm(){
    var formname = jQuery(this).attr('name');
    jQuery('form[name="'+formname+'"] .icl_form_errors').html('').hide();
    ajx_resp = jQuery('form[name="'+formname+'"] .icl_ajx_response').attr('id');
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action="+jQuery(this).attr('name')+"&"+jQuery(this).serialize(),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp('#'+ajx_resp, icl_ajx_saved);                                         
            }else{                        
                jQuery('form[name="'+formname+'"] .icl_form_errors').html(spl[1]).fadeIn();
                fadeInAjxResp('#'+ajx_resp, icl_ajx_error,true);
            }  
        }
    });
    return false;     
}

function iclValidateUser(){
    jQuery.post(icl_ajx_url, { icl_ajx_action: "iclValidateUser" },
      function(data){
        if(data==1){    
            location.reload();
        }
      }, "text");
}