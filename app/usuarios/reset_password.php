<?php
require_once dirname(__FILE__) . '/../../app/conf.php';

$Sesion = new Sesion(null, true);
$Pagina = new Pagina($Sesion, true);

// Do POST
switch ($_REQUEST['accion']) {
	case 'confirmacion':
		break;

	case 'reset':
		break;

	default:
		$email = trim($_POST['email']);
		if (preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $email)) {
			$Pagina->AddError(__('Debe ingresar un correo electrónico válido'));
		}
		break;
}

$Pagina->titulo = __('Reset password');
$Pagina->PrintTop(true);

switch ($_REQUEST['accion']) {
	case 'confirmacion':
		break;

	case 'reset':
	?>
	<div style="">
		<fieldset style="border: 1px solid black; margin: 50px auto; width: 500px">
			<legend><?php echo __('Cambiar Password'); ?></legend>
			<form method="POST" action="#">
				<div>
					<label>
						<?php echo __('Password'); ?>:
						<input type="password" name="password" id="password" value="" size="50" />
					</label>
				</div>
				<div>
					<label>
						<?php echo __('Confirme Password'); ?>:
						<input type="password" name="confirma_password" id="confirma_password" value="" size="50" />
					</label>
				</div>
				<div class="form-actions">
					<input type="submit" value="Cambiar Password" />
					<input type="reset" value="Cancelar" />
				</div>
			</form>
		</fieldset>
	</div>
	<?php
		break;

	default:
	?>
	<div style="">
		<fieldset style="border: 1px solid black; margin: 50px auto; width: 500px">
			<legend><?php echo __('Recuperar_clave'); ?></legend>
			<form method="POST" action="#">
				<label>
					<?php echo __('Email'); ?>:
					<input type="email" name="email" id="email" value="" size="50" />
				</label>
				<div class="form-actions">
					<input type="submit" value="Recuperar" />
					<input type="reset" value="Cancelar" />
				</div>
			</form>
		</fieldset>
	</div>
	<?php
		break;
}

$Pagina->PrintBottom(true);