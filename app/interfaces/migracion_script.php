<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../app/interfaces/migracion_conf.php';
	require_once Conf::ServerDir().'/../app/classes/Migracion.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';

	$sesion = new Sesion();
	$migracion = new Migracion($sesion);

	// Conectarse a la base de datos antiguo
	$dbhOrigen = @mysql_connect(ConfMigracion::dbHost(), ConfMigracion::dbUser(),ConfMigracion::dbPass()) or die(mysql_error());
	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);

	// Nombre de función para revisar datos recibidos: "ImprimirDataEnPantalla"
	
	set_time_limit(0);
	ini_set('memory_limit','256M');
	 
	/*
	if( method_exists('ConfMigracion','DatosPrm') && ConfMigracion::DatosPrm() != "" )
	{
		$migracion->SetDatosParametricos(ConfMigracion::DatosPrm());
	}

	if( method_exists('ConfMigracion','QueryUsuario') && ConfMigracion::QueryUsuario() != "" )
	{
		$responseUsuario = mysql_query(ConfMigracion::QueryUsuario(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryUsuario(),__FILE__,__LINE__,$dbhOrigen);
		//$migracion->ImprimirDataEnPantalla($responseUsuario);
		$migracion->Query2ObjetoUsuario($responseUsuario);
	}


	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryCliente') && ConfMigracion::QueryCliente() != "" )
	{
		$responseCliente = mysql_query(ConfMigracion::QueryCliente(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryCliente(),__FILE__,__LINE__,$dbhOrigen);
		$migracion->Query2ObjetosCliente( $responseCliente, true );
		$migracion->DefinirGruposPRC();
	}


	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryAsunto') && ConfMigracion::QueryAsunto() != "" )
	{
		$responseAsunto = mysql_query(ConfMigracion::QueryAsunto(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryAsunto(),__FILE__,__LINE__,$dbhOrigen);
		$migracion->Query2ObjetoAsunto($responseAsunto);
	}

	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryHoras') && ConfMigracion::QueryHoras() )
	{
		$query = "ALTER TABLE  `trabajo` DROP FOREIGN KEY  `trabajo_ibfk_4` ;";
		mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		
		$responseHoras = mysql_query(ConfMigracion::QueryHoras(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryHoras(),__FILE__,__LINE__,$dbhOrigen);
		$migracion->Query2ObjetoHora($responseHoras);
		//$horas = mysql_fetch_assoc($responseHoras);
		//$migracion->AgregarHoras($responseHoras);
	}


	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryGastos') && ConfMigracion::QueryGastos() != "" )
	{
		$responseGastos = mysql_query(ConfMigracion::QueryGastos(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryGastos(),__FILE__,__LINE__,$dbhOrigen);
		$migracion->Query2ObjetoGasto($responseGastos);
	}



	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryCobros') && ConfMigracion::QueryCobros() )
	{
		$responseCobros = mysql_query(ConfMigracion::QueryCobros(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryCobros(), __FILE__, __LINE__, $dbhOrigen);
		$migracion->Query2ObjetoCobro($responseCobros);
		$migracion->EmitirCobros();
	}

	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryTarifas') && ConfMigracion::QueryTarifas() )
	{
		$responseTarifas = mysql_query(ConfMigracion::QueryTarifas(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryTarifas(), __FILE__, __LINE__, $dbhOrigen);
		$migracion->Query2ObjetoTarifa($responseTarifas);
	}
	
	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryUsuariosTarifas') && ConfMigracion::QueryUsuariosTarifas() )
	{
		$responseTarifasUsuario = mysql_query(ConfMigracion::QueryUsuariosTarifas(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryUsuariosTarifas(), __FILE__, __LINE__, $dbhOrigen);
		$migracion->Query2ObjetoUsuarioTarifa($responseTarifasUsuario);
	}

	$migracion->EmitirFacturas();

	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryFacturas') && ConfMigracion::QueryFacturas() )
	{
		$responseFacturas = mysql_query(ConfMigracion::QueryFacturas(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryFacturas(),__FILE__,__LINE__,$dbhOrigen);
		$migracion->Query2ObjetoFactura($responseFacturas);
	}
	*/
	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryPagos') && ConfMigracion::QueryPagos() )
	{
		$responsePagos = mysql_query(ConfMigracion::QueryPagos(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryPagos(),__FILE__,__LINE__,$dbhOrigen);
		$migracion->Query2ObjetoPago($responsePagos);
		//$migracion->ImprimirDataEnPantalla($responsePagos);
	}
?>
