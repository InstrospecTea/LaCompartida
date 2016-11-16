<?php
require_once dirname(__FILE__).'/../../conf.php';

function formatofecha($fechasucia) {
  $fechasucia = explode('-',str_replace('/','-',$fechasucia));
  $fechalimpia = intval($fechasucia[2].$fechasucia[1].$fechasucia[0]);
  return $fechalimpia;
}

$sesion = new Sesion(array('ADM'));

$currency = [];
$querycurrency = "select * from prm_moneda";
$respcurrency = mysql_query($querycurrency, $sesion->dbh);
while ($fila = mysql_fetch_assoc($respcurrency)) {
  $currency[$fila['id_moneda']] = $fila;
}

$querydiff = "SELECT  contrato.id_contrato,
  contrato.factura_razon_social,
  date_format(cobro.fecha_emision,'%d-%m-%Y') fecha_emision,
	cobro.id_cobro,
	round(ifnull(docdeuda.monto,0),2) as monto_emitido,
	cobro.id_moneda as moneda_cobro,
	round(sum( IF( pef.codigo IN ('A', 'B'), 0, factura.total ) * IF( pdl.codigo = 'NC' , -1, 1 ) ),2) AS monto_facturado,
	factura.id_moneda as moneda_factura
	FROM cobro join contrato using (id_contrato)
	LEFT JOIN factura ON factura.id_cobro = cobro.id_cobro
	JOIN prm_estado_factura pef ON pef.id_estado = factura.id_estado
	JOIN prm_documento_legal pdl ON pdl.id_documento_legal = factura.id_documento_legal
	join (SELECT id_cobro, sum(monto) monto, id_moneda
	FROM documento
	WHERE tipo_doc = 'N' group by id_cobro) docdeuda on docdeuda.id_cobro=cobro.id_cobro
	WHERE cobro.estado <> 'INCOBRABLE'";

if ($_GET['fechai'])  $querydiff.= " AND cobro.fecha_creacion >= ".formatofecha($_GET['fechai']);
if ($_GET['fechaf'] )  $querydiff.= " AND cobro.fecha_creacion <= ".formatofecha($_GET['fechaf']);

$querydiff.= " GROUP BY cobro.id_cobro, contrato.id_contrato, contrato.factura_razon_social,fecha_emision";
$resp = mysql_unbuffered_query($querydiff, $sesion->dbh);
$data = [];
while($fila = mysql_fetch_assoc($resp)) {
  if ($id_moneda) {
		$simbolo = $currency[$id_moneda]['simbolo'];
		$factor = $currency[$fila['moneda_factura']]['tipo_cambio'] / $currency[$id_moneda]['tipo_cambio'];
		$decimales = $currency[$id_moneda]['cifras_decimales'];
   } else {
		$simbolo = $currency[$fila['moneda_cobro']]['simbolo'];
		$factor	= $currency[$fila['moneda_factura']]['tipo_cambio'] / $currency[$fila['moneda_cobro']]['tipo_cambio'];
		$decimales = $currency[$fila['moneda_cobro']]['decimales'];
  }
	$fila['monto_emitido'] = round($fila['monto_emitido'] * $factor, $decimales);
	$fila['monto_facturado'] = round($fila['monto_facturado'] * $factor, $decimales);

	$deuda = $fila['monto_emitido'] - $fila['monto_facturado'];

  if ($deuda != 0) {
		$fila['monto_emitido'] = "{$simbolo} {$fila['monto_emitido']}";
		$fila['monto_facturado'] = "{$simbolo} {$fila['monto_facturado']}";
	  $deuda = "$simbolo " . round($deuda, $decimales);
		if ($fila['factura_razon_social'] == '') {
			$fila['factura_razon_social'] = __('Contrato sin Razón Social');
		}
		$data[] = [
			$fila['id_cobro'],
			$fila['fecha_emision'],
			$fila['id_contrato'],
			$fila['factura_razon_social'],
			$fila['monto_emitido'],
			$fila['monto_facturado'],
			$deuda
		];
  }
}
echo json_encode(['aaData' => $data]);
