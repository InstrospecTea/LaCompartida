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

	$querydiff="select date_format(c.fecha_emision,'%d-%m-%Y') fecha_emision, c.id_cobro,c.estado, dc.monto as monto_deuda,dc.id_moneda, pagos.total_pagos, pagos.id_moneda, pagos.minfecha,pagos.maxfecha 
from cobro c join documento dc on dc.id_cobro=c.id_cobro
join (SELECT nd.id_documento_cobro, SUM( valor_pago_honorarios + valor_pago_gastos ) total_pagos, documento.id_moneda
, max(documento.fecha_creacion) maxfecha
, min(documento.fecha_creacion) minfecha
FROM neteo_documento nd
JOIN documento ON nd.id_documento_pago = documento.id_documento
GROUP BY id_documento_cobro, documento.id_moneda) pagos on pagos.id_documento_cobro=dc.id_documento
where   1 ";
if(isset($_GET['fechaicobro']) && formatofecha($_GET['fechaicobro'])>0)  $querydiff.= " AND c.fecha_emision >= ".formatofecha($_GET['fechaicobro']);
if(isset($_GET['fechafcobro']) && formatofecha($_GET['fechafcobro'])>0)  $querydiff.= " AND c.fecha_emision <= ".formatofecha($_GET['fechafcobro']);

if($_GET['estado'] )  {
    $querydiff.= " AND c.estado = '". $_GET['estado']."'";
    if(!$_GET['todo'])  $querydiff.=" AND (c.estado!='". $_GET['estado']."' and abs(dc.monto -total_pagos)<1) or (c.estado='PAGADO' and abs(dc.monto -total_pagos)>1) ";
}


        $resp = mysql_unbuffered_query($querydiff, $sesion->dbh) or die( mysql_error())                ;
	echo '{ "aaData": [';
	 
	$i=0;
	while($fila= mysql_fetch_row($resp)) {
	    if($i>0) echo ',';
            $i++;
	   /* $deuda=($fila[4]-$fila[6]);*/
	    if($id_moneda) {
		$simbolo=$currency[$id_moneda]['simbolo'];
		$factor= $currency[intval($fila[4])]['tipo_cambio'] /  $currency[$id_moneda]['tipo_cambio']      ;
                $factor2= $currency[intval($fila[6])]['tipo_cambio'] /  $currency[$id_moneda]['tipo_cambio']      ;
		$decimales=$currency[$id_moneda]['cifras_decimales'];
	   } else {
		$simbolo=$currency[intval($fila[4])]['simbolo'];
		$factor	=1	;
		$decimales=2;
                $factor2= $currency[intval($fila[6])]['tipo_cambio'] /  $currency[intval($fila[4])]['tipo_cambio']     ;
		
		
	    }
	    $monto1=$factor*$fila[3];
            $monto2=$factor2*$fila[5];
            $monto3=$monto1-$monto2;
            echo json_encode(
                    array(  $fila[1],
                            $fila[0],
                            $fila[2],
                            $simbolo.' '.$monto1,
                            $simbolo.' '.$monto2,
                            $simbolo.' '.$monto3)
                    );
            
		
	    
	}
	echo '] }';
	
	 
?>
