<?
 /* 
	* ANTES DE CORRER ESE SCRIPT ES IMPORTANTE AGREGAR LA COLUMNA ID_FACTURA_LEMONTECH
	* A LA TABLA TBFI_FACTURAPAGO 
	* 
	*/ 
	
		require_once dirname(__FILE__).'/../conf.php';
		require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
		require_once Conf::ServerDir().'/../app/classes/Documento.php';
		require_once Conf::ServerDir().'/../app/classes/Debug.php';
		require_once Conf::ServerDir().'/../app/implementacion/0_instrucciones_y_configuraciones.php';
		
		ini_set("memory_limit","128M");
		set_time_limit(1000);
		
		$dbh2 = @mysql_connect(ConfImplementacion::dbHost(),ConfImplementacion::dbUser(),ConfImplementacion::dbPass()) or die(mysql_error());
		mysql_select_db(ConfImplementacion::dbName()) or mysql_error($dbh2);
		$bd_origen = ConfImplementacion::dbName();
		
		$dbh_destino = @mysql_connect(ConfDestinoBD::dbHost(), ConfDestinoBD::dbUser(),ConfDestinoBD::dbPass()) or die(mysql_error());
		mysql_select_db(ConfDestinoBD::dbName()) or mysql_error($dbh_destino);
		
		
		$query = "	SELECT 
									fp.id_factura_lemontech as id_factura_lemontech, 
									fp.total_moneda as total_moneda, 
									IF(fp.moneda IS NOT NULL AND fp.moneda != '',fp.moneda,'1') as moneda, 
									IF(fp.fecha IS NOT NULL AND fp.fecha != '',fp.fecha,'1900-01-01 00:00:00') as fecha 
								FROM ".$bd_origen.".tbfi_facturapago as fp 
								LEFT JOIN ".$bd_origen.".tbfi_factura as f ON fp.id_factura_lemontech=f.id_factura_lemontech 
								LEFT JOIN ".$bd_origen.".tbad_clientes as c ON c.id_cliente=f.id_cliente 
								GROUP BY id_factura_pago ";
								
		$resp = mysql_query($query,$dbh2) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh2);
		
		$contador = 0;
		unset($query_ingreso);
		while( $row = mysql_fetch_assoc($resp) )
		{
			if( empty($query_ingreso) )
				{
					$query_ingreso = "INSERT INTO documento ( id_tipo_documento, codigo_cliente, id_cobro, 
																										monto, honorarios, 
																										id_moneda,id_moneda_base,tipo_doc, 
																										fecha, fecha_creacion, fecha_modificacion ) 
																							VALUES";
				}
			else
					$query_ingreso .= ",";
				
				$query_co = "SELECT count(*), codigo_cliente FROM cobro WHERE id_cobro = '".$row['id_factura_lemontech']."' GROUP BY id_cobro";
				$resp_co = mysql_query($query_co,$dbh_destino) or Utiles::errorSQL($query_co,__FILE__,__LINE__,$dbh_destino);
				list($cont, $codigo_cliente) = mysql_fetch_array($resp_co);
				
				if( $cont == 0 ) {
					$row['id_factura_lemontech'] = '1';
					$cliente = '9999';
				}
				else
					$cliente = $codigo_cliente;
					
				if( in_array($row['moneda'], array('4','04','004','0004') ) ) $row['moneda'] = '1';
				
			$query_ingreso .= "( '2' , '".$cliente."', '".$row['id_factura_lemontech']."',
													 '-".$row['total_moneda']."', '-".$row['total_moneda']."', '".$row['moneda']."',
													 '1', 'T', '".$row['fecha']."', '".$row['fecha']."', '".$row['fecha']."' )";
			$contador++;
		}
		
		
		echo 'Se ingresarán '.$contador.' documentos!!';
		if( $query_ingreso != '' )
			$resp = mysql_query($query_ingreso,$dbh_destino) or Utiles::errorSQL($query_ingreso,__FILE__,__LINE__,$dbh_destino);
		
		$sesion = new Sesion();
		echo 'Pagos ingresados!!!!';
		
		$documento_pago = new Documento($sesion);
		$documento_cobro = new Documento($sesion);
		$resp = true;
			if($resp)
				{
					$query = "SELECT id_documento FROM documento WHERE tipo_doc != 'N' AND id_documento > 59786 ";
					$resp = mysql_query($query,$dbh_destino) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh_destino);
					
					while( list($id_doc_pago) = mysql_fetch_array($resp) )
					{
						$documento_pago->Load($id_doc_pago);
						if( $documento_pago->fields['id_cobro'] )
						{
							$query2 = "SELECT id_documento FROM documento WHERE id_cobro = ".$documento_pago->fields['id_cobro']." AND tipo_doc='N'";
							$resp2 = mysql_query($query2,$dbh_destino) or Utiles::errorSQL($query2,__FILE__,__LINE__,$dbh_destino);
							list($id_doc_cobro) = mysql_fetch_array($resp2);
							
							$query2 = "UPDATE cobro SET estado = 'PAGADO', fecha_cobro = '".$documento_pago->fields['fecha']."' WHERE id_cobro = '".$documento_pago->fields['id_cobro']."'";
							mysql_query($query2,$dbh_destino) or Utiles::errorSQL($query2,__FILE__,__LINE__,$dbh_destino);
							
							$documento_cobro->Load($id_doc_cobro);
							
							if( $documento_cobro->fields['id_cobro'] )
							{
								$query3 = "SELECT SUM( monto ) FROM documento WHERE id_cobro = ".$documento_cobro->fields['id_cobro'];
								$resp3 = mysql_query($query3,$dbh_destino) or Utiles::errorSQL($query3,__FILE__,__LINE__,$dbh_destino);
								list($saldo_honorarios) = mysql_fetch_array($resp3);
								
								$documento_cobro->Edit('saldo_honorarios',$saldo_honorarios);
								$documento_cobro->Write();
								
								$neteo_documento = new NeteoDocumento($sesion);
								$neteo_documento->Edit('id_documento_cobro',$documento_cobro->fields['id_documento']);
								$neteo_documento->Edit('id_documento_pago',$documento_pago->fields['id_documento']);
								$neteo_documento->Edit('valor_cobro_honorarios',$documento_cobro->fields['honorarios']);
								$neteo_documento->Edit('valor_pago_honorarios',abs((-1)*$documento_pago->fields['monto'])>0 ? (-1)*$documento_pago->fields['monto'] : "0");
								$neteo_documento->Write();
								
								$cantidad_coneciones++;
							}
						}
					}
				}
	?>
