<?
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	$sesion = new Sesion( array() );
?>
<body onload="SetFocoPrimerElemento();" class="non_popup">
<table width="100%" height="100%" align="center" border="0" cellspacing="0" cellpadding="0">
	  <? if($color=='') {
	  echo "<tr style=\"height:55px;\" class=\"tb_facebook\">";
	} else {
	  echo "<tr style=\"height:55px; background: ".$color.";\">";
	} ?>
		<td align="center">
			<table cellpadding="0" cellspacing="0" width="970" border="0">
				<tr valign="center">
			  	<td align="left" width="50%"><img src="<?=Conf::ImgDir()?>/logo_top.png" /></td>
			    <td align="right" width="50%"><br/>
			    	<span style="color:#FFFFFF;">
			    		<span class="text_bold">Usuario</span>: 
			    			<?=$sesion->usuario->fields['nombre']?> <?=$sesion->usuario->fields['apellido1']?> <?=$sesion->usuario->fields['apellido2']?> | 
			    			<a style="color: white;" href="#" onClick="irIntranet('/fw/usuarios/index.php');">Inicio</a>
								<? if($_SESSION['ACTIVO_JUICIO'] && method_exists('Conf','HostJuicios') ){?> 
										| <a style="color: white;" href="<?=Conf::HostJuicios()?>" onClick="irIntranet('/fw/usuarios/index.php');">Gestión de Causas</a>
								<?}?> 
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
   <? $rootlength = strlen( Conf::RootDir() ); 
	if(( method_exists('Conf','GetConf') && (Conf::GetConf($this->sesion,'LibreriaMenu') == 'prototype')))
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
		?>
		 <script type="text/JavaScript">
			droplinemenu.buildmenu("droplinetabs1");
		</script>
		<?php
	}
	?>
<!-- DropLineMenu -->
<!--[if IE]>
<script type="text/JavaScript">
 window.onload = function() {
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
</script>
 <!{endif]-->

    </tr>
  <tr>
 <td style="background:transparent;">&nbsp;</td>
    <td class="ubicacion" height="20px" valign="bottom" align="left"> <br/></td>
 <td style="background-color:transparent;">&nbsp;</td>
  </tr>
  <tr>
 <td style="background:transparent;">&nbsp;</td>
    <td class="titulo_sec" height="30px" valign="bottom" align="left">
			<?=$this->titulo?>
			<hr size="2" width="850" align="center" color="#a3d45c"/>
		</td>
 <td style="background:transparent;">&nbsp;</td>
  </tr>
  <tr>
     <td style="background:transparent;">&nbsp;</td>
    <td class="cont_tabla" style="background-color: #FFFFFF;" align="center">
			<?
				if($this->num_infos > 0)
				{
			?>
			
			<table width="80%" class="info">
				<tr>
					<td valign="top" align="left" style="font-size: 12px;">
						<?=$this->GetInfos()?>
					</td>
				</tr>
			</table>
			
						<br/><br/>
			
			<?
				}
				if($this->num_errors > 0)
				{
			?>
			
			<table width="80%" class="alerta">
				<tr>
					<td valign="top" align="left" style="font-size: 12px;">
						<strong>Se han encontrado los siguientes errores:</strong><br/>
						<?=$this->GetErrors()?>
					</td>
				</tr>
			</table>
						<br/><br/>
			
			<?
			}
			?>
