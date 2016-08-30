<?php

class FacturacionElectronicaMx extends FacturacionElectronica {

	public static function InsertaMetodoPago() {
		global $factura, $contrato;
		$Sesion = new Sesion();
		$Form = new Form();
		$Form->defaultLabel = false;
		$dte_fecha_creacion = $factura->fields['dte_fecha_creacion'];

		echo '<tr>';
		echo '<td align="right">M&eacute;todo de Pago</td>';
		echo '<td align="left" colspan="3">';
		$query = "SELECT id_codigo, glosa FROM prm_codigo WHERE grupo = 'PRM_FACTURA_MX_METOD' ORDER BY codigo ASC";
		if (empty($dte_fecha_creacion)) {
			echo Html::SelectQuery($Sesion, $query, 'dte_metodo_pago', $factura->fields['dte_metodo_pago'], '', '', '300');
		} else {
			$options = "style='display: none;' data-default='{$factura->fields['dte_metodo_pago']}'";
			echo Html::SelectQuery($Sesion, $query, 'dte_metodo_pago', $factura->fields['dte_metodo_pago'], $options, __('Seleccione'), '300');
			$Criteria = new Criteria($Sesion);
			$metodo_pago = $Criteria->add_select('glosa')
															->add_from('prm_codigo')
															->add_restriction(CriteriaRestriction::and_clause(
																	CriteriaRestriction::equals('grupo', "'PRM_FACTURA_MX_METOD'"),
																	CriteriaRestriction::equals('id_codigo', $factura->fields['dte_metodo_pago'])
															))
															->run();
			$metodo_pago = $metodo_pago[0]['glosa'];

			if (empty($metodo_pago)) {
				$Criteria = new Criteria($Sesion);
				$metodo_pago = $Criteria->add_select('glosa')
																->add_from('prm_codigo')
																->add_restriction(CriteriaRestriction::and_clause(
																CriteriaRestriction::equals('grupo', "'OLD_FACTURA_MX_METOD'"),
																CriteriaRestriction::equals('id_codigo', $factura->fields['dte_metodo_pago'])
																))
																->run();
				$metodo_pago = $metodo_pago[0]['glosa'];
			}

			echo "<b id='metodo_pago_texto'>$metodo_pago</b> ";
		}

		$cta_pago = $factura->fields['dte_metodo_pago_cta'];
		if (is_null($cta_pago) || empty($cta_pago) || $cta_pago === 0) {
			$cta_pago = '';
		}
		echo $Form->input('dte_metodo_pago_cta', $cta_pago, array('size' => 10, 'maxlength' => 30, 'placeholder' => 'No. cuenta'));

		echo "</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td align="right">' . __('Nota o comentario') . '</td>';
		echo '<td align="left" colspan="3">';
		echo $Form->input('dte_comentario', $factura->fields['dte_comentario'], array('size' => '70', 'maxlength' => '255'));
		echo '</td>';
		echo '</tr>';
	}

	public static function BotonDescargarHTML($id_factura) {
		$img_dir = Conf::ImgDir();
		$Html = self::getHtml();
		$img_pdf = $Html->img("{$img_dir}/pdf.gif", array('border' => 0));
		$a1 = $Html->tag('a', $img_pdf, array('class' => 'factura-documento', 'data-factura' => $id_factura, 'href' => '#'));
		$img_xml = $Html->img("{$img_dir}/xml.gif", array('border' => 0));
		$a2 = $Html->tag('a', $img_xml, array('class' => 'factura-documento', 'data-factura' => $id_factura, 'data-format' => 'xml', 'href' => '#'));
		return $a1 . $a2;
	}

	public static function InsertaJSFacturaElectronica() {
		$BotonDescargarHTML = self::BotonDescargarHTML('0');
		echo <<<EOF
			jQuery(document).on("click", ".factura-electronica", function() {
				if (!confirm("�Confirma la generaci�n de Factura electr�nica?")) {
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

			jQuery("#id_estado").on("change", function() {
				if (jQuery(this).val() == 5) {
					jQuery('#metodo_pago_texto').hide();
					jQuery('#dte_metodo_pago').show();
				} else {
					jQuery('#dte_metodo_pago').val(jQuery('#dte_metodo_pago').data('default')).hide();
					jQuery('#metodo_pago_texto').show();
				};
			});
EOF;
	}

	public static function ValidarFactura() {
		global $pagina, $RUT_cliente, $direccion_cliente, $ciudad_cliente, $dte_metodo_pago, $dte_id_pais, $dte_metodo_pago_cta;
		if (empty($RUT_cliente)) {
			$pagina->AddError(__('Debe ingresar RFC del cliente.'));
		}
		if (empty($direccion_cliente)) {
			$pagina->AddError(__('Debe ingresar Direcci�n del cliente.'));
		}
		if (empty($ciudad_cliente)) {
			$pagina->AddError(__('Debe ingresar Ciudad del cliente.'));
		}
		if (empty($dte_metodo_pago)) {
			$pagina->AddError(__('Debe seleccionar el M�todo de Pago.'));
		}
		if (strlen($dte_metodo_pago_cta) > 0 && strlen($dte_metodo_pago_cta) < 4) {
			$pagina->AddError(__('El n�mero de cuenta debe tener al menos 4 d&iacute;gitos'));
		}
		if (empty($dte_id_pais)) {
			$pagina->AddError(__('Debe seleccionar el Pa�s del cliente.'));
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
					$estado_dte = Factura::$estados_dte['Firmado'];
					$Factura->Edit('dte_xml', $result->descripcion);
					$Factura->Edit('dte_fecha_creacion', date('Y-m-d H:i:s'));
					$Factura->Edit('dte_firma', $result->timbrefiscal);
					$Factura->Edit('dte_estado', $estado_dte);
					$Factura->Edit('dte_estado_descripcion', __(Factura::$estados_dte_desc[$estado_dte]));

					preg_match('/UUID="([a-zA-Z0-9-]+)"/', $result->descripcion, $folio_fiscal);
					$Factura->Edit('dte_folio_fiscal', $folio_fiscal[1]);

					$file_name = '/dtes/' . Utiles::sql2date($Factura->fields['fecha'], "%Y%m%d") . "_{$Factura->fields['serie_documento_legal']}-{$Factura->fields['numero']}.pdf";
					$file_data = base64_decode($result->documentopdf);
					$file_url = UtilesApp::UploadToS3($file_name, $file_data, 'application/pdf');
					$Factura->Edit('dte_url_pdf', $file_url);
					if ($Factura->Write()) {
						$hookArg['InvoiceURL'] = $file_url;
					}
				} catch (Exception $ex) {
					$hookArg['Error'] = self::ParseError($ex, 'BuildingInvoiceError');
				}
			} else {
				$hookArg['Error'] = self::ParseError($result, 'BuildingInvoiceError');
				$estado_dte = Factura::$estados_dte['ErrorFirmado'];
				$Factura->Edit('dte_estado', $estado_dte);
				$Factura->Edit('dte_estado_descripcion', utf8_decode($result->descripcion));
				$Factura->Write();
			}
		}
		return $hookArg;
	}

	public static function ParseError($result, $error_code) {
		$error_description = null;
		if (is_a($result, 'Exception')) {
			$error_log = $result->__toString();
		} else {
			$error_description = $result->codigo >= 501 ? null : "ERROR_{$result->codigo}";
			$error_log = utf8_decode($result->descripcion);
		}
		Log::write($error_log, "FacturacionElectronicaMx");
		return array(
			'Code' =>  $error_code,
			'Message' => $error_description
		);
	}

	public static function AnulaFacturaElectronica($hookArg) {
		$Sesion = new Sesion();
		$Factura = $hookArg['Factura'];

		if (!$Factura->DTEFirmado() && !$Factura->DTEProcesandoAnular()) {
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
				$estado_dte = Factura::$estados_dte['Anulado'];
				$Factura->Edit('dte_fecha_anulacion', date('Y-m-d H:i:s'));
				$Factura->Edit('dte_estado', $estado_dte);
				$Factura->Edit('dte_estado_descripcion', __(Factura::$estados_dte_desc[$estado_dte]));
				$Factura->Edit('dte_estado_descripcion', utf8_decode($result->descripcion));
				$Factura->Write();
			} catch (Exception $ex) {
				$hookArg['Error'] = self::ParseError($ex, 'SaveCanceledInvoiceError');
			}
		} else {
			$mensaje = "Usted ha solicitado anular un Documento Tributario Electr�nico. Este proceso puede tardar hasta 72 horas por lo que mientras esto ocurre, anularemos la factura en Time Billing para que usted pueda volver a generar el documento correctamente.";
			$estado_dte = Factura::$estados_dte['ProcesoAnular'];
			$Factura->Edit('dte_estado', $estado_dte);
			$Factura->Edit('dte_estado_descripcion', $mensaje);
			$Factura->Write();
			$hookArg['Error'] = self::ParseError($result, 'CancelGeneratedInvoiceError');
		}
		return $hookArg;
	}

	public static function PaymentMethod(Sesion $Sesion, Factura $Factura) {
		if (is_null($Factura->fields['dte_metodo_pago']) || $Factura->fields['dte_metodo_pago'] == "") {
			return "No Identificado";
		}

		$sql = "SELECT `prm_codigo`.`codigo`
					FROM `prm_codigo`
					WHERE `prm_codigo`.`id_codigo` = :code_id
						AND `prm_codigo`.`grupo` = 'PRM_FACTURA_MX_METOD'";

		$Statement = $Sesion->pdodbh->prepare($sql);
		$Statement->bindParam('code_id', $Factura->fields['dte_metodo_pago']);
		$Statement->execute();

		$payment_method = $Statement->fetchObject();

		if (is_object($payment_method)) {
			return $payment_method->codigo;
		} else {
			return "No Identificado";
		}
	}

	/**
	 * $strdocumento = 'COM|||version|3.2||serie|WS||folio|15||fecha|2013-07-18T10:14:49||formaDePago|PAGO EN UNA SOLA EXHIBICION||TipoCambio|1.000||condicionesDePago|EFECTOS FISCALES AL PAGO||subTotal|425.00||Moneda|MX||total|493.00||tipoDeComprobante|ingreso||metodoDePago|PAGO NO IDENTIFICADO||LugarExpedicion|MEXICO DISTRITO FEDERAL||NumCtaPago|1234||descuento|0.00||motivoDescuento|desc
	 * REF|||Regimen|REGIMEN GENERAL DE LEY PERSONAS MORALES
	 * REC|||rfc|DNM070221BS4||nombre|DISEр�OS NAOMI MEXICO, S.A. DE C.V.
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

		$zona_horaria = Conf::GetConf($Sesion,'ZonaHoraria');
		date_default_timezone_set($zona_horaria);
		$mx_hour = date("H:i:s", time());

		$PrmDocumentoLegal = new PrmDocumentoLegal($Sesion);
		$PrmDocumentoLegal->Load($Factura->fields['id_documento_legal']);
		$tipo_documento_legal = $PrmDocumentoLegal->fields['codigo'];
		$tipoComprobante = $PrmDocumentoLegal->fields['codigo_dte'];

		$tra = array();
		if ($Factura->fields['iva'] > 0) {
			$tra = array(
				'impuesto|IVA',
				'importe|' . number_format($Factura->fields['iva'], 2, '.', ''),
				'tasa|' . number_format($Factura->fields['porcentaje_impuesto'], 2, '.', '')
			);
		}

		$r = array(
			'COM' => array(
				'version|3.2',
				'serie|' . $Factura->fields['serie_documento_legal'],
				'folio|' . $Factura->fields['numero'],
				'fecha|' . Utiles::sql2date($Factura->fields['fecha'] . ' ' . $mx_hour, '%Y-%m-%dT%H:%M:%S'),
				'formaDePago|' . 'PAGO EN UNA SOLA EXHIBICION',
				'TipoCambio|' . number_format($Factura->get_tipo_cambio($Factura->fields['id_moneda']), 2, '.', ''),
				'condicionesDePago|' . 'EFECTOS FISCALES AL PAGO', // $Factura->fields['condicion_pago'],
				'Moneda|' . $monedas[$Factura->fields['id_moneda']]['simbolo_factura'],
				'metodoDePago|' . self::PaymentMethod($Sesion, $Factura),
				'total|' . number_format($Factura->fields['total'], 2, '.', ''),
				'LugarExpedicion|' . 'M�xico Distrito Federal',
				'tipoDeComprobante|' . $tipoComprobante
			),
			'REF' => array(
				'Regimen|' . 'R�gimen General de Ley, Personas Morales'
			),
			'REC' => array(
				'rfc|' . $Factura->fields['RUT_cliente'],
				'nombre|' . ($Factura->fields['cliente'])
			),
			'TRA' => $tra
		);



		/*
		*	El monto subtotal de la factura debe ser la suma de los subtotales
		*	subtotal = Monto Horararios;
		*	subtotal_gastos = Monto Gastos con impuestos;
		*	subtotal_gastos_sin_impuesto = Monto Gastos sin impuestos;
		*/

		$subtotal_factura = $Factura->fields['subtotal'] + $Factura->fields['subtotal_gastos'] + $Factura->fields['subtotal_gastos_sin_impuesto'];

		$r['COM'][] = 'subTotal|' . number_format($subtotal_factura, 2, '.', '');

		if (!is_null($Factura->fields['dte_metodo_pago_cta']) && !empty($Factura->fields['dte_metodo_pago_cta']) && (int) $Factura->fields['dte_metodo_pago_cta'] > 0) {
			$r['COM'][] = 'NumCtaPago|' . $Factura->fields['dte_metodo_pago_cta'];
		}

		if (!is_null($Factura->fields['direccion_cliente']) && !empty($Factura->fields['direccion_cliente'])) {
			$r['DOR'][] = 'calle|' . ($Factura->fields['direccion_cliente']);
		}

		if (!is_null($Factura->fields['comuna_cliente']) && !empty($Factura->fields['comuna_cliente'])) {
			$r['DOR'][] = 'municipio|' . ($Factura->fields['comuna_cliente']);
		}

		if (!is_null($Factura->fields['factura_estado']) && !empty($Factura->fields['factura_estado'])) {
			$r['DOR'][] = 'estado|' . ($Factura->fields['factura_region']);
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
				'unidad|' . __('N/A'),
				'descripcion|' . ($Factura->fields['descripcion']),
				'valorUnitario|' . number_format($Factura->fields['subtotal'], 2, '.', ''),
				'importe|' . number_format($Factura->fields['subtotal'], 2, '.', ''),
				'descuento|0.00'
			);
		}

		if ($Factura->fields['subtotal_gastos'] > 0) {
			$r['CON_gastos_con_iva'] = array(
				'cantidad|1.00',
				'unidad|' . __('N/A'),
				'descripcion|' . ($Factura->fields['descripcion_subtotal_gastos']),
				'valorUnitario|' . number_format($Factura->fields['subtotal_gastos'], 2, '.', ''),
				'importe|' . number_format($Factura->fields['subtotal_gastos'], 2, '.', ''),
				'descuento|0.00'
			);
		}

		if ($Factura->fields['subtotal_gastos_sin_impuesto'] > 0) {
			$r['CON_gastos_sin_iva'] = array(
				'cantidad|1.00',
				'unidad|' . __('N/A'),
				'descripcion|' . ($Factura->fields['descripcion_subtotal_gastos_sin_impuesto']),
				'valorUnitario|' . number_format($Factura->fields['subtotal_gastos_sin_impuesto'], 2, '.', ''),
				'importe|' . number_format($Factura->fields['subtotal_gastos_sin_impuesto'], 2, '.', ''),
				'descuento|0.00'
			);
		}

		if (!empty($Factura->fields['dte_comentario'])) {
			$r['ADI'] = array('comentarios|' . $Factura->fields['dte_comentario']);
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

	public static function InsertaEstadoDTE() {
		global $factura;
		$img_dir = Conf::ImgDir();
		$mensaje = $factura->fields['dte_estado_descripcion'];
		if (!is_null($mensaje) && $mensaje != '') {
			echo "<a class = 'factura-dte-estado' href = 'javascript:alert(\"{$mensaje}\");'><img src='$img_dir/info-icon-24.png' border='0' /></a>";
		}
	}

	public static function BotonDescargarEstadoHTML($id_factura, $estado, $icon) {
		$Html = self::getHtml();
		$img_dir = Conf::ImgDir();
		$img = $Html->img("{$img_dir}/{$icon}", array('border' => '0'));
		$attr_a = array(
			'title' => $estado,
			'class' => 'factura-documento',
			'data-factura' => $id_factura,
			'href' => '#'
		);
		return $Html->tag('a', $img, $attr_a);
	}

	public static function AgregarBotonFacturaElectronica($hookArg) {
		$Factura = $hookArg['Factura'];
		if ($Factura->FacturaElectronicaCreada()) {
			if ($Factura->DTEFirmado()) {
				$hookArg['content'] = self::BotonDescargarHTML($Factura->fields['id_factura']);
			} elseif ($Factura->DTEAnulado()) {
				$hookArg['content'] = self::BotonDescargarEstadoHTML($Factura->fields['id_factura'], $Factura->fields['dte_estado_descripcion'], 'pdf-gris.gif');
			} elseif ($Factura->DTEProcesandoAnular()) {
				$hookArg['content'] = self::BotonDescargarEstadoHTML($Factura->fields['id_factura'], $Factura->fields['dte_estado_descripcion'], 'pdf-gris-error.gif');
			}
		} else {
			if (!$Factura->Anulada()) {
				$hookArg['content'] = self::BotonGenerarHTML($Factura->fields['id_factura']);
			}
		}
		return $hookArg;
	}

}
