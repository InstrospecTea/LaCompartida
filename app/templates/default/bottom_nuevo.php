</td>
 <td  style="background:transparent;">&nbsp;</td>
  </tr>
  <tr>
<td  style="background:transparent;">&nbsp;</td>
<td height="40px" class="fondo_cierre" align="left">&nbsp;</td>
<td  style="background:transparent;">&nbsp;</td>
  </tr>
  <tr>
<td style="background:transparent;" colspan="3">
    <div id="ultimocontenedor" style="height:70px; width:130px;margin:0 auto 5px ;text-align:center;">
        <img src="<?=Conf::ImgDir()?>/logo_bottom.jpg" width="125" height="37" style="padding:15px 15px 0;float:left;" />&nbsp;
<div id="DigiCertClickID_iIR9fwBQ" style="float:right;" >&nbsp;</div>
</div>


</td>
  </tr>
    </table>

    
     </td>
  </tr>
</table>
<div id="dialogomodal" style="display:none;" ></div> 
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-21661196-2']);
_gaq.push(['_setDomainName', 'none']);
_gaq.push(['_setAllowLinker', true]);
_gaq.push(['_trackPageview']);

(function() {
var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>
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
    
var _sf_async_config={};
/** CONFIGURATION START **/
_sf_async_config.uid = 32419;
_sf_async_config.domain = "<?php echo $maindomain; ?>"; 
_sf_async_config.path = "<?php echo $elpath; ?>";
_sf_async_config.pathseguro="<?php echo $pathseguro; ?>";
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
jQuery.when(jQuery.ajax({async: false,type: "GET", url: 'https://files.thetimebilling.com/fw/js/droplinemenu.js', dataType: 'script' }) ).then(function() {
        droplinemenu.buildmenu("droplinetabs1");
    });
SetFocoPrimerElemento();
  
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
							if( top.window.Refrescar )
								top.window.Refrescar();
                            jQuery(this).html('');
                            //console.log('cerrado');
                        }
         });
    });  

  

 function downloadJSAtOnload() {
    
    jQuery("head").append("<link id='uicss' />");
    jQuery("#uicss").attr({ rel:  "stylesheet", type: "text/css",  href: "https://files.thetimebilling.com/jquery-ui.css"   });
 
     if (_sf_async_config.domain=='thetimebilling.com') {
         if (jQuery('#DigiCertClickID_iIR9fwBQ').length>0) {
                jQuery('#ultimocontenedor').css({'width':'330px'});
                if ("https:" == document.location.protocol) {
                   var __dcid = __dcid || [];__dcid.push(["DigiCertClickID_iIR9fwBQ", "3", "s", "black", "iIR9fwBQ"]);(function(){var cid=document.createElement("script");cid.type="text/javascript";cid.async=true;cid.src=("https:" === document.location.protocol ? "https://" : "http://")+"seal.digicert.com/seals/cascade/seal.min.js";var s = document.getElementsByTagName("script");var ls = s[(s.length - 1)];ls.parentNode.insertBefore(cid, ls.nextSibling);}());
                } else {
                        jQuery('#DigiCertClickID_iIR9fwBQ').html('<a style="border:0;text-decoration:none;" href="'+_sf_async_config.pathseguro+'"><img src="https://files.thetimebilling.com/templates/no_ssl.png" style="text-decoration:none;vertical-align:top;border: 0 none;margin-top:0;position:relative;top:0;right:0;" /></a>');
                }
            }
        }
     
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
    
  

    jQuery.when(jQuery.ajax({async: true,type: "GET", 
    url: 'https://asset0.zendesk.com/external/zenbox/v2.4/zenbox.js', dataType: 'script' }) ).then(function() {
    
        jQuery("head").append("<link id='zenboxcss' />");
        jQuery("#zenboxcss").attr({
          rel:  "stylesheet",
          type: "text/css",
          href: "https://asset0.zendesk.com/external/zenbox/v2.4/zenbox.css"
        });
        
    function getInternetExplorerVersion() {
	  var rv = -1; // Return value assumes failure.
	  if (navigator.appName == 'Microsoft Internet Explorer')
	  {
		var ua = navigator.userAgent;
		var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
		if (re.exec(ua) != null)
		  rv = parseFloat( RegExp.$1 );
	  }
	  return rv;
	}
	if (typeof(Zenbox) !== "undefined") {
		var ver = getInternetExplorerVersion();
		var lado = "Right";
		var imagen_fondo = "https://files.thetimebilling.com/templates/default/img/lemontech_logo_" + lado.toLowerCase() +".png";
		if( ver > -1) imagen_fondo = "https://files.thetimebilling.com/templates/default/img/lemontech_logo/lemontech_logo_" + lado.toLowerCase() +"_ie.png";
		Zenbox.init({
			dropboxID:   "20042787",
			url:         "https://lemontech.zendesk.com",
			tabID:       "support", 
			tabImageURL:    imagen_fondo,
			tabColor:    "#02782e",
			tabPosition: lado
		});
            }
        });

 }

if (window.addEventListener)
 window.addEventListener("load", downloadJSAtOnload, false);
 else if (window.attachEvent)
 window.attachEvent("onload", downloadJSAtOnload);
 else window.onload = downloadJSAtOnload;
        
/* ]]> */
</script>
</body>
</html>