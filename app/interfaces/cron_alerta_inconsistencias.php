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
	$mensaje = "Se han observado inconistencias en los siguientes cobros del cliente ".Conf::dbUser().":<br/><br/>";
	while( list($id_cobro,$monto_thh,$sumatoria) = mysql_fetch_array($resp) ) {
		$mensaje .= "Cobro: $id_cobro   Monto_thh: $monto_thh   Sumatoria según horas: $sumatoria <br/>";
		$enviar = true;
	}
	if( $enviar ) {
		Utiles::Insertar($sesion, "Inconsistencia datos ".Conf::dbUser(), $mensaje, "smoers@lemontech.cl,gtigre@lemontech.cl", "Soporte");
	}
?>
