<?php

require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

if ($_REQUEST['codigo_cliente'] == '') {
	return "";
}

$ClienteSeguimiento = new ClienteSeguimiento($Sesion);
$criteria = new Criteria();
$criteria
	->add_select('contacto')
	->add_select('apellido_contacto')
	->add_select('fono_contacto')
	->add_select('email_contacto')
	->add_limit(1)
	->add_from('contrato')
	->add_restriction(
			CriteriaRestriction::equals('codigo_cliente',$_REQUEST['codigo_cliente'])
		);

$statement = $Sesion->pdodbh->prepare($criteria->get_plain_query());
$statement->execute();
$results = $statement->fetchAll(PDO::FETCH_ASSOC);

extract($results[0]);

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
			.contact_data {
				font-size: smaller;
				margin-bottom: 2%;
			}
			.contact_data .data{
				margin-left: 5%;
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
			<div class="contact_data">
				<div class="title">Datos Contacto</div>
				<div class="data"><?php echo __('Nombre')?>: <?php echo($contacto.' '.$apellido_contacto) ?></div>
				<div class="data"><?php echo __('Fono')?>: <?php echo $fono_contacto; ?></div>
				<div class="data"><?php echo __('E-mail')?>: <?php echo $email_contacto; ?></div>
			</div>
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
					<textarea id="comentario" name="comentario" cols="38" rows="2"></textarea>
					<input id="submit" type="submit" class="btn" value="Guardar" onclick="return Validar();" />
				</div>
			</form>
			<script>
				function Validar() {
					c = document.getElementById('comentario');
					if (c.value.trim() == '') {
						alert('Debe ingresar un comentario');
						c.focus();
						return false;
					}
					return true;
				}
			</script>
		</div>
	</body>
</html>