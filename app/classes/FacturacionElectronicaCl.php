<?php

class FacturacionElectronicaCl extends FacturacionElectronica {

	public static function InsertaMetodoPago() {
		$Html = self::getHtml();
		echo $Html->tag('pre', 'algo', array('style' => 'background-color: #f00'));
	}

	public static function InsertaJSFacturaElectronica() {
		echo 'jQuery(document).on("click", ".factura-electronica", function() {
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
				buttons = jQuery(\'' . self::BotonDescargarHTML("0") . '\');
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
		});';

		echo 'jQuery(document).on("click", ".factura-documento", function() {
			var self = jQuery(this);
			var id_factura = self.data("factura");
			var format = self.data("format") || "pdf";
			window.location = root_dir + "/api/index.php/invoices/" + id_factura +  "/document?format=" + format
		});';

		echo 'jQuery(document).on("change", "#dte_metodo_pago",  function() {
			var metodo_pago = jQuery("#dte_metodo_pago option:selected").text();
			if (metodo_pago != "No Identificado") {
				jQuery("#dte_metodo_pago_cta").show();
			} else {
				jQuery("#dte_metodo_pago_cta").hide();
			}
		});';

		echo 'jQuery("#dte_metodo_pago").trigger("change")';
	}

	public static function ValidarFactura() {
		global $pagina, $RUT_cliente, $direccion_cliente, $ciudad_cliente, $dte_metodo_pago, $dte_id_pais, $dte_metodo_pago_cta;
		if (empty($RUT_cliente)) {
			$pagina->AddError(__('Debe ingresar RFC del cliente.'));
		}
		if (empty($direccion_cliente)) {
			$pagina->AddError(__('Debe ingresar Dirección del cliente.'));
		}
		if (empty($ciudad_cliente)) {
			$pagina->AddError(__('Debe ingresar Ciudad del cliente.'));
		}
		if ((int) $dte_metodo_pago_cta < 1000 && (int) $dte_metodo_pago_cta > 0) {
			$pagina->AddError(__('El número de cuenta debe tener al menos 4 d&iacute;gitos'));
		}
	}

	public static function GeneraFacturaElectronica($hookArg) {
		$Sesion = new Sesion();
		$Factura = $hookArg['Factura'];
		if (!empty($Factura->fields['dte_url_pdf'])) {
			$hookArg['InvoiceURL'] = $Factura->fields['dte_url_pdf'];
		} else {
			$client = new SoapClient("https://www.facturemosya.com:443/webservice/sRecibirXML.php?wsdl");
			$estudio = new PrmEstudio($Sesion);
			$estudio->Load($Factura->fields['id_estudio']);
			$estudio_data = $estudio->getMetadata('facturacion_electronica_mx');
			$usuario = $estudio_data['usuario'];
			$password = $estudio_data['password'];
			$strdocumento = self::FacturaToTXT($Sesion, $Factura);
			$hookArg['ExtraData'] = $strdocumento;
			$result = $client->RecibirTXT($usuario, $password, UtilesApp::utf8izar($strdocumento), 0);
			if ($result->codigo == 201) {
				try {
					$Factura->Edit('dte_xml', $result->descripcion);
					$Factura->Edit('dte_fecha_creacion', date('Y-m-d H:i:s'));
					$Factura->Edit('dte_firma', $result->timbrefiscal);

					$file_name = '/dtes/' . Utiles::sql2date($Factura->fields['fecha'], "%Y%m%d") . "_{$Factura->fields['serie_documento_legal']}-{$Factura->fields['numero']}.pdf";
					$file_data = base64_decode($result->documentopdf);
					$file_url = UtilesApp::UploadToS3($Sesion, $file_name, $file_data, 'application/pdf');

					$Factura->Edit('dte_url_pdf', $file_url);
					if ($Factura->Write()) {
						$hookArg['InvoiceURL'] = $file_url;
					}
				} catch (Exception $ex) {
					$hookArg['Error'] = array(
						'Code' => 'SaveGeneratedInvoiceError',
						'Message' => print_r($ex, true)
					);
				}
			} else {
				$hookArg['Error'] = array(
					'Code' => 'BuildingInvoiceError',
					'Message' => utf8_decode($result->descripcion)
				);
			}
		}
		return $hookArg;
	}

	public static function AnulaFacturaElectronica($hookArg) {
		$Sesion = new Sesion();
		$Factura = $hookArg['Factura'];

		if (!$Factura->FacturaElectronicaCreada()) {
			return $hookArg;
		}

		$estudio = new PrmEstudio($Sesion);
		$estudio->Load($Factura->fields['id_estudio']);
		$estudio_data = $estudio->getMetadata('facturacion_electronica_mx');
		$usuario = $estudio_data['usuario'];
		$password = $estudio_data['password'];

		$firma = $Factura->fields['dte_firma'];
		$firma_parts = explode("|", $firma);
		$UUID = $firma_parts[1];

		$client = new SoapClient("https://www.facturemosya.com:443/webservice/sCancelarCFDI.php?wsdl");
		$result = $client->CancelarCFDI($usuario, $password, $UUID);
		if ($result->codigo == 201) {
			try {
				$Factura->Edit('dte_fecha_anulacion', date('Y-m-d H:i:s'));
				$Factura->Write();
			} catch (Exception $ex) {
				$hookArg['Error'] = array(
					'Code' => 'SaveCanceledInvoiceError',
					'Message' => print_r($ex, true)
				);
			}
		} else {
			$hookArg['Error'] = array(
				'Code' => 'CancelGeneratedInvoiceError',
				'Message' => utf8_decode($result->descripcion . " $UUID")
			);
		}
		return $hookArg;
	}

	public static function PaymentMethod(Sesion $Sesion, Factura $Factura) {
		if (is_null($Factura->fields['dte_metodo_pago']) || $Factura->fields['dte_metodo_pago'] == "") {
			return "No Identificado";
		}

		$sql = "SELECT `prm_codigo`.`glosa`
					FROM `prm_codigo`
					WHERE `prm_codigo`.`id_codigo` = :code_id
						AND `prm_codigo`.`grupo` = 'PRM_FACTURA_MX_METOD'";

		$Statement = $Sesion->pdodbh->prepare($sql);
		$Statement->bindParam('code_id', $Factura->fields['dte_metodo_pago']);
		$Statement->execute();

		$payment_method = $Statement->fetchObject();

		if (is_object($payment_method)) {
			return $payment_method->glosa;
		} else {
			return "No Identificado";
		}
	}

	/**
	 * $strdocumento = 'COM|||version|3.2||serie|WS||folio|15||fecha|2013-07-18T10:14:49||formaDePago|PAGO EN UNA SOLA EXHIBICION||TipoCambio|1.000||condicionesDePago|EFECTOS FISCALES AL PAGO||subTotal|425.00||Moneda|MX||total|493.00||tipoDeComprobante|ingreso||metodoDePago|PAGO NO IDENTIFICADO||LugarExpedicion|MEXICO DISTRITO FEDERAL||NumCtaPago|1234||descuento|0.00||motivoDescuento|desc
	 * REF|||Regimen|REGIMEN GENERAL DE LEY PERSONAS MORALES
	 * REC|||rfc|DNM070221BS4||nombre|DISEÑ€˜OS NAOMI MEXICO, S.A. DE C.V.
	 * DOR|||calle|JOSE MARIA IZAZAGA # 50 DESP 101 1ER PISO||noExterior|51||colonia|CENTRO||municipio|CUAHUTEMOC||estado|MEXICO, D.F.||pais|MEXICO||codigopostal|06000
	 * CON|||cantidad|850||unidad|M||noIdentificacion|6XO959455C-BRU||descripcion|COLA DE RATA X  METRO||valorUnitario|0.50||descuento|0||importe|425.00
	 * CUP|||numero|A-1234
	 * RET|||impuesto|IVA||importe|0
	 * TRA|||impuesto|IVA||tasa|16.0||importe|68.00
	 * ADI|||numorden|111111||comentarios|demo comentarios';
	 *
	 */
	public static function FacturaToTXT(Sesion $Sesion, Factura $Factura) {
		$monedas = Moneda::GetMonedas($Sesion, '', true);
		$mx_timezone = -6;
		$mx_hour = date("H:i:s", time() + 3600 * ($mx_timezone + date("I")));

		$PrmDocumentoLegal = new PrmDocumentoLegal($Sesion);
		$PrmDocumentoLegal->Load($Factura->fields['id_documento_legal']);
		$tipo_documento_legal = $PrmDocumentoLegal->fields['codigo'];
		$tipoComprobante = $tipo_documento_legal == 'NC' ? 'egreso' : 'ingreso';

		$r = array(
			'COM' => array(
				'version|3.2',
				'serie|' . $Factura->fields['serie_documento_legal'],
				'folio|' . $Factura->fields['numero'],
				'fecha|' . Utiles::sql2date($Factura->fields['fecha'] . ' ' . $mx_hour, '%Y-%m-%dT%H:%M:%S'),
				'formaDePago|' . 'PAGO EN UNA SOLA EXHIBICION',
				'TipoCambio|' . number_format($Factura->fields['tipo_cambio'], 2, '.', ''),
				'condicionesDePago|' . 'EFECTOS FISCALES AL PAGO', // $Factura->fields['condicion_pago'],
				'Moneda|' . ($monedas[$Factura->fields['id_moneda']]['codigo']),
				'metodoDePago|' . self::PaymentMethod($Sesion, $Factura),
				'total|' . number_format($Factura->fields['total'], 2, '.', ''),
				'LugarExpedicion|' . 'México Distrito Federal',
				'tipoDeComprobante|' . $tipoComprobante
			),
			'REF' => array(
				'Regimen|' . 'Régimen General de Ley, Personas Morales'
			),
			'REC' => array(
				'rfc|' . $Factura->fields['RUT_cliente'],
				'nombre|' . ($Factura->fields['cliente'])
			),
			'TRA' => array(
				'impuesto|IVA',
				'tasa|' . number_format($Factura->fields['porcentaje_impuesto'], 2, '.', ''),
				'importe|' . number_format($Factura->fields['iva'], 2, '.', '')
			)
		);

		if ($Factura->fields['subtotal'] > 0) {
			$r['COM'][] = 'subTotal|' . number_format($Factura->fields['subtotal'], 2, '.', '');
		}

		if ($Factura->fields['subtotal_gastos'] > 0) {
			$r['COM'][] = 'subTotal|' . number_format($Factura->fields['subtotal_gastos'], 2, '.', '');
		}

		if ($Factura->fields['subtotal_gastos_sin_impuesto'] > 0) {
			$r['COM'][] = 'subTotal|' . number_format($Factura->fields['subtotal_gastos_sin_impuesto'], 2, '.', '');
		}

		if (!is_null($Factura->fields['dte_metodo_pago_cta']) && !empty($Factura->fields['dte_metodo_pago_cta']) && (int) $Factura->fields['dte_metodo_pago_cta'] > 0) {
			$r['COM'][] = 'NumCtaPago|' . $Factura->fields['dte_metodo_pago_cta'];
		}

		if (!is_null($Factura->fields['direccion_cliente']) && !empty($Factura->fields['direccion_cliente'])) {
			$r['DOR'][] = 'calle|' . ($Factura->fields['direccion_cliente']);
		}

		if (!is_null($Factura->fields['comuna_cliente']) && !empty($Factura->fields['comuna_cliente'])) {
			$r['DOR'][] = 'municipio|' . ($Factura->fields['comuna_cliente']);
		}

		if (!is_null($Factura->fields['ciudad_cliente']) && !empty($Factura->fields['ciudad_cliente'])) {
			$r['DOR'][] = 'localidad|' . ($Factura->fields['ciudad_cliente']);
		}

		$pais = $Factura->GetPais();

		if (!is_null($pais) && !empty($pais)) {
			$r['DOR'][] = 'pais|' . $pais;
		}
		if (!is_null($Factura->fields['factura_codigopostal']) && !empty($Factura->fields['factura_codigopostal'])) {
			$r['DOR'][] = 'codigoPostal|' . ($Factura->fields['factura_codigopostal']);
		}

		if ($Factura->fields['subtotal'] > 0) {
			$r['CON_honorarios'] = array(
				'cantidad|1.00',
				'unidad|un',
				'descripcion|' . ($Factura->fields['descripcion']),
				'valorUnitario|' . number_format($Factura->fields['subtotal'], 2, '.', ''),
				'importe|' . number_format($Factura->fields['subtotal'], 2, '.', ''),
				'descuento|0.00'
			);
		}

		if ($Factura->fields['subtotal_gastos'] > 0) {
			$r['CON_gastos_con_iva'] = array(
				'cantidad|1.00',
				'unidad|un',
				'descripcion|' . ($Factura->fields['descripcion_subtotal_gastos']),
				'valorUnitario|' . number_format($Factura->fields['subtotal_gastos'], 2, '.', ''),
				'importe|' . number_format($Factura->fields['subtotal_gastos'], 2, '.', ''),
				'descuento|0.00'
			);
		}

		if ($Factura->fields['subtotal_gastos_sin_impuesto'] > 0) {
			$r['CON_gastos_sin_iva'] = array(
				'cantidad|1.00',
				'unidad|un',
				'descripcion|' . ($Factura->fields['descripcion_subtotal_gastos_sin_impuesto']),
				'valorUnitario|' . number_format($Factura->fields['subtotal_gastos_sin_impuesto'], 2, '.', ''),
				'importe|' . number_format($Factura->fields['subtotal_gastos_sin_impuesto'], 2, '.', ''),
				'descuento|0.00'
			);
		}

		foreach ($r as $identificador => $valores) {
			if (in_array($identificador, array('CON_honorarios', 'CON_gastos_con_iva', 'CON_gastos_sin_iva'))) {
				$identificador = 'CON';
			}
			$txt .= "$identificador|||";
			$txt .= implode('||', $valores);
			$txt .= "\n";
		}

		return $txt;
	}

}