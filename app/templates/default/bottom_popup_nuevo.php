</td>
</tr>
</table>
<?php
$laurl= $_SERVER['HTTP_HOST']; 
$punto=strpos($laurl,'.'); 
$subdomain=substr($laurl,0,$punto); 
$maindomain=str_replace($subdomain.'.','',$laurl); 
if($subdomain) $subdomain='/'.$subdomain;
$elpath=$subdomain.$_SERVER['PHP_SELF'];
?>
<script type="text/javascript">
    if ("https:" == document.location.protocol) {
   var __dcid = __dcid || [];__dcid.push(["DigiCertClickID_iIR9fwBQ", "5", "s", "black", "iIR9fwBQ"]);(function(){var cid=document.createElement("script");cid.type="text/javascript";cid.async=true;cid.src=("https:" === document.location.protocol ? "https://" : "http://")+"seal.digicert.com/seals/cascade/seal.min.js";var s = document.getElementsByTagName("script");var ls = s[(s.length - 1)];ls.parentNode.insertBefore(cid, ls.nextSibling);}());
}
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


 
 /*   jQuery("head").append("<link id='hscss' />");
        jQuery("#hscss").attr({
          rel:  "stylesheet",
          type: "text/css",
          href: "https://static.thetimebilling.com/highslide.css"
        });
    var highslide =  document.createElement("script"); highslide.src = "https://static.thetimebilling.com/highslide-lemontech.packed.js"; document.body.appendChild(highslide);
   */   
//var deployall =  document.createElement("script"); deployall.src = RootDir+"/app/deploy/all.1234370043.js"; document.body.appendChild(deployall);
//var resize_iframe=  document.createElement("script"); resize_iframe.src = RootDir+"/app/deploy/resize_iframe.js"; document.body.appendChild(resize_iframe);

jQuery.when(jQuery.ajax({async: false,type: "GET", url: root_dir+'/fw/js/droplinemenu.js', dataType: 'script' }) ).then(function() {
    droplinemenu.buildmenu("droplinetabs1");
});
jQuery.when(jQuery.ajax({async: false,type: "GET", url: root_dir+'/fw/js/curvycorners.js', dataType: 'script' }) ).then(function() {
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

/* ]]> */

</script>
</body>
</html>