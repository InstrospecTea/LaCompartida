<?php

require_once dirname(__FILE__) . '/../conf.php';

require_once dirname(__FILE__) . '/../../fw/classes/Buscador.php';
require_once dirname(__FILE__) . '/../classes/Trabajo.php';

$sesion = new Sesion(array('COB', 'DAT'));
$pagina = new Pagina($sesion);
$contrato = new Contrato($sesion);
$cobros = new Cobro($sesion);

$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

$query_cliente = 'SELECT codigo_cliente, glosa_cliente FROM cliente WHERE activo = 1 ORDER BY glosa_cliente ASC';

$query_moneda = 'SELECT glosa_moneda, tipo_cambio FROM prm_moneda ORDER BY moneda_base DESC';
$resp_moneda = mysql_query($query_moneda, $sesion->dbh) or Utiles::errorSQL($query_moneda, __FILE__, __LINE__, $sesion->dbh);

$query_forma_cobro = 'SELECT forma_cobro, descripcion FROM prm_forma_cobro';

$sesion->pdodbh->exec('SET SESSION group_concat_max_len=15000');

if ($opc == 'excel') {
	// Es necesaria esta bestialidad para que no se caiga cuando es llamada desde otro lado.
	$no_activo = !$activo;
	$multiple = true;
	$forzar_username = true;
	require_once Conf::ServerDir() . '/interfaces/cobros_xls.php';
	exit;
}

if ($opc == 'asuntos_liquidar') {
	// Es necesaria esta bestialidad para que no se caiga cuando es llamada desde otro lado.
	"<h1>ENTRO</h1>";
	$no_activo = !$activo;
	$multiple = true;
	require_once Conf::ServerDir() . '/interfaces/asuntos_liquidar_xls.php';
	exit;
} elseif ($opc == 'buscar') {
	if ($cobros_generado) {
		$pagina->AddInfo(__('Cobros generado con &eacute;xito'));
	}
	else if ($cobros_emitidos) {
		$pagina->AddInfo(__('Cobros emitidos con &eacute;xito'));
	}
	else if ($cobros_en_revision == 1) {
		$pagina->AddInfo(__('Cobros cambiados a EN REVISION con &eacute;xito'));
	}
	else if ($cobros_en_revision == '0') {
		$pagina->AddInfo(__('No se encontraron cobros para cambiar de estado'));
	}
	if ($codigo_cliente_secundario) {
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
		$codigo_cliente = $cliente->fields['codigo_cliente'];
		$_POST['codigo_cliente'] = $codigo_cliente;
	}

	if ($codigo_asunto_secundario) {
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
		$codigo_asunto = $asunto->fields['codigo_asunto'];
		$_POST['codigo_asunto'] = $codigo_asunto;
	}

	if ($codigo_cliente) {
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($codigo_cliente);
		$codigo_cliente_secundario = $cliente->fields['codigo_cliente_secundario'];
		$_POST['codigo_cliente_secundario'] = $codigo_cliente_secundario;
	}

	###### BUSCADOR ######
	$CobroQuery = new CobroQuery($sesion);
	$query = $CobroQuery->genera_cobros(($_POST['opc'] == 'buscar') ? $_POST : $_GET);
	$x_pag = 20;
	$orden = 'cliente.glosa_cliente, asunto_lista';
	$b = new Buscador($sesion, $query, "Contrato", $desde, $x_pag, $orden);

	$arrayMIXTAS = array();
	$arrayHH = array();
	$arrayClientes = array();
	$arrayContratos = array();
	$totalHITOS = 0;

	$responseCobrosST = $sesion->pdodbh->query($query);
	$responseCobrosRS = $responseCobrosST->fetchAll(PDO::FETCH_FUNC, 'url_cobro_individual');

	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_gastos";
	$b->titulo = __('Proceso masivo de emisión de cobros');
	$b->AgregarEncabezado('glosa_cliente', __('Cliente'), '', '', 'SplitDuracion');
	$b->AgregarEncabezado('asuntos', __('Asunto'), 'align="left" nowrap');
	$b->AgregarEncabezado('fecha_ultimo_cobro', __('Último Cobro'), ' align="left" nowrap');
	$b->AgregarEncabezado('id_contrato', __('Acuerdo'), 'align="left"');
	$b->AgregarFuncion(__('Opción'), 'Opciones', 'align="center" nowrap width="8%"');
	$b->color_mouse_over = '#bcff5c';
	$b->funcionTR = 'funcionTR';
}

$pagina->titulo = __('Proceso masivo de emisión de cobros');

$pagina->PrintTop();
$Form = new Form();
$Form->defaultLabel = false;

if (Conf::GetConf($sesion, 'OcultarCobrosTotalCeroGeneracion')) {
	$cobrosencero_chk = false;
} else {
	$cobrosencero_chk = true;
}

?>

<script type="text/javascript">
	var interrumpeproceso = 0;

<?php
if ($opc == 'buscar') {

	echo "var arrayHH=" . json_encode($arrayHH) . ";\n";
	echo "var arrayMIXTAS=" . json_encode($arrayMIXTAS) . ";\n";
	echo "var arrayClientes=" . json_encode(array_values($arrayClientes)) . ";\n";
	echo "var arrayContratos=" . json_encode(array_values($arrayContratos)) . ";\n";
	echo "var totalHITOS=" . (empty($totalHITOS) ? '0' : $totalHITOS) . ";\n";
}
?>

	function ToggleDiv(divId)
	{
		var divObj = document.getElementById(divId);
		if (divObj)
		{
			if (divObj.style.display == 'none') {
				divObj.style.display = 'table-cell';
			} else {
				divObj.style.display = 'none';
			}

		}
	}

	function SubirExcel()
	{
		nuevaVentana("Subir_Excel", 500, 300, "subir_excel.php");
	}

	function DeleteCobro(id, i, id_contrato, me) {
		if (id) {
			var text_window = '<span style="font-size:12px;margin:10px; text-align:center;font-weight:bold"><?php echo __('¿Desea eliminar') . ' ' . __('el cobro') . ' ' . __('seleccionado?') ?>.</span>';

			interrumpeproceso = 0;
			jQuery('<p/>')
					.attr('title', 'Confirmación')
					.html(text_window)
					.dialog({
						resizable: true,
						autoOpen: true,
						height: 'auto',
						width: 350,
						modal: true,
						close: function(ev, ui) {
							jQuery(this).html('');
							interrumpeproceso = 1;
						},
						open: function() {
							jQuery('.ui-dialog-title').addClass('ui-icon-warning');
							jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
						},
						buttons: {
							'<?php echo __('Continuar') ?>': function() {

								if (jQuery('#fecha_ini').length) {
									var fecha_ini = jQuery('#fecha_ini').val();
								}
								if (jQuery('#fecha_fin').length) {
									var fecha_fin = jQuery('#fecha_fin').val();
								}
								var data = {
									accion: 'elimina_cobro',
									id_cobro: id,
									div: i,
									id_contrato: id_contrato,
									id_proceso: jQuery('#id_proceso').val(),
									fecha_ini: fecha_ini,
									fecha_fin: fecha_fin
								};
								jQuery.get('ajax.php', data, function(deleting) {
									if (deleting.error) {
										alert(deleting.message);
									} else {
										jQuery('#tr_cobro_' + id).html('').append(jQuery('<td/>').attr('colspan', 4).append(jQuery('<div/>').addClass('alert alert-danger alert-thin').html(deleting.message)));
									}
								}, 'json');
								jQuery(this).dialog("close");
								return true;
							},
							'<?php echo __('Cancelar') ?>': function() {
								jQuery(this).dialog("close");
								return false;
							}
						}
					});

		}
	}

	function SeleccionaTodos(field, check)
	{
		if (check)
			var valor = true;
		else
			var valor = false;

		for (i = 0; i < field.length; i++)
		{
			field[i].checked = valor;
		}
	}

	function UpdateContrato(check, id)
	{
		if (!form)
			var form = $('form_busca');

		var valor = check ? 1 : 0;

		jQuery.get('ajax.php?accion=update_contrato&id_contrato=' + id + '&incluir_en_cierre=' + valor);
		return true;
	}

	function GeneraCobros(form, desde, opcion, id_formato) {

		if (!form) {
			var form = $('form_busca');
		}

		if (desde == 'genera') {
			// Validar que no exista un proceso de generación de cobros pendiente
			if (ProcessLock()) {
				return;
			}

			<?php if (Conf::GetConf($sesion, 'SoloGastos')) { ?>
				if (jQuery('#tipo_liquidacion').val() == '') {
					text_window += '<br/><?php echo $Form->radio('radio_generacion', 'gastos', false, array('id' => 'radio_gastos')) . __('Sólo Gastos') ?>';
				}
				if (jQuery('#tipo_liquidacion').val() == '') {
					text_window += '<br/><?php echo $Form->radio('radio_generacion', 'honorarios', false, array('id' => 'radio_honorarios')) . __('Sólo Honorarios') ?>';
				}
			<?php } ?>

			var largoClientes = arrayClientes.length;
			var largoContratos = arrayContratos.length;

			if (largoContratos == 0 || largoClientes == 0) {
				text_window = '<div style="font-weight:bold;padding:10px;">No hay datos para los filtros que Ud. ha seleccionado</div>';
				jQuery('<p/>')
					.attr('title', 'Advertencia')
					.html(text_window)
					.dialog({
						autoOpen: true,
						height: 'auto',
						width: 350,
						modal: true,
						close: function(ev, ui) {
							jQuery(this).dialog('destroy').remove();
						},
						buttons: {
							'Cerrar': function() {
								jQuery(this).dialog('close');
							}
						}
					});
				return
			}
			var text_window = '<div style="font-size:11px; text-align:center;font-weight:bold;padding:10px;"><?php echo __('Antes de generar los borradores, asegúrese de haber actualizado los tipos de cambio.') ?>';
			text_window += '<br><div id="tiposdecambio"><br><?php echo '<a class="btn" style="text-decoration: none;border: 1px solid #AAA;display: block;width: 130px;margin: -10px auto;" href="tipo_cambio.php">' . __('Tipos de Cambio actuales') . '</a>'; ?><br>';
			text_window += '<table align="center" style="margin:auto;border:1px dotted #666" width=40%><tr><td><b><?php echo __('Moneda') ?></b></td><td><b><?php echo __('Cambio') ?></b></td></tr>';
			text_window += '<?php while ($monedas = mysql_fetch_array($resp_moneda)) { ?><tr><td><?php echo $monedas[glosa_moneda] ?></td><td><?php echo $monedas[tipo_cambio] ?></td></tr><?php } ?>';
			text_window += '</table></div>';
			text_window += '<br><span style="font-size:11px; text-align:center; color:#FF0000;"><?php echo __('Recuerde que al generar los borradores se eliminarán todos los borradores antiguos asociados a los contratos') ?></span><br>';
			text_window += '<br><span style="font-size:11px; text-align:center;font-weight:bold"><?php echo __('¿Desea generar los borradores?') ?></span><br>';
			text_window += '<div style="text-align:left;font-weight:normal;margin:0 20px;">';
			text_window += '<?php echo $Form->radio('radio_generacion', '', true, array('id' => 'radio_wip')) .  __('Honorarios') . ' y ' . __('Gastos') . __(', se incluirán horas hasta el') ?> ' + jQuery('#fecha_fin').val();
			text_window += '<br/><?php echo $Form->checkbox('cobrosencero_generacion', 1, $cobrosencero_chk, array('label' => 'Incluir ' . __('cobros') . ' de monto cero'));?>';
			text_window += '</div><div style="text-align:center;"> ';
			text_window += '<span id="loading" style="text-align:center;margin:auto;">&nbsp;</span> ';
			text_window += '<br><span id="respuestahh">&nbsp;</span> ';
			text_window += '<br><span id="respuestamixtas">&nbsp;</span>';
			text_window += '<br><span id="respuestagg">&nbsp;</span>';
			text_window += '<br><span id="nocerrar">&nbsp;</span></div></div>';

			text_window += '</div><div style="text-align:center;"> ';
			jQuery('<p/>')
					.attr('title', 'Advertencia')
					.append(text_window)
					.dialog({
						autoOpen: true,
						height: 'auto',
						width: 550,
						modal: true,
						open: function() {
							jQuery('.ui-dialog-title').addClass('ui-icon-warning');
							jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
						},
						close: function() {
							jQuery(this).dialog('destroy').remove();
						},
						buttons: {
							"Generar": function() {
								jQuery(".ui-dialog-buttonpane button:contains('Generar')").button("disable");
								jQuery('#loading, #nocerrar').show();
								jQuery('#tiposdecambio').slideUp();
								jQuery('#form_busca').attr('action', 'genera_cobros_guarda.php?generar_silenciosamente=1');
								<?php if (Conf::GetConf($sesion, 'SoloGastos')) { ?>
									if (jQuery('#radio_gastos').is(':checked')) {
										jQuery('#form_busca').attr('action', 'genera_cobros_guarda.php?gastos=1&generar_silenciosamente=1');
									} else if (jQuery('#radio_honorarios').is(':checked')) {
										jQuery('#form_busca').attr('action', 'genera_cobros_guarda.php?solohh=1&generar_silenciosamente=1');
									}
								<?php } ?>

								<?php if (Conf::GetConf($sesion, 'TipoGeneracionMasiva') == 'contrato') { ?>
									var data = {
										'solo': jQuery('[name="radio_generacion"]:checked').val(),
										'form': <?php echo json_encode($_POST);?>,
										'cobrosencero': jQuery('#cobrosencero_generacion').is(':checked') ? 1 : 0
									};
									jQuery.post(root_dir + '/app/ProcessLock/exec/<?php echo Cobro::PROCESS_NAME; ?>', data, function(reply) {
										jQuery('#respuestamixtas').html('<h3>Proceso Iniciado</h3> Se han enviado ' + largoContratos + ' contratos para la generación de sus cobros' + (totalHITOS ? ', se excluyen ' + totalHITOS + ' contratos del tipo HITOS' : '') + '.<br><br>Presione "Cerrar" para continuar.');
										jQuery(".ui-dialog-buttonpane button:contains('Generar')").remove();
										jQuery('#loading, #nocerrar').hide();
										jQuery(".ui-dialog-buttonpane button:contains('Cancelar')").text("Cerrar");
									});

								<?php } else { ?>
									var data = {
										'solo': jQuery('[name="radio_generacion"]:checked').val(),
										'form': <?php echo json_encode($_POST); ?>,
										'cobrosencero': jQuery('#cobrosencero_generacion').is(':checked') ? 1 : 0
									};
									jQuery.post(root_dir + '/app/ProcessLock/exec/<?php echo Cobro::PROCESS_NAME; ?>', data, function(reply) {
										jQuery('#respuestamixtas').html('<h3>Proceso Iniciado</h3> Se han enviado ' + largoClientes + ' clientes para la generacion de sus cobros.<br><br>Presione "Cerrar" para continuar.');
										jQuery(".ui-dialog-buttonpane button:contains('Generar')").remove();
										jQuery('#loading, #nocerrar').hide();
										jQuery(".ui-dialog-buttonpane button:contains('Cancelar')").text("Cerrar");
									});

								<?php } ?>
								seconds = 4;
								startCheckProcessLock();
							},
							"<?php echo __('Cancelar') ?>": function() {
								jQuery(this).dialog('close');
								interrumpeproceso = 1;
								return false;
							}
						}
					});


		} else if (desde == 'print') {
			jQuery('#form_busca').attr('action', 'genera_cobros_guarda.php?print=true&generar_silenciosamente=1&id_formato=' + id_formato + '&opcion=' + opcion);
			jQuery('#form_busca').submit();
		} else if (desde == 'excel') {

			jQuery.get('ajax.php?accion=existen_borradores', function(response) {
				if (response)
				{
					// No se pueden descargar los borradores si existe un proceso de generación de cobros pendiente
					if (ProcessLock()) {
						return;
					}

					var text_window = '<strong><center><?php echo __('A continuación se generarán los borradores del periodo que ha seleccionado.') ?><br><br><?php echo __('¿Desea descargar los cobros del periodo?') ?><center></strong><br><br>';

					var largoHH = arrayHH.length;
					var largoMIXTAS = arrayMIXTAS.length;
					var largototal = largoHH + largoMIXTAS;
					var largoClientes = arrayClientes.length;
					if (largototal == 0 || largoClientes == 0) {
						text_window += '<strong><center>No hay datos para los filtros que Ud. ha seleccionado</center></strong>';
					} else {
						text_window += '<div style="padding-left:40px; text-align:left; color:red; ">';
						text_window += '<br><label for="form_horas_trabajadas" style="padding-bottom: 4px;display:inline-block;width:180px;"><?php echo __('Mostrar horas trabajadas') ?>:</label><input type="checkbox" name="form_horas_trabajadas" id="form_horas_trabajadas" value="1" />';
						text_window += '<br><label for="form_horas_visibles" style="padding-bottom: 4px;display:inline-block;width:180px;"><?php echo __('Mostrar trabajos no visibles') ?>:</label><input type="checkbox" name="form_horas_visibles" id="form_horas_visibles" value="1" />';
						text_window += '<br><label for="form_asuntos_separados" style="padding-bottom: 4px;display:inline-block;width:180px;"><?php echo __('Ver asuntos por separado') ?>:</label><input type="checkbox" name="form_asuntos_separados" id="form_asuntos_separados" value="1" <?php echo Conf::GetConf($sesion, 'CodigoSecundario') ? '' : 'checked' ?>>';
						text_window += '<br><label for="form_asuntos_sin_horas" style="padding-bottom: 4px;display:inline-block;width:180px;"><?php echo __('Mostrar Asuntos Cobrables Sin Horas') ?>:</label><input type="checkbox" name="form_asuntos_sin_horas" id="form_asuntos_sin_horas" value="1" />';
						text_window += '</div>';
					}

					if (jQuery('#advertencia_descargar_borradores').length > 0) {
						jQuery('#advertencia_descargar_borradores').remove();
					}

					jQuery('<p/>')
							.attr('id', 'advertencia_excel_borradores')
							.attr('title', 'Advertencia - Excel Borradores')
							.html(text_window)
							.dialog({
								resizable: true,
								height: 'auto',
								width: 500,
								modal: true,
								close: function(ev, ui) {
									interrumpeproceso = 1;
								},
								open: function() {
									jQuery('.ui-dialog-title').addClass('ui-icon-warning');
									jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
								},
								buttons: {
									"<?php echo __('Descargar') ?>": function() {
										jQuery("#opc_ver_horas_trabajadas").val(jQuery("#form_horas_trabajadas").is(":checked") ? '1' : '0');
										jQuery("#opc_ver_cobrable").val(jQuery("#form_horas_visibles").is(":checked") ? '1' : '0');
										jQuery("#opc_ver_asuntos_separados").val(jQuery("#form_asuntos_separados").is(":checked") ? '1' : '0');
										jQuery("#opc_mostrar_asuntos_cobrables_sin_horas").val(jQuery("#form_asuntos_sin_horas").is(":checked") ? '1' : '0');

										form.action = 'genera_cobros.php';
										form.opc.value = 'excel';
										form.submit();
									},
									"<?php echo __('Cancelar') ?>": function() {
										jQuery(this).dialog('close');
										return false;
									}
								}
							});
				} else {
					alert('No existen ' + "<?php echo __('borradores') ?>" + ' en el sistema.');
					return false;
				}
			});


		} else if (desde == 'emitir') {
			if (jQuery('#modal_emitir_cobros').length == 1) {
				jQuery('#modal_emitir_cobros').remove();
			}

			jQuery('<div/>')
				.attr('id', 'modal_emitir_cobros')
				.attr('title', "<?php echo __('ALERTA'); ?>")
				.append(
					jQuery('<div/>')
						.attr('id', 'modal_emitir_cobros_resumen')
						.css({'text-align':'center', 'padding':'10px', 'font-size':'12px'})
						.append(jQuery('<div/>').html("<?php echo __('Ud. está realizando la emisión masiva de cobros, asegúrese de haber verificado sus datos o cobros en proceso.') ?>"))
						.append(
							jQuery('<div/>')
							.css({'font-weight':'bold', 'margin-top':'15px'})
							.html("<?php echo __('¿Desea emitir los cobros?'); ?>")
						)
				)
				.dialog({
					width: 400,
					height: 'auto',
					modal: true,
					closeOnEscape: false,
					dialogClass: 'no-close',
					draggable: false,
					resizable: false,
					open: function() {
						jQuery('.ui-dialog-title').addClass('ui-icon-warning');
						jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
					},
					buttons: {
						"<?php echo __('Cancelar'); ?>": function() {
							jQuery(this).dialog('close');
						},
						"<?php echo __('Continuar'); ?>": function() {
							form.action = 'genera_cobros_guarda.php?emitir=true&return_json=true';
							jQuery("#cobrosencero").val(0);
							jQuery('.ui-dialog-buttonpane button:contains("<?php echo __('Cancelar'); ?>")').button().hide();
							jQuery('.ui-dialog-buttonpane button:contains("<?php echo __('Continuar'); ?>")').button().hide();
							jQuery('.ui-dialog-buttonpane').hide();

							jQuery('#modal_emitir_cobros_resumen')
								.html('')
								.append(
									jQuery('<div/>')
										.css({'font-weight':'bold', 'margin-bottom':'15px'})
										.html("Emitiendo <?php echo __('cobros'); ?>")
								)
								.append(jQuery('<div/>').html('Procure no cerrar la pestaña actual de su navegador.'));

							jQuery('#modal_emitir_cobros_resumen')
								.append(
									jQuery('<div/>')
										.css({'margin':'10px auto', 'width':'100px', 'background':'url(https://static.thetimebilling.com/images/loading_bar.gif) no-repeat'})
										.html('&nbsp;')
								);

							jQuery.ajax({
								url: form.action,
								data: jQuery('#form_busca').serialize()
							}).fail(function(data) {
								alert(data);
							}).complete(function(data) {
								var data = jQuery.parseJSON(data.responseText);

								jQuery('#modal_emitir_cobros_resumen')
									.html('')
									.append(jQuery('<div/>').css({'margin-bottom':'15px', 'font-weight':'bold', 'text-align':'left', 'border-bottom':'1px solid #ccc'}).html("Resumen del proceso emisión de <?php echo __('cobros'); ?>"))
									.append(
										jQuery('<div/>')
											.css({'border':'1px dotted #ccc'})
											.append(jQuery('<div/>').css({'float':'left', 'text-align':'right', 'width':'180px'}).html('Procesados:'))
											.append(jQuery('<div/>').css({'float':'right', 'padding-right':'130px'}).html(data.total_cobros_procesados))
											.append(jQuery('<div/>').css({'clear':'both'}).html(''))
											.append(jQuery('<div/>').css({'float':'left', 'text-align':'right', 'width':'180px'}).html('Emitidos:'))
											.append(jQuery('<div/>').css({'float':'right', 'padding-right':'130px'}).html(data.total_cobros_emitidos))
											.append(jQuery('<div/>').css({'clear':'both'}).html(''))
											.append(jQuery('<div/>').css({'float':'left', 'text-align':'right', 'width':'180px'}).html('Con error:'))
											.append(jQuery('<div/>').css({'float':'right', 'padding-right':'130px'}).html(data.total_cobros_error))
											.append(jQuery('<div/>').css({'clear':'both'}).html(''))
									);

								if (data.total_cobros_error > 0) {
									jQuery('#modal_emitir_cobros_resumen').append(jQuery('<div/>').css({'font-weight':'bold', 'margin':'10px 0 10px 0', 'text-align':'left', 'border-bottom':'1px solid #ccc'}).html('Detalles'));

									jQuery.each(data.errores, function(k, v) {
										jQuery('#modal_emitir_cobros_resumen').append(jQuery('<div/>').css({'text-align':'left', 'margin-bottom':'10px'}).html(v));
									});
								}

								jQuery('#modal_emitir_cobros').dialog('option', 'buttons', { "<?php echo __('Aceptar'); ?>": function() { jQuery(this).dialog('close'); } });
								jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
								jQuery('.ui-dialog-buttonpane').show();
							});
						}
					}
				});
		} else if (desde == 'en_revision') {
			if (jQuery('#modal_en_revision_cobros').length == 1) {
				jQuery('#modal_en_revision_cobros').remove();
			}

			jQuery('<div/>')
				.attr('id', 'modal_en_revision_cobros')
				.attr('title', "<?php echo __('ALERTA'); ?>")
				.append(
					jQuery('<div/>')
						.attr('id', 'modal_en_revision_cobros_resumen')
						.css({'text-align':'center', 'padding':'10px', 'font-size':'12px'})
						.append(jQuery('<div/>').html("<?php echo __('Ud. está realizando un cambio masivo de estado a los cobros, asegúrese de haber verificado sus datos o cobros en proceso.') ?>"))
						.append(
							jQuery('<div/>')
							.html("<br><b><?php echo __('¿Desea cambiar el estado de los cobros a EN REVISION?'); ?></b><br><br>" +
								'<label for="cobrosencero_cobros_en_revision" style="padding-bottom: 4px;display:inline-block;width:180px;"><?php echo 'Incluir ' . __('cobros') . ' de monto cero'; ?>:</label><?php echo $Form->checkbox('cobrosencero_cobros_en_revision', 1, $cobrosencero_chk); ?>')
						)
				)
				.dialog({
					width: 400,
					height: 'auto',
					modal: true,
					closeOnEscape: false,
					dialogClass: 'no-close',
					draggable: false,
					resizable: false,
					open: function() {
						jQuery('.ui-dialog-title').addClass('ui-icon-warning');
						jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
					},
					buttons: {
						"<?php echo __('Cancelar'); ?>": function() {
							jQuery(this).dialog('close');
						},
						"<?php echo __('Continuar'); ?>": function() {
							form.action = 'genera_cobros_guarda.php?en_revision=true&return_json=true';
							if (jQuery('#cobrosencero_cobros_en_revision').is(':checked')) {
								jQuery("#cobrosencero").val(1);
							};

							jQuery('.ui-dialog-buttonpane button:contains("<?php echo __('Cancelar'); ?>")').button().hide();
							jQuery('.ui-dialog-buttonpane button:contains("<?php echo __('Continuar'); ?>")').button().hide();
							jQuery('.ui-dialog-buttonpane').hide();

							jQuery('#modal_en_revision_cobros_resumen')
								.html('')
								.append(
									jQuery('<div/>')
										.css({'font-weight':'bold', 'margin-bottom':'15px'})
										.html("Emitiendo <?php echo __('cobros'); ?>")
								)
								.append(jQuery('<div/>').html('Procure no cerrar la pestaña actual de su navegador.'));

							jQuery('#modal_en_revision_cobros_resumen')
								.append(
									jQuery('<div/>')
										.css({'margin':'10px auto', 'width':'100px', 'background':'url(https://static.thetimebilling.com/images/loading_bar.gif) no-repeat'})
										.html('&nbsp;')
								);

							jQuery.ajax({
								url: form.action,
								data: jQuery('#form_busca').serialize()
							}).fail(function(data) {
								alert(data);
							}).complete(function(data) {
								var data = jQuery.parseJSON(data.responseText);
								window.location.href = data.url_redirect;
							});
						}
					}
				});
		} else if (desde == 'asuntos_liquidar') {
			form.action = 'genera_cobros.php';
			form.opc.value = 'asuntos_liquidar';
			form.submit();
		} else {
			form.action = 'genera_cobros.php';
			form.opc.value = 'buscar';
			form.submit();
		}
	}

	/*
	 Impresión de cobros
	 */
	function ImpresionCobros(alerta, opcion, id_formato)
	{
		var form = jQuery('#form_busca');
		var proceso = jQuery('#id_proceso').val();

		jQuery.get('ajax.php?accion=existen_borradores', function(response) {
			interrumpeproceso = 0;
			if (response) {
				if (alerta) {
					// No se pueden descargar los borradores si existe un proceso de generación de cobros pendiente
					if (ProcessLock()) {
						return;
					}

					var text_window = '<strong><center><?php echo __('A continuación se generarán los borradores del periodo que ha seleccionado.') ?><br><br><?php echo __('¿Desea descargar los cobros del periodo?') ?><center></strong><br><br>';

					var largoHH = arrayHH.length;
					var largoMIXTAS = arrayMIXTAS.length;
					var largototal = largoHH + largoMIXTAS;
					var largoClientes = arrayClientes.length;
					if (largototal == 0 || largoClientes == 0) {
						text_window += '<strong><center>No hay datos para los filtros que Ud. ha seleccionado</center></strong>';
					} else {
						text_window += '<div style="padding-left:40px; text-align:left; color:red; ">';
						text_window += '<label for="id_formato" style="padding-bottom: 4px;display:inline-block;width:180px;">Formato del borrador:</label>';
						text_window += '<?php echo str_replace(array("'", "\n"), array('"', ''), Html::SelectQuery($sesion, "SELECT id_formato, descripcion FROM cobro_rtf", "id_formato", "", "", "Según opciones del " . __('Contrato'), '200px')); ?>';
						text_window += '<br><label for="cartas" style="padding-bottom: 4px;display:inline-block;width:180px;">Incluir cartas:</label><input type="checkbox" name="cartas" id="cartas"  />';
						text_window += '<br><label for="agrupar" style="padding-bottom: 4px;display:inline-block;width:180px;">Agrupar borradores por cliente:</label><input type="checkbox" name="agrupar" id="agrupar" />';
						text_window += '<br><label for="cobrosencero_descargar_borradores" style="padding-bottom: 4px;display:inline-block;width:180px;"><?php echo 'Incluir ' . __('cobros') . ' de monto cero'; ?>:</label><?php echo $Form->checkbox('cobrosencero_descargar_borradores', 1, $cobrosencero_chk); ?>';
						text_window += '<br><label for="mostrar_asuntos_cobrables_sin_horas_borradores" style="padding-bottom: 4px;display:inline-block;width:180px;"><?php echo __('Mostrar Asuntos Cobrables Sin Horas') ?>:</label><?php echo $Form->checkbox('mostrar_asuntos_cobrables_sin_horas_borradores', 1); ?>';
						text_window += '</div>';
					}

					if (jQuery('#advertencia_descargar_borradores').length > 0) {
						jQuery('#advertencia_descargar_borradores').remove();
					}

					jQuery('<p/>')
							.attr('id', 'advertencia_descargar_borradores')
							.attr('title', 'Advertencia - Descargar Borradores')
							.html(text_window)
							.dialog({
								resizable: true,
								height: 'auto',
								width: 500,
								modal: true,
								close: function(ev, ui) {
									interrumpeproceso = 1;
								},
								open: function() {
									jQuery('.ui-dialog-title').addClass('ui-icon-warning');
									jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
								},
								buttons: {
									"<?php echo __('Descargar') ?>": function() {
										var id_formato = jQuery('#id_formato').val();
										opciones = '';
										if (jQuery('#cartas').is(':checked')) {
											opciones += 'cartas';
										}
										if (jQuery('#agrupar').is(':checked')) {
											opciones += ',agrupar';
										}
										if (jQuery('#cobrosencero_descargar_borradores').is(':checked')) {
											jQuery('#cobrosencero').val(1);
										}
										if (jQuery('#mostrar_asuntos_cobrables_sin_horas_borradores').is(':checked')) {
											jQuery('#mostrar_asuntos_cobrables_sin_horas').val(1);
										}
										ImpresionCobros(false, opciones, id_formato);
										jQuery(this).dialog("close");
										return true;
									},
									"<?php echo __('Cancelar') ?>": function() {
										jQuery(this).dialog('close');
										return false;
									}
								}
							});

				} else {
					GeneraCobros(form, 'print', opcion, id_formato);
				}
			}
			else
			{
				alert('No existen ' + "<?php echo __('borradores') ?>" + ' en el sistema.');
				return false;
			}
		});
	}


	/*
	 Impresión de cobros
	 */
	function ImpresionAsuntosLiquidar(alerta, opcion)
	{
		var form = jQuery('#form_busca');
		var proceso = jQuery('#id_proceso').val();

		if (alerta)
		{
			interrumpeproceso = 0;
			var text_window = '<span style="text-align:center; font-size:11px; color:#000; "> <?php echo __('A continuación se generarán los borradores del periodo que ha seleccionado.') ?><br><br><?php echo __('¿Desea descargar los cobros del periodo?') ?></span><br><br>';
			text_window += '<span style="text-align:center; "> <input type="checkbox" name="cartas" id="cartas" checked="checked" /> Incluir cartas </span> ';

			jQuery('<p/>')
					.attr('title', '<?php echo __('ALERTA') ?>')
					.html(text_window)
					.dialog({
						resizable: true,
						height: 'auto',
						width: 470,
						modal: true,
						close: function(ev, ui) {
							jQuery(this).html('');
							interrumpeproceso = 1;
						},
						open: function() {
							jQuery('.ui-dialog-title').addClass('ui-icon-warning');
							jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
						},
						buttons: {
							'<?php echo __('Descargar') ?>': function() {
								if (jQuery('#cartas').is(':checked')) {
									ImpresionCobros(false, 'cartas');
								} else {
									ImpresionCobros(false, '');
								}
								jQuery(this).dialog('close');
								return true;
							},
							'<?php echo __('Cancelar') ?>': function() {
								jQuery(this).dialog('close');
								return false;
							}
						}
					});
		}
		else
		{
			GeneraCobros(form, 'print_asuntos_liquidar', opcion);
		}
	}

	//refrescar para popup
	function Refrescar() {
		$('opc').value = 'buscar';
		var opc = $('opc').value;

		<?php if (Conf::GetConf($sesion, 'CodigoSecundario')) { ?>
			var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
		<?php } else { ?>
			var codigo_cliente = $('codigo_cliente').value;
		<?php } ?>

		var grupo = $('id_grupo_cliente').value;
		var codigo_asunto = $('codigo_asunto').value;
		var id_usuario = $('id_usuario').value;
		var id_usuario_secundario = $('id_usuario_secundario') ? $('id_usuario_secundario').value : '';
		var id_proceso = $('id_proceso').value;
		var fecha_ini = $('fecha_ini').value;
		var fecha_fin = $('fecha_fin').value;

		if ($('activo').checked == true) {
			var activo = $('activo').value;
		} else {
			var activo = '';
		}

		<?php
		if ($desde) {
			echo "var pagina_desde = '&desde=" . $desde . "';";
		} else {
			echo "var pagina_desde = '';";
		}
		?>

		if ($('codigo_cliente')) {
			var url = "genera_cobros.php?codigo_cliente=" + codigo_cliente + "&popup=1&opc=" + opc + pagina_desde + "&id_grupo=" + id_grupo + "&id_usuario=" + id_usuario + "&id_usuario_secundario=" + id_usuario_secundario + "&id_proceso=" + id_proceso + "&fecha_ini=" + fecha_ini + "&fecha_fin=" + fecha_fin + "&activo=" + activo + "&codigo_asunto=" + codigo_asunto;
		} else if ($('codigo_cliente_secundario')) {
			var url = "genera_cobros.php?codigo_cliente_secundario=" + codigo_cliente_secundario + "&popup=1&opc=" + opc + pagina_desde + "&id_grupo=" + id_grupo + "&id_usuario=" + id_usuario + "&id_usuario_secundario=" + id_usuario_secundario + "&id_proceso=" + id_proceso + "&fecha_ini=" + fecha_ini + "&fecha_fin=" + fecha_fin + "&activo=" + activo + "&codigo_asunto=" + codigo_asunto;
		}

		self.location.href = url;
	}

	//fin refrescar para popup

	//Confirmación de generación de cobros individuales
	function GenerarIndividual(modalidad, id_contrato, fecha_ultimo_cobro, fecha_ini, fecha_fin,
		monto_estimado, monto_real, moneda, id_cobro_pendiente, incluye_honorarios, incluye_gastos) {

		interrumpeproceso = 0;
		var text_window = '<p style="padding:10px;font-size:12px;font-weight:bold;text-align:center;"><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php echo __('Al generar este borrador se eliminarán todos los borradores antiguos asociados a ' . __('este contrato')); ?>.<br>';
		if (modalidad == 'FLAT FEE' && monto_estimado > 0 && monto_real != monto_estimado)
		{
			text_window += '<?php echo __('El monto estipulado en el contrato no coincide con el monto') . " " . __('del cobro') . " " . __('programado, seleccione el monto a utilizar:') ?><br><br>';
			text_window += '<input type="radio" name="radio_monto" id="radio_real" checked /><?php echo __('Monto del Contrato') ?> ' + moneda + ' ' + monto_real + '<br>';
			text_window += '<input type="radio" name="radio_monto" id="radio_estimado" /><?php echo __('Monto del Cobro Programado') ?> ' + moneda + ' ' + monto_estimado + '<br><br>';
		}
		text_window += ' ('
		if (fecha_ini != '')
			text_window += 'Fecha desde: ' + fecha_ini + '<br>';
		text_window += 'Fecha hasta: ' + fecha_fin + ')';
		text_window += '<br><br><b><?php echo __('¿Desea generar el borrador?') ?></b></p>';
		text_window += '<div style="margin:10px auto;text-align:center;font-size:11px;" id="respuestadialog">&nbsp;</div>';
		jQuery('<p/>')
				.attr('title', 'Advertencia')
				.html(text_window)
				.dialog({
					resizable: true,
					height: 'auto',
					width: 470,
					modal: true,
					open: function() {
						jQuery('.ui-dialog-title').addClass('ui-icon-warning');
						jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
					},
					close: function(ev, ui) {
						jQuery(this).html('');
						interrumpeproceso = 1;
					},
					buttons: {
						"<?php echo __('Generar') ?>": function() {

							var dir = "";
							if ((modalidad == 'FLAT FEE') && monto_estimado > 0 && monto_real != monto_estimado) {
								if (jQuery('#radio_estimado').is(':checked'))
									nuevaVentana(
											'GeneraCobroIndividual', 1050, 690,
											"genera_cobros_guarda.php?id_contrato=" + id_contrato +
											"&fecha_ultimo_cobro=" + fecha_ultimo_cobro +
											"&fecha_ini=" + fecha_ini +
											"&fecha_fin=" + fecha_fin +
											"&id_cobro_pendiente=" + id_cobro_pendiente +
											"&monto=" + monto_estimado +
											"&incluye_honorarios=" + incluye_honorarios +
											"&incluye_gastos=" + incluye_gastos +
											"&individual=true"
											);
							} else {
								nuevaVentana(
										'GeneraCobroIndividual', 1050, 690,
										"genera_cobros_guarda.php?id_contrato=" + id_contrato +
										"&fecha_ultimo_cobro=" + fecha_ultimo_cobro +
										"&fecha_ini=" + fecha_ini +
										"&fecha_fin=" + fecha_fin +
										"&id_cobro_pendiente=" + id_cobro_pendiente +
										"&incluye_honorarios=" + incluye_honorarios +
										"&incluye_gastos=" + incluye_gastos +
										"&individual=true"
										);
							}

							jQuery(this).dialog("close");
							jQuery('#boton_buscar').click();
							return true;
						},
						"<?php echo __('Cerrar') ?>": function() {

							jQuery(this).dialog("close");
							jQuery('#boton_buscar').click();
							return false;
						}
					}
				});

}

	var timerProcessLock;
	var seconds = 4;
	function startCheckProcessLock() {
		timerProcessLock = window.setTimeout(checkProcessLock, seconds * 1000);
		if (seconds != 64) {
			seconds += seconds;
		}
	}

	/**
	 * Revisa por ajax si el proceso ha sido desbloqueado.
	 */
	function checkProcessLock() {
		jQuery.get(root_dir + '/app/ProcessLock/get_process_lock_not_notified/<?php echo Cobro::PROCESS_NAME; ?>', function(proceso) {
			if (proceso.id) {
				window.clearTimeout(timerProcessLock);
				jQuery.get(root_dir + '/app/ProcessLock/get_notification_html/' + proceso.id, function(html) {
					mostrar_notificacion(html, proceso.id);
				});
				return;
			}
			startCheckProcessLock();
		});
	}

	/**
	 * Verifica la existencia de procesos de cobros
	 * Si existe un proceso pendiente la función retorna TRUE, de lo contrario FALSE
	 * @return bool
	 */
	function ProcessLock() {
		var url = root_dir + '/app/ProcessLock';
		var reply = {};
		var locker = {};

		jQuery.ajax(url + '/is_locked/<?php echo Cobro::PROCESS_NAME; ?>', {
			async: false,
			success: function(result) {
				reply = result;
			}
		});

		if (reply.locked) {
			jQuery.ajax(url + '/get_locker/<?php echo Cobro::PROCESS_NAME; ?>', {
				async: false,
				success: function(result) {
					locker = result;
				}
			});

			jQuery('<p/>')
				.attr('title', 'Advertencia')
				.append('<p style="font-size:11px;">El proceso se encuentra bloqueado por el usuario ' + locker.nombre_usuario + '<br/><strong>Estado actual:</strong><br/>' + locker.estado + '</p>')
				.dialog({
					autoOpen: true,
					height: 'auto',
					width: 400,
					modal: true,
					open: function() {
						jQuery('.ui-dialog-title')
							.addClass('ui-icon-warning');
						jQuery('.ui-dialog-buttonpane')
							.find('button')
							.addClass('btn')
							.removeClass('ui-button ui-state-hover');
					},
					buttons: {
						'Cerrar': function() {
							jQuery(this).dialog('close');
						}
					}
				});

			return true;
		}

		return false;
	}
</script>

<?php
$BloqueoProceso = new BloqueoProceso($sesion);
$proceso = $BloqueoProceso->getProcessLockedByUserId($sesion->usuario->fields['id_usuario'], Cobro::PROCESS_NAME);
if ($proceso !== false) {
?>
	<script type="text/javascript" defer="defer">
		jQuery.get(root_dir + '/app/ProcessLock/get_process_locked/<?php echo Cobro::PROCESS_NAME; ?>', function(proceso) {
			if (proceso != false && proceso.bloqueado) {
				startCheckProcessLock();
			}
		});
	</script>
<?php } ?>

<form name='form_busca' id='form_busca' action='' method=post>
	<?php
	echo $Form->hidden('opc', '');
	echo $Form->hidden('cobrosencero', 0);
	echo $Form->hidden('mostrar_asuntos_cobrables_sin_horas', 0);
	?>
	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<!-- Fin calendario DIV -->

	<table width="90%">
		<tr>
			<td>
				<fieldset class="tb_base" style="width: 100%;border: 1px solid #BDBDBD;">
					<legend><?php echo 'Filtros' ?></legend>
					<table width='720px' style='border:0px dotted #999999'>
						<tr>
							<td align=right width='30%'>
								<b><?php echo __('Grupo') ?></b>&nbsp;
							</td>
							<td align=left colspan=2>
								<?php echo Html::SelectQuery($sesion, "SELECT id_grupo_cliente, glosa_grupo_cliente FROM grupo_cliente", "id_grupo_cliente", $id_grupo_cliente, "", "Ninguno", '280px') ?>
							</td>
						</tr>
						<tr>
							<td align=right width='30%'><b><?php echo __('Cliente') ?>&nbsp;</b></td>
							<td colspan=2 align=left>
								<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
								<?php if (Conf::GetConf($sesion, 'CodigoSecundario')) { ?>
									<input type="hidden" name="codigo_cliente" id="codigo_cliente"/>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<td align=right style="font-weight:bold;">
								<?php echo __('Asunto') ?>
							</td>
							<td nowrap align=left colspan=2>
								<?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
							</td>
						</tr>
						<tr>
							<td align=right><b><?php echo __('Encargado comercial') ?>&nbsp;</b></td>
							<td colspan=2 align=left><!-- Nuevo Select -->
								<?php echo $Form->select('id_usuario', $sesion->usuario->ListarActivos('', 'SOC'), $id_usuario, array('empty' => __('Cualquiera'), 'style' => 'width: 210px')); ?>
								<input type="hidden" size="6" name="id_proceso" id="id_proceso" value='<?php echo $id_proceso ?>' >
							</td>
						</tr>
						<?php if (Conf::GetConf($sesion, 'EncargadoSecundario')) { ?>
							<tr>
								<td align=right><b><?php echo __('Encargado Secundario') ?>&nbsp;</b></td>
								<td colspan=2 align=left><!-- Nuevo Select -->
									<?php echo $Form->select('id_usuario_secundario', $sesion->usuario->ListarActivos(), $id_usuario_secundario, array('empty' => __('Cualquiera'), 'style' => 'width: 210px')); ?>
									<input type="hidden" size="6" name="id_proceso" id="id_proceso" value='<?php echo $id_proceso ?>' >
								</td>
							</tr>
							<?php
						}

						($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_filtros_generacion_cobro') : false;
						?>
						<tr>
							<td align=right><b><?php echo __('Forma de Tarificación') ?>&nbsp;</b></td>
							<td colspan=2 align=left>
								<?php echo Html::SelectQuery($sesion, $query_forma_cobro, "forma_cobro", $forma_cobro, '', __('Cualquiera'), 'width="200"') ?>
							</td>
						</tr>
						<tr>
							<td align=right><b><?php echo __('Tipo de Liquidación') ?>&nbsp;</b></td>
							<td colspan=2 align=left>
								<?php
								$opts = array(
									'1' => __('Sólo Honorarios'),
									'2' => __('Sólo Gastos'),
									'3' => __('Sólo Mixtas (Honorarios y Gastos)')
								);
								$attrs = array(
									'id' => 'tipo_liquidacion',
									'empty' => __('Todas')
								);
								echo $Form->select('tipo_liquidacion', $opts, $tipo_liquidacion, $attrs);
								?>
							</td>
						</tr>
						<tr>
							<?php if (Conf::GetConf($sesion, 'UsaFechaDesdeCobranza')) {
								?>
								<td align=right><b><?php echo __('Fecha desde') ?>&nbsp;</b></td>
								<td align=left>
									<input type="text" name="fecha_ini" class="fechadiff" value="<?php echo!$fecha_ini ? '' : $fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
								</td>
							</tr>
							<tr>
							<?php } ?>
							<td align=right><b><?php echo __('Fecha hasta') ?>&nbsp;</b></td>
							<td align=left>
								<input onkeydown="if (event.keyCode == 13) GeneraCobros(this.form, '', false)" type="text" class="fechadiff"  name="fecha_fin" value="<?php echo!$fecha_fin ? date('d-m-Y') : $fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
							</td>
							<?php
							if (empty($_POST) || $_POST['activo'] == 1 || $_GET['activo'] == 1) {
								$activo_chk = true;
							} else {
								$activo_chk = false;
							}
							?>
						</tr>
						<tr>
							<td align="right"><b><?php echo __('Activo') ?>&nbsp;</b></td>
							<td align="left"><?php echo $Form->checkbox('activo', 1, $activo_chk);?></td>
						</tr>
						<tr>
							<td></td>
							<td align="left">
								<?php echo $Form->icon_button(__('Buscar'), 'find', array('id' => 'boton_buscar', 'onclick' => "GeneraCobros(jQuery('#form_busca').get(0), '', false)")); ?>
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
	</table>

	<?php if ($opc == 'buscar') { ?>
		<table width="820">
			<tr>
				<td align="right" nowrap>
					<?php echo __('Idioma') ?>: <?php echo Html::SelectQuery($sesion, "SELECT codigo_idioma,glosa_idioma FROM prm_idioma ORDER BY glosa_idioma", "lang", $cobro->fields['codigo_idioma'] != '' ? $cobro->fields['codigo_idioma'] : $contrato->fields['codigo_idioma'], '', '', 80); ?>
				</td>
			</tr>
			<tr>
				<td align="center" id="opciones_excel" colspan="2" style="display: none; font-size: 10px;">
					<input type="hidden" name="opc_ver_horas_trabajadas" id="opc_ver_horas_trabajadas" value="1" />
					<input type="hidden" name="opc_ver_cobrable" id="opc_ver_cobrable" value="1" />
					<input type="hidden" name="opc_ver_asuntos_separados" id="opc_ver_asuntos_separados" <?php echo Conf::GetConf($sesion, 'CodigoSecundario') ? '' : 'checked' ?> value="1" />
					<input type="hidden" name="opc_mostrar_asuntos_cobrables_sin_horas" id="opc_mostrar_asuntos_cobrables_sin_horas" value="1" />
					<?php
					$solicitante = Conf::GetConf($sesion, 'OrdenadoPor');

					if ($solicitante == 0) {  // no mostrar
						?>
						<input type="hidden" name="opc_ver_solicitante" id="opc_ver_solicitante" value="0" />
						<?php
					} elseif ($solicitante == 1) { // obligatorio
						?>
						<input type="hidden" name="opc_ver_solicitante" id="opc_ver_solicitante" value="1" />
						<?php
					} elseif ($solicitante == 2) { // opcional
						?>
						<input type="checkbox" name="opc_ver_solicitante" id="opc_ver_solicitante" value="1" <?php echo $cobro->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?> />
						<label for="opc_ver_solicitante"><?php echo __('Mostrar solicitante') ?></label>
						<?php
					}
					?>
				</td>
			</tr>
			<tr>
				<td align="center" colspan="2">
					<?php
					echo $Form->button(__('Asuntos por') . ' ' . __('cobrar'), array('onclick' => "GeneraCobros(this.form, 'asuntos_liquidar', false)"));
					echo $Form->button(__('Generar borradores').' '.__('masivamente'), array('onclick' => "GeneraCobros(this.form, 'genera', false)"));
					echo $Form->button(__('Excel borradores'), array('onclick' => "GeneraCobros(this.form, 'excel', false)"));
					echo $Form->button(__('Descargar borradores'), array('onclick' => "ImpresionCobros(true, false)"));
					echo $Form->button(__('Emitir cobros').' '.__('masivamente'), array('onclick' => "GeneraCobros(this.form, 'emitir', false)"));
					echo $Form->button(__('Pasar cobros a estado EN REVISIÓN'), array('onclick' => "GeneraCobros(this.form, 'en_revision', false)"));
					?>
				</td>
			</tr>
		</table>
		<?php
	}
	?>
	<br>
	<br />
	<?php echo $Form->icon_button(__('Subir Excel'), 'upload', array('onclick' => 'SubirExcel()')); ?>
</form>

<?php
if ($opc == 'buscar') {
	$b->Imprimir('');
}
echo $Form->script();

function funcionTR(& $contrato) {
	global $sesion;
	global $id_cobro;
	global $p_revisor;
	global $cobros;
	global $opc;
	global $fecha_ini;
	global $fecha_fin;
	global $id_proceso;
	static $i = 0;
	global $tipo_liquidacion;
	global $formato_fecha;
	global $html, $contratofields;

	if ($i % 2 == 0) {
		$color = "#dddddd";
	} else {
		$color = "#ffffff";
	}

	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	if ($contrato->fields['codigo_idioma'] != '') {
		$idioma->Load($contrato->fields['codigo_idioma']);
	} else {
		$idioma->Load(strtolower(Conf::GetConf($sesion, 'Idioma')));
	}
	if (!empty($contrato->fields['fecha_ultimo_cobro'])) {
		$fecha_ultimo_cobro = Utiles::sql2fecha($contrato->fields['fecha_ultimo_cobro'], $formato_fecha, "-");
	} else {
		$fecha_ultimo_cobro = 'N/A';
	}
	if ($contrato->fields['id_contrato'] > 0) {
		$where = 1;
		if ($tipo_liquidacion) {
			$tipo_liquidacion = intval($tipo_liquidacion);
			$where .= " AND cobro.incluye_honorarios = '" . ($tipo_liquidacion & 1) . "' " .
					" AND cobro.incluye_gastos = '" . ($tipo_liquidacion & 2 ? 1 : 0) . "' ";
		}

		$query_pendientes = "SELECT
									cobro_pendiente.id_cobro_pendiente,
									cobro_pendiente.monto_estimado,
									cobro_pendiente.descripcion,
									cobro_pendiente.fecha_cobro,
									prm_moneda.simbolo,
									prm_moneda.cifras_decimales
								FROM cobro_pendiente
								JOIN contrato ON contrato.id_contrato=cobro_pendiente.id_contrato
								JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
								WHERE cobro_pendiente.id_cobro IS NULL AND cobro_pendiente.id_contrato = '" . $contrato->fields['id_contrato'] . "'
								AND cobro_pendiente.fecha_cobro <= '" . Utiles::fecha2sql($fecha_fin) . "' AND cobro_pendiente.hito = 0 ORDER BY cobro_pendiente.fecha_cobro ASC";
		$lista_pendientes = new ListaCobrosPendientes($sesion, '', $query_pendientes);

		//Hitos
		$query_hitos = "SELECT
					cobro_pendiente.id_cobro_pendiente,
					cobro_pendiente.monto_estimado,
					cobro_pendiente.descripcion,
					cobro_pendiente.fecha_cobro,
					cobro_pendiente.id_cobro,
					cobro.estado,
					prm_moneda.simbolo,
					prm_moneda.cifras_decimales
				FROM cobro_pendiente
					JOIN contrato ON contrato.id_contrato=cobro_pendiente.id_contrato
					JOIN prm_moneda ON contrato.id_moneda_monto = prm_moneda.id_moneda
					LEFT JOIN cobro ON cobro.id_cobro = cobro_pendiente.id_cobro
				WHERE
					cobro_pendiente.id_contrato = '" . $contrato->fields['id_contrato'] . "' AND
					cobro_pendiente.hito = 1
				ORDER BY cobro_pendiente.id_cobro_pendiente ASC";
		$lista_hitos = new ListaCobrosPendientes($sesion, '', $query_hitos);

		#se dejó igual hasta que todos los clientes esten ordenados... 08-03-09
		$query_cobros = "SELECT
								id_cobro,
								monto,
								monto_subtotal,
								descuento,
								impuesto,
								cobro.codigo_idioma,
								monto_gastos,
								subtotal_gastos,
								impuesto_gastos,
								fecha_ini,
								fecha_fin,
								prm_moneda.simbolo,
								prm_moneda.cifras_decimales,
								moneda_opcion.simbolo as simbolo_moneda_opcion,
								moneda_opcion.cifras_decimales as cifras_decimales_moneda_opcion,
								cobro.id_proceso,
								incluye_gastos,
								incluye_honorarios
							FROM cobro
							JOIN prm_moneda ON cobro.id_moneda = prm_moneda.id_moneda
							JOIN prm_moneda as moneda_opcion ON moneda_opcion.id_moneda = cobro.opc_moneda_total
							WHERE $where AND cobro.id_contrato = '" . $contrato->fields['id_contrato'] . "'
							AND ( cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ORDER BY cobro.fecha_creacion ASC";
		$lista_cobros = new ListaCobros($sesion, '', $query_cobros);
	}
	$contratofields = $contrato->fields;
	$html = "";
	$html .= "<tr bgcolor=$color style='border-left: 1px solid #409C0B; border-right: 1px solid #409C0B; '>";
	$html .= "<td style='font-size:10px' valing=top><b>" . $contrato->fields[glosa_cliente];
	($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_imprimir_buscador') : false;

	$html .= "</b></td>";

	$lista_asuntos = $contrato->MattersByContract($contrato->fields['id_contrato']);
	$html .= "<td style='font-size:10px' align='left' id='tip_{$i}' valing='top'><b>{$lista_asuntos->limitado}</b></td>";
	$html .= "<td style='font-size:10px' align=center valing=top><b>" . $fecha_ultimo_cobro . "</b></td>";

	if ($contrato->fields['forma_cobro'] == 'RETAINER' || $contrato->fields['forma_cobro'] == 'PROPORCIONAL') {
		$texto_acuerdo = $contrato->fields['forma_cobro'] . " de " . $contrato->fields['simbolo_moneda_monto'] . " " . number_format($contrato->fields['monto'], $contrato->fields['cifras_decimales_moneda_monto'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . " por " . $contrato->fields['retainer_horas'] . " Hrs.";
	} else if ($contrato->fields['forma_cobro'] == 'TASA' || $contrato->fields['forma_cobro'] == 'HITOS' || $contrato->fields['forma_cobro'] == 'ESCALONADA') {
		$texto_acuerdo = $contrato->fields['forma_cobro'];
	} else {
		$texto_acuerdo = $contrato->fields['forma_cobro'] . " por " . $contrato->fields['simbolo_moneda_monto'] . " " . number_format($contrato->fields['monto'], $contrato->fields['cifras_decimales_moneda_monto'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
	}

	$html .= "<td style='font-size:10px' align=left valign=top colspan=2>";
	$html .= "&nbsp;&nbsp;<b>" . $texto_acuerdo . ', Tarifa: ' . $contrato->fields['glosa_tarifa'] . "</b>&nbsp;&nbsp;<a href='javascript:void(0)' onclick=\"nuovaFinestra('Editar_Contrato',730,600,'agregar_contrato.php?popup=1&id_contrato=" . $contrato->fields['id_contrato'] . "');\" style='font-size:10px' title='" . __('Editar Información Comercial') . "'>Editar</a></td>";
	$html .= "</tr>";

	if ($lista_cobros->num > 0 && $contrato->fields['id_contrato'] > 0) {
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; \">";
		$html .= "<td></td><td align=left colspan=4></td>";
		$html .= "</tr>\n";
		#DIV para el borrador..
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; \">";
		$html .= "<td style='border-right:1px dashed #999999; border-left:1px dashed #999999; border-top:1px dashed #999999; font-size:10px'>" . __('Borrador') . "</td><td colspan=4 style='border-right:1px dashed #999999; border-left:1px dashed #999999; border-top:1px dashed #999999;'>";
		$html .= "<div id='cobros_$i'>";
		$html .= "<table width=100%>";
		$txt_iva = __('IVA');
		for ($z = 0; $z < $lista_cobros->num; $z++) {
			$cobro = $lista_cobros->Get($z);
			$idioma_cobro = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
			if ($cobro->fields['codigo_idioma'] != '')
				$idioma_cobro->Load($cobro->fields['codigo_idioma']);
			else
				$idioma_cobro->Load(strtolower(Conf::GetConf($sesion, 'Idioma')));
			$total_horas = $cobros->TotalHorasCobro($cobro->fields['id_cobro']);
			$texto_horas = $cobro->fields['fecha_ini'] != '0000-00-00' ? __('desde') . ' ' . Utiles::sql2fecha($cobro->fields['fecha_ini'], $formato_fecha, "-") . ' ' . __('hasta') . ' ' . Utiles::sql2fecha($cobro->fields['fecha_fin'], $formato_fecha, "-") : __('hasta') . ' ' . Utiles::sql2fecha($cobro->fields['fecha_fin'], $formato_fecha, "-");

			$texto_tipo = empty($cobro->fields['incluye_honorarios']) ? '(sólo gastos)' :
					(empty($cobro->fields['incluye_gastos']) ? '(sólo honorarios)' : '');

			$honorarios = $cobro->fields['simbolo'] . ' ' . number_format($cobro->fields['monto'], 2, $idioma_cobro->fields['separador_decimales'], $idioma_cobro->fields['separador_miles']);
			if (!empty($cobro->fields['impuesto'])) {
				$honorarios = $cobro->fields['simbolo'] . ' ' . number_format($cobro->fields['monto_subtotal'] - $cobro->fields['descuento'], 2, $idioma_cobro->fields['separador_decimales'], $idioma_cobro->fields['separador_miles']) .
						" + $txt_iva ($honorarios)";
			}

			$texto_honorarios = "$honorarios por " .
					number_format($total_horas, 1, $idioma_cobro->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' Hrs. ';

			$gastos = $cobro->fields['simbolo_moneda_opcion'] . ' ' . number_format($cobro->fields['monto_gastos'], $cobro->fields['cifras_decimales_moneda_opcion'], $idioma_cobro->fields['separador_decimales'], $idioma_cobro->fields['separador_miles']);

			if (!empty($cobro->fields['impuesto_gastos'])) {
				$gastos = $cobro->fields['simbolo_moneda_opcion'] . ' ' . number_format($cobro->fields['subtotal_gastos'], $cobro->fields['cifras_decimales_moneda_opcion'], $idioma_cobro->fields['separador_decimales'], $idioma_cobro->fields['separador_miles']) .
						" + $txt_iva ($gastos)";
			}
			$texto_gastos = "$gastos en gastos ";

			$texto_monto = !empty($cobro->fields['incluye_honorarios']) && !empty($cobro->fields['incluye_gastos']) && !empty($cobro->fields['monto_gastos']) ?
					$texto_honorarios . ' y ' . $texto_gastos :
					(!empty($cobro->fields['incluye_honorarios']) ? $texto_honorarios : $texto_gastos);
			$html .= "<tr style='font-size:10px; vertical-align:middle; text-align:center;' id='tr_cobro_{$cobro->fields['id_cobro']}'><td width=3%>&nbsp;<img src='" . Conf::ImgDir() . "/color_amarillo.gif' border=0></td>
									<td align=center width=5% style='font-size:10px'>#" . $cobro->fields['id_cobro'] . "</td>
									<td align=left width=82% style='font-size:10px'>$texto_tipo&nbsp;de " . $texto_monto . $texto_horas . "</td>";


			$html .= "<td align=center style=\"white-space:nowrap; width: 52px;\">";
			$html .= "<a class=\"fl ui-button editar\" style=\"margin: 3px 1px;width: 18px;height: 18px;\"   title='" . __('Continuar con el cobro') . "' href=\"javascript:void(0)\" onclick=\"nuevaVentana('Editar_Cobro',1050,700,'cobros6.php?id_cobro=" . $cobro->fields['id_cobro'] . "&popup=1&contitulo=true', '');\">&nbsp;</a>";
			$html .= "<a class=\"fl ui-button cruz_roja\" style=\"margin: 3px 1px;width: 18px;height: 18px;\" title='" . __('Eliminar cobro') . "'  onclick=\"DeleteCobro('{$cobro->fields['id_cobro']}', {$i}, '{$contrato->fields['id_contrato']}', this)\">&nbsp;</a>";
			$html .= UtilesApp::LogDialog($sesion, 'cobro', $cobro->fields['id_cobro']);

			$html .= "</td>";
		}

		$html .= "</tr></table></div>";
		$html .= "</td></tr>\n";
		#FIN DIV borrador
	}

	if ($lista_pendientes->num > 0 && $contrato->fields['id_contrato'] > 0) {
		$check = $contrato->fields['incluir_en_cierre'] == 1 ? 'checked' : '';
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; \">";
		$html .= "<td></td><td align=left colspan=4></td>";
		$html .= "</tr>\n";
		#DIV para los cobros pendientes.
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; \">";
		$html .= "<td style='border-right:1px dashed #999999; border-left:1px dashed #999999; border-top:1px dashed #999999; font-size:10px'>" . __('Cobros Programados') . "</td><td colspan=4 style='border-right:1px dashed #999999; border-left:1px dashed #999999; border-top:1px dashed #999999;'>";
		$html .= "<div id='pendiente_$i'>";
		$html .= "<table width=100%>";

		for ($z = 0; $z < $lista_pendientes->num; $z++) {
			$pendiente = $lista_pendientes->Get($z);
			$html .= "<tr style='font-size:10px; vertical-align:middle; text-align:center;''><td width=2% align=center>&nbsp;<img src='" . Conf::ImgDir() . "/color_verde.gif' style='vertical-align:middle;' border=0></td>" .
					"<td align=left width=90% style='font-size:10px; vertical-align:middle;' colspan='2' id='glosa_programado_" . $i . "_" . $z . "'>" . __('Para el') . ' ' . Utiles::sql2date($pendiente->fields['fecha_cobro']) . ":" .
					"&nbsp;" . $pendiente->fields['descripcion'] . ' ' . (empty($pendiente->fields['monto_estimado']) ? "" : __('por la suma estimada de') . ' ' . $pendiente->fields['simbolo']
							. " " . number_format($pendiente->fields['monto_estimado'], $pendiente->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles'])) . "</td>"
					. "<script> new Tip('glosa_programado_" . $i . "_" . $z . "', '" . __('Para editar o eliminar el Cobro Programado debe hacerlo desde la edición del contrato') . "', {title : '', effect: '', offset: {x:-2, y:19}}); </script>";

			$html .= "<td align=center width=8%>";

			// Mostrar dos botones de monedas para crear liquidaciones por separado
			if ($contrato->fields['separar_liquidaciones']) {
				if (!($tipo_liquidacion & 2)) { //1-2 = honorarios-gastos, 3 = mixtas
					$html .= "<img src='" . Conf::ImgDir() . "/coins_16_honorarios.png' title='" . __('Generar cobro individual para honorarios') . "' border=0 onclick=\"GenerarIndividual('"
							. $contrato->fields['forma_cobro'] . "'," . $contrato->fields['id_contrato'] . ",'" . $contrato->fields['fecha_ultimo_cobro'] . "','','" . Utiles::sql2fecha($pendiente->fields['fecha_cobro'], $formato_fecha, "-") . "',"
							. ($pendiente->fields['monto_estimado'] ? $pendiente->fields['monto_estimado'] : 0) . "," . $contrato->fields['monto'] . ",'" . $contrato->fields['simbolo'] . "'," . $pendiente->fields['id_cobro_pendiente'] . ", 1, 0)\" >";
				}

				if (!$tipo_liquidacion)
					$html .= "&nbsp;&nbsp;";

				if (!($tipo_liquidacion & 1)) { //1-2 = honorarios-gastos, 3 = mixtas
					$html .= "<img src='" . Conf::ImgDir() . "/coins_16_gastos.png' title='" . __('Generar cobro individual para gastos') . "' border=0 onclick=\"GenerarIndividual('"
							. $contrato->fields['forma_cobro'] . "'," . $contrato->fields['id_contrato'] . ",'" . $contrato->fields['fecha_ultimo_cobro'] . "','','" . Utiles::sql2fecha($pendiente->fields['fecha_cobro'], $formato_fecha, "-") . "',"
							. ($pendiente->fields['monto_estimado'] ? $pendiente->fields['monto_estimado'] : 0) . "," . $contrato->fields['monto'] . ",'" . $contrato->fields['simbolo'] . "'," . $pendiente->fields['id_cobro_pendiente'] . ", 0, 1)\" >";
				}
			} else {
				// Flujo Actual, solo uno que hace ambas cosas
				$html .= "<img src='" . Conf::ImgDir() . "/coins_16.png' title='" . __('Generar cobro individual') . "' border=0 onclick=\"GenerarIndividual('"
						. $contrato->fields['forma_cobro'] . "'," . $contrato->fields['id_contrato'] . ",'" . $contrato->fields['fecha_ultimo_cobro'] . "','','" . Utiles::sql2fecha($pendiente->fields['fecha_cobro'], $formato_fecha, "-") . "',"
						. ($pendiente->fields['monto_estimado'] ? $pendiente->fields['monto_estimado'] : 0) . "," . $contrato->fields['monto'] . ",'" . $contrato->fields['simbolo'] . "'," . $pendiente->fields['id_cobro_pendiente'] . ", 1, 1)\" >";
			}
			if ($z == 0) {
				$html .= "&nbsp;<input type=checkbox name=opc onclick='UpdateContrato(this.checked," . $contrato->fields['id_contrato'] . ");' $check title='" . __('Si está seleccionado se generará un borrador en la generación masiva') . "' >";
			}
			$html .= "</td>";
		}

		$html .= "</tr></table></div>";
		$html .= "</td></tr>\n";
		#FIN DIV cobros pendientes.
	}

	//HITOS
	if ($lista_hitos->num > 0 && $contrato->fields['id_contrato'] > 0) {
		$check = $contrato->fields['incluir_en_cierre'] == 1 ? 'checked' : '';
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; \">";
		$html .= "<td></td><td align=left colspan=4></td>";
		$html .= "</tr>\n";
		$cobro_pendiente = new CobroPendiente($sesion);
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; \">";
		$html .= "<td style='border-right:1px dashed #999999; border-left:1px dashed #999999; border-top:1px dashed #999999; font-size:10px'>" . __('Hitos') . "<br/>" .
				__("Por liquidar") . ": " . $contrato->fields['simbolo_moneda_monto'] . " " . number_format($cobro_pendiente->MontoHitosPorLiquidar($contrato->fields['id_contrato']), $contrato->fields['cifras_decimales_moneda_monto'], '.', '') . "<br/>" .
				__("Liquidado") . ": " . $contrato->fields['simbolo_moneda_monto'] . " " . number_format($cobro_pendiente->MontoHitosLiquidados($contrato->fields['id_contrato']), $contrato->fields['cifras_decimales_moneda_monto'], '.', '') . "<br/>" .
				__("Pagado") . ": " . $contrato->fields['simbolo_moneda_monto'] . " " . number_format($cobro_pendiente->MontoHitosPagados($contrato->fields['id_contrato']), $contrato->fields['cifras_decimales_moneda_monto'], '.', '') . "<br/>" .
				"</td>";
		$html .= "<td colspan=4 style='border-right:1px dashed #999999; border-left:1px dashed #999999; border-top:1px dashed #999999;'>";
		$html .= "<div id='pendiente_$i'>";
		$html .= "<table width=100%>";

		for ($z = 0; $z < $lista_hitos->num; $z++) {
			$pendiente = $lista_hitos->Get($z);
			$color_pendiente = 'verde';
			if (!empty($pendiente->fields['id_cobro']))
				$color_pendiente = $pendiente->fields['estado'] == 'CREADO' || $pendiente->fields['estado'] == 'EN REVISION' ? 'amarillo' : 'blanco';
			$html .= "<tr style='font-size:10px; vertical-align:middle; text-align:center;'>" .
					"<td width=2% align=center>&nbsp;<img src='" . Conf::ImgDir() . "/color_$color_pendiente.gif' style='vertical-align:middle;' border=0></td>";

			if (!empty($pendiente->fields['id_cobro'])) {
				$html .= "<td align='center' width='5%' style='font-size:10px; vertical-align:middle;'>#" . $pendiente->fields['id_cobro'] . "</td>";
				$html .= "<td align=left width=84% style='font-size:10px; vertical-align:middle;' id='glosa_hito_" . $i . "_" . $z . "'>"
						. __('Hito') . ': ' . $pendiente->fields['descripcion'] . ' por un monto de ' . $pendiente->fields['simbolo'] . " " .
						number_format($pendiente->fields['monto_estimado'], $pendiente->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) .
						", " . __("cobro") . " en estado " . __($pendiente->fields['estado']) . "</td>"
						. "<script> new Tip('glosa_hito_" . $i . "_" . $z . "', '" . __('Para editar o eliminar el hito debe hacerlo desde la edición del contrato') . "', {title : '', effect: '', offset: {x:-2, y:19}}); </script>";

				$html .= "<td align=center width=8%/></tr>";
				continue;
			}

			$html .= "<td align=left width=90% style='font-size:10px; vertical-align:middle;' colspan='2' id='glosa_hito_" . $i . "_" . $z . "'>" . __('Hito') . ': ' . $pendiente->fields['descripcion'] . ' por un monto de ' . $pendiente->fields['simbolo'] . " " .
					number_format($pendiente->fields['monto_estimado'], $pendiente->fields['cifras_decimales'], '.', '') .
					(empty($pendiente->fields['fecha_cobro']) ? "" : " (Se recordará el " . Utiles::sql2date($pendiente->fields['fecha_cobro']) . ")") . "</td>"
					. "<script> new Tip('glosa_hito_" . $i . "_" . $z . "', '" . __('Para editar o eliminar el hito debe hacerlo desde la edición del contrato') . "', {title : '', effect: '', offset: {x:-2, y:19}}); </script>";

			$html .= "<td align=center width=8%>";

			// Mostrar dos botones de monedas para crear liquidaciones por separado
			if ($contrato->fields['separar_liquidaciones']) {
				$html .= "<img src='" . Conf::ImgDir() . "/coins_16_honorarios.png' title='" . __('Generar cobro individual para honorarios') . "' border=0 onclick=\"GenerarIndividual('"
						. $contrato->fields['forma_cobro'] . "'," . $contrato->fields['id_contrato'] . ",'" . $contrato->fields['fecha_ultimo_cobro'] . "','','" . Utiles::sql2fecha($pendiente->fields['fecha_cobro'], $formato_fecha, "-") . "',"
						. ($pendiente->fields['monto_estimado'] ? $pendiente->fields['monto_estimado'] : 0) . "," . $contrato->fields['monto'] . ",'" . $contrato->fields['simbolo'] . "'," . $pendiente->fields['id_cobro_pendiente'] . ", 1, 0)\" >";
			} else {
				// Flujo Actual, solo uno que hace ambas cosas
				$html .= "<img src='" . Conf::ImgDir() . "/coins_16.png' title='" . __('Generar cobro individual') . "' border=0 onclick=\"GenerarIndividual('"
						. $contrato->fields['forma_cobro'] . "'," . $contrato->fields['id_contrato'] . ",'" . $contrato->fields['fecha_ultimo_cobro'] . "','','" . Utiles::sql2fecha($pendiente->fields['fecha_cobro'], $formato_fecha, "-") . "',"
						. ($pendiente->fields['monto_estimado'] ? $pendiente->fields['monto_estimado'] : 0) . "," . $contrato->fields['monto'] . ",'" . $contrato->fields['simbolo'] . "'," . $pendiente->fields['id_cobro_pendiente'] . ", 1, 1)\" >";
			}

			$html .= "</td>";
		}

		$html .= "</tr></table></div>";
		$html .= "</td></tr>\n";
	}

	#WIP
	$wip = $contrato->ProximoCobroEstimado($fecha_ini ? Utiles::fecha2sql($fecha_ini) : '', Utiles::fecha2sql($fecha_fin), $contrato->fields['id_contrato']);

	$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; \">";
	$html .= "<td style='border:1px dashed #999999; font-size:10px'>" . __('WIP (Work in progress)') . "</td><td colspan=4 style='border:1px dashed #999999'>";
	$html .= "<div id='wip_$i'>";
	$html .= "<table width=100%>";
	$html .= "<tr style='font-size:10px; vertical-align:middle; text-align:center;'>";
	$html .= "<td width=2% align=center><img src='" . Conf::ImgDir() . "/color_verde.gif' style='align:center; vertical-align:middle;' border=0></td>";

	$wip_honorarios = ($wip[0] != '' ? number_format($wip[0], 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' Hrs.' : '0 Hrs.') .
			" (Según HH en " . $contrato->fields['simbolo'] . ' ' . number_format($wip[1], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ")";

	$wip_gastos = $wip[4] . ' ' . number_format($wip[3], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' en gastos';
	if ($wip[6] > 0) {
		$wip_egresos = $wip[4] . number_format($wip[5], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' en egresos';
		$wip_ingresos = $wip[4] . number_format($wip[6], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' en provisiones';
		$wip_gastos = '<div style="border-bottom: 1px black dotted;display:inline-block;cursor:help;" title="' . $wip_egresos . ' vs ' . $wip_ingresos . '">' . $wip_gastos . '</div>';
	}
	switch ($tipo_liquidacion) { //1-2 = honorarios-gastos, 3 = mixtas
		case 1: $txt_wip = $wip_honorarios;
			break;
		case 2: $txt_wip = $wip_gastos;
			break;
		default: $txt_wip = $wip_honorarios . ' y ' . $wip_gastos;
			break;
	}

	$html .= "<td align=left style='font-size:10px'>$txt_wip</td>";
	$html .= "<td width='8%' align='center' nowrap>";

	// Mostrar dos botones de monedas para crear liquidaciones por separado
	if ($contrato->fields['separar_liquidaciones'] || $contrato->fields['forma_cobro'] == 'HITOS') {
		if (!($tipo_liquidacion & 2) && $contrato->fields['forma_cobro'] != 'HITOS') { //1-2 = honorarios-gastos, 3 = mixtas
			$html .= "<img src='" . Conf::ImgDir() . "/coins_16_honorarios.png' title='" . __('Generar cobro individual para honorarios') . "' border=0 onclick=\"GenerarIndividual('',";
			$html .= $contrato->fields['id_contrato'] . ",'" . $contrato->fields['fecha_ultimo_cobro'] . "','" . $fecha_ini . "','" . $fecha_fin . "',0,0,'',0, 1, 0);\" />";
		}

		if (!$tipo_liquidacion)
			$html .= "&nbsp;&nbsp;";

		if (!($tipo_liquidacion & 1) || ($contrato->fields['forma_cobro'] == 'HITOS' && !($tipo_liquidacion & 1))) { //1-2 = honorarios-gastos, 3 = mixtas
			$html .= "<img src='" . Conf::ImgDir() . "/coins_16_gastos.png' title='" . __('Generar cobro individual para gastos') . "' border=0 onclick=\"GenerarIndividual('',";
			$html .= $contrato->fields['id_contrato'] . ",'" . $contrato->fields['fecha_ultimo_cobro'] . "','" . $fecha_ini . "','" . $fecha_fin . "',0,0,'',0, 0, 1);\" />";
		}
	} else {
		// Flujo Actual, solo uno que hace ambas cosas
		$html .= "<img src='" . Conf::ImgDir() . "/coins_16.png' title='" . __('Generar cobro individual') . "' border=0 onclick=\"GenerarIndividual('',";
		$html .= $contrato->fields['id_contrato'] . ",'" . $contrato->fields['fecha_ultimo_cobro'] . "','" . $fecha_ini . "','" . $fecha_fin . "',0,0,'',0, 1, 1);\" >";
	}

	$html .= "</tr></table></div>";
	$html .= "</td></tr>\n";
	#FIN WIP

	$html .="<tr border=1 bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\"><td colspan=5>&nbsp;</td></tr>";
	$html .="<script> new Tip('tip_$i', '{$lista_asuntos->completo}', {title : '" . __('Listado de asuntos') . "', effect: '', offset: {x:-2, y:10}}); </script>";
	$html .="<input type=hidden name=opc value='" . $opc . "'>";

	$i++;

	return $html;
}

function url_cobro_individual($id_contrato, $codigo_cliente, $glosa_cliente, $forma_cobro, $monto, $codigo_idioma, $simbolo, $asuntos, $asunto_lista, $forma_cobro, $monto_total, $activo, $fecha_ultimo_cobro, $glosa_tarifa, $incluir_en_cierre, $retainer_horas, $simbolo_moneda_monto, $cifras_decimales_moneda_monto, $separar_liquidaciones) {

	global $sesion, $arrayHH, $arrayMIXTAS, $arrayClientes, $arrayContratos, $totalHITOS;

	if ($forma_cobro == 'HITOS') {
		++$totalHITOS;
		return;
	}


	$arrayClientes[$codigo_cliente] = $codigo_cliente;
	$arrayContratos[] = $id_contrato;

	if ($separar_liquidaciones) {
		$arrayHH[] = $id_contrato;
	} else {
		$arrayMIXTAS[] = $id_contrato;
	}
}

$pagina->PrintBottom($popup);
