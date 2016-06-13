<?php
require_once dirname(__FILE__) . '/../conf.php';

class FacturaPdfDatos extends Objeto {
	function __construct($sesion, $fields = '', $params = '') {
		$this->tabla = 'factura_pdf_datos';
		$this->campo_id = 'id_dato';
		$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->papel = array();
	}

	function CargarDatos($id_factura, $id_documento_legal, $id_estudio) {
		if (!UtilesApp::ExisteCampo('align', 'factura_pdf_datos', $this->sesion)) {
			mysql_query("ALTER TABLE `factura_pdf_datos` ADD `align` VARCHAR(1) NOT NULL DEFAULT 'L' COMMENT 'J justifica, tb puede ser R C o L';", $this->sesion->dbh);
		}

		$query = "SELECT SQL_CALC_FOUND_ROWS codigo_tipo_dato, activo, coordinateX, coordinateY, cellW, cellH, font, style, mayuscula, tamano, align
			FROM factura_pdf_datos
				JOIN factura_pdf_tipo_datos USING( id_tipo_dato )
			WHERE (activo = 1 OR codigo_tipo_dato = 'tipo_papel') AND id_documento_legal = '$id_documento_legal' AND id_estudio = '$id_estudio'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$found_rows = (int) current(mysql_fetch_row(mysql_query("SELECT FOUND_ROWS()")));

		// si no tiene un documento seteado para el estudio se toma uno por defecto
		if ($found_rows == 0) {
			$query = "SELECT codigo_tipo_dato, activo, coordinateX, coordinateY, cellW, cellH, font, style, mayuscula, tamano, align
				FROM factura_pdf_datos
					JOIN factura_pdf_tipo_datos USING( id_tipo_dato )
				WHERE (activo = 1 OR codigo_tipo_dato = 'tipo_papel') AND id_documento_legal = '$id_documento_legal'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}

		$valores = $this->CargarGlosaDatos($id_factura);
		while ($row = mysql_fetch_assoc($resp)) {
			foreach($row as $tipo_dato => $valor) {
				if ($tipo_dato == 'codigo_tipo_dato') {
					$row['dato_letra'] = $valores[$row['codigo_tipo_dato']];
					$this->datos[$row['codigo_tipo_dato']] = $row;
				} else {
					$this->datos[$row['codigo_tipo_dato']][$tipo_dato] = $valor;
				}
			}
		}

		$this->papel = $this->datos['tipo_papel'];
		unset($this->datos['tipo_papel']);
	}

	function CargarGlosaDatos($id_factura) {
		$factura = new Factura($this->sesion);
		$factura->Load($id_factura);

		$cobro = new Cobro($this->sesion);
		$cobro->Load($factura->fields['id_cobro']);
		$cobro->LoadGlosaAsuntos();

		$contrato = new Contrato($this->sesion);
		$contrato->Load($cobro->fields['id_contrato']);

		$idioma = new Objeto($this->sesion,'','','prm_idioma','codigo_idioma');
		$idioma->Load( $cobro->fields['codigo_idioma'] );

		if ($idioma->fields['codigo_idioma'] == 'en') {
			global $_LANG ;
			include('../lang/en.php');
			$codigo = 'en_US';
		} else if ($idioma->fields['codigo_idioma'] == 'es') {
			$codigo = 'es';
		}

		$Cliente = new Cliente($this->sesion);
    	$Cliente->LoadByCodigo($cobro->fields['codigo_cliente']);

		$chargingBusiness = new ChargingBusiness($this->sesion);
		$coiningBusiness = new CoiningBusiness($this->sesion);
		$billingBusiness = new BillingBusiness($this->sesion);

		// Segmento Condiciones de pago
		$condicion_pago = $factura->ObtieneGlosaCondicionPago();
		// Segmento Monto en palabra solicitado por @gtigre
		$arreglo_monedas = Moneda::GetMonedas($this->sesion, null, true);

		$monto_total_factura = $factura->fields['total'];

		list ($monto_parte_entera, $monto_parte_decimal) = explode('.',$monto_total_factura);

		$glosa_moneda_plural = __($arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda_plural']);

		$con = __('CON');
		$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($monto_parte_entera, $codigo));
		$monto_total_palabra_fix = "{$monto_palabra_parte_entera} {$glosa_moneda_plural}";

		$NumbersWords = new Numbers_Words();

		if (empty($monto_parte_decimal)) {
			$monto_parte_decimal_fix = '00';
		} else {
			$centavos = __('CENTAVOS');
			$fix_decimal = strlen($monto_parte_decimal) == 2 ? 1 : 10;
			$monto_parte_decimal *= $fix_decimal;
			$monto_palabra_parte_decimal = strtoupper(Numbers_Words::toWords($monto_parte_decimal, $codigo));
			$monto_total_palabra_fix .= " {$con} {$monto_palabra_parte_decimal} {$centavos}";
			$monto_parte_decimal_fix = str_pad($monto_parte_decimal, 2, '0', STR_PAD_LEFT);
		}

		$monto_en_palabra_cero_cien = "{$monto_palabra_parte_entera} {$con} {$monto_parte_decimal_fix}/100 {$glosa_moneda_plural}";

		// Segmento Glosa Detraccion Solicitado por @gtigre para Hernandez
		$tipo_cambio_usd = $arreglo_monedas[2]['tipo_cambio'];
		$cifras_decimales_usd = $arreglo_monedas[2]['cifras_decimales'];

		$tipo_cambio_moneda_base = $arreglo_monedas[1]['tipo_cambio'];
		$cifras_decimales_moneda_base = $arreglo_monedas[1]['cifras_decimales'];

		$monto_total_factura_monedabase = $monto_total_factura;
		$glosa_detraccion = explode(';',Conf::GetConf($this->sesion,'GlosaDetraccion'));
		$FacturaTextoImpuesto = Conf::GetConf($this->sesion,'FacturaTextoImpuesto');

		$monto_detraccion = $glosa_detraccion[0];
		$texto_detraccion = $glosa_detraccion[1];

		if ($factura->fields['id_moneda'] != 1) {
			$monto_total_factura_monedabase = UtilesApp::CambiarMoneda($monto_total_factura, $tipo_cambio_usd, $cifras_decimales_usd, $tipo_cambio_moneda_base, $cifras_decimales_moneda_base, '');
		}

		if ($monto_total_factura_monedabase >= $monto_detraccion){
			$factura_texto_detraccion =  $texto_detraccion;
		} else {
			$factura_texto_detraccion = '';
		}

		if ($factura->fields['RUT_cliente'] == '' || $factura->fields['iva'] == 0){
			$texto_impuesto = '';
		} else {
			$texto_impuesto = $FacturaTextoImpuesto;
		}

		$honorarios_sin_impuesto = 0;
		if ($factura->fields['porcentaje_impuesto'] == 0) {
			$honorarios_sin_impuesto = $factura->fields['honorarios'];
		}

		$chargeId = $factura->fields['id_cobro'];
		$charge = $chargingBusiness->getCharge($chargeId);
		$currency = $coiningBusiness->getCurrency($factura->fields['id_moneda']);
		$invoice = $billingBusiness->getInvoice($factura->fields['id_factura']);
		$detail = $billingBusiness->getFeesDataOfInvoiceByCharge($invoice, $charge, $currency);
		$discount = $detail->get('descuento_honorarios');

		$datos = array(
			'numero' => $factura->fields['numero'],
			'glosa_cliente' => $Cliente->fields['glosa_cliente'],
			'glosa_asuntos' => implode(', ', $cobro->glosa_asuntos),
			'razon_social' => $factura->fields['cliente'],
			'rut' => $factura->fields['RUT_cliente'],
			'telefono' => $contrato->fields['factura_telefono'],
			'fecha_dia' => date("d", strtotime($factura->fields['fecha'])),
			'fecha_mes' => strftime("%B", strtotime($factura->fields['fecha'])),
			'fecha_mes_entre_de' => 'de ' . ucfirst(strftime("%B", strtotime($factura->fields['fecha']))) . ' de',
			'fecha_numero_mes' => strftime("%m", strtotime($factura->fields['fecha'])),
			'fecha_ano' => date("Y", strtotime($factura->fields['fecha'])),
			'fecha_ano_ultima_cifra' => substr(date("Y",strtotime($factura->fields['fecha'])),-1),
			'fecha_ano_dos_ultimas_cifras' => substr(date("Y",strtotime($factura->fields['fecha'])),-2),
			'fecha_venc_dia' => date("d", strtotime($factura->fields['fecha_vencimiento'])),
			'fecha_venc_mes' => strftime("%B", strtotime($factura->fields['fecha_vencimiento'])),
			'fecha_venc_ano' => date("Y", strtotime($factura->fields['fecha_vencimiento'])),
			'fecha_venc_ano_ultima_cifra' => substr(date("Y",strtotime($factura->fields['fecha_vencimiento'])),-1),
			'fecha_venc_ano_dos_cifras' => substr(date("Y",strtotime($factura->fields['fecha_vencimiento'])),-2),
			'fecha_venc_numero_mes' => strftime("%m", strtotime($factura->fields['fecha_vencimiento'])),
			'direccion' => $factura->fields['direccion_cliente'],
			'comuna' => $factura->fields['comuna_cliente'],
			'factura_codigopostal' => $factura->fields['factura_codigopostal'],
			'ciudad' => $factura->fields['ciudad_cliente'],
			'pais' => $factura->GetPais(),
			'giro_cliente' => $factura->fields['giro_cliente'],
			'lugar' => UtilesApp::GetConf($this->sesion, 'LugarFacturacion'),
			'id_cobro' => $factura->fields['id_cobro'],
			'nota_factura' => $condicion_pago,
			'desc_subtotal_honorarios' => __('SUBTOTAL') .  ' ' . __('HONORARIOS'),
			'desc_descuento_honorarios' => __('DESCUENTO') .  ' ' . __('HONORARIOS'),
			'desc_glosa_factura' => __('Descripci�n Glosa'),
			'glosa_factura' => $factura->fields['glosa'],
			'desc_encargado_comercial' => __('Encargado Comercial'),
			'encargado_comercial' => $factura->ObtenerEncargadoComercialUsername($factura->fields['id_contrato']),
			'descripcion_honorarios' => $factura->fields['descripcion'],
			'descripcion_gastos_con_iva' => $factura->fields['descripcion_subtotal_gastos'],
			'descripcion_gastos_sin_iva' => $factura->fields['descripcion_subtotal_gastos_sin_impuesto'],
			'descripcion_gastos_con_y_sin_iva' => __('Gastos totales'),
			'monto_honorarios' => number_format(
					$factura->fields['subtotal_sin_descuento'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_descuento_honorarios' => ($discount > 0) ? number_format(
					-1 * $discount,
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				) : NULL,
			'monto_subtotal_honorarios' => number_format(
					$detail->get('subtotal_honorarios'),
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_gastos_con_iva' => number_format(
					$factura->fields['subtotal_gastos'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_gastos_sin_iva' => number_format(
					$factura->fields['subtotal_gastos_sin_impuesto'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_gastos_con_y_sin_iva' => number_format(
					$factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'moneda_subtotal_honorarios' => $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'],
			'glosa_descuento' => ($discount > 0) ? __('Descuento') . ':' : ' ',
			'moneda_descuento_honorarios' => ($discount > 0) ? $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'] : NULL,
			'moneda_honorarios' => $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'],
			'moneda_gastos_con_iva' => $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'],
			'moneda_gastos_sin_iva' => $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'],
			'monto_en_palabra' => mb_strtoupper($monto_total_palabra_fix, 'ISO-8859-1'),
			'monto_total_palabra' => mb_strtoupper($monto_total_palabra_fix, 'ISO-8859-1'),
			'monto_en_palabra_cero_cien' => mb_strtoupper($monto_en_palabra_cero_cien, 'ISO-8859-1'),
			'porcentaje_impuesto' => $factura->fields['porcentaje_impuesto']."%",
			'moneda_subtotal' => $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'],
			'moneda_iva' => $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'],
			'moneda_total' => $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'],
			'monto_subtotal_bruto' => number_format(
					$discount + $factura->fields['subtotal_sin_descuento'] + $factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_descuento' => number_format(
					-1 * $discount,
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_subtotal' => number_format(
					$factura->fields['subtotal_sin_descuento'] + $factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_iva' => number_format(
					$factura->fields['iva'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_total' => number_format(
					$factura->fields['total'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'monto_total_2' => number_format(
					$factura->fields['total'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				),
			'glosa_detraccion' => $factura_texto_detraccion,
			'texto_impuesto' => $texto_impuesto,
			'solicitante' => $contrato->fields['contacto'],
			'lbl_fecha_vencimiento' => 'Fecha Vencimiento / Due Date:',
			'monto_honorarios_con_iva' => number_format($factura->fields['honorarios'] * ( 1 + ( $factura->fields['porcentaje_impuesto'] / 100) ),
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']),
			'subtotal_exento' => number_format($factura->fields['subtotal_gastos_sin_impuesto'] + $honorarios_sin_impuesto,
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']),
			'subtotal_impuesto' => number_format($factura->fields['honorarios'] * ( 1 + ( $factura->fields['porcentaje_impuesto'] / 100)) + $factura->fields['subtotal_gastos_sin_impuesto'] * ( 1 + ( $factura->fields['porcentaje_impuesto'] / 100)),
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles'])
		);

		// Segmento Comodines. Solicitados por @gtigre
		$query_comodines = "SELECT codigo, glosa FROM prm_codigo WHERE grupo = 'PRM_FACTURA_PDF'";
		$resp_comodines = mysql_query($query_comodines,$this->sesion->dbh) or Utiles::errorSQL($query_comodines,__FILE__,__LINE__,$this->sesion->dbh);

		while (list($codigo,$glosa) = mysql_fetch_array($resp_comodines)) {
			if (!$datos[$codigo]) {
				$datos[$codigo] = $glosa;
			}
		}

		return $datos;
	}

	function generarFacturaPDF($id_factura, $mantencion = false, $orientacion = 'P', $format = 'Letter') {
		require_once Conf::ServerDir() . '/fpdf/fpdf.php';

		$factura = new Factura($this->sesion);

		if (!$factura->Load($id_factura)) {
			echo "<html><head><title>Error</title></head><body><p>No se encuentra la factura $id_factura.</p></body></html>";
			return;
		}

		$query = "SELECT id_documento_legal, codigo, glosa FROM prm_documento_legal WHERE id_documento_legal = '{$factura->fields['id_documento_legal']}'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_documento_legal, $codigo_documento_legal, $glosa_documento_legal) = mysql_fetch_array($resp);

		$this->CargarDatos($id_factura, $id_documento_legal, $factura->fields['id_estudio']); // esto trae la posicion, tama�o y glosa de todos los campos m�s los datos del papel en la variable $this->papel;

		if(count($this->papel)) {
			$pdf = new FPDF($orientacion, 'mm', array($this->papel['cellW'], $this->papel['cellH']));
			$pdf->SetMargins($this->papel['coordinateX'], $this->papel['coordinateY']);
			$pdf->SetAutoPageBreak(true, $margin);
		} else {
			// P: hoja vertical
			// mm: todo se mide en mil�metros
			// Letter: formato de hoja
			$pdf = new FPDF($orientacion, 'mm', $format);
		}

		$query = "SELECT codigo, glosa FROM prm_documento_legal WHERE id_documento_legal = '{$factura->fields['id_documento_legal']}'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($codigo_documento_legal, $glosa_documento_legal) = mysql_fetch_array($resp);

		$pdf->SetTitle($glosa_documento_legal . ' ' . $factura->fields['numero']);

		// La orientaci�n y formato de la p�gina son los mismos que del documento
		$pdf->AddPage();
		$datos['dato_letra'] = str_replace(array("<br>\n", "<br/>\n", "<br />\n" ), "\n", $datos['dato_letra']);

		if (isset($this->datos['monto_honorarios']['dato_letra']) && intval($this->datos['monto_honorarios']['dato_letra']) === 0) {
			unset($this->datos['monto_honorarios']);
			unset($this->datos['moneda_honorarios']);
			unset($this->datos['descripcion_honorarios']);
		}

		if (isset($this->datos['monto_gastos_con_iva']['dato_letra']) && intval($this->datos['monto_gastos_con_iva']['dato_letra']) === 0) {
			unset($this->datos['monto_gastos_con_iva']);
			unset($this->datos['moneda_gastos_con_iva']);
			unset($this->datos['descripcion_gastos_con_iva']);
		}

		if (isset($this->datos['monto_gastos_sin_iva']['dato_letra']) && intval($this->datos['monto_gastos_sin_iva']['dato_letra']) === 0) {
			unset($this->datos['monto_gastos_sin_iva']);
			unset($this->datos['moneda_gastos_sin_iva']);
			unset($this->datos['descripcion_gastos_sin_iva']);
		}

		foreach ($this->datos as $tipo_dato => $datos) {
			$pdf->SetFont($datos['font'], $datos['style'], $datos['tamano']);
			$pdf->SetXY($datos['coordinateX'], $datos['coordinateY']);

			if ( $datos['mayuscula'] == 'may') {
				$datos['dato_letra'] = strtoupper($datos['dato_letra']);
			} else if( $datos['mayuscula'] == 'min') {
				$datos['dato_letra'] = strtolower($datos['dato_letra']);
			} else if( $datos['mayuscula'] == 'cap') {
				$datos['dato_letra'] = ucwords(strtolower($datos['dato_letra']));
			} else if( $datos['mayuscula'] == 'ucf') {
				$datos['dato_letra'] = ucfirst(strtolower($datos['dato_letra']));
			} else {
				$datos['dato_letra'] = $datos['dato_letra'];
			}

			if( $datos['cellH'] > 0 || $datos['cellW'] > 0 ) {
				$pdf->MultiCell($datos['cellW'], $datos['cellH'], $datos['dato_letra'], 0, ($datos['align'] ? $datos['align'] : 'L'));
			} else  {
				$pdf->Write(4, $datos['dato_letra']);
			}
		}

		if ($mantencion) {
			// $pdf->Output("../../pdf/factura.pdf","F");
		} else {
			ob_end_clean();
			$pdf->Output($glosa_documento_legal . '_' . $factura->fields['numero'] . '.pdf', 'D');
		}
	}
}
