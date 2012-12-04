<?php
$laurl= ($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']; 
$punto=strpos($laurl,'.'); 
$subdomain=substr($laurl,0,$punto); 
$maindomain=str_replace($subdomain.'.','',$laurl); 
if($subdomain) $subdomain='/'.$subdomain;
$elpath=$subdomain.$_SERVER['PHP_SELF'];

 if(is_readable('/var/www/html/instanceid')) $instanceid=file_get_contents('/var/www/html/instanceid');
 
 
echo '<!--/*NADA AP1*/-->';
?>
<!DOCTYPE html>
   <head>
       <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Lemontech - Aviso</title>
<script type="text/javascript">var _sf_startpt=(new Date()).getTime()</script>
<!--/*NADA AP1*/-->
 


<style>
input {font-size:1em !important;height:1.1em !important;}
.form-horizontal .control-label {width:85px !important;font-size:1em !important;padding-top: 2px;}
.form-horizontal .controls {margin-left:90px !important;}
.span1 {width:12px !important;}
.checkbox.inline {font-size:1.2em !important;}
.form-horizontal .control-group { margin: 5px !important;}
#logo {height:48px;width:150px;margin:10px;}
.submit {height:30px !important;width:90px;}
.container {margin:auto;text-align:center;}
.span6   {margin:auto;text-align:center;}
.cabecera {padding: 8px;background:#efefef;}
</style>
 
</head>
<body>
<br><br><br><br><br><br>
<div class="container">
<div class="row">
<div class="span3">&nbsp;</div>
<div class="span6">
 
			
		 
						<table width="100%"   cellspacing="2" cellpadding="2" id="maintable"    >
						 
				<tr>
					<td   width="400">
									<img  src="//static.thetimebilling.com/cartas/img/lemontech_logo400.png" height="126" width="400"  alt="Lemontech: Case Tracking"/> 
								</td>
					 
				</tr>
							
						
							 
							
                          
							
						<tr><td><span>Instancia: <?php  echo  $instanceid ; ?></span></td></tr>
						<tr><td><span>IP Interna: <?php echo  $_SERVER['SERVER_ADDR'] ; ?></span></td></tr>
				
						</table>			
					 
		 

</div>
<div class="span3"></div>
</div>
</div>
<script type="text/javascript">
var _sf_async_config={uid:32419,domain:"app6.thetimebilling.com"};
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