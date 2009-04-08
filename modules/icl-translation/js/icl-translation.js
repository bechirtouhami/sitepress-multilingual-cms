jQuery(document).ready(function(){
    jQuery('table.fixed th :checkbox').click(
        function(){
            if(jQuery(this).attr('checked')){
                jQuery('table.fixed :checkbox').attr('checked','checked');    
            }else{
                jQuery('table.fixed :checkbox').removeAttr('checked');    
            }
        }
    )
});