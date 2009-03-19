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
    jQuery('#icl_navigation_show_cat_menu').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('label[for="icl_navigation_cat_menu_title"]').fadeIn();
        }else{
            jQuery('label[for="icl_navigation_cat_menu_title"]').fadeOut();
        }
    })
    jQuery('#icl_navigation_form').submit(iclSaveForm);
    
});

