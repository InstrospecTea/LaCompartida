<?php
	require_once dirname(__FILE__) . '/../../app/conf.php';
	require_once dirname(__FILE__) . '/servers.class.php';

	$Sesion = new Sesion(array('ADM'));
	$Pagina = new Pagina($Sesion);
	$Servers = new Servers($Sesion);

	$Pagina->titulo = __('Librofresco');
	$Pagina->PrintTop();

	if (!$Sesion->usuario->TienePermiso('SADM')) {
		die('No Autorizado');
	}

	$dbhost = $_POST['dbhost'] ? $_POST['dbhost'] : $Servers->dbhosts[0];
?>

<link rel="stylesheet" type="text/css" href="librofresco.css">

<div id="modal_background"></div>
<div id="modal_content"></div>

<form method="POST">
	<table>
		<tr>
			<td>
				<label>Host de Base de Datos: </label>
				<?php echo Html::SelectArray($Servers->dbhosts, 'dbhost', $dbhost); ?>
				<input type="submit" value="Filtrar">
			</td>
		</tr>
	</table>
</form>

<?php
	if ($Servers->connection($dbhost)) {
		?>
		<div class="actualizar_todos">
			<a href="javascript:void(0)" class="actualizar_todos">Actualizar a todos los clientes</a>
		</div>

		<table>
			<thead>
				<tr>
					<th class="cliente">Cliente</th>
					<th>Timekeeper</th>
					<th>Adminitrative</th>
					<th>Casetracking</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($Servers->dbnames as $dbname) {
					$nombre_cliente = $Servers->getClientNameFromDbName($dbname);
					if ($Servers->selectDataBase($dbname)) {
						$clientes = $Servers->getClients();
						if (!empty($clientes)) {
							foreach ($clientes as $cliente) {
								?>
								<tr id="tr_<?php echo $nombre_cliente; ?>">
									<td><?php echo $nombre_cliente; ?></td>
									<td class="usuarios"><?php echo $cliente['timekeeper']; ?></td>
									<td class="usuarios"><?php echo $cliente['administrative']; ?></td>
									<td class="usuarios"><?php echo $cliente['casetracking']; ?></td>
									<td class="actualizar">
										<a href="javascript:void(0)"
											class="actualizar"
											data-client="<?php echo $nombre_cliente; ?>"
											data-timekeeper="<?php echo $cliente['timekeeper']; ?>"
											data-administrative="<?php echo $cliente['administrative']; ?>"
											data-casetracking="<?php echo $cliente['casetracking']; ?>"
											data-subdomain="<?php echo base64_encode("http://{$nombre_cliente}.thetimebilling.com"); ?>"
											data-error="">
											Actualizar
										</a>
									</td>
								</tr>
								<?php
							}
						} else {
							?>
							<tr id="tr_<?php echo $nombre_cliente; ?>" class="error">
								<td><?php echo $nombre_cliente; ?></td>
								<td class="usuarios">0</td>
								<td class="usuarios">0</td>
								<td class="usuarios">0</td>
								<td class="actualizar">
									<a href="javascript:void(0)" class="error" data-error="<?php echo addslashes($Servers->error); ?>">Error</a>
								</td>
							</tr>
							<?php
						}
					} else {
						?>
						<div class="error">
							<b>Error</b>: <?php echo $Servers->error; ?>
						</div>
						<?php
					}
				}
				?>
			</tbody>
		</table>

		<script type="text/javascript">
			var nro_cliente, total_clientes;

			jQuery(function() {
				jQuery('a.actualizar_todos').click(function() {
					nro_cliente = 0;
					total_clientes = jQuery('a.actualizar').size();

					jQuery('#modal_content')
						.html('')
						.append(
							jQuery('<div>')
								.css('padding-top', '30px')
								.append(jQuery('<div>').attr({id:'modal_content_title'}).html(jQuery('<b>').text('Actualizado')))
								.append(jQuery('<div>').attr({id:'modal_content_body'}).html(jQuery('<i>').text('Cliente')))
						);

					if (total_clientes > 0) {
						jQuery('#modal_content, #modal_background').toggleClass('active');
						jQuery('a.actualizar').each(function() {
							actualizarCliente(this);
						});
					}
				});

				jQuery('a.error, a.actualizado').live('click', function() {
					alert(jQuery(this).attr('data-error').replace(/\\/gi, ''));
				});

				jQuery('a.actualizar').live('click', function() {
					nro_cliente = 0;
					total_clientes = 1;

					jQuery('#modal_content')
						.html('')
						.append(
							jQuery('<div>')
								.css('padding-top', '30px')
								.append(jQuery('<div>').attr({id:'modal_content_title'}).html(jQuery('<b>').text('Actualizado')))
								.append(jQuery('<div>').attr({id:'modal_content_body'}).html(jQuery('<i>').text('Cliente')))
						);

					jQuery('#modal_content_body').html(jQuery('<i>').text(jQuery(this).attr('data-client')));
					jQuery('#modal_content, #modal_background').toggleClass('active');
					actualizarCliente(this);
				});
			});

			function actualizarCliente(a) {
				jQuery.ajax({
					url: 'librofresco.ajax.php',
					type: 'POST',
					dataType: 'json',
					data: {
						client: jQuery(a).attr('data-client'),
						timekeeper: jQuery(a).attr('data-timekeeper'),
						administrative: jQuery(a).attr('data-administrative'),
						casetracking: jQuery(a).attr('data-casetracking'),
						subdomain: jQuery(a).attr('data-subdomain')
					},
					beforeSend: function() {
						jQuery(a)
							.html('Actualizando...')
							.addClass('actualizando')
							.removeClass('actualizar');
					},
					success: function (data) {
						if (data.error == false) {
							jQuery(a)
								.html('Actualizado')
								.removeClass('actualizando')
								.addClass('actualizado')
								.attr('data-error', 'Actualizado');
						} else {
							jQuery(a)
								.html('Error')
								.removeClass('actualizando')
								.addClass('error')
								.attr('data-error', data.error);
							jQuery('#tr_' + jQuery(a).attr('data-client')).addClass('error')
						}
					},
					error: function (data) {
						jQuery(a)
							.html('Error')
							.removeClass('actualizando')
							.addClass('error')
							.attr('data-error', data.error);
					},
					complete: function() {
						nro_cliente++;
						jQuery('#modal_content_body').html(jQuery('<i>').text(jQuery(a).attr('data-client')));
						if (nro_cliente >= total_clientes) {
							jQuery('#modal_content, #modal_background').toggleClass('active');
						}
					}
				});
			}
		</script>
		<?php
	} else {
		?>
		<div class="error">
			<b>Error</b>: <?php echo $Servers->error; ?>
		</div>
		<?php
	}

	$Pagina->PrintBottom();
?>
