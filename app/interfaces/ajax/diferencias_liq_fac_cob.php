<?php
	require_once dirname(__FILE__).'/../../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	

	$sesion = new Sesion(array('ADM'));
        $fecha=$_POST['desde'];
	
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
";
	$querydiff="SELECT  contrato.id_contrato, contrato.factura_razon_social, date_format(cobro.fecha_emision,'%d-%m-%Y'), 
	    round(ifnull(docdeuda.monto,0),2) as montoemitido, 
round(sum( IF( pef.codigo IN ('A', 'B'), 0, factura.total ) * IF( pdl.codigo = 'NC' , -1, 1 ) )*pm.tipo_cambio,2) AS facturado, 

cobro.id_cobro, date_format(max(cobro.fecha_facturacion),'%d-%m-%Y') as fecha_facturacion
FROM cobro join contrato using (id_contrato)
LEFT JOIN factura ON factura.id_cobro = cobro.id_cobro
join prm_moneda pm on pm.id_moneda=factura.id_moneda
JOIN prm_estado_factura pef ON pef.id_estado = factura.id_estado
JOIN prm_documento_legal pdl ON pdl.id_documento_legal = factura.id_documento_legal
join (SELECT id_cobro, sum(monto * pm.tipo_cambio) monto
FROM documento JOIN prm_moneda pm USING ( id_moneda )
WHERE tipo_doc = 'N' group by id_cobro) docdeuda on docdeuda.id_cobro=cobro.id_cobro


WHERE cobro.fecha_creacion > '2011-06-01'
AND cobro.estado <> 'INCOBRABLE'
GROUP BY cobro.id_cobro, contrato.id_contrato, contrato.factura_razon_social, cobro.fecha_emision

";
//HAVING abs(montoemitido - facturado)>1 	
//echo $queryneteos;

        $resp = mysql_query($querydiff, $sesion->dbh);
	echo '{ "aaData": [';
	echo "\n";
	$fila= mysql_fetch_row($resp);
	 echo '["'.implode($fila,'","').'"]'."\n";
	while($fila= mysql_fetch_row($resp)) {
	    
	    echo ',["'.implode($fila,'","').'"]'."\n";
	    
	}
	echo '] }';
	
	
?>