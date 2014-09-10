<?php

class FacturacionElectronicaNubox extends FacturacionElectronica {

	public static function ValidarFactura(Factura $Factura = null) {
		if (empty($Factura)) {
			global $pagina, $RUT_cliente, $direccion_cliente, $ciudad_cliente, $comuna_cliente, $giro_cliente;
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
		}
		if (empty($direccion_cliente)) {
			$errors[] = __('Debe ingresar Dirección del cliente.');
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

		if (isset($pagina)) {
			foreach ($errors as $msg) {
				$pagina->AddError($msg);
			}
		} else {
			return $errors;
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
					window.location = root_dir + "/api/index.php/invoices/" + id_factura +  "/document?format=pdf"
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
					$archivo = self::FacturaToCsv($Sesion, $Factura, $Estudio);
					$opcionFolios = 1; //los folios son asignados por nubox
					$opcionRutClienteExiste = 0; //se toman los datos del sistema nubox
					$opcionRutClienteNoExiste = 1; //se agrega al sistema nubox
					$hookArg['ExtraData'] = $csv_documento;
					try {
						$result = $WsFacturacionNubox->emitirFactura($archivo, $opcionFolios, $opcionRutClienteExiste, $opcionRutClienteNoExiste);
						if ($WsFacturacionNubox->hasError()) {
							$hookArg['Error'] = self::ParseError($WsFacturacionNubox, 'BuildingInvoiceError');
						} else {
							try {
								$Factura->Edit('numero', $result['FolioCargado']);
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
		Log::write($error_log, "FacturacionElectronicaCl");
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
		echo $result;
		exit;
	}

}
