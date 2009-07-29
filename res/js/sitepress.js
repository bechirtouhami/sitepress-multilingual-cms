function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      if (oldonload) {
        oldonload();
      }
      func();
    }
  }
}
addLoadEvent(function(){
    var lhid = document.createElement('input');
    lhid.setAttribute('type','hidden');
    lhid.setAttribute('name','lang');
    lhid.setAttribute('value',icl_lang);     
    src = document.getElementById('searchform');   
    if(src){
        src.appendChild(lhid);
        src.action=icl_home; 
    }
});