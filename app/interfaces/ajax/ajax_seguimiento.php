<?php

require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

if ($_REQUEST['codigo_cliente'] == '') {
	return "";
}

$ClienteSeguimiento = new ClienteSeguimiento($Sesion);

if ($_REQUEST['opcion'] == 'guardar') {
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
			html, body { margin: 0; padding: 0 0 60px 0; position: relative; }
			form {
				position: fixed;
				bottom: 0;
				margin-bottom: 0;
				background-color: white;
				width: 100%;
			}
		</style>
	</head>
	<body>
		<div class="seguimiento_container">
			<div class="seguimiento_table">
				<table class="buscador" width="95%">
					<thead>
						<tr class="encabezado">
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
							<td width="30%"><?php echo $s['username']; ?></td>
							<td><?php echo $s['comentario']; ?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<form action="#" method="POST">
				<textarea name="comentario" cols="30" rows="2"></textarea>
				<input type="hidden" name="codigo_cliente" value="<?php echo $ClienteSeguimiento->fields['codigo_cliente']; ?>" />
				<input type="hidden" name="opcion" value="guardar" />
				<input type="submit" class="btn" value="Guardar" />
			</form>
		</div>
	</body>
</html>