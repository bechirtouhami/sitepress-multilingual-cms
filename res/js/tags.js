jQuery(document).ready(function(){  
    if(jQuery('form input[name="action"]').attr('value')=='addtag'){
        jQuery('.form-wrap p[class="submit"]').before(jQuery('#icl_tag_menu').html());    
    }else{
        jQuery('#edittag table[class="form-table"]').append(jQuery('#edittag table[class="form-table"] tr:last').clone());    
        jQuery('#edittag table[class="form-table"] tr:last th:first').html('&nbsp;');
        jQuery('#edittag table[class="form-table"] tr:last td:last').html(jQuery('#icl_tag_menu').html());        
    }    
    jQuery('#icl_tag_menu').remove();
        
});