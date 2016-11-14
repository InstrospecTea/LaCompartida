<?php

class FacturacionElectronicaMateriaSoftware extends FacturacionElectronica {

	public static function ValidarFactura() {
		$Sesion = new Sesion();
		global $pagina, $numero, $RUT_cliente, $cliente;

		if (empty($RUT_cliente)) {
			$pagina->AddError(__('Debe ingresar') . ' ' . __('ROL/RUT'));
		}
		if (empty($cliente)) {
			$pagina->AddError(__('Debe ingresar') . ' ' . __('Raz&oacute;n Social Cliente'));
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
		$Sesion = new Sesion();
		$Factura = $hookArg['Factura'];

		$DocumentoLegal = new PrmDocumentoLegal($Factura->sesion);
		$DocumentoLegal->Load($Factura->fields['id_documento_legal']);
		$docName = UtilesApp::slug($DocumentoLegal->fields['glosa']);
		$name = sprintf('%s_%s.pdf', $docName, $Factura->obtenerNumero());

		$Estudio = new PrmEstudio($Sesion);
		$Estudio->Load($Factura->fields['id_estudio']);

		$WsFacturacionMateriaSoftware = new WsFacturacionMateriaSoftware(
			$Estudio->GetMetaData('facturacion_electronica_materia_software.Url'),
			$Estudio->GetMetaData('facturacion_electronica_materia_software.Authorization')
		);

		$documento = json_decode($Factura->fields['dte_url_pdf']);

		$pdf = $WsFacturacionMateriaSoftware->GetStatus(
			$documento->Serie,
			(int) $documento->Correlativo
		);

		header("Content-Transfer-Encoding: binary");
		header("Content-Type: application/pdf");
		header('Content-Description: File Transfer');
		header("Content-Disposition: attachment; filename={$name}");
		echo base64_decode($pdf->PDF);
		exit;
	}

	public static function GeneraFacturaElectronica($hookArg) {
		$Sesion = new Sesion();
		$Factura = $hookArg['Factura'];

		if (!empty($Factura->fields['dte_url_pdf'])) {
			$hookArg['InvoiceURL'] = $Factura->fields['dte_url_pdf'];
		} else {
			$Estudio = new PrmEstudio($Sesion);
			$Estudio->Load($Factura->fields['id_estudio']);

			$Moneda = new Moneda($Sesion);
			$Moneda->Load($Factura->fields['id_moneda']);

			$WsFacturacionMateriaSoftware = new WsFacturacionMateriaSoftware(
				$Estudio->GetMetaData('facturacion_electronica_materia_software.Url'),
				$Estudio->GetMetaData('facturacion_electronica_materia_software.Authorization')
			);

			$DocumentoLegal = new PrmDocumentoLegal($Factura->sesion);
			$DocumentoLegal->Load($Factura->fields['id_documento_legal']);

			$documento = $WsFacturacionMateriaSoftware->documento($Factura, $Moneda, $DocumentoLegal);

			if ($WsFacturacionMateriaSoftware->hasError()) {
				$hookArg['Error'] = self::parseError($WsFacturacionMateriaSoftware, 'BuildingInvoiceError');
			} else {
				try {
					$Factura->Edit('dte_fecha_creacion', date('Y-m-d H:i:s'));
					$Factura->Edit('dte_url_pdf', json_encode($documento));
					if ($Factura->Write()) {
						$hookArg['InvoiceURL'] = json_encode($documento);
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
			$error_description = utf8_encode($result->getErrorMessage());
			$error_log = $error_description;
		}

		return array(
			'Code' => $error_code,
			'Message' => $error_description
		);
	}

}
