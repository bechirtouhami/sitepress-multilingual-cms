jQuery(document).ready(function(){
    show_messages();    
});

var do_message_refresh = false;
function show_messages() {
    var command = "icl_ajx_action=icl_messages";
    if (do_message_refresh) {
        command += "&refresh=1";
        do_message_refresh = false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: command,
        cache: false,
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                jQuery('#icl_reminder_list').html(spl[1]);
                jQuery('#icl_reminder_message').fadeIn();
                icl_tb_init('a.icl_thickbox');
                icl_tb_set_size('a.icl_thickbox');
            } else {
                jQuery('#icl_reminder_message').fadeOut();
            }  
        }
    }); 

}

function icl_tb_init(domChunk) {
    // copied from thickbox.js
    // add code so we can detect closure of popup

    jQuery(domChunk).unbind('click');
    
    jQuery(domChunk).click(function(){
    var t = this.title || this.name || "ICanLocalize Reminder";
    var a = this.href || this.alt;
    var g = this.rel || false;
    tb_show(t,a,g);
    
    do_message_refresh = true;
    jQuery('#TB_window').bind('unload', function(){
        if (t == "ICanLocalize Reminder" && do_message_refresh) {
            
            // do_message_refresh will only be true if we close the popup.
            // if the dismiss link is clicked then do_message_refresh is set to false before closing the popup.
            
            jQuery('#icl_reminder_list').html('Refreshing messages  ' + icl_ajxloaderimg);
            show_messages();
            }
        });
    
    this.blur();
    return false;
    });
}


function icl_tb_set_size(domChunk) {
    if (typeof(tb_getPageSize) != 'undefined') {

        var pagesize = tb_getPageSize();
        jQuery(domChunk).each(function() {
            var url = jQuery(this).attr('href');
            url += '&width=' + (pagesize[0] - 150);
            url += '&height=' + (pagesize[1] - 150);
            url += '&tb_avail=1'; // indicate that thickbox is available.
            jQuery(this).attr('href', url);
        });
    }
}

function dismiss_message(message_id) {
    do_message_refresh = false;
    jQuery('#icl_reminder_list').html('Refreshing messages  ' + icl_ajxloaderimg);
    tb_remove();
    
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_delete_message&message_id=" + message_id,
        async: false,
        success: function(msg){
        }
    }); 
    
    show_messages();
}