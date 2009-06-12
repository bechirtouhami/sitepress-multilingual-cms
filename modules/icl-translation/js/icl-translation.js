jQuery(document).ready(function(){
    jQuery('#icl-translation-dashboard th :checkbox').click(
        function(){
            if(jQuery(this).attr('checked')){
                jQuery('#icl-translation-dashboard :checkbox').attr('checked','checked');    
                iclUpdateTranslationEstimate(parseInt(jQuery('#icl-cw-total').html()), true);
                jQuery('#icl-tr-sel-doc').removeAttr('disabled');    
            }else{
                jQuery('#icl-translation-dashboard :checkbox').removeAttr('checked');  
                iclUpdateTranslationEstimate(0, true);  
                jQuery('#icl-tr-sel-doc').attr('disabled','disabled');    
            }   
        }
    );    
    jQuery('#icl-translation-dashboard td :checkbox').click(
        function(){
            if(!jQuery(this).attr('checked')){            
                jQuery('#icl-translation-dashboard th :checkbox').removeAttr('checked');    
                if (jQuery('table.fixed td :checkbox:checked').length==0){
                    jQuery('#icl-tr-sel-doc').attr('disabled','disabled');    
                }
            }else{
                no_language_selected = true;
                jQuery('#icl-tr-opt :checkbox').each(function(){
                    if(jQuery(this).attr('checked')) no_language_selected = false; 
                });
                if(no_language_selected){
                    jQuery('#icl-tr-sel-doc').attr('disabled','disabled');    
                } else {
                    jQuery('#icl-tr-sel-doc').removeAttr('disabled');
                }
            }
            
        }
    );    
    
    jQuery('table.fixed td :checkbox').click(function(){            
        var words = parseInt(jQuery('#icl-cw-'+jQuery(this).val()).html());
        if(jQuery(this).attr('checked')){
            iclUpdateTranslationEstimate(words);
        }else{
            iclUpdateTranslationEstimate(-1 * words);
        }            
    
        
    });
    
    jQuery('#icl-tr-sel-doc').click(function(){        
        
        if(jQuery('table.fixed td :checkbox:checked').length==0) return false;
    
        target_languages = new Array();
        jQuery('#icl-tr-opt :checkbox').each(function(){
            if(jQuery(this).attr('checked')){
                target_languages.push(jQuery(this).val());
            }
        });
        jQuery('#icl_ajx_response').fadeIn();
        jQuery('#icl-tr-sel-doc').attr('disabled','disabled');    
        jQuery('#icl-translation-dashboard :checkbox').each(function(){
            if(jQuery(this).attr('checked') && jQuery(this).val()!='on'){
                post_id = jQuery(this).val();
                tmpback = jQuery('#icl-tr-status-'+post_id).html();
                jQuery.ajax({
                    type: "POST",
                    async: false,
                    url: icl_ajx_url,
                    data: "icl_ajx_action=send_translation_request&post_id="+post_id+'&type=post&target_languages='+target_languages.join('#'),
                    success: function(msg){
                        if(msg > 0){
                            jQuery('#icl-tr-status-'+post_id).html(jQuery('#icl_message_2').html());
                        }else{
                            jQuery('#icl-tr-status-'+post_id).html(tmpback);
                        }
                        jQuery('#icl-tr-status-'+post_id).fadeIn();
                        location.href = location.href;
                    }
                });
            }            
        });        
        jQuery('#icl_ajx_response').html(jQuery('#icl_message_1').html());
        jQuery('#icl-tr-sel-doc').removeAttr('disabled');    
        //window.setTimeout('location.reload()',3000);
        
    });
    
    jQuery('#icl-tr-opt :checkbox').click(function(){
        iclUpdateTranslationEstimate();
        if(jQuery(this).attr('checked')){
            jQuery('#icl-tr-sel-doc').removeAttr('disabled');    
        }else{
            none_selected = true;
            jQuery('#icl-tr-opt :checkbox').each(function(){
                if(jQuery(this).attr('checked')) none_selected = false; 
            });
            if(none_selected){
                jQuery('#icl-tr-sel-doc').attr('disabled','disabled');    
            }
        }
    })
      
    jQuery('a.translation_details_but').click(toogleTranslationDetails);
    
    if(jQuery('input[name="access[website_id]"]').val()){
        jQuery.ajax({
            type: "POST",
            async: false,
            url: icl_ajx_url,
            data: "icl_ajx_action=get_translator_status"
        });
    }

    // initialize the word count
    jQuery('#icl-translation-dashboard :checkbox').each(function(){
        var words = parseInt(jQuery('#icl-cw-'+jQuery(this).val()).html());
        if(jQuery(this).attr('checked')){
            iclUpdateTranslationEstimate(words);
        }
    });
    
    
});

function iclUpdateTranslationEstimate(n, set){
    var selected_languages_count = getSelectedLanguagesCount();
    if(n == undefined) n = 0;
    if(set != undefined){
        words = parseInt(n) ;
    }else{
        words = parseInt(n) + parseInt(jQuery('#icl-estimated-words-count').html());
    }    
    if(words=='') words = '0';
    jQuery('#icl-estimated-words-count').html(words);
    quote = Math.round(100 * words * 0.07, 2)/100;
    if(quote=='') quote = '0';
    jQuery('#icl-estimated-quote').html(quote);

    quote = Math.round(100 * words * 0.07 * selected_languages_count, 2)/100;
    if(quote=='') quote = '0';
    jQuery('#icl-estimated-quote-all').html(quote);
    
    if (selected_languages_count > 1) {
        jQuery('#icl-estimated-all').fadeIn();
    } else {
        jQuery('#icl-estimated-all').fadeOut();
    }
}

function getSelectedLanguagesCount(){
    var selected_languages_count = 0;
    jQuery('#icl-tr-opt :checkbox').each(function(){
        if(jQuery(this).attr('checked')){
            selected_languages_count++;
        }
    });
    return selected_languages_count;    
}

var icl_tr_details_selected_rid = 0;
function toogleTranslationDetails(){    
    jQuery('.icl-tr-details:visible').slideUp();
    var rid = jQuery(this).attr('href').replace(/#translation-details-/,'');        
    if(rid == icl_tr_details_selected_rid){
        icl_tr_details_selected_rid = 0;
        return;
    } 
    icl_tr_details_selected_rid = rid;
    var tr = jQuery(this).parent().parent();
    var last_col = tr.find('td:eq(4)');
    last_col.append(icl_ajxloaderimg);    
    tr.find('td span.icl-tr-details').load(location.href.replace(/#(.*)$/,''), {
        icl_ajx_req:'get_translation_details',
        rid:rid
    }, function(){        
        last_col.find('img').fadeOut('fast',function(){jQuery(this).remove()});
        jQuery(this).slideDown();
    });    
}