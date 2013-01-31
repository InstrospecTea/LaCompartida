<?php
require_once dirname(__FILE__) . '/app/conf.php';

$Sesion = new Sesion(null, true);
$Sesion->CheckLogin();

$Pagina = new Pagina($Sesion, true);

$_SESSION['ERROR'] = '';

$Pagina->PrintHeaders();
?>
<div style="padding-top: 50px; text-align: center">
	<div style="border: 1px solid #999; width: 400px; margin: 0 auto;">
		<div style="background-color: #efefef; padding: 8px; margin-bottom: 5px;">
			<?php echo Conf::AppName() ?>
		</div>
		<?php if ($Sesion->error_msg != '') { ?>
			<table style="width: 80%; margin: 0 auto;" class="alerta">
				<tr>
					<td><?php echo $Sesion->error_msg; ?></td>
				</tr>
			</table>
		<?php } ?>
		<div style="padding: 10px">
			<form action="<?php if(defined('APPDOMAIN')) echo str_replace(array('http:','https:'),'',APPDOMAIN); ?>app/usuarios/login.php" method="post">
				<table style="width: 100%">
					<tbody>
						<tr>
							<td rowspan="4">
							<?php if (Conf::GetConf($Sesion,'UsaDisenoNuevo')) { ?>
								<img src="<?php echo Conf::ImgDir(); ?>/logo_lemontech_ttb.jpg" width="175" height="70" />
							<?php	} else { ?>
								<img src="<?php echo Conf::Logo(); ?>" />
							<?php	} ?>
							</td>
							<td align="right"><?php echo Conf::GetConf($Sesion, 'NombreIdentificador'); ?>:</td>
							<td align="left">
								<?php if (strtolower(UtilesApp::Getconf($Sesion, 'NombreIdentificador')) == 'rut') { ?>
									<input type="text" name="rut" value="" style="width: 63%" /> - <input type="text"  name="dvrut" value="" size="1" />
								<?php } else { ?>
									<input type="text" name="rut" value="" style="width: 100%" />
								<?php } ?>
							</td>
						</tr>
						<tr>
							<td align="right">Password:</td>
							<td align="left"><input type="password" name="password" value="" style="width: 100%" /></td>
						</tr>
						<?php if (Conf::GetConf($Sesion, 'RecordarSesion')) { ?>
							<tr>
								<td colspan="2" align="right" style='vertical-align:top; font-size:10px'>
									Recordar en este equipo&nbsp;&nbsp;
									<input type="checkbox" name='recordar' id='recordar' value="1" />
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td colspan="2" align="right">
								<a href="app/usuarios/reset_password.php"><?php echo __('¿olvidó su password?'); ?></a>
							</td>
						</tr>
						<tr>
							<td></td>
							<td align="left">
								<input type="submit" class="btn" value="Entrar" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>
		<div>
			<div id="DigiCertClickID_iIR9fwBQ" style="margin:5px auto 15px;" >&nbsp;</div>
		</div>
	</div>
</div>

<?php
$Pagina->PrintBottom(true);