<?php

class FacturacionElectronicaSatcom extends FacturacionElectronica {

	public static function ValidarFactura() {
		global $pagina, $RUT_cliente, $direccion_cliente, $ciudad_cliente, $comuna_cliente, $giro_cliente, $id_factura_padre, $dte_codigo_referencia, $dte_razon_referencia;
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
			$pagina->AddError(__('Debe ingresar ' . __('Giro') . ' del cliente.'));
		}
		if ($id_factura_padre  > 0) {
			if (empty($dte_codigo_referencia)) {
				$pagina->AddError(__('Debe seleccionar Referencia'));
			}
			if (empty($dte_razon_referencia)) {
				$pagina->AddError(__('Debe ingresar razón de la Referencia'));
			}
		}
	}

	public static function BotonDescargarHTML($id_factura) {
		$img_dir = Conf::ImgDir();
		$Html = self::getHtml();
		$img_pdf = $Html->img("{$img_dir}/pdf.gif", array('border' => 0));
		$output = $Html->tag('a', $img_pdf, array('title' => 'Descargar original', 'class' => 'factura-documento', 'data-factura' => $id_factura, 'data-original' => 1, 'href' => '#'));
		return $output;
	}

	public static function AgregarBotonFacturaElectronica($hookArg) {
		$Factura = $hookArg['Factura'];
		$id_factura = $Factura->fields['id_factura'];
		if ($Factura->FacturaElectronicaCreada()) {
			$hookArg['content'] = self::BotonDescargarHTML($id_factura);
		} elseif (!$Factura->Anulada()) {
			$hookArg['content'] = self::BotonGenerarHTML($id_factura);
		}
		return $hookArg;
	}

	public static function InsertaJSFacturaElectronica() {
		$BotonDescargarHTML = self::BotonDescargarHTML('0');
		echo <<<EOF
			jQuery(document).on("click", ".factura-electronica", function() {
				if (!confirm("¿Confirma la generación del documento electrónico?")) {
					return;
				}
				var self = jQuery(this);
				var id_factura = self.data("factura");
				var codigo_tipo_doc = self.data("codigo-tipo");
				var loading = jQuery("<span/>", {class: "loadingbar", style: "float:left;position:absolute;width:95px;height:20px;margin-left:-90px;"});
				self.parent().append(loading);
				jQuery.ajax({url: root_dir + "/api/index.php/invoices/" + id_factura +  "/build",
					type: "POST"
				}).success(function(data) {
					loading.remove();
					buttons = jQuery('{$BotonDescargarHTML}');
					buttons.each(function(i, e) {
						jQuery(e).attr("data-factura", id_factura);
					});
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
				var original = self.data("original");
				var format = self.data("format") || "pdf";
				window.location = root_dir + "/api/index.php/invoices/" + id_factura +  "/document?format=" + format  + "&original=" + original
			});
EOF;
	}

	/**
  * Descarga archivo PDF
  * @param type $hookArg
	*/
	public static function DescargarPdf($hookArg) {
		$factura = $hookArg['Factura'];

		$PrmDocumentoLegal = new PrmDocumentoLegal($factura->sesion);
		$PrmDocumentoLegal->Load($factura->fields['id_documento_legal']);
		$docName = UtilesApp::slug($PrmDocumentoLegal->fields['glosa']);
		$name = sprintf('%s_%s.pdf', $docName, $factura->obtenerNumero());

		$WsFacturacionSatcom = new WsFacturacionSatcom;
		$documento = $WsFacturacionSatcom->obtenerPdf($factura->fields['dte_url_pdf']);

		header("Content-Transfer-Encoding: binary");
		header("Content-Type: application/pdf");
		header('Content-Description: File Transfer');
		header("Content-Disposition: attachment; filename={$name}");
		echo $documento;
		exit;
	}

	public static function GeneraFacturaElectronica($hookArg) {
		$Sesion = new Sesion();
		$factura = $hookArg['Factura'];

		if (!empty($factura->fields['dte_url_pdf'])) {
			$hookArg['InvoiceURL'] = $factura->fields['dte_url_pdf'];
		} else {
			$Estudio = new PrmEstudio($Sesion);
			$Estudio->Load($factura->fields['id_estudio']);

			$factura->fields['RucEmisor'] = $Estudio->GetMetaData('facturacion_electronica_satcom.RucEmisor');
			$factura->fields['ClaveAcceso'] = $Estudio->GetMetadata('facturacion_electronica_satcom.ClaveAcceso');
			$factura->fields['Establecimiento'] = $Estudio->GetMetadata('facturacion_electronica_satcom.Establecimiento');
			$factura->fields['Punto'] = $Estudio->GetMetadata('facturacion_electronica_satcom.Punto');

			$WsFacturacionSatcom = new WsFacturacionSatcom;
			$documento = $WsFacturacionSatcom->emitirFactura($factura);

			if ($WsFacturacionSatcom->hasError()) {
				$hookArg['Error'] = self::parseError($WsFacturacionSatcom, 'BuildingInvoiceError');
			} else {
				try {
					$factura->Edit('dte_fecha_creacion', date('Y-m-d H:i:s'));
					$factura->Edit('dte_url_pdf', $documento);
					if ($factura->Write()) {
						$hookArg['InvoiceURL'] = $documento;
					}
				} catch (Exception $ex) {
					$hookArg['Error'] = self::parseError($ex, 'BuildingInvoiceError');
				}
			}
		}

		return $hookArg;
	}

	public static function parseError($result, $error_code) {
		$error_description = null;

		if (is_a($result, 'Exception')) {
			$error_log = $result->__toString();
		} else {
			$error_description = utf8_decode($result->getErrorMessage());
			$error_log = $error_description;
		}

		Log::write($error_log, "FacturacionElectronicaSatcom");

		return array(
			'Code' => $error_code,
			'Message' => $error_description
		);
	}

}
