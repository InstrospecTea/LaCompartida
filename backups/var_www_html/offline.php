<?php
$laurl= ($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']; 
$punto=strpos($laurl,'.'); 
$subdomain=substr($laurl,0,$punto); 
$maindomain=str_replace($subdomain.'.','',$laurl); 
if($subdomain) $subdomain='/'.$subdomain;
$elpath=$subdomain.$_SERVER['PHP_SELF'];

 
 
	 
?>
<!DOCTYPE html>
   <head>
       <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Lemontech - Aviso</title>

<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap.min.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
<script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>
<script language="JavaScript" type="text/javascript">
        var _sf_startpt=(new Date()).getTime();
        var DivLoading='<div class="divloading">&nbsp;</div>';
	var _sf_async_config={};
        var __dcid = __dcid || [];
            _sf_async_config.uid = 32419;
            _sf_async_config.domain = "<?php echo $maindomain; ?>"; 
            _sf_async_config.path = "<?php echo $elpath; ?>";
    </script>

<style>
input {font-size:1em !important;height:1.1em !important;}
.form-horizontal .control-label {width:85px !important;font-size:1em !important;padding-top: 2px;}
.form-horizontal .controls {margin-left:90px !important;}
.span1 {width:12px !important;}
.checkbox.inline {font-size:1.2em !important;}
.form-horizontal .control-group { margin: 5px !important;}
#logo {height:48px;width:150px;margin:10px;}
.submit {height:30px !important;width:90px;}

.cabecera {padding: 8px;background:#efefef;}
</style>
<script>
jQuery(document).ready(function() {
jQuery('#recuerdame').tooltip( {animation:true, placement:'right',title:'Seleccione esta opción si desea recordar sus datos de acceso en este equipo.'});
jQuery('#usar_ad').tooltip( {animation:true, placement:'right',title:'Seleccione esta opción si desea autentificar con usuario y contraseña de ActiveDirectory.'});
 });
</script>
</head>
<body>
<br><br><br><br><br><br>
<div class="container">
<div class="row">
<div class="span3">&nbsp;</div>
<div class="span6">
 
			
		 
						<table width="100%"   cellspacing="2" cellpadding="2" id="maintable"  >
						 
				<tr>
					<td   width="400">
									<img  src="http://static.thetimebilling.com/cartas/img/lemontech_logo400.png" height="126" width="400"  alt="Lemontech: Case Tracking"/> 
								</td>
					 
				</tr>
							
						
							<tr><td  >&nbsp;</td>
								 
							</tr>
							
                          
							<tr><td  class="alert alert-block"><h4 class="alert-heading">Error</h4>Estamos experimentando problemas<br>Rogamos disculpar las molestias</td>
								 
							</tr>
						
				
						</table>			
					 
		 

</div>
<div class="span3">&nbsp;</div>
</div>
</div>

<script type="text/javascript">
	jQuery.ajax({async: false,cache:true,type: "GET", url:"//static.thetimebilling.com/js/bottom.js", dataType: "script" });
</script>
</body>
</html>