</td>
 <td  style="background:transparent;">&nbsp;</td>
  </tr>
  <tr>
<td  style="background:transparent;">&nbsp;</td>
<td height="40px" class="fondo_cierre" align="left">&nbsp;</td>
<td  style="background:transparent;">&nbsp;</td>
  </tr>
  <tr>
<td style="background:transparent;">&nbsp;</td>
    <td height="40px" align="center"><img src="<?=Conf::ImgDir()?>/logo_bottom.jpg" width="125" height="37" /></td>
<td style="background:transparent;">&nbsp;</td>
  </tr>
    </table>

    
     </td>
  </tr>
</table>

<script language="Javascript" type="text/javascript" src="<?=Conf::RootDir()?>/app/js/google_analytics.js"></script>
<?php
$laurl= $_SERVER['HTTP_HOST']; 
$punto=strpos($laurl,'.'); 
$subdomain=substr($laurl,0,$punto); 
$maindomain=str_replace($subdomain.'.','',$laurl); 
$elpath=$subdomain.$_SERVER['PHP_SELF'];
?>
<script type="text/javascript">
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

</script>
</body>
</html>

