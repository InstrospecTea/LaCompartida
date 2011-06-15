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
	
	
	$query = "SELECT 
							id_cliente, 
							empresa, 
							IF(moneda IS NOT NULL, moneda, '1') as moneda, 
							IF(ind_activo='S','1','0') as activo, 
							IF( id_staff IS NOT NULL, id_staff, '9999' ) as id_staff,
							contacto,
							domicilio,
							num_docid as rut,
							observaciones
						FROM ".$bd_origen.".tbad_clientes";
	$resp = mysql_query($query,$dbh2) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh2);
	
	while( $row = mysql_fetch_array($resp) )
	{
		if( empty($query_ingreso) )
				{
					$query_ingreso = "INSERT IGNORE INTO cliente 
															( glosa_cliente, rsocial, codigo_cliente, codigo_cliente_secundario, id_contrato, 
																id_moneda, activo, dir_calle, nombre_contacto, id_usuario_encargado, rut ) 
														VALUES ";
					$query_ingreso_contrato = "INSERT IGNORE INTO contrato 
																			( id_contrato, glosa_contrato, codigo_cliente, id_usuario_responsable, 
																				id_moneda, id_moneda_tramite, id_moneda_monto, opc_moneda_total, 
																				activo, id_tarifa, usa_impuesto_separado, usa_impuesto_gastos, observaciones, rut, factura_razon_social, factura_direccion ) 
																		 VALUES ";
				}
			else
				{
					$query_ingreso .= ",";
					$query_ingreso_contrato .= ",";
				}
			if( $row['activo'] == '1' )
				$activo_contrato = 'SI';
			else
				$activo_contrato = 'NO';
			$query_ingreso .= "( '".addslashes($row['empresa'])."' , '".addslashes($row['empresa'])."' , '".substr($row['id_cliente'],-4)."',
													 '".substr($row['id_cliente'],-4)."', '".$row['id_cliente']."','".$row['moneda']."',
													 '".$row['activo']."','".addslashes($row['domicilio'])."',
													 '".addslashes($row['contacto'])."', '".$row['id_staff']."', '".$row['rut']."' )";
			$query_ingreso_contrato .= "( '".$row['id_cliente']."', '', '".substr($row['id_cliente'],-4)."', '".$row['id_staff']."',
																		'".$row['moneda']."', '".$row['moneda']."', '".$row['moneda']."', '".$row['moneda']."',
																		'".$activo_contrato."', '1', '1', '1', '".addslashes($row['observaciones'])."', '".$row['rut']."' 
																		, '".addslashes($row['empresa'])."' ,'".addslashes($row['domicilio'])."')";
	}
	mysql_close($dbh2);
	
	$sesion = new Sesion();
	
	if( !empty($query_ingreso) )
		{
			$query_ingreso .= ", ( 'Otro cliente para asociar datos vinculado a clientes que no existen','', '9999','9999','9999','1', '0', '', '', '9999','')";
			$query_ingreso_contrato .= ", ( '9999', '','9999', '9999','1','1','1','1','0','1','1','1', '', '', '', '')";
			mysql_query($query_ingreso,$dbh_destino) or Utiles::errorSQL($query_ingreso,__FILE__,__LINE__,$dbh_destino);
			mysql_query($query_ingreso_contrato,$dbh_destino) or Utiles::errorSQL($query_ingreso_contrato,__FILE__,__LINE__,$dbh_destino);
		}
	?>
