<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion(array('DAT'));
$Pagina = new Pagina($Sesion);
$id_usuario = $Sesion->usuario->fields['id_usuario'];
$desde_agrega_cliente = true;

$cliente = new Cliente($Sesion);
$contrato = new Contrato($Sesion);
$archivo = new Archivo($Sesion);
$Form = new Form();
$SelectHelper = new FormSelectHelper();
$usuario = new UsuarioExt($Sesion);

$CodigoClienteAsuntoModificable = (boolean) Conf::GetConf($Sesion, 'CodigoClienteAsuntoModificable');

if (!empty($_GET['codigo_cliente'])) {
	$codigo_cliente = $_GET['codigo_cliente'];
	$cliente->LoadByCodigo($codigo_cliente);
	$id_cliente = $cliente->fields['id_cliente'];
}

if ($id_cliente > 0) {
	$cliente->Load($id_cliente);
	$contrato->Load($cliente->fields['id_contrato']);
	$cobro = new Cobro($Sesion);
} else {
	$codigo_cliente = $cliente->AsignarCodigoCliente();
	$cliente->fields['codigo_cliente'] = $codigo_cliente;
}

$CodigoSecundario = Conf::GetConf($Sesion, 'CodigoSecundario');
$validacionesCliente = Conf::GetConf($Sesion, 'ValidacionesCliente');
$validacionesClienteJS = $validacionesCliente ? 'true' : 'false';
require_once Conf::ServerDir() . '/interfaces/agregar_contrato_validaciones.php';

if ($validacionesCliente) {
	$obligatorio = '<span class="req">*</span>';
} else {
	$obligatorio = '';
}

if ($opcion == "guardar") {

	$cli = new Cliente($Sesion);
	$cli->LoadByCodigo($codigo_cliente);

	//	Validaciones
	$val = false;

	if ($cli->Loaded()) {
		if (!$activo) {
			$cli->InactivarAsuntos();
		}

		if (($cli->fields['id_cliente'] != $cliente->fields['id_cliente']) && ($cliente->Loaded())) {
			$Pagina->AddError(__('Existe cliente'));
			$val = true;
		}

		if (!$cliente->Loaded()) {
			$Pagina->AddError(__('Existe cliente'));
			$val = true;
		}

		if ($codigo_cliente_secundario) {
			$query_codigos = "SELECT codigo_cliente_secundario FROM cliente WHERE id_cliente != '{$cliente->fields['id_cliente']}'";
			$resp_codigos = mysql_query($query_codigos, $Sesion->dbh) or Utiles::errorSQL($query_codigos, __FILE__, __LINE__, $Sesion->dbh);

			while (list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos)) {
				if ($codigo_cliente_secundario == $codigo_cliente_secundario_temp) {
					$Pagina->FatalError('El código secundario ingresado ya existe');
					$val = true;
				}
			}
		}

		$loadasuntos = false;
	} else {
		$loadasuntos = true;
		if ($codigo_cliente_secundario) {
			$where = '1';

			if ($cliente->Loaded()) {
				$where .= " AND id_cliente != '{$cliente->fields['id_cliente']}'";
			}

			$query_codigos = "SELECT codigo_cliente_secundario FROM cliente WHERE $where";

			$resp_codigos = mysql_query($query_codigos, $Sesion->dbh) or Utiles::errorSQL($query_codigos, __FILE__, __LINE__, $Sesion->dbh);

			while (list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos)) {
				if ($codigo_cliente_secundario == $codigo_cliente_secundario_temp) {
					$Pagina->FatalError('El código secundario ingresado ya existe');
					$val = true;
				}
			}
		}
	}

	if (Conf::GetConf($Sesion, 'EncargadoSecundario')) {
		$id_usuario_secundario = (!empty($id_usuario_secundario) && $id_usuario_secundario != -1 ) ? $id_usuario_secundario : 0;
	}

	if ($validacionesCliente) {
		if (empty($glosa_cliente)) {
			$Pagina->AddError(__("Por favor ingrese el nombre del cliente"));
		}
		if ($id_usuario_encargado == '-1') {
			$Pagina->AddError(__("Por favor seleccione un usuario encargado"));
		}
		if (empty($codigo_cliente)) {
			$Pagina->AddError(__("Por favor ingrese el codigo del cliente"));
		}
		if (Conf::GetConf($Sesion, 'ClienteReferencia') && empty($id_cliente_referencia)) {
			$Pagina->AddError(__("Por favor ingrese la referencia"));
		}
	}

	foreach (array_keys($hito_fecha) as $i) {
		if (!empty($hito_fecha[$i]) || !empty($hito_descripcion[$i]) || !empty($hito_monto_estimado[$i])) {
			if (empty($hito_fecha[$i])) {
				$Pagina->AddError(__('Debe ingresar una fecha de recordatorio para el ') . __('hito') . (empty($hito_descripcion[$i]) ? ' ' . __('con monto') . ' ' . $hito_monto_estimado[$i] : ' ' . $hito_descripcion[$i]));
			}
			if (empty($hito_descripcion[$i])) {
				$Pagina->AddError(__('Debe ingresar una descripción válida para el ') . __('hito') . (empty($hito_fecha[$i]) ? ' ' . __('con monto') . ' ' . $hito_monto_estimado[$i] : ' ' . __('con fecha') . ' ' . $hito_fecha[$i]));
			}
			if ($hito_monto_estimado[$i] <= 0) {
				$Pagina->AddError(__('Debe ingresar un monto válido para el ') . __('hito') . (empty($hito_descripcion[$i]) ? ' ' . __('con fecha') . ' ' . $hito_fecha[$i] : ' ' . $hito_descripcion[$i]));
			}
		}
	}

	$contractValidation->validate();

	$errores = $Pagina->GetErrors();

	if (!empty($errores)) {
		$val = true;
		$loadasuntos = false;
	}

	if (!$val) {

		$cliente->Edit("glosa_cliente", $glosa_cliente);
		$cliente->Edit("codigo_cliente", $codigo_cliente);

		// El código de homologación se utiliza para los clientes con LEDES, por defecto es el código del cliente
		$cliente->Edit("codigo_homologacion", !empty($codigo_homologacion) ? $codigo_homologacion : $codigo_cliente);

		if (Conf::GetConf($Sesion, 'CodigoSecundario') && !empty($codigo_cliente_secundario)) {
			$cliente->Edit("codigo_cliente_secundario", strtoupper($codigo_cliente_secundario));
		} else {
			$cliente->Edit("codigo_cliente_secundario", null);
		}

		$cliente->Edit("id_moneda", 1);

		if ($activo != 1 && $cliente->fields['activo'] == '1') {
			$cliente->Edit("fecha_inactivo", date('Y-m-d H:i:s'));
		} else if ($activo == 1 && $cliente->fields['activo'] != '1') {
			$cliente->Edit("fecha_inactivo", 'NULL');
		}

		$cliente->Edit("activo", $activo == 1 ? '1' : '0');
		$cliente->Edit("id_usuario_encargado", $id_usuario_encargado);
		$cliente->Edit("id_grupo_cliente", $id_grupo_cliente > 0 ? $id_grupo_cliente : 'NULL');
		$cliente->Edit("alerta_hh", $cliente_alerta_hh);
		$cliente->Edit("alerta_monto", $cliente_alerta_monto);
		$cliente->Edit("limite_hh", $cliente_limite_hh);
		$cliente->Edit("limite_monto", $cliente_limite_monto);
		$cliente->Edit("desglose_referencia", $desglose_referencia);
		$cliente->Edit("id_cliente_referencia", (!empty($id_cliente_referencia) && $id_cliente_referencia != '-1' ) ? $id_cliente_referencia : "NULL" );


		if ($cliente->Write()) {

			//	Segmento : "Contrato";
			$contrato->Load($cliente->fields['id_contrato']);

			if ($forma_cobro != 'TASA' && $forma_cobro != 'HITOS' && $forma_cobro != 'ESCALONADA' && $monto == '') {
				$Pagina->AddError(__('Ud. ha seleccionado forma de cobro:') . ' ' . $forma_cobro . ' ' . __('y no ha ingresado monto'));
				$val = true;
			} elseif ($forma_cobro == 'TASA') {
				$monto = '0';
			}
			if ($tipo_tarifa == 'flat') {
				if (empty($tarifa_flat)) {
					$Pagina->AddError(__('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto'));
					$val = true;
				} else {
					$tarifa = new Tarifa($Sesion);
					$id_tarifa = $tarifa->GuardaTarifaFlat($tarifa_flat, $id_moneda, $id_tarifa_flat);
					$_REQUEST['id_tarifa'] = $id_tarifa;
				}
			}
			if (isset($_REQUEST['nombre_contacto'])) {
				// nombre_contacto no existe como campo en la tabla contrato y es necesario crear la variable "contacto" dentro de _REQUEST
				$_REQUEST['contacto'] = trim($_REQUEST['nombre_contacto']);
			}

			$contrato->Fill($_REQUEST, true);
			$contrato->Edit('codigo_cliente', $codigo_cliente);

			if ($contrato->Write()) {
				// Segmento "Cobros pendientes";
				CobroPendiente::EliminarPorContrato($Sesion, $contrato->fields['id_contrato']);
				if ($contrato->fields['forma_cobro'] !== 'FLAT FEE') {
					$valor_fecha = array();
				}

				for ($i = 2; $i <= sizeof($valor_fecha); $i++) {
					$cobro_pendiente = new CobroPendiente($Sesion);
					$cobro_pendiente->Edit("id_contrato", $contrato->fields['id_contrato']);
					$cobro_pendiente->Edit("fecha_cobro", Utiles::fecha2sql($valor_fecha[$i]));
					$cobro_pendiente->Edit("descripcion", $valor_descripcion[$i]);
					$cobro_pendiente->Edit("monto_estimado", $valor_monto_estimado[$i]);
					$cobro_pendiente->Write();
				}
				$cliente->Edit("id_contrato", $contrato->fields['id_contrato']);

				if ($forma_cobro == 'HITOS') {
					foreach (array_keys($hito_fecha) as $i) {
						if (empty($hito_monto_estimado[$i])) {
							continue;
						}
						$cobro_pendiente = new CobroPendiente($Sesion);
						$cobro_pendiente->Edit("id_contrato", $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
						$cobro_pendiente->Edit("fecha_cobro", empty($hito_fecha[$i]) ? 'NULL' : Utiles::fecha2sql($hito_fecha[$i]));
						$cobro_pendiente->Edit("descripcion", $hito_descripcion[$i]);
						$cobro_pendiente->Edit("observaciones", $hito_observaciones[$i]);
						$cobro_pendiente->Edit("monto_estimado", $hito_monto_estimado[$i]);
						$cobro_pendiente->Edit("hito", '1');
						$cobro_pendiente->Write();
					}
				}

				ContratoDocumentoLegal::EliminarDocumentosLegales($Sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
				if (is_array($docs_legales)) {
					foreach ($docs_legales as $doc_legal) {
						if (empty($doc_legal['documento_legal']) or ( empty($doc_legal['honorario']) and empty($doc_legal['gastos_con_iva']) and empty($doc_legal['gastos_sin_iva']) )) {
							continue;
						}
						$contrato_doc_legal = new ContratoDocumentoLegal($Sesion);
						$contrato_doc_legal->Edit('id_contrato', $contrato->fields['id_contrato']);
						$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
						if (!empty($doc_legal['honorario'])) {
							$contrato_doc_legal->Edit('honorarios', 1);
						}
						if (!empty($doc_legal['gastos_con_iva'])) {
							$contrato_doc_legal->Edit('gastos_con_impuestos', 1);
						}
						if (!empty($doc_legal['gastos_sin_iva'])) {
							$contrato_doc_legal->Edit('gastos_sin_impuestos', 1);
						}
						$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
						$contrato_doc_legal->Write();
					}
				}

				if ($cliente->Write()) {
					$Pagina->AddInfo(__('Cliente') . ' ' . __('Guardado con exito') . '<br>' . __('Contrato guardado con éxito'));
					//To S3
					$archivo->LoadById($contrato->fields['id_contrato']);
					if ($desde_agrega_cliente == 1) {
						$mp = new \TTB\Mixpanel();
						$mp->identifyAndTrack($RUT, "Agregar Cliente");
					}
				} else {
					$Pagina->AddError($contrato->error);
				}
			} else {
				$Pagina->AddError($cliente->error);
			}
		}
	}

	$asuntos = explode(';', Conf::GetConf($Sesion, 'AgregarAsuntosPorDefecto'));

	if ($asuntos[0] == "true" && $loadasuntos) {
		if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
			if (empty($codigo_cliente_secundario)) {
				$codigo_cliente_secundario = $codigo_cliente;
			}
			$codigo_asunto_secundario = $asunto->AsignarCodigoAsuntoSecundario($codigo_cliente_secundario);
		} else {
			$codigo_asunto_secundario = null;
		}
		for ($i = 1; $i < count($asuntos); $i++) {
			$asunto = new Asunto($Sesion);
			$asunto->Edit('codigo_asunto', $asunto->AsignarCodigoAsunto($codigo_cliente));
			$asunto->Edit('codigo_asunto_secundario', $codigo_asunto_secundario);
			$asunto->Edit('glosa_asunto', $asuntos[$i]);
			$asunto->Edit('codigo_cliente', $codigo_cliente);
			$asunto->Edit('id_contrato', $contrato->fields['id_contrato']);
			$asunto->Edit('id_usuario', $id_usuario);
			$asunto->Edit('contacto', $contacto);
			$asunto->Edit("fono_contacto", $fono_contacto_contrato);
			$asunto->Edit("email_contacto", $email_contacto_contrato);
			$asunto->Edit("direccion_contacto", $direccion_contacto_contrato);

			if (!$id_usuario_encargado || $id_usuario_encargado == -1) {
				$id_usuario_encargado = ($id_usuario_secundario) ? $id_usuario_secundario : 'NULL';
			}

			if (!$id_usuario_encargado == -1) {
				$asunto->Edit("id_encargado", $id_usuario_encargado);
			}
			$asunto->Write();
		}
	}
}

if (Conf::GetConf($Sesion, 'AlertaCliente')) {
	$display_alerta = '';
} else {
	$display_alerta = 'display:none;';
}

// Segmento "Encargado Comercial";

$params_array['lista_permisos'] = array('REV', 'DAT');

$permisos = $Sesion->usuario->permisos->Find('FindPermiso', $params_array);

//	SEGMENTO USUARIO ENCARGADO

$segmento_usuario_encargado = '';
$validar_usuario_encargado = false;

if (Conf::GetConf($Sesion, 'VerCampoUsuarioEncargado') != 1) {

	if (!Conf::GetConf($Sesion, 'EncargadoSecundario')) {

		if (Conf::GetConf($Sesion, 'AtacheSecundarioSoloAsunto') == 0) {

			$segmento_usuario_encargado .= '<tr  class="controls controls-row ">';
			$segmento_usuario_encargado .= '<td class="ar">';
			$segmento_usuario_encargado .= '<div class="span2">' . __('Usuario encargado') . '</div> ';
			if (!$contractValidation->validationSkipped('id_usuario_encargado')) {
				$segmento_usuario_encargado .= $obligatorio;
			}
			$segmento_usuario_encargado .= '</td>';
			$segmento_usuario_encargado .= '<td class="al"> <!-- Nuevo Select -->';
			$id_default = $cliente->fields['id_usuario_encargado'] ? $cliente->fields['id_usuario_encargado'] : $id_usuario_encargado;
			$segmento_usuario_encargado .= $Form->select('id_usuario_encargado', $usuario->get_usuarios_agregar_cliente($id_usuario, $permisos->fields['permitido']), $id_default, array('empty' => '', 'style' => 'width: 170px'));
			$segmento_usuario_encargado .= '</td>';
			$segmento_usuario_encargado .= '</tr>';
			$validar_usuario_encargado = true;
		}
	}
}

//	SEGMENTO CLIENTE REFERENCIA

$segmento_cliente_referencia = '';

if (Conf::GetConf($Sesion, 'ClienteReferencia')) {
	$segmento_cliente_referencia .= '<tr>';
	$segmento_cliente_referencia .= '<td class="ar">';
	if (!$contractValidation->validationSkipped('id_cliente_referencia')) {
		$segmento_cliente_referencia .= '<div class="controls controls-row">' . __('Referencia') . $obligatorio . '</div>';
	} else {
		$segmento_cliente_referencia .= '<div class="controls controls-row">' . __('Referencia') . '</div>';
	}
	$segmento_cliente_referencia .= '</td>';
	$segmento_cliente_referencia .= '<td class="al">';
	$segmento_cliente_referencia .= '<div class="span2">';

	$segmento_cliente_referencia .= $SelectHelper->ajax_select(
		'id_cliente_referencia',
		$cliente->fields['id_cliente_referencia'] ? $cliente->fields['id_cliente_referencia'] : $id_cliente_referencia,
		array('class' => 'span3', 'style' => 'display:inline'),
		array(
			'source' => 'ajax/ajax_prm.php?prm=ClienteReferencia&fields=orden,requiere_desglose',
			'onLoad' => '
				var element = selected_id_cliente_referencia;
				jQuery("#desglose_referencia").hide();
				if (element && element.requiere_desglose == "1") {
					jQuery("#desglose_referencia").show();
				}
			',
			'onChange' => '
				var element = selected_id_cliente_referencia;
				jQuery("#desglose_referencia").hide();
				if (element && element.requiere_desglose == "1") {
					jQuery("#desglose_referencia").show();
				}
			'
		)
	);

	$segmento_cliente_referencia .= '&nbsp;';
	$segmento_cliente_referencia .= $Form->input('desglose_referencia', $cliente->fields['desglose_referencia'], array('placeholder' => 'Referido', 'style' => 'display:none', 'class' => 'span5', 'label' => false, 'id' => 'desglose_referencia'));
	$segmento_cliente_referencia .= '</div>';
	$segmento_cliente_referencia .= '</td>';
	$segmento_cliente_referencia .= '</tr>';
}

// TIPS DEL FORMULARIO

$Pagina->titulo = __('Ingreso cliente');
$Pagina->PrintTop();

?>

<script src="//static.thetimebilling.com/js/bootstrap.min.js" type="text/javascript"></script>

<form name='formulario' id="formulario-cliente" method="post" action="<?php echo $_SERVER[PHP_SELF] ?>" >
	<input type="hidden" name="opcion" value="guardar" />
	<input type="hidden" name='opcion_contrato' value="guardar_contrato" />
	<input type="hidden" name="id_cliente" value="<?php echo $cliente->fields['id_cliente'] ?>" />
	<input type="hidden" name="id_contrato" value="<?php echo $contrato->fields['id_contrato'] ?>" />
	<input type="hidden" name="desde" id="desde" value="agregar_cliente" />

	<fieldset id="formularioinicial" class="tb_base" style="border: 1px solid #BDBDBD;">

		<legend><?php echo __('Agregar Cliente') ?>&nbsp;&nbsp;<?php echo $cliente->fields['activo'] == 0 && $id_cliente ? '<span style="color:#FF0000; font-size:10px">(' . __('Este cliente está Inactivo') . ')</span>' : '' ?></legend>

		<table width="90%" cellspacing="3" cellpadding="3" >

			<tr  class="controls controls-row " >
				<td class="ar"  width="200">
					<div class="span2">
						<?php echo __('Código'); ?>
						<?php echo $obligatorio; ?>
					</div >
				</td>
				<td class="al" width="600">
					<div class="controls controls-row" style="white-space:nowrap;">
						<input type="text" style="float:left;" class="input-small span2" placeholder="0000" name="codigo_cliente" size="5" maxlength="5" <?php echo !$CodigoClienteAsuntoModificable ? 'readonly="readonly"' : ''; ?> value="<?php echo $cliente->fields['codigo_cliente'] ?>" onchange="this.value = this.value.toUpperCase()" />

						<div class="span4" style="float:left;">
							&nbsp;&nbsp;&nbsp;
							<?php if (Conf::GetConf($Sesion, 'CodigoSecundario')) { ?>
								<label><?php echo __('Código secundario') ?></label>
								<input type="text" class="input-small" id="codigo_cliente_secundario" name="codigo_cliente_secundario"
								       size="15" maxlength="20" value="<?php echo $cliente->fields['codigo_cliente_secundario']; ?>"
								       onchange="this.value = this.value.toUpperCase()" style='text-transform: uppercase;'/>
								<?php echo $CodigoSecundario ? $obligatorio : '<span class="help-inline">(' . __('Opcional') . ')</span>'; ?>
							<?php } ?>
						</div>
					</div>
				</td>
			</tr>
			<?php if (Conf::GetConf($Sesion, 'ExportacionLedes')) { ?>
				<tr>
					<td align="right" title="<?php echo __('Código con el que el cliente identifica internamente el cliente. Es obligatorio si se desea generar un archivo en formato LEDES'); ?>">
						<?php echo __('Código de homologación'); ?>
					</td>
					<td align="left">
						<input name="codigo_homologacion" size="45" value="<?php echo $cliente->fields['codigo_homologacion']; ?>" />
					</td>
				</tr>
			<?php } ?>

			<tr class="controls controls-row">
				<td class="ar">
					<div class="span2"><?php echo __('Nombre') ?>
						<span class="req inline-help">*</span>
					</div>
				</td>
				<td class="al">
					<input type="text" class="span5" name="glosa_cliente" id="glosa_cliente" size="50" value="<?php echo $cliente->fields['glosa_cliente'] ? $cliente->fields['glosa_cliente'] : $glosa_cliente ?>"  />
				</td>
			</tr>
			<tr  class="controls controls-row ">
				<td class="ar">
					<div class="span2"><?php echo __('Grupo') ?></div>
				</td>
				<td class="al">
					<?php echo $SelectHelper->ajax_select(
						'id_grupo_cliente',
						$cliente->fields['id_grupo_cliente'] ? $cliente->fields['id_grupo_cliente'] : $id_grupo_cliente,
						array('id' => 'id_grupo_cliente', 'class' => 'span3', 'style' => 'display:inline'),
						array(
						  'source' => 'ajax/ajax_prm.php?prm=GrupoCliente&single_class=1&order_by=glosa_grupo_cliente&fields=glosa_grupo_cliente,codigo_cliente,id_pais,id_grupo_cliente',
						  'selectedName' => 'selected_group',
						  'onLoad' => '
							var element = selected_group;
							jQuery("#edit_group").hide()
							if (element && element.id_grupo_cliente) {
								jQuery("#edit_group").show()
							}
						  ',
						  'onChange' => '
							var element = selected_group;
							jQuery("#edit_group").hide()
							if (element && element.id_grupo_cliente) {
								jQuery("#edit_group").show()
							}
						  '
						)
					  );
					  ?>

					<a href="#" id="add_group" ><img border="0" src="<?php echo Conf::ImgDir()?>/agregar.gif"></a>
					<a href="#" id="edit_group" style="display:none;"><img border="0" src="<?php echo Conf::ImgDir()?>/editar_on.gif"></a>
					<script>
					jQuery(document).ready(function() {
						var closeModalGrupo = function() {
							jQuery('#formulario-grupo #id_pais_grupo').remove();
							jQuery('#formulario-grupo #guardar_grupo').remove();
							jQuery('#formulario-grupo #cancelar_grupo').remove();
							jQuery('#formulario-grupo #eliminar_grupo').remove();
							jQuery('#formulario-grupo').closest('.ui-dialog-content').dialog('destroy').remove();
						};

						var saveGroup = function() {
							var url = '../../fw/tablas/ajax_tablas.php';
							jQuery.post(url, jQuery('#formulario-grupo').serialize(), function(data) {
								if (data.success) {
									FormSelectHelper.reload_id_grupo_cliente();
								} else {
									alert('Ocurrio un error al guardar.');
								}
								closeModalGrupo();
							}, 'json');
						  return false;
									};

						var deleteGroup = function(id) {
							if (!confirm('¿Está seguro de eliminar el grupo seleccionado?')) {
								return;
							}
							var url = '../../fw/tablas/ajax_tablas.php';
							jQuery('#formulario-grupo input[name="accion"]').val('eliminar_registro');
							jQuery.post(url, jQuery('#formulario-grupo').serialize(), function(data) {
								if (data.success) {
									FormSelectHelper.reload_id_grupo_cliente();
								} else {
									alert('Ocurrio un error al eliminar. Quizá el grupo esté asociado a otro cliente');
								}
								closeModalGrupo();
							}, 'json');
						  return false;
						}

						var editGroup = function(id) {
							var url = 'editar_grupo.php';
							jQuery.post(url, {'tabla': 'grupo_cliente', id: id}, function(html) {
								jQuery('<div/>').html(html).dialog({
									title: 'Agregar/Modificar Grupo',
									width: 500,
									height: 250,
									modal: true,
									close: function() {
										closeModalGrupo();
									}
								});
								jQuery('#guardar_grupo').click(function() {
								  saveGroup();
								});
								jQuery('#cancelar_grupo').click(function() {
								  closeModalGrupo();
								});
								jQuery('#eliminar_grupo').click(function() {
									deleteGroup(jQuery('#id_grupo_cliente').val());
								  closeModalGrupo();
								});
							}, 'html');
							return false;
						};

						jQuery('#add_group').click(function() {
							editGroup();
							return false;
						})

						jQuery('#edit_group').click(function() {
							editGroup(jQuery('#id_grupo_cliente').val());
							return false;
						});

					});
					</script>
				</td>
			</tr>

			<!-- SEGMENTO CLIENTE REFERENCIA -->

			<?php echo $segmento_cliente_referencia; ?>

			<!-- FIN SEGMENTO -->

			<!-- SEGMENTO USUARIO ENCARGADO -->

			<?php echo $segmento_usuario_encargado; ?>

			<!--- FIN SEGMENTO -->

			<tr class="controls controls-row ">
				<td class="ar">
					<div class="span2">
						<?php
						echo __('Fecha Creación');
						$intfechacreacion = intval(date('Ymd', strtotime($cliente->fields['fecha_creacion'])));
						if ($intfechacreacion > 19990101) {
							$fecha_creacion = date('d-m-Y', strtotime($cliente->fields['fecha_creacion']));
						} else {
							$fecha_creacion = date('d-m-Y');
						}
						?>
					</div>
				</td>
				<td class="al">
					<div class="span3">
						<input type="text" name="fecha_creacion" class="span2 fechadiff" id="fecha_creacion" readonly="true" size="50" value="<?php echo $fecha_creacion; ?>"  />
					</div>

				</td>
			</tr>

			<tr class="controls controls-row ">
				<td class="ar">
					<div class="span2">
						<?php echo __('Activo') ?>
					</div>
				</td>
				<td class="al">
					<div class="span4">
						<label for  class="activo">
							<input type='checkbox' name='activo' id="activo" value='1' <?php echo $cliente->fields['activo'] == 1 ? 'checked="checked"' : !$id_cliente ? 'checked="checked"' : ''  ?>/>
							&nbsp;<?php echo __('Los clientes inactivos no aparecen en los listados.') ?>
						</label>
					</div>
				</td>
			</tr>

			<tr>
				<td align="right" colspan="2">
					<div class="span6">&nbsp;<!--espaciador--></div>
				</td>
			</tr>

	</fieldset>

	<table width='100%' cellspacing="0" cellpadding="0">
		<tr>
			<td>
				<?php require_once Conf::ServerDir() . '/interfaces/agregar_contrato.php'; ?>
			</td>
		</tr>
	</table>

	<table width='100%' cellspacing="0" cellpadding="0" style="<?php echo $display_alerta; ?>">
		<tr>
			<td colspan="2" align="center">
				<fieldset  class="border_plomo tb_base">
					<legend><?php echo __('Alertas') ?></legend>
					<p>&nbsp;<?php echo __('El sistema enviará un email de alerta al encargado del cliente si se superan estos límites:') ?></p>

					<table>
						<tr>
							<td align=right>
								<input name="cliente_limite_hh" value="<?php echo $cliente->fields['limite_hh'] ? $cliente->fields['limite_hh'] : '0' ?>" size=5 title="<?php echo __('Total de Horas') ?>"/>
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Total de Horas') ?>"><?php echo __('Límite de horas') ?></span>
							</td>
							<td align=right>
								<input name=cliente_limite_monto value="<?php echo $cliente->fields['limite_monto'] ? $cliente->fields['limite_monto'] : '0' ?>" size=5 title="<?php echo __('Valor Total según Tarifa Hora Hombre') ?>"/>
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Valor Total según Tarifa Hora Hombre') ?>"><?php echo __('Límite de monto') ?></span>
							</td>
						</tr>
						<tr>
							<td align=right>
								<input name=cliente_alerta_hh value="<?php echo $cliente->fields['alerta_hh'] ? $cliente->fields['alerta_hh'] : '0' ?>" title="<?php echo __('Total de Horas en trabajos no cobrados') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Total de Horas en trabajos no cobrados') ?>"><?php echo __('horas no cobradas') ?></span>
							</td>
							<td align=right>
								<input name=cliente_alerta_monto value="<?php echo $cliente->fields['alerta_monto'] ? $cliente->fields['alerta_monto'] : '0' ?>" title="<?php echo __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>"><?php echo __('monto según horas no cobradas') ?>
							</td>
						</tr>
					</table>

				</fieldset>
			</td>
		</tr>
	</table>

	<table width="100%" cellspacing="3" cellpadding="3">
		<tr>
			<td colspan="2" align="center">

				<?php
				if ($cant_encargados > 0) {

					if (Conf::GetConf($Sesion, 'RevisarTarifas')) {
						$funcion_validar = "return RevisarTarifas('id_tarifa', 'id_moneda', jQuery('#formulario-cliente').get(0), false);";
					} else {
						$funcion_validar = "return Validar(jQuery('#formulario-cliente').get(0));";
					}

					echo $Form->button(__('Guardar'), array('onclick' => $funcion_validar));

				} else { ?>
					<span style="font-size:10px;background-color:#C6DEAD"><?php echo __('No se han configurado encargados comerciales') . '<br>' . __('Para configurar los encargados comerciales debe ir a Usuarios y activar el perfil comercial.') ?></span>
				<?php } ?>

			</td>
		</tr>
	</table>

	<br/>
	<br/>

	<?php if (!empty($cliente->fields['id_cliente']) && $cliente->fields['activo'] == 1): ?>
	<table width="100%">
		<tr>
			<td class="cvs" align="center">
				<?php
				$btn_title = __('Asuntos');
				$attrs = array(
					'title' => $btn_title,
					'onclick' => "iframeLoad('asuntos.php?codigo_cliente={$cliente->fields['codigo_cliente']}&opc=entregar_asunto&popup=1&from=agregar_cliente');"
				);
				echo $Form->button($btn_title, $attrs);
				?>
			</td>
			<td class="cvs" align="center">
				<?php
				$btn_title = __('Contratos');
				$attrs = array(
					'title' => $btn_title,
					'onclick' => "iframeLoad('contratos.php?codigo_cliente={$cliente->fields['codigo_cliente']}&popup=1&buscar=1&activo=SI');"
				);
				echo $Form->button($btn_title, $attrs);
				?>
			</td>
			<td class="cvs" align="center">
				<?php
				$btn_title = __('Cobros');
				$attrs = array(
					'title' => $btn_title,
					'onclick' => "iframeLoad('lista_cobros.php?codigo_cliente={$cliente->fields['codigo_cliente']}&popup=1&opc=buscar&no_mostrar_filtros=1');"
				);
				echo $Form->button($btn_title, $attrs);
				?>
			</td>
		</tr>
		<tr>
			<td class="cvs" align="center" colspan=3>
				<iframe name='iframe_asuntos'  class="resizableframe" id='iframe_asuntos' src='about:blank' style="width:100%; height: 300px; border:none;">&nbsp;</iframe>
			</td>
		</tr>
	</table>
	<?php endif; ?>

</form>
<?php echo $Form->script(); ?>
<style type="text/css">

	textarea,
	input[type="text"],
	input[type="password"],
	input[type="datetime"],
	input[type="datetime-local"],
	input[type="date"],
	input[type="month"],
	input[type="time"],
	input[type="week"],
	input[type="number"],
	input[type="email"],
	input[type="url"],
	input[type="search"],
	input[type="tel"],
	input[type="color"],
	.uneditable-input {
		padding: 1px 2px !important;
	}

	select {
		padding: 1px 1px 1px 3px !important;
		height:20px !important;
	}

	h2 {
		font-size:14px;
		line-height:18px;
	}

	legend {
		vertical-align:top  !important;
		margin-bottom:15px !important;
		border-bottom:0 none !important;
		width:auto !important;
	}

	.input-append .add-on, .input-prepend .add-on {
		padding: 2px 5px;
	}

	fieldset {
		border:1px solid #CCC !important;
		margin:auto;
	}

	.selectMultiple {
		height:80px !important;
	}

</style>


<script type="text/javascript">

	var CodigoSecundario =<?php echo $CodigoSecundario; ?>;
	var glosa_cliente_unica = false;
	var rut_cliente_unica = false;
	var glosa_cliente_tmp = '';
	var rut_cliente_tmp = '';
	var tmp_time = 0;
	var cargando = false;

	jQuery(document).ready(function() {
		setTimeout(function() {
			jQuery("#iframe_asuntos").attr('src', iframesrc);
		}, 2000);
	});

	function validarUnicoCliente(dato, campo, id_cliente) {

		var accion = 'existe_' + campo + '_cliente';
		if (id_cliente !== undefined) {
			var url_ajax = 'ajax.php?accion=' + accion + '&dato_cliente=' + dato + '&id_cliente=' + id_cliente;

		} else {
			var url_ajax = 'ajax.php?accion=' + accion + '&dato_cliente=' + dato;
		}

		jQuery.ajax(url_ajax, {
			async: false,
			success: function(response) {
				if (response == 0) {
					if (campo == 'glosa') {
						glosa_cliente_unica = true;
					} else if (campo == 'rut') {
						rut_cliente_unica = true;
					}
				} else if (response == 1) {
					if (campo == 'glosa') {
						glosa_cliente_unica = false;
					} else if (campo == 'rut') {
						rut_cliente_unica = false;
					}
				}
			}
		});

		return true;
	}

	function Validar(form) {

		if (!form) {
			var form = $('formulario');
		}

		var plugin_facturacion_mx = '<?php echo $plugins_activos ?>';
		if (plugin_facturacion_mx != '') {
			if (form.id_pais.options[0].selected == true) {
				alert("<?php echo __('Debe ingresar el pais del cliente. Es Obligatorio debido a Facturación Electrónica') ?>");
				form.id_pais.focus();
				return false;
			}
		}

		<?php if (Conf::GetConf($Sesion, 'CodigoSecundario') && Conf::GetConf($Sesion, 'CodigoClienteSecundarioCorrelativo')) { ?>
			if (jQuery('#codigo_cliente_secundario').hasClass('error-correlativo')) {
				alert(jQuery('#codigo_cliente_secundario').data('glosa-error'));
				jQuery('#codigo_cliente_secundario').focus();
				return false;
			}
		<?php } ?>

		form.glosa_cliente.value = form.glosa_cliente.value.trim();
		if (!form.glosa_cliente.value) {
			alert("<?php echo __('Debe ingresar el nombre del cliente') ?>");
			form.glosa_cliente.focus();
			return false;
		}
		if (validarUnicoCliente(form.glosa_cliente.value, 'glosa', form.id_cliente.value)) {
			if (!glosa_cliente_unica) {
				alert("El nombre del <?php echo __('cliente'); ?> ya existe, por favor corregir");
				form.glosa_cliente.focus();
				return false;
			}
		}

		<?php if ($validacionesCliente && $validar_usuario_encargado) { ?>
			if (form.id_usuario_encargado.value == "-1") {
				alert("<?php echo __('Debe seleccionar un usuario encargado') ?>");
				form.id_usuario_encargado.focus();
				return false;
			}
		<?php } ?>

		form.factura_rut.value = form.factura_rut.value.trim();
		if (form.factura_rut.value) {
			validarUnicoCliente(form.factura_rut.value, 'rut', form.id_cliente.value);
			if (!rut_cliente_unica) {
				if (!confirm(("El <?php echo __('ROL/RUT') . ' del ' . __('cliente'); ?> ya existe, ¿desea continuar de todas formas?"))) {
					form.factura_rut.focus();
					return false;
				}
			}
		}

<?php if ($validacionesCliente) { ?>


	<?php if (Conf::GetConf($Sesion, 'ClienteReferencia')) { ?>
					if (!form.id_cliente_referencia.value || form.id_cliente_referencia.value == -1) {
						alert("<?php echo __('Debe ingresar la referencia') ?>");
						form.id_cliente_referencia.focus();
						return false;
					}
	<?php } ?>

	<?php echo $contractValidation->getClientValidationsScripts(); ?>


<?php } ?>


		// NUEVO MODULO FACTURA
<?php if (Conf::GetConf($Sesion, 'NuevoModuloFactura')) { ?>
			if (!validar_doc_legales(true)) {
				return false;
			}
<?php } ?>

<?php
if (Conf::GetConf($Sesion, 'TodoMayuscula')) {
	echo "form.glosa_cliente.value=form.glosa_cliente.value.toUpperCase();";
}
?>

<?php if (Conf::GetConf($Sesion, 'CodigoSecundario')) { ?>
			if (!form.codigo_cliente_secundario.value) {
				alert("<?php echo __('Debe ingresar el código secundario del cliente') ?>");
				form.codigo_cliente_secundario.focus();
				return false;
			}
<?php } ?>


		if (form.monto.value < 0) {
			alert('Atención! Se ha seleccionado la forma de cobro Retainer con un monto 0');
			return false;
		}

		var forma_cobro = jQuery('#div_cobro').children("input:checked").val();
		if (forma_cobro == 'RETAINER' && form.monto.value == 0 && form.monto.value != ''
						&& (form.monto_posterior.value != form.monto.value || form.forma_cobro_posterior.value != forma_cobro)) {
			alert('Se eligió Retainer como Forma de Cobro e ingresó el monto 0');
		}

		form.submit();
		return true;
	}

	function MuestraPorValidacion(divID) {
		var divArea = $(divID);
		var divAreaImg = $(divID + "_img");
		var divAreaVisible = divArea.style['display'] != "none";
		divArea.style['display'] = "inline";
		divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
	}

	function calcHeight(idIframe, idMainElm) {
		ifr = $(idIframe);
		the_size = ifr.$(idMainElm).offsetHeight + 20;
		new Effect.Morph(ifr, {
			style: 'height:' + the_size + 'px',
			duration: 0.2
		});
	}

	function ShowMonto() {
		div = document.getElementById("div_monto");
		div.style.display = "block";
	}

	function HideMonto() {
		div = document.getElementById("div_monto");
		div.style.display = "none";
	}

	function goLite(form, boton) {
		var btn = $(boton);
		btn.style['color'] = '#336699';
		btn.style['borderTopColor'] = '#666666';
		btn.style['borderBottomColor'] = '#666666';
	}

	function goDim(form, boton) {
		var btn = $(boton);
		btn.style['color'] = '#777777';
		btn.style['borderTopColor'] = '#AAAAAA';
		btn.style['borderBottomColor'] = '#AAAAAA';
	}

	function iframeLoad(url) {
		window.document.getElementById('iframe_asuntos').src = url;
	}

	jQuery(document).ready(function() {
		<?php if (Conf::GetConf($Sesion, 'CodigoSecundario') && Conf::GetConf($Sesion, 'CodigoClienteSecundarioCorrelativo')) { ?>
			<?php if (!$cliente->Loaded()) { ?>

				jQuery.get('ajax/cliente.php', {'opt': 'ultimo_codigo'}, function(resp) {
					if (resp.error) {
						alert(resp.error);
						return;
					}
					jQuery('#codigo_cliente_secundario').val(resp.codigo);
				}, 'json');

				jQuery('#codigo_cliente_secundario').change(function() {
					var me = jQuery(this);
					var patt = /^(0+)/;
					if (patt.test(me.val())) {
						me.val(me.val().replace(patt, ''));
					}
					jQuery.get('ajax/cliente.php', {'opt': 'validar_codigo', codigo: me.val()}, function(resp) {
						if (resp.error) {
							alert(resp.error);
							me.addClass('error-correlativo');
							me.data('glosa-error', resp.error);
						} else {
							me.removeClass('error-correlativo');
							me.data('glosa-error', 'resp.error');
						}
					}, 'json');
				});
			<?php } ?>
		<?php } else {?>
			jQuery('#codigo_cliente_secundario').blur(function() {
				if (jQuery(this).val() === "") {
					return;
				}
				<?php
				if ($_GET['id_cliente']) {
					echo 'var id_cliente=' . intval($_GET['id_cliente']) . ';';
				} else {
					echo 'var id_cliente=null;';
				}
				?>

				var dato = jQuery(this).val();
				var campo = jQuery(this).attr('id');
				var accion = 'existe_' + campo + '_cliente';
				var url_ajax = 'ajax.php?accion=' + accion + '&dato_cliente=' + dato + '&id_cliente=' + '<?php echo !empty($cliente->fields['id_cliente']) ? $cliente->fields['id_cliente'] : null; ?>';

				jQuery.get(url_ajax, function(data) {
					objResp = null;
					try {
						var objResp = JSON.parse(data);
					} catch (e) {
						console.log(e);
					}

					if (objResp) {
						var bd_cliente = 1 * objResp.id_cliente;

						if (id_cliente !== null && bd_cliente === id_cliente) {
							console.log(id_cliente, bd_cliente);
							return true;
						} else {
							console.log(id_cliente, objResp);

							var codigo_cliente = objResp.codigo_cliente;
							var codigo_cliente_secundario = objResp.codigo_cliente_secundario;
							var glosa_cliente = objResp.glosa_cliente;

							if (codigo_cliente !== "") {
								jQuery('#formularioinicial').prepend('<div  class="alert"><span  id="alerta"></span><a class="close" data-dismiss="alert">×</a>  </div>');
								var MensajeAlerta = "Error: el código secundario " + codigo_cliente_secundario + " ya existe en la Base de Datos y corresponde a <a href='?id_cliente=" + bd_cliente + "'>[" + codigo_cliente + "] " + glosa_cliente + "</a>."
								jQuery('#alerta').html(MensajeAlerta).alert();
							}
						}
					}
				});
			});
		<?php } ?>
	});

	<?php
	if ($CodigoSecundario) {
		echo "var iframesrc='asuntos.php?codigo_cliente_secundario=" . $cliente->fields['codigo_cliente_secundario'] . "&opc=entregar_asunto&popup=1&from=agregar_cliente';";
	} else {
		echo "var iframesrc='asuntos.php?codigo_cliente=" . $cliente->fields['codigo_cliente'] . "&opc=entregar_asunto&popup=1&from=agregar_cliente';";
	}
	?>

	jQuery('#iframe_asuntos').load(function() {
		frame = jQuery(this);
		frame.css('height', frame[0].contentWindow.document.body.offsetHeight + 'px');
	});
</script>

<?php
$Pagina->PrintBottom();
