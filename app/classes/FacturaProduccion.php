<?php

require_once dirname(__FILE__) . '/../conf.php';

class FacturaProduccion {

	public static $configuracion_reporte = array (
		array (
			'field' => 'id_factura',
			'title' => 'Correlativo'
		),
		array (
			'field' => 'id_contrato',
			'title' => 'Contrato'
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array (
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
		),
		array (
			'field' => 'numero',
			'format' => 'number',
			'title' => 'N° Documento'
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo'
		),
		array (
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array (
			'field' => 'total',
			'format' => 'number',
			'title' => 'Total'
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
			'field' => 'username_generador',
			'title' => 'Generador'
		),

		array (
			'field' => 'nombre_generador',
			'title' => 'Nombre'
		),

		array (
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array (
			'field' => 'codigo_cliente',
			'title' => 'Codigo cliente',
		),

		array (
			'field' => 'glosas_asunto',
			'title' => 'Asuntos',
		),

	);


	public static $configuracion_cobranza = array (
		array (
			'field' => 'id_factura',
			'title' => 'Correlativo'
		),
		array (
			'field' => 'id_contrato',
			'title' => 'Contrato'
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array (
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
		),
		array (
			'field' => 'numero',
			'format' => 'number',
			'title' => 'N° Documento'
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo'
		),
		array (
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array (
			'field' => 'total_facturado',
			'format' => 'number',
			'title' => 'Total'
		),
		array
(			'field' => 'total_pagado',
			'format' => 'number',
			'title' => 'Total'
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
			'field' => 'username_generador',
			'title' => 'Generador'
		),

		array (
			'field' => 'nombre_generador',
			'title' => 'Nombre'
		),

		array (
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array (
			'field' => 'codigo_cliente',
			'title' => 'Codigo cliente',
		),

		array (
			'field' => 'glosas_asunto',
			'title' => 'Asuntos',
		),

	);

	public static $configuracion_cobranza_aplicada = array (
		array (
			'field' => 'id_factura',
			'title' => 'Correlativo'
		),
		array (
			'field' => 'id_contrato',
			'title' => 'Contrato'
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array (
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
		),
		array (
			'field' => 'numero',
			'format' => 'number',
			'title' => 'N° Documento'
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo'
		),
		array (
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array (
			'field' => 'total',
			'format' => 'number',
			'title' => 'Total'
		),
		array (
			'field' => 'simbolo',
			'title' => 'Símbolo Moneda'
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
			'field' => 'username_generador',
			'title' => 'Generador'
		),

		array (
			'field' => 'nombre_generador',
			'title' => 'Nombre'
		),

		array (
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array (
			'field' => 'porcentaje_aporte',
			'title' => 'Porcentaje Aporte',
			'format' => 'number',
		),

		array (
			'field' => 'monto_aporte',
			'title' => 'Monto Aporte',
			'format' => 'number',
		),

		array (
			'field' => 'codigo_cliente',
			'title' => 'Codigo cliente',
		),

		array (
			'field' => 'glosas_asunto',
			'title' => 'Asuntos',
		),

	);

	function FacturaProduccion($sesion, $report_code = 'FACTURA_PRODUCCION') {
		$this->sesion = $sesion;
		$this->report_code = $report_code;
	}

	public function QueryReporte() {
		if ($this->report_code == 'FACTURA_PRODUCCION') {
			$query = " SELECT factura.id_factura,
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
						factura_generador.porcentaje_genera,
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
					GROUP BY factura.numero";
		}

		if ($this->report_code == 'FACTURA_COBRANZA') {
			$query = "SELECT ccfm2.id_factura,
							factura.id_contrato AS id_contrato,
							factura.id_cobro AS id_cobro,
							cliente.glosa_cliente,
							factura.serie_documento_legal,
							factura.numero,
							prm_documento_legal.codigo AS tipo,
							fp.fecha,
							factura.total * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS total_facturado,
							SUM(ccfmn.monto * (ccfmm.tipo_cambio) / (ccfmmf.tipo_cambio)) AS total_pagado,
							usuario.id_usuario id_usuario_generador,
							usuario.username username_generador,
							CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_generador,
							factura_generador.porcentaje_genera,
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
				GROUP BY ccfm2.id_factura, usuario.id_usuario";

		}

		if ($this->report_code == 'FACTURA_COBRANZA_APLICADA') {
			$query = "SELECT factura.id_cobro,
						ccfm2.id_factura,
						factura.id_contrato AS id_contrato,
				   	cliente.glosa_cliente,
						factura.serie_documento_legal,
				   	factura.numero,
				   	prm_documento_legal.codigo AS tipo,
			         fp.fecha,
			         documento.monto * (cobro_moneda.tipo_cambio) /  (moneda_filtro.tipo_cambio) AS total_cobrado,
			         factura.total * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS total_facturado,
			         factura.total * (moneda_factura.tipo_cambio) / documento.monto * (cobro_moneda.tipo_cambio) AS aporte_factura_cobro,
			         SUM(ccfmn.monto * (ccfmm.tipo_cambio) / (ccfmmf.tipo_cambio)) AS total_pagado,
						usuario.id_usuario,
						usuario.username,
						CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_usuario,
						factura_generador.porcentaje_genera / 100.0,
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

				JOIN cobro_moneda moneda_factura ON factura.id_moneda = moneda_factura.id_moneda AND factura.id_cobro = moneda_factura.id_cobro
				JOIN cobro_moneda moneda_filtro ON moneda_filtro.id_cobro = factura.id_cobro AND moneda_filtro.id_moneda = :currency_id

				JOIN cta_cte_fact_mvto_moneda AS ccfmm ON ccfmm.id_cta_cte_fact_mvto = ccfm.id_cta_cte_mvto AND ccfmm.id_moneda = ccfm.id_moneda
				JOIN cta_cte_fact_mvto_moneda AS ccfmmf ON ccfmmf.id_cta_cte_fact_mvto = ccfm.id_cta_cte_mvto AND ccfmmf.id_moneda = :currency_id

				JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
			 	JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
				JOIN documento ON documento.id_cobro = factura.id_cobro AND documento.tipo_doc = 'N'
			 	JOIN cobro_moneda  ON cobro_moneda.id_cobro = documento.id_cobro AND cobro_moneda.id_moneda = documento.id_moneda
			 	JOIN cliente ON cliente.codigo_cliente = factura.codigo_cliente

				LEFT JOIN factura_generador ON factura_generador.id_factura = factura.id_factura
				LEFT JOIN usuario ON usuario.id_usuario = factura_generador.id_usuario

				WHERE ccfm.anulado = 0 AND ccfm2.anulado = 0
				AND fp.fecha >= :period_from AND fp.fecha <= :period_to
				GROUP BY ccfm2.id_factura, usuario.id_usuario";
		}

		return $query;
	}

	public function ReportData($query, $params, $fetch_options = PDO::FETCH_ASSOC) {
		if ($this->report_code == 'FACTURA_COBRANZA_APLICADA') {
			$fetch_options = PDO::FETCH_ASSOC | PDO::FETCH_GROUP;
		}
		$Statement = $this->sesion->pdodbh->prepare($query);
		foreach ($params as $key => $item) {
			if (strpos($key, 'period_') !== false) {
				$Statement->bindParam($key, Utiles::fecha2sql($item));
			} else {
				$Statement->bindParam($key, $item);
			}
		}
		$Statement->execute();
		return $Statement->fetchAll($fetch_options);
	}

	public function ProcessReport($results, $params) {
		if ($this->report_code != 'FACTURA_COBRANZA_APLICADA') {
			return $results;
		}

		if (isset($results) && !empty($results)) {
			$charges = array_keys($results);
			$charges = empty($charges) ? '0' : implode(', ', $charges);
		} else {
			$charges = '0';
		}

		$query = "SELECT 	trabajo.id_cobro,
					 usuario.id_usuario,
					 usuario.username,
					 CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_usuario,
					 SUM(Time_to_sec(trabajo.duracion_cobrada) / 3600 * trabajo.tarifa_hh_estandar) *
						(moneda_cobro.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_cobrado,
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
		  WHERE trabajo.id_cobro IN ($charges)
			  AND trabajo.cobrable = 1
		  GROUP BY usuario.id_usuario, trabajo.id_cobro";

		$jobs = $this->ReportData($query, array('currency_id' => $params['currency_id']));
		/*foreach ($user_jobs as $id_cobro => $jobs_cobro) {
			$paymentos_cobro = $results[$id_cobro];
			foreach ($jobs_cobro as $job) {
				$invoice_id = 0;
				foreach ($paymentos_cobro as $payment) {
					if ($invoice_id != $payment['id_factura']) {

					}
				}
			}
		}
		*/
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