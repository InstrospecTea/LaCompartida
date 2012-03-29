jQuery("head").append("<link id='uicss' />");

jQuery.ajax({async: false, cache:true, type: "GET", url: 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js', dataType: 'script', success: function() {     
jQuery.ajax({async: true, cache:true, type: "GET", url: 'https://jquery-ui.googlecode.com/svn/trunk/ui/i18n/jquery.ui.datepicker-es.js', dataType: 'script'});
jQuery.datepicker.setDefaults(   jQuery.datepicker.regional[ "es" ]    );
   jQuery.when(jQuery("#uicss").attr({ rel:  "stylesheet", type: "text/css",  href: "https://estaticos.thetimebilling.com/jquery-ui.css"   }) )
		.then(function() {
		if(window.location==top.window.location) {
		    jQuery('#dialogomodal').dialog({
			    autoOpen: false, height: 'auto',width: 800,  maxHeight: 550, modal: true,  
			    show: 'fade', hide: 'fade',  position: ['center',30],  dialogClass: 'lemondialog',
			    close: function(event,ui) { 
				 var prescroll=jQuery(this).attr('alt');
							    if( top.window.Refrescarse ) {
								top.window.Refrescarse();
							      top.window.jQuery('#dialogomodal .activomodal').show();
							     
							    } else {
							    if( parent.window.Refrescar ) 	parent.window.Refrescar();
							    }

			       top.window.jQuery('#soymodal').attr({'src':'','rel':'inactivo'});
			       							     top.window.jQuery('html, body').animate({scrollTop:prescroll},500);


			    }
		    }).attr('rel','activomodal').append(DivLoading).append('<iframe id="soymodal" rel="inactivo" style="display:none;height:100%;width:100%" frameborder="0"></iframe>');
		     }
	      if(typeof  YoucangonowMichael == 'function')      YoucangonowMichael(); 
	//if(window.console)  console.log('YCGNM es '+ top.window.YoucangonowMichael);
	  
    });  
   }});




(function(){
  function loadChartbeat() {
    window._sf_endpt=(new Date()).getTime();
    var e = document.createElement('script');
    e.setAttribute('language', 'javascript');
    e.setAttribute('type', 'text/javascript');
    e.setAttribute('src',
       (("https:" == document.location.protocol) ? "https://a248.e.akamai.net/chartbeat.download.akamai.com/102508/" : "http://static.chartbeat.com/") +
       "js/chartbeat.js");
    document.body.appendChild(e);
  }
  var oldonload = window.onload;
  window.onload = (typeof window.onload != 'function') ?
     loadChartbeat : function() { oldonload(); loadChartbeat(); };
})();


SetFocoPrimerElemento();


function nuovaFinestra(name, w, h, url, opciones ) {

                if(1==0 && top.window.jQuery('#soymodal').attr('rel')=='inactivo' && top.window.jQuery('#dialogomodal').length>0 && top.window.jQuery('#dialogomodal').attr('rel')=='activomodal') {
                            
				var inipos=Math.max(top.window.jQuery('body').scrollTop(), top.window.jQuery('html').scrollTop());
			
			
			    
			    jQuery.when( top.window.jQuery('html, body').animate({scrollTop:0}, 500) ).done(function() {
				top.window.jQuery('#dialogomodal').dialog('open').attr('alt',inipos).dialog('option','title',name.replace('_',' ')).dialog('option','height',h).dialog('option','width',w);
				    
					   			
					    top.window.jQuery('#soymodal').attr({'src':url, 'rel':'activo'}).show('fast',function() {
						 top.window.jQuery('#dialogomodal .divloading').slideUp();
					    });
				    
				
			    });
                        } else {
			  if(window.console)  console.log(top.window.jQuery('#dialogomodal'));
			     return  nuevaVentana( name, w, h, url, opciones );  
                        }
                }
function Cerrar() {

        if(window.location==parent.window.location) { //estoy en un popup
             if(  parent.window.Refrescarse ) {
                          parent.window.Refrescarse(); 
                   } else if(  parent.window.Refrescar ) {
                         parent.window.Refrescar(); 
                   } else if (window.opener!==undefined && window.opener.Refrescar) {
                        window.opener.Refrescar();
                   }
            window.close();
        } else { //estoy en un overlay
           if(  top.window.Refrescarse )   top.window.Refrescarse(); 
          
	top.window.jQuery('#dialogomodal').dialog('close');
	top.window.jQuery('#soymodal').attr({'src':'','rel':'inactivo'});        
    }
        
}   
