<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	
	$sesion = new Sesion();
	
		if($argv[1]!='inconsistencia' && !isset($_GET['inconsistencia'])) exit;

	
	// Revisiones de inconsistencías entre código cliente y código asunto de los gastos del cliente.
	$query = "SELECT 
					cta_corriente.id_movimiento, 
					cta_corriente.codigo_cliente, 
					cta_corriente.codigo_asunto 
				FROM cta_corriente 
				JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente 
				JOIN asunto ON cta_corriente.codigo_asunto = asunto.codigo_asunto 
				WHERE asunto.codigo_cliente != cliente.codigo_cliente 
				   OR cta_corriente.codigo_cliente != SUBSTRING( cta_corriente.codigo_asunto,1,4) "; 
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	$enviar = false;
	$mensaje = "Se han observado inconistencias en los siguientes gastos del cliente <b>".strtoupper(Conf::dbUser())."</b>:<br/><br/>";
	while( list($id_movimiento,$codigo_cliente,$codigo_asunto) = mysql_fetch_array($resp) ) {
		$mensaje .= "ID: $id_movimiento   Código cliente: $codigo_cliente   Código Asunto: $codigo_asunto <br/>";
		$enviar = true;
	}
	if( $enviar ) {
		Utiles::Insertar($sesion, "Inconsistencia datos ".Conf::dbUser(), $mensaje, "ffigueroa@lemontech.cl,gtigre@lemontech.cl", "Soporte");
	}
	
	// Revisiones de inconsistencías entre monto_thh del cobro y sumatoria de tarifas_hh 
	$query = "SELECT 
					cobro.id_cobro, 
					cobro.monto_thh, 
					SUM( IF( trabajo.cobrable =1, trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) /3600, 0 ) ) AS suma_horas_cob 
				FROM trabajo 
				JOIN cobro ON cobro.id_cobro = trabajo.id_cobro 
				WHERE cobro.estado NOT IN ('CREADO','EN REVISION') 
				GROUP BY cobro.id_cobro 
				HAVING 
				ABS( cobro.monto_thh - SUM(IF(trabajo.cobrable =1,trabajo.tarifa_hh*TIME_TO_SEC( trabajo.duracion_cobrada )/3600,0)))>1 
				ORDER BY cobro.id_cobro DESC"; 
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	$enviar = false;
	$mensaje = "Se han observado inconsistencias en los siguientes cobros del cliente ".Conf::dbUser().":<br/><br/>";
	while( list($id_cobro,$monto_thh,$sumatoria) = mysql_fetch_array($resp) ) {
		$mensaje .= "Cobro: $id_cobro   Monto_thh: $monto_thh   Sumatoria según horas: $sumatoria <br/>";
		$enviar = true;
	}
	if( $enviar ) {
		Utiles::Insertar($sesion, "Inconsistencia datos ".Conf::dbUser(), $mensaje, "ffigueroa@lemontech.cl,gtigre@lemontech.cl", "Soporte");
	}
	
	// Revisiones de inconsistencías entre documentos de pago y facturas de pago
	$querys=array();
	$querys[]="drop table if exists netres;";
 $querys[]="create table netres as 
    select c.id_cobro , 
    fc.id_documento iddoccobro,
    ccfm_cobro.id_factura id_factura_cobro,
    ccfm_cobro.monto_bruto cobrototal_factura,  
    ccfm_pago.monto_bruto pagototal_factura,  

     ccfm_pago.id_factura_pago  factura_pago, 
    fp.monto as monto_pago_factura, fp.id_neteo_documento_adelanto, dp.id_documento as iddocpago

    from cobro c  
    LEFT JOIN factura_cobro fc on fc.id_cobro=c.id_cobro
    LEFT JOIN cta_cte_fact_mvto AS ccfm_cobro ON ccfm_cobro.id_factura = fc.id_factura 
    left JOIN cta_cte_fact_mvto_neteo AS ccfmn on ccfmn.id_mvto_deuda = ccfm_cobro.id_cta_cte_mvto
    left JOIN cta_cte_fact_mvto AS ccfm_pago on  ccfmn.id_mvto_pago = ccfm_pago.id_cta_cte_mvto 
    left join factura_pago fp ON fp.id_factura_pago = ccfm_pago.id_factura_pago 
    left join documento dp on dp.id_factura_pago = fp.id_factura_pago and dp.tipo_doc!='N';";
    


$querys[]="ALTER TABLE  `netres` ADD INDEX (  `id_cobro` );";
$querys[]="ALTER TABLE  `netres` ADD INDEX (  `iddoccobro` ); ";

 $querys[]="drop table if exists netdos;";
$querys[]="create table netdos as
SELECT nd.id_neteo_documento, dc.monto monto_cobro,
nd.id_documento_cobro iddoccobro,
(nd.valor_cobro_honorarios + nd.valor_cobro_gastos) as cobrototal,
(nd.valor_pago_honorarios+nd.valor_pago_gastos) as pagototal,
nd.id_documento_pago iddocpago,
-1*dp.monto as monto_pago,
dc.id_cobro id_cobro, dp.id_factura_pago, if(dp.numero_doc='',dp.numero_operacion,dp.numero_doc) as numero_documento_pago
FROM  cobro join documento dc on dc.id_cobro=cobro.id_cobro 
right join neteo_documento nd on dc.id_documento=nd.id_documento_cobro and dc.tipo_doc='N'
left join documento dp ON dp.id_documento=nd.id_documento_pago and dp.tipo_doc!='N';";


 
$querys[]="ALTER TABLE  `netdos` ADD INDEX (  `id_cobro` );";
$querys[]="ALTER TABLE  `netdos` ADD INDEX (  `iddoccobro` );";
	foreach ($querys as $q) mysql_query($q, $sesion->dbh);
$query ="select netdos.iddoccobro, netdos.iddocpago, netdos.id_cobro  from netdos left join netres on netdos.id_cobro=netres.id_cobro
and netdos.iddoccobro=netres.iddoccobro 
where  netres.id_cobro is null
union 
select netres.iddoccobro, netres.iddocpago, netres.id_cobro  from netdos right join netres on netdos.id_cobro=netres.id_cobro
and netres.iddocpago=netdos.iddocpago
where  netdos.id_cobro is null and netres.iddocpago is not null";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	$enviar = false;
	$mensaje = "Se han observado inconsistencias en los siguientes documentos  de pago del cliente ".Conf::dbUser().":<br/><br/>";
	while( list($iddoccobro, $iddocpago, $id_cobro) = mysql_fetch_array($resp) ) {
		$mensaje .= "Documento Cobro: $iddoccobro Cobro: $id_cobro  Documento Pago: $iddocpago <br/>";
		$enviar = true;
	}
	if( $enviar ) {
		Utiles::Insertar($sesion, "Inconsistencia documentos pago ".Conf::dbUser(), $mensaje, "ffigueroa@lemontech.cl,gtigre@lemontech.cl", "Soporte");
	}
	
	
	
	
	
?>
