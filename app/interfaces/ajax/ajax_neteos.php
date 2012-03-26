<?php
	require_once dirname(__FILE__).'/../../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	

	$sesion = new Sesion(array('ADM'));
        
	$queryneteos="select * from (
SELECT 'Neteo' as comentario, dc.tipo_doc as tipocobro,
nd.id_documento_cobro iddoccobro,
(nd.valor_cobro_honorarios + nd.valor_cobro_gastos) as cobrototal,
(nd.valor_pago_honorarios+nd.valor_pago_gastos) as pagototal,
nd.id_documento_pago iddocpago,
dp.es_adelanto,dp.tipo_doc as tipopago, ifnull(dc.id_cobro,dp.id_cobro) id_cobro, dp.glosa_documento as glosa
FROM  documento dc 
right join neteo_documento nd on dc.id_documento=nd.id_documento_cobro and dc.tipo_doc='N'
left join documento dp ON dp.id_documento=nd.id_documento_pago and dp.tipo_doc!='N'


union 

SELECT 'Cobro sin Neteo' as comentario, dc.tipo_doc as tipocobro,
dc.id_documento iddoccobro,
(dc.subtotal_honorarios + dc.subtotal_gastos) as cobrototal,
(nd.valor_pago_honorarios + nd.valor_pago_gastos) as pagototal,
NULL as iddocpago,
NULL as es_adelanto, NULL as tipocpago, dc.id_cobro, dc.glosa_documento as glosa
FROM  documento dc 
left join neteo_documento nd on dc.id_documento=nd.id_documento_cobro 
where dc.tipo_doc='N' and nd.id_documento_cobro is null
and dc.id_documento not in (SELECT dc.id_documento iddoccobro
FROM  documento dc 
left join neteo_documento nd on dc.id_documento=nd.id_documento_cobro 
right join documento dp ON dp.id_documento=nd.id_documento_pago 
where dc.codigo_cliente=dp.codigo_cliente
and dc.id_cobro=dp.id_cobro
and nd.id_neteo_documento is null
and dp.tipo_doc!='N'
and dc.tipo_doc='N') 

union

SELECT 'Pago sin neteo' as comentario, null as tipocobro,
nd.id_documento_cobro iddoccobro,
(nd.valor_cobro_honorarios + nd.valor_cobro_gastos) as cobrototal,
abs(dp.monto) as pagototal,
dp.id_documento iddocpago,
dp.es_adelanto, dp.tipo_doc as tipocpago, dp.id_cobro, dp.glosa_documento as glosa
FROM  documento dp 
left join neteo_documento nd on dp.id_documento=nd.id_documento_pago 
where dp.tipo_doc!='N' and nd.id_documento_cobro is null
and dp.id_documento not in (SELECT dp.id_documento iddocpago
FROM  documento dc 
left join neteo_documento nd on dc.id_documento=nd.id_documento_cobro 
right join documento dp ON dp.id_documento=nd.id_documento_pago 
where dc.codigo_cliente=dp.codigo_cliente
and dc.id_cobro=dp.id_cobro
and nd.id_neteo_documento is null
and dp.tipo_doc!='N'
and dc.tipo_doc='N') 

union

SELECT 'Posible match sin neteo' as comentario, dc.tipo_doc as tipocobro,
dc.id_documento iddoccobro,
(dc.subtotal_honorarios + dc.subtotal_gastos) as cobrototal,
abs(dp.monto) as pagototal,
dp.id_documento iddocpago,
dp.es_adelanto,dp.tipo_doc as tipopago, dc.id_cobro, ifnull(dc.glosa_documento,dp.glosa_documento) as glosa
FROM  documento dc 
left join neteo_documento nd on dc.id_documento=nd.id_documento_cobro 
right join documento dp ON dp.id_documento=nd.id_documento_pago 
where dc.codigo_cliente=dp.codigo_cliente
and dc.id_cobro=dp.id_cobro
and nd.id_neteo_documento is null
and dp.tipo_doc!='N'
and dc.tipo_doc='N') neteos
 limit 0,100";
	
	
//echo $queryneteos;

        $resp = mysql_query($queryneteos, $sesion->dbh);
	echo '{ "aaData": [';
	echo "\n";
	$fila= mysql_fetch_row($resp);
	 echo '["'.implode($fila,'","').'"]'."\n";
	while($fila= mysql_fetch_row($resp)) {
	    
	    echo ',["'.implode($fila,'","').'"]'."\n";
	    
	}
	echo '] }';
	
	
?>