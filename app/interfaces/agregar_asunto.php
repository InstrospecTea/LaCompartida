<?php
require_once dirname(__FILE__) . '/../conf.php';
use TTB\Pagina as Pagina;

$Sesion = new Sesion(array('DAT', 'SASU'));
$Pagina = new Pagina($Sesion);
$id_usuario = $Sesion->usuario->fields['id_usuario'];
$PrmTipoProyecto = new PrmTipoProyecto($Sesion);
$Form = new Form;
$SelectHelper = new FormSelectHelper();
$AutocompleteHelper = new FormAutocompleteHelper();
$validacionesCliente = Conf::GetConf($Sesion, 'ValidacionesCliente') && $cobro_independiente;
$validacionesClienteJS = Conf::GetConf($Sesion, 'ValidacionesCliente') ? "(document.getElementById('cobro_independiente').checked)" : 'false';
require_once Conf::ServerDir() . '/interfaces/agregar_contrato_validaciones.php';
$usuario_responsable_obligatorio = Conf::GetConf($Sesion, 'ObligatorioEncargadoComercial');
$usuario_secundario_obligatorio = Conf::GetConf($Sesion, 'ObligatorioEncargadoSecundarioAsunto');
$encargado_obligatorio = Conf::GetConf($Sesion, 'AtacheSecundarioSoloAsunto') == 1;
$contrato = new Contrato($Sesion);
$Cliente = new Cliente($Sesion);
$Asunto = new Asunto($Sesion);

if ($codigo_cliente_secundario != '') {
	$Cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
	$codigo_cliente = $Cliente->fields['codigo_cliente'];
}

$CodigoClienteAsuntoModificable = (boolean) Conf::GetConf($Sesion, 'CodigoClienteAsuntoModificable');

//  Edicion de un asunto
if ($id_asunto > 0) {
	if (!$Asunto->Load($id_asunto)) {
		$Pagina->FatalError('Código inválido');
	}

	if ($Asunto->fields['id_contrato'] > 0) {
		$contrato->Load($Asunto->fields['id_contrato']);
	}

	$Cliente->LoadByCodigo($Asunto->fields['codigo_cliente']);

	if (!$Cliente->Loaded()) {
		if ($codigo_cliente != '') {
			$Cliente->LoadByCodigo($codigo_cliente);
		}
	} elseif ($Cliente->fields['codigo_cliente'] != $codigo_cliente) {
		// Esto hay que revisarlo se usó como parche y se debería de corregir
		if (Conf::GetConf($Sesion, 'CodigoEspecialGastos')) {
			$codigo_asunto = $Asunto->AsignarCodigoAsunto($codigo_cliente, $glosa_asunto);
		} elseif ($id_asunto != '') {
			$codigo_asunto = $Asunto->fields['codigo_asunto'];
		} else {
			$codigo_asunto = $Asunto->AsignarCodigoAsunto($codigo_cliente);
		}

		// validación para que al cambiar un asunto de un cliente a otro,
		// no existan cobros ni gastos asociados para el cliente inicial
		if ($opcion == "guardar") { //entra aqui cuando la edicion viene desde guardar
			$query = "SELECT COUNT(*) FROM cobro WHERE id_cobro IN (SELECT c.id_cobro FROM cobro_asunto c WHERE codigo_asunto = '" . $Asunto->fields['codigo_asunto'] . "' ) AND codigo_cliente = '" . $Cliente->fields['codigo_cliente'] . "' ";
			$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
			list($count) = mysql_fetch_array($resp);

			if ($count > 0) {
				$Pagina->AddError(__('No se puede cambiar el cliente a un asunto que tiene ') . __('cobros') . ' ' . __('asociados'));
			}

			$query = "SELECT COUNT(*) FROM cta_corriente WHERE codigo_asunto = '" . $Asunto->fields['codigo_asunto'] . "' AND codigo_cliente = '" . $Cliente->fields['codigo_cliente'] . "' ";
			$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
			list($count) = mysql_fetch_array($resp);

			if ($count > 0) {
				$Pagina->AddError(__('No se puede cambiar el cliente a un asunto que tiene gastos asociados'));
			}
		}
	} elseif ($Cliente->fields['codigo_cliente_secundario'] != $codigo_cliente_secundario && Conf::GetConf($Sesion, 'CodigoSecundario')) {
		$codigo_asunto = $Asunto->AsignarCodigoAsunto($codigo_cliente);
	}
}

if ($codigo_cliente != '' && !$Cliente->Loaded()) {
	$Cliente->LoadByCodigo($codigo_cliente);
	$loaded = Conf::GetConf($Sesion, 'CodigoSecundario') ?
		$Cliente->LoadByCodigoSecundario($codigo_cliente) :
		$Cliente->LoadByCodigo($codigo_cliente);

	if ($loaded) {
		$codigo_cliente = $Cliente->fields['codigo_cliente'];
	}
}

if ($Cliente->Loaded() && empty($id_asunto) && (!isset($opcion) || $opcion != "guardar")) {
	$ContratoCliente = new Contrato($Sesion);
	$ContratoCliente->Load($Cliente->fields['id_contrato']);
	$cargar_datos_contrato_cliente_defecto = $ContratoCliente->fields;
}

if ($opcion == 'guardar') {
	$enviar_mail = 1;

	if (! $Cliente->Loaded()) {
		$Pagina->AddError(__('El cliente seleccionado no existe en el sistema'));
	}

	if (empty($glosa_asunto)) {
		$Pagina->AddError(__('Por favor ingrese un título para el ') . __('asunto'));
	}

	if (empty($codigo_cliente)) {
		$Pagina->AddError(__('Por favor ingrese el codigo del cliente'));
	}

	if (Conf::GetConf($Sesion, 'ValidacionesCliente')) {
		if (empty($id_area_proyecto)) {
			$Pagina->AddError(__('Por favor ingrese el área del ') . __('asunto'));
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

	if (!$val || $opc_copiar) {
		$As = new Asunto($Sesion);
		$As->LoadByCodigo($codigo_asunto);
		if ($As->Loaded()) {
			$enviar_mail = 0;
		}

		if (!$Asunto->Loaded() || !$codigo_asunto) {
			if (Conf::GetConf($Sesion, 'CodigoEspecialGastos')) {
				$codigo_asunto = $Asunto->AsignarCodigoAsunto($codigo_cliente, $glosa_asunto);
			} elseif ($codigo_asunto == '') {
				$codigo_asunto = $Asunto->AsignarCodigoAsunto($codigo_cliente);
			}
		}

		if (!$codigo_cliente_secundario) {
			$codigo_cliente_secundario = $Cliente->CodigoACodigoSecundario($codigo_cliente);
		}

		$Asunto->NoEditar("opcion");
		$Asunto->NoEditar("popup");
		$Asunto->NoEditar("motivo");
		$Asunto->NoEditar("id_usuario_tarifa");
		$Asunto->NoEditar("id_moneda_tarifa");
		$Asunto->NoEditar("tarifa_especial");

		$Asunto->Edit("id_usuario", $Sesion->usuario->fields['id_usuario']);
		$Asunto->Edit("codigo_asunto", $codigo_asunto, true);

		if (Conf::GetConf($Sesion, 'CodigoSecundario') || !empty($codigo_asunto_secundario)) {
			$Asunto->Edit("codigo_asunto_secundario", $codigo_cliente_secundario . '-' . strtoupper($codigo_asunto_secundario));
		} else {
			$Asunto->Edit("codigo_asunto_secundario", null);
		}

		if (Conf::GetConf($Sesion, 'TodoMayuscula')) {
			$glosa_asunto = strtoupper($glosa_asunto);
		}

		$Asunto->Edit("glosa_asunto", $glosa_asunto);
		$Asunto->Edit("codigo_cliente", $codigo_cliente, true);

		if (Conf::GetConf($Sesion, 'ExportacionLedes')) {
			$Asunto->Edit("codigo_homologacion", $codigo_homologacion ? $codigo_homologacion : 'NULL');
		}

		$Asunto->Edit("id_tipo_asunto", $id_tipo_asunto, true);

		if (!empty($id_area_proyecto)) {
			$Asunto->Edit("id_area_proyecto", $id_area_proyecto, true);
		} else {
			$Asunto->Edit("id_area_proyecto", "NULL");
		}

		if (!is_null($desglose_area)) {
			$Asunto->Edit("desglose_area", $desglose_area);
		}

		if (!is_null($giro)) {
			$Asunto->Edit("giro", $giro);
		}

		$Asunto->Edit("id_idioma", $id_idioma);
		$Asunto->Edit("descripcion_asunto", $descripcion_asunto);
		$Asunto->Edit("id_encargado", !empty($id_encargado) ? $id_encargado : "NULL");
		$Asunto->Edit("id_encargado2", !empty($id_encargado2) ? $id_encargado2 : "NULL");
		$Asunto->Edit("contacto", $asunto_contacto);
		$Asunto->Edit("contraparte", $contraparte);
		$Asunto->Edit("cotizado_con", $cotizado_con);
		$Asunto->Edit("fono_contacto", $fono_contacto);
		$Asunto->Edit("email_contacto", $email_contacto);
		$Asunto->Edit("actividades_obligatorias", $actividades_obligatorias ? '1' : '0');
		$Asunto->Edit("activo", intval($activo), true);

		if (!$activo) {
			$fecha_inactivo = date('Y-m-d H:i:s');
			$Asunto->Edit("fecha_inactivo", $fecha_inactivo, true);
		} else {
			$Asunto->Edit("fecha_inactivo", '', true);
		}

		$Asunto->Edit("cobrable", intval($cobrable), true);
		$Asunto->Edit("mensual", $mensual ? "SI" : "NO");
		$Asunto->Edit("alerta_hh", $asunto_alerta_hh);
		$Asunto->Edit("alerta_monto", $asunto_alerta_monto);
		$Asunto->Edit("limite_hh", $asunto_limite_hh);
		$Asunto->Edit("limite_monto", $asunto_limite_monto);
		$cobro_pendiente = false;

		if ($cobro_independiente) {
			// CONTRATO
			if ($Asunto->fields['id_contrato'] != $Cliente->fields['id_contrato']) {
				$contrato->Load($Asunto->fields['id_contrato']);
			} else if ($Asunto->fields['id_contrato_indep'] > 0 && ($Asunto->fields['id_contrato_indep'] != $Cliente->fields['id_contrato'])) {
				$contrato->Load($Asunto->fields['id_contrato_indep']);
			} else {
				$contrato = new Contrato($Sesion);
			}

			if ($forma_cobro != 'TASA' && $forma_cobro != 'HITOS' && $forma_cobro != 'ESCALONADA' && $monto == '') {
				$Pagina->AddError(__('Ud. ha seleccionado forma de ') . __('cobro') . ': ' . $forma_cobro . ' ' . __('y no ha ingresado monto'));
				$val = true;
			} elseif ($forma_cobro == 'TASA') {
				$monto = '0';
			}

			if ($tipo_tarifa == 'flat') {
				if (empty($tarifa_flat)) {
					$Pagina->AddError(__('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto'));
					$val = true;
				} else {
					$Tarifa = new Tarifa($Sesion);
					$id_tarifa = $Tarifa->GuardaTarifaFlat($tarifa_flat, $id_moneda, $id_tarifa_flat);
					$_REQUEST['id_tarifa'] = $id_tarifa;
				}
			}

			if (isset($_REQUEST['nombre_contacto'])) {
				// nombre_contacto no existe como campo en la tabla contrato y es necesario crear la variable "contacto" dentro de _REQUEST
				$_REQUEST['contacto'] = trim($_REQUEST['nombre_contacto']);
			}

			$contrato->Fill($_REQUEST, true);
			$contrato->Edit('codigo_cliente', $codigo_cliente);
			$contrato->Edit('fecha_inicio_cap', Utiles::fecha2sql($fecha_inicio_cap));

			if ($contrato->Write()) {

				// Subiendo Archivo
				if (!empty($archivo_data)) {
					$archivo->Edit('id_contrato', $contrato->fields['id_contrato']);
					$archivo->Edit('descripcion', $descripcion);
					$archivo->Edit('archivo_data', $archivo_data);
					$archivo->Write();
				}

				// Cobro pendiente
				CobroPendiente::EliminarPorContrato($Sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
				if ($contrato->fields['forma_cobro'] == 'FLAT FEE') {
					for ($i = 2; $i <= sizeof($valor_fecha); $i++) {
						$CobroPendiente = new CobroPendiente($Sesion);
						$CobroPendiente->Edit("id_contrato", $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
						$CobroPendiente->Edit("fecha_cobro", Utiles::fecha2sql($valor_fecha[$i]));
						$CobroPendiente->Edit("descripcion", $valor_descripcion[$i]);
						$CobroPendiente->Edit("monto_estimado", $valor_monto_estimado[$i]);
						$CobroPendiente->Write();
					}
				}

				foreach (array_keys($hito_fecha) as $i) {
					if (!empty($hito_monto_estimado[$i])) {
						$CobroPendiente = new CobroPendiente($Sesion);
						$CobroPendiente->Edit("id_contrato", $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
						$CobroPendiente->Edit("fecha_cobro", empty($hito_fecha[$i]) ? 'NULL' : Utiles::fecha2sql($hito_fecha[$i]));
						$CobroPendiente->Edit("descripcion", $hito_descripcion[$i]);
						$CobroPendiente->Edit("observaciones", $hito_observaciones[$i]);
						$CobroPendiente->Edit("monto_estimado", $hito_monto_estimado[$i]);
						$CobroPendiente->Edit("hito", '1');
						$CobroPendiente->Write();
					}
				}

				$Asunto->Edit("id_contrato", $contrato->fields['id_contrato']);
				$Asunto->Edit("id_contrato_indep", $contrato->fields['id_contrato']);

				ContratoDocumentoLegal::EliminarDocumentosLegales($Sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
				if (is_array($docs_legales)) {
					foreach ($docs_legales as $doc_legal) {
						if (empty($doc_legal['documento_legal']) or ( empty($doc_legal['honorario']) and empty($doc_legal['gastos_con_iva']) and empty($doc_legal['gastos_sin_iva']) )) {
							continue;
						}

						$ContratoDocumentoLegal = new ContratoDocumentoLegal($Sesion);
						$ContratoDocumentoLegal->Edit('id_contrato', $contrato->fields['id_contrato']);
						$ContratoDocumentoLegal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);

						if (!empty($doc_legal['honorario'])) {
							$ContratoDocumentoLegal->Edit('honorarios', 1);
						}

						if (!empty($doc_legal['gastos_con_iva'])) {
							$ContratoDocumentoLegal->Edit('gastos_con_impuestos', 1);
						}

						if (!empty($doc_legal['gastos_sin_iva'])) {
							$ContratoDocumentoLegal->Edit('gastos_sin_impuestos', 1);
						}

						$ContratoDocumentoLegal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
						$ContratoDocumentoLegal->Write();
					}
				}
			} else {
				$Pagina->AddError($contrato->error);
			}
		} else {
			if ($Asunto->fields['id_contrato'] > 0) {
				$criteria = new Criteria($Sesion);
				$criteria->add_select('COUNT(*)', 'total')
					->add_from('cobro_pendiente')
					->add_restriction(CriteriaRestriction::equals('id_contrato', $Asunto->fields['id_contrato']));

				$result = $criteria->run();
				$cobro_pendiente = ($result[0]['total'] > 0) ? true : false;
			}

			if ($cobro_pendiente) {
				$Pagina->AddError(__('El') . ' ' . __('contrato') . ' ' . __('tiene cobros programados configurados, no se puede desvincular del') . ' ' . __('asunto') . ' ' . __('hasta que quite los cobros programados.'));
			} else {
				$Contrato_indep = $Asunto->fields['id_contrato_indep'];
				$Asunto->Edit("id_contrato", $Cliente->fields['id_contrato']);
				$Asunto->Edit("id_contrato_indep", null);
			}
		}

		if (!$cobro_pendiente) {
			$existeCodigoAsuntoSecundario = false;
			if (!$Asunto->Loaded() && $Asunto->fields['codigo_asunto_secundario']) {
				$existeCodigoAsuntoSecundario = $Asunto->existeCodigoAsuntoSecundario($Asunto->fields['codigo_asunto_secundario']);
			} else if ($Asunto->Loaded() && $Asunto->fields['codigo_asunto_secundario']) {
				$existeCodigoAsuntoSecundario = $Asunto->existeCodigoAsuntoSecundarioParaOtroIdAsunto($Asunto->fields['codigo_asunto_secundario'], $Asunto->fields['id_asunto']);
			}

			if ($existeCodigoAsuntoSecundario) {
				$Pagina->AddError(sprintf(__("El código de %s secundario ingresado ya está siendo utilizado por otro %s"), __('asunto'), __('asunto')));
			} else {
				if ($Asunto->Write()) {
					$Asunto->writeAreaDetails($id_desglose_area);
					$Asunto->writeEconomicActivities($id_asunto_giro);
					$Pagina->AddInfo(__('Asunto') . ' ' . __('Guardado con exito') . '<br>' . __('Contrato guardado con éxito'));

					if ($Asunto->fields['id_contrato_indep'] === null && isset($Contrato_indep)) {
						$ContratoObj = new Contrato($Sesion);
						$ContratoObj->Load($Contrato_indep);
						$ContratoObj->Eliminar();
					}
				} else {
					$Pagina->AddError($Asunto->error);
				}
			}

			$MailAsuntoNuevo = Conf::GetConf($Sesion, 'MailAsuntoNuevo');

			if ($enviar_mail && $MailAsuntoNuevo) {
				EnviarEmail($Asunto);
			}
		}
	}

	$errors = $Pagina->GetErrors();
	if (empty($errors)) {
		$NuevoCliente = new Cliente($Sesion);
		if ($nuevo_codigo_cliente_secundario != '') {
			$NuevoCliente->LoadByCodigoSecundario($nuevo_codigo_cliente_secundario);
			$nuevo_codigo_cliente = $NuevoCliente->fields['codigo_cliente'];
		} else {
			$NuevoCliente = new Cliente($Sesion);
			$NuevoCliente->LoadByCodigo($nuevo_codigo_cliente);
		}
		if ($NuevoCliente->Loaded()) {
			$response = $Asunto->CambiaCliente($NuevoCliente);
			if (!empty($response['errors'])) {
				$Pagina->AddError($response['errors']);
			} else {
				if (!empty($response['Client'])) {
					$NuevoCliente = $response['Client'];
					$Cliente = $response['Client'];
					$Pagina->AddInfo("El asunto fue asociado al cliente: <b>{$Cliente->fields['glosa_cliente']}</b>");
				}
			}
		}
	}
}

$id_idioma_default = $contrato->IdIdiomaPorDefecto($Sesion);
$AreaProyecto = new AreaProyecto($Sesion);

$Pagina->titulo = "Ingreso de " . __('asunto');
$Pagina->PrintTop($popup);

if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
	$field_codigo_asunto_secundario = array_pop(explode('-', $Asunto->fields['codigo_asunto_secundario']));

	if (Conf::GetConf($Sesion, 'CodigoAsuntoSecundarioCorrelativo')) {
		?>
		<script type="text/javascript">
			var codigo_asunto_secundario = <?php echo $Asunto->Loaded() ? $field_codigo_asunto_secundario : 'null'; ?>;
			function valueInteger(elm) {
					var patt = /^(0+)/;
					var val = elm.val();
					if (patt.test(val) || val !== Math.abs(val)) {
							elm.val(Math.abs(val.replace(patt, '')));
					}
			}
			jQuery(document).ready(function () {
		<?php if (!$Asunto->Loaded()) { ?>
						jQuery.get('ajax/asunto_secundario.php', {'opt': 'ultimo_codigo'}, function (resp) {
								if (resp.error) {
										alert(resp.error);
										return;
								}
								jQuery('#codigo_asunto_secundario').val(resp.codigo);
								codigo_asunto_secundario = resp.codigo;
						}, 'json');
		<?php } ?>

					jQuery('#codigo_asunto_secundario').change(function () {
							var me = jQuery(this);
							valueInteger(me);
							if (codigo_asunto_secundario != me.val()) {
									jQuery.get('ajax/asunto_secundario.php', {'opt': 'validar_codigo', codigo: me.val()}, function (resp) {
											if (resp.error) {
													alert(resp.error);
													me.addClass('error-correlativo');
													me.data('glosa-error', resp.error);
											} else {
													me.removeClass('error-correlativo');
													me.data('glosa-error', 'resp.error');
											}
									}, 'json');
							} else {
									me.removeClass('error-correlativo');
									me.data('glosa-error', 'resp.error');
							}
					});
			});
		</script>
		<?php
	}
}
?>
<script type="text/javascript">
	function Volver(form) {
			window.opener.location = 'agregar_cliente.php?id_cliente=<?php echo $Cliente->fields['id_cliente'] ?>';
			window.close();
	}

	function MuestraPorValidacion(divID) {
			var divArea = $(divID);
			var divAreaImg = $(divID + "_img");
			var divAreaVisible = divArea.style['display'] != "none";
			divArea.style['display'] = "inline";
			divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
	}

	function Validar(form) {
			if (!form) {
					var form = $('formulario');
			}
<?php if (Conf::GetConf($Sesion, 'AtacheSecundarioSoloAsunto') == 1) { ?>
				if (form.id_encargado && !form.id_encargado.value) {
						alert('<?php echo 'Debe ingresar ' . __('Usuario encargado') ?>');
						form.id_encargado.focus();
						return false;
				}
<?php } ?>

<?php if (Conf::GetConf($Sesion, 'CodigoSecundario') && Conf::GetConf($Sesion, 'CodigoAsuntoSecundarioCorrelativo')) { ?>
				if (jQuery('#codigo_asunto_secundario').hasClass('error-correlativo')) {
						alert(jQuery('#codigo_asunto_secundario').data('glosa-error'));
						jQuery('#codigo_asunto_secundario').focus();
						return false;
				}
<?php } ?>

			if (!form.glosa_asunto.value) {
					alert("<?php echo __('Por favor ingrese un título para el') . ' ' . __('asunto'); ?>");
					form.glosa_asunto.focus();
					return false;
			}

<?php if (Conf::GetConf($Sesion, 'ValidacionesCliente')) { ?>
				if (!form.id_area_proyecto.value) {
						alert("Debe ingresar el área del <?php echo __('asunto'); ?>");
						form.id_area_proyecto.focus();
						return false;
				}
<?php } ?>

<?php
if (Conf::GetConf($Sesion, 'TodoMayuscula')) {
	echo "form.glosa_asunto.value=form.glosa_asunto.value.toUpperCase();";
}
?>

<?php if (Conf::GetConf($Sesion, 'CodigoSecundario')) { ?>
				if (!form.codigo_cliente_secundario.value) {
						alert("Debe ingresar un cliente");
						form.codigo_cliente_secundario.focus();
						return false;
				}
				if (!form.codigo_asunto_secundario.value) {
						alert("<?php echo __('Debe ingresar el código secundario del asunto') ?>");
						form.codigo_asunto_secundario.focus();
						return false;
				}
<?php } else { ?>
				if (!form.codigo_cliente.value) {
						alert("Debe ingresar un cliente");
						form.codigo_cliente.focus();
						return false;
				}
<?php } ?>

<?php echo $contractValidation->getClientValidationsScripts(); ?>

			jQuery(form).submit();
			return true;
	}

	function InfoCobro() {
			cliente = jQuery('#codigo_cliente').val();
			jQuery.get('ajax.php', {accion: 'info_cobro', codigo_cliente: cliente}, function (response) {
					if (response.indexOf('|') != -1 && response.indexOf('VACIO') != -1) {
							alert(response);
					} else if (response.indexOf('|') != -1) {
							arreglo = response.split('|');
					}
			}, 'text');
	}

	function CheckCodigo() {
			codigo_asunto = jQuery('#codigo_asunto');
			asunto = codigo_asunto.val();
			jQuery.get('ajax.php', {accion: 'check_codigo_asunto', codigo_asunto: asunto}, function (response) {
					if (response.indexOf('OK') == -1 && response.indexOf('NO') == -1) {
							alert(response);
					} else {
							if (response.indexOf('NO') != -1) {
									alert("<?php echo __('El código ingresado ya se encuentra asignado a otro asunto. Por favor ingrese uno nuevo') ?>");
									codigo_asunto.val('');
									codigo_asunto.focus();
							}
					}
			}, 'text');
	}

	function HideMonto() {
			div = document.getElementById("div_monto");
			div.style.display = "none";
	}

	function ShowMonto() {
			div = document.getElementById("div_monto");
			div.style.display = "block";
	}

	function MostrarMonto() {
			fc1 = document.getElementById("fc1");

			if (fc1.checked) {
					HideMonto();
			} else {
					ShowMonto();
			}
	}

	function MostrarFormaCobro() {
			cobro_independiente = document.getElementById("cobro_independiente");

			if (cobro_independiente.checked) {
					ShowFormaCobro();
					MostrarMonto();
			} else {
					HideFormaCobro();
					HideMonto();
			}
	}

	function HideFormaCobro() {
			div = document.getElementById("div_cobro");
			div.style.display = "none";
	}

	function ShowFormaCobro() {
			div = document.getElementById("div_cobro");
			div.style.display = "block";
	}

	function Mostrar(form) {
			alert(form.mensual.value);
	}

	function Contratos(codigo, id_contrato) {
			jQuery.get('ajax.php', {accion: 'check_codigo_asunto', codigo_asunto: asunto}, function (response) {
					jQuery('#div_contrato').html(response);
			}, 'text');
	}

	function SetearLetraCodigoSecundario() {
			var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
			$('glosa_codigo_cliente_secundario').innerHTML = '&nbsp;&nbsp;' + codigo_cliente_secundario + '-';
	}
</script>

<form name="formulario" id="formulario" method="post">
	<input type="hidden" name="opcion" value="guardar" />
	<input type="hidden" name="opc_copiar" value="" />
	<input type="hidden" name="id_asunto" id="id_asunto" value="<?php echo $Asunto->fields['id_asunto'] ?>" />
	<input type="hidden" name="desde" id="desde" value="agregar_asunto" />

	<table width="90%">
		<tr>
			<td align="center">
				<fieldset class="border_plomo tb_base">
				<legend><?php echo __('Datos generales') ?></legend>
					<table>
						<tr>
							<td align="right"><?php echo __('Código'); ?></td>
							<td align="left">
								<input 	id="codigo_asunto"
												name="codigo_asunto" <?php echo !$CodigoClienteAsuntoModificable ? 'readonly="readonly"' : ''; ?>
												size="10"
												maxlength="10"
												value="<?php echo $Asunto->fields['codigo_asunto'] ?>"
												onchange="this.value = this.value.toUpperCase(); <?php echo !$Asunto->Loaded() ? 'CheckCodigo();' : ''; ?>"/>
								&nbsp;&nbsp;&nbsp;
								<?php if (Conf::GetConf($Sesion, 'CodigoSecundario')) { ?>
									<?php echo __('Código secundario'); ?>
									<div id="glosa_codigo_cliente_secundario" style="width: 50px; display: inline;">&nbsp;&nbsp;<?php echo !empty($Cliente->fields['codigo_cliente_secundario']) ? "{$Cliente->fields['codigo_cliente_secundario']}-" : ''; ?></div>
									<input 	id="codigo_asunto_secundario"
													name="codigo_asunto_secundario"
													size="15"
													maxlength="20"
													value="<?php echo array_pop(explode('-', $Asunto->fields['codigo_asunto_secundario'])); ?>"
													onchange='this.value=this.value.toUpperCase();'
													style='text-transform: uppercase;'/>
									<span style="color:#FF0000; font-size:10px">*</span>
								<?php } ?>
							</td>
						</tr>
						<?php if (Conf::GetConf($Sesion, 'ExportacionLedes')) { ?>
							<tr>
								<td align="right" title="<?php echo __('Código con el que el cliente identifica internamente el asunto. Es obligatorio si se desea generar un archivo en formato LEDES'); ?>">
								<?php echo __('Código de homologación'); ?>
								</td>
								<td align="left">
									<input name="codigo_homologacion" size="45" value="<?php echo $Asunto->fields['codigo_homologacion']; ?>" />
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td align="right"><?php echo __('Título') ?></td>
							<td align="left">
								<input name="glosa_asunto" size=45 value="<?php echo $Asunto->fields['glosa_asunto'] ?>" />
								<span style="color:#FF0000; font-size:10px">*</span>
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Cliente') ?></td>
							<td align="left">
								<?php
								if (!$Asunto->Loaded()) {
									if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
										echo InputId::Imprimir($Sesion, 'cliente', 'codigo_cliente_secundario', 'glosa_cliente', 'codigo_cliente_secundario', $Cliente->fields['codigo_cliente_secundario'], ' ', 'SetearLetraCodigoSecundario(); CambioEncargadoSegunCliente(this.value); CambioDatosFacturacion(this.value);');
									} else {
										echo InputId::Imprimir($Sesion, 'cliente', 'codigo_cliente', 'glosa_cliente', 'codigo_cliente', $Asunto->fields['codigo_cliente'] ? $Asunto->fields['codigo_cliente'] : $Cliente->fields['codigo_cliente'], ' ', 'CambioEncargadoSegunCliente(this.value); CambioDatosFacturacion(this.value);');
									}
								} else {
									if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
										$input_cliente = InputId::Imprimir($Sesion, 'cliente', 'codigo_cliente_secundario', 'glosa_cliente', 'nuevo_codigo_cliente_secundario', $Cliente->fields['codigo_cliente_secundario'], ' class="nuevo_codigo_cliente secundario" ', 'SetearLetraCodigoSecundario(); CambioEncargadoSegunCliente(this.value); CambioDatosFacturacion(this.value);', 300);
										$_codigo_cliente = $Cliente->fields['codigo_cliente_secundario'];
										$_name = 'codigo_cliente_secundario';
									} else {
										$input_cliente = InputId::Imprimir($Sesion, 'cliente', 'codigo_cliente', 'glosa_cliente', 'nuevo_codigo_cliente', $Asunto->fields['codigo_cliente'] ? $Asunto->fields['codigo_cliente'] : $Cliente->fields['codigo_cliente'], ' class="nuevo_codigo_cliente" ', 'CambioEncargadoSegunCliente(this.value); CambioDatosFacturacion(this.value);', 300);
										$_codigo_cliente = ($Asunto->fields['codigo_cliente'] ? $Asunto->fields['codigo_cliente'] : $Cliente->fields['codigo_cliente']);
										$_name = 'codigo_cliente';
									}
									echo '<input type="text" id="campo_' . $_name . '" size="15" value="' . $_codigo_cliente . '" readonly="readonly">';
									echo '<input type="text" id="glosa_' . $_name . '" name="glosa_' . $_name . '" size="45" value="' . $Cliente->fields['glosa_cliente'] . '" readonly="readonly">';
									echo '<input type="hidden" id="' . $_name . '" name="' . $_name . '" value="' . $_codigo_cliente . '">';
								}
								if ($Asunto->Loaded()) { ?>
									<a href='#' id='change_client'><img src='//static.thetimebilling.com/images/editar_on.gif' border='0' title='Cambiar Cliente'></a>
									<span style="color:#FF0000; font-size:10px">*</span>
									<div id="nuevo_codigo" class="hidden" style="padding: 5px 0px 5px 5px; background-color: yellowgreen">Asociar a cliente</br>
										<?php echo $input_cliente; ?>
									</div>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Idioma') ?></td>
							<td align="left">
								<?php echo Html::SelectQuery($Sesion, "SELECT * FROM prm_idioma", 'id_idioma', $Asunto->fields['id_idioma'] ? $Asunto->fields['id_idioma'] : $id_idioma_default); ?>&nbsp;&nbsp;
								<?php echo __('Categoría de asunto') ?>
								<?php echo Html::SelectArrayDecente($PrmTipoProyecto->Listar('ORDER BY orden, glosa_tipo_proyecto ASC'), 'id_tipo_asunto', $Asunto->fields['id_tipo_asunto']); ?>
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Área') . ' ' . __('asunto') ?></td>
							<td align="left">
								<?php echo $SelectHelper->ajax_select(
																		'id_area_proyecto',
																		$Asunto->fields['id_area_proyecto'] ? $Asunto->fields['id_area_proyecto'] : $id_area_proyecto,
																		array(
																			'class' => 'span3',
																			'style' => 'display:inline'
																		),
																		array(
																			'source' => 'ajax/ajax_prm.php?prm=AreaProyecto&single_class=1&fields=orden,requiere_desglose&order_by=orden,glosa&order_by_type=asc',
																			'onChange' => 'var element = selected_id_area_proyecto;
																										jQuery("#id_desglose_area_container").hide();
																										jQuery("#desglose_area").hide()
																										if (element && element.requiere_desglose == "1") {
																											jQuery("#id_desglose_area_container").show();
																											FormSelectHelper.reload_id_desglose_area();
																										}'
																					)); ?>
								<?php if (Conf::GetConf($Sesion, 'ValidacionesCliente')) { ?>
									<span style="color:#FF0000; font-size:10px">*</span>
								<?php } ?>
								<?php echo $SelectHelper->checkboxes(
																		'id_desglose_area',
																		array(),
																		$Asunto->getAreaDetails(),
																		array(
																			'class' => 'span6',
																			'style' => 'display:inline'
																		),
																		array(
																			'autoload' => false,
																			'source' => 'ajax/ajax_prm.php?prm=AreaProyectoDesglose&single_class=1&fields=glosa,id_area_proyecto,requiere_desglose',
																			'onSource' => 'source = source + "&q=id_area_proyecto:" + jQuery("#id_area_proyecto").val();',
																			'onChange' => 'var element = selected_id_desglose_area;
																										if (element && element.requiere_desglose == "1") {
																											if (checked) {
																												jQuery("#desglose_area").show();
																											} else {
																												jQuery("#desglose_area").val("").hide();
																											}
																										}'
																		)); ?>
							<?php echo $Form->input('desglose_area', $Asunto->fields['desglose_area'], array('placeholder' => 'Desglose', 'style' => 'display:none', 'size' => '50', 'label' => false, 'id' => 'desglose_area')); ?>
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Descripción') ?></td>
							<td align="left"><textarea name="descripcion_asunto" cols="50"><?php echo $Asunto->fields['descripcion_asunto'] ?></textarea></td>
						</tr>
						<?php
						$prmGiro = new PrmGiro($Sesion);
						$giros = $prmGiro->Listar();
						if (count($giros) > 0) { ?>
						<tr>
							<td align="right"><?php echo __('Giro') ?></td>
							<td align="left">
								<?php
								echo $SelectHelper->checkboxes(
																'id_asunto_giro',
																array(),
																$Asunto->getEconomicActivities(),
																array(
																	'class' => 'span6',
																	'style' => 'display:inline'
																),
																array(
																'autoload' => true,
																'source' => 'ajax/ajax_prm.php?prm=Giro&fields=glosa,requiere_desglose',
																'onChange' => 'var element = selected_id_asunto_giro;
																							if (element && element.requiere_desglose == "1") {
																								if (checked) {
																									jQuery("#giro").show();
																								} else {
																									jQuery("#giro").val("").hide();
																								}
																							}'
																)); ?>
								<?php echo $Form->input('giro', $Asunto->fields['giro'], array('placeholder' => __('Giro'), 'style' => 'display:none', 'size' => '50', 'label' => false, 'id' => 'giro')); ?>
							</td>
						</tr>
						<?php } ?>
						<tr>
							<td align="right"><?php echo __('Usuario responsable'); ?></td>
							<td align="left"><!-- Nuevo Select -->
							<?php echo $Form->select('id_encargado', $Sesion->usuario->ListarActivos('', TRUE), $Asunto->fields['id_encargado'], array('empty' => __('Seleccione'), 'style' => 'width: 200px')); ?>
							<?php
							if (isset($encargado_obligatorio) && $encargado_obligatorio) {
								echo $obligatorio;
							} ?>
							</td>
						</tr>
						<?php if (Conf::GetConf($Sesion, 'AsuntosEncargado2')) { ?>
						<tr>
							<td align="right"><?php echo __('Encargado 2'); ?></td>
							<td align="left">'<!-- Nuevo Select -->
								<?php echo $Form->select('id_encargado2', $Sesion->usuario->ListarActivos('', TRUE), $Asunto->fields['id_encargado2'], array('empty' => __('Seleccione'), 'style' => 'width: 200px')); ?>
							</td>
						</tr>
						<?php } ?>
						<tr>
							<td align="right"><?php echo __('Contraparte') ?></td>
							<td align="left">
								<input name="contraparte" size="50" value="<?php echo $Asunto->fields['contraparte'] ?>" />
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Cotizado conjuntamente con') ?></td>
							<td align="left">
								<input name="cotizado_con" size="50" value="<?php echo $Asunto->fields['cotizado_con'] ?>" />
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Contacto solicitante') ?></td>
							<td align="left">
								<input name="asunto_contacto" size="30" value="<?php echo $Asunto->fields['contacto'] ?>" />
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Teléfono Contacto') ?></td>
							<td align="left">
								<input name="fono_contacto" value="<?php echo $Asunto->fields['fono_contacto'] ?>" />
								&nbsp;&nbsp;&nbsp;
								<?php echo __('E-mail contacto') ?>
								<input name="email_contacto" value="<?php echo $Asunto->fields['email_contacto'] ?>" />
							</td>
						</tr>
						<tr>
							<td align="right"><label for="activo"><?php echo __('Activo') ?></label></td>
							<td align="left">
								<input type="checkbox" name="activo" id="activo" value="1" <?php echo $Asunto->fields['activo'] == 1 ? "checked" : "" ?> <?php echo!$Asunto->Loaded() ? 'checked' : '' ?> />
								&nbsp;&nbsp;&nbsp;
								<label for="cobrable"><?php echo __('Cobrable') ?></label>
								<input  type="checkbox" name="cobrable" id="cobrable" value="1" <?php echo $Asunto->fields['cobrable'] == 1 ? "checked" : "" ?><?php echo!$Asunto->Loaded() ? 'checked' : '' ?>  />
								&nbsp;&nbsp;&nbsp;
								<label for="actividades_obligatorias"><?php echo __('Actividades obligatorias') ?></label>
								<input type="checkbox" id="actividades_obligatorias" name="actividades_obligatorias" value="1" <?php echo $Asunto->fields['actividades_obligatorias'] == 1 ? "checked" : "" ?> />
							</td>
						</tr>
					</table>
				</fieldset>

				<br/>
				<?php
				if ($Asunto->fields['id_contrato'] && ($Asunto->fields['id_contrato'] != $Cliente->fields['id_contrato']) && ($Asunto->fields['codigo_cliente'] == $Cliente->fields['codigo_cliente'])) {
					$checked = true;
				} else {
					$checked = false;
				}

				$hide_areas = false;
				if ($Sesion->usuario->Es('SASU')) {
					$hide_areas = true;
				} else {
					if ((!isset($codigo_cliente) || $codigo_cliente == '') && $Asunto->Loaded()) {
						$codigo_cliente = $Asunto->fields['codigo_cliente'];
					}
				} ?>

				<table width="100%" cellspacing="0" cellpadding="0">
					<tr>
						<td id="td_cobro_independiente" <?php echo $hide_areas ? 'style="display:none;"' : ''; ?>>
							<?php echo $Form->checkbox('cobro_independiente', 1, $checked, array('label' => __('Se cobrará de forma independiente'), 'id' => 'cobro_independiente')); ?>
						</td>
						<td id="tbl_copiar_datos" style="display:<?php echo !empty($checked) ? 'inline' : 'none'; ?>;">&nbsp;</td>
					</tr>
				</table>

				<br/>
				<div id="tbl_contrato">
					<?php
					if (!$Sesion->usuario->Es('SASU')) {
						$contrato_nuevo = $Asunto->fields['id_contrato_indep'] == 0;
						$cliente = &$Cliente;
						require_once Conf::ServerDir() . '/interfaces/agregar_contrato.php';
					}
					?>
				</div>

				<br/>
				<fieldset class="border_plomo tb_base">
					<legend><?php echo __('Alertas') . ' ' . __('Asunto') ?></legend>
					<p>&nbsp;<?php echo __('El sistema enviará un email de alerta al encargado si se superan estos límites:') ?></p>
					<table>
						<tr>
							<td align=right>
								<input name="asunto_limite_hh" value="<?php echo $Asunto->fields['limite_hh'] ? $Asunto->fields['limite_hh'] : '0' ?>" title="<?php echo __('Total de Horas') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Total de Horas') ?>"><?php echo __('Límite de horas') ?></span>
							</td>
							<td align=right>
								<input name="asunto_limite_monto" value="<?php echo $Asunto->fields['limite_monto'] ? $Asunto->fields['limite_monto'] : '0' ?>" title="<?php echo __('Valor Total según Tarifa Hora Hombre') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Valor Total según Tarifa Hora Hombre') ?>"><?php echo __('Límite de monto') ?></span>
							</td>
						</tr>
						<tr>
							<td align=right>
								<input name="asunto_alerta_hh" value="<?php echo $Asunto->fields['alerta_hh'] ? $Asunto->fields['alerta_hh'] : '0' ?>" title="<?php echo __('Total de Horas en trabajos no cobrados') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Total de Horas en trabajos no cobrados') ?>"><?php echo __('horas no cobradas') ?></span>
							</td>
							<td align=right>
								<input name="asunto_alerta_monto" value="<?php echo $Asunto->fields['alerta_monto'] ? $Asunto->fields['alerta_monto'] : '0' ?>" title="<?php echo __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>"><?php echo __('monto según horas no cobradas') ?></span>
							</td>
						</tr>
					</table>
				</fieldset>
				<br>

				<!-- GUARDAR -->
				<fieldset class="border_plomo tb_base">
				<legend><?php echo __('Guardar datos') ?></legend>
					<table>
						<tr>
							<td colspan=6 align="center">
							<?php
							if (!$Sesion->usuario->Es('SASU') && Conf::GetConf($Sesion, 'RevisarTarifas')) {
								$funcion_validar = "return RevisarTarifas('id_tarifa', 'id_moneda', jQuery('#formulario').get(0), false);";
							} else {
								$funcion_validar = "return Validar(jQuery('#formulario')[0]);";
							}
							echo $Form->button(__('Guardar'), array('onclick' => $funcion_validar));
							echo $Form->script();
							?>
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">
var form = $('formulario');

jQuery('document').ready(function () {
	jQuery('#codigo_cliente, #codigo_cliente, #codigo_cliente, #codigo_cliente').change(function () {
		CambioEncargadoSegunCliente(jQuery(this).val());
	});

	jQuery('#change_client').click(function() {
		var $ = jQuery;
		var input_nuevo_codigo = $('input.nuevo_codigo_cliente');
		var select_nuevo_codigo = $('select.nuevo_codigo_cliente');
		var secundario = input_nuevo_codigo.hasClass('secundario') ? '_secundario' : '';
		var codigo_cliente = $('#campo_codigo_cliente' + secundario).val();
		var glosa_cliente = $('#glosa_codigo_cliente' + secundario).val();
		$('#nuevo_codigo').toggleClass('hidden');
		input_nuevo_codigo.val(codigo_cliente);
		select_nuevo_codigo.val(codigo_cliente);
	});

	jQuery(document).on("change", "#cobro_independiente", function() {
		if (jQuery(this).is(":checked")) {
			jQuery("#tbl_contrato").show();
			jQuery("#tbl_copiar_datos").show();
		} else {
			jQuery("#tbl_contrato").hide();
			jQuery("#tbl_copiar_datos").hide();
		};
	});

	jQuery("#cobro_independiente").trigger("change");
});

function CambioEncargadoSegunCliente(idcliente) {
	var CopiarEncargadoAlAsunto = <?php echo (Conf::GetConf($Sesion, "CopiarEncargadoAlAsunto") ? '1' : '0'); ?>;
	var UsuarioSecundario = <?php echo (Conf::GetConf($Sesion, 'EncargadoSecundario') ? '1' : '0' ); ?>;
	var ObligatorioEncargadoSecundarioAsunto = <?php echo (Conf::GetConf($Sesion, 'ObligatorioEncargadoSecundarioAsunto') ? '1' : '0' ); ?>;
	jQuery('#id_usuario_secundario').removeAttr('disabled');
	jQuery('#id_usuario_responsable').removeAttr('disabled');
	jQuery.post('../ajax.php', {accion: 'busca_encargado_por_cliente', codigobuscado: idcliente}, function (data) {
		var ladata = data.split('|');
		jQuery('#id_usuario_responsable').attr({'disabled': ''}).val(ladata[0]);
		if (ladata[1] && jQuery('#id_usuario_secundario option[value=' + ladata[1] + ']').length > 0) {
			if (UsuarioSecundario) {
				jQuery('#id_usuario_secundario').attr({'disabled': ''}).val(ladata[1]);
			}
		} else {
			if (ladata[2]) {
				jQuery('#id_usuario_secundario').append('<option value="' + ladata[1] + '" selected="selected">' + ladata[2] + '</option>').attr({'disabled': ''}).val(ladata[1]);
			}
		}

		jQuery('#id_usuario_responsable').removeAttr('disabled');
		if (CopiarEncargadoAlAsunto) {
			jQuery('#id_usuario_responsable').attr({'disabled': 'disabled'});
			if (UsuarioSecundario) {
				jQuery('#id_usuario_secundario').attr({'disabled': 'disabled'});
			}
		} else if (ObligatorioEncargadoSecundarioAsunto) {
			if (UsuarioSecundario) {
				jQuery('#id_usuario_secundario').removeAttr('disabled');
			}
		}

		jQuery('#id_usuario_responsable, #id_usuario_secundario').removeClass('loadingbar');
	});
	jQuery('#id_usuario_responsable, #id_usuario_secundario').addClass('loadingbar');
}

function CambioDatosFacturacion(id_cliente) {
	var url = root_dir + '/app/interfaces/ajax.php';

	jQuery.get(url, {accion: 'cargar_datos_contrato', codigo_cliente: id_cliente}, function (response) {
		if (response.indexOf('|') != -1) {
				response = response.split('\\n');
				response = response[0];
				var campos = response.split('~');

				if (response.indexOf('VACIO') != -1) {
					//dejamos los campos en blanco.
				} else {
					for (i = 0; i < campos.length; i++) {
						valores = campos[i].split('|');
						jQuery('[name="factura_razon_social"]').val(valores[0] != '' ? valores[0] : '');
						jQuery('[name="factura_direccion"]').val(valores[1] != '' ? valores[1] : '');
						jQuery('[name="factura_rut"]').val(valores[2] != '' ? valores[2] : '');
						jQuery('[name="factura_comuna"]').val(valores[3] != '' ? valores[3] : '');
						jQuery('[name="factura_ciudad"]').val(valores[4] != '' ? valores[4] : '');
						jQuery('[name="factura_giro"]').val(valores[6] != '' ? valores[6] : '');
						jQuery('[name="factura_codigopostal"]').val(valores[7] != '' ? valores[7] : '');
						jQuery('[name="id_pais"]').val(valores[8] != '' ? valores[8] : '');
						jQuery('[name="cod_factura_telefono"]').val(valores[9] != '' ? valores[9] : '');
						jQuery('[name="factura_telefono"]').val(valores[10] != '' ? valores[10] : '');
						jQuery('[name="glosa_contrato"]').val(valores[11] != '' ? valores[11] : '');
					}
				}
		} else {
			if (response.indexOf('head') != -1) {
				alert('Sesión Caducada');
				top.location.href = '<?php echo Conf::Host(); ?>';
			} else {
				alert(response);
			}
		}
	}, 'text');
}
</script>

<?php echo InputId::Javascript($Sesion) ?>

<?php
$Pagina->PrintBottom($popup, false, true);

function EnviarEmail($Asunto) {

	global $Sesion;

	$glosa = $Asunto->fields['glosa_asunto'];
	$codigo = $Asunto->fields['codigo_asunto'];
	$cod_cliente = $Asunto->fields['codigo_cliente'];
	$desc = $Asunto->fields['descripcion_asunto'];

	$query = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente='$cod_cliente'";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	list($Cliente) = mysql_fetch_array($resp);

	$MailSistema = Conf::GetConf($Sesion, 'MailSistema');

	if (Conf::GetConf($Sesion, 'MailAsuntoNuevoATodosLosAdministradores')) {
		$query = "SELECT usuario.nombre,usuario.email FROM usuario LEFT JOIN usuario_permiso USING( id_usuario ) WHERE usuario.activo=1 AND usuario_permiso.codigo_permiso='ADM'";
		$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
		while (list($nombre, $email) = mysql_fetch_array($resp)) {
			$from = Conf::AppName();
			$headers = "From: Time & Billing <" . $MailSistema . ">" . "\r\n" .
							"Reply-To: " . $MailSistema . "\r\n" .
							'X-Mailer: PHP/' . phpversion();
			$mensaje = "Estimado(a) $nombre,\nse ha agregado el asunto $glosa ($codigo) al cliente $Cliente en el sistema de Time & Billing.\nDescripción: $desc\nRecuerda refrescar las listas de la aplicación local Time & Billing.";
			Utiles::Insertar($Sesion, "Nuevo asunto - " . Conf::AppName(), $mensaje, $email, $nombre);
		}
	} else {
		$from = Conf::AppName();
		$headers = "From: Time & Billing <" . $MailSistema . ">" . "\r\n" .
						"Reply-To: " . $MailSistema . "\r\n" .
						'X-Mailer: PHP/' . phpversion();
		$mensaje = "Estimado(a) Admin,\nse ha agregado el asunto $glosa ($codigo) al cliente $Cliente en el sistema de Time & Billing.\nDescripción: $desc\nRecuerda refrescar las listas de la aplicación local Time & Billing.";
		$email = Conf::GetConf($Sesion, 'MailAdmin');
		Utiles::Insertar($Sesion, "Nuevo asunto - " . Conf::AppName(), $mensaje, $email, $nombre);
	}
}
