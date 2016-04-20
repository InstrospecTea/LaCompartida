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

	/**
  * Descarga archivo PDF
  * @param type $hookArg
	*/
	public static function DescargarPdf($hookArg) {
		$Factura = $hookArg['Factura'];
		if (!empty($Factura->fields['dte_url_pdf']) && $hookArg['original'] == true) {
			$hookArg['InvoiceURL'] = $Factura->fields['dte_url_pdf'];
		} else {
			$Sesion = new Sesion();
			$Estudio = new PrmEstudio($Sesion);
			$Estudio->Load($Factura->fields['id_estudio']);

			$rut = $Estudio->GetMetaData('rut');
			$usuario = $Estudio->GetMetadata('facturacion_electronica_cl.usuario');
			$password = $Estudio->GetMetadata('facturacion_electronica_cl.password');

			$WsFacturacionCl = new WsFacturacionCl;
			$WsFacturacionCl->setLogin($rut, $usuario, $password);
			if ($WsFacturacionCl->hasError()) {
				$hookArg['Error'] = self::parseError($WsFacturacionCl, $WsFacturacionCl->getErrorCode());
			} else {
				$arrayDocumento = self::FacturaToArray($Sesion, $Factura ,$Estudio);
				try {
					$result = $WsFacturacionCl->obtenerLink($arrayDocumento['folio'], $arrayDocumento['tipo_dte'], $hookArg['original']);
					if (!$WsFacturacionCl->hasError()) {
						$hookArg['InvoiceURL'] = $result;
					} else {
						$hookArg['Error'] = self::parseError($WsFacturacionCl, 'BuildingInvoiceError');
					}
				} catch  (Exception $ex) {
					$hookArg['Error'] = self::parseError($ex, 'BuildingInvoiceError');
				}
			}
		}

		if (!is_null($hookArg['Error'])) {
			return $hookArg;
		} else {
			$PrmDocumentoLegal = new PrmDocumentoLegal($Factura->sesion);
			$PrmDocumentoLegal->Load($Factura->fields['id_documento_legal']);
			$docName = UtilesApp::slug($PrmDocumentoLegal->fields['glosa']);
			$name = sprintf('%s_%s.pdf', $docName, $Factura->obtenerNumero());
			header("Content-Transfer-Encoding: binary");
			header("Content-Type: application/pdf");
			header('Content-Description: File Transfer');
			header("Content-Disposition: attachment; filename=$name");
			echo file_get_contents($hookArg['InvoiceURL']);
			exit;
		}
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
		$WsFacturacionCl = new WsFacturacionCl;
		$WsFacturacionCl->setLogin($rut, $usuario, $password);
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
					$hookArg['Error'] = self::parseError($ex, 'SaveCanceledInvoiceError');
				}
			} else {
				$hookArg['Error'] = self::parseError($WsFacturacionCl, 'CancelGeneratedInvoiceError');
				$mensaje = "Usted ha solicitado anular un Documento Tributario. Este proceso puede tardar y mientras esto ocurre, anularemos la factura en Time Billing para que usted pueda volver a generar el documento correctamente.";
				$estado_dte = Factura::$estados_dte['ProcesoAnular'];
				$Factura->Edit('dte_estado', $estado_dte);
				$Factura->Edit('dte_estado_descripcion', $mensaje);
				$Factura->Write();
			}
		}
		return $hookArg;
	}

}
