<?php
require_once dirname(__FILE__) . '/../conf.php';
$Html = new \TTB\Html;
//La funcionalidad contenida en esta pagina puede invocarse desde integracion_contabilidad3.php (SOLO GUARDAR).
//(desde_webservice será true). Esa pagina emula el POST, es importante revisar que los cambios realizados en la FORM
//se repliquen en el ingreso de datos via webservice.

if ($desde_webservice && UtilesApp::VerificarPasswordWebServices($usuario, $password)) {
	$sesion = new Sesion();
	$factura = new Factura($sesion);
} else { //ELSE (no es WEBSERVICE)
	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	$DocumentoLegalNumero = new DocumentoLegalNumero($sesion);
	$factura = new Factura($sesion);
	$prm_codigo = new PrmCodigo($sesion);
	$prm_plugin = new PrmPlugin($sesion);

	if (!empty($id_factura)) {
		$factura->Load($id_factura);
		if (empty($codigo_cliente)) {
			$codigo_cliente = $factura->fields['codigo_cliente'];
		}
		if (empty($id_cobro)) {
			$id_cobro = $factura->fields['id_cobro'];
		}
	}

	if ($id_cobro > 0) {
		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);
		$contrato = new Contrato($sesion);
		if (empty($id_contrato)) {
			$id_contrato = $cobro->fields['id_contrato'];
		}
		$contrato->Load($id_contrato, array('glosa_contrato', 'rut', 'factura_ciudad', 'factura_comuna', 'factura_codigopostal', 'factura_direccion', 'factura_giro', 'factura_razon_social', 'region_cliente', 'id_estudio', 'email_contacto', 'id_usuario_responsable'));
	}

	if ($cobro->Loaded() && empty($codigo_cliente)) {
		$codigo_cliente = $cobro->fields['codigo_cliente'];
	}

	if (!empty($codigo_cliente) && empty($codigo_cliente_secundario)) {
		$Cliente = new Cliente($sesion);
		$codigo_cliente_secundario = $Cliente->CodigoACodigoSecundario($codigo_cliente);
	}

	if ($factura->loaded() && !$id_cobro) {
		$id_cobro = $factura->fields['id_cobro'];
	}

	if ($factura->loaded()) {
		$id_documento_legal = $factura->fields['id_documento_legal'];
	}

	$query = "SELECT id_documento_legal, glosa, codigo FROM prm_documento_legal WHERE id_documento_legal = '$id_documento_legal'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($id_documento_legal, $tipo_documento_legal, $codigo_tipo_doc) = mysql_fetch_array($resp);

	if (!$tipo_documento_legal) {
		$pagina->FatalError('Error al cargar el tipo de Documento Legal');
	}

	if ($opc == 'generar_factura') {
		// POR HACER
		// mejorar
		if ($id_factura) {
			UtilesApp::generarFacturaPDF($id_factura, $sesion);
		} else {
			echo "Error";
		}
		exit;
	}

	$opc_inicial = $opcion;

	if ($opcion == "restaurar") {
		$opc_inicial = $opcion;
		$opcion = "guardar";
	}

	if ($opcion == "anular") {
		$data = array('Factura' => $factura);
		$Slim->applyHook('hook_anula_factura_electronica', &$data);
		$error = $data['Error'];
		if (!is_null($error)) {
			$pagina->AddError($error['Message'] ? $error['Message'] : __($error['Code']));
			$requiere_refrescar = "window.opener.Refrescar();";
		} else {
			$factura->Edit('estado', 'ANULADA');
			$factura->Edit('id_estado', $id_estado ? $id_estado : "1");
			$factura->Edit('fecha_anulacion', date('Y-m-d H:i:s'));
			$factura->Edit('anulado', 1);
			if ($factura->Escribir()) {
				$pagina->AddInfo(__('Documento Tributario') . ' ' . __('anulado con éxito'));
				$requiere_refrescar = "window.opener.Refrescar();";
			}
		}
	}
}

//FIN DE ELSE (No es WEBSERVICE)

if ($opcion == "guardar") {

	$guardar_datos = true;

	//El webservice ignora todo llamado a $pagina
	if ($desde_webservice) {
		$errores = array();
		if (!is_numeric($monto_honorarios_legales) || !is_numeric($monto_gastos_con_iva) || !is_numeric($monto_gastos_sin_iva)) {
			$errores[] = 'error';
		}
	} else {
		if (empty($cliente)) {
			$pagina->AddError(__('Debe ingresar la razon social del cliente.'));
		}
		if (Conf::GetConf($sesion, 'NuevoModuloFactura')) {
			if (!is_numeric($monto_honorarios_legales)) {
				$pagina->AddError(__('Debe ingresar un monto válido para los honorarios. (' . $monto_honorarios_legales . ')'));
			}
			if (!is_numeric($monto_gastos_con_iva)) {
				$pagina->AddError(__('Debe ingresar un monto válido para los gastos c/ IVA. (' . $monto_gastos_con_iva . ')'));
			}
			if (Conf::GetConf($sesion, 'UsarGastosConSinImpuesto') && !is_numeric($monto_gastos_sin_iva)) {
				$pagina->AddError(__('Debe ingresar un monto válido para los gastos s/ IVA. (' . $monto_gastos_sin_iva . ')'));
			}
		}
		($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_validar_factura') : false;
		$errores = $pagina->GetErrors();
	}

	if (!empty($errores)) {
		$guardar_datos = false;
	}

	if ($guardar_datos) {
		//chequear
		$mensaje_accion = 'guardado';
		$factura->Edit('subtotal', $monto_neto);
		$factura->Edit('porcentaje_impuesto', $porcentaje_impuesto);

		if ($comprobante_erp) {
			$factura->Edit('comprobante_erp', $comprobante_erp);
		}

		$factura->Edit('condicion_pago', '' . $condicion_pago);
		$factura->Edit('fecha_vencimiento', $fecha_vencimiento_pago_input ? Utiles::fecha2sql($fecha_vencimiento_pago_input) : "");
		$factura->Edit('iva', $iva);
		$factura->Edit('id_estudio', $id_estudio);
		$factura->Edit('total', '' . ($monto_neto + $iva));
		$factura->Edit("id_factura_padre", $id_factura_padre ? $id_factura_padre : NULL);
		$factura->Edit("fecha", Utiles::fecha2sql($fecha));
		$factura->Edit("cliente", $cliente ? addslashes($cliente) : "");
		$factura->Edit("RUT_cliente", $RUT_cliente ? $RUT_cliente : "");
		$factura->Edit("direccion_cliente", $direccion_cliente ? addslashes($direccion_cliente) : "");

		$factura->Edit('id_documento_referencia', $id_documento_referencia);
		$factura->Edit('folio_documento_referencia', $folio_documento_referencia);
		$factura->Edit("fecha_documento_referencia", Utiles::fecha2sql($fecha_documento_referencia));

		$factura->Edit("comuna_cliente", $comuna_cliente ? addslashes($comuna_cliente) : "");
		$factura->Edit("factura_codigopostal", $factura_codigopostal ? $factura_codigopostal : "");
		$factura->Edit("dte_metodo_pago", $dte_metodo_pago ? $dte_metodo_pago : "");
		$factura->Edit("dte_metodo_pago_cta", $dte_metodo_pago_cta ? $dte_metodo_pago_cta : "");
		$factura->Edit("dte_codigo_referencia", $dte_codigo_referencia ? $dte_codigo_referencia : "");
		$factura->Edit("dte_razon_referencia", $dte_razon_referencia ? $dte_razon_referencia : "");
		if (!is_null($dte_id_pais) && !empty($dte_id_pais)) {
			$factura->Edit("dte_id_pais", $dte_id_pais ? $dte_id_pais : "");
		}
		$factura->Edit('dte_comentario', $dte_comentario ? $dte_comentario : NULL);

		$factura->Edit("ciudad_cliente", $ciudad_cliente ? addslashes($ciudad_cliente) : "");
		if (Conf::GetConf($sesion, 'RegionCliente')) {
			$factura->Edit("factura_region", $factura_region ? addslashes($factura_region) : "");
		}
		$factura->Edit("giro_cliente", $giro_cliente ? addslashes($giro_cliente) : "");
		$factura->Edit("codigo_cliente", $codigo_cliente ? $codigo_cliente : "");
		$factura->Edit("id_cobro", $id_cobro ? $id_cobro : NULL);
		$factura->Edit("id_documento_legal", $id_documento_legal ? $id_documento_legal : 1);
		$factura->Edit('serie_documento_legal', $serie);
		$factura->Edit("numero", $numero ? $numero : "1");
		$factura->Edit("id_estado", $id_estado ? $id_estado : "1");
		$factura->Edit("id_moneda", $id_moneda_factura ? $id_moneda_factura : "1");
		$factura->Edit('fecha_anulacion', NULL);
		if ($id_estado == '5') {
			$factura->Edit('estado', 'ANULADA');
			$factura->Edit('anulado', 1);
			$factura->Edit('fecha_anulacion', date('Y-m-d H:i:s'));
			$mensaje_accion = 'anulado';
		} else if (!empty($factura->fields['anulado'])) {
			$factura->Edit('estado', 'ABIERTA');
			$factura->Edit('anulado', '0');
		}

		($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_agregar_factura') : false;

		if (Conf::GetConf($sesion, 'NuevoModuloFactura')) {
			$factura->Edit("glosa", $glosa);
			$factura->Edit("descripcion", $descripcion_honorarios_legales);
			$factura->Edit("honorarios", $monto_honorarios_legales ? $monto_honorarios_legales : 0);
			$factura->Edit("subtotal", $monto_honorarios_legales ? $monto_honorarios_legales : 0);
			$factura->Edit("subtotal_sin_descuento", $monto_honorarios_legales ? $monto_honorarios_legales : 0);
			$factura->Edit("descripcion_subtotal_gastos", $descripcion_gastos_con_iva ? $descripcion_gastos_con_iva : '');
			$factura->Edit("subtotal_gastos", $monto_gastos_con_iva ? $monto_gastos_con_iva : 0);
			$factura->Edit("descripcion_subtotal_gastos_sin_impuesto", $descripcion_gastos_sin_iva ? $descripcion_gastos_sin_iva : '');
			$factura->Edit("subtotal_gastos_sin_impuesto", $monto_gastos_sin_iva ? $monto_gastos_sin_iva : 0);
			$factura->Edit("total", $total ? $total : 0);
			$factura->Edit("iva", $iva_hidden ? $iva_hidden : 0);
		} else {
			$factura->Edit("descripcion", $descripcion);
		}

		if (!empty($contrato)) {
			$factura->Edit("id_usuario_responsable", $contrato->fields['id_usuario_responsable']);
		}

		if (Conf::GetConf($sesion, 'TipoDocumentoIdentidadFacturacion')) {
			$factura->Edit('id_tipo_documento_identidad', $tipo_documento_identidad);
		}

		$factura->Edit('letra', $letra);
		if ($letra_inicial) {
			$factura->Edit('letra', $letra_inicial);
		}

		if (empty($factura->fields['id_factura'])) {
			$generar_nuevo_numero = true;
		}

		if ($id_cobro && empty($factura->fields['id_factura'])) {
			if (!$cobro->Load($id_cobro)) {
				$cobro = null;
			}
			if ($cobro) {
				$factura->Edit('id_moneda', $cobro->fields['opc_moneda_total']);
			}
		}

		if (!$factura->ValidarDocLegal()) {
			if (empty($id_estudio)) {
				$estudios = PrmEstudio::GetEstudios($sesion);
				$id_estudio = $estudios[0]['id_estudio'];
			}

			$numero_documento_legal = $factura->ObtenerNumeroDocLegal($id_documento_legal, $serie, $id_estudio);

			if (!$desde_webservice) {
				$mensaje_validacion_documento_tributario = "El numero {$numero} del " . __('documento tributario') . ' ya fue usado';
				$mensaje_validacion_documento_tributario .= empty($factura->fields['id_factura']) ? '.' : ', pero se ha asignado uno nuevo, por favor verifique los datos y vuelva a guardar';

				$pagina->AddError($mensaje_validacion_documento_tributario);

				$factura->Edit('numero', $numero_documento_legal);
			} else {
				$resultado = array('error' => 'El número ' . $numero . ' del ' . __('documento tributario') . ' ya fue usado, vuelva a intentar con número: ' . $numero_documento_legal);
			}
		} else {
			if ($mensaje_accion == 'anulado') {
				$data_anular = array('Factura' => $factura);
				($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_anula_factura_electronica', &$data_anular) : false;
				$error_message = $data_anular['Error'];
				echo "<!-- {$error_message} -->";
				if (!is_null($error_message)) {
					$pagina->AddInfo($factura->fields["dte_estado_descripcion"] . " <br/>Para consultar el estado de su factura, puede dar clic en el ícono i (más información)");
					$factura->Load($id_factura);
				}
			}

			if ($factura->Escribir()) {
				if ($generar_nuevo_numero) {
					$factura->GuardarNumeroDocLegal($id_documento_legal, $numero, $serie, $id_estudio);
				}

				$signo = $codigo_tipo_doc == 'NC' ? 1 : -1; //es 1 o -1 si el tipo de doc suma o resta su monto a la liq
				$neteos = empty($id_factura_padre) ? null : array(array($id_factura_padre, $signo * $factura->fields['total']));

				$cta_cte_fact = new CtaCteFact($sesion);
				$mvto_guardado = $cta_cte_fact->RegistrarMvto($factura->fields['id_moneda'], $signo * ($factura->fields['total'] - $factura->fields['iva']), $signo * $factura->fields['iva'], $signo * $factura->fields['total'], $factura->fields['fecha'], $neteos, $factura->fields['id_factura'], null, $codigo_tipo_doc, $ids_monedas_documento, $tipo_cambios_documento, !empty($factura->fields['anulado']));

				if (Conf::GetConf($sesion, 'UsarModuloProduccion')) {
					$factura->ActualizaGeneradores();
				}

				if ($mvto_guardado->fields['tipo_mvto'] != 'NC' && $mvto_guardado->fields['saldo'] == 0 && $mvto_guardado->fields['anulado'] != 1) {
					$query = "SELECT id_estado FROM prm_estado_factura WHERE codigo = 'C'";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
					list($id_estado_cobrado) = mysql_fetch_array($resp);

					$factura->Edit('id_estado', $id_estado_cobrado);
				}

				//El webservice ignora todo llamado a $pagina
				if (!$desde_webservice) {
					if ($opc_inicial != 'restaurar') {
						$pagina->AddInfo(__('Documento Tributario') . ' ' . $mensaje_accion . ' ' . __(' con éxito'));
					}
				}
				$requiere_refrescar = "window.opener.Refrescar();";



				# Esto se puede descomentar para imprimir facturas desde la edición

				if ($id_cobro) {
					$cobro->Load($id_cobro);

					if ($cobro->Loaded()) {
						$cobro->AgregarFactura($factura);

						if ($usar_adelantos && empty($factura->fields['anulado']) && $codigo_tipo_doc != 'NC') {

							if (Conf::GetConf($sesion, 'AsociarAdelantosALiquidacion')) {
								$factura->PagarUsandoAdelantos();
							} else {
								$documento = $cobro->DocumentoCobro();
								$documento->GenerarPagosDesdeAdelantos(
									$documento->fields['id_documento'],
									array($factura->fields['id_factura'] => $factura->fields['total']),
									$id_adelanto);
							}
						}

						$cobro->CambiarEstadoSegunFacturas();
					}
				}
			}
		}

		$observacion = new Observacion($sesion);
		$observacion->Edit('fecha', date('Y-m-d H:i:s'));
		$observacion->Edit('comentario', "MODIFICACIÓN FACTURA");
		$observacion->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
		$observacion->Edit('id_factura', $factura->fields['id_factura']);
		$observacion->Write();
	}
}

//Fin opcion guardar

if ($desde_webservice) {
	if ($factura->fields['id_factura']) {
		$resultado = array(
			'id_factura' => $factura->fields['id_factura'],
			'descripción' => 'El ' . __('documento tributario') . ' se ha guardado exitosamente.'
		);
	}
	return 'EXITO';
	//Si vengo del webservice, no continua.
}

// Se ingresa la anotación de modificación de factura en el historial
if (!$id_factura && $factura->loaded()) {
	$id_factura = $factura->fields['id_factura'];
}

$titulo_pagina = $txt_pagina = $id_factura ? __('Edición de ') . $tipo_documento_legal . ' #' . $factura->fields['numero'] : __('Ingreso de ') . $tipo_documento_legal;

if ($id_cobro) {
	$titulo_pagina .= ' ' . __('para Cobro') . ' #' . $id_cobro;
	$txt_pagina .= ' ' . __('para Cobro') . '&nbsp; <a href="cobros6.php?id_cobro=' . $id_cobro . '&popup=1">#' . $id_cobro . '</a>';
}

$pagina->titulo = $titulo_pagina;
$pagina->PrintTop($popup);


/* Mostrar valores por defecto */

//SIN DESGLOSE
$suma_monto = 0;
$suma_iva = 0;
$suma_total = 0;

//CON DESGLOSE
$descripcion_honorario = __(Conf::GetConf($sesion, 'FacturaDescripcionHonorarios'));

$monto_honorario = 0;
$descripcion_subtotal_gastos = __(Conf::GetConf($sesion, 'FacturaDescripcionGastosConIva'));
$monto_subtotal_gastos = 0;
$descripcion_subtotal_gastos_sin_impuesto = __(Conf::GetConf($sesion, 'FacturaDescripcionGastosSinIva'));
$monto_subtotal_gastos_sin_impuesto = 0;


//ASIGNO LOS MONTOS POR DEFECTO DE LOS DOCUMENTOS
$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $id_cobro, array(), $cobro->fields['opc_moneda_total'], true);

$opc_moneda_total = $x_resultados['opc_moneda_total'];
$id_moneda_factura = $opc_moneda_total;

if (!empty($factura->fields['id_factura'])) {
	$id_moneda_factura = $factura->fields['id_moneda'];
}

$cifras_decimales_opc_moneda_total = $x_resultados['cifras_decimales_opc_moneda_total'];
$cifras_decimales_factura_conf = Conf::GetConf($sesion, 'CantidadDecimalesTotalFactura');
if ($cifras_decimales_factura_conf != -1) {
	$cifras_decimales_opc_moneda_total = $cifras_decimales_factura_conf;
}

$subtotal_honorarios = $x_resultados['monto_honorarios'][$opc_moneda_total];
$subtotal_gastos_sin_impuestos = $x_resultados['subtotal_gastos_sin_impuesto'][$opc_moneda_total];
$subtotal_gastos = $x_resultados['subtotal_gastos'][$opc_moneda_total] - $subtotal_gastos_sin_impuestos;
$impuesto_gastos = $x_resultados['impuesto_gastos'][$opc_moneda_total];
$impuesto = $x_resultados['impuesto'][$opc_moneda_total];

//SIN DESGLOSE
$suma_monto = $subtotal_honorarios + $subtotal_gastos;
$suma_iva = $impuesto_gastos + $impuesto;
$suma_total = $subtotal_honorarios + $subtotal_gastos + $impuesto_gastos + $impuesto;

//CON DESGLOSE
$cobro_ = new Cobro($sesion);
$descripcion_honorario = __(Conf::GetConf($sesion, 'FacturaDescripcionHonorarios'));

if (empty($glosa) && $contrato) {
	$glosa = $contrato->fields['glosa_contrato'];
}

if ($descripcion_honorario == '') {
	$descripcion_honorario = $contrato->fields['glosa_contrato'];
}

if (Conf::GetConf($sesion, 'DescripcionFacturaConAsuntos')) {
	$descripcion_honorario .= "\n" . implode(', ', $cobro_->AsuntosNombreCodigo($id_cobro));
}

$monto_honorario = $subtotal_honorarios;
$descripcion_subtotal_gastos = __(Conf::GetConf($sesion, 'FacturaDescripcionGastosConIva'));
$monto_subtotal_gastos = $subtotal_gastos;
$descripcion_subtotal_gastos_sin_impuesto = __(Conf::GetConf($sesion, 'FacturaDescripcionGastosSinIva'));
$monto_subtotal_gastos_sin_impuesto = $subtotal_gastos_sin_impuestos;

if ($factura->loaded()) {
	$porcentaje_impuesto = $factura->fields['porcentaje_impuesto'];
} else if ($id_cobro > 0) {
	$porcentaje_impuesto = $cobro->fields['porcentaje_impuesto'];
} else {
	$porcentaje_impuesto = 0;
}

$query_moneda = "SELECT m.simbolo , m.glosa_moneda, m.cifras_decimales FROM prm_moneda m WHERE m.id_moneda = " . $id_moneda_factura;
$resp_moneda = mysql_query($query_moneda, $sesion->dbh) or Utiles::errorSQL($resp_moneda, __FILE__, __LINE__, $sesion->dbh);
list($simbolo, $glosa_moneda, $cifras_decimales) = mysql_fetch_array($resp_moneda);
$simbolosinadorno = $simbolo;

if ($factura->fields['total'] > 0) {
	$simbolo = "<span style='padding-left:5px'>" . $simbolo . "</span>";

	//SIN DESGLOSE
	if ($factura->fields['subtotal']) {
		$suma_monto = $factura->fields['subtotal'];
	}

	if ($factura->fields['iva']) {
		$suma_iva = $factura->fields['iva'];
	}

	if ($factura->fields['total']) {
		$suma_total = $factura->fields['total'];
	}

	//CON DESGLOSE
	$descripcion_honorario = $factura->fields['descripcion'];
	$monto_honorario = $factura->fields['subtotal'];
	$honorario = $factura->fields['subtotal'];
	$descripcion_subtotal_gastos = $factura->fields['descripcion_subtotal_gastos'];
	$monto_subtotal_gastos = $factura->fields['subtotal_gastos'];
	$descripcion_subtotal_gastos_sin_impuesto = $factura->fields['descripcion_subtotal_gastos_sin_impuesto'];
	$monto_subtotal_gastos_sin_impuesto = $factura->fields['subtotal_gastos_sin_impuesto'];

	if ($descripcion_honorario == '' && $monto_honorario > 0) {
		$descripcion_honorario = __('Honorarios Legales');
		if (Conf::GetConf($sesion, 'DescripcionFacturaConAsuntos')) {
			$descripcion_honorario .= "\n" . implode(', ', $cobro_->AsuntosNombreCodigo($id_cobro));
		}
	}

	if ($descripcion_subtotal_gastos == '') {
		$descripcion_subtotal_gastos = __('Gastos c/ IVA');
	}

	if ($descripcion_subtotal_gastos_sin_impuesto == '') {
		$descripcion_subtotal_gastos_sin_impuesto = __('Gastos s/ IVA');
	}
}

if ($monto_honorario == '') {
	$monto_honorario = 0;
}

if ($monto_subtotal_gastos == '') {
	$monto_subtotal_gastos = 0;
}

if ($monto_subtotal_gastos_sin_impuesto == '') {
	$monto_subtotal_gastos_sin_impuesto = 0;
}

$Form = new Form();
$Form->defaultLabel = false;
/*
 * FIN - Mostrar valores por defecto
 */

?>

<form method=post id="form_facturas" name="form_facturas">
	<input type="hidden" name="opcion" id="opcion" value="" />
	<input type='hidden' name="id_factura" id="id_factura" value="<?php echo $factura->fields['id_factura']; ?>" />
	<input type="hidden" name="id_documento_legal" value="<?php echo $id_documento_legal; ?>" />
	<input type="hidden" name="elimina_ingreso" id="elimina_ingreso" value="" />
	<input type="hidden" name="id_cobro" id="id_cobro" value="<?php echo $id_cobro; ?>" />
	<input type="hidden" name="subTotal" id="subTotal" value="<?php echo $suma_monto; ?>" />
	<input type="hidden" name="id_contrato" id="id_contrato" value='<?php echo $id_contrato ?>'/>
	<input type="hidden" name="id_moneda_factura" id="id_moneda_factura" value='<?php echo $id_moneda_factura ?>'/>
	<input type="hidden" class="aproximable" name="honorario_disp" id="honorario_disp" value='<?php echo $honorario_disp ?>'/>
	<input type="hidden" class="aproximable" name="gastos_con_impuestos_disp" id="gastos_con_impuestos_disp" value='<?php echo $gastos_con_impuestos_disp ?>'/>
	<input type="hidden" class="aproximable" name="gastos_sin_impuestos_disp" id="gastos_sin_impuestos_disp" value='<?php echo $gastos_sin_impuestos_disp ?>'/>
	<input type="hidden" class="aproximable" name="honorario_total" id="honorario_total" value='<?php echo $honorario_total ?>'/>
	<input type="hidden" class="aproximable" name="gastos_con_impuestos_total" id="gastos_con_impuestos_total" value='<?php echo $gastos_con_impuestos_total ?>'/>
	<input type="hidden" class="aproximable" name="gastos_sin_impuestos_total" id="gastos_sin_impuestos_total" value='<?php echo $gastos_sin_impuestos_total ?>'/>
	<input type='hidden' name='opc' id='opc' value='buscar'>
	<input type="hidden" name="porcentaje_impuesto" id="porcentaje_impuesto" value="<?php echo $porcentaje_impuesto; ?>">
	<input type="hidden" name="usar_adelantos" id="usar_adelantos" value="0"/>
	<input type="hidden" name="id_adelanto" id="id_adelanto" value=""/>

	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<!-- Fin calendario DIV -->

	<p width='90%' class="al tb">
		<?php echo $txt_pagina; ?>
	</p>
	<p width='90%' class="al tb">
		<?php echo __('Información de') . ' ' . $tipo_documento_legal; ?>
	</p>

	<hr/>
	<table width='95%'>
		<tbody>
			<tr>
				<td id="controles_factura" colspan="4" align="center"></td>
			</tr>
			<?php
			// Si no viene de un POST puede ser nuevo o existente, si es nuevo ocupo el del $contrato
			if (empty($id_estudio)) {
				$id_estudio = !empty($factura->fields['id_estudio']) ? $factura->fields['id_estudio'] : $contrato->fields['id_estudio'];
			}

			$estudios_array = PrmEstudio::GetEstudios($sesion);
			if (count($estudios_array) > 1) {
				?>
				<tr>
					<td align="right"><?php echo __('Companía'); ?></td>
					<td align="left" colspan="3">
						<?php echo Html::SelectArray($estudios_array, 'id_estudio', $id_estudio, 'id="id_estudio" onchange="cambiarEstudio(this.value)"', '', '300px'); ?>
					</td>
				</tr>
			<?php } else { ?>
				<input type="hidden" name="id_estudio" id="id_estudio" value="<?php echo $estudios_array[0]['id_estudio']; ?>" />
			<?php } ?>

			<?php
			$numero_documento = '';
			if (Conf::GetConf($sesion, 'NuevoModuloFactura')) {
				$serie = $factura->Loaded() ? $factura->fields['serie_documento_legal'] : $DocumentoLegalNumero->SeriesPorTipoDocumento($id_documento_legal, true);
				$numero_documento = $factura->ObtenerNumeroDocLegal($id_documento_legal, $serie, $id_estudio);
			} else if (Conf::GetConf($sesion, 'UsaNumeracionAutomatica')) {
				$numero_documento = $factura->ObtieneNumeroFactura();
			}
			?>

			<?php
				$disableInvoiceNumber = ($factura->loaded() && $factura->FacturaElectronicaCreada()) ? 'readonly' : '';
			?>
			<tr>
				<td width="140" align="right"><?php echo __('Número'); ?></td>
				<td align="left">
					<?php
					if (Conf::GetConf($sesion, 'NumeroFacturaConSerie')) {
						echo Html::SelectQuery($sesion, $DocumentoLegalNumero->SeriesQuery($id_estudio), 'serie', $serie, 'onchange="NumeroDocumentoLegal()" ' . $disableInvoiceNumber, null, 60);
					} else {
						$serie_documento_legal = $DocumentoLegalNumero->SeriesPorTipoDocumento(1, true);
						?>
						<input type="hidden" name="serie" id="serie" value="<?php echo $serie_documento_legal; ?>">
					<?php } ?>
					<input type="text" <?php echo $disableInvoiceNumber; ?> name="numero" value="<?php echo $factura->fields['numero'] ? $factura->fields['numero'] : $numero_documento; ?>" id="numero" size="11" maxlength="10" />
				</td>
				<td align="right" colspan="2"><?php echo __('Estado'); ?>
				<?php
					$deshabilita_estado = ($factura->fields['anulado'] == 1 && ($factura->DTEAnulado() || $factura->DTEProcesandoAnular())) ? 'disabled' : '';
				?>
				<?php echo Html::SelectQuery($sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC", "id_estado", $factura->fields['id_estado'] ? $factura->fields['id_estado'] : $id_estado, 'onchange="mostrarAccionesEstado(this.form)" ' . $deshabilita_estado, '', "160"); ?>
				<?php ($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_factura_dte_estado') : false; ?>
				<?php
						if (!empty($factura->fields['fecha_anulacion'])) {
							$fecha_anula = Utiles::sql3fecha($factura->fields['fecha_anulacion'], '%d-%m-%Y'); ?>
							<span style="background-color:yellow"><?php echo "el {$fecha_anula}"?></span>
				<?php 	} ?>
				</td>
			</tr>

			<?php
			//Se debe elegir un documento legal padre si:
			$buscar_padre = false;

			$query_doc = "SELECT codigo, codigo_dte FROM prm_documento_legal WHERE id_documento_legal = '$id_documento_legal'";
			$resp_doc = mysql_query($query_doc, $sesion->dbh) or Utiles::errorSQL($query_doc, __FILE__, __LINE__, $sesion->dbh);
			list($codigo_documento_legal, $codigo_dte) = mysql_fetch_array($resp_doc);

			if (($codigo_documento_legal == 'NC' || ($codigo_documento_legal == 'ND' && !is_null($codigo_dte))) && ($id_cobro || $codigo_cliente)) {
				$glosa_numero_serie = Conf::GetConf($sesion, 'NumeroFacturaConSerie') ? "prm_documento_legal.glosa,' #', factura.serie_documento_legal, '-', numero" : "prm_documento_legal.glosa, ' #', numero";
				if ($id_cobro) {
					$query_padre = "SELECT id_factura, CONCAT({$glosa_numero_serie}) FROM factura JOIN prm_documento_legal USING (id_documento_legal) WHERE id_cobro = '{$id_cobro}'";
				} else if ($codigo_cliente) {
					$query_padre = "SELECT id_factura, CONCAT({$glosa_numero_serie}) FROM factura JOIN prm_documento_legal USING (id_documento_legal) WHERE codigo_cliente = '{$codigo_cliente}'";
				}
				$resp_padre = mysql_query($query_padre, $sesion->dbh) or Utiles::errorSQL($query_padre, __FILE__, __LINE__, $sesion->dbh);
				if (list($a, $b) = mysql_fetch_array($resp_padre)) {
					$buscar_padre = true;
				}
			}

			if ($buscar_padre) {
			?>
			<tr>
				<td align="right"><?php echo __('Para Documento Tributario:') ?></td>
				<td align="left" colspan="3"><?php echo Html::SelectQuery($sesion, $query_padre, 'id_factura_padre', $factura->fields['id_factura_padre'], '', '--', '160') ?></td>
			</tr>
			<?php } ?>

			<?php if ($prm_plugin->isActive(array('facturacion_electronica_cl.php', 'facturacion_electronica_nubox.php'))): ?>
				<?php $codigos = $prm_codigo->getCodigosByGrupo('PRM_FACTURA_DR'); ?>
				<?php if ($codigo_documento_legal == 'FA' && !is_null($codigo_dte) && sizeof($codigos) > 0): ?>
				<tr>
					<td align="right">
						<label for="id_documento_referencia"><?php echo __('Documento de Referencia') ?>:</label>
					</td>
					<td align="left">
						<?php echo $Form->select('id_documento_referencia', $codigos, $factura->fields['id_documento_referencia'], array('empty' => __('Seleccione'))); ?>
					</td>
				</tr>
				<tr>
					<td align="right"><label for="folio_documento_referencia">Folio Documento Referencia</label></td>
					<td align="left">
						<input type="text" name="folio_documento_referencia" id="folio_documento_referencia" value="<?php echo ! empty($factura->fields['folio_documento_referencia']) ? $factura->fields['folio_documento_referencia'] : NULL ?>">
					</td>
				</tr>
				<tr>
					<td align="right"><label for="fecha_documento_referencia">Fecha Documento Referencia</label></td>
					<td align="left">
						<input type="text" class="fechadiff" name="fecha_documento_referencia" id="fecha_documento_referencia" value="<?php echo ! empty($factura->fields['fecha_documento_referencia']) ? Utiles::sql2date($factura->fields['fecha_documento_referencia'], '%d-%m-%Y') : NULL ?>">
					</td>
				</tr>
				<?php endif; ?>
			<?php endif; ?>

		<?php
		$zona_horaria = Conf::GetConf($sesion, 'ZonaHoraria');

		if ($zona_horaria) {
			date_default_timezone_set($zona_horaria);
		}
		?>
		<tr>
			<td align="right"><?php echo __('Fecha') ?></td>
			<td align="left" colspan="2">
				<?php echo $Html::PrintCalendar('fecha', Utiles::sql2date($factura->fields['fecha'])); ?>
			</td>

			<td><span style='display:none' id=letra_inicial>&nbsp;&nbsp;
		<?php echo __('Letra') ?>
					:&nbsp;
					<input name='letra_inicial' value='<?php echo $factura->fields['letra'] ? $factura->fields['letra'] : '' ?>' size=10/>
				</span></td>
		</tr>
		<tr>
			<td align="right"><?php echo __('Cliente') ?></td>
			<td align="left" colspan="3">
				<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario);?>
			</td>
		</tr>
		<tr style="display:none;">
			<td><?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?></td>
		</tr>

		<tr>
			<?php if (Conf::GetConf($sesion, 'TipoDocumentoIdentidadFacturacion')) { ?>
				<td align="right"><?php echo __('Doc. Identidad'); ?></td>
				<td align="left" colspan="3">
					<?php echo Html::SelectQuery($sesion, "SELECT id_tipo_documento_identidad, glosa FROM prm_tipo_documento_identidad", "tipo_documento_identidad", $factura->fields['id_tipo_documento_identidad'], "", " ", 150); ?>
					<input type="text" name="RUT_cliente" value="<?php echo $factura->loaded() ? $factura->fields['RUT_cliente'] : $contrato->fields['rut']; ?>" id="RUT_cliente" size="30" maxlength="50" />
				</td>
			<?php } else { ?>
				<td align="right"><?php echo __('ROL/RUT'); ?></td>
				<td align="left" colspan="3">
					<input type="text" name="RUT_cliente" value="<?php echo $factura->loaded() ? $factura->fields['RUT_cliente'] : $contrato->fields['rut']; ?>" id="RUT_cliente" size="30" maxlength="50" />
				</td>
			<?php } ?>
		</tr>
		<tr>
			<td align="right"><?php echo __('Raz&oacute;n Social Cliente'); ?></td>
			<td align="left" colspan="3">
				<input type="text" name="cliente" value="<?php echo $factura->loaded() ? $factura->fields['cliente'] : $contrato->fields['factura_razon_social']; ?>" id="cliente" size="70"/>
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo __('Direcci&oacute;n Cliente'); ?></td>
			<td align="left" colspan="3">
				<input type="text" name="direccion_cliente" value="<?php echo $factura->loaded() ? $factura->fields['direccion_cliente'] : $contrato->fields['factura_direccion']; ?>" id="direccion_cliente" size="70" maxlength="255" />
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo __('Comuna'); ?></td>
			<td align="left" colspan="3">
				<input type="text" name="comuna_cliente" value="<?php echo $factura->loaded() ? $factura->fields['comuna_cliente'] : $contrato->fields['factura_comuna']; ?>" id="comuna_cliente" size="70" maxlength="255" />
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo __('Código Postal'); ?></td>
			<td align="left" colspan="3"><input type="text" name="factura_codigopostal" value="<?php echo $factura->loaded() ? $factura->fields['factura_codigopostal'] : $contrato->fields['factura_codigopostal']; ?>" id="factura_codigopostal" size="30" maxlength="20" />
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo __('Ciudad'); ?></td>
			<td align="left" colspan="3"><input type="text" name="ciudad_cliente" value="<?php echo $factura->loaded() ? $factura->fields['ciudad_cliente'] : $contrato->fields['factura_ciudad']; ?>" id="ciudad_cliente" size="70" maxlength="255" />
			</td>
		</tr>
		<?php if (Conf::GetConf($sesion, 'RegionCliente')) { ?>
		<tr>
			<td align="right"><?php echo __('Región'); ?></td>
			<td align="left" colspan="3"><input type="text" name="factura_region" value="<?php echo $factura->loaded() ? $factura->fields['factura_region'] : $contrato->fields['region_cliente']; ?>" id="factura_region" size="70" maxlength="255" />
			</td>
		</tr>
		<?php } ?>
		<tr>
			<td align="right"><?php echo __('Giro'); ?></td>
			<td align="left" colspan="3">
				<input type="text" name="giro_cliente" value="<?php echo $factura->loaded() ? $factura->fields['giro_cliente'] : $contrato->fields['factura_giro']; ?>" id="giro_cliente" size="70" maxlength="255" />
			</td>
		</tr>
		<tr>
			<td align="right" colspan="1"><?php echo __('País'); ?></td>
			<td align="left" colspan="3">
				<?php echo Html::SelectQuery($sesion, PrmPais::SearchQuery(), 'dte_id_pais', $factura->fields['dte_id_pais'] ? $factura->fields['dte_id_pais'] : $contrato->fields['id_pais'], 'class ="span3"', 'Vacio', 160); ?>
			</td>
		</tr>
		<?php ($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_factura_metodo_pago') : false; ?>
		<tr>
			<td align="right"><?php echo __('Condición de Pago') ?></td>
			<td align="left" colspan="3">
				<select type="text" name="condicion_pago" value="<?php echo $factura->fields['condicion_pago'] ?>" id="condicion_pago" >
					<?php
						$Criteria = new Criteria($sesion);
						$condiciones_pago = $Criteria
							->add_select('id_condicion_pago, glosa, defecto')
							->add_from('condicion_pago')
							->add_ordering('orden')
							->run();

						foreach ($condiciones_pago as $condicion) {
							if (empty($factura->fields['condicion_pago'])) {
								$select = $condicion['defecto'] == 1 ? 'selected' : '';
							} else {
								$select = $factura->fields['condicion_pago'] == $condicion['id_condicion_pago'] ? 'selected' : '';
							}

							echo "<option value='" .
								$condicion['id_condicion_pago'] . "' " .
								$select . " >" .
								str_pad($condicion['id_condicion_pago'], 2, '0', STR_PAD_LEFT) . ': ' . $condicion['glosa'] .
								"</option>";
						}
					?>
				</select>
			</td>
		</tr>
		<tr class="fecha_vencimiento_pago" style="visibility: visible;">
			<td align="right" ><?php echo __('Fecha Vencimiento')?></td>
			<td align="left" colspan="3" >
				<?php echo $Html::PrintCalendar('fecha_vencimiento_pago_input', Utiles::sql2date($factura->fields['fecha_vencimiento'])); ?>
			</td>
		</tr>


		<?php
		$cantidad_lineas_descripcion = Conf::GetConf($sesion, 'CantidadLineasDescripcionFacturas');
		if (Conf::GetConf($sesion, 'NuevoModuloFactura')) {
			?>
			<tr>
				<td align="right" ><?php echo __('Glosa Factura')?></td>
				<td align="left" colspan="3" ><textarea id="glosa" name="glosa" cols="50" rows="2" style="font-family: Arial; font-size: 11px"><?php echo trim($glosa); ?></textarea></td>
			</tr>
			<tr id='descripcion_factura'>
				<td align="right" width="100">&nbsp;</td>
				<td align="left" style="vertical-align:bottom" width="250"><?php echo __('Descripción'); ?></td>
				<td align="left" width="100"><?php echo __('Monto'); ?></td>
				<td align="left"><?php echo __('Monto Impuesto'); ?></td>
			</tr>

			<tr id="fila_descripcion_honorarios_legales">
				<td id="glosa_honorarios_legales" align="right"><?php echo __('Honorarios legales'); ?></td>
				<td align="left">
					<?php
					if (Conf::GetConf($sesion, 'DescripcionFacturaConAsuntos')) {
						?>
						<textarea id="descripcion_honorarios_legales" name="descripcion_honorarios_legales"  id="descripcion_honorarios_legales" cols="50" rows="5" style="font-family: Arial; font-size: 11px"><?php echo trim($descripcion_honorario); ?></textarea>
						<?php
					} else if ($cantidad_lineas_descripcion > 1) {
						?>
						<textarea  id="descripcion_honorarios_legales"  name="descripcion_honorarios_legales"  id="descripcion_honorarios_legales" cols="50" rows="<?php echo $cantidad_lineas_descripcion ?>" style="font-family: Arial; font-size: 11px; text-align: left;"><?php echo trim($descripcion_honorario); ?></textarea>
						<?php
					} else {
						?>
						<input type="text" name="descripcion_honorarios_legales" id="descripcion_honorarios_legales" value="<?php echo trim($descripcion_honorario); ?>" maxlength="250" size="40" />
						<?php
					}
					?>
				</td>

				<td id="td_honorarios_legales"  align="left" nowrap><?php echo $simbolo; ?>
					<input type="text" name="monto_honorarios_legales" class="aproximable"  id="monto_honorarios_legales" value="<?php echo isset($honorario) ? $honorario : $monto_honorario; ?>" size="10" maxlength="30" onblur="desgloseMontosFactura(this.form)"; onkeydown="MontoValido(this.id);">
				<?php if ($id_cobro > 0 && Conf::GetConf($sesion,'VisualizaDescuentoEnFactura')) { ?>
 					<img data-id='<?php echo $factura->fields["id_factura"] ?>' data-chargeId='<?php echo $id_cobro ?>' class='detalle_honorarios_factura' src='<?php echo Conf::ImgDir()  ?>/noticia16.png' style='cursor:pointer' />
				<?php }?>
				</td>
				<td id="td_impto_honorarios_legales" align="left" nowrap><?php echo $simbolo; ?>
					<input type="text" name="monto_iva_honorarios_legales" class="aproximable"   id="monto_iva_honorarios_legales" value="<?php echo $impuesto; ?>" disabled="true" value="0" size="10" maxlength="30" onkeydown="MontoValido(this.id);"></td>
			</tr>

			<tr id="fila_descripcion_gastos_con_iva">
				<td align="right"><?php echo __('Gastos c/ IVA'); ?></td>
				<td align="left">
					<?php if ($cantidad_lineas_descripcion > 1) { ?>
						<textarea id="descripcion_gastos_con_iva" name="descripcion_gastos_con_iva" cols="50" rows="<?php echo $cantidad_lineas_descripcion ?>" style="font-family: Arial; font-size: 11px; text-align: left;"><?php echo trim($descripcion_subtotal_gastos); ?></textarea>
					<?php } else { ?>
						<input type="text" id="descripcion_gastos_con_iva" name="descripcion_gastos_con_iva" value="<?php echo trim($descripcion_subtotal_gastos); ?>" size="40" maxlength="250">
					<?php } ?>
				</td>
				<td align="left" nowrap><?php echo $simbolo; ?>
					<input type="text" name="monto_gastos_con_iva"  class="aproximable"  id="monto_gastos_con_iva" value="<?php echo isset($gastos_con_iva) ? $gastos_con_iva : $monto_subtotal_gastos; ?>" size="10" maxlength="30" onblur="desgloseMontosFactura()"  >
				</td>
				<td align="left" nowrap><?php echo $simbolo; ?>
					<input type="text" name="monto_iva_gastos_con_iva" class="aproximable"   id="monto_iva_gastos_con_iva" value="<?php echo $impuesto_gastos; ?>" disabled="true" value="0" size="10" maxlength="30" >
				</td>
			</tr>

			<tr id="fila_monto_gastos_sin_iva"  <?php echo (!Conf::GetConf($sesion, 'UsarGastosConSinImpuesto')) ? "style='display:none;'" : ""; ?> >
				<td align="right"><?php echo __('Gastos s/ IVA'); ?></td>
				<td align="left">
					<?php if ($cantidad_lineas_descripcion > 1) { ?>
						<textarea id="descripcion_gastos_sin_iva" name="descripcion_gastos_sin_iva" cols="50" rows="<?php echo $cantidad_lineas_descripcion ?>" style="font-family: Arial; font-size: 11px; text-align: left;"><?php echo trim($descripcion_subtotal_gastos_sin_impuesto); ?></textarea>
					<?php } else { ?>
						<input type="text" id="descripcion_gastos_sin_iva" name="descripcion_gastos_sin_iva"     id="descripcion_gastos_sin_iva" value="<?php echo trim($descripcion_subtotal_gastos_sin_impuesto); ?>" size="40" maxlength="250" >
					<?php } ?>
				</td>
				<td align="left" nowrap><?php echo $simbolo; ?>
					<input type="text" name="monto_gastos_sin_iva"  class="aproximable"  id="monto_gastos_sin_iva" value="<?php echo isset($gastos_sin_iva) ? $gastos_sin_iva : $monto_subtotal_gastos_sin_impuesto; ?>" size="10" maxlength="30"   ></td>
				<td align="left">&nbsp;</td>
			</tr>

			<tr>
				<td align="right" colspan=2 ><?php echo __('Monto') ?></td>
				<td align="left" nowrap><?php echo $simbolo; ?>
					<input type="text"  class="aproximable"  name="monto_neto" id='monto_neto' value="<?php echo $suma_monto; ?>" size="10" maxlength="30" disabled="true"  /></td>
				<td align="left">&nbsp;</td>
			</tr>

			<tr id='descripcion_factura'>
				<td align="right" colspan=2><?php echo __('Impuesto') ?></td>
				<td align="left" nowrap><?php echo $simbolo; ?>
					<input type="text" id='iva'  class="aproximable"  name="iva" value="<?php echo $suma_iva; ?>" size="10" maxlength="30" disabled="true"  />
					<input type="hidden" id='iva_hidden'   class="aproximable" name="iva_hidden"></td>
			</tr>

			<tr id='descripcion_factura'>
				<td align="right" colspan=2><?php echo __('Monto Total') ?></td>
				<td align="left" nowrap><?php echo $simbolo; ?>
					<input type="text" id='total' name="total"  class="aproximable"  value="<?php echo $suma_total; ?>" size="10" maxlength="30"  readonly="readonly"></td>
				<td>&nbsp;</td>
			</tr>

		<?php } else { ?>

			<tr id='descripcion_factura'>
				<td align="right"><?php echo __('Descripción') ?></td>
				<td align="left"><textarea id="descripcion" name="descripcion" cols="45" rows="3"><?php echo $factura->loaded() ? $factura->fields['giro_cliente'] : $contrato->fields['glosa_contrato']; ?></textarea></td>
			</tr>
			<tr id='descripcion_factura'>
				<td align="right"><?php echo __('Monto') ?></td>
				<td align="left"><input type="text" name="monto_neto" class="aproximable"  id='monto_neto' value="<?php echo $suma_monto; ?>" onchange="var total = Number($('monto_neto').value.replace(',', '.')) + Number($('iva').value.replace(',', '.'));
							$('total').value = total.toFixed(2);" /></td>
			</tr>
			<tr id='descripcion_factura'>
				<td align="right"><?php echo __('Impuesto') ?></td>
				<td align="left"><input type="text" id='iva' name="iva" class="aproximable"  value="<?php echo $suma_iva; ?>" size="10" maxlength="30"   onchange="var total = Number($('monto_neto').value.replace(',', '.')) + Number($('iva').value.replace(',', '.'));
							$('total').value = total.toFixed(2);" /></td>
			</tr>
			<tr id='descripcion_factura'>
				<td align="right"><?php echo __('Monto Total') ?></td>
				<td align="left"><input type="text" id='total' name="total"  class="aproximable"  value="<?php echo $suma_total; ?>" size="10" maxlength="30"  readonly="readonly"></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<div id="TipoCambioFactura" style="display:none" title="<?php echo __('Tipo de Cambio Documento de Pago') ?>">
		<div id="contenedor_tipo_cambio">
			<table cellpadding="3" width="100%">
				<?php
				if ($factura->fields['id_factura']) {
					$query = "SELECT count(*)
							FROM cta_cte_fact_mvto_moneda
							LEFT JOIN cta_cte_fact_mvto AS ccfm ON ccfm.id_cta_cte_mvto=cta_cte_fact_mvto_moneda.id_cta_cte_fact_mvto
							WHERE ccfm.id_factura = '" . $factura->fields['id_factura'] . "'";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
					list($cont) = mysql_fetch_array($resp);
				} else {
					$cont = 0;
				}
				if ($cont > 0) {
					$query = "SELECT prm_moneda.id_moneda, glosa_moneda, cta_cte_fact_mvto_moneda.tipo_cambio
							FROM cta_cte_fact_mvto_moneda
							JOIN prm_moneda ON cta_cte_fact_mvto_moneda.id_moneda = prm_moneda.id_moneda
							LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_cta_cte_mvto = cta_cte_fact_mvto_moneda.id_cta_cte_fact_mvto
							WHERE cta_cte_fact_mvto.id_factura = '" . $factura->fields['id_factura'] . "'";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				} else {
					$query = "SELECT prm_moneda.id_moneda, glosa_moneda, cobro_moneda.tipo_cambio
							FROM cobro_moneda
							JOIN prm_moneda ON cobro_moneda.id_moneda = prm_moneda.id_moneda
							WHERE id_cobro = '" . $id_cobro . "'";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				}
				$num_monedas = 0;
				$ids_monedas = array();
				$tipo_cambios = array();
				while (list($id_moneda, $glosa_moneda, $tipo_cambio) = mysql_fetch_array($resp)) {
					?>
					<tr>
						<td class="ar tb">
							<?php echo $glosa_moneda ?>:
						</td>
						<td class="al" style="width: 60%">
							<?php echo $Form->input("factura_moneda_{$id_moneda}", $tipo_cambio, array('size' => 9)); ?>
						</td>
					</tr>
					<?php
					++$num_monedas;
					$ids_monedas[] = $id_moneda;
					$tipo_cambios[] = $tipo_cambio;
				}
				?>
			</table>
			<?php
			echo $Form->hidden('tipo_cambios_factura', implode(',', $tipo_cambios));
			echo $Form->hidden('ids_monedas_factura', implode(',', $ids_monedas));
			?>
			</td>
		</div>
	</div>
	<hr/>

	<table style="border: 0px solid #666;" width='95%'>
		<tbody>
		<tr>
			<td align="left">
				<?php
				echo $Form->icon_submit(__('Guardar'), 'save');
				echo $Form->icon_button(__('Cancelar'), 'exit', array('onclick' => 'Cerrar()'));
				if ($factura->loaded() && $factura->fields['anulado'] == 1 && !$factura->DTEAnulado() && !$factura->DTEProcesandoAnular()) {
					echo $Form->icon_button(__('Restaurar'), 'restore', array('onclick' => "Cambiar(jQuery('#form_facturas'), 'restaurar')"));
				}
				echo $Form->icon_button(__('Actualizar Tipo de Cambio'), 'ui-icon-money', array('onclick' => 'MostrarTipoCambioPago()', 'title' => 'Tipo de Cambio del Documento de Pago al ser pagado.'));
				?>
			</td>
		</tr>
		</tbody>
	</table>
</form>
<script  type="text/javascript" src="https://static.thetimebilling.com/js/typewatch.js"></script>

<script type="text/javascript">

	jQuery('.detalle_honorarios_factura').live('click', function() {
		var id = jQuery(this).data('id');
		var chargeId = jQuery(this).data('chargeid');
		var options = {
			"invoice": id,
			"charge": chargeId,
			"language": 'es',
			"amount": jQuery('#monto_honorarios_legales').val()
		}
		DetalleHonorarios(options, 'Invoice');
	});

	function DetalleHonorarios(options, type) {
		var text_window = '';
		jQuery.ajax({
			type: "POST",
			dataType: "JSON",
			url: root_dir + '/app/' + type + '/feeAmountDetailTable/',
			data: options,
			success: function(data, status, jqXHR) {
				if (data && data.detail) {
					text_window += data.detail;
				} else {
					text_window += "No existe desglose";
				}
				GeneraPopUpDetalleMonto(text_window);
			},
			error: function(jqXHR, status, error) {
				text_window += '<p>No se ha encontrado información.<p/>';
				GeneraPopUpDetalleMonto(text_window);
			}
		});

	}

	function GeneraPopUpDetalleMonto(html) {
		jQuery('<p/>')
			.attr('title', 'Desglose del monto')
			.html(html)
			.dialog({
				resizable: true,
				height: 350,
				width: 420,
				modal: true,
				open: function() {
					jQuery('.ui-dialog-title').addClass('ui-icon-info');
					jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
				},
				buttons: {
					"<?php echo __('Aceptar') ?>": function() {
						jQuery(this).dialog('close');
						return false;
					}
				}
			});
	}

	var cantidad_decimales = <?php echo intval($cifras_decimales_opc_moneda_total); ?>;
	var string_decimales = "<?php echo str_pad('', $cifras_decimales_opc_moneda_total, '0'); ?>";
	var porcentaje_impuesto = "<?php echo $porcentaje_impuesto; ?>";
	var saldo_trabajos = "<?php echo $x_resultados['monto_trabajos'][$opc_moneda_total]; ?>";
	var saldo_tramites = "<?php echo $x_resultados['monto_trabajos'][$opc_moneda_total]; ?>";
	var usar_monto_superior = false;
	<?php
	if ($id_cobro > 0) {
		echo "var porcentaje_impuesto_gastos = '{$cobro->fields['porcentaje_impuesto_gastos']}';";
	} else {
		if ($cobro->fields['porcentaje_impuesto_gastos'] == 0 && (Conf::GetConf($sesion, 'ValorImpuestoGastos'))) {
			echo "var porcentaje_impuesto_gastos = '" . Conf::GetConf($sesion, 'ValorImpuestoGastos') . "';";
		}
	}

	$numeros_serie = $DocumentoLegalNumero->UltimosNumerosSerie($id_documento_legal);
	$series = array();
	foreach ($numeros_serie as $numero_serie) {
		$series[$numero_serie['estudio']][$numero_serie['serie']] = $numero_serie['numero'];
	}
	echo 'var estudio_series = ' . json_encode($series) . ';';
	?>

	function formato_numeros() {
		var ceros = "0".times(parseFloat(cantidad_decimales));
		var decimales = '0.' + ceros;
		format = decimales;
		return {format: format, locale: 'us'};
	}

// funcion ajax para asignar valores a los campos del cliente en agregar factura
	function CargarDatosCliente(sin_contrato) {
		<?php if (Conf::GetConf($sesion, 'CodigoSecundario')) { ?>
			var id_origen = 'codigo_cliente_secundario';
		<?php } else { ?>
			var id_origen = 'codigo_cliente';
		<?php } ?>
		var accion = 'cargar_datos_contrato';
		var id_contrato = "<?php echo $id_contrato; ?>";
		var select_origen = document.getElementById(id_origen);
		var rut = document.getElementById('RUT_cliente');
		var cliente = document.getElementById('cliente');
		var direccion_cliente = document.getElementById('direccion_cliente');
		var comuna_cliente = document.getElementById('comuna_cliente');
		var ciudad_cliente = document.getElementById('ciudad_cliente');

		<?php if (Conf::GetConf($sesion, 'RegionCliente')) { ?>
			var factura_region = document.getElementById('factura_region');
		<?php } ?>

		var giro_cliente = document.getElementById('giro_cliente');
		var factura_codigopostal = document.getElementById('factura_codigopostal');
		var dte_id_pais = document.getElementById('dte_id_pais');

		<?php if (Conf::GetConf($sesion, 'NuevoModuloFactura')) { ?>
			var descripcion_honorarios_legales = document.getElementById('descripcion_honorarios_legales');
			var monto_honorarios_legales = document.getElementById('monto_honorarios_legales');
			var monto_iva_honorarios_legales = document.getElementById('monto_iva_honorarios_legales');
			var descripcion_gastos_con_iva = document.getElementById('descripcion_gastos_con_iva');
			var monto_gastos_con_iva = document.getElementById('monto_gastos_con_iva');
			var monto_iva_gastos_con_iva = document.getElementById('monto_iva_gastos_con_iva');
			<?php	if (Conf::GetConf($sesion, 'UsarGastosConSinImpuesto') == '1') { ?>
			var descripcion_gastos_sin_iva = document.getElementById('descripcion_gastos_sin_iva');
			var monto_gastos_sin_iva = document.getElementById('monto_gastos_sin_iva');
			<?php
			}
		} else { ?>
			var descripcion = document.getElementById('descripcion');
		<?php } ?>
		var http = getXMLHTTP();

		var url = root_dir + '/app/interfaces/ajax.php?accion=' + accion + '&codigo_cliente=' + select_origen.value;
		if (!sin_contrato) {
			url += '&id_contrato=' + id_contrato;
		}

		http.open('get', url, true);
		http.onreadystatechange = function()
		{
			if (http.readyState == 4)
			{
				var response = http.responseText;

				if (response.indexOf('|') != -1)
				{
					response = response.split('\\n');
					response = response[0];
					var campos = response.split('~');
					if (response.indexOf('VACIO') != -1)
					{
						// Dejamos los campos en blanco.
						cliente.value = '';
						direccion_cliente.value = '';
						rut.value = '';
						comuna_cliente.value = '';
						ciudad_cliente.value = '';
						<?php if (Conf::GetConf($sesion, 'RegionCliente')) { ?>
							factura_region.value = '';
						<?php } ?>
						giro_cliente.value = '';
						factura_codigopostal.value = '';
						if (dte_id_pais) {
							dte_id_pais.value = '';
						}
					}
					else
					{
						//select_destino.length = 1;
						for (i = 0; i < campos.length; i++)
						{
							valores = campos[i].split('|');
							var option = new Option();
							option.value = valores[0];
							option.text = valores[1];

							// Cliente
							if (valores[0] != '') {
								cliente.value = valores[0];
							} else {
								cliente.value = '';
							}
							// Dirección
							if (valores[1] != '') {
								direccion_cliente.value = valores[1];
							} else {
								direccion_cliente.value = '';
							}
							// Rut
							if (valores[2] != '') {
								rut.value = valores[2];
							} else {
								rut.value = '';
							}
							// Comuna
							if (valores[3] != '') {
								comuna_cliente.value = valores[3];
							} else {
								comuna_cliente.value = '';
							}
							// Ciudad
							if (valores[4] != '') {
								ciudad_cliente.value = valores[4];
							} else {
								ciudad_cliente.value = '';
							}

							// Región
							<?php if (Conf::GetConf($sesion, 'RegionCliente')) { ?>
								if(valores[5] != ''){
									factura_region.value = valores[5]
								} else{
									factura_region.value = '';
								}
							<?php } ?>

							// Giro
							if (valores[6] != '') {
								giro_cliente.value = valores[6];
							} else {
								giro_cliente.value = '';
							}

							// Codigo Postal
							if (valores[7] != '') {
								factura_codigopostal.value = valores[7];
							} else {
								factura_codigopostal.value = '';
							}

							// País
							if (valores[8] != '') {
								if (dte_id_pais) {
									dte_id_pais.value = valores[8];
								}
							} else {
								if (dte_id_pais) {
									dte_id_pais.value = '';
								}
							}
						}
					}
				}
				else
				{
					if (response.indexOf('head') != -1)
					{
						alert('Sesión Caducada');
						top.location.href = '".Conf::Host()."';
					}
					else
						alert(response);
				}
			}
			cargando = false;
		};
		http.send(null);
	}

	function isNumber(n) {
		return !isNaN(parseFloat(n)) && isFinite(n);
	}

	function MontoValido(id_campo)
	{
		var monto = document.getElementById(id_campo).value.replace('\,', '.');
		var arr_monto = monto.split('\.');
		var monto = arr_monto[0];
		for ($i = 1; $i < arr_monto.length - 1; $i++)
			monto += arr_monto[$i];
		if (arr_monto.length > 1)
			monto += '.' + arr_monto[arr_monto.length - 1];

		document.getElementById(id_campo).value = monto;
	}

	function MostrarTipoCambioPago() {
		jQuery('#TipoCambioFactura').dialog({
			width: 'auto',
			height: 'auto',
			modal: true,
			open: function() {
				jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
			},
			buttons: {
				"<?php echo __('Guardar') ?>": function() {
					if (ActualizarDocumentoMonedaPago()) {
						jQuery(this).dialog('close');
					} else {

					}
				},
				"<?php echo __('Cancelar') ?>": function() {
					jQuery(this).dialog('close');
				}
			}
		});
		jQuery('#TipoCambioFactura').show();
	}

	function BuscarFacturas()
	{
		document.forms.item(submit);
	}

	function Letra()
	{
		$('letra_inicial').show();
	}

	function mostrarAccionesEstado(form)
	{
		var id_estado = form.id_estado.value;
		$('letra_inicial').hide();
		if (id_estado == '4')
		{
			$('letra_inicial').show();
		}
		else if (id_estado == '5')
		{
			//Cambiar(form,'anular');
		}
	}

	function CambioCliente() {
		CargarDatosCliente();
	}

	function Cambiar(form, opc) {
		jQuery('#opcion').val('guardar');
		jQuery('#id_estado').val(1);
		jQuery(form).submit();
	}
	var saltar_validacion_saldo = 0;
	var mostrar_alert_saldo = 0;

	function utilizarAdelanto(id_documento) {
		jQuery('#id_adelanto').val(id_documento);
		jQuery('#usar_adelantos').val(1);
		jQuery('#form_facturas').submit();
	}

	function ValidaSaldoPendienteCobro(form) {
		loading('Actualizando campo');
		jQuery.ajax('ajax.php', {
			async: false,
			data: {accion: 'saldo_cobro_factura', id: jQuery('#id_cobro').val()},
			dataType: 'text',
			success: function(text) {
				if (text == 'primera_factura') {
					saltar_validacion_saldo = 1;
				}
				saldos = text.split('//');
				var format_number = formato_numeros();
				jQuery('#honorario_disp').val(jQuery('#honorario_total').parseNumber(format_number) + jQuery.parseNumber(saldos[0], format_number));
				jQuery('#gastos_con_impuestos_disp').val(jQuery('#gastos_con_impuestos_total').parseNumber(format_number) + jQuery.parseNumber(saldos[1], format_number));
				jQuery('#gastos_sin_impuestos_disp').val(jQuery('#gastos_sin_impuestos_total').parseNumber(format_number) + jQuery.parseNumber(saldos[1], format_number));
			}
		});
	}


	jQuery('#form_facturas').submit(function() {
		return Validar();
	});

	function validate(field, msg, rule) {
		var valid = true;
		if (!rule) {
			rule = 'empty';
		}
		var element = jQuery('#' + field);
		if (rule == 'empty' && element.val() == '') {
			valid = false;
		} else if (rule == 'number' && !isNumber(element.val())) {
			valid = false;
		}
		if (!valid) {
			if (msg) {
				alert(msg);
			}
			element.focus();
		}
		return valid;
	}

	function Validar() {
		<?php
		UtilesApp::GetConfJS($sesion, 'UsarGastosConSinImpuesto');
		UtilesApp::GetConfJS($sesion, 'TipoSelectCliente');
		UtilesApp::GetConfJS($sesion, 'TipoDocumentoIdentidadFacturacion');
		UtilesApp::GetConfJS($sesion, 'TipoSelectCliente');
		UtilesApp::GetConfJS($sesion, 'CodigoSecundario');
		UtilesApp::GetConfJS($sesion, 'NuevoModuloFactura');
		?>
		if (TipoDocumentoIdentidadFacturacion != 0) {
			if (!Validar_Rut())
				return false;
		}

		var campo_cliente = 'codigo_cliente';
		if (TipoSelectCliente == 'autocompletador') {
			campo_cliente = 'glosa_cliente';
		} else if (CodigoSecundario != 0) {
			campo_cliente = 'codigo_cliente_secundario';
		}
		if (!validate(campo_cliente, '<?php echo __('Debe ingresar un cliente') ?>')) {
			return false;
		}
		if (!validate('cliente', '<?php echo __('Debe ingresar la razon social del cliente.') ?>')) {
			return false;
		}

		if (NuevoModuloFactura == 1) {
			if (!validate('monto_honorarios_legales', '<?php echo __('Debe ingresar un monto para los honorarios') ?>')) {
				return false;
			}
			if (!validate('monto_honorarios_legales', '<?php echo __('Debe ingresar un monto válido para los honorarios') ?>', 'number')) {
				return false;
			}
			if (!validate('monto_iva_honorarios_legales', '<?php echo __('Debe ingresar un monto IVA para los honorarios') ?>')) {
				return false;
			}
			if (!validate('monto_iva_honorarios_legales', '<?php echo __('Debe ingresar un monto IVA válido para los honorarios.') ?>', 'number')) {
				return false;
			}
			if (!validate('monto_gastos_con_iva', '<?php echo __('Debe ingresar un monto para los gastos c/ IVA') ?>')) {
				return false;
			}
			if (!validate('monto_gastos_con_iva', '<?php echo __('Debe ingresar un monto válido para los gastos c/ IVA') ?>', 'number')) {
				return false;
			}
			if (!validate('monto_iva_gastos_con_iva', '<?php echo __('Debe ingresar un monto iva para los gastos c/ IVA') ?>')) {
				return false;
			}
			if (!validate('monto_iva_gastos_con_iva', '<?php echo __('Debe ingresar un monto iva válido para los gastos c/ IVA') ?>', 'number')) {
				return false;
			}

			opcion_seleccionada = jQuery('#id_estado option:selected').text().toLowerCase();
			id_opcion_seleccionada = jQuery('#id_estado').val();
			id_opcion_original = <?php echo $factura->fields['id_estado'] ? $factura->fields['id_estado'] : '1' ?>;
			if ((opcion_seleccionada == 'anulado' || opcion_seleccionada == 'anulada') && id_opcion_seleccionada != id_opcion_original) {
				//debe ser syncrono para que devuelva el valor antes de continuar
				jQuery.ajax('ajax.php', {
					async: false,
					data: {accion: 'obtener_num_pagos', id_factura: jQuery('#id_factura').val()},
					dataType: 'text',
					success: function(text) {
						num_pagos = text;
					}
				});
				if (num_pagos > 0) {
					alert('<?php echo __('La factura no puede anularse ya que posee pagos asociados.'); ?>');
					jQuery('#id_estado').val(id_opcion_original);
					return false;
				}
			}

			<?php if (!$factura->loaded() && ($id_documento_legal != 2)) { ?>
				var format_number = formato_numeros();
				jQuery('#monto_gastos_con_iva, #gastos_con_impuestos_disp, #monto_honorarios_legales, #honorario_disp, #monto_gastos_sin_iva, #gastos_sin_impuestos_disp').formatNumber(format_number);
				var monto_gastos_sin_iva_validacion = jQuery('#monto_gastos_sin_iva').parseNumber(format_number);
				var gastos_sin_impuestos_disp_validacion = jQuery('#gastos_sin_impuestos_disp').parseNumber(format_number);

				var monto_honorarios_legales_value = jQuery('#monto_honorarios_legales').parseNumber(format_number);
				var monto_gastos_con_iva_value = jQuery('#monto_gastos_con_iva').parseNumber(format_number);
				var honorario_disp_value = jQuery('#honorario_disp').parseNumber(format_number);
				var gastos_con_impuestos_disp_value = jQuery('#gastos_con_impuestos_disp').parseNumber(format_number);

				var monto_facturado = monto_honorarios_legales_value + monto_gastos_con_iva_value + monto_gastos_sin_iva_validacion;
				var saldo_cobro = honorario_disp_value + gastos_con_impuestos_disp_value + gastos_sin_impuestos_disp_validacion;
				var monto_superior = monto_facturado > saldo_cobro;

				if (!usar_monto_superior && (jQuery('#id_documento_legal').val() != 2) && (saltar_validacion_saldo == 0) && (monto_superior)) {
					usar_monto_superior = confirm('<?php echo __("Los montos ingresados superan el saldo a facturar") ?>');
					if (!usar_monto_superior) {
						if (UsarGastosConSinImpuesto == '1') {
							if (jQuery('#monto_honorarios_legales').val() > jQuery('#honorario_disp').val()) {
								jQuery('#monto_honorarios_legales').focus();
							}
							else if (jQuery('#monto_gastos_con_iva').val() > jQuery('#gastos_con_impuestos_disp').val()) {
								jQuery('#monto_gastos_con_iva').focus();
							}
							else if (jQuery('#monto_gastos_sin_iva').val() > jQuery('#gastos_sin_impuestos_disp').val()) {
								jQuery('#monto_gastos_sin_iva').focus();
							}

						} else {

							if (jQuery('#monto_honorarios_legales').val() > jQuery('#honorario_disp').val()) {
								jQuery('#monto_honorarios_legales').focus();
							}
							else if (jQuery('#monto_gastos_con_iva').val() > jQuery('#gastos_con_impuestos_disp').val()) {
								jQuery('#monto_gastos_con_iva').focus();
							}

						}
						return false;
					}
				}

			<?php } ?>

			if (UsarGastosConSinImpuesto == '1') {
				if (!validate('monto_gastos_sin_iva', '<?php echo __('Debe ingresar un monto para los gastos s/ IVA') ?>')) {
					return false;
				}
				if (!validate('monto_gastos_sin_iva', '<?php echo __('Debe ingresar un monto válido para los gastos s/ IVA') ?>', 'number')) {
					return false;
				}
				if (!validate('descripcion_gastos_sin_iva') && !validate('descripcion_gastos_con_iva') && !validate('descripcion_honorarios_legales')) {
					alert('<?php echo __('Debe ingresar una descripción para los honorarios y/o  gastos') ?>');
					return false;
				}
			}

		} else {
			if (!validate('descripcion', '<?php echo __('Debe ingresar una descripción') ?>')) {
				return false;
			}
		}

		if (jQuery('#id_factura_padre').length && !validate('id_factura_padre', '<?php echo __('Este documento debe estar asociado a un documento tributario') ?>')) {
			return false;
		}

		<?php
		if (!$factura->loaded() && $id_cobro && $id_documento_legal != 2) {
			if (Conf::GetConf($sesion, 'AsociarAdelantosALiquidacion')) {
				$cobro_facturado = new Cobro($sesion);
				$cobro_facturado->Load($id_cobro);
				$saldo = $cobro_facturado->MontoSaldoAdelantos();

				if ($saldo > 0) { ?>
					if (confirm('<?php echo __('Existen adelantos por ') . $simbolo . ' ' . number_format($saldo, $cifras_decimales) . ' ' .  __('asociados a esta liquidación. ¿Desea utilizarlos para saldar esta') . ' ' . $tipo_documento_legal . '?' ?>')) {
						$('usar_adelantos').value = '1';
					}
			<?php
				}
			} else {
				$documento = new Documento($sesion);
				$hh = $honorario;
				$gg = $gastos_con_iva + $gastos_sin_iva;
				$saldo = $documento->SaldoAdelantosDisponibles($codigo_cliente, $id_contrato, $hh>0, $gg>0, $cobro->fields['opc_moneda_total']);

				if ($saldo) {
				?>
					if (!jQuery('#id_adelanto').val() && confirm("<?php echo __('Existen adelantos') . ' ' . __('asociados a esta liquidación. ¿Desea utilizarlos para saldar esta') . " $tipo_documento_legal" . '?' ?>")) {
						var params = {
							popup: 1,
							id_cobro: '<?php echo $id_cobro ?>',
							codigo_cliente: '<?php echo $codigo_cliente ?>',
							elegir_para_pago: 1,
							id_contrato: '<?php echo $cobro->fields['id_contrato'] ?>',
							desde_factura_pago: 0,
							pago_honorarios: monto_honorarios_legales_value > 0 ? 1 : 0,
							pago_gastos: monto_gastos_sin_iva_validacion + monto_gastos_con_iva_value > 0 ? 1 : 0,
							como_funcion: 1
						};
						nuovaFinestra('Adelantos', 730, 470, root_dir + '/app/Advances/get_list?' + decodeURIComponent(jQuery.param(params)), 'top=100, left=125, scrollbars=yes');
						return false;
					}
				<?php
				}
			}
		}
		?>

		jQuery('#opcion').val('guardar');

		if (NuevoModuloFactura == 1) {
			jQuery('#iva_hidden').val(jQuery('#iva').val());
		}

		// Debe ser syncrono para que devuelva el valor antes de continuar
		jQuery.ajax('ajax.php', {
			async: false,
			data: {accion: 'obtener_num_pagos', id_factura: jQuery('#id_factura_padre').val()},
			dataType: 'text',
			success: function(text) {
				num_pagos = text;
			}
		});

		if (num_pagos > 0) {
			var mensaje = 'Estimado usuario, está tratando de asociar una nota de crédito a una factura que contiene pagos.\n\n¿Desea continuar?';
			if (!confirm(mensaje)) {
				return false;
			}
		}

		return true;
	}

	function Cerrar()
	{
		window.close();
	}

	function desgloseMontosFactura() {
		var monto_impuesto = 0;
		var monto_impuesto_gasto = 0;
		var monto_gasto_sin_impuesto = 0;
		var monto_neto = 0;
		var format_number = formato_numeros();
		monto_impuesto = jQuery('#monto_honorarios_legales').parseNumber(format_number) * (porcentaje_impuesto / 100);
		monto_impuesto_gasto = jQuery('#monto_gastos_con_iva').parseNumber(format_number) * (porcentaje_impuesto_gastos / 100);

		<?php if (Conf::GetConf($sesion, 'UsarGastosConSinImpuesto') == '1') { ?>
			monto_gasto_sin_impuesto = jQuery('#monto_gastos_sin_iva').parseNumber(format_number);
		<?php } ?>

		monto_neto = jQuery('#monto_honorarios_legales').parseNumber(format_number) +
				jQuery('#monto_gastos_con_iva').parseNumber(format_number) +
				monto_gasto_sin_impuesto;

		var iva = monto_impuesto + monto_impuesto_gasto;
		var total = monto_neto + iva;

		jQuery('#monto_neto').val(jQuery.formatNumber(monto_neto, format_number));
		jQuery('#monto_iva_honorarios_legales').val(jQuery.formatNumber(monto_impuesto, format_number));
		jQuery('#monto_iva_gastos_con_iva').val(jQuery.formatNumber(monto_impuesto_gasto, format_number));
		jQuery('#iva').val(jQuery.formatNumber(iva, format_number));
		jQuery('#total').val(jQuery.formatNumber(total, format_number));

		if (cantidad_decimales != -1) {
			jQuery('.aproximable').each(function() {
				jQuery(this).parseNumber(format_number);
				jQuery(this).formatNumber(format_number);
			});

		}
	}

	function ActualizarDocumentoMonedaPago() {
		var ids_monedas = jQuery('#ids_monedas_factura').val();
		var arreglo_ids = ids_monedas.split(',');
		var tipo_cambios_factura = [];
		for (var i = 0; i < arreglo_ids.length - 1; i++) {
			tipo_cambios_factura.push(jQuery('#factura_moneda_' + arreglo_ids[i]).val());
		}
		i = arreglo_ids.length - 1;
		tipo_cambios_factura.push(jQuery('#factura_moneda_' + arreglo_ids[i]).val());
		jQuery('#tipo_cambios_factura').val(tipo_cambios_factura.join(','));

		if (!jQuery('#id_factura').val()) {
			return true;
		}

		jQuery('<img/>').attr('src', '<?php echo Conf::ImgDir() ?>/ajax_loader.gif').insertBefore('.ui-dialog-buttonpane button:first');
		jQuery('.ui-dialog-buttonpane button:first').hide();

		var tc = new Array();
		for (var i = 0; i < arreglo_ids.length; i++) {
			tc[i] = jQuery('#factura_moneda_' + arreglo_ids[i]).val();
		}

		var url = root_dir + '/app/interfaces/ajax.php';
		var data_get = {accion: 'actualizar_factura_moneda', id_factura: '<?php echo $factura->fields['id_factura'] ?>', ids_monedas: ids_monedas, tcs: tc.join(',')};
		var actualizado = false;
		jQuery.ajax(url, {
			async: false,
			data: data_get,
			dataType: 'text',
			success: function(text) {
				if (text == 'EXITO') {
					actualizado = true;
				}
				jQuery('.ui-dialog-buttonpane img').remove();
				jQuery('.ui-dialog-buttonpane button:first').show();
			}
		});
		return actualizado;
	}

	/*Validador de Rut*/
	function Validar_Rut()
	{
		<?php if (!Conf::GetConf($sesion, 'TipoDocumentoIdentidadFacturacion')) : ?>
				return true;
		<?php else: ?>
				var tipo = $('tipo_documento_identidad');
				if (tipo.value != 5) {
					return true;
				}
		<?php endif; ?>

		var o = $('RUT_cliente');
		var tmpstr = "";
		var intlargo = o.value

		if (intlargo.length > 0) {
			crut = o.value
			largo = crut.length;

			if (largo < 2) {
				alert('<?php echo __("Rut inválido") ?>');
				o.focus();
				return false;
			}

			for (i = 0; i < crut.length; i++)
				if (crut.charAt(i) != ' ' && crut.charAt(i) != '.' && crut.charAt(i) != '-') {
					tmpstr = tmpstr + crut.charAt(i);
				}

			rut = tmpstr;
			crut = tmpstr;
			largo = crut.length;

			if (largo > 2) {
				rut = crut.substring(0, largo - 1);
			} else {
				rut = crut.charAt(0);
			}

			dv = crut.charAt(largo - 1);

			if (rut == null || dv == null) {
				alert('<?php echo __("Rut inválido") ?>');
				o.focus();
				return false;
			}

			var dvr = '0';
			suma = 0;
			mul = 2;

			for (i = rut.length - 1; i >= 0; i--)
			{
				suma = suma + rut.charAt(i) * mul;
				if (mul == 7)
					mul = 2;
				else
					mul++;
			}

			res = suma % 11;

			if (res == 1) {
				dvr = 'k';
			} else if (res == 0) {
				dvr = '0';
			} else {
				dvi = 11 - res;
				dvr = dvi + "";
			}

			if (dvr != dv.toLowerCase()) {
				alert('<?php echo __("El Rut Ingresado es Invalido") ?>');
				o.focus();
				return false;
			}
			return true;
		}

		alert('<?php echo __("Rut inválido") ?>');
		o.focus();
		return false;

	}

	function ObtenerPagos(id_factura)
	{
		/* por algun motivo no me lo toma, aunque sea sincrono */
		var http = getXMLHTTP();
		http.open('get', 'ajax.php?accion=obtener_num_pagos&id_factura=' + id_factura, false);
		http.onreadystatechange = revisaEstado;
		http.send(null);

		function revisaEstado()
		{
			if (http.readyState == 4)
			{
				response = http.responseText;
				return response;
			}
		}

		return http.responseText;
	}

	function NumeroDocumentoLegal() {
		var estudio_serie_numero = jQuery(document).data('estudio_serie_numero');

		jQuery.each(estudio_series, function(estudio, series) {
			if (jQuery('#id_estudio').val() == estudio) {
				jQuery.each(series, function(serie, numero) {
					if (jQuery('#serie').val() == serie) {
						if (estudio_serie_numero.estudio != estudio || estudio_serie_numero.serie != serie) {
							jQuery('#numero').val(numero);
						} else {
							jQuery('#numero').val(estudio_serie_numero.numero);
						}
						return false;
					}
				});
				return false;
			}
		});

		return true;
	}

	function cambiarEstudio(id_estudio) {
		if (jQuery('#serie').attr('type') == 'hidden') {
			var estudio_serie_numero = jQuery(document).data('estudio_serie_numero');

			jQuery.each(estudio_series, function(estudio, series) {
				if (jQuery('#id_estudio').attr('value') == estudio) {
					jQuery.each(series, function(serie, numero) {
						if (estudio_serie_numero.estudio != estudio || estudio_serie_numero.serie != serie) {
							jQuery('#numero').attr('value', numero);
						} else {
							jQuery('#numero').attr('value', estudio_serie_numero.numero);
						}
						return false;
					});
				}
			});
		} else {
			var select = jQuery('#serie');
			var options = (select.prop) ? select.prop('options') : select.attr('options');

			jQuery('option', select).remove();

			jQuery.each(estudio_series, function(estudio, series) {
				if (jQuery('#id_estudio').attr('value') == estudio) {
					jQuery.each(series, function(serie, numero) {
						options[options.length] = new Option(serie, serie);
					});
				}
			});

			NumeroDocumentoLegal();
		}

		return true;
	}

	function obtiene_fecha_vencimiento(dias, myDate){
		var offset = (dias * 24 * 60 * 60 * 1000);

		myDate.setTime(myDate.getTime() + offset);

		//Transformar objeto date a fecha
		var dia = myDate.getDate();
		var mes = myDate.getMonth() + 1;
		if(mes < 10){
			mes = '0' + mes;
		}
		if(dia < 10){
			dia = '0' + dia;
		}
		var anio = myDate.getFullYear();

		var fecha_vencimiento_pago = dia + "-" + mes + "-" + anio;

		return fecha_vencimiento_pago;
	}

	<?php
	if (Conf::GetConf($sesion, 'NuevoModuloFactura')) {
		echo "desgloseMontosFactura();\n";
		if ($factura->loaded() && $factura->fields['id_estado'] == '4' && $factura->fields['letra'] != '') {
			echo "Letra();\n";
		}
	}
	?>

	jQuery(document).ready(function() {
		jQuery(document).data('estudio_serie_numero', {
			'estudio': jQuery('#id_estudio').val(),
			'serie': jQuery('#serie').val(),
			'numero': jQuery('#numero').val()
		});

		jQuery('#codigo_cliente,#campo_codigo_cliente').change(function() {
			CargarDatosCliente(1);
		});

		jQuery('#fecha').change(function(){
			jQuery('#condicion_pago').trigger('change');
		});

		//Manejo de select de condicion de pago.
		jQuery('#condicion_pago').change(function(){
			var codigo = jQuery(this).val();
			if(codigo == 1 || codigo == 21){
				//jQuery('.fecha_vencimiento_pago').css('visibility', 'visible');
				var persistedDate = '<?php echo Utiles::sql2date($factura->fields["fecha_vencimiento"]) ?>';
				if (persistedDate == '') {
					var dias = 1;
					var myDate = new Date();
					var fecha_vencimiento_pago = obtiene_fecha_vencimiento(dias, myDate);
					jQuery('#fecha_vencimiento_pago_input').val(fecha_vencimiento_pago);
				} else {
					jQuery('#fecha_vencimiento_pago_input').val(persistedDate);
				}
				jQuery('#fecha_vencimiento_pago_input').attr('readonly',false);
			}
			else{
				//jQuery('.fecha_vencimiento_pago').css('visibility', 'hidden');
				jQuery('#fecha_vencimiento_pago_input').attr('readonly',true);
				var texto = jQuery(this).find(":selected").text();
				var splitted_text = texto.split(' ');
				var dias = splitted_text[2];
				dias++;
				var fecha_definida = jQuery('#fecha').val();
				var fecha_definida_split = fecha_definida.split('-');
				var myDate = new Date(fecha_definida_split[2], fecha_definida_split[1] - 1, fecha_definida_split[0]);

				var fecha_vencimiento_pago = obtiene_fecha_vencimiento(dias, myDate);

				jQuery('#fecha_vencimiento_pago_input').val(fecha_vencimiento_pago);
			}
		});

		jQuery('#condicion_pago').trigger('change');

		if (cantidad_decimales != -1) {
			jQuery('.aproximable').each(function() {
				jQuery(this).val = jQuery(this).parseNumber({format: "0.<?php echo str_pad('', $cifras_decimales_opc_moneda_total, "0"); ?>", locale: "us"}) + 0.0000001;
				jQuery(this).formatNumber({format: "0.<?php echo str_pad('', $cifras_decimales_opc_moneda_total, "0"); ?>", locale: "us"});
			});

			jQuery('.aproximable').typeWatch({
				callback: function() {
					desgloseMontosFactura();
				},
				wait: 700,
				highlight: false,
				captureLength: 1
			});
		}

		jQuery('#RUT_cliente').blur(function() {
			<?php if (Conf::GetConf($sesion, 'TipoDocumentoIdentidadFacturacion')) { ?>
				Validar_Rut();
			<?php } ?>
		});

		<?php if (($codigo_cliente || $codigo_cliente_secundario) && empty($id_factura)) { ?>
			CargarDatosCliente();
		<?php } ?>

		<?php echo ($requiere_refrescar) ? $requiere_refrescar : ''; ?>

	});

	<?php ($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_factura_javascript_after') : false; ?>
</script>
<?php $pagina->PrintBottom($popup);
