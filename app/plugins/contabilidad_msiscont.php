<?php
/**
 * Ofrece exportar las facturas al archivo de contabilidad para MSISCONT
 *
 * @package The Time Billing
 * @subpackage Plugins
 */

require_once dirname(__FILE__) . '/../conf.php';

$Slim = Slim::getInstance('default', true);
$Slim->hook('hook_factura_fin', 'InsertarBotonDescargar');
$Slim->hook('hook_facturas_js', 'InsertarJSBuscador');
$Slim->hook('hook_facturas_genera_archivo_contabilidad', function($hookArg) {
	return GenerarArchivoContabilidad($hookArg);
});

function InsertarBotonDescargar() {
	$content = "<a class=\"btn botonizame\" name=\"boton_contabilidad\" onclick=\"DescargarArchivoContabilidad(this.form, 'archivo_contabilidad')\" >" . __('Descargar Archivo Contabilidad') . "</a>";
	echo $content;
}

function InsertarJSBuscador() {
	$js =<<<JS
function DescargarArchivoContabilidad(form) {
	if (!form) {
		var form = $('form_facturas');
	}
	form.action = 'facturas.php?archivo_contabilidad=1';
	form.submit();
	return true;
}
JS;

	echo $js;
}

function GenerarArchivoContabilidad($hookArg) {
	require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';
	$Sesion = new Sesion();
        $results = $hookArg['Resultados'];
	$SimpleReport = new SimpleReport($Sesion);
	$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($Sesion));

	$config = array(
		array(
			'field' => 'fixed_1',
			'order' => 1,
			'format' => 'number',
			'extras' => array(
				'length' => 2,
				'value' => '2',
				'real_field' => 'fixed'
			)
		),
		array(
			'field' => 'iterator',
			'order' => 2,
			'format' => 'number',
			'extras' => array(
				'length' => 4
			)
		),
		array(
			'field' => 'fecha_1',
			'format' => 'date',
			'order' => 3,
			'extras' => array(
				'length' => 10,
				'real_field' => 'fecha'
			)
		),
		array(
			'field' => 'fixed_2',
			'format' => 'number',
			'order' => 4,
			'extras' => array(
				'length' => 10,
				'value' => '7041',
				'real_field' => 'fixed'
			)
		),
		array(
			'field' => 'total_1',
			'format' => 'float',
			'order' => 5,
			'extras' => array(
				'length' => 12,
				'real_field' => 'total'
			),
		),
		array(
			'field' => 'fixed_3',
			'order' => 6,
			'extras' => array(
				'length' => 1,
				'value' => 'D',
				'real_field' => 'fixed'
			)
		),
		array(
			'field' => 'codigo_moneda',
			'order' => 7,
			'extras' => array(
				'length' => 1,
				'translate' => array(
					'USD' => 'D',
					'PEN' => 'S'
				)
			)
		),
		array(
			'field' => 'tipo_cambio',
			'format' => 'float',
			'order' => 8,
			'extras' => array(
				'length' => 10
			)
		),
		array(
			'field' => 'tipo',
			'order' => 9,
			'extras' => array(
				'length' => 2,
				'translate' => array(
					'FA' => '01',
					'BO' => '02',
					'NC' => '03',
					'ND' => '04'
				)
			)
		),
		array(
			'field' => 'serie_documento_legal',
			'format' => 'number',
			'order' => 10,
			'extras' => array(
				'length' => 3
			)
		),
		array(
			'field' => 'fixed_4',
			'order' => 11,
			'extras' => array(
				'length' => 1,
				'value' => '-',
				'real_field' => 'fixed'
			)
		),
		array(
			'field' => 'numero',
			'format' => 'number',
			'order' => 12,
			'extras' => array(
				'length' => 16
			)
		),
		array(
			'field' => 'fixed_5',
			'order' => 13,
			'extras' => array(
				'length' => 8,
				'value' => '',
				'real_field' => 'fixed'
			)
		),
		array(
			'field' => 'RUT_cliente_1',
			'order' => 14,
			'extras' => array(
				'length' => 11,
				'real_field' => 'RUT_cliente'
			)
		),
		array(
			'field' => 'fixed_6',
			'order' => 15,
			'extras' => array(
				'length' => 25,
				//'value' => 'C',
				'value' => '',
				'real_field' => 'fixed'
			)
		),
		array(
			'field' => 'fecha_2',
			'format' => 'date',
			'order' => 16,
			'extras' => array(
				'length' => 10,
				'real_field' => 'fecha'
			)
		),
		array(
			'field' => 'total_2',
			'format' => 'float',
			'order' => 17,
			'extras' => array(
				'length' => 12,
				'real_field' => 'total'
			),
		),
		array(
			'field' => 'total_3',
			'format' => 'float',
			'order' => 18,
			'extras' => array(
				'length' => 12,
				'real_field' => 'total'
			),
		),
		array(
			'field' => 'total_4',
			'format' => 'float',
			'order' => 19,
			'extras' => array(
				'length' => 12,
				'real_field' => 'total'
			),
		),
		array(
			'field' => 'total_5',
			'format' => 'float',
			'order' => 20,
			'extras' => array(
				'length' => 12,
				'real_field' => 'total'
			),
		),
		array(
			'field' => 'total_6',
			'format' => 'float',
			'order' => 21,
			'extras' => array(
				'length' => 12,
				'real_field' => 'total'
			),
		),
		array(
			'field' => 'RUT_cliente_2',
			'order' => 22,
			'extras' => array(
				'length' => 11,
				'real_field' => 'RUT_cliente'
			)
		),
		array(
			'field' => 'fixed_7',
			'order' => 23,
			'extras' => array(
				'length' => 1,
				'value' => '1',
				'real_field' => 'fixed'
			)
		),
		array(
			'field' => 'factura_rsocial',
			'order' => 24,
			'extras' => array(
				'length' => 40,
				'align' => 'left'
			)
		),
		array(
			'field' => 'descripcion',
			'order' => 25,
			'extras' => array(
				'length' => 30
			)
		),
		array(
			'field' => 'fixed_8',
			'order' => 26,
			'extras' => array(
				'length' => 64,
				'value' => '1001',
				'real_field' => 'fixed',
				'align' => 'left'
			)
		),
		array(
			'field' => 'total_7',
			'format' => 'float',
			'order' => 27,
			'extras' => array(
				'length' => 12,
				'real_field' => 'total'
			),
		),
		array(
			'field' => 'total_8',
			'format' => 'float',
			'order' => 28,
			'extras' => array(
				'length' => 12,
				'real_field' => 'total'
			),
		),
		array(
			'field' => 'fixed_9',
			'order' => 29,
			'extras' => array(
				'length' => 58,
				'value' => '',
				'real_field' => 'fixed'
			)
		)
	);

	$SimpleReport->LoadConfigFromArray($config);

	$SimpleReport->LoadResults($results);

	$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Text');
	$writer->save('Archivo_Contabilidad');
}