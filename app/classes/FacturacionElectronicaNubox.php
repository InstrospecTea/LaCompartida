<?php

class FacturacionElectronicaNubox extends FacturacionElectronica {

	public static function ValidarFactura(Factura $Factura = null) {
		global $id_factura_padre, $RUT_cliente, $dte_codigo_referencia, $dte_razon_referencia;
		if (empty($Factura)) {
			global $pagina, $direccion_cliente, $ciudad_cliente, $comuna_cliente, $giro_cliente;
		} else {
			$campos = array(
				'RUT_cliente' => null,
				'direccion_cliente' => null,
				'ciudad_cliente' => null,
				'comuna_cliente' => null,
				'giro_cliente' => null
			);
			$datos = array_intersect_key($Factura->fields, $campos);
			extract($datos);
		}
		$errors = array();
		if (empty($RUT_cliente)) {
			$errors[] = __('Debe ingresar RUT del cliente.');
		} else {
			$arr_rut = explode('-', $RUT_cliente);
			$rut = $arr_rut[0];
			$dv = $arr_rut[1];
			if (!is_null($dv) && !Utiles::ValidarRut($rut, $dv)) {
				$errors[] = __('El RUT del cliente no es válido.');
			}
		}
		if (empty($direccion_cliente)) {
			$errors[] = __('Debe ingresar Dirección del cliente.');
		}
		if (strlen($direccion_cliente) > 60) {
			$errors[] = __('La Dirección del cliente supera los 60 caracteres.');
		}
		if (empty($comuna_cliente)) {
			$errors[] = __('Debe ingresar Comuna del cliente.');
		}
		if (empty($ciudad_cliente)) {
			$errors[] = __('Debe ingresar Ciudad del cliente.');
		}
		if (empty($giro_cliente)) {
			$errors[] = __('Debe ingresar ' . __('Giro') . ' del cliente.');
		}
		if ($id_factura_padre  > 0) {
			if (empty($dte_codigo_referencia)) {
				$errors[] = __('Debe seleccionar Referencia');
			}
			if (empty($dte_razon_referencia)) {
				$errors[] = __('Debe ingresar razón de la Referencia');
			}
		}
		if (isset($pagina)) {
			foreach ($errors as $msg) {
				$pagina->AddError($msg);
			}
		} else {
			return $errors;
		}
	}

	public static function InsertaMetodoPago() {
		global $factura, $contrato, $buscar_padre;
		$Sesion = new Sesion();
		if ($buscar_padre) {
			echo "<tr>";
			echo "<td align='right'>Referencia</td>";
			echo "<td align='left' colspan='3'>";
			echo Html::SelectQuery($Sesion, "SELECT id_codigo, glosa FROM prm_codigo WHERE grupo = 'PRM_FACTURA_CL_REF' ORDER BY glosa ASC", "dte_codigo_referencia", $factura->fields['dte_codigo_referencia'], "", "Sleccione", "300");
			echo "</td>";
			echo "</tr>";

			echo '<tr>';
			echo '<td align="right" colspan="1">Raz&oacute;n Referencia';
			echo '<td align="left" colspan="3">';
			echo "<input type='text' name='dte_razon_referencia' placeholder='Raz&oacute;n Referencia' value='" . $factura->fields['dte_razon_referencia'] . "' id='dte_razon_referencia' size='40' maxlength='90'>";
			echo '</td>';
			echo '</tr>';
		}
	}


	public static function InsertaJSFacturaElectronica() {
		$BotonDescargarHTML = self::BotonDescargarHTML('0');
		echo <<<EOF
			jQuery(document).on("click", ".factura-electronica", function() {
				if (!confirm("¿Confirma la generación de Factura electrónica?")) {
					return;
				}
				var self = jQuery(this);
				var id_factura = self.data("factura");
				var loading = jQuery("<span/>", {class: "loadingbar", style: "float:left;position:absolute;width:95px;height:20px;margin-left:-90px;"});
				self.parent().append(loading);
				jQuery.ajax({url: root_dir + "/api/index.php/invoices/" + id_factura +  "/build",
					type: "POST"
				}).success(function(data) {
					loading.remove();
					buttons = jQuery('{$BotonDescargarHTML}');
					buttons.each(function(i, e) { jQuery(e).attr("data-factura", id_factura)});
					self.replaceWith(buttons);
				}).error(function(error_data){
					loading.remove();
					response = JSON.parse(error_data.responseText);
					if (response.errors) {
						error_message = response.errors[0].message;
						alert(error_message);
					}
				});
			});

			jQuery(document).on("click", ".factura-documento", function() {
				var self = jQuery(this);
				var id_factura = self.data("factura");
				var format = self.data("format") || "pdf";
				window.location = root_dir + "/api/index.php/invoices/" + id_factura +  "/document?format=" + format
			});

			jQuery(document).on("change", "#dte_metodo_pago",  function() {
				var metodo_pago = jQuery("#dte_metodo_pago option:selected").text();
				if (metodo_pago != "No Identificado") {
					jQuery("#dte_metodo_pago_cta").show();
				} else {
					jQuery("#dte_metodo_pago_cta").hide();
				}
			});

			jQuery("#dte_metodo_pago").trigger("change");
EOF;
	}

	public static function GeneraFacturaElectronica(&$hookArg) {
		$Sesion = new Sesion();
		$Factura = $hookArg['Factura'];
		if (!empty($Factura->fields['dte_url_pdf'])) {
			$hookArg['InvoiceURL'] = $Factura->fields['dte_url_pdf'];
		} else {
			try {
				$errores = self::ValidarFactura($Factura);
				if (!empty($errores)) {
					throw new Exception(implode("\n", $errores), 0);
				}
				$Estudio = new PrmEstudio($Sesion);
				$Estudio->Load($Factura->fields['id_estudio']);
				$rut = $Estudio->GetMetaData('rut');
				$login = $Estudio->GetMetadata('facturacion_electronica');
				$WsFacturacionNubox = new WsFacturacionNubox($rut, $login);
				if ($WsFacturacionNubox->hasError()) {
					$hookArg['Error'] = self::ParseError($WsFacturacionNubox, $WsFacturacionNubox->getErrorCode());
				} else {
					$csv_documento = self::FacturaToCsv($Sesion, $Factura, $Estudio);
					$csv_referencias = ($Factura->fields['id_factura_padre'] > 0) ? self::ReferenciaToCsv($Sesion, $Factura, $Estudio) : null;
					$opcionFolios = 1; //los folios son asignados por nubox
					$opcionRutClienteExiste = 0; //se toman los datos del sistema nubox
					$opcionRutClienteNoExiste = 1; //se agrega al sistema nubox
					$hookArg['ExtraData'] = $csv_documento;
					try {
						$result = $WsFacturacionNubox->emitirFactura($csv_documento, $opcionFolios, $opcionRutClienteExiste, $opcionRutClienteNoExiste, $csv_referencias);
						if ($WsFacturacionNubox->hasError()) {
							$hookArg['Error'] = self::ParseError($WsFacturacionNubox, 'BuildingInvoiceError');
						} else {
							try {
								$Factura->Edit('numero', $result['Folio']);
								$Factura->Edit('dte_url_pdf', $result['Identificador']);
								$Factura->Edit('dte_fecha_creacion', date('Y-m-d H:i:s'));
								if ($Factura->Write()) {
									$hookArg['InvoiceURL'] = $file_url;
								}
							} catch (Exception $ex) {
								$hookArg['Error'] = self::ParseError($ex, 'BuildingInvoiceError');
							}
						}
					} catch (Exception $ex) {
						throw $ex;
					}
				}
			} catch (Exception $ex) {
				$hookArg['Error'] = self::ParseError($ex, 'BuildingInvoiceError');
			}
		}
		return $hookArg;
	}

	public static function ParseError($result, $error_code) {
		$error_description = null;
		if (is_a($result, 'Exception')) {
			$error_log = $result->__toString();
			if ($result->getCode() === 0) {
				$error_description = $result->getMessage();
			}
		} else {
			$error_description = $result->getErrorMessage();
			$error_log = $error_description;
		}
		return array(
			'Code' => $error_code,
			'Message' => $error_description
		);
	}

	/**
	 * Genera CSV de datos de la factura para enviar a Nubox
	 * @param Sesion $Sesion
	 * @param Factura $Factura
	 * @return array
	 */
	public static function FacturaToCsv(Sesion $Sesion, Factura $Factura, PrmEstudio $Estudio) {
		$subtotal_factura = $Factura->fields['subtotal'] + $Factura->fields['subtotal_gastos'] + $Factura->fields['subtotal_gastos_sin_impuesto'];
		$PrmDocumentoLegal = new PrmDocumentoLegal($Sesion);
		$PrmDocumentoLegal->Load($Factura->fields['id_documento_legal']);

		$tipoDTE = $PrmDocumentoLegal->fields['codigo_dte'];
		$afecto = $PrmDocumentoLegal->fields['documento_afecto'] ? 'SI' : 'NO';
		$detalle_facturas = array();

		$Moneda = new Moneda($Sesion);
		$Moneda->Load($Factura->fields['id_moneda']);

		if ($Factura->fields['subtotal'] > 0) {
			$detalle_facturas[] = array(
				'producto' => 'honorarios',
				'descripcion' => preg_replace("/[\n\r]+/", ' - ', $Factura->fields['descripcion']),
				'cantidad' => 1,
				'precio_unitario' => $Moneda->getFloat($Factura->fields['subtotal'])
			);
		}

		if ($Factura->fields['subtotal_gastos'] > 0) {
			$detalle_facturas[] = array(
				'producto' => 'gastos',
				'descripcion' => preg_replace("/[\n\r]+/", ' - ', $Factura->fields['descripcion_subtotal_gastos']),
				'cantidad' => 1,
				'precio_unitario' => $Moneda->getFloat($Factura->fields['subtotal_gastos'])
			);
		}

		if ($Factura->fields['subtotal_gastos_sin_impuesto'] > 0) {
			$detalle_facturas[] = array(
				'producto' => 'gastos exentos',
				'descripcion' => preg_replace("/[\n\r]+/", ' - ', $Factura->fields['descripcion_subtotal_gastos_sin_impuesto']),
				'cantidad' => 1,
				'precio_unitario' => $Moneda->getFloat($Factura->fields['subtotal_gastos_sin_impuesto'])
			);
		}

		$arrayFactura = array();
		$item = 0;
		foreach ($detalle_facturas as $detalle_factura) {
			$arrayFactura[] = array(
				'TIPO' => $tipoDTE,
				'FOLIO' => $Factura->fields['numero'],
				'SECUENCIA' => ++$item,
				'FECHA' => Utiles::sql2date($Factura->fields['fecha'], '%d-%m-%Y'),
				'RUT' => preg_replace('/\./', '', $Factura->fields['RUT_cliente']),
				'RAZONSOCIAL' => UtilesApp::transliteration($Factura->fields['cliente']),
				'GIRO' => substr(UtilesApp::transliteration($Factura->fields['giro_cliente']), 0, 40),
				'COMUNA' => UtilesApp::transliteration($Factura->fields['comuna_cliente']),
				'DIRECCION' => UtilesApp::transliteration($Factura->fields['direccion_cliente']),
				'AFECTO' => $afecto,
				'PRODUCTO' => $detalle_factura['producto'],
				'DESCRIPCION' => $detalle_factura['descripcion'],
				'CANTIDAD' => $detalle_factura['cantidad'],
				'PRECIO' => $Moneda->getFloat($detalle_factura['precio_unitario']),
				'PORCENTDSCTO' => 0,
				'EMAIL' => '',
				'TIPOSERVICIO' => 3,
				'PERIODODESDE' => '',
				'PERIODOHASTA' => '',
				'FECHAVENCIMIENTO' => '',
				'CODSUCURSAL' => 1,
				'VENDEDOR' => '',
				'CODRECEPTOR' => '',
				'CODITEM' => '',
				'UNIDADMEDIDA' => 'UNID',
				'PORCENTDSCTO2' => 0,
				'PORCENTDSCTO3' => 0,
				'CODIGOIMP' => '',
				'MONTOIMP' => '',
				'INDICADORTRASLADO' => '',
				'FORMAPAGO' => '',
				'MEDIOPAGO' => '',
				'TERMINOSPAGOSDIAS' => '',
				'TERMINOSPAGOCODIGO' => '',
				'COMUNADESTINO' => '',
				'RUTSOLICITANTEFACTURA' => ''
			);
		}

		if (!empty($arrayFactura)) {
			array_unshift($arrayFactura, array_keys($arrayFactura[0]));
		}
		foreach ($arrayFactura as $key => $item) {
			$arrayFactura[$key] = implode(';', $item);
		}
		return implode("\n", $arrayFactura);
	}


	/**
	 * Genera CSV de datos de la factura padre de una ND o NC
	 * @param Sesion $Sesion
	 * @param Factura $Factura
	 * @return array
	 */
	public static function ReferenciaToCsv(Sesion $Sesion, Factura $Factura, PrmEstudio $Estudio) {
		$subtotal_factura = $Factura->fields['subtotal'] + $Factura->fields['subtotal_gastos'] + $Factura->fields['subtotal_gastos_sin_impuesto'];
		$PrmDocumentoLegal = new PrmDocumentoLegal($Sesion);
		$PrmDocumentoLegalPadre = new PrmDocumentoLegal($Sesion);
		$PrmDocumentoLegal->Load($Factura->fields['id_documento_legal']);

		$tipoDTE = $PrmDocumentoLegal->fields['codigo_dte'];
			
		$FacturaPadre = new Factura($Sesion);
		$FacturaPadre->Load($Factura->fields['id_factura_padre']);
		$PrmDocumentoLegalPadre->Load($FacturaPadre->fields['id_documento_legal']);
		$tipoDTEPadre = $PrmDocumentoLegalPadre->fields['codigo_dte'];
		$referenciaId = $Factura->fields['dte_codigo_referencia'];
		$Referencia = new PrmCodigo($Sesion);
		$Referencia->Load($referenciaId);
		$codigoReferencia = $Referencia->Loaded() ? $Referencia->fields['codigo'] : 1;

		$arrayFactura[] = array(
			'TIPO' => $tipoDTE,
			'FOLIO' => $Factura->fields['numero'],
			'SECUENCIA' => 1,
			'TIPODOCUMENTOREFERENCIADO' => $tipoDTEPadre,
			'FOLIODOCUMENTOREFERENCIADO' => $FacturaPadre->fields['numero'],
			'FECHADOCUMENTOREFERENCIADO' => Utiles::sql2date($FacturaPadre->fields['fecha'], '%d-%m-%Y'),
			'MOTIVOREFERENCIA' => $codigoReferencia,
			'GLOSAREFERENCIA' => $Factura->fields['dte_razon_referencia']
		);

		if (!empty($arrayFactura)) {
			array_unshift($arrayFactura, array_keys($arrayFactura[0]));
		}
		foreach ($arrayFactura as $key => $item) {
			$arrayFactura[$key] = implode(';', $item);
		}
		return implode("\n", $arrayFactura);
	}

	/**
	 * Descarga archivo PDF
	 * @param type $hookArg
	 */
	public static function DescargarPdf($hookArg) {
		$Factura = $hookArg['Factura'];
		$id = $Factura->fields['dte_url_pdf'];
		if (empty($id)) {
			throw new Exception('Identificador no valido.');
		}
		$Estudio = new PrmEstudio($Factura->sesion);
		$Estudio->Load($Factura->fields['id_estudio']);
		$rut = $Estudio->GetMetaData('rut');
		$login = $Estudio->GetMetadata('facturacion_electronica');
		$WsFacturacionNubox = new WsFacturacionNubox($rut, $login);
		if ($WsFacturacionNubox->hasError()) {
			throw new Exception($WsFacturacionNubox->getErrorMessage(), $WsFacturacionNubox->getErrorCode());
		}
		$result = $WsFacturacionNubox->getPdf($id);
		if ($WsFacturacionNubox->hasError()) {
			throw new Exception($WsFacturacionNubox->getErrorMessage(), $WsFacturacionNubox->getErrorCode());
		}
		$name = sprintf('Factura_%s.pdf', $Factura->obtenerNumero());
		header("Content-Transfer-Encoding: binary");
		header("Content-Type: application/pdf");
		header('Content-Description: File Transfer');
		header("Content-Disposition: attachment; filename=$name");
		echo $result->ObtenerPDFResult;
		exit;
	}

}
