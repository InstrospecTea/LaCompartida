<?php

require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(null, true);

if ($argv[1] != 'ambienteprueba' && !isset($_GET['ambienteprueba'])) {
	die($argv[1] . $_GET['ambienteprueba']);
}

$sesion = new Sesion(null, true);

if (Conf::GetConf($sesion, 'EsAmbientePrueba')) {
	/* Query para borrar factura */
	$query = "TRUNCATE TABLE  `cta_cte_fact_mvto_neteo`;";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Neteos borrados.<br>';

	/* Query para borrar factura */
	$query = "TRUNCATE TABLE  `cta_cte_fact_mvto`;";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Movimientos borrados.<br>';

	/* Query para borrar los neteos de documentos */
	$query = "TRUNCATE TABLE neteo_documento";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Neteos borrado.<br>';

	/* Query para borrar documentos */
	$query = "DELETE FROM documento WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Documentos borrado.<br>';

	/* Query para borrar factura */
	$query = "TRUNCATE TABLE `factura_pago`;";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Facturas Pagos borrado.<br>';

	/* Query para borrar factura */
	$query = "DELETE FROM factura 
							 WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Facturas borrado.<br>';

	/* Elimina todos los entradas en cobro_asunto */
	$query = "DELETE FROM cobro_asunto WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Todos los datos en cobro_asunto eliminado.<br>';

	/* Elimina todos los entradas en cobro_moneda */
	$query = "DELETE FROM cobro_moneda WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Todos los datos en cobro_moneda eliminado.<br>';

	/* Vuelva cobros emitidios atras a creado */
	$query = " UPDATE cobro SET estado='CREADO' WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);


	/* Borrar cobros creados por visitantes */
	$query = "DELETE FROM cobro WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Cobros borrado.<br>';

	/* Borrar todos los trabajos creados por visitantes */
	$query = "DELETE FROM trabajo 
							 WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Trabajos borrado.<br>';

	/* Borrar todos los gastos ingresados por visitantes */
	$query = "DELETE FROM cta_corriente WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Gastos borrado.<br>';

	/* Borrar todos los trámites ingresados por visitantes */
	$query = "DELETE FROM tramite WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo 'Trámites borrado.<br>';

	if (Conf::GetConf($sesion, 'TieneTablaVisitante')) {
		/* Busca cualquier usuario */
		$query = "SELECT id_usuario FROM usuario WHERE id_visitante = 0 ORDER BY id_usuario LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($id_usuario) = mysql_fetch_array($resp);

		/* Modifica encargados en asuntos y contrato que tiene relacion con un visitante para poder borrarlos despues */
		$query = "UPDATE asunto 
										JOIN usuario AS u ON u.id_usuario=asunto.id_usuario 
										left JOIN usuario AS encargado ON encargado.id_usuario=asunto.id_encargado 
										left JOIN usuario AS cobrador ON cobrador.id_usuario=asunto.id_cobrador 
										 SET asunto.id_usuario = " . $id_usuario . ", 
										 		 asunto.id_encargado = " . $id_usuario . ", 
										 		 asunto.id_cobrador = " . $id_usuario . " 
									 WHERE u.id_visitante > 0 OR encargado.id_visitante > 0 OR cobrador.id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, $sesion->dbh);

		$query = "UPDATE contrato 
										JOIN usuario AS u ON u.id_usuario=contrato.id_usuario_responsable 
										 SET id_usuario_responsable = " . $id_usuario . " 
									 WHERE u.id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		$query = "UPDATE modificaciones_contrato AS mc 
										JOIN usuario AS ur ON ur.id_usuario=mc.id_usuario_responsable  
										JOIN usuario AS u ON u.id_usuario=mc.id_usuario 
										 SET mc.id_usuario_responsable = " . $id_usuario . ",  
										 		 mc.id_usuario = " . $id_usuario . "  
									 WHERE u.id_visitante > 0 OR ur.id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		/* BOrrar todos los usuarios que tienen id_visitante mejor a 0 */
		$query = "DELETE FROM usuario WHERE id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		echo 'Todos los visitantes borrado de la tabla de usuarios dentro del sistema';
		$query = "update usuario set activo=0 WHERE id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		echo 'Todos los visitantes desactivados de la tabla de usuarios dentro del sistema';	// por si alguno no se pudo borrar
	}

	if (method_exists('Conf', 'BorrarDatosAdministracion') && Conf::BorrarDatosAdministracion()) {
		/* Borrar todos los tipos trámites ingresados por visitantes */
		$query = "DELETE FROM tramite_tipo 
									 WHERE 1";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		echo 'Tipos Trámites borrado.<br>';

		/* Borrar todos los asuntos */
		$query = "DELETE FROM asunto WHERE 1";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		echo 'Asuntos borrado.<br>';

		/* Borrar todos los contratos */
		$query = "DELETE FROM contrato WHERE 1";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		echo 'Contratos borrados.<br>.';

		/* Borrar todos los clientes */
		$query = "DELETE FROM cliente WHERE 1";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		echo 'Clientes borrados.<br>';
	}
} else {
	echo 'En este Sistema no se permite la eliminación automatica.';
}
