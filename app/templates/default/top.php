<?
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	$sesion = new Sesion( array() );
?>
<body onload="SetFocoPrimerElemento();">
<table width="100%" style="height:100%" cellspacing="0" cellpadding="0">
    <tr>
		<td bgcolor="#000000" width="1"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="1" alt="" /></td>
		<td valign="top" width="170">

<table border="0" width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td valign="top" bgcolor="#000000"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="1" alt="" /></td>
	</tr>
	<tr>
		<td valign="top" align="center" bgcolor="<?=( method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'ColorLineaSuperior') : ( method_exists('Conf','ColorLineaSuperior') ? Conf::ColorLineaSuperior() : '#CC9933' ) )?>" class="menu_intranet" style="color: #ffffff;" height="18">
			&nbsp;
		</td>
	</tr>
	<tr>
		<td valign="top" bgcolor="#000000"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="1" alt="" /></td>
	</tr>
	<tr>
		<td valign="middle" align="center" style="padding: 4px;">
			<img src="<?=Conf::Logo()?>" alt="Inicio" onclick="self.location.href='<?=Conf::RootDir().'/fw/usuarios/index.php'?>'" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td valign="top"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="5" alt="" /></td>
	</tr>
	<tr>
		<td valign="top" bgcolor="#000000"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="1" alt="" /></td>
	</tr>
	<tr>
		<td>
			<?= Html::PrintMenu($this->sesion) ?>
		</td>
	</tr>
</table>

        </td>
        <td valign="top" width="1" bgcolor="#000000"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="1" alt="" /></td>
        <td>
<table border="0" width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td bgcolor="#000000" height="1"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="1" alt="" /></td>
	</tr>
	<tr>
		<td align="center" bgcolor="<?=( method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'ColorLineaSuperior') : ( method_exists('Conf','ColorLineaSuperior') ? Conf::ColorLineaSuperior() : '#CC9933' ) )?>" style="color: #ffffff; vertical-align: middle;" height="18">

<table border="0" width="100%" cellpadding="2" cellspacing="0">
	<tr>
	<?php
	  if($_SESSION['ACTIVO_JUICIO'])
	  {
	  #&& method_exists('Conf','TablaJuicios')
	?>
		<td align="left">
			<a href="../../app/interfaces/cambiar_sistema.php" class="menu_top">Ir al Sistema "Gesti&oacute;n de Causas"</a>
		</td>
	<?php
		}
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ColumnaNotificacion') != '' )
			{
				$query = "SELECT id_notificacion FROM notificacion ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($notificacion1) = mysql_fetch_array($resp);
				
				$query = "SELECT ".Conf::GetConf($sesion,'ColumnaNotificacion')." FROM usuario WHERE id_usuario=".$sesion->usuario->fields['id_usuario'];
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($notificacion2) = mysql_fetch_array($resp);
			} 
		else if( method_exists('Conf','ColumnaNotificacion') )
			{
				$query = "SELECT id_notificacion FROM notificacion ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($notificacion1) = mysql_fetch_array($resp);
				
				$query = "SELECT ".Conf::ColumnaNotificacion()." FROM usuario WHERE id_usuario=".$sesion->usuario->fields['id_usuario'];
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($notificacion2) = mysql_fetch_array($resp);
			}
	?>
		<td valign="bottom" align="right" class="menu_top">
		<? if( method_exists('Conf','ColumnaNotificacion') || ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ColumnaNotificacion') != '' ) )
				{ 
				if( $notificacion1==$notificacion2 ) 
					{?>
					<a class="menu_top" href="#" onClick="irIntranet('/app/interfaces/noticias.php');">Noticias</a>&nbsp;&nbsp;|&nbsp;&nbsp
		<?		}
				else
					{ ?>
					<a class="menu_top" href="#" onClick="irIntranet('/app/interfaces/noticias.php');">Noticias(<?=$notificacion1-$notificacion2 ?>)</a>&nbsp;&nbsp;|&nbsp;&nbsp
			<?	}	
			} ?>
 			<b><?=__('Usuario')?></b>: <?=$sesion->usuario->fields['nombre']?> <?=$sesion->usuario->fields['apellido1']?> <?=$sesion->usuario->fields['apellido2']?>&nbsp;&nbsp;|&nbsp;&nbsp;
			<a class="menu_top" href="#" onClick="irIntranet('/fw/usuarios/index.php');">Inicio</a>&nbsp;|&nbsp;
			<a class="menu_top" href="#" onClick="irIntranet('/fw/usuarios/logout.php?salir=true');">Salir</a> 
			<img src="<?=Conf::ImgDir()?>/pix.gif" width="90" height="1" alt=""/>
		</td>
	</tr>
</table>

		</td>
	</tr>
	<tr>
		<td bgcolor="#000000" height="1"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="1" alt="" /></td>
	</tr>
	<tr>
		<td valign="top">

<table width="720px">
    <tr>
        <td align="center">

<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td valign="top" align="left" class="titulo" bgcolor="<?=( method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'ColorTituloPagina') : ( method_exists('Conf','ColorTituloPagina') ? Conf::ColorTituloPagina() : '#A7DF60' ) )?>">
			<?=$this->titulo?>
		</td>
	</tr>
	<tr>
		<td valign="top" align="center" style="padding: 0px;" bgcolor="#000000"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="10" height="1" alt="" /></td>
	</tr>
	<tr>
		<td valign="top" align="center">
			<img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="10" alt="" />
		</td>
	</tr>
	<tr>
		<td valign="top" align="center">

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

		</td>
	</tr>
	<tr>
		<td valign="top" align="center">
