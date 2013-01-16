<?php
require_once dirname(__FILE__) . '/../../app/conf.php';

ini_set('display_errors', 'On');
//ini_set('error_reporting', E_ALL);

$Sesion = new Sesion(null, true);
$Pagina = new Pagina($Sesion, true);

$view = $_REQUEST['view'];

if (isset($_POST['accion'])) {
	switch ($_POST['accion']) {
		case 'confirmacion':
			break;

		case 'reset':
			$Sesion->error_msg = __('Su password ha sido restablecido correctamente');
			$view = 'password_restablecida';

			$passwd = $_POST['password'];
			$c_passwd = $_POST['confirme_password'];

			if ($passwd == '' || $c_passwd == '' || $passwd != $c_passwd) {
				$Sesion->error_msg = __('Debe ingresar un password válido y ambos deben ser iguales');
				$view = 'restablecer_password';
			}
			break;

		case 'send':
			$view = 'instrucciones_enviadas';
			$Sesion->error_msg = __('Se han enviado correctamente las instrucciones');

			$email = trim($_POST['email']);
			if ($email == '' || preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $email)) {
				$Sesion->error_msg = __('Debe ingresar un correo electrónico válido');
				$view = 'enviar_instrucciones';
			}

			$Usuario = new Usuario($Sesion);
			$Usuario->LoadByEmail($email);

			if (!$Usuario->Loaded()) {
				$Sesion->error_msg = __('El email indicado no se encuentra registrado en el sistema');
				$view = 'enviar_instrucciones';
			}
			// Enviar mail con token

			break;
	}
}

$Pagina->titulo = __('Restablecer Password');
$Pagina->PrintTop(true);
?>
<div style="padding-top: 50px; text-align: center">
	<div style="border: 1px solid #999; width: 400px; margin: 0 auto;">
		<div style="background-color: #efefef; padding: 8px; margin-bottom: 5px;">
			<?php echo __('Restablecer Password'); ?>
		</div>
		<?php if ($Sesion->error_msg != '') { ?>
			<table style="width: 80%; margin: 0 auto;" class="alerta">
				<tr>
					<td><?php echo $Sesion->error_msg; ?></td>
				</tr>
			</table>
		<?php } ?>
		<div style="padding: 5px">
			<form action="#" method="post">
				<table style="width: 100%">
					<tbody>
						<tr>
							<td>
							<?php if (Conf::GetConf($Sesion,'UsaDisenoNuevo')) { ?>
								<img src="<?php echo Conf::ImgDir(); ?>/logo_lemontech_ttb.jpg" width="175" height="70" />
							<?php	} else { ?>
								<img src="<?php echo Conf::Logo(); ?>" />
							<?php	} ?>
							</td>
							<td>
<?php
	switch ($view) {
		case 'instrucciones_enviadas':
?>
					<table>
						<tr>
							<td align="left">
								Por favor revise su casilla de correo electrónico para continuar el proceso de recuperar password,
								en caso de que no reciba un correo dentro de los próximos 5 minutos favor revisar en la bandeja de
								correos no deseados.
								<br /><br />
								Dependiendo del proveedor de correo o software que está usando, será necesario agregar a
								<strong><?php echo Conf::GetConf($Sesion, 'UsernameMail'); ?></strong> a su lista de contactos o a
								la "lista aceptada" de correos, también puede marcar que el mail de <strong>TimeBilling</strong> no
								es <strong>spam</strong>.
							</td>
						</tr>
						<tr>
							<td align="left">
								<a href="../../index.php"><?php echo __('volver al inicio'); ?></a>
							</td>
						</tr>
					</table>
<?php
			break;

		case 'password_restablecida':
?>
					<table>
						<tr>
							<td align="left">
								Ahora puede ingresar al sistema normalmente con la nueva password establecida.
							</td>
						</tr>
						<tr>
							<td align="left">
								<a href="../../index.php"><?php echo __('volver al sistema'); ?></a>
							</td>
						</tr>
					</table>
<?php
			break;

		case 'restablecer_password':
?>
					<table>
						<tr>
							<td colspan="2" align="left">
								Por favor ingrese el password que desea ocupar de ahora en adelante en el sistema.
							</td>
						</tr>
						<tr>
							<td align="right"><label for="password"><?php echo __('Password'); ?></label>:</td>
							<td align="left"><input type="password" name="password" id="password" size="25" /></td>
						</tr>
						<tr>
							<td align="right"><label for="confirme_password"><?php echo __('Confirme Password'); ?></label>:</td>
							<td align="left"><input type="password" name="confirme_password" id="confirme_password" size="25" /></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td align="left">
								<input type="hidden" name="accion" value="reset" />
								<input type="submit" class="btn" value="Cambiar Password" />
							</td>
						</tr>
					</table>
<?php
			break;

		case 'enviar_instrucciones':
?>
					<table>
						<tr>
							<td colspan="2" align="left">
								Por su seguridad se enviará un correo de confirmación indicando los pasos a seguir
								para restablecer su password, <strong>nunca</strong> enviaremos password en texto plano a su correo.
							</td>
						</tr>
						<tr>
							<td align="right"><label for="email"><?php echo __('Email'); ?></label>:</td>
							<td align="left"><input type="email" name="email" id="email" value="<?php echo $email; ?>" size="28" /></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td align="left">
								<input type="hidden" name="accion" value="send" />
								<input type="submit" class="btn" value="Restablecer" />
								<a href="../../index.php"><?php echo __('cancelar'); ?></a>
							</td>
						</tr>
					</table>
<?php
			break;
	}
?>
					</tbody>
				</table>
			</form>
		</div>
	</div>
</div>
<?php
$Pagina->PrintBottom(true);