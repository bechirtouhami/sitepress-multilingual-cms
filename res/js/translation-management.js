jQuery(document).ready(function(){
    
    jQuery('#icl_tm_selected_user').change(function(){
        if(jQuery(this).val()){
            jQuery('.icl_tm_lang_pairs').slideDown();
        }else{
            jQuery('.icl_tm_lang_pairs').slideUp();
            jQuery('#icl_tm_adduser .icl_tm_lang_pairs_to').hide();
            jQuery('#icl_tm_add_user_errors span').hide();
        }
        
    });
    
    jQuery('#icl_tm_adduser .icl_tm_from_lang').change(function(){
        if(jQuery(this).attr('checked')){
           jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').slideDown();
        }else{
            jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').find(':checkbox').removeAttr('checked'); 
            jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').slideUp();
        }
    });
    
    jQuery('#icl_tm_adduser').submit(function(){
        jQuery('#icl_tm_add_user_errors span').hide();
        if(jQuery('.icl_tm_to_lang:checked').length==0){
            jQuery('#icl_tm_add_user_errors .icl_tm_no_to').show();    
            return false;    
        }
    });
    
    jQuery('a[href="#hide-advanced-filters"]').click(function(){        
        athis = jQuery(this);        
        icl_save_dashboard_setting('advanced_filters',0,function(f){
            jQuery('#icl_dashboard_advanced_filters').slideUp()
            athis.hide();
            jQuery('a[href="#show-advanced-filters"]').show();
        });
    })
    
    jQuery('a[href="#show-advanced-filters"]').click(function(){
        athis = jQuery(this);        
        icl_save_dashboard_setting('advanced_filters',1,function(f){
            jQuery('#icl_dashboard_advanced_filters').slideDown()
            athis.hide();
            jQuery('a[href="#hide-advanced-filters"]').show();
        });
    })
    
    function icl_save_dashboard_setting(setting, value, callback){
        jQuery('#icl_dashboard_ajax_working').fadeIn();
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: 'icl_ajx_action=save_dashboard_setting&setting='+setting+'&value='+value,
            success: function(msg){
                jQuery('#icl_dashboard_ajax_working').fadeOut();
                callback(msg);                
            }
        });         
    }
            
})
