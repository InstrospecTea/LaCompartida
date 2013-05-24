<?php

class ReportesEspecificos {
	
	// Del reporte /interfaces/planillas/planilla_saldo.php
	public static $configuracion_saldo_clientes = array(
		array(
			'field' => 'tipo',
			//'group' => 1,
			'visible' => false,
		),
		array(
			'field' => 'identificador',
			'title' => 'N°',
			'visible' => false
		),
		array(
			'field' => 'fecha',
			'title' => 'Fecha',
			'format' => 'date',
			'extras' => array(
				'attrs' => 'width="10%" style="text-align:center"'
			)
		),
		array(
			'field' => 'encargado_comercial',
			'title' => 'Encargado Comercial',
			'visible' => false
		),
		array(
			'field' => 'descripcion',
			'title' => 'Descripción',
			'extras' => array(
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'tipo_liq',
			'visible' => false,
			/*'title' => 'Tipo',
			'extras' => array(
				'attrs' => 'width="5%"',
				'width' => 5
			)*/
		),
		// array(
		// 	'field' => 'monto_original',
		// 	'title' => 'Monto',
		// 	'format' => 'number',
		// 	'extras' => array(
		// 		'symbol' => 'moneda_documento',
		// 		'attrs' => 'width="12%" style="text-align:right"',
		// 		'subtotal' => false
		// 	)
		// ),
		// array(
		// 	'field' => 'saldo_original',
		// 	'title' => 'Saldo',
		// 	'format' => 'number',
		// 	'extras' => array(
		// 		'symbol' => 'moneda_documento',
		// 		'class' => 'saldo',
		// 		'attrs' => 'width="12%" style="text-align:right"',
		// 		'subtotal' => false
		// 	)
		// ),
		array(
			'field' => 'monto_base',
			'title' => 'Monto (base)',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_base',
				'attrs' => 'width="12%" style="text-align:right"'
			)
		),
		array(
			'field' => 'saldo_adelantos',
			'title' => 'Adelantos no utilizados',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_base',
				'class' => 'saldo',
				'attrs' => 'width="12%" style="text-align:right"'
			)
		),
		array(
			'field' => 'saldo_gastos',
			'title' => 'Gastos por liquidar',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_base',
				'class' => 'saldo',
				'attrs' => 'width="12%" style="text-align:right"'
			)
		),
		array(
			'field' => 'saldo_liquidaciones',
			'title' => 'Liquidaciones por pagar',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_base',
				'class' => 'saldo',
				'attrs' => 'width="12%" style="text-align:right"'
			)
		),
		array(
			'field' => '=ACUMULAR(%saldo_adelantos%,%saldo_gastos%,%saldo_liquidaciones%)',
			'title' => 'Acumulado',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_base',
				'class' => 'saldo',
				'attrs' => 'width="12%" style="text-align:right"',
				'subtotal' => false
			)
		)
	);

public static $configuracion_saldo_clientes_resumen = array(
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente',
			'extras' => array(
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'encargado_comercial',
			'title' => 'Encargado Comercial',
			'visible' => false
		),
		array(
			'field' => 'saldo_adelantos',
			'title' => 'Adelantos no utilizados',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'class' => 'saldo',
				'attrs' => 'width="12%" style="text-align:right"'
			)
		),
		array(
			'field' => 'saldo_gastos',
			'title' => 'Gastos por liquidar',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'class' => 'saldo',
				'attrs' => 'width="12%" style="text-align:right"'
			)
		),
		array(
			'field' => 'saldo_liquidaciones',
			'title' => 'Liquidaciones por pagar',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'class' => 'saldo',
				'attrs' => 'width="12%" style="text-align:right"'
			)
		),
		array(
			'field' => '=SUM(%saldo_liquidaciones%,%saldo_gastos%,%saldo_adelantos%)',
			'title' => 'Saldo Total',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'class' => 'saldo',
				'attrs' => 'width="12%" style="text-align:right"',
			)
		),
/*
		array(
			'field' => 'total_liquidaciones',
			'title' => 'Total Liquidaciones',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'class' => 'saldo',
				'attrs' => 'width="15%" style="text-align:right"',
			)
		),
		array(
			'field' => 'total_gastos',
			'title' => 'Total Gastos',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'class' => 'saldo',
				'attrs' => 'width="15%" style="text-align:right"'
			)
		),
		array(
			'field' => 'total_adelantos',
			'title' => 'Total Adelantos',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'class' => 'saldo',
				'attrs' => 'width="15%" style="text-align:right"'
			)
		),
		array(
			'field' => 'total_total',
			'title' => 'Total Total',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'class' => 'saldo',
				'attrs' => 'width="15%" style="text-align:right"'
			)
		),
*/
	);
}
