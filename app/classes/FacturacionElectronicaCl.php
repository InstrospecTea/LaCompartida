<?php

class FacturacionElectronicaCl extends FacturacionElectronica {

	public static function ValidarFactura() {
		global $pagina, $RUT_cliente, $direccion_cliente, $ciudad_cliente, $comuna_cliente, $giro_cliente;
		if (empty($RUT_cliente)) {
			$pagina->AddError(__('Debe ingresar RUT del cliente.'));
		}
		if (empty($direccion_cliente)) {
			$pagina->AddError(__('Debe ingresar Dirección del cliente.'));
		}
		if (empty($comuna_cliente)) {
			$pagina->AddError(__('Debe ingresar Comuna del cliente.'));
		}
		if (empty($ciudad_cliente)) {
			$pagina->AddError(__('Debe ingresar Ciudad del cliente.'));
		}
		if (empty($giro_cliente)) {
			$pagina->AddError(__('Debe ingresar Giro del cliente.'));
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

	public static function GeneraFacturaElectronica($hookArg) {
		$Sesion = new Sesion();
		$Factura = $hookArg['Factura'];
		if (!empty($Factura->fields['dte_url_pdf'])) {
			$hookArg['InvoiceURL'] = $Factura->fields['dte_url_pdf'];
		} else {
			$Estudio = new PrmEstudio($Sesion);
			$Estudio->Load($Factura->fields['id_estudio']);
			$rut = $Estudio->GetMetaData('rut');
			$usuario = $Estudio->GetMetadata('facturacion_electronica_cl.usuario');
			$password = $Estudio->GetMetadata('facturacion_electronica_cl.password');
			$WsFacturacionCl = new WsFacturacionCl($rut, $usuario, $password);
			if ($WsFacturacionCl->hasError()) {
				$hookArg['Error'] = self::ParseError($WsFacturacionCl, $WsFacturacionCl->getErrorCode());
			} else {
				$arrayDocumento = self::FacturaToArray($Sesion, $Factura ,$Estudio);
				$hookArg['ExtraData'] = $arrayDocumento;
				try {
					$result = $WsFacturacionCl->emitirFactura($arrayDocumento);
					if (!$WsFacturacionCl->hasError()) {
						try {
							$Factura->Edit('dte_xml', $result['Detalle']['Documento']['xmlDTE']);
							$Factura->Edit('dte_fecha_creacion', date('Y-m-d H:i:s'));
							$file_url = $result['Detalle']['Documento']['urlPDF'];
							$Factura->Edit('dte_url_pdf', $file_url);
							if ($Factura->Write()) {
								$hookArg['InvoiceURL'] = $file_url;
							}
						} catch (Exception $ex) {
							$hookArg['Error'] = self::ParseError($ex, 'BuildingInvoiceError');
						}
					} else {
						$hookArg['Error'] = self::ParseError($WsFacturacionCl, 'BuildingInvoiceError');
					}
				} catch  (Exception $ex) {
					$hookArg['Error'] = self::ParseError($ex, 'BuildingInvoiceError');
				}
			}
		}
		return $hookArg;
	}


	public static function ParseError($result, $error_code) {
		$error_description = null;
		if (is_a($result, 'Exception')) {
			$error_log = $result->__toString();
		} else {

			$error_description = utf8_decode($result->getErrorMessage());
			$error_log = $error_description;
		}
		Log::write($error_log, "FacturacionElectronicaCl");
		return array(
			'Code' =>  $error_code,
			'Message' => $error_description
		);
	}

	public static function AnulaFacturaElectronica($hookArg) {
		$Sesion = new Sesion();
		$Factura = $hookArg['Factura'];

		if (!$Factura->FacturaElectronicaCreada()) {
			return $hookArg;
		}

		$Estudio = new PrmEstudio($Sesion);
		$Estudio->Load($Factura->fields['id_estudio']);
		$rut = $Estudio->GetMetaData('rut');
		$usuario = $Estudio->getMetadata('facturacion_electronica_cl.usuario');
		$password = $Estudio->getMetadata('facturacion_electronica_cl.password');
		$WsFacturacionCl = new WsFacturacionCl($rut, $usuario, $password);
		if ($WsFacturacionCl->hasError()) {
			$hookArg['Error'] = array(
				'Code' => $WsFacturacionCl->getErrorCode(),
				'Message' => $WsFacturacionCl->getErrorMessage()
			);
		} else {
			$PrmDocumentoLegal = new PrmDocumentoLegal($Sesion);
			$PrmDocumentoLegal->Load($Factura->fields['id_documento_legal']);
			$tipoDTE = $PrmDocumentoLegal->fields['codigo_dte'];
			$WsFacturacionCl->anularFactura($Factura->fields['numero'], $tipoDTE);
			if (!$WsFacturacionCl->hasError()) {
				try {
					$estado_dte = Factura::$estados_dte['Anulado'];
					$Factura->Edit('dte_fecha_anulacion', date('Y-m-d H:i:s'));
					$Factura->Edit('dte_estado', $estado_dte);
					$Factura->Edit('dte_estado_descripcion', __(Factura::$estados_dte_desc[$estado_dte]));
					$Factura->Write();
				} catch (Exception $ex) {
					$hookArg['Error'] = self::ParseError($ex, 'SaveCanceledInvoiceError');
				}
			} else {
				$hookArg['Error'] = self::ParseError($WsFacturacionCl, 'CancelGeneratedInvoiceError');
				$mensaje = "Usted ha solicitado anular un Documento Tributario Electrónico. Este proceso puede tardar y mientras esto ocurre, anularemos la factura en Time Billing para que usted pueda volver a generar el documento correctamente.";
				$estado_dte = Factura::$estados_dte['ProcesoAnular'];
				$Factura->Edit('dte_estado', $estado_dte);
				$Factura->Edit('dte_estado_descripcion', $mensaje);
				$Factura->Write();
			}
		}
		return $hookArg;
	}

	/**
	 * Genera array de datos de la factura para enviar a Facturacion.cl
	 * @param Sesion $Sesion
	 * @param Factura $Factura
	 * @return array
	 */
	public static function FacturaToArray(Sesion $Sesion, Factura $Factura, PrmEstudio $Estudio) {
		$subtotal_factura = $Factura->fields['subtotal'] + $Factura->fields['subtotal_gastos'] + $Factura->fields['subtotal_gastos_sin_impuesto'];

		$PrmDocumentoLegal = new PrmDocumentoLegal($Sesion);
		$PrmDocumentoLegal->Load($Factura->fields['id_documento_legal']);
		$tipoDTE = $PrmDocumentoLegal->fields['codigo_dte'];
		$afecto = $PrmDocumentoLegal->fields['documento_afecto'];
		$arrayFactura = array(
			'tipo_dte' => $tipoDTE,
			'afecto' => $afecto,
			'fecha_emision' => Utiles::sql2date($Factura->fields['fecha'], '%Y-%m-%d'),
			'folio' => $Factura->fields['numero'],
			'monto_neto' => intval($subtotal_factura),
			'tasa_iva' => intval($Factura->fields['porcentaje_impuesto']),
			'monto_iva' => intval($Factura->fields['iva']),
			'monto_total' => intval($Factura->fields['total']),
			'emisor' => array(
				'rut' => $Estudio->GetMetaData('rut'),
				'razon_social' => $Estudio->GetMetaData('razon_social'),
				'giro' => $Estudio->GetMetaData('giro'),
				'codigo_actividad' => $Estudio->GetMetaData('codigo_actividad'),
				'direccion' => $Estudio->GetMetaData('direccion'),
				'comuna' => $Estudio->GetMetaData('comuna'),
				'cuidad' => $Estudio->GetMetaData('cuidad')
			),
			'receptor' => array(
				'rut' => $Factura->fields['RUT_cliente'],
				'razon_social' => UtilesApp::transliteration($Factura->fields['cliente']),
				'giro' => UtilesApp::transliteration($Factura->fields['giro_cliente']),
				'direccion' => UtilesApp::transliteration($Factura->fields['direccion_cliente']),
				'comuna' => UtilesApp::transliteration($Factura->fields['comuna_cliente']),
				'cuidad' => UtilesApp::transliteration($Factura->fields['ciudad_cliente'])
			),
			'detalle' => array()
		);

		if ($Factura->fields['subtotal'] > 0) {
			$arrayFactura['detalle'][] = array(
				'descripcion' => $Factura->fields['descripcion'],
				'cantidad' => 1,
				'precio_unitario' => (int) number_format($Factura->fields['subtotal'], 2, '.', '')
			);
		}

		if ($Factura->fields['subtotal_gastos'] > 0) {
			$arrayFactura['detalle'][] = array(
				'descripcion' => $Factura->fields['descripcion_subtotal_gastos'],
				'cantidad' => 1,
				'precio_unitario' => (int) number_format($Factura->fields['subtotal_gastos'], 2, '.', '')
			);
		}

		if ($Factura->fields['subtotal_gastos_sin_impuesto'] > 0) {
			$arrayFactura['detalle'][] = array(
				'descripcion' => $Factura->fields['descripcion_subtotal_gastos_sin_impuesto'],
				'cantidad' => 1,
				'precio_unitario' => (int) number_format($Factura->fields['subtotal_gastos_sin_impuesto'], 2, '.', '')
			);
		}

		return $arrayFactura;
	}

}