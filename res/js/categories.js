jQuery(document).ready(function(){    
    if(jQuery('form[name="addcat"]').html()){
        jQuery('form[name="addcat"] p[class="submit"]').before(jQuery('#icl_category_menu').html());    
    }else{
        jQuery('form[name="editcat"] table[class="form-table"]').append(jQuery('form[name="editcat"] table[class="form-table"] tr:last').clone());    
        jQuery('form[name="editcat"] table[class="form-table"] tr:last th:first').html('&nbsp;');
        jQuery('form[name="editcat"] table[class="form-table"] tr:last td:last').html(jQuery('#icl_category_menu').html());
    }    
    jQuery('#icl_category_menu').remove();
   
   jQuery('select[name="icl_category_language"]').change(function(){
        var lang = jQuery(this).val();
        var ajx = location.href.replace(/#(.*)$/,'');
        if(-1 == location.href.indexOf('?')){
            url_glue='?';
        }else{
            url_glue='&';
        }   
        jQuery('#posts-filter').parent().load(ajx+url_glue+'lang='+lang + ' #posts-filter');        
   })     
});