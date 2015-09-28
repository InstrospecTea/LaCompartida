<?php

class FacturacionElectronicaCl extends FacturacionElectronica {

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

	public static function BotonDescargarHTMLCedible($id_factura) {
		$img_dir = Conf::ImgDir();
		$Html = self::getHtml();
		$img_pdf_copia = $Html->img("{$img_dir}/pdf.gif", array('border' => 0, 'style' => 'opacity:0.5;'));
		if (!empty($id_factura) && $id_factura != 0) {
			$sesion = new Sesion();
			$factura = new Factura($sesion);
			$factura->Load($id_factura);
			$tipo_doc = $factura->CodigoTipoDocumentoLegal();
			if (!empty($tipo_doc) && $tipo_doc != 'NC' && $tipo_doc != 'ND') {
				$output = $Html->tag('a', $img_pdf_copia, array('title' => 'Descargar copia cedible', 'class' => 'factura-documento factura-cedible', 'data-factura' => $id_factura, 'data-original' => 0, 'href' => '#'));
			}
		} else {
			$output = $Html->tag('a', $img_pdf_copia, array('title' => 'Descargar copia cedible', 'class' => 'factura-documento factura-cedible', 'data-factura' => $id_factura, 'data-original' => 0, 'href' => '#'));
		}
		return $output;
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
			$hookArg['content'] = self::BotonDescargarHTML($id_factura) . self::BotonDescargarHTMLCedible($id_factura);
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
		$BotonDescargarCedible = self::BotonDescargarHTMLCedible('0');
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
					if (codigo_tipo_doc == 'NC' || codigo_tipo_doc == 'ND') {
						buttons = jQuery('{$BotonDescargarHTML}');
					} else {
						buttons = jQuery('{$BotonDescargarHTML}{$BotonDescargarCedible}');
					}
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
				$hookArg['Error'] = self::ParseError($WsFacturacionCl, $WsFacturacionCl->getErrorCode());
			} else {
				$arrayDocumento = self::FacturaToArray($Sesion, $Factura ,$Estudio);
				try {
					$result = $WsFacturacionCl->obtenerLink($arrayDocumento['folio'], $arrayDocumento['tipo_dte'], $hookArg['original']);
					if (!$WsFacturacionCl->hasError()) {
						$hookArg['InvoiceURL'] = $result;
					} else {
						$hookArg['Error'] = self::ParseError($WsFacturacionCl, 'BuildingInvoiceError');
					}
				} catch  (Exception $ex) {
					$hookArg['Error'] = self::ParseError($ex, 'BuildingInvoiceError');
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
		$Factura = $hookArg['Factura'];
		if (!empty($Factura->fields['dte_url_pdf'])) {
			$hookArg['InvoiceURL'] = $Factura->fields['dte_url_pdf'];
		} else {
			$Estudio = new PrmEstudio($Sesion);
			$Estudio->Load($Factura->fields['id_estudio']);
			$rut = $Estudio->GetMetaData('rut');
			$usuario = $Estudio->GetMetadata('facturacion_electronica_cl.usuario');
			$password = $Estudio->GetMetadata('facturacion_electronica_cl.password');
			$WsFacturacionCl = new WsFacturacionCl;
			$WsFacturacionCl->setLogin($rut, $usuario, $password);
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
					$hookArg['Error'] = self::ParseError($ex, 'SaveCanceledInvoiceError');
				}
			} else {
				$hookArg['Error'] = self::ParseError($WsFacturacionCl, 'CancelGeneratedInvoiceError');
				$mensaje = "Usted ha solicitado anular un Documento Tributario. Este proceso puede tardar y mientras esto ocurre, anularemos la factura en Time Billing para que usted pueda volver a generar el documento correctamente.";
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

		$Contrato = new Contrato($Sesion);
		$Contrato->Load($Factura->fields['id_contrato']);

		if ($tipoDTE == 39 || $tipoDTE == 41) {
			$Cobro = new Cobro($Sesion);
			$Cobro->Load($Factura->fields['id_cobro'], array('id_cobro', 'fecha_ini', 'fecha_fin'));
			$fecha_desde = $Cobro->fields['fecha_ini'];
			if (empty($fecha_desde) || $fecha_desde == '0000-00-00') {
				$fecha_desde = $Cobro->fechaPrimerTrabajo();
			}
			$fecha_hasta = $Cobro->fields['fecha_fin'];
		}
		$arrayFactura = array(
			'tipo_dte' => $tipoDTE,
			'afecto' => $afecto,
			'fecha_emision' => Utiles::sql2date($Factura->fields['fecha'], '%Y-%m-%d'),
			'fecha_vencimiento' => Utiles::sql2date($Factura->fields['fecha_vencimiento'], '%Y-%m-%d'),
			'fecha_desde' => $fecha_desde,
			'fecha_hasta' => $fecha_hasta,
			'folio' => $Factura->fields['numero'],
			'monto_neto' => intval($subtotal_factura),
			'tasa_iva' => intval($Factura->fields['porcentaje_impuesto']),
			'monto_iva' => intval($Factura->fields['iva']),
			'monto_total' => intval($Factura->fields['total']),
			'condicion_pago' => $Factura->ObtieneGlosaCondicionPago(),
			'emisor' => array(
				'rut' => $Estudio->GetMetaData('rut'),
				'razon_social' => $Estudio->GetMetaData('razon_social'),
				'giro' => $Estudio->GetMetaData('giro'),
				'correo' => $Contrato->fields['email_contacto'],
				'codigo_actividad' => $Estudio->GetMetaData('codigo_actividad'),
				'direccion' => $Estudio->GetMetaData('direccion'),
				'comuna' => $Estudio->GetMetaData('comuna'),
				'cuidad' => $Estudio->GetMetaData('cuidad')
			),
			'receptor' => array(
				'rut' => $Factura->fields['RUT_cliente'],
				'codigo' => $Factura->fields['codigo_cliente'],
				'razon_social' => UtilesApp::transliteration($Factura->fields['cliente']),
				'giro' => UtilesApp::transliteration($Factura->fields['giro_cliente']),
				'direccion' => UtilesApp::transliteration($Factura->fields['direccion_cliente']),
				'comuna' => UtilesApp::transliteration($Factura->fields['comuna_cliente']),
				'cuidad' => UtilesApp::transliteration($Factura->fields['ciudad_cliente']),
				'contacto' => $Contrato->ObtenerSolicitante()
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

		if ($Factura->fields['id_factura_padre'] > 0) {
			$FacturaPadre = new Factura($Sesion);
			$FacturaPadre->Load($Factura->fields['id_factura_padre']);
			$arrayPadre = self::FacturaToArray($Sesion, $FacturaPadre, $Estudio);

			$PrmDocumentoLegal->Load($FacturaPadre->fields['id_documento_legal']);
			$tipoDTE = $PrmDocumentoLegal->fields['codigo_dte'];

			$referenciaId = intval($Factura->fields['dte_codigo_referencia']);
			$Referencia = new PrmCodigo($Sesion);
			$Referencia->LoadById($referenciaId);
			$codigoReferencia = $Referencia->Loaded() ? $Referencia->fields['codigo'] : 1;
			$arrayFactura['referencia'] = array(
				'tipo_dte'	=> $tipoDTE,
				'folio'	=> $FacturaPadre->fields['numero'],
				'fecha_emision'	=> Utiles::sql2date($FacturaPadre->fields['fecha'], '%Y-%m-%d'),
				/**
				 * CodRef: Indica los distintos casos de referencia, los cuales pueden ser:
				 * a) Nota de Crédito que elimina documento de referencia en forma completa
				 *    (Factura de venta, Nota de débito, o Factura de compra)
				 * b) Nota de crédito que corrige un texto del documento de referencia
				 * c) Nota de Débito que elimina una Nota de Crédito en la referencia en forma completa
				 * d) Notas de crédito o débito que corrigen montos de otro documento
				 * Casos a) b) y c) deben tener un único documento de referencia, es decir una sola línea de referencia.
				 * Sus valores pueden ser:
				 *   1: Anula Documento de Referencia.
				 *   2: Corrige Texto Documento de Referencia.
				 *   3: Corrige Montos.
				 */
				'codigo'	=> $codigoReferencia,
				/**
				 * RazonRef: Explicitar razon. Ejemplo una Nota de Credito que hacer referencia a una factura,
				 * indica "descuento por pronto pago", "error en precio" o “anula factura”, etc.
				 * El campo tiene un largo maximo de 90 caracteres.
				 */
				'razon'	=> $Factura->fields['dte_razon_referencia'],
			);
		} else if ($Factura->fields['id_documento_referencia'] > 0) {
			$FacturaPadre = new Factura($Sesion);
			$FacturaPadre->Load($Factura->fields['id_factura_padre']);
			$arrayPadre = self::FacturaToArray($Sesion, $FacturaPadre, $Estudio);

			$referenciaId = intval($Factura->fields['id_documento_referencia']);
			$Referencia = new PrmCodigo($Sesion);
			$Referencia->LoadById($referenciaId);
			$codigoReferencia = $Referencia->Loaded() ? $Referencia->fields['codigo'] : 1;
			$arrayFactura['referencia'] = array(
				'tipo_dte'	=> $referenciaId,
				'folio'	=> $Factura->fields['folio_documento_referencia'],
				'fecha_emision'	=> Utiles::sql2date($Factura->fields['fecha_documento_referencia'], '%Y-%m-%d'),
				/**
				 * CodRef: Indica los distintos casos de referencia, los cuales pueden ser:
				 * a) Nota de Crédito que elimina documento de referencia en forma completa
				 *    (Factura de venta, Nota de débito, o Factura de compra)
				 * b) Nota de crédito que corrige un texto del documento de referencia
				 * c) Nota de Débito que elimina una Nota de Crédito en la referencia en forma completa
				 * d) Notas de crédito o débito que corrigen montos de otro documento
				 * Casos a) b) y c) deben tener un único documento de referencia, es decir una sola línea de referencia.
				 * Sus valores pueden ser:
				 *   1: Anula Documento de Referencia.
				 *   2: Corrige Texto Documento de Referencia.
				 *   3: Corrige Montos.
				 */
				'codigo'	=> $codigoReferencia,
				/**
				 * RazonRef: Explicitar razon. Ejemplo una Nota de Credito que hacer referencia a una factura,
				 * indica "descuento por pronto pago", "error en precio" o “anula factura”, etc.
				 * El campo tiene un largo maximo de 90 caracteres.
				 */
				'razon'	=> $Factura->fields['dte_razon_referencia'],
			);
		}

		return $arrayFactura;
	}

}
