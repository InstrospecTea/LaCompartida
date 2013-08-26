<?php

require_once dirname(__FILE__) . '/../conf.php';

class FacturaPago extends Objeto {

	public static $llave_carga_masiva = 'id_factura_pago';
	public static $campos_carga_masiva = array(
		'id_factura' => array(
			'titulo' => 'Factura Asociada',
			'requerido' => true
		),
		'fecha' => array(
			'titulo' => 'Fecha',
			'tipo' => 'fecha'
		),
    'monto' => array(
			'titulo' => 'Monto',
			'tipo' => 'numero'
		),
    'id_moneda' => array(
			'titulo' => 'Moneda',
			'relacion' => 'Moneda'
		),
		'tipo_doc' => array(
			'titulo' => 'Tipo Documento',
			'tipo' => array('EP', 'CP', 'RP', 'TP', 'OP', 'CC')
		),
    'nro_documento' => 'Número Doc Pago',
    'nro_cheque' => 'Número Cheque',
    'descripcion' => 'Descripción'
	);

	public static $configuracion_reporte = array(
			array(
					'format' => 'date',
					'title' => 'Fecha Pago',
					'field' => 'fecha_pago',
			),
			array(
					'title' => 'Tipo',
					'field' => 'tipo',
			),
			array(
					'title' => 'N° Documento',
					'field' => 'numero',
			),
			array(
					'title' => 'Serie Documento',
					'field' => 'serie_documento_legal',
					'visible' => false,
			),
			array(
					'title' => 'Código Cliente',
					'field' => 'cliente_pago',
			),
			array(
					'title' => 'Cliente',
					'field' => 'glosa_cliente',
			),
			array(
					'title' => 'Acuerdo Comercial',
					'field' => 'idcontrato',
					'visible' => false,
			),
			array(
					'title' => 'Razón Social',
					'field' => 'factura_razon_social',
			),
			array(
					'title' => 'Encargado Comercial',
					'field' => 'encargado_comercial',
			),
			array(
					'title' => 'Estado Documento',
					'field' => 'estado',
			),
			array(
					'title' => 'N° Liquidación',
					'field' => 'id_cobro',
			),
			array(
					'title' => 'Concepto Pago',
					'field' => 'concepto_pago',
			),
			array(
					'title' => 'Descripción Pago',
					'field' => 'descripcion_pago',
			),
			array(
					'title' => 'Nombre Banco',
					'field' => 'nombre_banco',
			),
			array(
					'title' => 'N° Cuenta',
					'field' => 'numero_cuenta',
			),
			array(
					'format' => 'date',
					'title' => 'Fecha Factura',
					'field' => 'fecha_factura',
			),
			array(
					'title' => 'Código Idioma',
					'field' => 'codigo_idioma',
					'visible' => false,
			),
			array(
					'title' => 'Símbolo Moneda',
					'field' => 'simbolo_factura',
			),
			array(
					'title' => 'Cifras Decimales',
					'field' => 'cifras_decimales_factura',
					'visible' => false,
			),
			array(
					'format' => 'number',
					'title' => 'Tipo Cambio',
					'field' => 'tipo_cambio_pago',
			),
			array(
					'format' => 'number',
					'extras' =>
					array(
							'symbol' => 'simbolo_factura',
					),
					'title' => 'Honorarios',
					'field' => 'honorarios',
			),
			array(
					'format' => 'number',
					'extras' =>
					array(
							'symbol' => 'simbolo_factura',
					),
					'title' => 'Subtotal Gastos',
					'field' => 'subtotal_gastos',
			),
			array(
					'format' => 'number',
					'extras' =>
					array(
							'symbol' => 'simbolo_factura',
					),
					'title' => 'Subtotal Gastos sin impuesto',
					'field' => 'subtotal_gastos_sin_impuesto',
			),
			array(
					'format' => 'number',
					'extras' =>
					array(
							'symbol' => 'simbolo_factura',
					),
					'title' => 'IVA',
					'field' => 'iva',
			),
			array(
					'format' => 'number',
					'extras' =>
					array(
							'symbol' => 'simbolo_factura',
					),
					'title' => 'Total Factura',
					'field' => 'monto_factura',
			),
			array(
					'format' => 'number',
					'extras' =>
					array(
							'symbol' => 'simbolo_pago',
					),
					'title' => 'Monto Aporte',
					'field' => 'monto_aporte',
			),
			array(
					'format' => 'number',
					'extras' =>
					array(
							'symbol' => 'simbolo_factura',
					),
					'title' => 'Saldo Factura',
					'field' => 'saldo_factura',
			),
			array(
					'format' => 'number',
					'title' => 'Moneda Pago',
					'field' => 'simbolo_pago',
			),
			array(
					'format' => 'number',
					'extras' =>
					array(
							'symbol' => 'simbolo_pago',
					),
					'title' => 'Saldo Pago',
					'field' => 'saldo_pago',
			),
			array(
					'title' => 'Códigos Asuntos',
					'field' => 'codigos_asunto',
			),
			array(
					'title' => 'Asuntos',
					'field' => 'glosas_asunto',
			),
			array(
					'title' => 'Descripción Factura',
					'field' => 'descripcion_factura',
			),
	);

	function FacturaPago($sesion, $fields = "", $params = "") {
		$this->tabla = "factura_pago";
		$this->campo_id = "id_factura_pago";
		$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->log_update = true;
	}

	function LoadByIdContabilidad($id_contabilidad) {
		$query = "SELECT id_factura_pago FROM factura_pago WHERE id_contabilidad = '$id_contabilidad';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if ($id)
			return $this->Load($id);
		return false;
	}

	function Id($id = null) {
		if ($id)
			$this->fields[$this->campo_id] = $id;
		if (empty($this->fields[$this->campo_id]))
			return false;
		return $this->fields[$this->campo_id];
	}

	function LoadByNeteoAdelanto($id_neteo) {
		$query = "SELECT id_factura_pago FROM factura_pago WHERE id_neteo_documento_adelanto = '$id_neteo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if ($id)
			return $this->Load($id);
		return false;
	}

	function Eliminar() {
		$cta_cte_fact = new CtaCteFact($this->sesion);
		if ($cta_cte_fact->EliminarMvtoPago($this->fields['id_factura_pago'])) {
			if (!empty($this->fields['id_neteo_documento_adelanto'])) {
				$neteo_documento = new NeteoDocumento($this->sesion);
				$neteo_documento->Load($this->fields['id_neteo_documento_adelanto']);
				$neteo_documento->Reestablecer(2);
				$neteo_documento->Delete();
			}
			return $this->Delete();
		}
		else
			return false;
	}

	function HtmlListaPagos($sesion, & $factura, $id_documento) {
		$moneda = new Moneda($sesion);
		$moneda->Load($factura->fields['id_moneda']);
		$lista_pagos = $factura->GetPagosSoyFactura(null, $id_documento);
		$html = "<table width=\"500\">
					<tr bgcolor=\"#aaffaa\" style=\"font-size: 10pt;\">
						<th width=\"50\" align=center>N°</th>
						<th width=\"100\" align=center>" . __('Fecha') . "</th>
						<th width=200>" . __('Descripción') . "</th>
						<th width=100>" . __('Monto Pago') . "</th>
						<th style='width:60px;white-space:nowrap;'>Opc.</th>
					</tr>";
		for ($i = 0; $i < $lista_pagos->num; $i++) {
			$pago = $lista_pagos->Get($i);

			$html .= "<tr heigth=\"16\">";
			$html .= "<td align=center>" . $pago->fields['id_factura_pago'] . "</td>";
			$html .= "<td align=center>" . Utiles::sql2fecha("d-m-Y", $pago->fields['fecha']) . "</td>";
			$html .= "<td align=center>" . $pago->fields['descripcion'] . "</td>";
			$html .= "<td align=center>" . $moneda->fields['simbolo'] . " " . number_format($pago->fields['monto_aporte'], $moneda->fields['cifras_decimales']) . "</td>";
			$html .= "<td align=center>
									<a href='javascript:void(0)' onclick=\"nuovaFinestra('Editar_Factura_Pago', 730, 580, 'agregar_pago_factura.php?id_factura_pago=" . $pago->fields['id_factura_pago'] . "&id_factura=" . $factura->fields['id_factura'] . "&id_cobro=" . $factura->fields['id_cobro'] . "&popup=1', 'top=100, left=155');\" ><img src='" . Conf::ImgDir() . "/editar_on.gif' border=\"0\" title=\"Editar\"/></a>
									<img src='" . Conf::ImgDir() . "/cruz_roja_nuevo.gif' onclick=\"if( confirm('Está eliminando un pago. Se reajustarán los saldos de los documentos asociados. ¿Desea continuar?') )EliminarPago('" . $pago->fields['id_factura_pago'] . "');\" />";
			$html .= UtilesApp::LogDialog($sesion, 'factura_pago', $pago->fields['id_factura_pago']);



			$html.="</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";

		echo $html;
	}

	function GeneraHTMLFacturaPago($id_formato_factura_pago = null) {
		if ($id_formato_factura_pago != null) {
			$templateData = UtilesApp::TemplateFacturaPago($this->sesion, $id_formato_factura_pago);
			$cssData = UtilesApp::TemplateFacturaPagoCSS($this->sesion, $id_formato_factura_pago);
		} else {
			$templateData = UtilesApp::TemplateFacturaPago($this->sesion, 1);
			$cssData = UtilesApp::TemplateFacturaPagoCSS($this->sesion, 1);
		}
		$parser = new TemplateParser($templateData);
		$lang = 'es';
		$html = $this->generarDocumentoPago($parser, 'VOUCHER', $lang);

		$html_css = array();
		$html_css['html'] = $html;
		$html_css['css'] = $cssData;

		return $html_css;
	}

	function generarDocumentoPago($parser_factura_pago, $theTag = '', $lang = 'es', $arr_fila_tmp = null) {
		if (!isset($parser_factura_pago->tags[$theTag]))
			return;
		$idioma = new Objeto($this->sesion, '', '', 'prm_idioma', 'codigo_idioma');
		$idioma->Load($lang);

		$query = "SELECT
			cta_cte_fact_mvto.id_cta_cte_mvto
			,cliente.glosa_cliente
			,cliente.rut
			,prm_banco.nombre as glosa_banco
			,cuenta_banco.numero as numero_cuenta
			,prm_moneda.glosa_moneda
			,factura_pago.pago_retencion
		FROM factura_pago
		LEFT JOIN cliente ON cliente.codigo_cliente = factura_pago.codigo_cliente
		LEFT JOIN prm_banco ON prm_banco.id_banco = factura_pago.id_banco
		LEFT JOIN cuenta_banco ON (cuenta_banco.id_banco = prm_banco.id_banco AND cuenta_banco.id_cuenta = factura_pago.id_cuenta)
		LEFT JOIN prm_moneda ON prm_moneda.id_moneda = factura_pago.id_moneda
		LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura_pago = factura_pago.id_factura_pago
		WHERE factura_pago.id_factura_pago = '" . $this->Id() . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_mvto_pago, $glosa_cliente, $rut_cliente, $glosa_banco, $numero_cuenta, $glosa_moneda, $pago_retencion) = mysql_fetch_array($resp);

		$mvto = new CtaCteFactMvto($this->sesion);
		$lista = $mvto->GetNeteosSoyPago($id_mvto_pago);

		$mvto_neteado = new CtaCteFactMvto($this->sesion);
		$factura = new Factura($this->sesion);
		$factura_encabezado = new Factura($this->sesion);

		//EL voucher debe mostrar como encabezado el nombre de
		//la razon social asociada a la 1era Factura que esta pagando
		$lista_facturas_desde_pago = $this->GetListaFacturasSoyPago();
		$arr_factura = split(',', $lista_facturas_desde_pago);
		$factura_encabezado->Load($arr_factura[0]);
		//print_r($factura_encabezado->fields);
		$glosa_cliente_encabezado = $factura_encabezado->fields['cliente'];
		$rut_cliente_encabezado = $factura_encabezado->fields['RUT_cliente'];


		$html = $parser_factura_pago->tags[$theTag];

		switch ($theTag) {
			case 'VOUCHER':

				$html = str_replace('%ENCABEZADO%', $this->generarDocumentoPago($parser_factura_pago, 'ENCABEZADO', $lang), $html);
				$html = str_replace('%CLIENTE%', $this->generarDocumentoPago($parser_factura_pago, 'CLIENTE', $lang), $html);
				$html = str_replace('%DATOS_FACTURA_PAGO%', $this->generarDocumentoPago($parser_factura_pago, 'DATOS_FACTURA_PAGO', $lang), $html);

				break;

			case 'ENCABEZADO':

				if (UtilesApp::GetConf($this->sesion, 'PdfLinea1'))
					$PdfLinea1 = UtilesApp::GetConf($this->sesion, 'PdfLinea1');
				$html = str_replace('%estudio_valor%', $PdfLinea1, $html);

				break;

			case 'CLIENTE':

				$html = str_replace('%Num%', __('N°'), $html);
				$html = str_replace('%rut%', __('RUT'), $html);
				$html = str_replace('%cliente%', __('CLIENTE'), $html);
				$html = str_replace('%cheque%', __('Cheque'), $html);
				$html = str_replace('%concepto%', __('Concepto'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%banco%', __('Banco'), $html);
				$html = str_replace('%cta_bco%', __('Cta. Bco.'), $html);
				$html = str_replace('%moneda%', __('Moneda'), $html);

				$html = str_replace('%Num_valor%', $this->fields['nro_documento'], $html);
				$html = str_replace('%rut_valor%', $rut_cliente_encabezado, $html);
				$html = str_replace('%cliente_valor%', $glosa_cliente_encabezado, $html);
				$html = str_replace('%cheque_valor%', $this->fields['nro_cheque'], $html);
				$html = str_replace('%concepto_valor%', $this->fields['descripcion'], $html);
				$html = str_replace('%fecha_valor%', $this->fields['fecha'], $html);
				$html = str_replace('%banco_valor%', $glosa_banco, $html);
				$html = str_replace('%cta_bco_valor%', $numero_cuenta, $html);
				$html = str_replace('%moneda_valor%', $glosa_moneda, $html);
				break;

			case 'DATOS_FACTURA_PAGO':

				$html = str_replace('%Num%', __('N°'), $html);
				$html = str_replace('%tipo%', __('Tipo'), $html);
				$html = str_replace('%documento%', __('Documento'), $html);
				$html = str_replace('%concepto%', __('Concepto'), $html);
				$html = str_replace('%importe%', __('Importe'), $html);
				$html = str_replace('%neto%', __('Neto'), $html);
				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%detraccion%', __('Detracción'), $html);
				$html = str_replace('%total_mayuscula%', __('TOTAL'), $html);
				$html = str_replace('%moneda%', __('Moneda'), $html);
				$html = str_replace('%hecho_por%', __('Hecho por'), $html);
				$html = str_replace('%aprobado_por%', __('Aprobado por'), $html);
				$html = str_replace('%V_B%', __('V° B°'), $html);

				$html = str_replace('%hecho_por_valor%', '', $html);
				$html = str_replace('%V_B_hecho_por_valor%', '', $html);
				$html = str_replace('%aprobado_por_valor%', '', $html);
				$html = str_replace('%V_B_aprobado_por_valor%', '', $html);



				$row = "";
				$html_lista_facturas = $parser_factura_pago->tags['LISTA_FACTURAS'];
				$html_lista_facturas_tmp = $html_lista_facturas;

				for ($i = 0; $i < $lista->num; $i++) {
					$neteo = $lista->Get($i);
					if ($mvto_neteado->Load($neteo->fields['id_mvto_deuda'])) {
						if ($factura->Load($mvto_neteado->fields['id_factura'])) {
							$row .= str_replace($html_lista_facturas_tmp, $this->generarDocumentoPago($parser_factura_pago, 'LISTA_FACTURAS', $lang, $mvto_neteado), $html_lista_facturas_tmp);
						}
					}
				}
				$html = str_replace('%LISTA_FACTURAS%', $row, $html);
				if ($this->fields['pago_retencion'] == 1) {
					$html = str_replace('%saldo_total_valor%', '', $html);
					$html = str_replace('%saldo_retencion_valor%', $this->fields['monto'], $html);
					$html = str_replace('%saldo_neteo_valor%', '', $html);
				} else {
					$html = str_replace('%saldo_total_valor%', '', $html);
					$html = str_replace('%saldo_retencion_valor%', '', $html);
					$html = str_replace('%saldo_neteo_valor%', $this->fields['monto'], $html);
					;
				}
				break;


			case 'LISTA_FACTURAS':
				$query_factura = "SELECT
								prm_documento_legal.codigo
								,prm_documento_legal.glosa
								,factura.serie_documento_legal
								,factura.numero
								,factura.descripcion
								,cta_cte_fact_mvto.monto_bruto as monto_bruto
								,cta_cte_fact_mvto.saldo as saldo
								FROM factura
								LEFT JOIN prm_documento_legal ON prm_documento_legal.id_documento_legal = factura.id_documento_legal
								LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura = factura.id_factura
								WHERE factura.id_factura = '" . $arr_fila_tmp->fields['id_factura'] . "'";

				$resp_factura = mysql_query($query_factura, $this->sesion->dbh) or Utiles::errorSQL($query_factura, __FILE__, __LINE__, $this->sesion->dbh);
				list($fac_codigo, $fac_glosa, $fac_serie, $fac_numero, $fac_descripcion, $monto_bruto, $saldo) = mysql_fetch_array($resp_factura);
				if ($monto_bruto != '0') {
					$monto_bruto = $monto_bruto * (-1);
				}
				if ($saldo != '0') {
					$saldo = $saldo * (-1);
				}
				if ($pago_retencion == 1) {
					$retencion = $this->fields['monto'];
				} else {
					$neto = $this->fields['monto'];
				}

				$id_concepto = $this->fields['id_concepto'];

				$fac_concepto = $this->glosaFacturaVoucher($monto_bruto, $this->fields['monto'], $pago_retencion, $id_concepto);

				$factura_ = new Factura($this->sesion);

				$html = str_replace('%factura_codigo%', $fac_codigo, $html);
				$html = str_replace('%factura_numero%', $factura_->ObtenerNumero(null, $fac_serie, $fac_numero), $html);
				$html = str_replace('%factura_descripcion%', $fac_descripcion, $html);
				$html = str_replace('%factura_concepto%', $fac_concepto, $html);
				$html = str_replace('%factura_total%', $monto_bruto, $html);
				$html = str_replace('%factura_retencion%', $retencion, $html);
				$html = str_replace('%factura_neto%', $neto, $html);
				break;
		}
		return $html;
	}

	function glosaFacturaVoucher($monto_bruto, $saldo, $pago_retencion, $id_concepto = null) {
		if (!$id_concepto) {
			$id_concepto = $this->fields['id_concepto'];
		}
		$query = "	SELECT glosa, pje_variable
					FROM prm_factura_pago_concepto
					WHERE id_concepto = " . $id_concepto;
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($glosa_concepto, $pje_variable) = mysql_fetch_array($resp);

		$pje = ($saldo * 100) / $monto_bruto;
		$pje = 100 - $pje;
		$pje = number_format($pje, 0);
		$mje = "";
		if ($pje_variable == 1)
			$mje = str_replace('%', " $pje%", $glosa_concepto);
		else
			$mje = $glosa_concepto;

		$mje_saldado = $this->GetSoyElUltimoPago();
		$mje .= " " . $mje_saldado;

		return strtoupper($mje);
	}

	function GetSoyElUltimoPago($id_factura_pago = null) {
		/*
		 * Verificar si la factura_pago saldo la deuda de la Factura
		 * de ser asi retornanr mensaje
		 * 
		 * Para esto se debe cumplir:
		 * 		ser el ultimo factura_pago asociado
		 * 		saldo >= 0
		 */

		//instanciamos variables y clases por defecto
		if (!$id_factura_pago) {
			$id_factura_pago = $this->Id();
		}
		$mje = '';
		$factura = new Factura($this->sesion);
		$cta_cte_mvto = new CtaCteFactMvto($this->sesion);
		//obtenemos todas las facturas asociadas a la factura pago actual
		$lista_facturas = $this->GetListaFacturasSoyPago($id_factura_pago);
		//de la lista de facturas asociadas, buscamos a la última factura_pago
		$ultimo_id_factura_pago = $factura->GetUltimoPagoSoyFactura($lista_facturas);
		//Obtenemos el saldo de la factura (saldo deuda)
		$saldo = $cta_cte_mvto->GetSaldoDeuda($lista_facturas);
		//verificamos si el pago actual es el último pago
		if ($ultimo_id_factura_pago == $id_factura_pago) {
			/*
			 * si el saldo es negativo, significa que es plata encontra
			 * (aún no se termina de pagar)
			 */
			if ($saldo >= 0) {
				$mje = " (" . __('Saldado') . ") ";
			}
		}
		return $mje;
	}

	function GetFacturasSoyPago($id = null, $col_condicion = null) {
		if (!$id) {
			$id = $this->Id();
		}
		if (!$col_condicion) {
			$col_condicion = 'id_factura_pago';
		}
		$query = "SELECT f.*
								FROM factura AS f
								JOIN cta_cte_fact_mvto AS ccfm ON f.id_factura = ccfm.id_factura
								JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_deuda = ccfm.id_cta_cte_mvto
								LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_pago = ccfm2.id_cta_cte_mvto
								LEFT JOIN factura_pago fp ON ccfm2.id_factura_pago = fp.id_factura_pago
								WHERE fp." . $col_condicion . " =  '" . $id . "'";
//		echo $query; //FFF: Agrego comprobacion vs respaldo documento.//left join documento d on d.id_cobro=f.id_cobro and d.id_factura_pago=fp.id_factura_pago
		return new ListaFacturas($this->sesion, null, $query);
	}

	function GetListaFacturasSoyPago($id = null, $col_condicion = null, $col_seleccion = null) {
		if (!$id) {
			$id = $this->Id();
		}
		if (!$col_condicion) {
			$col_condicion = 'id_factura_pago';
		}
		if (!$col_seleccion) {
			$col_seleccion = 'id_factura';
		}
		$lista_facturas = $this->GetFacturasSoyPago($id, $col_condicion);
		for ($i = 0; $i < $lista_facturas->num; $i++) {
			$item = $lista_facturas->Get($i);
			if ($i == 0)
				$lista_ids = $item->fields[$col_seleccion];
			else
				$lista_ids .= ',' . $item->fields[$col_seleccion];
		}
		return $lista_ids;
	}

	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadExcel($results) {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('FACTURAS_PAGOS');

		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save(__('Facturas_Pagos'));
	}

	public function QueryReporte($orden, $where, $id_concepto, $id_banco, $id_cuenta, $id_estado, $pago_retencion, $fecha1, $fecha2, $serie, $numero, $codigo_cliente_secundario, $tipo_documento_legal_buscado, $codigo_asunto, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura) {
		if ($orden == '')
			$orden = 'fp.fecha DESC';

		if ($where == '') {
			$where = 1;

			if (UtilesApp::GetConf($this->sesion, 'SelectMultipleFacturasPago')) {
				if (isset($_REQUEST['id_concepto'])) {
					$condiciones = "";
					foreach ($_REQUEST['id_concepto'] as $key => $value) {
						if (strlen($condiciones) > 0) {
							$condiciones .= " OR ";
						}
						$condiciones .= " fp.id_concepto = '$value' ";
					}
					$where .= " AND ( $condiciones ) ";
				}
				if (isset($_REQUEST['id_banco'])) {
					$condiciones = "";
					foreach ($_REQUEST['id_banco'] as $key => $value) {
						if (strlen($condiciones) > 0) {
							$condiciones .= " OR ";
						}
						$condiciones .= " fp.id_banco = '$value' ";
					}
					$where .= " AND ( $condiciones ) ";
				}
				if (isset($_REQUEST['id_cuenta'])) {
					$condiciones = "";
					foreach ($_REQUEST['id_cuenta'] as $key => $value) {
						if (strlen($condiciones) > 0) {
							$condiciones .= " OR ";
						}
						$condiciones .= " fp.id_cuenta = '$value' ";
					}
					$where .= " AND ( $condiciones ) ";
				}
				if (isset($_REQUEST['id_estado'])) {
					$condiciones = "";
					foreach ($_REQUEST['id_estado'] as $key => $value) {
						if (strlen($condiciones) > 0) {
							$condiciones .= " OR ";
						}
						$condiciones .= " factura.id_estado = '$value' ";
					}
					$where .= " AND ( $condiciones ) ";
				}
			} else {
				if ($id_concepto) {
					$where .= " AND fp.id_concepto = '$id_concepto' ";
				}
				if ($id_banco) {
					$where .= " AND fp.id_banco = '$id_banco' ";
				}
				if ($id_cuenta) {
					$where .= " AND fp.id_cuenta = '$id_cuenta' ";
				}
				if ($id_estado) {
					$where .= " AND factura.id_estado = '$id_estado' ";
				}
			}

			if ($pago_retencion) {
				$where .= " AND fp.pago_retencion = '$pago_retencion' ";
			}
			if ($fecha1 && $fecha2) {
				$where .= " AND fp.fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . " 00:00:00' AND '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
			} else if ($fecha1) {
				$where .= " AND fp.fecha >= '" . Utiles::fecha2sql($fecha1) . ' 00:00:00' . "' ";
			} else if ($fecha2) {
				$where .= " AND fp.fecha <= '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
			}

			if (!empty($serie) && $serie != -1) {
				$where .= " AND '$serie' LIKE CONCAT('%',factura.serie_documento_legal) ";
			}
			if ($numero != '')
				$where .= " AND factura.numero*1 = $numero*1 ";

			if (UtilesApp::GetConf($this->sesion, 'CodigoSecundario') && $codigo_cliente_secundario) {
				$cliente = new Cliente($this->sesion);
				$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
				$codigo_cliente = $cliente->fields['codigo_cliente'];
			}
			if ($tipo_documento_legal_buscado)
				$where .= " AND factura.id_documento_legal = '$tipo_documento_legal_buscado' ";

			if ($codigo_cliente) {
				$where .= " AND fp.codigo_cliente='$codigo_cliente' ";
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
				$where .= " AND cobro.id_contrato='$id_contrato' ";
			}
			if ($id_cobro) {
				$where .= " AND factura.id_cobro='$id_cobro' ";
			}
			if ($id_estado) {
				$where .= " AND factura.id_estado = '$id_estado' ";
			}
			if ($id_moneda) {
				$where .= " AND fp.id_moneda = '$id_moneda' ";
			}
			if ($grupo_ventas) {
				$where .= " AND prm_documento_legal.grupo = 'VENTAS' ";
			}
			if ($razon_social) {
				$where .= " AND factura.cliente LIKE '%$razon_social%'";
			}
			if ($descripcion_factura) {
				$where .= " AND (fp.descripcion LIKE '%$descripcion_factura%'";
				$where .= " OR factura.descripcion_subtotal_gastos LIKE '%$descripcion_factura%' ";
				$where .= " OR factura.descripcion_subtotal_gastos_sin_impuesto LIKE '%$descripcion_factura%')";
			}
		} else {
			$where = base64_decode($where);
		}

		return "SELECT SQL_CALC_FOUND_ROWS
				  factura.id_factura
				, factura.fecha as fecha_factura
				, factura.id_moneda
				, factura.honorarios
				, factura.subtotal_gastos
				, factura.subtotal_gastos_sin_impuesto
				, factura.iva
				, factura.total
				, factura.id_factura
				, factura.id_cobro
				, factura.numero
				, factura.serie_documento_legal
				, factura.descripcion AS descripcion_factura
				, factura.cliente as factura_razon_social
				, fp.fecha as fecha_pago
				, fp.descripcion as descripcion_pago
				, fp.id_moneda AS id_moneda_factura_pago
				, fp.codigo_cliente as cliente_pago
				, fp.id_factura_pago
				, prm_documento_legal.codigo as tipo
				, factura.serie_documento_legal
				, cliente.glosa_cliente
				, prm_banco.nombre as nombre_banco 
				, cuenta_banco.numero as numero_cuenta 
				, co.glosa as concepto_pago
				, usuario.username AS encargado_comercial
				, prm_estado_factura.glosa as estado
				, cobro.codigo_idioma as codigo_idioma
				, moneda_factura.simbolo as simbolo_factura
				, moneda_factura.cifras_decimales as cifras_decimales_factura
				, moneda_factura.tipo_cambio as tipo_cambio_factura
				, moneda_pago.simbolo as simbolo_pago
				, moneda_pago.cifras_decimales as cifras_decimales_pago
				, moneda_pago.tipo_cambio as tipo_cambio_pago
				, -1 * ccfm2.saldo as saldo_factura
				, -1 * ccfm.saldo as saldo_pago
				, ccfm.monto_bruto as monto_pago
				, -1 * ccfm2.monto_bruto as monto_factura
				, ccfmn.monto AS monto_aporte
				, contrato.codigo_idioma
				, if(factura.RUT_cliente != contrato.rut,factura.cliente,'no' ) as mostrar_diferencia_razon_social
				, GROUP_CONCAT(asunto.codigo_asunto SEPARATOR ';') AS codigos_asunto
				, GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ';') AS glosas_asunto
			FROM factura_pago AS fp
			JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
			JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
			LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
			LEFT JOIN factura ON ccfm2.id_factura = factura.id_factura
			LEFT JOIN prm_banco ON fp.id_banco = prm_banco.id_banco 
			LEFT JOIN cuenta_banco ON fp.id_cuenta = cuenta_banco.id_cuenta 
			LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
			left join factura_cobro fc ON fc.id_factura=factura.id_factura and fc.id_cobro=cobro.id_cobro
			LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
			LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
			LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
			LEFT JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
			LEFT JOIN prm_moneda as moneda_factura ON moneda_factura.id_moneda=factura.id_moneda
			LEFT JOIN prm_moneda as moneda_pago ON moneda_pago.id_moneda = fp.id_moneda
			LEFT JOIN prm_factura_pago_concepto AS co ON fp.id_concepto = co.id_concepto
			LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
			LEFT JOIN cobro_asunto ON cobro_asunto.id_cobro = factura.id_cobro
			LEFT JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
			WHERE $where
			GROUP BY fp.id_factura_pago";
	}

	public function DatosReporte($orden, $where, $id_concepto, $id_banco, $id_cuenta, $id_estado, $pago_retencion, $fecha1, $fecha2, $serie, $numero, $codigo_cliente_secundario, $tipo_documento_legal_buscado, $codigo_asunto, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura) {
		$query = $this->QueryReporte($orden, $where, $id_concepto, $id_banco, $id_cuenta, $id_estado, $pago_retencion, $fecha1, $fecha2, $serie, $numero, $codigo_cliente_secundario, $tipo_documento_legal_buscado, $codigo_asunto, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura);

		//agregar al reporte de factura las columnas, monto real - observaciones - Saldo - fecha último pago
		$statement = $this->sesion->pdodbh->prepare($query);
		$statement->execute();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function PreCrearDato($data) {
		if (!empty($data['id_factura'])) {
			$factura_padre_array = explode('-', $data['id_factura']);
			$factura_padre_serie = intval($factura_padre_array[0]);
			$factura_padre_numero = intval($factura_padre_array[1]);
			$factura_padre = new Factura($this->sesion);
			$factura_padre->LoadByNumero($factura_padre_numero, $factura_padre_serie);

			if ($factura_padre->Loaded()) {
				// Ocupo estos datos como auxiliares
				$this->extra_fields['id_factura'] = $factura_padre->fields['id_factura'];
				$this->extra_fields['id_cobro'] = $factura_padre->fields['id_cobro'];

				$data['codigo_cliente'] = $factura_padre->fields['codigo_cliente'];
			} else {
				throw new Exception("No existe la factura indicada", 1);
			}

			unset($data['id_factura']);
		}

		$data['monto_moneda_cobro'] = $data['monto'];
		$data['id_moneda_cobro'] = $data['id_moneda'];

		return $data;
	}

	public function PostCrearDato() {
		$Movimiento = new CtaCteFact($this->sesion);
		// Si es NC los montos se consideran como pagos
		$neteos = array();
		if (!empty($this->extra_fields['id_factura'])) {
			$neteos = array(array(
				$this->extra_fields['id_factura'],
				$this->fields['monto']
			));

			$Pagina = new Pagina($this->sesion);
			$id_cobro = $this->extra_fields['id_cobro'];
			$Movimiento->IngresarPago($this, $neteos, $id_cobro, $Pagina);
		}
	}
}

class ListaFacturaPago extends Lista {

	function ListaFacturaPago($sesion, $params, $query) {
		$this->Lista($sesion, 'FacturaPago', $params, $query);
	}

}
