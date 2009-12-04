jQuery(document).ready(function(){
    
    display_side_bar_if_required();
    show_help_links();
    
    jQuery('#icl_sidebar_hide').click(icl_hide_sidebar);
    jQuery('#icl_sidebar_show').click(icl_show_sidebar);
    
});

function show_help_links() {
    var command = "icl_ajx_action=icl_help_links";
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: command,
        cache: false,
        success: function(msg){
            spl = msg.split('|');
            if(spl[0] == '1'){
                jQuery('#icl_help_links').html(spl[1]);
                display_side_bar_if_required();
            } else {
                jQuery('.icl_sidebar').fadeOut();
            }
        }
    });
}

function display_side_bar_if_required() {

    sidebar_width = 207;
    if(jQuery('#icl_sidebar_full').css('display')=='none') {
        jQuery('#icl_sidebar').css({'width': '25px'});
        sidebar_width = 25;
    } else {
        jQuery('#icl_sidebar').css({'width': '207px'});
    }
    
    margin_right = '' + (sidebar_width + 30) + 'px'
    jQuery('#icl_pro_content').css({'margin-right': margin_right});
    jQuery('#icl_sidebar').fadeIn();
    
}

function icl_show_sidebar() {

    jQuery('#icl_sidebar_hide_div').fadeOut();
    jQuery('#icl_sidebar_full').fadeIn(display_side_bar_if_required);
    
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_show_sidebar&state=show",
        async: true,
        success: function(msg){
        }
    }); 
    
    
}

function icl_hide_sidebar() {
    
    jQuery('#icl_sidebar_full').fadeOut(display_side_bar_if_required);
    jQuery('#icl_sidebar_hide_div').fadeIn();

    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_show_sidebar&state=hide",
        async: true,
        success: function(msg){
        }
    }); 
    
}