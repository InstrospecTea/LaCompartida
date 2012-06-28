<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../app/interfaces/conf_migracion.php';
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

*/

	if( method_exists('ConfMigracion','QueryCliente') && ConfMigracion::QueryCliente() != "" )
	{
		$responseCliente = mysql_query(ConfMigracion::QueryCliente(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryCliente(),__FILE__,__LINE__,$dbhOrigen);
		$migracion->Query2ObjetosCliente($responseCliente);
	}

	mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
	if( method_exists('ConfMigracion','QueryAsunto') && ConfMigracion::QueryAsunto() != "" )
	{
		$responseAsunto = mysql_query(ConfMigracion::QueryAsunto(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryAsunto(),__FILE__,__LINE__,$dbhOrigen);
		$migracion->Query2ObjetoAsunto($responseAsunto);
	}
	/*
	if( method_exists('ConfMigracion','QueryHoras') && ConfMigracion::QueryHoras() )
	{
		$responseHoras = mysql_query(ConfMigracion::QueryHoras(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryHoras(),__FILE__,__LINE__,$dbhOrigen);
		$horas = mysql_fetch_assoc($responseHoras);
	}
	
	if( method_exists('ConfMigracion','QueryGastos') && ConfMigracion::QueryGastos() != "" )
	{
		$responseGastos = mysql_query(ConfMigracion::QueryGastos(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryHoras(),__FILE__,__LINE__,$dbhOrigen);
		$gastos = mysql_fetch_assoc($responseGastos);
	}
	
	if( method_exists('ConfMigracion','QueryCobros') && ConfMigracion::QueryCobros() )
	{
		$responseCobros = mysql_query(ConfMigracion::QueryCobros(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryCobros(),__FILE__,__LINE__,$dbhOrigen);
		$cobros = mysql_fetch_assoc($responseCobros);
	}
	
	if( method_exists('ConfMigracion','QueryFacturas') && ConfMigracion::QueryFacturas() )
	{
		$responseFacturas = mysql_query(ConfMigracion::QueryFacturas(),$dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryFacturas(),__FILE__,__LINE__,$dbhOrigen);
		$facturas = mysql_fetch_assoc($responseFacturas);
	}*/
?>
