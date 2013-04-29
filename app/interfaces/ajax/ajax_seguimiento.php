<?php

require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

if ($_REQUEST['codigo_cliente'] == '') {
	return "";
}

$ClienteSeguimiento = new ClienteSeguimiento($Sesion);

if ($_POST['opcion'] == 'guardar') {
	$ClienteSeguimiento->Fill($_REQUEST, true);
	$ClienteSeguimiento->Edit('id_usuario', $Sesion->usuario->fields['id_usuario']);

	if ($ClienteSeguimiento->Write()) {
		// todo ok
	}
} else {
	$ClienteSeguimiento->Fill($_REQUEST);
}

$seguimientos = $ClienteSeguimiento->FindAll();
?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="https://static.thetimebilling.com/templates/default/css/deploy/all.1226330411_nuevo.css" />
		<style>
			html, body { margin: 0; padding: 0; position: relative; }
			.seguimiento_table {
				padding-bottom: 60px;
			}
			form {
				position: fixed;
				bottom: 0;
				margin-bottom: 0;
				background-color: white;
				width: 100%;
			}
			form textarea {
				vertical-align: text-top;
			}
			form input {
				vertical-align: text-top;
			}
		</style>
	</head>
	<body>
		<div class="seguimiento_container">
			<?php if (count($seguimientos) > 0) { ?>
			<div class="seguimiento_table">
				<table class="buscador" width="98%">
					<thead>
						<tr class="encabezado">
							<td class="encabezado">Fecha</td>
							<td class="encabezado">Usuario</td>
							<td class="encabezado">Comentario</td>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 0;
						foreach ($seguimientos as $s) {
							$i++;
						?>
						<tr bgcolor="<?php echo ($i % 2 == 0) ? '#FFF' : '#EEE'; ?>">
							<td width="20%"><?php echo Utiles::sql2fecha($s['fecha_creacion'], "%d/%m/%Y"); ?></td>
							<td width="10%" title="<?php echo $s['nombre_usuario']; ?>"><?php echo $s['iniciales_usuario']; ?></td>
							<td><?php echo $s['comentario']; ?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<?php } else { ?>
				<em>No se han ingresado comentarios</em>
			<?php } ?>
			<form action="#" method="POST">
				<input type="hidden" name="codigo_cliente" value="<?php echo $ClienteSeguimiento->fields['codigo_cliente']; ?>" />
				<input type="hidden" name="opcion" value="guardar" />
				<div style="vertical-align: top">
					<textarea name="comentario" cols="38" rows="2"></textarea>
					<input id="submit" type="submit" class="btn" value="Guardar" />
				</div>
			</form>
		</div>
		<script>
			jQuery(document).ready(function () {
				jQuery('#submit').click(function () {
					jQuery('form').submit();
					return false;
				});
			});
		</script>
	</body>
</html>