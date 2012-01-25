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
$dataurl=parse_url($_SERVER['SCRIPT_URI']); 
$dataurl['aux']=explode('.',$dataurl['host']); 
$dataurl['path']='/'.$dataurl['aux'][0].$dataurl['path']; 
$dataurl['host']=$dataurl['aux'][1].'.'.$dataurl['aux'][2];
?>
<script type="text/javascript">
var _sf_async_config={};
/** CONFIGURATION START **/
_sf_async_config.uid = 32419;
_sf_async_config.domain = "<?php echo $dataurl['host']; ?>"; 
_sf_async_config.path = "<?php echo $dataurl['path']; ?>";
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

