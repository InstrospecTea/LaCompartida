<?php

	echo "Probar aqui";

	$reporte = new Reporte($this->Session);

	$filtros = array(
		'fecha_ini' => '01-01-2014',
		'fecha_fin' => '01-01-2015',
		'campo_fecha' => 'cobro',
		'dato' => 'valor_cobrado',
		'prop' => 'cliente',
		'vista' => 'glosa_cliente',
		'id_moneda' => 1
	);

	$reporte->setFiltros($filtros);
	$reporte->setVista('area_asunto-area_usuario-categoria_usuario-area_trabajo');

	$reporte->Query();

	$r = $reporte->toArray();

?>
