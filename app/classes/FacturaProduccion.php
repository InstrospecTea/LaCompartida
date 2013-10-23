<?php

require_once dirname(__FILE__) . '/../conf.php';

class FacturaProduccion {

	public static $configuracion_reporte = array (
		array (
			'field' => 'id_factura',
			'title' => 'Correlativo',
			'visible' => false
		),
		array (
			'field' => 'id_contrato',
			'title' => 'Código Asunto'
		),
		array (
			'field' => 'glosas_asunto',
			'title' => 'Glosa Asunto',
		),
		array (
			'field' => 'codigo_cliente',
			'title' => 'Codigo cliente',
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo Doc.'
		),
		array (
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
		),
		array (
			'field' => 'numero',
			'format' => 'number',
			'title' => 'Nº Documento',
			'extras' => array(
				'subtotal' => false
			)
		),
		array (
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array (
			'field' => 'total',
			'format' => 'number',
			'title' => 'Total Facturado'
		),
		array (
			'field' => 'username_generador',
			'title' => 'Código Generador'
		),

		array (
			'field' => 'nombre_generador',
			'title' => 'Nombre Generador'
		),

		array (
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array (
			'field' => 'monto_genera',
			'title' => 'Monto Generador',
			'format' => 'number',
		)

	);


	public static $configuracion_cobranza = array (
		array (
			'field' => 'id_factura',
			'title' => 'Correlativo',
			'visible' => false
		),
		array (
			'field' => 'id_contrato',
			'title' => 'Código Asunto'
		),
		array (
			'field' => 'glosas_asunto',
			'title' => 'Glosa Asunto',
		),
		array (
			'field' => 'codigo_cliente',
			'title' => 'Código cliente',
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo Doc.'
		),
		array (
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
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
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array (
			'field' => 'total_facturado',
			'format' => 'number',
			'title' => 'Total Facturado'
		),
		array (
			'field' => 'total_pagado',
			'format' => 'number',
			'title' => 'Total Pagado'
		),
		array (
			'field' => 'username_generador',
			'title' => 'Código Generador'
		),

		array (
			'field' => 'nombre_generador',
			'title' => 'Nombre Generador'
		),

		array (
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array (
			'field' => 'monto_genera',
			'title' => 'Monto Generador',
			'format' => 'number',
		)
	);

	public static $configuracion_cobranza_aplicada = array (
		array (
			'field' => 'id_factura',
			'title' => 'Correlativo',
			'visible' => false
		),
		array (
			'field' => 'id_contrato',
			'title' => 'Código Asunto'
		),
		array (
			'field' => 'glosas_asunto',
			'title' => 'Glosa Asunto',
		),

		array (
			'field' => 'codigo_cliente',
			'title' => 'Código cliente',
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo Doc.'
		),
		array (
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
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
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),

		array (
			'field' => 'subtotal_cobro',
			'format' => 'number',
			'title' => 'Total Liquidación'
		),
		array (
			'field' => 'total_facturado',
			'format' => 'number',
			'title' => 'Total Facturado'
		),
		array (
			'field' => 'total_pagado',
			'format' => 'number',
			'title' => 'Total Pagado'
		),

		array (
			'field' => 'username_generador',
			'title' => 'Código Generador'
		),

		array (
			'field' => 'nombre_generador',
			'title' => 'Nombre Generador'
		),

		array (
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array (
			'field' => 'monto_genera',
			'title' => 'Monto Generador',
			'format' => 'number',
		),

		array (
			'field' => 'total_trabajado',
			'title' => 'Total Trabajado',
			'format' => 'number',
		),

		array (
			'field' => 'porcentaje_aporte_trabajos',
			'title' => 'Porcentaje Trabajado',
			'format' => 'number',
		),

		array (
			'field' => 'monto_aporte_pago_trabajos',
			'title' => 'Monto Aporte Pago Trabajos',
			'format' => 'number',
		),

	);

	private static $queries = array(
		'FACTURAS' => " SELECT factura.id_cobro,
						factura.id_factura,
						contrato.id_contrato AS id_contrato,
						cliente.glosa_cliente,
						factura.serie_documento_legal,
						factura.numero,
						prm_documento_legal.codigo AS tipo,
						factura.fecha,
						factura.total * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS total,
						moneda_filtro.simbolo,
						usuario.id_usuario id_usuario_generador,
						usuario.username username_generador,
						CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_generador,
						factura_generador.porcentaje_genera / 100.0 AS porcentaje_genera,
						(factura_generador.porcentaje_genera / 100.0) * factura.total * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_genera,
						factura.codigo_cliente,
						GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ';') AS glosas_asunto
					 FROM factura
					 JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
					 JOIN prm_moneda moneda_factura ON moneda_factura.id_moneda = factura.id_moneda
					 JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
					 JOIN cobro ON cobro.id_cobro = factura.id_cobro
					 JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
					 JOIN contrato ON contrato.id_contrato = cobro.id_contrato
					 LEFT JOIN cobro_asunto ON cobro_asunto.id_cobro = factura.id_cobro
					 LEFT JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
					 LEFT JOIN prm_moneda moneda_filtro ON moneda_filtro.id_moneda = :currency_id
					 LEFT JOIN factura_generador ON factura_generador.id_factura = factura.id_factura
						LEFT JOIN usuario ON usuario.id_usuario = factura_generador.id_usuario
					WHERE prm_estado_factura.id_estado != 5
						AND factura.fecha >= :period_from AND factura.fecha <= :period_to
					GROUP BY factura.numero, usuario.id_usuario",

		'PAGOS' => "SELECT factura.id_cobro AS id_cobro,
							ccfm2.id_factura,
							factura.id_contrato AS id_contrato,
							cliente.glosa_cliente,
							factura.serie_documento_legal,
							factura.numero,
							prm_documento_legal.codigo AS tipo,
							fp.fecha,
							cobro.monto_subtotal * (moneda_cobro.tipo_cambio) / (moneda_filtro.tipo_cambio) AS subtotal_cobro,
							factura.total * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS total_facturado,
							SUM(ccfmn.monto * (ccfmm.tipo_cambio) / (ccfmmf.tipo_cambio)) AS total_pagado,
							usuario.id_usuario id_usuario_generador,
							usuario.username username_generador,
							CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_generador,
							factura_generador.porcentaje_genera / 100.0 AS porcentaje_genera,
							(factura_generador.porcentaje_genera / 100.0) * SUM(ccfmn.monto * (ccfmm.tipo_cambio) / (ccfmmf.tipo_cambio)) AS monto_genera,
							factura.codigo_cliente,
							(SELECT GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ';')
								 FROM cobro_asunto
								 JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
								WHERE cobro_asunto.id_cobro = factura.id_cobro) AS glosas_asunto
				FROM factura_pago AS fp
				JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
				JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
				JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
				JOIN factura ON ccfm2.id_factura = factura.id_factura
				JOIN cobro ON cobro.id_cobro = factura.id_cobro
				JOIN cobro_moneda moneda_cobro ON cobro.id_moneda = moneda_cobro.id_moneda AND cobro.id_cobro = moneda_cobro.id_cobro
				JOIN cobro_moneda moneda_factura ON factura.id_moneda = moneda_factura.id_moneda AND factura.id_cobro = moneda_factura.id_cobro
				JOIN cobro_moneda moneda_filtro ON moneda_filtro.id_cobro = factura.id_cobro AND moneda_filtro.id_moneda = :currency_id

				JOIN cta_cte_fact_mvto_moneda AS ccfmm ON ccfmm.id_cta_cte_fact_mvto = ccfm.id_cta_cte_mvto AND ccfmm.id_moneda = ccfm.id_moneda
				JOIN cta_cte_fact_mvto_moneda AS ccfmmf ON ccfmmf.id_cta_cte_fact_mvto = ccfm.id_cta_cte_mvto AND ccfmmf.id_moneda = :currency_id

				JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
				JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado

				JOIN cliente ON cliente.codigo_cliente = factura.codigo_cliente

				LEFT JOIN factura_generador ON factura_generador.id_factura = factura.id_factura
				LEFT JOIN usuario ON usuario.id_usuario = factura_generador.id_usuario
			WHERE ccfm.anulado = 0 AND ccfm2.anulado = 0
				AND fp.fecha >= :period_from AND fp.fecha <= :period_to
				GROUP BY ccfm2.id_factura, usuario.id_usuario",

		'COBROS' => "SELECT factura.id_cobro
						FROM factura_pago AS fp
						JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
						JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
						JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
						JOIN factura ON ccfm2.id_factura = factura.id_factura
						JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
					 	JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
						JOIN documento ON documento.id_cobro = factura.id_cobro AND documento.tipo_doc = 'N'
						WHERE ccfm.anulado = 0 AND ccfm2.anulado = 0 AND factura.anulado = 0
						AND fp.fecha >= :period_from AND fp.fecha <= :period_to AND :currency_id = :currency_id",

		'TRABAJOS' => "SELECT trabajo.id_cobro,
					usuario.id_usuario,
					usuario.username,
					CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_usuario,
					moneda_filtro.tipo_cambio AS tipo_cambio,
					SUM(trabajo.monto_cobrado) * (moneda_cobro.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_trabajos,
					prm_moneda.simbolo
				FROM trabajo
				JOIN cobro_moneda AS moneda_cobro
				  ON moneda_cobro.id_cobro = trabajo.id_cobro
				JOIN cobro_moneda AS moneda_filtro
				  ON moneda_filtro.id_cobro = trabajo.id_cobro
				 AND moneda_filtro.id_moneda = :currency_id
				 AND moneda_cobro.id_moneda = trabajo.id_moneda
				JOIN prm_moneda ON moneda_filtro.id_moneda = prm_moneda.id_moneda
				JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
 			   WHERE trabajo.id_cobro IN (:charges)
 				 AND trabajo.cobrable = 1
			GROUP BY trabajo.id_cobro, usuario.id_usuario"
	);

	function FacturaProduccion($sesion, $report_code = 'FACTURA_PRODUCCION') {
		$this->sesion = $sesion;
		$this->report_code = $report_code;
	}

	public function QueryReporte() {
		if ($this->report_code == 'FACTURA_PRODUCCION') {
			return FacturaProduccion::$queries['FACTURAS'];
		}
		if ($this->report_code == 'FACTURA_COBRANZA') {
			return FacturaProduccion::$queries['PAGOS'];
		}
		if ($this->report_code == 'FACTURA_COBRANZA_APLICADA') {
			return FacturaProduccion::$queries['COBROS'];
		}
	}

	public function ReportData($query, $params, $fetch_options = PDO::FETCH_ASSOC) {
		$Statement = $this->sesion->pdodbh->prepare($query);
		foreach ($params as $key => $item) {
			if (strpos($key, 'period_') !== false) {
				$Statement->bindValue($key, Utiles::fecha2sql($item));
			} else {
				$Statement->bindValue($key, $item, PDO::PARAM_BOOL);
			}
		}
		$Statement->execute();
		return $Statement->fetchAll($fetch_options);
	}

	public function AddRowToResults($id_cobro, $row, &$results) {
		$row['id_cobro'] = $id_cobro;
		array_push($results, $row);
	}

	public function ProcessReport($results, $params) {
		if ($this->report_code != 'FACTURA_COBRANZA_APLICADA') {
			return $results;
		}
		$charges_array = array();
		if (isset($results) && !empty($results)) {
			$charges = array_keys($results);
			$charges = empty($charges) ? '0' : implode(', ', $charges);
		} else {
			$charges = '0';
		}

		$query_trabajos = str_replace(":charges", $charges, FacturaProduccion::$queries['TRABAJOS']);
		$trabajos = $this->ReportData($query_trabajos, array('currency_id' => $params['currency_id']), PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
		$pagos = $this->ReportData(FacturaProduccion::$queries['PAGOS'], $params, PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

		$report_results = array();
		foreach ($pagos as $id_cobro => $facturas_generador) {
			$trabajos_cobro = $trabajos[$id_cobro];
			if (!is_null($trabajos_cobro) && !empty($trabajos_cobro)) {
				foreach ($facturas_generador as $factura) {
					$trabajo_el_generador = false;
					foreach ($trabajos_cobro as $trabajo) {
						if (!is_null($factura['id_usuario_generador']) && $factura['id_usuario_generador'] == $trabajo['id_usuario']) {
							$trabajo_el_generador = true;
						}
		 				#Proratear el monto del trabajo en el monto del cobro  (trabajo es 50% del cobro (ej))
						#Proratear el monto de la factura en el monto del cobro (factura corresponde al 20% del cobro)
						#Obtener el % de aporte del usuario (aportó con el 10% a la factura)
						#
						$aporte_factura = $factura['total_facturado'] / $factura['subtotal_cobro'];
						$aporte_trabajo = $trabajo['monto_trabajos'] / $factura['subtotal_cobro'];
						$aporte = $aporte_factura * $aporte_trabajo;
						#echo "Monto trabajos {$trabajo['monto_trabajos']}   Monto Cobro {$factura['subtotal_cobro']}  		Aporte factura: $aporte_factura 		aporte trabajo 		$aporte_trabajo	 		= $aporte<br/>";
						$factura["id_usuario_generador"] = $trabajo['id_usuario'];
						$factura["username_generador"] = $trabajo['username'];
						$factura["nombre_generador"] = $trabajo['nombre_usuario'];
						$factura["total_trabajado"] = $trabajo['monto_trabajos'];
						$factura["porcentaje_aporte_trabajos"] = $aporte;
						$factura["monto_aporte_pago_trabajos"] = $factura['total_pagado'] * $factura["porcentaje_aporte_trabajos"] ;

						$this->AddRowToResults($id_cobro, $factura, $report_results);
					}
					if (!$trabajo_el_generador) {
						$this->AddRowToResults($id_cobro, $factura, $report_results);
					}
				}
			} else {
				foreach ($facturas_generador as $factura) {
					$this->AddRowToResults($id_cobro, $factura, $report_results);
				}
			}
		}

		return $report_results;
	}


	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadReport($results, $writer_type= 'Json') {
		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration($this->report_code);
		$SimpleReport->LoadResults($results);
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, $writer_type);
		$writer->save(__('Facturas'));
	}


}