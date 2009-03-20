addLoadEvent(function(){         
    jQuery('#icl_navigation_show_cat_menu').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('label[for="icl_navigation_cat_menu_title"]').fadeIn();
        }else{
            jQuery('label[for="icl_navigation_cat_menu_title"]').fadeOut();
        }
    })
    jQuery('#icl_navigation_form').submit(iclSaveForm);
    
});

