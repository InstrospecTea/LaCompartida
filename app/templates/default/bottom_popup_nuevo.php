</td>
</tr>
</table>
<div id="dialogomodal" style="display:none;" ></div>  

<?php
$laurl= $_SERVER['HTTP_HOST']; 
$punto=strpos($laurl,'.'); 
$subdomain=substr($laurl,0,$punto); 
$maindomain=str_replace($subdomain.'.','',$laurl); 
if($subdomain) $subdomain='/'.$subdomain;
$elpath=$subdomain.$_SERVER['PHP_SELF'];
$pathseguro='https://'.$laurl.$_SERVER['PHP_SELF'];
?>
<script type="text/javascript">
    jQuery.when(jQuery.ajax({async: false,type: "GET", url: 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js', dataType: 'script' }) ).then(function() {
          jQuery('#dialogomodal').dialog({
                        autoOpen: false,
			height: 'auto',
			width: 800,
                        maxHeight: 550,
			modal: true,
                        show: 'fade',
                        hide: 'fade',
                        position: ['center',30],
                        dialogClass: 'lemondialog',
                        close: function() { 
                            jQuery(this).html('');
                            console.log('cerrado');
                        }
         });
    });  

  
var _sf_async_config={};
/** CONFIGURATION START **/
_sf_async_config.uid = 32419;
_sf_async_config.domain = "<?php echo $maindomain; ?>"; 
_sf_async_config.path = "<?php echo $elpath; ?>";
/** CONFIGURATION END **/

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


/* <![CDATA[   */


 function downloadJSAtOnload() {
 
 if (jQuery('#DigiCertClickID_iIR9fwBQ').length>0) {
    if ("https:" == document.location.protocol) {
        var __dcid = __dcid || [];__dcid.push(["DigiCertClickID_iIR9fwBQ", "3", "s", "black", "iIR9fwBQ"]);(function(){var cid=document.createElement("script");cid.type="text/javascript";cid.async=true;cid.src=("https:" === document.location.protocol ? "https://" : "http://")+"seal.digicert.com/seals/cascade/seal.min.js";var s = document.getElementsByTagName("script");var ls = s[(s.length - 1)];ls.parentNode.insertBefore(cid, ls.nextSibling);}());
    } else {
       var pathseguro="<?php echo $pathseguro; ?>";
       if (_sf_async_config.domain=='thetimebilling.com') {
           jQuery('#DigiCertClickID_iIR9fwBQ').html('<a style="border:0;text-decoration:none;" href="'+pathseguro+'"><img src="https://files.thetimebilling.com/templates/no_ssl.png" style="text-decoration:none;vertical-align:top;border: 0 none;" /></a>');
       }
    }
}
        
	jQuery("head").append("<link id='uicss' />");
        jQuery("#uicss").attr({ rel:  "stylesheet", type: "text/css",  href: "https://files.thetimebilling.com/jquery-ui.css"   });

	

jQuery.when(jQuery.ajax({async: false,type: "GET", url: 'https://files.thetimebilling.com/fw/js/droplinemenu.js', dataType: 'script' }) ).then(function() {
    droplinemenu.buildmenu("droplinetabs1");
});
jQuery.when(jQuery.ajax({async: false,type: "GET", url: 'https://files.thetimebilling.com/fw/js/curvycorners.js', dataType: 'script' }) ).then(function() {
   var settings = {
      tl: { radius: 5 },
      tr: { radius: 5 },
      bl: { radius: 5 },
      br: { radius: 5 },
      antiAlias: true
    }
    var divObj = document.getElementById("fd_menu_grey");
    curvyCorners(settings, divObj); 
});
SetFocoPrimerElemento();

}
 if (window.addEventListener)
 window.addEventListener("load", downloadJSAtOnload, false);
 else if (window.attachEvent)
 window.attachEvent("onload", downloadJSAtOnload);
 else window.onload = downloadJSAtOnload;

 function nuovaFinestra(name, w, h, url, opciones ) {

                if(top.window.jQuery('#soymodal').length>0 || top.window.jQuery('#dialogomodal').length==0) {
                             return  nuevaVentana( name, w, h, url, opciones );
                        } else {
                             jQuery.when( top.window.jQuery('body').animate({scrollTop:0}, 1000)).done(function() {
                             top.window.jQuery('#dialogomodal').dialog('open').dialog('option','title',name.replace('_',' ')).dialog('option','height',h).dialog('option','width',w);
                             top.window.jQuery('#dialogomodal').html('<iframe id="soymodal" src="'+url+'" style="height:100%;width:100%" frameborder="0"></iframe>');
                             })
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
           if(  parent.window.Refrescarse )   parent.window.Refrescarse(); 

           parent.window.jQuery('#dialogomodal').dialog('close').find('iframe').remove();
        }
        
}  

/* ]]> */
</script>
</body>
</html>