addLoadEvent(function(){     
    jQuery('#icl_theme_localization').submit(iclSaveThemeLocalization);
    jQuery('#icl_theme_localization_type').submit(iclSaveThemeLocalizationType);
    jQuery('#icl_tl_rescan').click(iclThemeLocalizationRescan);
});

function iclSaveThemeLocalization(){
    var ajx = location.href.replace(/#(.*)$/,'');
    if(-1 == location.href.indexOf('?')){
        url_glue='?';
    }else{
        url_glue='&';
    }
    spl = jQuery(this).serialize().split('&');    
    var parameters = {};
    for(var i=0; i< spl.length; i++){        
        var par = spl[i].split('=');
        eval('parameters.' + par[0] + ' = par[1]');
    }    
    jQuery('#icl_theme_localization_wrap').load(location.href + ' #icl_theme_localization_subwrap', parameters, function(){
        fadeInAjxResp('#icl_ajx_response_fn', icl_ajx_saved);                                                 
    }); 
    return false;   
}

function iclSaveThemeLocalizationType(){
    var formname = jQuery(this).attr('name');
    ajx_resp = jQuery('form[name="'+formname+'"] .icl_ajx_response').attr('id');
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action="+jQuery(this).attr('name')+"&"+jQuery(this).serialize(),
        success: function(msg){
            spl = msg.split('|');
            location.href=location.href.replace(/#(.*)$/,'');
        }
    });
    return false;         
}

function iclThemeLocalizationRescan(){
    var thisb = jQuery(this);
    thisb.next().fadeIn();
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_tl_rescan",
        success: function(msg){
            thisb.next().fadeOut();
            location.href=location.href.replace(/#(.*)$/,'');
        }
    });    
    return false;
}
