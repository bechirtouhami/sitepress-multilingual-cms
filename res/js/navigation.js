addLoadEvent(function(){         
    jQuery('a[href="#read-more"]').click(function(){
        if(jQuery(this).html().indexOf('+') != -1){
            jQuery('#icl_nav_read_more').fadeIn();
            if(jQuery('#icl_nav_read_more').attr('value')==''){
                jQuery('#icl_nav_read_more').load(icl_ajx_url,{icl_ajx_action:'nav_read_more'}, function(resp){
                    jQuery('#icl_nav_read_more').attr('value',resp);
                });    
            }                        
            jQuery(this).html(jQuery(this).html().replace(/\+/g,'-'));            
        }else{
            jQuery(this).html(jQuery(this).html().replace(/\-/g,'+'));            
            jQuery('#icl_nav_read_more').fadeOut();
        }
    });
    jQuery('#icl_enable_nav_but').click(function(){
        enabled = jQuery('#icl_enable_nav').attr('checked')?1:0;
        jQuery('#icl_enable_nav_ajxresp').show().load(icl_ajx_url,{icl_ajx_action:'nav_save',enabled:enabled}, function(){
            window.setTimeout("jQuery('#icl_enable_nav_ajxresp').fadeOut()", 3000);
        });
    })
});

