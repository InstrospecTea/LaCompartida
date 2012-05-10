<?php
	require_once dirname(__FILE__).'/../../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	
function formatofecha($fechasucia) {
    $fechasucia=explode('/',$fechasucia);
    $fechalimpia=intval($fechasucia[2].$fechasucia[1].$fechasucia[0]);
    return $fechalimpia;
}
$sesion = new Sesion(array('ADM'));
$currency=array();
$querycurrency="select * from prm_moneda";
 $respcurrency = mysql_query($querycurrency, $sesion->dbh);
 $i=0;
while($fila= mysql_fetch_assoc($respcurrency)) {
    $currency[++$i]=$fila;
}

	$querydiff="SELECT  contrato.id_contrato, 
	    contrato.factura_razon_social, 
	    date_format(cobro.fecha_emision,'%d-%m-%Y') fecha_emision, 
cobro.id_cobro,
round(ifnull(docdeuda.monto,0),2) as montoemitido, 
cobro.id_moneda as moneda_cobro,
round(sum( IF( pef.codigo IN ('A', 'B'), 0, factura.total ) * IF( pdl.codigo = 'NC' , -1, 1 ) ),2) AS facturado,
factura.id_moneda as moneda_factura


FROM cobro join contrato using (id_contrato)
LEFT JOIN factura ON factura.id_cobro = cobro.id_cobro

JOIN prm_estado_factura pef ON pef.id_estado = factura.id_estado
JOIN prm_documento_legal pdl ON pdl.id_documento_legal = factura.id_documento_legal
join (SELECT id_cobro, sum(monto) monto, id_moneda
FROM documento 
WHERE tipo_doc = 'N' group by id_cobro) docdeuda on docdeuda.id_cobro=cobro.id_cobro

WHERE cobro.estado <> 'INCOBRABLE'  ";
if($_GET['fechai'])  $querydiff.= " AND cobro.fecha_creacion >= ".formatofecha($_GET['fechai']);
if($_GET['fechaf'] )  $querydiff.= " AND cobro.fecha_creacion <= ".formatofecha($_GET['fechaf']);

$querydiff.=" GROUP BY cobro.id_cobro, contrato.id_contrato, contrato.factura_razon_social,fecha_emision";



        $resp = mysql_unbuffered_query($querydiff, $sesion->dbh);
	echo '{ "aaData": [';
	echo "\n";
	$i=0;
	while($fila= mysql_fetch_row($resp)) {
	    
	    $deuda=($fila[4]-$fila[6]);
	    if($id_moneda) {
		$simbolo=$currency[$id_moneda]['simbolo'];
		$factor= $currency[intval($fila[5])]['tipo_cambio'] /  $currency[$id_moneda]['tipo_cambio']      ;
		$decimales=$currency[$id_moneda]['cifras_decimales'];
	   } else {
		$simbolo=$currency[intval($fila[5])]['simbolo'];
		$factor	=1	;
		$decimales=2;
		
	    }
	    
	    if((intval($deuda)!=0 || $todo=='true')  ) {
		$fila[4]=$simbolo.' '.round($fila[4]*$factor,$decimales);
	    $fila[6]=$simbolo.' '.round($fila[6]*$factor,$decimales);
	    $deuda=$simbolo.' '.round($deuda*$factor,$decimales);
		if($fila[1]=='') $fila[1]='Contrato sin Raz&oacute;n Social';
		if($i++>0) echo ',';
		
		   $string=  '["'.intval($fila[3]).'","'.$fila[2].'","'.$fila[0].'","'.str_replace("\\","",$fila[1]).'","'.$fila[4].'","'.$fila[6].'","'.$deuda. '"]';
		   echo preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);
	    }
		
	    
	}
	echo '] }';
	
	/* $fecha=$_POST['desde'];
	
	$queryneteos="SELECT  contrato.id_contrato, contrato.factura_razon_social, date_format(cobro.fecha_emision,'%d-%m-%Y'), 
	    round(ifnull(docdeuda.monto,0),2) as montoemitido, 
round(sum( IF( pef.codigo IN ('A', 'B'), 0, factura.total ) * IF( pdl.codigo = 'NC' , -1, 1 ) )*pm.tipo_cambio,2) AS facturado, 
round(ifnull(docpago.monto,0),2) as montopagado,
cobro.id_cobro, date_format(max(cobro.fecha_facturacion),'%d-%m-%Y') as fecha_facturacion
FROM cobro join contrato using (id_contrato)
LEFT JOIN factura ON factura.id_cobro = cobro.id_cobro
join prm_moneda pm on pm.id_moneda=factura.id_moneda
JOIN prm_estado_factura pef ON pef.id_estado = factura.id_estado
JOIN prm_documento_legal pdl ON pdl.id_documento_legal = factura.id_documento_legal
join (SELECT id_cobro, sum(monto * pm.tipo_cambio) monto
FROM documento JOIN prm_moneda pm USING ( id_moneda )
WHERE tipo_doc = 'N' group by id_cobro) docdeuda on docdeuda.id_cobro=cobro.id_cobro
left join (SELECT id_cobro, -sum(monto * pm.tipo_cambio) monto
FROM documento
JOIN prm_moneda pm
USING ( id_moneda )
WHERE tipo_doc != 'N' group by id_cobro) docpago on docpago.id_cobro=cobro.id_cobro

WHERE cobro.fecha_creacion > '2011-06-01'
AND cobro.estado <> 'INCOBRABLE'
GROUP BY cobro.id_cobro, contrato.id_contrato, contrato.factura_razon_social, cobro.fecha_emision
HAVING abs(montoemitido - facturado)>1 
";*/
?>
