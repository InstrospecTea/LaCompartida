<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	
	$sesion = new Sesion();
	
	if($argv[1]!='inconsistencia') exit;
	
	// Revisiones de inconsistencías 
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
	$mensaje = "Se han observado inconistencias en las siguientes cobros del cliente ".Conf::dbUser().":<br/><br/>";
	while( list($id_cobro,$monto_thh,$sumatoria) = mysql_fetch_array($resp) ) {
		$mensaje .= "Cobro: $id_cobro   Monto_thh: $monto_thh   Sumatoria según horas: $sumatoria <br/>";
		$enviar = true;
	}
	if( $enviar ) {
		Utiles::Insertar($sesion, "Inconsistencia base de datos", $mensaje, "smoers@lemontech.cl", "Stefan");
	}
?>