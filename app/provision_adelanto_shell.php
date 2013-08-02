<?php
require_once dirname(__FILE__) . '/conf.php';

$sesion = new Sesion();
$gasto = new Gasto($sesion);
$pagina = new Pagina($sesion);

$Slim = Slim::getInstance('default', true);

$where = $gasto->WhereQuery(array('motivo' => 'gastos', 'cobrado' => 'NO', 'egresooingreso' => 'soloingreso'));

$selectfrom = $gasto::SelectFromQuery();
$query = "SELECT cta_corriente.id_movimiento FROM $selectfrom WHERE ( cobro.estado IS NULL OR cobro.estado NOT LIKE 'INCOBRABLE' )
			AND (cta_corriente.ingreso IS NOT NULL OR cta_corriente.egreso IS NOT NULL) AND $where order by fecha desc";
$resp = $sesion->pdodbh->query($query);
$x = 0;
foreach ($resp as $fila) {
	++$x;
	$gasto->Load($fila['id_movimiento']);
	$Slim->applyHook('hook_realizar_convertir_adelanto');
}

echo "Se convirtieron $x provisiones en adelantos.<br/><br/><a href='javascript:history.back(1)'>< Atrás</a>";