<?php
	require_once dirname(__FILE__).'/app/conf.php';
	require_once dirname(__FILE__).'/fw/classes/Sesion.php';
	require_once dirname(__FILE__).'/fw/classes/Pagina.php';
 
	$sesion = new Sesion(null, true);
	
	$sesion->CheckLogin(); #Chequea cookies y hace login
	
	$pagina = new Pagina($sesion, true);

	$_SESSION['ERROR'] = '';

	$pagina->PrintHeaders();
	
	if ( !Conf::GetConf($sesion,'ActualizacionTerminado') ) {
		echo "<h2>Estimado cliente, </h2>&nbsp;&nbsp;Estamos actualizando su sistema. El proceso de actualización se demora approximadamente 10 a 15 minutos ...";
		?>
		<br/><br/>
		<img src="<?php echo Conf::ImgDir();?>/logo_lemon.png" />
		<?php
		exit; 
	}
?>

<table width="100%" height="100%">
	<tr>
		<td align="center">
			<br><br><br><br><br><br>

<table cellspacing="0" cellpadding="0" style="border: 1px solid #999999;">
	<tr>
		<td align="center" style="padding: 8px;" bgcolor="#efefef">
		<?php echo Conf::AppName() ?>
	</td>
	</tr>
	<tr>
		<td align="center" bgcolor="#999999"></td>
	</tr>
	<tr>
		<td align="center" style="padding: 8px;">
<table width="100%" cellspacing="2" cellpadding="2">
 <form action="app/usuarios/login.php" method="post">
	<tr>
		<td align="right" rowspan="3">
			<?php 
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists( 'Conf', 'UsaDisenoNuevo' ) && Conf::UsaDisenoNuevo() ) ) 
				{ ?>
					<img src="<?php echo Conf::ImgDir()?>/logo_lemontech_ttb.jpg" width="175" height="70" />
		<?php	}
			else 
				{ ?>
					<img src="<?= Conf::Logo() ?>" /> 
		<?php	} ?>
		</td>
		<td align="right">
			<?php echo ( method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'NombreIdentificador') : Conf::NombreIdentificador() )?>:
		</td>
		<td align="left" nowrap>
			<?php
				$identificador = UtilesApp::Getconf($sesion, 'NombreIdentificador');
				if( strtolower($identificador) == 'rut' ) {
			?>
				<input type="text" name="rut" value="" size="10">-<input type="text"  name="dvrut" value="" size="1">
			<?php
				} else {
			?>
				<input type="text" name="rut" value="" size="17">
			<?php } ?>
				<br>
		</td>
	</tr>
	<tr>
		<td align="right">
			Password:
		</td>
		<td align="left">
			<input type="password" name="password" value="" size="17">
		</td>
	</tr>
<?php
	//Revisa el Conf si esta permitido y la función existe
	if( method_exists( 'Conf','GetConf' ) )
		$RecordarSesion = Conf::GetConf( $sesion, 'RecordarSesion');
	else if( method_exists( 'Conf', 'RecordarSesion' ) )
		$RecordarSesion = Conf::RecordarSesion();
	else
		$RecordarSesion = false;
		
	if( $RecordarSesion )
	{
?>
	<tr>
		<td colspan=2 align=right style='vertical-align:top; font-size:10px'>
			Recordar en este equipo&nbsp;&nbsp;<input type=checkbox name='recordar' id='recordar' value=1 />
		</td>
	</tr>
<?php
	}
	else
	{
?>
	<tr>
		<td colspan=2 align=right style='vertical-align:top; font-size:10px'>
			Recordar en este equipo&nbsp;&nbsp;<input type=checkbox name='recordar' id='recordar' value=1 />
		</td>
	</tr>
<?php
	}
?>
	<tr>
		<td align="right">
			&nbsp;
		</td>
		<td align="left">
			<input type="submit" class=btn value="Entrar">
		</td>
	</tr>
 </form>
</table>

		</td>
	</tr>
	<tr>
		<td align="center">

<?php
	if($sesion->error_msg != '')
	{
?>

<table width="80%" class="alerta">
	<tr>
		<td valign="top" align="left" style="font-size: 12px;">
			<?=$sesion->error_msg?>
		</td>
	</tr>
</table>

			<br>

<?php
	}
?>

		</td>
	</tr>
        <tr><td align="center"><div id="DigiCertClickID_iIR9fwBQ" style="margin:5px auto 15px;" >&nbsp;</div></td></tr>

</table>

		
<?php $pagina->PrintBottom(true);?>
