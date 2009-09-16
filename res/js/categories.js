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
        ajx = ajx.replace(/pagenum=([0-9]+)/,'');
        if(-1 == location.href.indexOf('?')){
            url_glue='?';
        }else{
            url_glue='&';
        }   
        jQuery('#posts-filter').parent().load(ajx+url_glue+'lang='+lang + ' #posts-filter', {}, function(resp){
            strt = resp.indexOf('<span id="icl_subsubsub">');
            endd = resp.indexOf('</span>\'', strt);
            lsubsub = resp.substr(strt,endd-strt+7);
            jQuery('table.widefat').before(lsubsub);            
                         
            start_sel = resp.indexOf('<select name=\'category_parent\' id=\'category_parent\' class=\'postform\' >');
            end_sel = resp.indexOf('</select>', start_sel);
            sel_sel = resp.substr(start_sel+70, end_sel-start_sel-70);            
            jQuery('#category_parent').html(sel_sel)
            
        });        
   })     
});

jQuery(function($) {
        // this function will be called after a category is added to the list
        // We need to check to see if the trid is in the url
        // and redirect to the page without the trid after "Add Category" is
        // done for a translation.
	var addAfter3 = function( r, settings ) {
            var temp = location.search.substring(1); // remove the '?'
            var params = temp.split('&');
            
            temp = '';
            var redirect_required = false;
            for (var i in params) {
                
                if (params[i].substr(0, 5) == 'trid=') {
                    redirect_required = true
                } else {
                    if (temp != '') {
                        temp += '&' + params[i];
                    } else {
                        temp += params[i];
                    }
                }
            }

            if (redirect_required) {
                var new_url = location.protocol + '//' + location.host + location.pathname;
                if (temp != '') {
                    new_url += '?' + temp;
                }
                window.location = new_url;
            }
	}
        
	$('#the-list').wpList( { addAfter: addAfter3} );
        

});
