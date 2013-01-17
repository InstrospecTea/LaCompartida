<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/classes/CronCobroProgramado.php';

$CronCobroProgramado = new CronCobroProgramado();
$CronCobroProgramado->cobrosPendientes();

/*
	require_once dirname(__FILE__).'/../conf.php';
	require_once dirname(__FILE__).'/../classes/AlertaCron.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
	require_once Conf::ServerDir().'/classes/Observacion.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	//require_once Conf::ServerDir().'/classes/Alerta.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Contrato.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';
	require_once Conf::ServerDir().'/classes/Notificacion.php';
	require_once Conf::ServerDir().'/classes/Tarea.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	//require_once Conf::ServerDir().'/interfaces/graficos/Grafico.php';
	//$dbh = mysql_connect(Conf::dbHost(), Conf::dbUser(), Conf::dbPass());
	//mysql_select_db(Conf::dbName()) or mysql_error($dbh);
	$sesion = new Sesion (null, true);

	$alerta = new Alerta ($sesion);


	$notificacion = new Notificacion($sesion);
	$datos_aviso = array();
	$mensaje = "";
	$mensajes = array();
*/
	/*
	 * vamos a buscar los cobros que se tienen que generar para el día.}
	 */
/*
	$fecha_cron = @date('Y-m-d');
	$query = "SELECT cobro_pendiente.id_cobro_pendiente,cobro_pendiente.monto_estimado, cobro_pendiente.id_contrato
				FROM cobro_pendiente
				WHERE cobro_pendiente.id_cobro IS NULL
					AND DATE_FORMAT(cobro_pendiente.fecha_cobro, '%Y-%m-%d')='".$fecha_cron."'
				ORDER BY cobro_pendiente.fecha_cobro";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	$numrows = mysql_num_rows( $resp );

	if( $numrows > 0 )
	{
		$datos_aviso = array();
		while( list($id_cobro_pendiente, $monto_programado, $id_contrato) = mysql_fetch_array($resp))
		{
			$cobro = new Cobro($sesion);

			$id_proceso_nuevo = $cobro->GeneraProceso();
			$query2 = "SELECT c.forma_cobro, cl.glosa_cliente, GROUP_CONCAT( a.glosa_asunto ) as asuntos  FROM contrato c JOIN cliente cl ON ( c.codigo_cliente = cl.codigo_cliente ) JOIN asunto a ON ( c.id_contrato = a.id_contrato ) WHERE c.id_contrato = " . $id_contrato . " GROUP BY c.id_contrato";
			$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
			list( $forma_cobro, $glosa_cliente, $asuntos ) = mysql_fetch_array($resp2);

			if( $forma_cobro != 'FLAT FEE')
			{
				$monto_programado = '';
			}

			$id_cobro = $cobro->PrepararCobro('', $fecha_cron, $id_contrato, true, $id_proceso_nuevo, $monto_programado, $id_cobro_pendiente);
			if( $id_cobro != null && $id_cobro != '' ){
				$cobro->Load($id_cobro);
				$cobro->GuardarCobro();

				$moneda = new Moneda($sesion);
				$moneda->Load( $cobro->fields['id_moneda'] );


				if( $cobro->fields['id_moneda'] != $cobro->fields['opc_moneda_total']) {
					$moneda_total = new Moneda( $sesion );
					$moneda_total->Load( $cobro->fields['opc_moneda_total']);

					$monto_gastos = $cobro->fields['monto_gastos'] * ( $moneda_total->fields['tipo_cambio'] / $moneda->fields['tipo_cambio'] );
					$monto_final = $moneda->fields['simbolo'] . ' ' . ( $monto_gastos + $cobro->fields['monto'] );
				} else {
					$monto_final = $moneda->fields['simbolo'] . ' ' . ( $cobro->fields['monto'] + $cobro->fields['monto_gastos'] );
				}

				$datos_aviso[$id_contrato] = array( "glosa_cliente" => $glosa_cliente, "monto_programado" => $monto_final, "asuntos" => $asuntos );
			}
		}
		$mensajes = $notificacion->mensajeProgramados( $datos_aviso );

		if( sizeof( $mensajes ) > 0 )
		{
			$alerta->enviarAvisoCobrosProgramados($mensajes, $sesion);
		}
	}

	if($desplegar_correo == 'simeadivinaslafraseeresbrujo')
	{
		echo var_dump($mensajes);
	}

*/

?>
