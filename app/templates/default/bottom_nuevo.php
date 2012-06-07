
<div style="clear:both;display:block;">&nbsp;</div>

    <div id="ultimocontenedor" style="clear:both;height:70px; width:130px;margin:40px auto 5px ;text-align:center;">
            <img src="<?php echo Conf::ImgDir()?>/logo_bottom.jpg" width="125" height="37" style="padding:15px 15px 0;float:left;" />&nbsp;
    <div id="DigiCertClickID_iIR9fwBQ" style="float:right;" >&nbsp;</div>
    </div>

    
</div>
<div id="dialogomodal" style="display:none;text-align:center" > </div> 
<div id="dialog-confirm" style="display:none;" ></div>  
<script type="text/javascript">
/* <![CDATA[   */
    jQuery.ajax({async: true,cache:true, type: "GET", url: 'https://estaticos.thetimebilling.com/fw/js/droplinemenu.js'	, 
	dataType: 'script',
	complete: function() {
        droplinemenu.buildmenu("droplinetabs1");
	}
    });
    
 
jQuery.ajax({async: false,cache:true,type: "GET", url: root_dir+'/app/js/bottom.js', dataType: 'script' });


 function downloadJSAtOnload() {
   
   jQuery("head").append("<link id='zenboxcss' />");
    jQuery("#zenboxcss").attr({rel:  "stylesheet", type: "text/css", href: "https://asset0.zendesk.com/external/zenbox/v2.4/zenbox.css" });
     

    
  if (_sf_async_config.pathseguro!==undefined) {
         if (jQuery('#DigiCertClickID_iIR9fwBQ').length>0) {
                jQuery('#ultimocontenedor').css({'width':'335px'});
                if ("https:" == document.location.protocol) {
                   __dcid.push(["DigiCertClickID_iIR9fwBQ", "3", "s", "black", "iIR9fwBQ"]);(function(){var cid=document.createElement("script");cid.type="text/javascript";cid.async=true;cid.src=("https:" === document.location.protocol ? "https://" : "http://")+"seal.digicert.com/seals/cascade/seal.min.js";var s = document.getElementsByTagName("script");var ls = s[(s.length - 1)];ls.parentNode.insertBefore(cid, ls.nextSibling);}());
                } else {
                         jQuery('#DigiCertClickID_iIR9fwBQ').html('<a style="border:0;text-decoration:none;" href="'+_sf_async_config.pathseguro+'"><img src="https://estaticos.thetimebilling.com/images/no_ssl_cifrado.png" style="text-decoration:none;vertical-align:top;border: 0 none;margin-top:0;position:relative;top:0;right:0;" /></a>');
                }
            }
    }
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

   
        jQuery.ajax({async: true,cache:true, type: "GET", url: 'https://asset0.zendesk.com/external/zenbox/v2.4/zenbox.js', 
	dataType: 'script',
	complete: function() {
		if (typeof(Zenbox) !== "undefined") {
			Zenbox.init({
				dropboxID:   "20042787",
				url:         "https://lemontech.zendesk.com",
				tabID:       "support", 
				tabImageURL: "https://estaticos.thetimebilling.com/templates/default/img/tag_soporte3.png",
				tabColor:    "transparent",
				tabPosition: "Right"
			    });
			}          		
              jQuery('#zenbox_tab').hide();
              jQuery('#zenbox_tab').css({'right':'-35', 'border':'0 none !important'})                                                                                                                                                                                                                                                  
              jQuery('#zenbox_tab').show().animate({'right':'0'},3000);
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
</body>
</html>