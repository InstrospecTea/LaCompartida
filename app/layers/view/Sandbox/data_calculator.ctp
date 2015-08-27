<?php

	echo "Probar aqui";

	$reporte = new ReporteCriteria($this->Session);

	$filtros = array(
		'fecha_ini' => '2014-01-01',
		'fecha_fin' => '2015-01-01',
		'campo_fecha' => 'cobro',
		'dato' => 'valor_cobrado',
		'prop' => 'cliente',
		'vista' => 'glosa_cliente',
		'id_moneda' => 1
	);

	$reporte->setFiltros($filtros);

	$reporte->Query();

	$r = $reporte->toArray();
	$calculator = new BilledAmountDataCalculator(
		$this->Session,
		array('clientes' => array('1', '2'), 'campo_fecha' => 'trabajo', 'fecha_fin' => '123q23123'),
		array('area_asunto'),
		array('1')
	);

	pr($r);
	pr($calculator->buildChargeQuery()->get_plain_query());

	// $Criteria = $calculator->getWorksCriteria();

	// pr($Criteria->get_plain_query());

?>