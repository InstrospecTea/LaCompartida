<?php

require_once dirname(__FILE__) . '/../conf.php';

class Factura extends Objeto {
	var $max_numero = 1000000000;

	public static $configuracion_reporte = array (
		array (
			'field' => 'codigo_cliente',
			'title' => 'Código Cliente',
			'visible' => false,
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente',
		),
		array (
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento',
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo',
		),
		array (
			'field' => 'serie_documento_legal',
			'title' => 'Serie Documento',
			'visible' => false,
		),
		array (
			'field' => 'numero',
			'format' => 'number',
			'title' => 'N° Documento',
			'extras' => array(
				'subtotal' => false
			)
		),
		array (
			'field' => 'factura_rsocial',
			'title' => 'Razón Social',
		),
		array (
			'field' => 'glosas_asunto',
			'title' => 'Asuntos',
		),
		array (
			'field' => 'codigos_asunto',
			'title' => 'Códigos Asuntos',
		),
		array (
			'field' => 'encargado_comercial',
			'title' => 'Encargado Comercial',
		),
		array (
			'field' => 'descripcion',
			'title' => 'Descripción Factura',
		),
		array (
			'field' => 'id_cobro',
			'title' => 'N° Liquidación',
		),
		array (
			'field' => 'idcontrato',
			'title' => 'Acuerdo Comercial',
			'visible' => false,
		),
		array (
			'field' => 'simbolo',
			'visible' => false,
			'title' => 'Símbolo Moneda',
		),
		array (
			'field' => 'tipo_cambio',
			'format' => 'number',
			'title' => 'Tipo Cambio',
			'visible' => false,
		),
		array (
			'field' => 'honorarios',
			'format' => 'number',
			'title' => 'Honorarios',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'subtotal_gastos',
			'format' => 'number',
			'title' => 'Subtotal Gastos',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'subtotal_gastos_sin_impuesto',
			'format' => 'number',
			'title' => 'Subtotal Gastos sin impuesto',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'subtotal',
			'format' => 'number',
			'title' => 'Subtotal',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'iva',
			'format' => 'number',
			'title' => 'IVA',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'total',
			'format' => 'number',
			'title' => 'Total',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'monto_real',
			'format' => 'number',
			'title' => 'Monto Real',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'observaciones',
			'title' => 'Observaciones',
		),
		array (
			'field' => 'pagos',
			'format' => 'number',
			'title' => 'Pagos',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'saldo',
			'format' => 'number',
			'title' => 'Saldo',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'fecha_ultimo_pago',
			'format' => 'date',
			'title' => 'Fecha Último Pago',
		),
		array (
			'field' => 'estado',
			'title' => 'Estado Documento',
		),
		array (
			'field' => 'codigo_idioma',
			'title' => 'Código Idioma',
			'visible' => false,
		),
		array (
			'field' => 'cifras_decimales',
			'title' => 'Cifras Decimales',
			'visible' => false,
		),
	);

	function Factura($sesion, $fields = "", $params = "") {
		$this->tabla = "factura";
		$this->campo_id = "id_factura";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->log_update = true;
	}

	function Id($id = null) {
		if ($id) {
			$this->fields[$this->campo_id] = $id;
		}
		if (empty($this->fields[$this->campo_id])) {
			return false;
		}
		return $this->fields[$this->campo_id];
	}

	function LoadByCobro($id_cobro) {
		$query = "SELECT id_factura FROM factura WHERE anulado = 0 AND id_cobro = '$id_cobro';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if ($id) {
			return $this->Load($id);
		}
		return false;
	}

	function LoadByNumero($numero) {
		$query = "SELECT id_factura FROM factura WHERE numero = '$numero';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if ($id) {
			return $this->Load($id);
		}
		return false;
	}

	function ObtenerValorReal($id_factura) {
		$query = "SELECT ( (-1) * SUM( ccfm.monto_bruto * ccfmm.tipo_cambio / ccfmmbase.tipo_cambio ) ) as valor_real
					FROM cta_cte_fact_mvto ccfm
						JOIN factura f USING ( id_factura )
                                                JOIN prm_estado_factura pef ON f.id_estado = pef.id_estado
						JOIN factura fp ON ( fp.id_factura = IF( ( f.id_factura_padre IS NULL OR f.id_factura_padre = 0)	, f.id_factura, f.id_factura_padre ) )
						JOIN cta_cte_fact_mvto_moneda ccfmm ON ( ccfm.id_cta_cte_mvto = ccfmm.id_cta_cte_fact_mvto
							AND ccfm.id_moneda = ccfmm.id_moneda )
						JOIN cta_cte_fact_mvto_moneda ccfmmbase ON ( ccfm.id_cta_cte_mvto = ccfmmbase.id_cta_cte_fact_mvto
							AND ccfmmbase.id_moneda = fp.id_moneda )
					WHERE f.id_factura =  '$id_factura'
						OR ( f.id_factura_padre = '$id_factura' AND pef.glosa NOT LIKE '%ANULADO%' );"; //11357
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list( $valor_real ) = mysql_fetch_array($resp);

		if ($valor_real) {
			return $valor_real;
		}
		return false;
	}

	function ObtenerIdsDocumentos($id_factura) {
		$query = "SELECT CONCAT(ccfm.tipo_mvto, '::', f.numero) as bloque
					FROM cta_cte_fact_mvto ccfm
						JOIN factura f USING ( id_factura )
					WHERE f.id_factura_padre =  '$id_factura';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$ids_array = array();
		while (list( $bloque ) = mysql_fetch_array($resp)) {
			$ids_array[] = $bloque;
		}
		$ids = implode("||", $ids_array);

		if ($ids) {
			return $ids;
		}
		return false;
	}

	function SaldoAdelantosDisponibles($codigo_cliente, $id_contrato, $pago_honorarios, $pago_gastos, $id_moneda = null, $tipos_cambio = null) {
		$monedas = ArregloMonedas($this->sesion);
		if (empty($tipos_cambio)) {
			$tipos_cambio = array();
			foreach ($monedas as $id => $moneda) { //uf:20000, us:500, idmoneda:us. adelanto de 100 uf -> us4000
				$tipos_cambio[$id] = $moneda['tipo_cambio'];
			}
		}
		$cambios = array();
		foreach ($tipos_cambio as $id => $cambio) {
			$cambios[$id] = $id_moneda ? $cambio / $tipos_cambio[$id_moneda] : $cambio;
		}
		$where_contrato = '';
		if ($id_contrato) {
			$where_contrato = " AND (id_contrato = '$id_contrato' OR id_contrato IS NULL) ";
		}
		$query = "SELECT saldo_pago, documento.id_moneda, prm_moneda.tipo_cambio
			FROM documento
			JOIN prm_moneda ON documento.id_moneda = prm_moneda.id_moneda
			WHERE es_adelanto = 1 AND codigo_cliente = '$codigo_cliente'
			$where_contrato AND saldo_pago < 0";
		if (empty($pago_honorarios)) {
			$query.= ' AND pago_gastos = 1';
		} else if (empty($pago_gastos)) {
			$query.= ' AND pago_honorarios = 1';
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$saldo = 0;
		while (list($saldo_pago, $moneda_pago, $tipo_cambio) = mysql_fetch_array($resp)) {
			if ($id_moneda) {
				$tipo_cambio = $cambios[$moneda_pago];
			}
			$saldo += -$saldo_pago * $tipo_cambio;
		}
		if (!$saldo) {
			return '';
		}
		if ($id_moneda) {
			return $monedas[$id_moneda]['simbolo'] . ' ' . number_format($saldo, 2);
		}
		return $saldo;
	}


	function Escribir() {
		if (!$this->Id()) {
			$this->Edit('asiento_contable', $this->MaxNumeroAsientoContable() + 1);
			$this->Edit('mes_contable', date('Ym'));
		}

		$cobro = new Cobro($this->sesion);
		if ($cobro->Load($this->fields['id_cobro'])) {
			$this->Edit('id_contrato', $cobro->fields['id_contrato']);
		}
		if ($this->Write()) {
			if ($cobro->Load($this->fields['id_cobro'])) {
				$cobro->Edit('documento', $this->ListaDocumentosLegales($cobro));
				$cobro->Write();

				/* if( ( $this->fields['subtotal_gastos'] > 0 || $this->fields['subtotal_gastos_sin_impuesto'] > 0 ) && $this->ComparaGastos() )
				  {
				  $this->GastosAsociaCobro();
				  } */
			}
			return true;
		} else {
			return false;
		}
	}

	/* function ComparaGastos() {
	  $factura_subtotal_gastos = 0;
	  $factura_subtotal_gastos_sin_impuesto = 0;
	  $documento_subtotal_gastos = 0;
	  $documento_subtotal_gastos_sin_impuesto = 0;

	  $query = "SELECT subtotal_gastos, subtotal_gastos_sin_impuesto FROM factura
	  WHERE id_cobro = " . $this->fields['id_cobro'] . "
	  AND ( subtotal_gastos > 0 OR subtotal_gastos_sin_impuesto > 0 ) ";
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
	  list( $factura_subtotal_gastos, $factura_subtotal_gastos_sin_impuesto ) = mysql_fetch_array($resp);

	  $query = "SELECT subtotal_gastos, subtotal_gastos_sin_impuesto FROM documento
	  WHERE id_cobro = " . $this->fields['id_cobro'] . "
	  AND ( subtotal_gastos > 0 OR subtotal_gastos_sin_impuesto > 0 ) ";
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
	  list( $documento_subtotal_gastos, $documento_subtotal_gastos_sin_impuesto ) = mysql_fetch_array($resp);

	  if ($factura_subtotal_gastos != $documento_subtotal_gastos || $factura_subtotal_gastos_sin_impuesto != $documento_subtotal_gastos_sin_impuesto) {
	  return false;
	  }
	  return true;
	  }

	  function GastosAsociaCobro() {
	  $query = "SELECT id_movimiento FROM cta_corriente WHERE id_cobro = " . $this->fields['id_cobro'] . "";
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

	  $gasto = new Gasto($this->sesion);
	  while (list($id_movimiento) = mysql_fetch_array($resp)) {
	  $gasto->Load($id_movimiento);

	  if ($this->fields['id_estado'] == 5) {
	  $gasto->Edit('id_factura', "NULL");
	  $gasto->Edit('fecha_factura', "NULL");
	  } else {
	  $gasto->Edit('id_factura', $this->fields['id_factura']);
	  $gasto->Edit('fecha_factura', $this->fields['fecha']);
	  }
	  if (!$gasto->Write()) {
	  //return false;
	  }
	  }

	  return true;
	  } */

	function PrimerTipoDocumentoLegal() {
		$query = "SELECT id_documento_legal FROM prm_documento_legal ORDER BY id_documento_legal ASC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_documento_legal) = mysql_fetch_array($resp);
		return $id_documento_legal;
	}

	function GeneraHTMLFactura($id_formato_factura = null) {
		if ($this->fields['id_moneda'] != 2 && ( ( method_exists('Conf', 'InfoBancariaCYC') && Conf::InfoBancariaCYC() ) || ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'InfoBancariaCYC') ) )) {
			$templateData = UtilesApp::TemplateFactura($this->sesion, 2);
			$cssData = UtilesApp::TemplateFacturaCSS($this->sesion, 2);
			$xmlData = UtilesApp::TemplateFacturaXML($this->sesion, 2);
			$xmlBit = UtilesApp::TemplateBitXML($this->sesion, 2);
		} else {
			if ($id_formato_factura != null) {
				$templateData = UtilesApp::TemplateFactura($this->sesion, $id_formato_factura);
				$cssData = UtilesApp::TemplateFacturaCSS($this->sesion, $id_formato_factura);
				$xmlData = UtilesApp::TemplateFacturaXML($this->sesion, $id_formato_factura);
				$xmlBit = UtilesApp::TemplateBitXML($this->sesion, $id_formato_factura);
			} else {
				// verificar el tipo de documento legal, y mostrar ese formato, sino mostrar por defecto
				$query = "";
				if ($this->fields['id_documento_legal'] > 0) {
					$query = "SELECT id_factura_formato FROM factura_rtf WHERE id_tipo='" . $this->fields['id_documento_legal'] . "' order by id_factura_formato asc limit 0,1";
				} else {
					$query = "SELECT id_factura_formato FROM factura_rtf ORDER BY id_factura_formato ASC LIMIT 1";
				}
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($id_formato_factura) = mysql_fetch_array($resp);
				if ($id_formato_factura > 0) {
					$templateData = UtilesApp::TemplateFactura($this->sesion, $id_formato_factura);
					$cssData = UtilesApp::TemplateFacturaCSS($this->sesion, $id_formato_factura);
					$xmlData = UtilesApp::TemplateFacturaXML($this->sesion, $id_formato_factura);
					$xmlBit = UtilesApp::TemplateBitXML($this->sesion, $id_formato_factura);
				} else {
					$templateData = UtilesApp::TemplateFactura($this->sesion);
					$cssData = UtilesApp::TemplateFacturaCSS($this->sesion);
					$xmlData = UtilesApp::TemplateFacturaXML($this->sesion);
					$xmlBit = UtilesApp::TemplateBitXML($this->sesion);
				}
			}
		}



		$templateData = $this->ReemplazarMargenes($templateData);
		$parser = new TemplateParser($templateData);
		//echo '<pre>';print_r($parser);echo '<pre>';die();
		$query = "SELECT cobro.codigo_idioma
							FROM factura
							LEFT JOIN cobro ON factura.id_cobro=cobro.id_cobro
							WHERE factura.id_factura=" . $this->fields['id_factura'];
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($lang) = mysql_fetch_array($resp);

		if (!$lang) {
			$query = "SELECT contrato.codigo_idioma
							FROM factura
							LEFT JOIN cliente ON factura.codigo_cliente=cliente.codigo_cliente
							LEFT JOIN contrato ON contrato.id_contrato = cliente.id_contrato
							WHERE factura.id_factura=" . $this->fields['id_factura'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($lang) = mysql_fetch_array($resp);
		}

		$cobro = new Cobro($this->sesion);
		if ($cobro->Load($this->fields['id_cobro'])) {
			global $x_detalle_profesional;
			global $x_resumen_profesional;
			list( $x_detalle_profesional, $x_resumen_profesional ) = $cobro->DetalleProfesional();

			global $x_resultados;
			$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro']);
			global $x_cobro_gastos;
			$x_cobro_gastos = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);
		}

		$html = $this->GenerarDocumento($parser, 'CARTA_FACTURA', $lang);

		$html_css = array();
		$html_css['html'] = $html;
		$html_css['css'] = $cssData;
		$html_css['xmlbit'] = $xmlBit;
		if ($xmlBit) {
			$xml = $this->GenerarDocumento($xmlData, 'ENCABEZADO', $lang, true);
			$xml = $this->GenerarDocumento($xml, 'DATOS_FACTURA', $lang, true);
			$xml = $this->GenerarDocumento($xml, 'BOTTOM', $lang, true);
			$html_css['xml'] = str_replace(array('UTF-8', '&nbsp;', '<br>', '<br/>', "<br />\n", '<br />', '<v:shape '), array('ISO-8859-1', '&#160;', '&#xD;', '&#xD;', '</w:t></w:r></w:p><w:p><w:r><w:t>', '</w:t></w:r></w:p><w:p><w:r><w:t>', '<v:shape filled="f" stroked="f" '), $xml);
		}
		return $html_css;
	}

	function ReemplazarMargenes($html) {
		$espacios_monto_palabra = "";
		for ($i = 0; $i < UtilesApp::GetConf($this->sesion, 'EspacioMontoPalabra'); $i++) {
			$espacios_monto_palabra .= "&nbsp;";
		}
		$html = str_replace('%espacio_encabezado%', UtilesApp::GetConf($this->sesion, 'EspacioEncabezado'), $html);
		$html = str_replace('%margen_izquierda_rsocial%', UtilesApp::GetConf($this->sesion, 'MargenIzquierdaRsocial'), $html);
		$html = str_replace('%espacio_cuerpo%', UtilesApp::GetConf($this->sesion, 'EspacioCuerpo'), $html);
		$html = str_replace('%espacios_monto_palabra%', $espacios_monto_palabra, $html);
		$html = str_replace('%margen_derecha_cuerpo%', UtilesApp::GetConf($this->sesion, 'MargenDerechaCuerpo'), $html);
		$html = str_replace('%ancho_columna_dia%', UtilesApp::GetConf($this->sesion, 'AnchoColumnaDia'), $html);
		$html = str_replace('%ancho_columna_mes%', UtilesApp::GetConf($this->sesion, 'AnchoColumnaMes'), $html);
		$html = str_replace('%ancho_columna_anyo%', UtilesApp::GetConf($this->sesion, 'AnchoColumnaAnyo'), $html);
		$html = str_replace('%ancho_columna_base_encabezado%', UtilesApp::GetConf($this->sesion, 'AnchoColumnaBaseEncabezado'), $html);
		$html = str_replace('%ancho_columna_base_cuerpo%', UtilesApp::GetConf($this->sesion, 'AnchoColumnaBaseCuerpo'), $html);
		$html = str_replace('%margen_izquierda_cuerpo%', UtilesApp::GetConf($this->sesion, 'MargenIzquierdaCuerpo'), $html);
		$html = str_replace('%ancho_columna_monto_subtotal%', UtilesApp::GetConf($this->sesion, 'AnchoColumnaMontoSubtotal'), $html);
		$html = str_replace('%ancho_columna_monto_iva%', UtilesApp::GetConf($this->sesion, 'AnchoColumnaMontoIVA'), $html);
		$html = str_replace('%ancho_columna_monto_total%', UtilesApp::GetConf($this->sesion, 'AnchoColumnaMontoTotal'), $html);

		return $html;
	}

	function GenerarDocumento($parser_factura, $theTag = '', $lang = 'es', $xml = false) {
		if (!$xml && !isset($parser_factura->tags[$theTag])) {
			return;
		}

		$idioma = new Objeto($this->sesion, '', '', 'prm_idioma', 'codigo_idioma');
		$idioma->Load($lang);

		global $cobro_moneda;
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		$cobro = new NotaCobro($this->sesion);
		$cobro->Load($this->fields['id_cobro']);
		$cobro->LoadAsuntos();

		$tipo_dl = $this->fields['id_documento_legal'];   /* tipo documento legal Factura, Nota de crédito, nota de débito, boleta */

		$tipo_cambio_moneda_total = $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];

		$html2 = ($xml) ? $parser_factura : $parser_factura->tags[$theTag];

		switch ($theTag) {
			case 'CARTA_FACTURA':
				$html2 = str_replace('%ENCABEZADO%', $this->GenerarDocumento($parser_factura, 'ENCABEZADO', $lang), $html2);
				$html2 = str_replace('%DATOS_FACTURA%', $this->GenerarDocumento($parser_factura, 'DATOS_FACTURA', $lang), $html2);
				$html2 = str_replace('%BOTTOM%', $this->GenerarDocumento($parser_factura, 'BOTTOM', $lang), $html2);
				$html2 = str_replace('%BOTTOM_COPIA%', $this->GenerarDocumento($parser_factura, 'BOTTOM_COPIA', $lang), $html2);
				$html2 = str_replace('%CLIENTE%', $cobro->GenerarSeccionCliente($parser_factura->tags['CLIENTE'], $idioma, $moneda, $asunto), $html2);
				$html2 = str_replace('%SALTO_PAGINA%', $cobro->GenerarDocumentoComun($parser_factura, 'SALTO_PAGINA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html3, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html2);

				if ($cobro->fields['modalidad_calculo'] == 1) {
					$html2 = str_replace('%DETALLE_COBRO%', $cobro->GenerarDocumento2($parser_factura, 'DETALLE_COBRO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html3, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html2);
					$html2 = str_replace('%ASUNTOS%', $cobro->GenerarDocumento2($parser_factura, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html3, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html2);
					$html2 = str_replace('%GASTOS%', $cobro->GenerarDocumento2($parser_factura, 'GASTOS', $parser_carta, $moneda_Cliente_cambio, $moneda_cli, $lang, $html3, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html2);
				} else {
					$html2 = str_replace('%DETALLE_COBRO%', $cobro->GenerarDocumento($parser_factura, 'DETALLE_COBRO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html3, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html2);
					$html2 = str_replace('%ASUNTOS%', $cobro->GenerarDocumento($parser_factura, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html3, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html2);
					$html2 = str_replace('%GASTOS%', $cobro->GenerarDocumento($parser_factura, 'GASTOS', $parser_carta, $moneda_Cliente_cambio, $moneda_cli, $lang, $html3, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html2);
				}
				break;

			case 'ENCABEZADO':
				$PdfLinea1 = UtilesApp::GetConf($this->sesion, 'PdfLinea1');
				$PdfLinea2 = UtilesApp::GetConf($this->sesion, 'PdfLinea2');
				$PdfLinea3 = UtilesApp::GetConf($this->sesion, 'PdfLinea3');
				$CiudadSignatura = UtilesApp::GetConf($this->sesion, 'CiudadSignatura');
				$logo_doc = UtilesApp::GetConf($this->sesion, 'LogoDoc');

				$html2 = str_replace('%linea1%', $PdfLinea1, $html2);
				$html2 = str_replace('%linea2%', $PdfLinea2, $html2);
				$html2 = str_replace('%linea3%', $PdfLinea3, $html2);
				$html2 = str_replace('%ciudad%', $CiudadSignatura, $html2);
				$html2 = str_replace('%LogoDoc%', $logo_doc, $html2);
				$query = "SELECT titulo_contacto, contacto, apellido_contacto, cobro.id_cobro, factura.numero,
													 CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre, factura.fecha as fecha,
													prm_documento_legal.glosa
											FROM contrato
											LEFT JOIN cobro ON contrato.id_contrato=cobro.id_contrato
											LEFT JOIN factura ON cobro.id_cobro=factura.id_cobro
											LEFT JOIN prm_documento_legal ON prm_documento_legal.id_documento_legal = factura.id_documento_legal
											LEFT JOIN usuario ON contrato.id_usuario_responsable=usuario.id_usuario
											WHERE id_factura=" . $this->fields['id_factura'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list( $titulo_contacto, $contacto, $apellido_contacto, $id_cobro, $numero_factura, $encargado_comercial, $fecha_factura, $glosa_tipo_doc) = mysql_fetch_array($resp);
				$glosa_tipo_doc_mayus = str_replace('é', 'É', strtoupper($glosa_tipo_doc));
				if ($lang == 'es') {
					$html2 = str_replace('%numero_factura%', __($glosa_tipo_doc) . ' No. ' . $numero_factura, $html2);
					$html2 = str_replace('%Senores%', 'SEÑORES', $html2);
					$html2 = str_replace('%tipo_doc_legal%', $glosa_tipo_doc_mayus, $html2);
					$html2 = str_replace('%numero_doc_legal%', $numero_factura, $html2);
					$html2 = str_replace('%numero_factura_cyc%', __('FACTURA DE VENTA NO.').' '. $numero_factura, $html2);
				} else {
					$titulos_es = array('Sr.', 'Sra.', 'Srta.');
					$titulos_en = array('Mr.', 'Mrs.', 'Ms.');
					$titulo_contacto = str_replace($titulos_es, $titulos_en, $titulo_contacto);
					$html2 = str_replace('%numero_factura%', __($glosa_tipo_doc) . ' No. ' . $numero_factura, $html2);
					$html2 = str_replace('%Senores%', 'Messrs', $html2);
					$html2 = str_replace('%numero_factura_cyc%', __('INVOICE').' '. $numero_factura, $html2);
				}
				$html2 = str_replace('%subtitulo%', '', $html2);

				if (method_exists('Conf', 'Server') && method_exists('Conf', 'ImgDir')) {
					$html2 = str_replace('%logo_cobro%', Conf::Server() . Conf::ImgDir(), $html2);
				}

				$fecha_factura = $this->fields['fecha'];

				$glosa_cliente = $this->fields['cliente'];
				$direccion_cliente = $this->fields['direccion_cliente'];
				if( UtilesApp::existecampo('ciudad_cliente', 'factura', $this->sesion)) {
					$ciudad_cliente = $this->fields['ciudad_cliente'];
				}
				if( UtilesApp::existecampo('comuna_cliente', 'factura', $this->sesion)) {
				$comuna_cliente = $this->fields['comuna_cliente'];
				}
				if( UtilesApp::existecampo('giro_cliente', 'factura', $this->sesion)) {
				$giro_cliente = $this->fields['giro_cliente'];
				}

				$MAX = UtilesApp::GetConf($this->sesion, 'AnchoMaximoGlosaCliente');

				if ($MAX > 3 && strlen($glosa_cliente) > $MAX) {
					$glosa_cliente = substr($glosa_cliente, 0, $MAX - 3) . '...';
				}

				$MAX = UtilesApp::GetConf($this->sesion, 'AnchoMaximoDireccionCliente');

				if ($MAX > 3 && strlen($direccion_cliente) > $MAX) {
					$direccion_cliente = substr($direccion_cliente, 0, $MAX - 3) . '...';
				}

				$html2 = str_replace('%nombre_cliente%', $this->fields['cliente'], $html2);
				$html2 = str_replace('%glosa_cliente%', $glosa_cliente, $html2);
				$html2 = str_replace('%glosa_cliente_mayuscula%', strtoupper($glosa_cliente), $html2);
				$html2 = str_replace('%encargado_comercial%', $encargado_comercial, $html2);
				$html2 = str_replace('%rut_cliente%', $this->fields['RUT_cliente'], $html2);
				$meses_org = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
				$month_short = array('JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC');
				$mes_corto = array('JAN', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC');
				$mes_largo_es = array('ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE');
				$html2 = str_replace('%nombre_encargado%', strtoupper($titulo_contacto . ' ' . $contacto . ' ' . $apellido_contacto), $html2);
				$html2 = str_replace('%direccion_cliente%', $direccion_cliente, $html2);
				$html2 = str_replace('%direccion_cliente_mayuscula%', strtoupper($direccion_cliente), $html2);
				$html2 = str_replace('%comuna_cliente%', $comuna_cliente, $html2);
				$html2 = str_replace('%ciudad_cliente%', $ciudad_cliente, $html2);
				$html2 = str_replace('%giro_cliente%', UtilesApp::GetConf($this->sesion, 'LugarFacturacion'), $html2);
				$html2 = str_replace('%lugar_facturacion%', UtilesApp::GetConf($Sesion, $direccion_cliente), $html2);
				$html2 = str_replace('%num_dia%', date('d', strtotime($fecha_factura)), $html2);
				$html2 = str_replace('%glosa_mes%', str_replace($meses_org, $mes_largo_es, date('M', strtotime($fecha_factura))), $html2);
				$html2 = str_replace('%num_anio%', date('Y', strtotime($fecha_factura)), $html2);
				$html2 = str_replace('%num_mes%', date('m', strtotime($fecha_factura)), $html2);
				$html2 = str_replace('%num_anio_2cifras%', date('y', strtotime($fecha_factura)), $html2);
				$anio_yyyy = date('Y', strtotime($fecha_factura));
				$html2 = str_replace('%num_anio_ultimacifra%', $anio_yyyy[3], $html2);
				if ($lang == 'es') {
					$html2 = str_replace('%fecha_actual%', str_replace($meses_org, $mes_corto, date('j-M-y', strtotime($fecha_factura))), $html2);
					$html2 = str_replace('%glosa_fecha%', 'FECHA', $html2);
					$html2 = str_replace('%ATN%', 'ATENCION', $html2);
					$html2 = str_replace('%id_cobro%', '  ' . $id_cobro, $html2);
				} elseif ($lang == 'en') {
					$html2 = str_replace('%fecha_actual%', str_replace($meses_org, $month_short, date('M-d-y', strtotime($fecha_factura))), $html2);
					$html2 = str_replace('%glosa_fecha%', 'DATE', $html2);
					$html2 = str_replace('%ATN%', 'Attention', $html2);
					$html2 = str_replace('%id_cobro%', '   <br> INVOICE No.   ' . $id_cobro, $html2);
				}
				break;

			case 'DATOS_FACTURA':

				$select_col = "";
				if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
					$select_col = ",
									factura.descripcion_subtotal_gastos,
									factura.descripcion_subtotal_gastos_sin_impuesto,
									factura.subtotal_gastos,
									factura.subtotal_gastos_sin_impuesto";
				}

				$query = "SELECT
									factura.id_moneda,
									factura.descripcion,
									cobro.id_cobro,
									cobro.id_moneda,
									cobro.fecha_ini,
									cobro.fecha_fin,
									cobro.porcentaje_impuesto,
									prm_moneda.glosa_moneda,
									prm_moneda.glosa_moneda_plural,
									prm_moneda.simbolo,
									prm_moneda.cifras_decimales,
									factura.subtotal,
									factura.subtotal_sin_descuento,
									factura.descuento_honorarios,
									factura.honorarios,
									factura.subtotal_gastos,
									factura.gastos,
									factura.iva,
									factura.total
									$select_col
									FROM factura
									LEFT JOIN cobro ON factura.id_cobro=cobro.id_cobro
									LEFT JOIN prm_moneda ON factura.id_moneda=prm_moneda.id_moneda
									WHERE id_factura=" . $this->fields['id_factura'];

				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
					list($factura_id_moneda, $factura_descripcion, $id_cobro, $cobro_id_moneda, $fecha_ini, $fecha_fin, $porcentaje_impuesto, $glosa_moneda, $glosa_moneda_plural, $simbolo, $cifras_decimales, $monto_subtotal, $monto_subtotal_sin_descuento, $descuento_honorarios, $honorarios, $subtotal_gastos, $monto_gastos, $impuesto, $total, $descripcion_subtotal_gastos, $descripcion_subtotal_gastos_sin_impuesto, $subtotal_gastos_con_impuesto, $subtotal_gastos_sin_impuesto) = mysql_fetch_array($resp);
				} else {
					list($factura_id_moneda, $factura_descripcion, $id_cobro, $cobro_id_moneda, $fecha_ini, $fecha_fin, $porcentaje_impuesto, $glosa_moneda, $glosa_moneda_plural, $simbolo, $cifras_decimales, $monto_subtotal, $monto_subtotal_sin_descuento, $descuento_honorarios, $honorarios, $subtotal_gastos, $monto_gastos, $impuesto, $total) = mysql_fetch_array($resp);
				}

				// FFF 2012-09-15 normaliza saltos de linea en la descripcion. Y sí, ya probé con preg_replace y tiene comportamiento impredecible.
				$factura_descripcion=str_replace(array('\r\n','\n\r',"\r\n","\n\r",'\n','\r'), "\n", $factura_descripcion);

				$moneda_factura = new Moneda($this->sesion);
				$moneda_factura->Load($factura_id_moneda);
				$query = "SELECT glosa_asunto , codigo_asunto
											FROM cobro
											JOIN contrato ON cobro.id_contrato=contrato.id_contrato
											JOIN asunto ON contrato.id_contrato=asunto.id_contrato
											WHERE cobro.id_cobro='" . $id_cobro . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$i = 1;
				while (list($glosa_asunto, $codigo_asunto) = mysql_fetch_array($resp)) {
					if ($i == 1) {
						$asuntos = $glosa_asunto;
						$cod_asuntos = $codigo_asunto;
					} else {
						$asuntos .= ', ' . $glosa_asunto;
						$cod_asuntos .= ', ' . $codigo_asunto;
					}
					$i++;
				}


				if (UtilesApp::GetConf($this->sesion, 'CalculacionCyC')) {
					/* esto habría que mejorarlo en el caso de que se les ocurriera facturar en más de 1 documento */
					$query_cyc = "SELECT
									subtotal_honorarios,
									subtotal_sin_descuento,
									descuento_honorarios,
									honorarios,
									impuesto,
									subtotal_gastos,
									subtotal_gastos_sin_impuesto,
									subtotal_sin_descuento

								FROM documento
								WHERE id_cobro = '{$this->fields["id_cobro"]}'
									AND tipo_doc='N'
								;";

					//echo $query_cyc; exit;
					$resp_cyc = mysql_query($query_cyc, $this->sesion->dbh) or Utiles::errorSQL($query_cyc, __FILE__, __LINE__, $this->sesion->dbh);
					list( $monto_subtotal,
						$monto_subtotal_sin_descuento,
						$descuento_honorarios,
						$honorarios_con_descuento_con_impuesto,
						$impuesto_factura,
						$subtotal_gastos,
						$subtotal_gastos_sin_impuesto,
						$subtotal_sin_descuento) = mysql_fetch_array($resp_cyc);
					$monto_gastos = $subtotal_gastos;
					$subtotal_gastos_con_impuesto = $subtotal_gastos;
					if (!UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
						$honorarios = $monto_subtotal;
					}
					/* Fin de lo que hay que mejorar */
				}

				$mostrar_honorarios = true;
				$array_docs_ocultar = explode(';;', UtilesApp::GetConf($this->sesion, 'EsconderValoresFacturaEnCero'));
				if (in_array($tipo_dl, $array_docs_ocultar)) {
					if (UtilesApp::GetConf($this->sesion, 'CalculacionCyC')) {
						$mostrar_honorarios = ( number_format($honorarios, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) > 0 ? true : false );
					} else {
						$mostrar_honorarios = ( number_format($monto_subtotal, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) > 0 ? true : false );
					}
				}
				/* $subtotal_gastos_con_impuesto
				  $subtotal_gastos_sin_impuesto */

				$mostrar_gastos_con_impuesto = true;
				if (in_array($tipo_dl, $array_docs_ocultar)) {
					if (UtilesApp::GetConf($this->sesion, 'CalculacionCyC')) {
						$mostrar_gastos_con_impuesto = ( number_format($subtotal_gastos_con_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) > 0 ? true : false );
					} else {
						$mostrar_gastos_con_impuesto = ( number_format($subtotal_gastos_con_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) > 0 ? true : false );
					}
				}

				$mostrar_gastos_sin_impuesto = true;
				if (in_array($tipo_dl, $array_docs_ocultar)) {
					if (UtilesApp::GetConf($this->sesion, 'CalculacionCyC')) {
						$mostrar_gastos_sin_impuesto = ( number_format($subtotal_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) > 0 ? true : false );
					} else {
						$mostrar_gastos_sin_impuesto = ( number_format($subtotal_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) > 0 ? true : false );
					}
				}

				if ($descuento_honorarios > 0) {
					$html2 = str_replace('%tr_descuento%', '<tr>
												<td align="left" class="descripcion" colspan="3">%descuento_glosa%</td>
												<td class="monto_normal" align="right">%descuento_honorarios%</td></tr>', $html2);
				} else {
					$html2 = str_replace('%tr_descuento%', '', $html2);
				}

				$html2 = str_replace('%porcentaje_impuesto_sin_simbolo%', (int) ($porcentaje_impuesto), $html2);

				if (UtilesApp::GetConf($this->sesion, "CantidadLineasDescripcionFacturas") > 1) {

					// Lo separo en lineas
					//$factura_descripcion_separado = explode("\n", __($factura_descripcion));
					//$factura_descripcion_separado = implode("<br />\n", $factura_descripcion_separado);
					//$honorarios_descripcion = $factura_descripcion_separado;
					$honorarios_descripcion = nl2br(__($factura_descripcion));

					if ($mostrar_gastos_con_impuesto) {
						$factura_descripcion_separado .="<br/><br/>" . __($descripcion_subtotal_gastos);
					}
					if ($mostrar_gastos_sin_impuesto) {
						$factura_descripcion_separado .="<br/><br/>" . __($descripcion_subtotal_gastos_sin_impuesto);
					}
				} else {
					$factura_descripcion_separado = __($factura_descripcion);
					$honorarios_descripcion = $factura_descripcion_separado;

					if ($subtotal_gastos_con_impuesto) {
						$factura_descripcion_separado .="<br/><br/>" . __($descripcion_subtotal_gastos);
					}
					if ($mostrar_gastos_sin_impuesto) {
						$factura_descripcion_separado .="<br/><br/>" . __($descripcion_subtotal_gastos_sin_impuesto);
					}
				}
				// FFF soporta 3 nuevas glosas para separar HH, Gasto c y sin
				// honorarios_periodo no incluye gastos
				// servicios_periodo es la concatenacion de hh, gasto con y gasto sin
				// como se imprimen en lineas separadas, llevan 1 salto de linea en vez de 2
				$descripcion_subtotal_gastos = "<br/>" . __($descripcion_subtotal_gastos);
				$descripcion_subtotal_gastos_sin_impuesto = "<br/>" . __($descripcion_subtotal_gastos_sin_impuesto);
				if (UtilesApp::GetConf($this->sesion, 'UsarGlosaFacturaMayusculas')) {
					$factura_descripcion_separado = strtoupper($factura_descripcion_separado);
					$descripcion_subtotal_gastos = strtoupper($descripcion_subtotal_gastos);
					$descripcion_subtotal_gastos_sin_impuesto = strtoupper($descripcion_subtotal_gastos_sin_impuesto);
				}
				if ($mostrar_honorarios) {
					$html2 = str_replace('%honorarios_periodo%', $honorarios_descripcion, $html2);
				} else {
					$html2 = str_replace('%honorarios_periodo%', '', $html2);
				}
				if ($subtotal_gastos_con_impuesto) {
					$html2 = str_replace('%gastos_con_impuesto_periodo%', $descripcion_subtotal_gastos, $html2);
				} else {
					$html2 = str_replace('%gastos_con_impuesto_periodo%', '', $html2);
				}
				if ($mostrar_gastos_sin_impuesto) {
					$html2 = str_replace('%gastos_sin_impuesto_periodo%', $descripcion_subtotal_gastos_sin_impuesto, $html2);
				} else {
					$html2 = str_replace('%gastos_sin_impuesto_periodo%', '', $html2);
				}
				if ($lang == 'es') {
					if ($descuento_honorarios > 0) {
						$html2 = str_replace('%<br><br>%', '<br><br>', $html2);
					} else {
						$html2 = str_replace('%<br><br>%', '<br><br><br><br>', $html2);
					}
					if ($mostrar_honorarios) {
						if (UtilesApp::GetConf($this->sesion, 'UsarGlosaFacturaMayusculas')) {
							$html2 = str_replace('%servicios_periodo%', strtoupper($factura_descripcion_separado), $html2);
							$html2 = str_replace('%servicios_periodo%', strtoupper('Honorarios por servicios profesionales prestados %fecha_ini% %fecha_fin%'), $html2);
						} else {
							$html2 = str_replace('%servicios_periodo%', $factura_descripcion_separado, $html2);
							$html2 = str_replace('%servicios_periodo%', 'Honorarios por servicios profesionales prestados %fecha_ini% %fecha_fin%', $html2);
						}
					} else {
						$html2 = str_replace('%servicios_periodo%', '', $html2);
						$html2 = str_replace('%servicios_periodo%', '', $html2);
					}
					$meses_org = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
					$mes_corto = array('jan', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
					if ($fecha_ini && $fecha_ini != '0000-00-00') {
						$html2 = str_replace('%fecha_ini%', 'desde ' . str_replace($meses_org, $mes_corto, date('j-M-y', strtotime($fecha_ini))), $html2);
					} else {
						$html2 = str_replace('%fecha_ini%', '', $html2);
					}
					if ($fecha_fin && $fecha_fin != '0000-00-00') {
						$html2 = str_replace('%fecha_fin%', 'hasta ' . str_replace($meses_org, $mes_corto, date('j-M-y', strtotime($fecha_fin))), $html2);
					} else {
						$html2 = str_replace('%fecha_fin%', '', $html2);
					}
					if ($mostrar_honorarios) {
						$html2 = str_replace('%texto_honorarios%', 'HONORARIOS', $html2);
						$html2 = str_replace('%glosa_honorarios%', $factura_descripcion_separado, $html2);
					} else {
						$html2 = str_replace('%texto_honorarios%', '', $html2);
						$html2 = str_replace('%glosa_honorarios%', '', $html2);
					}

					$html2 = str_replace('%texto_gastos%', 'GASTOS', $html2);
					$html2 = str_replace('%texto_descripcion_gastos%', 'Gastos incurridos en su caso, según relación adjunta.', $html2);
					$html2 = str_replace('%total_honorarios_y_gastos%', 'Total servicios profesionales y gastos incurridos', $html2);
					$html2 = str_replace('%pje_impuesto%', $porcentaje_impuesto . '%', $html2);
					if (UtilesApp::GetConf($this->sesion, 'UsarImpuestoSeparado')) {
						if (method_exists('Conf', 'GetConf')) {
							$html2 = str_replace('%texto_impuesto%', __('IVA') . ' (' . Conf::GetConf($this->sesion, 'ValorImpuesto') . '%)', $html2);
						} else if (method_exists('Conf', 'ValorImpuesto')) {
							$html2 = str_replace('%texto_impuesto%', __('IVA') . ' (' . $porcentaje_impuesto . '%)', $html2);
						}
					} else {
						$html2 = str_replace('%texto_impuesto%', '', $html2);
					}
					$html2 = str_replace('%descripcion%', '', $html2);
					$html2 = str_replace('%texto_total%', 'Total ', $html2);
					$html2 = str_replace('%firma%', 'Firma', $html2);
					if ($descuento_honorarios > 0) {
						$html2 = str_replace('%descuento_honorarios%', '- ' . number_format($descuento_honorarios, $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%descuento_glosa%', __('Descuento'), $html2);
					} else {
						$html2 = str_replace('%descuento_honorarios%', '', $html2);
						$html2 = str_replace('%descuento_glosa%', '', $html2);
					}
				} else if ($lang == 'en') {
					$html2 = str_replace('%firma%', 'Signature', $html2);
					if ($descuento_honorarios > 0) {
						$html2 = str_replace('%<br><br>%', '', $html2);
					} else {
						$html2 = str_replace('%<br><br>%', '<br><br>', $html2);
					}
					$html2 = str_replace('%servicios_periodo%', $factura_descripcion_separado, $html2);
					$html2 = str_replace('%servicios_periodo%', 'For legal services rendered %fecha_ini% %fecha_fin%', $html2);
					$meses_org = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
					$month_short = array('jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec');
					if ($fecha_ini && $fecha_ini != '0000-00-00') {
						$html2 = str_replace('%fecha_ini%', 'from ' . str_replace($meses_org, $month_short, date('M-d-y', strtotime($fecha_ini))), $html2);
					} else {
						$html2 = str_replace('%fecha_ini%', '', $html2);
					}
					if ($fecha_fin && $fecha_fin != '0000-00-00') {
						$html2 = str_replace('%fecha_fin%', 'until ' . str_replace($meses_org, $month_short, date('M-d-y', strtotime($fecha_fin))), $html2);
					} else {
						$html2 = str_replace('%fecha_fin%', '', $html2);
					}

					if ($mostrar_honorarios) {
						$html2 = str_replace('%texto_honorarios%', 'LEGAL SERVICES', $html2);
						$html2 = str_replace('%glosa_honorarios%', $factura_descripcion_separado, $html2);
					} else {
						$html2 = str_replace('%texto_honorarios%', '', $html2);
						$html2 = str_replace('%glosa_honorarios%', '', $html2);
					}


					$html2 = str_replace('%texto_gastos%', 'EXPENSES', $html2);
					$html2 = str_replace('%texto_descripcion_gastos%', 'Expenses incurred in this case.', $html2);
					$html2 = str_replace('%total_honorarios_y_gastos%', 'Total legal services and expenses', $html2);
					if (UtilesApp::GetConf($this->sesion, 'UsarImpuestoSeparado')) {
						$html2 = str_replace('%texto_impuesto%', __('IVA') . ' (' . UtilesApp::GetConf($this->sesion, 'ValorImpuesto') . '%)', $html2);
					} else {
						$html2 = str_replace('%texto_impuesto%', '', $html2);
					}
					$html2 = str_replace('%descripcion%', '', $html2);
					if ($lang == 'en') {
						$meses = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
						$months = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
						$html2 = str_replace('Honorarios por asesorías prestadas', 'Legal services rendered', $html2);
						$html2 = str_replace('durante el mes de', 'during the month of', $html2);
						$html2 = str_replace($meses, $months, $html2);
					}
					$html2 = str_replace('%texto_total%', 'Total ', $html2);
					$html2 = str_replace('%firma%', 'Signature', $html2);
					if ($descuento_honorarios > 0) {
						$html2 = str_replace('%descuento_honorarios%', '- ' . number_format($descuento_honorarios, $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%descuento_glosa%', __('Discount'), $html2);
					} else {
						$html2 = str_replace('%descuento_honorarios%', '', $html2);
						$html2 = str_replace('%descuento_glosa%', '', $html2);
					}
				}
				if (UtilesApp::GetConf($this->sesion, "DescripcionFacturaConAsuntos")) {
					// Lo separo en lineas
					$factura_descripcion_con_asuntos = explode("\n", __($factura_descripcion));

					$max_caracter_por_linea = UtilesApp::GetConf($this->sesion, 'MaximoCaracterPorLineaDescripcion');

					if ($max_caracter_por_linea > 0) {
						$lineas_factura_descripcion = array();

						// Formo n lineas por los pedazos de linea obtenidos
						foreach ($factura_descripcion_con_asuntos as $linea) {
							if (strlen($linea) > $max_caracter_por_linea) {
								$lineas_factura_descripcion = array_merge(
									$lineas_factura_descripcion, str_split($linea, $max_caracter_por_linea)
								);
							} else {
								$lineas_factura_descripcion[] = $linea;
							}
						}

						$factura_descripcion_con_asuntos = $lineas_factura_descripcion;
					}

					$max_lineas = UtilesApp::GetConf($this->sesion, 'MaximoLineasDescripcion');

					if ($max_lineas > 0 && count($factura_descripcion_con_asuntos) > $max_lineas) {
						$factura_descripcion_con_asuntos = array_slice($factura_descripcion_con_asuntos, 0, $max_lineas);
					}

					$factura_descripcion_con_asuntos = implode("<br />\n", $factura_descripcion_con_asuntos);

					$html2 = str_replace('%factura_descripcion%', $factura_descripcion_con_asuntos, $html2);
					$html2 = str_replace('%asuntos%', "", $html2);
					$html2 = str_replace('%cod_asuntos%', "", $html2);
				} else {
					$html2 = str_replace('%asuntos%', $asuntos, $html2);
					$html2 = str_replace('%cod_asuntos%', $cod_asuntos, $html2);
				}
				if (method_exists('Conf', 'Server') && method_exists('Conf', 'ImgDir')) {
					$html2 = str_replace('%logo_cobro%', Conf::Server() . Conf::ImgDir(), $html2);
				}
				$html2 = str_replace('%glosa_moneda_factura%', '%' . $cobro_moneda->moneda[$factura_id_moneda]['glosa_moneda'] . '%', $html2);
				$html2 = str_replace('%Peso%', 'PESOS', $html2);
				if ($lang == 'es') {
					$html2 = str_replace('%Dólar%', 'DÓLARES', $html2);
				} else {
					$html2 = str_replace('%Dólar%', 'DOLLARS', $html2);
				}

				$html2 = str_replace('%Euro%', 'EUROS', $html2);
				if (method_exists('Conf', 'GetConf')) {
					$html2 = str_replace('%porcentaje%', Conf::GetConf($this->sesion, 'ValorImpuesto') . '%', $html2);
				} else if (method_exists('Conf', 'ValorImpuesto')) {
					$html2 = str_replace('%porcentaje%', Conf::ValorImpuesto() . '%', $html2);
				}

				$monto_gastos_sin_impuesto = $monto_gastos / ( 1 + ( $porcentaje_impuesto / 100 ) );
				$impuesto_gastos = $monto_gastos - $monto_gastos_sin_impuesto;



				if (UtilesApp::GetConf($this->sesion, 'CalculacionCyC')) {

					if ($mostrar_honorarios) {
						$html2 = str_replace('%monto_honorarios%', number_format($monto_subtotal, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%monto_honorarios%', '', $html2);
					}
					if ($mostrar_gastos_con_impuesto) {
						$html2 = str_replace('%monto_gastos%', number_format($subtotal_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%monto_gastos%', '', $html2);
					}
					$total_factura_netto = round($monto_subtotal_sin_descuento + $subtotal_gastos, $moneda_factura->fields['cifras_decimales']);
					$html2 = str_replace('%monto_total%', number_format($total_factura_netto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$impuesto_factura = round(round($monto_subtotal_sin_descuento + $subtotal_gastos, $moneda_factura->fields['cifras_decimales']) * ($porcentaje_impuesto / 100), $moneda_factura->fields['cifras_decimales']);
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') ) || ( method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ))) {
						$html2 = str_replace('%monto_impuestos%', number_format($impuesto_factura, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%monto_impuestos%', '', $html2);
					}
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') ) || ( method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ))) {
						$html2 = str_replace('%monto_total_brutto%', number_format($total_factura_netto + $impuesto_factura, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%monto_total_brutto%', '', $html2);
					}
				} else {
					if ($mostrar_honorarios) {
						$html2 = str_replace('%monto_honorarios%', number_format($monto_subtotal_sin_descuento, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%monto_honorarios%', '', $html2);
					}
					if ($mostrar_gastos_con_impuesto) {
						$html2 = str_replace('%monto_gastos%', number_format($monto_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%monto_gastos%', '', $html2);
					}

					$html2 = str_replace('%monto_total%', number_format($monto_subtotal + $monto_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') ) || ( method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ))) {
						$html2 = str_replace('%monto_impuestos%', number_format($impuesto + $impuesto_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%monto_impuestos%', '', $html2);
					}
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') ) || ( method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ))) {
						$html2 = str_replace('%monto_total_brutto%', number_format($total + $monto_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%monto_total_brutto%', '', $html2);
					}
				}

				/*
				 * Montos Especiales de Vouga y Olmedo (o Paraguay en general )
				 *
				 * anchors:
				 *
				 * %simbolo_honorarios_sin_impuesto% simbolo de moneda de honorarios sin impuesto
				 * %monto_honorarios_sin_impuesto% valor de honorarios cuando por contrato no llevan impuesto
				 * %simbolo_honorarios_con_impuesto% simbolo de moneda de honorarios
				 * %monto_honorarios_con_impuesto% valor de honorarios cuando si incluyen impuesto es excluyente con %monto_honorarios_sin_impuesto%
				 *
				 * %simbolo_subtotal_gastos_con_impuesto%
				 * %subtotal_gastos_con_impuesto%
				 * descripcion_subtotal_gastos_sin_impuesto%
				 * %simbolo_subtotal_gastos_sin_impuesto%
				 * %subtotal_gastos_sin_impuesto%
				 * %total_exentos% sumatoria de los valores que no llevan impuesto
				 * %total_diez% sumatoria de los valores que en paraguay llevan 10% de impuesto
				 * %total% sumatoria de exentos más 10
				 * %iva_diez% total de iva de la sumatoria de montos con iva
				 * %total_iva% iva de lo que lleva iva
				 */

				$subtotal_exentos = 0;
				$subtotal_diez = 0;
				$subtotal_completo = 0;
				$honorarios_con_impuesto = 0;
				$honorarios_sin_impuesto = 0;
				$gastos_con_impuesto = 0;
				$gastos_sin_impuesto = 0;
				$monto_impuesto_honorarios = 0;
				$monto_impuesto_gastos = 0;
				$glosa_banco = "";

				if (isset($cobro) && $cobro->loaded()) {

					if ($cobro->fields['porcentaje_impuesto'] > 0) {
						$honorarios_con_impuesto = $this->fields['honorarios'] * ( 1 + ( $cobro->fields['porcentaje_impuesto'] / 100) );
						$monto_impuesto_honorarios = $this->fields['honorarios'] * ( $cobro->fields['porcentaje_impuesto'] / 100);
					} else {
						$honorarios_sin_impuesto = $this->fields['honorarios'];
						$gastos_sin_impuestos = $this->fields['subtotal_gastos_sin_impuesto'];
					}

					$gastos_con_impuesto = $this->fields['subtotal_gastos'] * ( 1 + ( $cobro->fields['porcentaje_impuesto_gastos'] / 100) );
					$monto_impuesto_gastos = $this->fields['subtotal_gastos'] * ( $cobro->fields['porcentaje_impuesto_gastos'] / 100);

					$gastos_sin_impuesto = $this->fields['subtotal_gastos_sin_impuesto'];

					$subtotal_diez = number_format($honorarios_con_impuesto + $gastos_con_impuesto, $moneda_factura->fields['cifras_decimales'], '.', '');
					$subtotal_exentos = number_format($honorarios_sin_impuesto + $gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], '.', '');

					$subtotal_completo = $honorarios_con_impuesto + $gastos_con_impuesto + $honorarios_sin_impuesto + $gastos_sin_impuesto;
					$query_glosa_banco = " SELECT cb.glosa
												FROM cuenta_banco cb
													JOIN contrato c ON ( cb.id_cuenta = c.id_cuenta )
													JOIN cobro cob ON ( c.id_contrato = cob.id_contrato )
												WHERE cob.id_cobro = '{$cobro->fields[id_cobro]}' LIMIT 1";
					//echo $query_glosa_banco; exit;
					$resu_glosa = mysql_query($query_glosa_banco, $this->sesion->dbh) or Utiles::errorSQL($query_glosa_banco, __FILE__, __LINE__, $this->sesion->dbh);
					list($glosa_banco) = mysql_fetch_array($resu_glosa);
				}

				if ($mostrar_honorarios) {
					$html2 = str_replace('%simbolo_honorarios_sin_impuesto%', '%simbolo_honorarios%', $html2);
					$html2 = str_replace('%simbolo_honorarios_con_impuesto%', '%simbolo_honorarios%', $html2);


					/* debe mostrar ceros en los espacios que sea 0 */
					$html2 = str_replace('%monto_honorarios_sin_impuesto%', number_format($honorarios_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_honorarios_con_impuesto%', number_format($honorarios_con_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				} else {

					/*
					 * se reemplazan por '', por que si se pone &nbsp; se corre todo para abajo y dejaria un espacio,
					 *  al no tener nada html esconde la fila
					 */
					$html2 = str_replace('%simbolo_honorarios_sin_impuesto%', '', $html2);
					$html2 = str_replace('%simbolo_honorarios_con_impuesto%', '', $html2);

					$html2 = str_replace('%monto_honorarios_sin_impuesto%', '', $html2);
					$html2 = str_replace('%monto_honorarios_con_impuesto%', '', $html2);
				}

				if ($gastos_con_impuesto > 0) {

					$html2 = str_replace('%subtotal_gasto_con_impuesto%', number_format($gastos_con_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				} else {

					$html2 = str_replace('%simbolo_subtotal_gastos_con_impuesto%', '', $html2);
					$html2 = str_replace('%subtotal_gasto_con_impuesto%', '', $html2);
				}

				if ($gastos_sin_impuesto > 0) {

					$html2 = str_replace('%subtotal_gasto_sin_impuesto%', number_format($gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				} else {

					$html2 = str_replace('%simbolo_subtotal_gastos_sin_impuesto%', '', $html2);
					$html2 = str_replace('%subtotal_gasto_sin_impuesto%', '', $html2);
				}

				$html2 = str_replace('%glosa_banco%', $glosa_banco, $html2);
				$html2 = str_replace('%total_exentos%', number_format($subtotal_exentos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%total_diez%', number_format($subtotal_diez, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%total_paraguay%', number_format($subtotal_completo, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%ceros%', number_format(0, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

				$html2 = str_replace('%iva_diez%', number_format($monto_impuesto_honorarios + $monto_impuesto_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

				$html2 = str_replace('%total_iva%', number_format($monto_impuesto_honorarios + $monto_impuesto_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);


				/*
				  Montos Rebaza-alcazar
				 */

				if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {


					if ($mostrar_honorarios) {
						$html2 = str_replace('%honorarios%', number_format($monto_subtotal, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%honorarios%', '', $html2);
					}

					if (UtilesApp::GetConf($this->sesion, "CantidadLineasDescripcionFacturas") > 1) {
						$descripcion_subtotal_gastos_separado = nl2br( __($descripcion_subtotal_gastos));
						$descripcion_subtotal_gastos_sin_impuesto_separado = nl2br( __($descripcion_subtotal_gastos_sin_impuesto));
					} else {
						$descripcion_subtotal_gastos_separado = __($descripcion_subtotal_gastos);
						$descripcion_subtotal_gastos_sin_impuesto_separado = __($descripcion_subtotal_gastos_sin_impuesto);
					}

					if ($mostrar_gastos_con_impuesto) {
						$html2 = str_replace('%simbolo_subtotal_gastos_con_impuesto%', $simbolo, $html2);
						if (UtilesApp::GetConf($this->sesion, 'UsarGlosaFacturaMayusculas')) {
							$html2 = str_replace('%descripcion_subtotal_gastos_con_impuesto%', strtoupper($descripcion_subtotal_gastos_separado), $html2);
						} else {
							$html2 = str_replace('%descripcion_subtotal_gastos_con_impuesto%', $descripcion_subtotal_gastos_separado, $html2);
						}
						$html2 = str_replace('%subtotal_gastos_con_impuesto%', number_format($subtotal_gastos_con_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%simbolo_subtotal_gastos_con_impuesto%', '&nbsp;', $html2);
						$html2 = str_replace('%descripcion_subtotal_gastos_con_impuesto%', '&nbsp;', $html2);
						$html2 = str_replace('%subtotal_gastos_con_impuesto%', '&nbsp;', $html2);
					}

					if (UtilesApp::GetConf($this->sesion, 'UsarGastosConSinImpuesto')) {
						if ($mostrar_gastos_sin_impuesto) {
							$html2 = str_replace('%simbolo_subtotal_gastos_sin_impuesto%', $simbolo, $html2);
							if (UtilesApp::GetConf($this->sesion, 'UsarGlosaFacturaMayusculas')) {
								$html2 = str_replace('%descripcion_subtotal_gastos_sin_impuesto%', strtoupper($descripcion_subtotal_gastos_sin_impuesto_separado), $html2);
							} else {
								$html2 = str_replace('%descripcion_subtotal_gastos_sin_impuesto%', $descripcion_subtotal_gastos_sin_impuesto_separado, $html2);
							}

							$html2 = str_replace('%subtotal_gastos_sin_impuesto%', number_format($subtotal_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
						} else {
							$html2 = str_replace('%simbolo_subtotal_gastos_sin_impuesto%', '', $html2);
							$html2 = str_replace('%descripcion_subtotal_gastos_sin_impuesto%', '', $html2);
							$html2 = str_replace('%subtotal_gastos_sin_impuesto%', '', $html2);
						}
					} else {
						$html2 = str_replace('%simbolo_subtotal_gastos_sin_impuesto%', '', $html2);
						$html2 = str_replace('%descripcion_subtotal_gastos_sin_impuesto%', '', $html2);
						$html2 = str_replace('%subtotal_gastos_sin_impuesto%', '', $html2);
					}

					$suma_monto_sin_iva = number_format($honorarios + $subtotal_gastos_con_impuesto + $subtotal_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$suma_monto_con_iva = number_format($total, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$impuesto = number_format($impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

					$html2 = str_replace('%suma_montos_sin_iva%', $suma_monto_sin_iva, $html2);
					$html2 = str_replace('%suma_montos_solo_iva%', $impuesto, $html2);
					$html2 = str_replace('%suma_monto_con_iva%', $suma_monto_con_iva, $html2);

					$monto_subtotal_honorario_y_gastos = $monto_subtotal + $subtotal_gastos + $subtotal_gastos_sin_impuesto;
					$html2 = str_replace('%monto_subtotal_honorario_y_gastos%', number_format($monto_subtotal_honorario_y_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				} else {

					if ($mostrar_honorarios) {
						$html2 = str_replace('%honorarios%', number_format($monto_subtotal, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
					} else {
						$html2 = str_replace('%honorarios%', '', $html2);
					}

					$html2 = str_replace('%simbolo_subtotal_gastos_con_impuesto%', '', $html2);
					$html2 = str_replace('%descripcion_subtotal_gastos_con_impuesto%', '', $html2);
					$html2 = str_replace('%subtotal_gastos_con_impuesto%', '', $html2);

					$html2 = str_replace('%simbolo_subtotal_gastos_sin_impuesto%', '', $html2);
					$html2 = str_replace('%descripcion_subtotal_gastos_sin_impuesto%', '', $html2);
					$html2 = str_replace('%subtotal_gastos_sin_impuesto%', '', $html2);

					$html2 = str_replace('%suma_montos_sin_iva%', '', $html2);
					$html2 = str_replace('%suma_montos_solo_iva%', '', $html2);
					$html2 = str_replace('%suma_monto_con_iva%', '', $html2);
					$html2 = str_replace('%monto_subtotal_honorario_y_gastos%', '', $html2);
				}

				$monto_palabra = new MontoEnPalabra($this->sesion);

				$glosa_moneda_lang = __($glosa_moneda);
				$glosa_moneda_plural_lang = __($glosa_moneda_plural);

				$monto_total_palabra = strtoupper($monto_palabra->ValorEnLetras($total, $cobro_id_moneda, $glosa_moneda_lang, $glosa_moneda_plural_lang));
				if ($mostrar_honorarios) {
					$html2 = str_replace('%simbolo_honorarios%', $simbolo, $html2);
				} else {
					$html2 = str_replace('%simbolo_honorarios%', '', $html2);
				}
				$html2 = str_replace('%simbolo%', $simbolo, $html2);

				$html2 = str_replace('%subtotal%', number_format($monto_subtotal, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_impuesto_sin_gastos%', number_format($impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_total_bruto_sin_gastos%', number_format($total, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_total_palabra%', $monto_total_palabra, $html2);


				break;
			/* case ( $theTag == 'BOTTOM' || $theTag == 'BOTTOM_COPIA' ):  <<< esto creo que no se puede */
			case 'BOTTOM': //hará lo mismo que BOTTOM_COPIA por lo que no tiene que tener instrucciones ni break;
			case 'BOTTOM_COPIA':
				if (method_exists('Conf', 'GetConf')) {
					$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
					$numeracion = Conf::GetConf($this->sesion, 'Numeracion');
					$numeracion_fecha = Conf::GetConf($this->sesion, 'NumeracionFecha');
					$numeracion_desde = Conf::GetConf($this->sesion, 'NumeracionDesde');
					$numeracion_hasta = Conf::GetConf($this->sesion, 'NumeracionHasta');
					$titulo_empresa = Conf::GetConf($this->sesion, 'NombreEmpresa');
					$subtitulo_empresa = Conf::GetConf($this->sesion, 'SubtituloEmpresa');
				} else {
					if (method_exists('Conf', 'PdfLinea1'))
						$PdfLinea1 = Conf::PdfLinea1();
					$numeracion = '';
					$numeracion_fecha = '';
					$numeracion_desde = '';
					$numeracion_hasta = '';
					$titulo_empresa = '';
					$subtitulo_empresa = '';
				}

				$html2 = str_replace('%linea1%', $PdfLinea1, $html2);
				$html2 = str_replace('%numeracion%', $numeracion, $html2);
				$meses_org = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
				$meses_largo = array('ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE');
				$html2 = str_replace('%fecha_numeracion%', str_replace($meses_org, $meses_largo, date('M j Y', strtotime($numeracion_fecha))), $html2);
				$html2 = str_replace('%numeracion_desde%', $numeracion_desde, $html2);
				$html2 = str_replace('%numeracion_hasta%', $numeracion_hasta, $html2);
				$html2 = str_replace('%titulo%', $titulo_empresa, $html2);
				$html2 = str_replace('%subtitulo%', $subtitulo_empresa, $html2);
				break;
		}

		return $html2;
	}

	function ObtenerNumero($id_factura = null, $serie = null, $numero = null, $mostrar_comprobante = false) {
		if ($this->Loaded()) {
			if (UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie')) {

				$serie = empty($this->fields['serie_documento_legal']) ? '001' : $this->fields['serie_documento_legal'];
				$n = str_pad($serie, 3, '0', STR_PAD_LEFT) . "-" . $this->fields['numero'];
			} else {

				$n = $this->fields['numero'];
			}

			if ($mostrar_comprobante && $this->fields['comprobante_erp']) {
				$n = '<span title="' . __('Comprobante') . ': ' . $this->fields['comprobante_erp'] . '"><b>' . $n . '</b></span>';
			}

			return $n;
		} else if (!empty($id_factura)) {
			$query = "SELECT serie_documento_legal, numero FROM factura WHERE id_factura = " . $id_factura;
			$serie_numero = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($serie, $numero) = mysql_fetch_array($serie_numero);
			if (UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie')) {
				$serie = empty($serie) ? '001' : $serie;
				return str_pad($serie, 3, '0', STR_PAD_LEFT) . "-" . $numero;
			}
			return $numero;
		} else if (!empty($numero)) {
			if (UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie')) {
				$serie = empty($serie) ? '001' : $serie;
				return str_pad($serie, 3, '0', STR_PAD_LEFT) . '-' . $numero;
			}
			return $numero;
		}
		return false;
	}

	function ObtieneNumeroFactura() {
		if (method_exists('Conf', 'GetConf')) {
			$min_numero_factura = Conf::GetConf($this->sesion, 'NumeracionDesde');
			$max_numero_factura = Conf::GetConf($this->sesion, 'NumeracionHasta');
		} else if (method_exists('Conf', 'NumeracionDesde')) {
			$min_numero_factura = Conf::NumeracionDesde();
			$max_numero_factura = Conf::NumeracionHasta();
		}

		$where_max = " 1 ";
		if ($max_numero_factura > 0) {
			$where_max .= " AND numero <= " . $max_numero_factura;
		}

		$query = "SELECT MAX(numero) FROM factura WHERE $where_max";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($max_numero_documento) = mysql_fetch_array($resp);

		if ($max_numero_documento < $min_numero_factura) {
			return $min_numero_factura;
		} else {
			return $max_numero_documento + 1;
		}
	}

	function ObtieneNumeroDocumentoLegal($tipo_documento_legal) {
		return $this->ObtenerNumeroDocLegal($tipo_documento_legal);

		/*EL RESTO ESTA DEPRECATED*/
		/*$min_numero_factura = UtilesApp::GetConf($this->sesion, 'NumeracionDesde');
		$max_numero_factura = UtilesApp::GetConf($this->sesion, 'NumeracionHasta');

		$where_max = " 1 ";
		if ($max_numero_factura > 0) {
			$where_max .= " AND numero <= " . $max_numero_factura;
		}
		if ($tipo_documento_legal > 0) {
			$where_max .= " AND f.id_documento_legal = " . $tipo_documento_legal;
		}

		$query = "SELECT IF(MAX(f.numero)>pdl.numero_inicial,MAX(f.numero),pdl.numero_inicial) as numero_actual
					FROM factura f
					LEFT JOIN prm_documento_legal pdl on pdl.id_documento_legal = f.id_documento_legal
					WHERE $where_max";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($max_numero_documento) = mysql_fetch_array($resp);

		if ($max_numero_documento < $min_numero_factura) {
			return $min_numero_factura;
		} else {
			return $max_numero_documento + 1;
		}*/
	}

	function ObtenerNumeroDocLegal($tipo_documento_legal, $serie = null) {

		if (empty($tipo_documento_legal)) {
			return false;
		}

		if (UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie') and !empty($serie)) {
			$query = "SELECT numero_inicial FROM prm_doc_legal_numero WHERE id_documento_legal = " . $tipo_documento_legal . " AND serie = '" . $serie . "'";
		} else {
			$query = "SELECT numero_inicial FROM prm_documento_legal WHERE id_documento_legal = " . $tipo_documento_legal;
		}

		$numero_resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($numero) = mysql_fetch_array($numero_resp);
		return $numero;
	}

	function ExisteNumeroDocLegal($tipo_documento_legal, $numero, $serie) {
		if (empty($tipo_documento_legal) or empty($numero)) {
			return false;
		}

		if (UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie') and !empty($serie)) {
			$query = "SELECT COUNT(*) FROM factura WHERE numero = '" . $numero . "' AND id_documento_legal = '" . $tipo_documento_legal . "' AND serie_documento_legal = '" . (int) $serie . "'";
		} else {
			$query = "SELECT COUNT(*) FROM factura WHERE numero = '" . $numero . "' AND id_documento_legal = '" . $tipo_documento_legal . "' AND serie_documento_legal = '" . Conf::GetConf($this->sesion, 'SerieDocumentosLegales') . "'";
		}
		$cantidad_resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cantidad) = mysql_fetch_array($cantidad_resp);
		return $cantidad > 0;
	}

	function ValidarDocLegal() {
		if (empty($this->fields['id_factura'])) {
			if ($this->ExisteNumeroDocLegal($this->fields['id_documento_legal'], $this->fields['numero'], $this->fields['serie_documento_legal'])) {
				return false;
			}
		}
		return true;
	}

	function GetUltimoPagoSoyFactura($id = null) {
		if (!$id) {
			$id = $this->Id();
			$where = " WHERE ccfm2.id_factura =  '" . $id . "'";
		} else {
			$where = " WHERE ccfm2.id_factura IN (" . $id . ") ";
		}

		$query = "SELECT fp.id_factura_pago
								FROM factura_pago AS fp
								JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
								JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
								LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
								" . $where . "
								ORDER BY fp.fecha,fp.id_factura_pago DESC	";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($ultimo_id_factura_pago) = mysql_fetch_array($resp);
		return $ultimo_id_factura_pago;
	}

	function GetPagosSoyFactura($id = null, $doc_cobro = null) {
		if (!$id) {
			$id = $this->Id();
		}
		$query = "SELECT fp.*,
					SUM(IF( ccfm2.id_factura = '$id', ccfmn.monto, 0)) AS monto_aporte
				FROM factura_pago AS fp
				JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
				LEFT JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
				LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
				LEFT JOIN neteo_documento AS nd ON fp.id_neteo_documento_adelanto = nd.id_neteo_documento
				WHERE ccfm2.id_factura = '$id' OR nd.id_documento_cobro = '$doc_cobro'
				GROUP BY fp.id_factura_pago";
		return new ListaFacturaPago($this->sesion, null, $query);
	}

	function MaxNumeroDocLegal($tipo_documento_legal, $serie = null) {
		if (empty($tipo_documento_legal)) {
			return false;
		}
		$serie = UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie') && !empty($serie) ? (int)$serie : Conf::GetConf($this->sesion, 'SerieDocumentosLegales');
		$query = "SELECT MAX(numero+0) as numero_actual FROM factura
			WHERE id_documento_legal = '$tipo_documento_legal' AND (numero+0) < {$this->max_numero} AND serie_documento_legal = '$serie'";
		$numero_resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($numero_max) = mysql_fetch_array($numero_resp);
		return $numero_max;
	}

	function MaxNumeroAsientoContable() {

		$query = "SELECT MAX(asiento_contable) as numero_actual FROM factura WHERE mes_contable = '" . date('Ym') . "';";

		$numero_resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($numero_max) = mysql_fetch_array($numero_resp);
		return $numero_max;
	}

	function GuardarNumeroDocLegal($tipo_documento_legal, $numero, $serie = null) {
		//si el "numero" no es un int32 lo ignoro para efectos del numero siguiente
		if (empty($tipo_documento_legal) || empty($numero) || !is_numeric($numero) || $numero >= $this->max_numero) {
			return false;
		}

		$numero += 1;
		if ($this->ExisteNumeroDocLegal($tipo_documento_legal, $numero, $serie) || UtilesApp::GetConf($this->sesion, 'InformarContabilidad')) {
			$numero = $this->MaxNumeroDocLegal($tipo_documento_legal, $serie) + 1;
		}

		if (UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie') and !empty($serie)) {
			$query = "UPDATE prm_doc_legal_numero SET numero_inicial = " . $numero . " WHERE id_documento_legal = '" . $tipo_documento_legal . "' AND serie = '" . $serie . "'";
			$respuesta = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		} else {
			$query = "UPDATE prm_documento_legal SET numero_inicial = $numero WHERE id_documento_legal = '" . $tipo_documento_legal . "'";
			$respuesta = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}
		return true;
	}

	function CambiarEstado($codigo_estado, $id_factura = null) {
		if (!$id_factura) {
			$id_factura = $this->fields[$this->campo_id];
		}

		$query = "UPDATE factura SET id_estado = (SELECT id_estado FROM prm_estado_factura WHERE codigo = '$codigo_estado') WHERE id_factura = '$id_factura'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function GetCodigoEstado($id_factura = null) {
		if (!$id_factura) {
			$id_factura = $this->fields[$this->campo_id];
		}

		$query = "SELECT e.codigo FROM prm_estado_factura e JOIN factura f ON e.id_estado = f.id_estado WHERE f.id_factura = '$id_factura'";
		$respuesta = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($codigo) = mysql_fetch_array($respuesta);
		return $codigo;
	}

	function GetNumeroCobro($id_factura) {
		$query = "SELECT id_cobro FROM factura WHERE id_factura = '$id_factura';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $id;
	}

	function GetlistaCobroSoyDatoFactura($id = null, $tipo = null, $numero = null, $serie = null) {
		$lista_cobros = '';
		$where = " WHERE 1";
		if ($id) {
			$where .= " AND f.id_factura = '" . $id . "'";
		}
		if ($tipo) {
			$where .= " AND f.id_documento_legal = '" . $tipo . "'";
		}
		if ($numero) {
			$where .= " AND f.numero = '" . $numero . "'";
		}
		if ($serie) {
			$where .= " AND f.serie_documento_legal = '" . $serie . "'";
		}
		$query = "SELECT GROUP_CONCAT(id_cobro) , '1' as grupo FROM factura f " . $where . " GROUP BY grupo";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($lista_cobros, $grupo) = mysql_fetch_array($resp);
		return $lista_cobros;
	}

	function ListaDocumentosLegales($cobro) {
		if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
			$formato_numero = UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie') ? "CONCAT(LPAD(f.serie_documento_legal, 3, '0'), '-', f.numero)" : "f.numero";
			$query = "SELECT
                        group_concat(idDocLegal) as listaDocLegal
                        FROM (
                        SELECT
                         CONCAT(if(f.id_documento_legal != 0, if(f.letra is not null, if(f.letra != '',concat('LETRA ',f.letra), CONCAT(p.codigo,' '," . $formato_numero . ")), CONCAT(p.codigo,' '," . $formato_numero . ")), ''),IF(f.anulado=1,' (ANULADO)',''),' ') as idDocLegal
                        ,f.id_cobro
                        FROM factura f, prm_documento_legal p
                        WHERE f.id_documento_legal = p.id_documento_legal
                        AND id_cobro = '" . $this->fields['id_cobro'] . "'
                        )zz
                        GROUP BY id_cobro";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($lista) = mysql_fetch_array($resp);
			return $lista;
		} else {
			return UtilesApp::GetConf($this->sesion, 'NumeroFacturaConSerie') ? $this->fields['serie_documento_legal'] . '-' . $this->fields['numero'] : $this->fields['numero'];
		}
	}

	function PagarUsandoAdelantos() {
		$mvto_pago = new CtaCteFactMvto($this->sesion);
		$ccf = new CtaCteFact($this->sesion);

		$saldo_fact = $this->fields['total'];

		$query = "SELECT ccfm.id_factura_pago, ccfm.saldo, ccfm.saldo * fp.monto_moneda_cobro / fp.monto as saldo_moneda_cobro
			FROM cta_cte_fact_mvto ccfm
			JOIN factura_pago fp ON fp.id_factura_pago = ccfm.id_factura_pago
			JOIN neteo_documento nd ON nd.id_neteo_documento = fp.id_neteo_documento_adelanto
			JOIN documento dc ON dc.id_documento = nd.id_documento_cobro
			WHERE dc.id_cobro = '" . $this->fields['id_cobro'] . "' AND ccfm.saldo > 0";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while (list($id_pago, $saldo_pago, $saldo_moneda_cobro) = mysql_fetch_array($resp)) {
			if ($saldo_fact <= 0) {
				break;
			}
			$mvto_pago->LoadByPago($id_pago);
			$monto_moneda_cobro = min($saldo_moneda_cobro, $saldo_fact);
			$monto_pago = $monto_moneda_cobro * $saldo_pago / $saldo_moneda_cobro;
			$neteos = array(array($this->fields['id_factura'], $monto_moneda_cobro, $monto_pago));
			$ccf->AgregarNeteos($mvto_pago, $neteos);
			$saldo_fact -= $monto_moneda_cobro;

			//aqui deberia agregar la descripcion del pago
			$factura_pago = new FacturaPago($this->sesion);
			$facturas_asociadas = $factura_pago->GetListaFacturasSoyPago($id_pago, 'id_factura_pago', 'numero');
			$facturas_array = explode(",", $facturas_asociadas);
			$facturas_asociadas = "#" . join(", #", $facturas_array);
			$factura_pago->Load($id_pago);
			$descripcion_pago = explode(" :: ", $factura_pago->fields['descripcion']);
			$factura_pago->Edit('descripcion', $descripcion_pago[0] . " :: Facturas: " . $facturas_asociadas);
			$factura_pago->Write();
		}

		$query = "SELECT id_estado FROM factura WHERE id_factura = '" . $this->fields['id_factura'] . "'";
		$respuesta = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($estado) = mysql_fetch_array($respuesta);
		$this->fields['id_estado'] = $estado;
	}

	public function SearchQuery() {
		$query = "SELECT SQL_CALC_FOUND_ROWS
					prm_documento_legal.codigo as tipo
					, numero
					, factura.serie_documento_legal
					, factura.codigo_cliente
					, cliente.glosa_cliente
					, contrato.id_contrato as idcontrato
					, IF( TRIM(contrato.factura_razon_social) = TRIM( factura.cliente ),
								factura.cliente,
								IF( contrato.factura_razon_social IN ('',' '),
										factura.cliente,
										IF( contrato.factura_razon_social IS NULL,
												factura.cliente,
												CONCAT_WS(' ',factura.cliente,'(',contrato.factura_razon_social,')')
											)
									)
							) as factura_rsocial
					, fecha
					, usuario.username AS encargado_comercial
					, descripcion
					, prm_estado_factura.codigo as estado
					, factura.id_cobro
					, cobro.codigo_idioma as codigo_idioma
					, prm_moneda.simbolo
					, prm_moneda.cifras_decimales
					, prm_moneda.tipo_cambio
					, factura.id_moneda
					, factura.honorarios
					, factura.subtotal_gastos
					, factura.subtotal_gastos_sin_impuesto
					, factura.iva
					, total
					, '' as saldo_pagos
					, cta_cte_fact_mvto.saldo as saldo
					, '' as monto_pagos_moneda_base
					, '' as saldo_moneda_base
					, factura.id_factura
					, if(factura.RUT_cliente != contrato.rut,factura.cliente, 'no' ) as mostrar_diferencia_razon_social,
					factura.RUT_cliente
				FROM factura
				JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
				JOIN prm_moneda ON prm_moneda.id_moneda=factura.id_moneda
				LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
				LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura = factura.id_factura
				LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
				LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
				LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
				LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable";

		$wheres = array();

//		if (!empty($this->fields['numero'])) {
//			$wheres[] = "sa.id_solicitud_adelanto = '{$this->fields['id_solicitud_adelanto']}'";
//		}
//
//		if (!empty($this->fields['codigo_cliente'])) {
//			$wheres[] = "sa.codigo_cliente = '{$this->fields['codigo_cliente']}'";
//		}
//
//		if (!empty($this->fields['estado'])) {
//			$wheres[] = "sa.estado = '{$this->fields['estado']}'";
//		}
//
//		if (!empty($this->extra_fields['fecha_desde'])) {
//			$wheres[] = "sa.fecha >= '" . Utiles::fecha2sql($this->extra_fields['fecha_desde']) . "'";
//		}
//		if (!empty($this->extra_fields['fecha_hasta'])) {
//			$wheres[] = "sa.fecha <= '" . Utiles::fecha2sql($this->extra_fields['fecha_hasta']) . "'";
//		}

		$query .= " WHERE " . implode(' AND ', $wheres);
		$query .= " GROUP BY sa.id_solicitud_adelanto";

		return $query;
	}

	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadExcel($results,$tipo='Spreadsheet') {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('FACTURAS');

		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, $tipo);
		$writer->save(__('Facturas'));
	}
	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadRegistroVentas($results) {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('REGISTRO_VENTAS');

		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Excel');
		$writer->save(__('Reg_Venta'));
	}

	public function QueryReporte($orden, $where, $numero, $fecha1, $fecha2, $codigo_cliente_secundario,
		$tipo_documento_legal_buscado, $codigo_cliente, $codigo_asunto, $id_contrato, $id_cia,
		$id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura, $serie, $desde_asiento_contable ) {

		if ($orden == "") {
			$orden = "fecha DESC";
		}

		if ($where == '') {
			$where = 1;
			if ($numero != '') {
				$where .= " AND numero*1 = $numero*1 ";
			}
			if ($fecha1 && $fecha2) {
				$where .= " AND fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . " 00:00:00' AND '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
			} else if ($fecha1) {
				$where .= " AND fecha >= '" . Utiles::fecha2sql($fecha1) . ' 00:00:00' . "' ";
			} else if ($fecha2) {
				$where .= " AND fecha <= '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
			}
			if (UtilesApp::GetConf($this->sesion, 'CodigoSecundario') && $codigo_cliente_secundario) {
				$cliente = new Cliente($this->sesion);
				$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
				$codigo_cliente = $cliente->fields['codigo_cliente'];
			}
			if ($tipo_documento_legal_buscado) {
				$where .= " AND factura.id_documento_legal = '$tipo_documento_legal_buscado' ";
			}

			if ($codigo_cliente) {
				//$where .= " AND factura.codigo_cliente='".$codigo_cliente."' ";
				$where .= " AND cobro.codigo_cliente='" . $codigo_cliente . "' ";
			}
			if (UtilesApp::GetConf($this->sesion, 'CodigoSecundario') && $codigo_cliente_secundario) {
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigoSecundario($codigo_cliente_secundario);
				$id_contrato = $asunto->fields['id_contrato'];
			}
			if ($codigo_asunto) {
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigo($codigo_asunto);
				$id_contrato = $asunto->fields['id_contrato'];
			}
			if ($id_contrato) {
				$where .= " AND cobro.id_contrato=" . $id_contrato . " ";
			}
			if ($id_cia && ( method_exists('Conf', 'dbUser') && Conf::dbUser() == "rebaza" )) {
				$where .= " AND factura.id_cia = '$id_cia' ";
			}
			if ($id_cobro) {
				$where .= " AND factura.id_cobro=" . $id_cobro . " ";
			}
			if ($id_estado) {
				$where .= " AND factura.id_estado = " . $id_estado . " ";
			}
			if ($id_moneda) {
				$where .= " AND factura.id_moneda = " . $id_moneda . " ";
			}
			if ($grupo_ventas) {
				$where .= " AND prm_documento_legal.grupo = 'VENTAS' ";
			}
			if ($razon_social) {
				$where .= " AND factura.cliente LIKE '%" . $razon_social . "%'";
			}
			if ($descripcion_factura) {
				$where .= " AND (factura.descripcion LIKE '%" . $descripcion_factura . "%' OR factura.descripcion_subtotal_gastos LIKE '%" . $descripcion_factura . "%' OR factura.descripcion_subtotal_gastos_sin_impuesto LIKE '%" . $descripcion_factura . "%')";
			}
			if (!empty($serie) && $serie != -1) {
				$where .= " AND '$serie' LIKE CONCAT('%',factura.serie_documento_legal) ";
			}
			if (isset($desde_asiento_contable) && is_numeric($desde_asiento_contable)) {
				$where .= " AND factura.asiento_contable >= $desde_asiento_contable";
			}
		} else {
			$where = base64_decode($where);
		}

		return "SELECT SQL_CALC_FOUND_ROWS
				prm_documento_legal.codigo as tipo
			   , numero
			   , factura.serie_documento_legal
			   , factura.codigo_cliente
			   , cliente.glosa_cliente, contrato.id_contrato as idcontrato
			   , IF( TRIM(contrato.factura_razon_social) = TRIM( factura.cliente )
						OR contrato.factura_razon_social IN ('',' ')
						OR contrato.factura_razon_social IS NULL,
					factura.cliente,
					CONCAT_WS(' ',factura.cliente,'(',contrato.factura_razon_social,')')
				) as factura_rsocial
			   , usuario.username AS encargado_comercial
			   , fecha
			   , usuario.username AS encargado_comercial
			   , descripcion
			   , prm_estado_factura.codigo as codigo_estado
			   , prm_estado_factura.glosa as estado
			   , factura.id_cobro
			   , cobro.codigo_idioma as codigo_idioma
			   , prm_moneda.simbolo
			   , prm_moneda.cifras_decimales
			   , prm_moneda.tipo_cambio
			   , factura.id_moneda
			   , factura.honorarios
			   , factura.subtotal_gastos
			   , factura.subtotal_gastos_sin_impuesto
			   , factura.iva
			   , total
			   , '' as saldo_pagos
			   , -cta_cte_fact_mvto.saldo as saldo
			   , '' as monto_pagos_moneda_base
			   , '' as saldo_moneda_base
			   , factura.id_factura
			   , if(factura.RUT_cliente != contrato.rut,factura.cliente,'no' ) as mostrar_diferencia_razon_social
			   , GROUP_CONCAT(asunto.codigo_asunto SEPARATOR ';') AS codigos_asunto
			   , GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ';') AS glosas_asunto
			   , factura.RUT_cliente
		   FROM factura
		   JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
		   JOIN prm_moneda ON prm_moneda.id_moneda=factura.id_moneda
		   LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
		   LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura = factura.id_factura
		   LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
		   LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
		   LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
		   LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
		   LEFT JOIN cobro_asunto ON cobro_asunto.id_cobro = factura.id_cobro
		   LEFT JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
		   WHERE $where
		   GROUP BY factura.id_factura";
	}

	public function DatosReporte($orden, $where, $numero, $fecha1, $fecha2, $codigo_cliente_secundario,
		$tipo_documento_legal_buscado, $codigo_cliente, $codigo_asunto, $id_contrato, $id_cia,
		$id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura, $serie, $desde_asiento_contable) {

		$query = $this->QueryReporte($orden, $where, $numero, $fecha1, $fecha2, $codigo_cliente_secundario,
		$tipo_documento_legal_buscado, $codigo_cliente, $codigo_asunto, $id_contrato, $id_cia,
		$id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura, $serie, $desde_asiento_contable);

		//agregar al reporte de factura las columnas, monto real - observaciones - Saldo - fecha último pago
		$statement = $this->sesion->pdodbh->prepare($query);
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);

		foreach ($results as $key => $fila) {
			//monto_real
			$monto_real = $this->ObtenerValorReal($fila['id_factura']);
			$results[$key]['monto_real'] = strtoupper($fila['codigo_estado']) == 'A' ? '0' : $monto_real;

			//observaciones
			$ids_doc = $this->ObtenerIdsDocumentos($fila['id_factura']);
			$ids_doc_array = explode('||', $ids_doc);
			$valores = array();
			$comentarios = '';
			if (true || $fila['total'] != $monto_real) {
				foreach ($ids_doc_array as $par_cod_num) {
					$documento = strtr($par_cod_num, '::', ' ');
					if (strlen($documento) > 0) {
						array_push($valores, $documento);
					}
				}
				$comentarios = implode(', ', $valores);
			}
			$results[$key]['observaciones'] = $comentarios;

			//saldo pago, fecha ultimo pago
			$query2 = "SELECT SUM(ccfmn.monto) as saldo_pagos, MAX(ccfm.fecha_modificacion) as fecha_ultimo_pago
							FROM factura_pago AS fp
							JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
							JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
							LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
							LEFT JOIN prm_moneda mo ON ccfm.id_moneda = mo.id_moneda
							WHERE ccfm2.id_factura =  '" . $fila['id_factura'] . "' GROUP BY ccfm2.id_factura ";

			$statement = $this->sesion->pdodbh->prepare($query2);
			$statement->execute();
			$pago = $statement->fetch(PDO::FETCH_ASSOC);

			$results[$key]['pagos'] = $pago['saldo_pagos'] > 0 ? $pago['saldo_pagos'] : '0';
			$results[$key]['fecha_ultimo_pago'] = $pago['fecha_ultimo_pago'];
		}

		return $results;
	}
}

#end Class
if(!class_exists('ListaFacturas')) {
	class ListaFacturas extends Lista {

		function ListaFacturas($sesion, $params, $query) {
			$this->Lista($sesion, 'Factura', $params, $query);
		}

	}
}
