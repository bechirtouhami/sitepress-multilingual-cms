jQuery(document).ready(function(){    
    if(jQuery('form[name="addcat"]').html()){
        jQuery('form[name="addcat"] p[class="submit"]').before(jQuery('#icl_category_menu').html());    
    }else{
        jQuery('form[name="editcat"] table[class="form-table"]').append(jQuery('form[name="editcat"] table[class="form-table"] tr:last').clone());    
        jQuery('form[name="editcat"] table[class="form-table"] tr:last th:first').html('&nbsp;');
        jQuery('form[name="editcat"] table[class="form-table"] tr:last td:last').html(jQuery('#icl_category_menu').html());
    }    
    jQuery('#icl_category_menu').remove();
        
});

