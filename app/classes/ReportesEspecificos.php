<?php

class ReportesEspecificos {
	
	// Del reporte /interfaces/planillas/planilla_saldo.php
	public static $configuracion_saldo_clientes = array(
		array(
			'field' => 'tipo',
			'group' => 1
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
			'field' => 'descripcion',
			'title' => 'Descripción',
			'extras' => array(
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'tipo_liq',
			'title' => 'Tipo',
			'extras' => array(
				'attrs' => 'width="5%"',
				'width' => 5
			)
		),
		array(
			'field' => 'monto_original',
			'title' => 'Monto',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_documento',
				'attrs' => 'width="15%" style="text-align:right"',
				'subtotal' => false
			)
		),
		array(
			'field' => 'saldo_original',
			'title' => 'Saldo',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_documento',
				'class' => 'saldo',
				'attrs' => 'width="15%" style="text-align:right"',
				'subtotal' => false
			)
		),
		array(
			'field' => 'monto_base',
			'title' => 'Monto (base)',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_base',
				'attrs' => 'width="15%" style="text-align:right"'
			)
		),
		array(
			'field' => 'saldo_base',
			'title' => 'Saldo (base)',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'moneda_base',
				'class' => 'saldo',
				'attrs' => 'width="15%" style="text-align:right"'
			)
		)
	);
}
