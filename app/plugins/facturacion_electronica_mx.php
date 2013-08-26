<?php
/**
 * Ofrece exportar las facturas al WS de Facturación electrónica México
 *
 * @package The Time Billing
 * @subpackage Plugins
 */

require_once dirname(__FILE__) . '/../conf.php';
$Slim = Slim::getInstance('default', true);
$Slim->hook('hook_factura_javascript_after', 'InsertaJSFacturaElectronica');
$Slim->hook('hook_cobro6_javascript_after', 'InsertaJSFacturaElectronica');
$Slim->hook('hook_cobros7_botones_after',  function($hookArg) {
	return AgregarBotonFacturaElectronica($hookArg);
});
$Slim->hook('hook_genera_factura_electronica', function($hookArg) {
	return GeneraFacturaElectronica($hookArg);
});
$Slim->hook('hook_anula_factura_electronica', function($hookArg) {
	return AnulaFacturaElectronica($hookArg);
});

function InsertaJSFacturaElectronica() {
	echo 'jQuery(document).on("click", ".factura-electronica", function() {
		if (!confirm("¿Confirma la generación de Factura electrónica?")) {
			return;
		}
		var self = jQuery(this);
		var id_factura = self.data("factura");
		var loading = jQuery("<span/>", {class: "loadingbar", style: "float:left;position:absolute;width:85px;height:20px;margin-left:-80px;"});
		self.parent().append(loading);
		jQuery.ajax({url: root_dir + "/api/index.php/invoices/" + id_factura +  "/build",
			type: "POST"
		}).success(function(data) {
			loading.remove();
			buttons = jQuery("' . BotonDescargarHTML("0") . '");
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
}

function BotonGenerarHTML($id_factura) {
	$img_dir = Conf::ImgDir();
	$content = "<a style = 'margin-left: 8px;margin-right: 8px;text-decoration: none;' title = 'Generar Factura Electrónica' class = 'factura-electronica' data-factura = '$id_factura' href = '#' >
			<img src = '$img_dir/invoice.png' border='0' />
		</a>";
	return $content;
}

function BotonDescargarHTML($id_factura) {
	$img_dir = Conf::ImgDir();
	$content  = "<a class = 'factura-documento' data-factura = '$id_factura' href = '#' >	<img src='$img_dir/pdf.gif' border='0' /></a>";
	$content .= "<a class = 'factura-documento' data-format = 'xml' data-factura = '$id_factura' href = '#' > <img src='$img_dir/xml.gif' border='0' /></a>";
	return $content;
}

function AgregarBotonFacturaElectronica($hookArg) {
	$Factura = $hookArg['Factura'];
	if ($Factura->FacturaElectronicaCreada()){
		$hookArg['content'] = BotonDescargarHTML($Factura->fields['id_factura']);
	} elseif (!$Factura->Anulada()) {
		$hookArg['content'] = BotonGenerarHTML($Factura->fields['id_factura']);
	}
	return $hookArg;
}

function GeneraFacturaElectronica($hookArg) {
	$Sesion = new Sesion();
	$Factura = $hookArg['Factura'];
	if (!empty($Factura->fields['dte_url_pdf'])) {
		$hookArg['InvoiceURL'] = $Factura->fields['dte_url_pdf'];
	} else {
		$client = new SoapClient("https://www.facturemosya.com:443/webservice/sRecibirXML.php?wsdl");
		$usuario = Conf::GetConf($Sesion, 'FacturacionElectronicaUsuario');
		$password = Conf::GetConf($Sesion, 'FacturacionElectronicaPassword');
		$strdocumento = FacturaToTXT($Sesion, $Factura);
		$result = $client->RecibirTXT($usuario, $password, $strdocumento);
		if ($result->codigo == 201) {
			try {
				$Factura->Edit('dte_xml', $result->descripcion);
				$Factura->Edit('dte_fecha_creacion', date('Y-m-d H:i:s'));
				$Factura->Edit('dte_firma', $result->timbrefiscal);

				$file_name = '/dtes/' . Utiles::sql2date($Factura->fields['fecha'], "%Y%m%d") . "_{$Factura->fields['serie_documento_legal']}-{$Factura->fields['numero']}.pdf";
				$file_data = base64_decode($result->documentopdf);
				$file_url = UtilesApp::UploadToS3($file_name, $file_data, 'application/pdf');

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

function AnulaFacturaElectronica($hookArg) {
	$Sesion = new Sesion();
	$Factura = $hookArg['Factura'];
	/*$hookArg['Error'] = array(
		'Code' => 'CancelGeneratedInvoiceError',
		'Message' => print_r($ex, true)
	);*/
	$Factura->Edit('dte_fecha_anulacion', date('Y-m-d H:i:s'));
	return $hookArg;

	$client = new SoapClient("https://www.facturemosya.com:443/webservice/sRecibirXML.php?wsdl");
	$usuario = Conf::GetConf($Sesion, 'FacturacionElectronicaUsuario');
	$password = Conf::GetConf($Sesion, 'FacturacionElectronicaPassword');
	$strdocumento = FacturaToTXT($Sesion, $Factura);
	$result = $client->RecibirTXT($usuario, $password, $strdocumento);
	if ($result->codigo == 201) {
		try {
			$Factura->Edit('dte_xml', $result->descripcion);
			$Factura->Edit('dte_fecha_creacion', date('Y-m-d H:i:s'));
			$Factura->Edit('dte_firma', $result->timbrefiscal);

			$file_name = '/dtes/' . Utiles::sql2date($Factura->fields['fecha'], "%Y%m%d") . "_{$Factura->fields['serie_documento_legal']}-{$Factura->fields['numero']}.pdf";
			$file_data = base64_decode($result->documentopdf);
			$file_url = UtilesApp::UploadToS3($file_name, $file_data, 'application/pdf');

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
	return $hookArg;
}

//
// $strdocumento = 'COM|||version|3.2||serie|WS||folio|15||fecha|2013-07-18T10:14:49||formaDePago|PAGO EN UNA SOLA EXHIBICION||TipoCambio|1.000||condicionesDePago|EFECTOS FISCALES AL PAGO||subTotal|425.00||Moneda|MX||total|493.00||tipoDeComprobante|ingreso||metodoDePago|PAGO NO IDENTIFICADO||LugarExpedicion|MEXICO DISTRITO FEDERAL||NumCtaPago|1234||descuento|0.00||motivoDescuento|desc
// REF|||Regimen|REGIMEN GENERAL DE LEY PERSONAS MORALES
// REC|||rfc|DNM070221BS4||nombre|DISEÑ€˜OS NAOMI MEXICO, S.A. DE C.V.
// DOR|||calle|JOSE MARIA IZAZAGA # 50 DESP 101 1ER PISO||noExterior|51||colonia|CENTRO||municipio|CUAHUTEMOC||estado|MEXICO, D.F.||pais|MEXICO||codigopostal|06000
// CON|||cantidad|850||unidad|M||noIdentificacion|6XO959455C-BRU||descripcion|COLA DE RATA X  METRO||valorUnitario|0.50||descuento|0||importe|425.00
// CUP|||numero|A-1234
// RET|||impuesto|IVA||importe|0
// TRA|||impuesto|IVA||tasa|16.0||importe|68.00
// ADI|||numorden|111111||comentarios|demo comentarios';
//
//
function FacturaToTXT(Sesion $Sesion, Factura $Factura) {
	$monedas = Moneda::GetMonedas($Sesion);

	$r = array(
		'COM' => array(
			'version|3.2',
			'serie|' . $Factura->fields['serie_documento_legal'],
			'folio|' . $Factura->fields['numero'],
			'fecha|' . Utiles::sql2date($Factura->fields['fecha'], '%Y-%m-%dT%H:%M:%S'),
			'formaDePago|' . 'PAGO EN UNA SOLA EXHIBICION',
			'TipoCambio|' . number_format($Factura->fields['tipo_cambio'], 2, '.', ''),
			'condicionesDePago|' . 'EFECTOS FISCALES AL PAGO', // $Factura->fields['condicion_pago'],
			'subTotal|' . number_format($Factura->fields['subtotal'], 2, '.', ''),
			'Moneda|' . $monedas[$Factura->fields['id_moneda']]['codigo'],
			'metodoDePago|' . 'Depósito en Cuenta',
			'total|' . number_format($Factura->fields['total'], 2, '.', ''),
			'LugarExpedicion|' . 'México Distrito Federal',
			'tipoDeComprobante|ingreso'
		),
		'REF' => array(
			'Regimen|' . 'Persona física régimen general'
		),
		'REC' => array(
			'rfc|' . 'DNM070221BS4', //$Factura->fields['RUT_cliente'],
			'nombre|' . utf8_encode($Factura->fields['cliente'])
		),
		'DOR' => array(
			'calle|' . utf8_encode($Factura->fields['direccion_cliente']),
			'colonia|' . utf8_encode($Factura->fields['comuna_cliente']),
			'municipio|' . utf8_encode($Factura->fields['comuna_cliente']),
			//'estado|' . utf8_encode($Factura->fields['ciudad_cliente']),
			'pais|' . utf8_encode($Factura->fields['ciudad_cliente']),
			'codigoPostal|' . $Factura->fields['factura_codigopostal'],
		),
		'TRA' => array(
			'impuesto|IVA',
			'tasa|' . number_format($Factura->fields['porcentaje_impuesto'], 2, '.', ''),
			'importe|' . number_format($Factura->fields['iva'], 2, '.', '')
		)
	);

	if ($Factura->fields['subtotal'] > 0) {
		$r['CON_honorarios'] = array(
			'cantidad|1.00',
			'unidad|un',
			'descripcion|' . utf8_encode($Factura->fields['descripcion']),
			'valorUnitario|' . number_format($Factura->fields['subtotal'], 2, '.', ''),
			'importe|' . number_format($Factura->fields['subtotal'], 2, '.', ''),
			'descuento|0.00'
		);
	}
	if ($Factura->fields['subtotal_gastos'] > 0) {
		$r['CON_gastos_con_iva'] = array(
			'cantidad|1.00',
			'unidad|un',
			'descripcion|' . utf8_encode($Factura->fields['descripcion_subtotal_gastos']),
			'valorUnitario|' . number_format($Factura->fields['subtotal_gastos'], 2, '.', ''),
			'importe|' . number_format($Factura->fields['subtotal_gastos'], 2, '.', ''),
			'descuento|0.00'
		);
	}
	if ($Factura->fields['subtotal_gastos_sin_impuesto'] > 0) {
		$r['CON_gastos_sin_iva'] = array(
			'cantidad|1.00',
			'unidad|un',
			'descripcion|' . utf8_encode($Factura->fields['descripcion_subtotal_gastos_sin_impuesto']),
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

	return utf8_encode($txt);
}
