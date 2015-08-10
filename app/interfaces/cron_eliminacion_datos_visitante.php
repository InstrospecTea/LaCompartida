<?php

require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(null, true);
$sesion->usuario = new Usuario($sesion, '99511620');

if ($argv[1] != 'ambienteprueba' && !isset($_GET['ambienteprueba'])) {
	die($argv[1] . $_GET['ambienteprueba']);
}

if (Conf::GetConf($sesion, 'EsAmbientePrueba')) {

	$query = "SET FOREIGN_KEY_CHECKS = 0;";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('FOREIGN_KEY_CHECKS 0');

	/* Query para borrar factura */
	$query = "TRUNCATE TABLE  `cta_cte_fact_mvto_neteo`;";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Neteos borrados');

	/* Query para borrar factura */
	$query = "TRUNCATE TABLE  `cta_cte_fact_mvto`;";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Movimientos borrados');

	/* Query para borrar los neteos de documentos */
	$query = "TRUNCATE TABLE neteo_documento";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Neteos borrado');

	/* Query para borrar las monedas de los documentos */
	$query = "TRUNCATE TABLE documento_moneda";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Documento Moneda borrado');

	/* Query para borrar documentos */
	$query = "DELETE FROM documento WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Documentos borrado');

	/* Query para borrar factura */
	$query = "TRUNCATE TABLE `factura_pago`;";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Facturas Pagos borrado');

	$query = "TRUNCATE TABLE `factura_cobro`;";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Facturas Cobros borrado');

	/* Query para borrar factura */
	$query = "DELETE FROM factura WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Facturas borrado');

	/* Elimina todos los entradas en cobro_asunto */
	$query = "DELETE FROM cobro_asunto WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Todos los datos en cobro_asunto eliminado');

	/* Elimina todos los entradas en cobro_moneda */
	$query = "DELETE FROM cobro_moneda WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Todos los datos en cobro_moneda eliminado');

	/* Borrar cobros movimiento */
	$query = "TRUNCATE TABLE cobro_movimiento";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Cobros Movimientos borrado');

	/* Vuelva cobros emitidios atras a creado */
	$query = " UPDATE cobro SET estado='CREADO' WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	/* Borrar cobros creados por visitantes */
	$query = "DELETE FROM cobro WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Cobros borrado');

	/* Borrar OLAP Liquidaciones */
	$query = "TRUNCATE TABLE olap_liquidaciones";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('OLAP Liquidaciones borrado');

	/* Borrar OLAP Liquidaciones */
	$query = "TRUNCATE TABLE trabajo_historial";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('OLAP Liquidaciones borrado');

	/* Borrar todos los trabajos creados por visitantes */
	$query = "DELETE FROM trabajo WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Trabajos borrado');

	/* Borrar todos los gastos ingresados por visitantes */
	$query = "DELETE FROM cta_corriente WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Gastos borrado');

	/* Borrar todos los trámites ingresados por visitantes */
	$query = "DELETE FROM tramite WHERE 1";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	Debug::pr('Trámites borrado');

	if (Conf::GetConf($sesion, 'TieneTablaVisitante')) {
		/* Busca cualquier usuario */
		$query = "SELECT id_usuario FROM usuario WHERE id_visitante = 0 ORDER BY id_usuario LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($id_usuario) = mysql_fetch_array($resp);

		/* Modifica encargados en asuntos y contrato que tiene relacion con un visitante para poder borrarlos despues */
		$query = "UPDATE asunto
				JOIN usuario AS u ON u.id_usuario=asunto.id_usuario
				LEFT JOIN usuario AS encargado ON encargado.id_usuario=asunto.id_encargado
				LEFT JOIN usuario AS cobrador ON cobrador.id_usuario=asunto.id_cobrador
			SET asunto.id_usuario = {$id_usuario},
				asunto.id_encargado = {$id_usuario},
				asunto.id_cobrador = {$id_usuario}
			WHERE u.id_visitante > 0 OR encargado.id_visitante > 0 OR cobrador.id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, $sesion->dbh);

		$query = "UPDATE contrato
										JOIN usuario AS u ON u.id_usuario=contrato.id_usuario_responsable
										 SET id_usuario_responsable = {$id_usuario}
									 WHERE u.id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		$query = "UPDATE modificaciones_contrato AS mc
										JOIN usuario AS ur ON ur.id_usuario=mc.id_usuario_responsable
										JOIN usuario AS u ON u.id_usuario=mc.id_usuario
										 SET mc.id_usuario_responsable = " . $id_usuario . ",
										 		 mc.id_usuario = " . $id_usuario . "
									 WHERE u.id_visitante > 0 OR ur.id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		/* Borrar todos los usuarios que tienen id_visitante mejor a 0 */
		$query = "DELETE FROM usuario WHERE id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		Debug::pr('Todos los visitantes borrado de la tabla de usuarios dentro del sistema');
		$query = "UPDATE usuario SET activo = 0 WHERE id_visitante > 0";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		Debug::pr('Todos los visitantes desactivados de la tabla de usuarios dentro del sistema'); // por si alguno no se pudo borrar
	}

	if (method_exists('Conf', 'BorrarDatosAdministracion') && Conf::BorrarDatosAdministracion()) {
		/* Borrar todos los tipos trámites ingresados por visitantes */
		$query = "DELETE FROM tramite_tipo WHERE 1";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		Debug::pr('Tipos Trámites borrado');

		/* Borrar todos los asuntos */
		$query = "DELETE FROM asunto WHERE 1";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		Debug::pr('Asuntos borrado');

		/* Borrar todos los contratos */
		$query = "DELETE FROM contrato WHERE 1";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		Debug::pr('Contratos borrados');

		/* Borrar todos los clientes */
		$query = "DELETE FROM cliente WHERE 1";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		Debug::pr('Clientes borrados');
	}
} else {
	Debug::pr('En este Sistema no se permite la eliminación automatica');
}
