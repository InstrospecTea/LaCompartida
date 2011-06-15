<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/implementacion/0_instrucciones_y_configuraciones.php';
		
	set_time_limit(1000);
		
	$dbh2 = @mysql_connect(ConfImplementacion::dbHost(), ConfImplementacion::dbUser(),ConfImplementacion::dbPass()) or die(mysql_error());
	mysql_select_db(ConfImplementacion::dbName()) or mysql_error($dbh2);
	$bd_origen = ConfImplementacion::dbName();
	
	$dbh_destino = @mysql_connect(ConfDestinoBD::dbHost(), ConfDestinoBD::dbUser(),ConfDestinoBD::dbPass()) or die(mysql_error());
	mysql_select_db(ConfDestinoBD::dbName()) or mysql_error($dbh_destino);
	
	$query_asuntos_id_duplicado = "SELECT id_ot FROM ".$bd_origen.".tbfi_ot GROUP BY id_ot HAVING count(*)>1";
	$resp = mysql_query($query_asuntos_id_duplicado,$dbh2) or Utiles::errorSQL($query_asuntos_id_duplicado,__FILE__,__LINE__,$dbh2);
		
	$asuntos_duplicados = array();
	while( list($id_ot) = mysql_fetch_array($resp) )
	{
		$asuntos_duplicados[$id_ot] = 'modificar';
	}
	
	$query = "SELECT 
							IF(cliente.id_cliente IS NOT NULL AND cliente.id_cliente != '',cliente.id_cliente,'9999') as id_cliente, 
							asunto.id_ot as id_ot, 
							asunto.descripcion, 
							IF(asunto.moneda IS NOT NULL, asunto.moneda, '1') as moneda, 
							IF(asunto.ind_activo='S','SI','NO') as activo, 
							IF( asunto.id_staff IS NOT NULL AND asunto.id_staff != '', asunto.id_staff, '9999' ) as id_staff, 
							cliente.observaciones as observaciones, 
							asunto.datemodify, 
							asunto.maximo, 
							asunto.fecha, 
							cliente.num_docid as rut, 
							cliente.empresa as rsocial,
							cliente.domicilio as domicilio
						FROM ".$bd_origen.".tbfi_ot as asunto 
						LEFT JOIN ".$bd_origen.".tbad_clientes as cliente ON asunto.id_cliente = cliente.id_cliente";
	$resp = mysql_query($query,$dbh2) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh2);
		
	$query_ingreso = '';
		
	while( $row = mysql_fetch_array($resp) )
	{
		if( $query_ingreso=='' )
				{
					$query_ingreso = "INSERT IGNORE INTO contrato 
															( id_contrato, glosa_contrato, codigo_cliente, id_usuario_responsable, 
																id_moneda, id_moneda_tramite, id_moneda_monto, opc_moneda_total, 
																activo, id_tarifa, usa_impuesto_separado, usa_impuesto_gastos, observaciones, rut
																,factura_razon_social, factura_direccion ) 
														VALUES ";
				}
			else
				$query_ingreso .= ",";
				
			if( $asuntos_duplicados[$row['id_ot']] == 'modificar' ) 
				{
					$id_ot = ($row['id_ot']-0)+9900;
					$asuntos_duplicados[$row['id_ot']] = '';
				}
			else
				$id_ot = $row['id_ot'];
				
				$id_contrato = $id_ot + 2000;
				
			if( $id_ot != '9999' )
				$query_ingreso .= "( '".$id_contrato."','".addslashes($row['descripcion'])."' , 
													 '".substr($row['id_cliente'],-4)."',
													 '".$row['id_staff']."', '".$row['moneda']."', '".$row['moneda']."', 
													 '".$row['moneda']."', '".$row['moneda']."',
													 '".$row['activo']."','1','1','1', '".addslashes($row['observaciones'])."', '".$row['rut']."', '".addslashes($row['rsocial'])."','".addslashes($row['domicilio'])."' )";
	}
	mysql_close($dbh2);
		
	$sesion = new Sesion();
		
	if( $query_ingreso != '' )
		{
			mysql_query($query_ingreso,$dbh_destino) or Utiles::errorSQL($query_ingreso,__FILE__,__LINE__,$dbh_destino);
		}
		
	?>
