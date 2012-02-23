<?php
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	$sesion = new Sesion( array() );
        $rel='v ';
          if (file_exists(Conf::ServerDir().'/../app/version_svn.php') and $versionsvn=file_get_contents(Conf::ServerDir().'/../app/version_svn.php', NULL, NULL, 13,5)) $rel.=$versionsvn; 
           if (file_exists(Conf::ServerDir().'/../app/version.php') and $versiondb=file_get_contents(Conf::ServerDir().'/../app/version.php', NULL, NULL, 13,5)) $rel.=' db'.$versiondb; 
		
        if ( !Conf::GetConf($sesion,'ActualizacionTerminado') ) {
			echo "<h2>Estimado cliente, </h2>&nbsp;&nbsp;Estamos actualizando su sistema. El proceso de actualización se demora aproximadamente 10 a 15 minutos ...";
			?>
			<br/><br/>
			<img src="<?php echo Conf::ImgDir();?>/logo_lemon.png" />
			<?php
			exit; 
		}
?>
<body  class="non_popup" rel="<?php echo $rel;?>">
<script type="text/javascript" src="https://asset0.zendesk.com/external/zenbox/v2.4/zenbox.js"></script>
<link type="text/css" media="screen, projection" rel="stylesheet" href="https://asset0.zendesk.com/external/zenbox/v2.4/zenbox.css" />
<style type="text/css">
	#zenbox_tab {
		border-radius: 10px 0px 0px 10px !important;    /* cambiar por 0px 10px 10px 0px si el lado que va es izquierdo*/
		-moz-border-radius: 10px 0px 0px 10px  !important; 
		-webkit-border-radius: 10px 0px 0px 10px  !important; 
		border: 1px solid #000000  !important;
		overflow: hidden;
	}
</style>
<script type="text/javascript">
	function getInternetExplorerVersion()
	{
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
		var imagen_fondo = "<?php echo Conf::ImgDir() ? Conf::ImgDir() : '/app/templates/default/img'; ?>/lemontech_logo_" + lado.toLowerCase() +".png";
		if( ver > -1) {
			imagen_fondo = "<?php echo Conf::ImgDir() ? Conf::ImgDir() : '/app/templates/default/img'; ?>/lemontech_logo" + lado.toLowerCase() +"_ie.png";
		}
		Zenbox.init({
			dropboxID:   "20042787",
			url:         "https://lemontech.zendesk.com",
			tabID:       "support", 
			tabImageURL:    imagen_fondo,
			tabColor:    "#02782e",
			tabPosition: lado
		});
	}
</script>       
<table width="100%" height="100%" align="center" border="0" cellspacing="0" cellpadding="0">
	  <?php if($color=='') {
	  echo "<tr style=\"height:55px;\" class=\"tb_facebook\">";
	} else {
	  echo "<tr style=\"height:55px; background: ".$color.";\">";
	} ?>
		<td align="center">
			<table cellpadding="0" cellspacing="0" width="970" border="0">
				<tr valign="center">
			  	<td align="left" width="50%"><img src="<?php echo Conf::ImgDir()?>/logo_top.png" /></td>
			    <td align="right" width="50%"><br/>
			    	<span style="color:#FFFFFF;">
			    		<span class="text_bold">Usuario</span>: 
			    			<?php echo $sesion->usuario->fields['nombre']?> <?php echo $sesion->usuario->fields['apellido1']?> <?php echo $sesion->usuario->fields['apellido2']?> | 
			    			<a style="color: white;" href="#" onClick="irIntranet('/fw/usuarios/index.php');">Inicio</a>
								<?php if ($_SESSION['ACTIVO_JUICIO'] && method_exists('Conf','HostJuicios') ){?> 
										| <a style="color: white;" href="<?php echo Conf::HostJuicios()?>" onClick="irIntranet('/fw/usuarios/index.php');">Gestión de Causas</a>
								<?php }?> 
                                                        | <a style="color: white;" href="http://lemontech.zendesk.com/home" target="_blank" >Soporte</a> 
							| <a href="#" style="color: white;" onClick="irIntranet('/fw/usuarios/logout.php?salir=true');">Salir</a></span></td>
			  </tr>
			</table>
		</td>
	</tr>
  <tr>
    <td align="center">
    <table id="tb_header" cellpadding="0" cellspacing="0" width="970" border="0" >
  <tr>
	  <td width="10px" style="background:transparent;">&nbsp;</td>
  	<td height="3px" width="964px"></td>
	  <td width="10px" style="background:transparent;">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="3" align="left" id="menu_principal_tt"> 
   <?php $rootlength = strlen( Conf::RootDir() ); 
	if( UtilesApp::GetConf($this->sesion,'LibreriaMenu') == 'prototype')
	{	
		echo UtilesApp::PrintMenuDisenoNuevoPrototype($this->sesion, substr($_SERVER['PHP_SELF'],$rootlength));
		?>
		 <script type="text/JavaScript">
			buildmenu("droplinetabs1"); 
		</script>
		<?php
	}
	else
	{
		echo UtilesApp::PrintMenuDisenoNuevojQuery($this->sesion, substr($_SERVER['PHP_SELF'],$rootlength)) ;
	}
	?>


    </tr>
  <tr>
 <td style="background:transparent;">&nbsp;</td>
    <td class="ubicacion" height="20px" valign="bottom" align="left"> <br/></td>
 <td style="background-color:transparent;">&nbsp;</td>
  </tr>
  <tr>
 <td style="background:transparent;">&nbsp;</td>
    <td class="titulo_sec" height="30px" valign="bottom" align="left">
			<?php echo $this->titulo; ?>
			<hr size="2" width="850" align="center" color="#a3d45c"/>
		</td>
 <td style="background:transparent;">&nbsp;</td>
  </tr>
  <tr>
     <td style="background:transparent;">&nbsp;</td>
    <td class="cont_tabla" style="background-color: #FFFFFF;" align="center">
			<?php 	if($this->num_infos > 0):	?>
			
			<table width="80%" class="info">
				<tr>
					<td valign="top" align="left" style="font-size: 12px;">
						<?php echo $this->GetInfos(); ?>
					</td>
				</tr>
			</table>
			
						<br/><br/>
			
			<?php endif;
                        if($this->num_errors > 0): 		?>
			
			<table width="80%" class="alerta">
				<tr>
					<td valign="top" align="left" style="font-size: 12px;">
						<strong>Se han encontrado los siguientes errores:</strong><br/>
						<?php echo $this->GetErrors(); ?>
					</td>
				</tr>
			</table>
						<br/><br/>
			
			<?php endif; 	?>
