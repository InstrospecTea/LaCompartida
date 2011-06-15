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
							IF(asunto.ind_activo='S','1','0') as activo, 
							IF( asunto.id_staff IS NOT NULL AND asunto.id_staff != '', asunto.id_staff, '9999' ) as id_staff, 
							asunto.datemodify, 
							asunto.maximo, 
							asunto.fecha 
						FROM ".$bd_origen.".tbfi_ot as asunto 
						LEFT JOIN ".$bd_origen.".tbad_clientes as cliente ON asunto.id_cliente = cliente.id_cliente";
	$resp = mysql_query($query,$dbh2) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh2);
		
	while( $row = mysql_fetch_array($resp) )
	{
			if( empty($query_ingreso) )
				{
					$query_ingreso = "INSERT IGNORE INTO asunto 
															( codigo_asunto, codigo_asunto_secundario, id_usuario, id_encargado, id_cobrador, 
															  codigo_cliente, glosa_asunto, activo, id_moneda, fecha_creacion, 
															  limite_monto, fecha_modificacion, id_contrato, id_contrato_indep ) 
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
			
			$query_ingreso .= "( '".substr($row['id_cliente'],-4).'-'.substr($id_ot,-4)."', 
													 '".substr($row['id_cliente'],-4).'-'.substr($id_ot,-4)."',
													 '".$row['id_staff']."', '".$row['id_staff']."', '".$row['id_staff']."',
													 '".substr($row['id_cliente'],-4)."', '".addslashes($row['descripcion'])."',
													 '".$row['activo']."', '".$row['moneda']."', '".$row['fecha']."',
													 '".$row['maximo']."', '".$row['datemodify']."', '".$id_contrato."', 
													 '".$id_contrato."' )";
	}
	mysql_close($dbh2);
		
	$sesion = new Sesion();
		
	if( !empty($query_ingreso) )
		{
			$query_ingreso .= ", ( '9999-9999','9999-9999', '9999', '9999', '9999', '9999', 
														 'Otro asunto para asociar datos vinculados a asuntos que ya no existen',
														 '0','1','1900-01-01','','1900-01-01','9999','9999' )";
			mysql_query($query_ingreso,$dbh_destino) or Utiles::errorSQL($query_ingreso,__FILE__,__LINE__,$dbh_destino);
		}
		
	echo "Asuntos insertados!<br>\r\n";
	?>
