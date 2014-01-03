<?php

require_once dirname(__FILE__) . '/../conf.php';

class FacturaProduccion {

	public static $configuracion_reporte = array(
		array(
			'field' => 'id_factura',
			'title' => 'Correlativo',
			'visible' => false
		),
		array(
			'field' => 'id_contrato',
			'title' => 'Código Asunto'
		),
		array(
			'field' => 'glosas_asunto',
			'title' => 'Glosa Asunto',
		),
		array(
			'field' => 'codigo_cliente',
			'title' => 'Codigo cliente',
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array(
			'field' => 'tipo',
			'title' => 'Tipo Doc.'
		),
		array(
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
		),
		array(
			'field' => 'numero',
			'format' => 'number',
			'title' => 'Nº Documento',
			'extras' => array(
				'subtotal' => false
			)
		),
		array(
			'field' => 'estado_factura',
			'title' => 'Estado'
		),
		array(
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array(
			'field' => 'moneda_original',
			'title' => 'Moneda Original'
		),
		array(
			'field' => 'subtotal_facturado_original',
			'format' => 'number',
			'title' => 'SubTotal Facturado Original'
		),
		array(
			'field' => 'impuesto_facturado_original',
			'format' => 'number',
			'title' => 'Impuesto'
		),
		array(
			'field' => 'total_facturado_original',
			'format' => 'number',
			'title' => 'Total Facturado Original'
		),
		array(
			'field' => 'monto_detraccion_original',
			'format' => 'number',
			'title' => 'Monto Detracción Original',
			'visible' => false
		),
		array(
			'field' => 'total_liquido_original',
			'format' => 'number',
			'title' => 'Total Líquido Original',
			'visible' => false
		),
		array(
			'field' => 'moneda',
			'title' => 'Moneda'
		),
		array(
			'field' => 'total',
			'format' => 'number',
			'title' => 'Total Facturado'
		),
		array(
			'field' => 'monto_detraccion',
			'format' => 'number',
			'title' => 'Monto Detracción',
			'visible' => false
		),
		array(
			'field' => 'total_liquido',
			'format' => 'number',
			'title' => 'Total Líquido',
			'visible' => false
		),
		array(
			'field' => 'tipo_cambio',
			'format' => 'number',
			'title' => 'Tipo de Cambio'
		),
		array(
			'field' => 'username_generador',
			'title' => 'Código Generador'
		),
		array(
			'field' => 'area_usuario',
			'title' => 'Area Usuario'
		),
		array(
			'field' => 'nombre_generador',
			'title' => 'Nombre Generador'
		),

		array(
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array(
			'field' => 'monto_genera',
			'title' => 'Monto Generador',
			'format' => 'number',
		)

	);


	public static $configuracion_cobranza = array(
		array(
			'field' => 'id_factura',
			'title' => 'Correlativo',
			'visible' => false
		),
		array(
			'field' => 'id_contrato',
			'title' => 'Código Asunto'
		),
		array(
			'field' => 'glosas_asunto',
			'title' => 'Glosa Asunto',
		),
		array(
			'field' => 'codigo_cliente',
			'title' => 'Código cliente',
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array(
			'field' => 'tipo',
			'title' => 'Tipo Doc.'
		),
		array(
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
		),
		array(
			'field' => 'numero',
			'format' => 'number',
			'title' => 'N° Documento',
			'extras' => array(
				'subtotal' => false
			)
		),
		array(
			'field' => 'estado_factura',
			'title' => 'Estado'
		),
		array(
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array(
			'field' => 'fecha_pago',
			'format' => 'date',
			'title' => 'Fecha Pago'
		),
		array(
			'field' => 'moneda_original',
			'title' => 'Moneda Original'
		),
		array(
			'field' => 'subtotal_facturado_original',
			'format' => 'number',
			'title' => 'SubTotal Facturado Original'
		),
		array(
			'field' => 'impuesto_facturado_original',
			'format' => 'number',
			'title' => 'Impuesto'
		),
		array(
			'field' => 'total_facturado_original',
			'format' => 'number',
			'title' => 'Total Facturado Original'
		),
		array(
			'field' => 'monto_detraccion_original',
			'format' => 'number',
			'title' => 'Monto Detracción Original',
			'visible' => false
		),
		array(
			'field' => 'total_liquido_original',
			'format' => 'number',
			'title' => 'Total Líquido Original',
			'visible' => false
		),
		array(
			'field' => 'moneda',
			'title' => 'Moneda'
		),
		array(
			'field' => 'total_facturado',
			'format' => 'number',
			'title' => 'Total Facturado'
		),
		array(
			'field' => 'subtotal_facturado',
			'format' => 'number',
			'title' => 'Subtotal Facturado'
		),
		array(
			'field' => 'monto_detraccion',
			'format' => 'number',
			'title' => 'Monto Detracción',
			'visible' => false
		),
		array(
			'field' => 'total_liquido',
			'format' => 'number',
			'title' => 'Total Líquido',
			'visible' => false
		),
		array(
			'field' => 'tipo_cambio',
			'format' => 'number',
			'title' => 'Tipo de Cambio'
		),
		array(
			'field' => 'total_pagado',
			'format' => 'number',
			'title' => 'Total Pagado'
		),
		array(
			'field' => 'username_generador',
			'title' => 'Código Generador'
		),
		array(
			'field' => 'area_usuario',
			'title' => 'Area Usuario'
		),
		array(
			'field' => 'nombre_generador',
			'title' => 'Nombre Generador'
		),

		array(
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array(
			'field' => 'monto_genera',
			'title' => 'Monto Generador',
			'format' => 'number',
		)
	);

	public static $configuracion_cobranza_aplicada = array(
		array(
			'field' => 'id_factura',
			'title' => 'Correlativo',
			'visible' => false
		),
		array(
			'field' => 'id_contrato',
			'title' => 'Código Asunto'
		),
		array(
			'field' => 'glosas_asunto',
			'title' => 'Glosa Asunto',
		),

		array(
			'field' => 'codigo_cliente',
			'title' => 'Código cliente',
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array(
			'field' => 'tipo',
			'title' => 'Tipo Doc.'
		),
		array(
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
		),
		array(
			'field' => 'numero',
			'format' => 'number',
			'title' => 'N° Documento',
			'extras' => array(
				'subtotal' => false
			)
		),
		array(
			'field' => 'estado_factura',
			'title' => 'Estado'
		),
		array(
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array(
			'field' => 'fecha_pago',
			'format' => 'date',
			'title' => 'Fecha Pago'
		),
		array(
			'field' => 'moneda_original',
			'title' => 'Moneda Original'
		),
		array(
			'field' => 'subtotal_facturado_original',
			'format' => 'number',
			'title' => 'SubTotal Facturado Original'
		),
		array(
			'field' => 'impuesto_facturado_original',
			'format' => 'number',
			'title' => 'Impuesto'
		),
		array(
			'field' => 'total_facturado_original',
			'format' => 'number',
			'title' => 'Total Facturado Original'
		),
		array(
			'field' => 'monto_detraccion_original',
			'format' => 'number',
			'title' => 'Monto Detracción Original',
			'visible' => false
		),
		array(
			'field' => 'total_liquido_original',
			'format' => 'number',
			'title' => 'Total Líquido Original',
			'visible' => false
		),
		array(
			'field' => 'moneda',
			'title' => 'Moneda'
		),
		array(
			'field' => 'subtotal_cobro',
			'format' => 'number',
			'title' => 'Total Liquidación'
		),
		array(
			'field' => 'total_facturado',
			'format' => 'number',
			'title' => 'Total Facturado'
		),
		array(
			'field' => 'subtotal_facturado',
			'format' => 'number',
			'title' => 'Subtotal Facturado'
		),
		array(
			'field' => 'monto_detraccion',
			'format' => 'number',
			'title' => 'Monto Detracción',
			'visible' => false
		),
		array(
			'field' => 'total_liquido',
			'format' => 'number',
			'title' => 'Total Líquido',
			'visible' => false
		),
		array(
			'field' => 'tipo_cambio',
			'format' => 'number',
			'title' => 'Tipo de Cambio'
		),
		array(
			'field' => 'total_pagado',
			'format' => 'number',
			'title' => 'Total Pagado'
		),
		array(
			'field' => 'username_generador',
			'title' => 'Código Generador'
		),
		array(
			'field' => 'area_usuario',
			'title' => 'Area Usuario'
		),
		array(
			'field' => 'nombre_generador',
			'title' => 'Nombre Generador'
		),

		array(
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array(
			'field' => 'producido_total',
			'title' => 'Producido Total',
			'format' => 'number',
		),

		array(
			'field' => 'horas_trabajadas',
			'title' => 'Horas Trabajadas',
			'format' => 'number',
		),

		array(
			'field' => 'producido_abogado',
			'title' => 'Producido Abogado',
			'format' => 'number',
		),

		array(
			'field' => 'monto_aporte',
			'title' => 'Monto Aporte',
			'format' => 'number',
		),

	);

public static $configuracion_gastos = array(
		array(
			'field' => 'id_movimiento',
			'title' => 'N°',
			'visible' => false
		),
		array(
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha',
		),
		array(
			'field' => 'codigo_cliente',
			'title' => 'Código Cliente',
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente',
		),
		array(
			'field' => 'codigo_asunto',
			'title' => 'Código Asunto',
		),
		array(
			'field' => 'glosa_asunto',
			'title' => 'Asunto',
		),
		array(
			'field' => 'encargado_comercial',
			'title' => 'Encargado Comercial',
		),
		array(
			'field' => 'usuario_ingresa',
			'title' => 'Ingresado por',
		),
		array(
			'field' => 'usuario_ordena',
			'title' => 'Ordenado por',
		),
		array(
			'field' => 'tipo',
			'title' => 'Tipo',
		),
		array(
			'field' => 'descripcion',
			'title' => 'Descripción',
		),
		array(
			'field' => 'simbolo',
			'title' => 'Símbolo Moneda',
		),
		array(
			'field' => 'egreso',
			'format' => 'number',
			'title' => 'Egreso',
			'extras' =>
			array(
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array(
			'field' => 'ingreso',
			'format' => 'number',
			'title' => 'Ingreso',
			'extras' =>
			array(
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array(
			'field' => 'monto_cobrable',
			'format' => 'number',
			'title' => 'Monto Cobrable',
			'extras' =>
			array(
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array(
			'field' => 'con_impuesto',
			'title' => 'Con Impuesto',
		),
		array(
			'field' => 'id_cobro',
			'title' => 'N° Liquidación',
		),
		array(
			'field' => 'estado_cobro',
			'title' => 'Estado Liquidación',
		),
		array(
			'field' => 'cobrable',
			'title' => 'Cobrable',
		),
		array(
			'field' => 'numero_documento',
			'title' => 'N° Documento',
		),
		array(
			'field' => 'numero_ot',
			'title' => 'N° Orden Trabajo',
			'visible' => false
		),
		array(
			'field' => 'rut_proveedor',
			'title' => 'RUT Proveedor',
		),
		array(
			'field' => 'nombre_proveedor',
			'title' => 'Proveedor',
		),
		array(
			'field' => 'estado_pago',
			'title' => 'Estado Pago',
			'visible' => false
		),
		array(
			'field' => 'tipo_documento_asociado',
			'title' => 'Tipo Documento Asociado',
		),
		array(
			'field' => 'fecha_documento_asociado',
			'title' => 'Fecha Documento Asociado',
		),
		array(
			'field' => 'codigo_documento_asociado',
			'title' => 'N° Documento Asociado',
		),
		array(
			'field' => 'username_generador',
			'title' => 'Código Generador'
		),
		array(
			'field' => 'area_usuario',
			'title' => 'Area Generador'
		),
		array(
			'field' => 'nombre_generador',
			'title' => 'Nombre Generador'
		),

		array(
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array(
			'field' => 'monto_genera',
			'title' => 'Monto Generador',
			'format' => 'number',
		)
	);

	private static $queries = array(
		'FACTURAS' => " SELECT factura.id_cobro,
						factura.id_factura,
						contrato.id_contrato AS id_contrato,
						cliente.glosa_cliente,
						factura.serie_documento_legal,
						factura.numero,
						prm_estado_factura.codigo estado_factura,
						prm_documento_legal.codigo AS tipo,
						factura.fecha,
						moneda_factura.simbolo AS moneda_original,
						factura.total * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS total,
						factura.subtotal AS subtotal_facturado_original,
						factura.iva AS impuesto_facturado_original,
						factura.total AS total_facturado_original,
						(factura.total * 0.12)  * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_detraccion,
						(factura.subtotal - (factura.total * 0.12))  * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS total_liquido,
						factura.total * 0.12 AS monto_detraccion_original,
						factura.subtotal - (factura.total * 0.12) AS total_liquido_original,
						(moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS tipo_cambio,
						moneda_filtro.simbolo moneda,
						usuario.id_usuario id_usuario_generador,

						usuario.username username_generador,
						CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_generador,
						prm_area_usuario.glosa AS area_usuario,
						factura_generador.porcentaje_genera / 100.0 AS porcentaje_genera,
						(factura_generador.porcentaje_genera / 100.0) * factura.subtotal * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_genera,
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
						LEFT JOIN prm_area_usuario ON prm_area_usuario.id = usuario.id_area_usuario
					WHERE factura.fecha >= :period_from AND factura.fecha <= :period_to
					GROUP BY factura.numero, usuario.id_usuario",


		'PAGOS' => "SELECT factura.id_cobro AS id_cobro,
							ccfm2.id_factura,
							factura.id_contrato AS id_contrato,
							cliente.glosa_cliente,
							factura.serie_documento_legal,
							factura.numero,
							prm_estado_factura.codigo estado_factura,
							prm_documento_legal.codigo AS tipo,
							factura.fecha,
							fp.fecha as fecha_pago,
							prm_moneda.simbolo AS moneda_original,
							cobro.monto_subtotal * (moneda_cobro.tipo_cambio) / (moneda_filtro.tipo_cambio) AS subtotal_cobro,
							factura.total * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS total_facturado,
							factura.subtotal * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS subtotal_facturado,
							factura.subtotal AS subtotal_facturado_original,
							factura.iva AS impuesto_facturado_original,
							factura.total AS total_facturado_original,
							(factura.total * 0.12) * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_detraccion,
							(factura.subtotal - (factura.total * 0.12)) * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS total_liquido,
							factura.total * 0.12 AS monto_detraccion_original,
							factura.subtotal - (factura.total * 0.12) AS total_liquido_original,
							(moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS tipo_cambio,
							prm_moneda_filtro.simbolo moneda,
							SUM(ccfmn.monto * (ccfmm.tipo_cambio) / (ccfmmf.tipo_cambio)) AS total_pagado,
							usuario.id_usuario id_usuario_generador,
							usuario.username username_generador,
							CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_generador,
							prm_area_usuario.glosa AS area_usuario,
							factura_generador.porcentaje_genera / 100.0 AS porcentaje_genera,
							(factura_generador.porcentaje_genera / 100.0) * factura.subtotal * (moneda_factura.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_genera,
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
				JOIN prm_moneda ON moneda_factura.id_moneda = prm_moneda.id_moneda
				JOIN cobro_moneda moneda_filtro ON moneda_filtro.id_cobro = factura.id_cobro AND moneda_filtro.id_moneda = :currency_id
				JOIN prm_moneda prm_moneda_filtro  ON prm_moneda_filtro.id_moneda = moneda_filtro.id_moneda
				JOIN cta_cte_fact_mvto_moneda AS ccfmm ON ccfmm.id_cta_cte_fact_mvto = ccfm.id_cta_cte_mvto AND ccfmm.id_moneda = ccfm.id_moneda
				JOIN cta_cte_fact_mvto_moneda AS ccfmmf ON ccfmmf.id_cta_cte_fact_mvto = ccfm.id_cta_cte_mvto AND ccfmmf.id_moneda = :currency_id

				JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
				JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado

				JOIN cliente ON cliente.codigo_cliente = factura.codigo_cliente
				LEFT JOIN factura_generador ON factura_generador.id_factura = factura.id_factura
				LEFT JOIN usuario ON usuario.id_usuario = factura_generador.id_usuario
				LEFT JOIN prm_area_usuario ON prm_area_usuario.id = usuario.id_area_usuario
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

		'TRABAJOS_COBRO' => "SELECT trabajo.id_cobro,
					moneda_filtro.tipo_cambio AS tipo_cambio,
					SUM(TIME_TO_SEC(trabajo.duracion_cobrada)/3600) AS duracion,
					SUM(TIME_TO_SEC(trabajo.duracion_cobrada)/3600*trabajo.tarifa_hh)  * (moneda_cobro.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_trabajos,
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
				LEFT JOIN prm_area_usuario ON prm_area_usuario.id = usuario.id_area_usuario
 			   WHERE trabajo.id_cobro IN (:charges)
 				 AND trabajo.cobrable = 1
			GROUP BY trabajo.id_cobro",

		'TRABAJOS' => "SELECT trabajo.id_cobro,
					usuario.id_usuario,
					usuario.username,
					CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_usuario,
					prm_area_usuario.glosa AS area_usuario,
					moneda_filtro.tipo_cambio AS tipo_cambio,
					SUM(TIME_TO_SEC(trabajo.duracion_cobrada)/3600) AS duracion,
					SUM(TIME_TO_SEC(trabajo.duracion_cobrada)/3600*trabajo.tarifa_hh)  * (moneda_cobro.tipo_cambio) / (moneda_filtro.tipo_cambio) AS monto_trabajos,
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
				LEFT JOIN prm_area_usuario ON prm_area_usuario.id = usuario.id_area_usuario
 			   WHERE trabajo.id_cobro IN (:charges)
 				 AND trabajo.cobrable = 1
			GROUP BY trabajo.id_cobro, usuario.id_usuario"
	);

	function FacturaProduccion($sesion, $report_code = 'FACTURA_PRODUCCION') {
		$this->sesion = $sesion;
		$this->report_code = $report_code;
	}

	public function QueryReporte() {
		switch ($this->report_code) {
			case 'FACTURA_PRODUCCION':
				return FacturaProduccion::$queries['FACTURAS'];

			case 'FACTURA_COBRANZA':
				return FacturaProduccion::$queries['PAGOS'];

			case 'FACTURA_COBRANZA_APLICADA':
				return FacturaProduccion::$queries['COBROS'];

			case 'GASTOS_NO_COBRABLES':
				$Gasto = new Gasto($this->sesion);
				$where = array(
					'cobrable' => '0',
					'fecha1' => ':period_from',
					'fecha2' => ':period_to',
					'moneda_gasto' => ':currency_id'
				);

				$col_select = " , generador.username AS username_generador
								, CONCAT(generador.apellido1, ' ', generador.apellido2, ', ', generador.nombre) AS nombre_generador
								, area_generador.glosa AS area_usuario
								, contrato_generador.porcentaje_genera / 100.0 AS porcentaje_genera
								, (contrato_generador.porcentaje_genera / 100.0) * IFNULL(cta_corriente.egreso, 0) AS monto_genera ";

				$join_extra = " LEFT JOIN contrato_generador ON contrato.id_contrato = contrato_generador.id_contrato
						LEFT JOIN usuario AS generador ON generador.id_usuario = contrato_generador.id_usuario
						LEFT JOIN prm_area_usuario AS area_generador ON area_generador.id = generador.id_area_usuario ";

				return $Gasto->SearchQuery($this->sesion, $Gasto->WhereQuery($where), $col_select, $join_extra);
		}
	}

	public function ReportData($query, $params, $fetch_options = PDO::FETCH_ASSOC) {
		#echo $query;
		#exit();
		$Statement = $this->sesion->pdodbh->prepare($query);
		foreach ($params as $key => $item) {
			if (strpos($key, 'period_') !== false) {
				$item = Utiles::fecha2sql($item);
			}
			$Statement->bindValue($key, $item);
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
			foreach ($results as $res) {
				array_push($charges_array, $res['id_cobro']);
			}
			$charges = empty($charges_array) ? '0' : implode(', ', $charges_array);
		} else {
			$charges = '0';
		}

		$trab = 1.0;
		$gen = 0.0;

		$query_trabajos = str_replace(":charges", $charges, FacturaProduccion::$queries['TRABAJOS']);
		$query_totales = str_replace(":charges", $charges, FacturaProduccion::$queries['TRABAJOS_COBRO']);

		$trabajos = $this->ReportData($query_trabajos, array('currency_id' => $params['currency_id']), PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
		$trabajos_totales = $this->ReportData($query_totales, array('currency_id' => $params['currency_id']), PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
		$pagos = $this->ReportData(FacturaProduccion::$queries['PAGOS'], $params, PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

		$report_results = array();
		error_reporting(E_ALL ^ E_NOTICE);
		foreach ($pagos as $id_cobro => $facturas_generador) {
			$trabajos_cobro = $trabajos[$id_cobro];
			$total_trabajos = $trabajos_totales[$id_cobro][0];
			$producido_total = $total_trabajos["monto_trabajos"];

			if (!is_null($trabajos_cobro) && !empty($trabajos_cobro)) {
				$generadores = array();
				$trabajadores = array();
				# Necesito diferenciar previamente los generadores (si los hay)
				foreach ($facturas_generador as $factura) {
					if (!is_null($factura['id_usuario_generador'])) {
						$numero = $factura['numero'];
						$usuario = $factura['id_usuario_generador'];
						$aporte_factura = $factura['subtotal_facturado'] / $factura['subtotal_cobro'];
						$factura["producido_total"] = $producido_total * $aporte_factura;
						$factura["producido_abogado"] = 0;
						if ($factura["producido_total"] > ($factura['subtotal_facturado'] * $trab)) {
							$factura["monto_aporte"] = $factura["porcentaje_genera"] * $factura['subtotal_facturado'] * $gen
														 + $factura["producido_abogado"] * $trab * ($factura['subtotal_facturado'] / $factura["producido_total"]);
						} else {
							$factura["monto_aporte"] = $factura["producido_abogado"]
														 + $factura["porcentaje_genera"] * ($factura['subtotal_facturado'] - $factura["producido_total"]);
						}
						$generadores[$numero][$usuario] = $factura;
					}
				}
				foreach ($facturas_generador as $factura) {
					foreach ($trabajos_cobro as $trabajo) {
						$numero = $factura['numero'];
						$usuario = $trabajo['id_usuario'];
						# Proratear el monto del trabajo en el monto del cobro  (trabajo es 50% del cobro (ej))
						# Proratear el monto de la factura en el monto del cobro (factura corresponde al 20% del cobro)
						# Obtener el % de aporte del usuario (aportó con el 10% a la factura)
						$aporte_factura = $factura['subtotal_facturado'] / $factura['subtotal_cobro'];
						$producido_total_factura = $producido_total * $aporte_factura;

						if ($generadores && $generadores[$numero] && $generadores[$numero][$usuario]) {
							# Ya existe como generador No lo vuelvo a agregar a esta factura ya que se agregará al final
							$elgenerador = $generadores[$numero][$usuario];
							$elgenerador["producido_total"] = $producido_total_factura;
							$elgenerador["horas_trabajadas"] = $trabajo['duracion'] * $aporte_factura;
							$elgenerador["producido_abogado"] = $trabajo['monto_trabajos'] * $aporte_factura;

							if ($elgenerador["producido_total"] > ($factura['subtotal_facturado'] * $trab)) {
								$elgenerador["monto_aporte"] = $elgenerador["porcentaje_genera"] * $factura['subtotal_facturado'] * $gen
															 + $elgenerador["producido_abogado"] * $trab * ($factura['subtotal_facturado'] / $elgenerador["producido_total"]);
							} else {
								$elgenerador["monto_aporte"] = $elgenerador["producido_abogado"]
															 + $elgenerador["porcentaje_genera"] * ($factura['subtotal_facturado'] - $elgenerador["producido_total"]);
							}

							$generadores[$numero][$usuario] = $elgenerador;
						} else {
							# Le copio los datos de la factura y calculo los correspondientes
							# Lo asigno a un array asociativo por si lo repito (misma factura más de 1 generador)
							$eltrabajador = $factura;
							$eltrabajador["id_usuario_generador"] = $trabajo['id_usuario'];
							$eltrabajador["username_generador"] = $trabajo['username'];
							$eltrabajador["nombre_generador"] = $trabajo['nombre_usuario'];
							$eltrabajador["area_usuario"] = $trabajo['area_usuario'];
							$eltrabajador["porcentaje_genera"] = 0;
							$eltrabajador["producido_total"] = $producido_total_factura;
							$eltrabajador["horas_trabajadas"] = $trabajo['duracion'] * $aporte_factura;
							$eltrabajador["producido_abogado"] = $trabajo['monto_trabajos'] * $aporte_factura;

							if ($eltrabajador["producido_total"] > ($factura['subtotal_facturado'] * $trab)) {
								$eltrabajador["monto_aporte"] = $eltrabajador["porcentaje_genera"] * $factura['subtotal_facturado'] * $gen
															 + $eltrabajador["producido_abogado"] * $trab * ($factura['subtotal_facturado'] / $eltrabajador["producido_total"]);
							} else {
								$eltrabajador["monto_aporte"] = $eltrabajador["producido_abogado"]
															 + $eltrabajador["porcentaje_genera"] * ($factura['subtotal_facturado'] - $eltrabajador["producido_total"]);
							}

							$trabajadores[$numero][$usuario] = $eltrabajador;
						}
					}
				}

				# Agrego los generadores calculados con sus trabajos
				foreach ($generadores as $generador) {
					foreach ($generador as $factura) {
						$this->AddRowToResults($id_cobro, $factura, $report_results);
					}
				}
				# Agrego los que realmente hicieron la pega calculados con sus trabajos
				foreach ($trabajadores as $trabajador) {
					foreach ($trabajador as $factura) {
						$this->AddRowToResults($id_cobro, $factura, $report_results);
					}
				}

			} else {
				foreach ($facturas_generador as $factura) {
					$this->AddRowToResults($id_cobro, $factura, $report_results);
				}
			}
		}

		# THE END
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
		return $writer->save(__('Facturas'));
	}


}