jQuery(document).ready(function(){
    jQuery('input[name="icl_enable_absolute_links"]').change(function(){
        jQuery('#icl_ajax_loader_alp').html(icl_ajxloaderimg);
        if(jQuery(this).attr('checked')){
            jQuery('#icl_alp_wrap').load(location.href + ' #icl_alp_wrap',{icl_enable_alp:1}, function(){jQuery('#icl_ajax_loader_alp').html('');location.reload()});
        }else{
            jQuery('#icl_alp_wrap').load(location.href + ' #icl_alp_wrap',{icl_enable_alp:0}, function(){jQuery('#icl_ajax_loader_alp').html('');location.reload()});
        }
    });
    
    jQuery('a[href="#revert-links"]').click(function(){
        jQuery('#icl_ajax_loader_alp').html(icl_ajxloaderimg);
        jQuery('#icl_alp_wrap').load(location.href + ' #icl_alp_wrap',{icl_enable_alp:1,ilc_enable_alp_onetime:1}, function(){
            jQuery('#icl_ajax_loader_alp').html('');
            alp_do_revert_urls();
        });
    });
    
});

function alp_do_revert_urls(){
    jQuery('#alp_revert_urls').attr('disabled','disabled');
    jQuery('#alp_revert_urls').attr('value','Running');
    jQuery.ajax({
        type: "POST",
        url: location.href,
        data: "alp_ajx_action=alp_revert_urls",
        success: function(msg){                                                    
            if(-1==msg || msg==0){
                jQuery('#alp_ajx_ldr_2').fadeOut();
                jQuery('#alp_rev_items_left').html('');
                window.clearTimeout(req_rev_timer);
                jQuery('#alp_revert_urls').removeAttr('disabled');                            
                jQuery('#alp_revert_urls').attr('value','Start');                            
                jQuery('#icl_alp_wrap').load(location.href + ' #icl_alp_wrap',{icl_enable_alp:0}, function(){jQuery('#icl_ajax_loader_alp').html('');});
            }else{
                jQuery('#alp_rev_items_left').html(msg + ' items left');
                req_rev_timer = window.setTimeout(alp_do_revert_urls,3000);
                jQuery('#alp_ajx_ldr_2').fadeIn();
            }                            
        },
        error: function (msg){
            //alert('Something went wrong');
        }                                                            
    });
}
