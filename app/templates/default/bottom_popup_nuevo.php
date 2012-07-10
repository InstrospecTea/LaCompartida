</td>
</tr>
</table>
<div id="dialogomodal" style="display:none;" ></div>  
<div id="dialog-confirm" style="display:none;" ></div>  
<script type="text/javascript">
/* <![CDATA[   */
jQuery.ajax({async: false,cache:false,type: "GET", url:'https://static.thetimebilling.com/bottom.js?v2', dataType: 'script' });
   


  



 function downloadJSAtOnload() {


   if (!Modernizr.borderradius) {
    jQuery.ajax({async: false,cache:true, type: "GET", url: 'https://estaticos.thetimebilling.com/fw/js/curvycorners.js', 
	dataType: 'script',
	complete: function() {
		  var settings = {
		      tl: { radius: 5 },
		      tr: { radius: 5 },
		      bl: { radius: 5 },
		      br: { radius: 5 },
		      antiAlias: true
		    }
            var divObj = document.getElementById("fd_menu_grey");
            curvyCorners(settings, divObj); 
	    }
        });            
	  }
if (_sf_async_config.pathseguro!==undefined) {
         if (jQuery('#DigiCertClickID_iIR9fwBQ').length>0) {
                jQuery('#ultimocontenedor').css({'width':'330px'});
                if ("https:" == document.location.protocol) {
                   __dcid.push(["DigiCertClickID_iIR9fwBQ", "3", "s", "black", "iIR9fwBQ"]);(function(){var cid=document.createElement("script");cid.type="text/javascript";cid.async=true;cid.src=("https:" === document.location.protocol ? "https://" : "http://")+"seal.digicert.com/seals/cascade/seal.min.js";var s = document.getElementsByTagName("script");var ls = s[(s.length - 1)];ls.parentNode.insertBefore(cid, ls.nextSibling);}());
                } else {
                         jQuery('#DigiCertClickID_iIR9fwBQ').html('<a style="border:0;text-decoration:none;" href="'+_sf_async_config.pathseguro+'"><img src="https://estaticos.thetimebilling.com/images/no_ssl_cifrado.png" style="text-decoration:none;vertical-align:top;border: 0 none;margin-top:0;position:relative;top:0;right:0;" /></a>');
                }
            }
}

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