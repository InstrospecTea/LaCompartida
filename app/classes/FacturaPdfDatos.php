<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once('Numbers/Words.php');

class FacturaPdfDatos extends Objeto {
	function FacturaPdfDatos($sesion, $fields = '', $params = '') {
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

		while ($row = mysql_fetch_assoc($resp)) {
			foreach($row as $tipo_dato => $valor) {
				if ($tipo_dato == 'codigo_tipo_dato') {
					$this->datos[$row['codigo_tipo_dato']]['dato_letra'] = $this->CargarGlosaDato($valor, $id_factura);
				} else {
					$this->datos[$row['codigo_tipo_dato']][$tipo_dato] = $valor;
				}
			}
		}

		$this->papel = $this->datos['tipo_papel'];
		unset($this->datos['tipo_papel']);
	}

	function CargarGlosaDato($tipo_dato, $id_factura) {
		$factura = new Factura($this->sesion);
		$factura->Load($id_factura);

		$cobro = new Cobro($this->sesion);
		$cobro->Load($factura->fields['id_cobro']);

		$contrato = new Contrato($this->sesion);
		$contrato->Load($cobro->fields['id_contrato']);

		$idioma = new Objeto($this->sesion,'','','prm_idioma','codigo_idioma');
		$idioma->Load( $cobro->fields['codigo_idioma'] );

		$condicion_pago = $factura->fields['condicion_pago'];

		switch ($condicion_pago) {
			case '1': $condicion_pago = __('CONTADO');
				break;
			case '3': $condicion_pago = __('Vencimiento 15 días	');
				break;
			case '4': $condicion_pago = __('Vencimiento 30 días	');
				break;
			case '5': $condicion_pago = __('Vencimiento 45 días	');
				break;
			case '6': $condicion_pago = __('Vencimiento 60 días	');
				break;
			case '7': $condicion_pago = __('Vencimiento 75 días	');
				break;
			case '8': $condicion_pago = __('Vencimiento 90 días	');
				break;
			case '9': $condicion_pago = __('Vencimiento 120 días');
				break;
			case '12': $condicion_pago = __('Letra 30 días');
				break;
			case '13': $condicion_pago = __('Letra 45 días');
				break;
			case '14': $condicion_pago = __('Letra 60 días');
				break;
			case '15': $condicion_pago = __('Letra 90 días');
				break;
			case '18': $condicion_pago = __('Cheque 30 días');
				break;
			case '19': $condicion_pago = __('Cheque 45 días');
				break;
			case '20': $condicion_pago = __('Cheque 60 días');
				break;
			case '21': $condicion_pago = __('Cheque a fecha');
				break;
		}

		$query_comodines ="SELECT codigo, glosa FROM prm_codigo WHERE grupo = 'PRM_FACTURA_PDF'";
		$resp_comodines = mysql_query($query_comodines,$this->sesion->dbh) or Utiles::errorSQL($querypapel,__FILE__,__LINE__,$this->sesion->dbh);
		$array_comodines = array();

		while (list($codigo,$glosa) = mysql_fetch_array($resp_comodines)) {
			$array_comodines[$codigo] = $glosa;
		}

		$arreglo_monedas = ArregloMonedas($this->sesion);
		$monto_palabra=new MontoEnPalabra($this->sesion);

		$monto_total_factura = $factura->fields['total'];

		list ($monto_parte_entera, $monto_parte_decimal) = explode('.',$monto_total_factura);

		$glosa_moneda_cero_cien = " 00/100 ".$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda'];
		$glosa_moneda_plural_cero_cien = " 00/100 ".$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda_plural'];

		$glosa_moneda = $arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda'];
		$glosa_moneda_plural = $arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda_plural'];

		if (strlen($monto_parte_decimal) == '2') {
			$fix_decimal = '1';
		} else {
			$fix_decimal = '10';
		}

		if (empty($monto_parte_decimal)) {
			$monto_en_palabra_cero_cien = mb_strtoupper($monto_palabra->ValorEnLetras($monto_parte_entera, $factura->fields['id_moneda'], $glosa_moneda_cero_cien, $glosa_moneda_plural_cero_cien), 'ISO-8859-1');
			$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($monto_parte_entera, 'es'));
			$monto_total_palabra_fix = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural, 'ISO-8859-1');
		} else {
			$monto_en_palabra_cero_cien = mb_strtoupper($monto_palabra->ValorEnLetras($monto_total_factura, $factura->fields['id_moneda'], $glosa_moneda, $glosa_moneda_plural), 'ISO-8859-1');
			$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($monto_parte_entera, 'es'));
			$monto_palabra_parte_decimal = strtoupper(Numbers_Words::toWords($monto_parte_decimal * $fix_decimal, 'es'));
			$monto_total_palabra_fix = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural, 'ISO-8859-1') . ' CON ' . $monto_palabra_parte_decimal . ' CENTAVOS';
		}

		switch( $tipo_dato ) {
			case 'razon_social':
				$glosa_dato = $factura->fields['cliente'];
				break;
			case 'rut':
				$glosa_dato = $factura->fields['RUT_cliente'];
				break;
			case 'telefono':
				$glosa_dato = $contrato->fields['factura_telefono'];
				break;
			case 'fecha_dia':
				$glosa_dato = date("d", strtotime($factura->fields['fecha']));
				break;
			case 'fecha_mes':
				$glosa_dato = strftime("%B", strtotime($factura->fields['fecha']));
				break;
			case 'fecha_numero_mes':
				$glosa_dato = strftime("%m", strtotime($factura->fields['fecha']));
				break;
			case 'fecha_ano':
				$glosa_dato = date("Y", strtotime($factura->fields['fecha']));
				break;
			case 'fecha_ano_ultima_cifra':
				$glosa_dato = substr(date("Y",strtotime($factura->fields['fecha'])),-1);
				break;
			case 'fecha_ano_dos_ultimas_cifras':
				$glosa_dato = substr(date("Y",strtotime($factura->fields['fecha'])),-2);
				break;
			case 'direccion':
				$glosa_dato = $factura->fields['direccion_cliente'];
				break;
			case 'comuna':
				$glosa_dato = $factura->fields['comuna_cliente'];
				break;
			case 'factura_codigopostal':
				$glosa_dato = $factura->fields['factura_codigopostal'];
				break;
			case 'ciudad':
				$glosa_dato = $factura->fields['ciudad_cliente'];
				break;
			case 'giro_cliente':
				$glosa_dato = $factura->fields['giro_cliente'];
				break;
			case 'lugar':
				$glosa_dato = UtilesApp::GetConf($this->sesion, 'LugarFacturacion');
				break;
			case 'nota_factura':
				$glosa_dato = $condicion_pago;
				break;
			case 'descripcion_honorarios':
				$glosa_dato = $factura->fields['descripcion'];
				break;
			case 'descripcion_gastos_con_iva':
				$glosa_dato = $factura->fields['descripcion_subtotal_gastos'];
				break;
			case 'descripcion_gastos_sin_iva':
				$glosa_dato = $factura->fields['descripcion_subtotal_gastos_sin_impuesto'];
				break;
			case 'monto_honorarios':
				$glosa_dato = number_format(
					$factura->fields['subtotal_sin_descuento'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				);
				break;
			case 'monto_gastos_con_iva':
				$glosa_dato = number_format(
					$factura->fields['subtotal_gastos'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				);
				break;
			case 'monto_gastos_sin_iva':
				$glosa_dato = number_format(
					$factura->fields['subtotal_gastos_sin_impuesto'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				);
				break;
			case 'moneda_honorarios':
				$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
				break;
			case 'moneda_gastos_con_iva':
				$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
				break;
			case 'moneda_gastos_sin_iva':
				$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
				break;
			case 'monto_en_palabra':
				$glosa_dato = strtoupper(
					$monto_palabra->ValorEnLetras(
						$factura->fields['total'],
						$factura->fields['id_moneda'],
						$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda'],
						$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda_plural']
					)
				);
				break;
			case 'monto_total_palabra':
				$glosa_dato = $monto_total_palabra_fix;
				break;
			case 'monto_en_palabra_cero_cien':
				$glosa_dato = $monto_en_palabra_cero_cien;
				break;
			case 'porcentaje_impuesto':
				$glosa_dato = $factura->fields['porcentaje_impuesto']."%";
				break;
			case 'moneda_subtotal':
				$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
				break;
			case 'moneda_iva':
				$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
				break;
			case 'moneda_total':
				$glosa_dato = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
				break;
			case 'monto_subtotal':
				$glosa_dato = number_format(
					$factura->fields['subtotal_sin_descuento'] + $factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				);
				break;
			case 'monto_iva':
				$glosa_dato = number_format(
					$factura->fields['iva'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				);
				break;
			case 'monto_total':
				$glosa_dato = number_format(
					$factura->fields['total'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				);
				break;
			case 'monto_total_2':
				$glosa_dato = number_format(
					$factura->fields['total'],
					$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
					$idioma->fields['separador_decimales'],
					$idioma->fields['separador_miles']
				);
				break;
			default:
				if (array_key_exists($tipo_dato, $array_comodines)) {
					$glosa_dato = $array_comodines[$tipo_dato];
				}
		}

		return $glosa_dato;
	}

	function CargarFilaDato($id_factura) {
		$factura = new Factura($this->sesion);
		$factura->Load($id_factura);

		$cobro = new Cobro($this->sesion);
		$cobro->Load($factura->fields['id_cobro']);

		$contrato = new Contrato($this->sesion);
		$contrato->Load($cobro->fields['id_contrato']);

		$idioma = new Objeto($this->sesion, '', '', 'prm_idioma', 'codigo_idioma');
		$idioma->Load($cobro->fields['codigo_idioma']);

		$arreglo_monedas = ArregloMonedas($this->sesion);
		$monto_palabra = new MontoEnPalabra($this->sesion);
		$fila = array();

		$fila['razon_social'] = $factura->fields['cliente'];
		$fila['rut'] = $factura->fields['RUT_cliente'];
		$fila['telefono'] = $contrato->fields['factura_telefono'];
		$fila['comuna'] = $factura->fields['comuna_cliente'];
		$fila['ciudad'] = $factura->fields['ciudad_cliente'];
		$fila['factura_codigopostal'] = $factura->fields['factura_codigopostal'];
		$fila['giro_cliente'] = $factura->fields['giro_cliente'];
		$fila['lugar'] = UtilesApp::GetConf($this->sesion, 'LugarFacturacion');
		$fila['nota_factura'] = $factura->fields['condicion_pago'];
		$fila['fecha_dia'] = date("d",strtotime($factura->fields['fecha']));
		$fila['fecha_mes'] = strftime("%B",strtotime($factura->fields['fecha']));
		$fila['fecha_numero_mes'] = strftime("%m",strtotime($factura->fields['fecha']));
		$fila['fecha_ano'] = date("Y",strtotime($factura->fields['fecha']));
		$fila['fecha_ano_ultima_cifra'] = substr(date("Y",strtotime($factura->fields['fecha'])),-1);
		$fila['fecha_ano_dos_ultimas_cifras'] = substr(date("Y",strtotime($factura->fields['fecha'])),-2);
		$fila['direccion'] = $factura->fields['direccion_cliente'];
		$fila['descripcion_honorarios'] = $factura->fields['descripcion'];
		$fila['descripcion_gastos_con_iva'] = $factura->fields['descripcion_subtotal_gastos'];
		$fila['descripcion_gastos_sin_iva'] = $factura->fields['descripcion_subtotal_gastos_sin_impuesto'];
		$fila['monto_honorarios'] = number_format(
			$factura->fields['subtotal_sin_descuento'],
			$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
			$idioma->fields['separador_decimales'],
			$idioma->fields['separador_miles']
		);
		$fila['monto_gastos_con_iva'] = number_format(
			$factura->fields['subtotal_gastos'],
			$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
			$idioma->fields['separador_decimales'],
			$idioma->fields['separador_miles']
		);
		$fila['monto_gastos_sin_iva'] = number_format(
			$factura->fields['subtotal_gastos_sin_impuesto'],
			$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
			$idioma->fields['separador_decimales'],
			$idioma->fields['separador_miles']
		);
		$fila['moneda_honorarios'] = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
		$fila['moneda_gastos_con_iva'] = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
		$fila['moneda_gastos_sin_iva'] = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
		$fila['monto_en_palabra_cero_cien'] = $monto_en_palabra_cero_cien;
		$fila['monto_total_palabra'] = $monto_total_palabra_fix;
		$fila['monto_en_palabra'] = strtoupper(
			$monto_palabra->ValorEnLetras(
				$factura->fields['total'],
				$factura->fields['id_moneda'],
				$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda'],
				$arreglo_monedas[$factura->fields['id_moneda']]['glosa_moneda_plural']
			)
		);
		$fila['porcentaje_impuesto'] = $factura->fields['porcentaje_impuesto']."%";
		$fila['moneda_subtotal'] = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
		$fila['moneda_iva'] = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
		$fila['moneda_total'] = $arreglo_monedas[$factura->fields['id_moneda']]['simbolo'];
		$fila['monto_subtotal'] = number_format(
			$factura->fields['subtotal_sin_descuento'] + $factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
			$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
			$idioma->fields['separador_decimales'],
			$idioma->fields['separador_decimales']
		);
		$fila['monto_iva'] = number_format(
			$factura->fields['iva'],
			$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
			$idioma->fields['separador_decimales'],
			$idioma->fields['separador_miles']
		);
		$fila['monto_total'] = number_format(
			$factura->fields['total'],
			$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
			$idioma->fields['separador_decimales'],
			$idioma->fields['separador_miles']
		);
		$fila['monto_total_2'] = number_format(
			$factura->fields['total'],
			$arreglo_monedas[$factura->fields['id_moneda']]['cifras_decimales'],
			$idioma->fields['separador_decimales'],
			$idioma->fields['separador_miles']
		);

		return $fila;
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

		$this->CargarDatos($id_factura, $id_documento_legal, $factura->fields['id_estudio']); // esto trae la posicion, tamaño y glosa de todos los campos más los datos del papel en la variable $this->papel;

		if(count($this->papel)) {
			$pdf = new FPDF($orientacion, 'mm', array($this->papel['cellW'], $this->papel['cellH']));
			$pdf->SetMargins($this->papel['coordinateX'], $this->papel['coordinateY']);
			$pdf->SetAutoPageBreak(true, $margin);
		} else {
			// P: hoja vertical
			// mm: todo se mide en milímetros
			// Letter: formato de hoja
			$pdf = new FPDF($orientacion, 'mm', $format);
		}

		$query = "SELECT codigo, glosa FROM prm_documento_legal WHERE id_documento_legal = '{$factura->fields['id_documento_legal']}'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($codigo_documento_legal, $glosa_documento_legal) = mysql_fetch_array($resp);

		$pdf->SetTitle($glosa_documento_legal . ' ' . $factura->fields['numero']);

		// La orientación y formato de la página son los mismos que del documento
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
			$pdf->Output($glosa_documento_legal . '_' . $factura->fields['numero'] . '.pdf', 'D');
		}
	}
}
