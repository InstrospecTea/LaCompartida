<?
 /* 
	* ANTES DE CORRER ESE SCRIPT ES IMPORTANTE AGREGAR LA COLUMNA ID_FACTURA_LEMONTECH
	* A LA TABLA TBFI_HORAS PARA QUE EL SCRIPT TIENE REFERENCIA AL TRABAJO 
	* ESPECIFICO
	* Hay que agregar despcipcion_factura y tipo_documento_local a la tabla de cobro en la base de datos de destino y luego de la execucion de todo  el proceso eliminarlo
	*/ 

	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/implementacion/0_instrucciones_y_configuraciones.php';
		
		ini_set("memory_limit","128M");
		set_time_limit(1000);
		
		$dbh2 = @mysql_connect(ConfImplementacion::dbHost(),ConfImplementacion::dbUser(),ConfImplementacion::dbPass()) or die(mysql_error());
		mysql_select_db(ConfImplementacion::dbName()) or mysql_error($dbh2);
		$bd_origen = ConfImplementacion::dbName();
		
		$dbh_destino = @mysql_connect(ConfDestinoBD::dbHost(), ConfDestinoBD::dbUser(),ConfDestinoBD::dbPass()) or die(mysql_error());
		mysql_select_db(ConfDestinoBD::dbName()) or mysql_error($dbh_destino);
		
		// Pasar solamente las facturas que corresponden a facturas activos.
		
		/*
		 *  el nuemero de la factura puede ser nume_doc o id_factu
		 */ 
		for($i=0;$i<500;$i++)
		{
		$query = "SELECT
								id_factura_lemontech,
								t_f.nume_doc as numero,
								IF( t_o.id_ot IS NOT NULL AND t_o.id_ot != '',t_o.id_ot,'9999') as id_ot, 
								IF( t_s.id_staff IS NOT NULL AND t_s.id_staff != '',t_s.id_staff,'9999') as id_staff, 
								GROUP_CONCAT( IF( t_c.id_cliente IS NOT NULL AND t_c.id_cliente != '',t_c.id_cliente,'9999') SEPARATOR '//' ) as clientes, 
								IF(t_f.importe IS NOT NULL,t_f.importe,'0') as importe, 
								IF(t_f.Pct_IGV IS NOT NULL,t_f.Pct_IGV,'0') as Pct_IGV, 
								IF(t_f.igv IS NOT NULL,t_f.igv,'0') as igv, 
								IF(t_f.Total IS NOT NULL,t_f.Total,'0') as Total, 
								IF(t_f.fecha IS NOT NULL AND t_f.fecha != '',t_f.fecha,'1900-01-01') as fecha, 
								IF(t_f.fecha_ini IS NOT NULL,t_f.fecha_ini,'1900-01-01') as fecha_ini, 
								IF(t_f.fecha_fin IS NOT NULL,t_f.fecha_fin,'1900-01-01') as fecha_fin, 
								IF(t_f.moneda IS NOT NULL,t_f.moneda,'9999') as moneda, 
								IF(t_T.sunat_compra IS NOT NULL AND t_T.sunat_compra != 0 AND t_T.sunat_compra != '',t_T.sunat_compra,'2.766') as tipo_cambio, 
								IF(t_f.ind_activo='S','1','0') as activo,
								t_fd.descripcion as descripcion_factura,
								t_f.ind_pagado as ind_pagado,
 								t_f.id_tipdoc as tipo_documento_legal 
							FROM ".$bd_origen.".tbfi_factura as t_f
							LEFT JOIN ".$bd_origen.".tbfi_facturadet as t_fd ON t_fd.id_factu_num=t_f.id_factu_num  
							LEFT JOIN ".$bd_origen.".tbfi_ot as t_o ON t_f.id_ot=t_o.id_ot AND t_o.id_cia = t_f.id_cia 
							LEFT JOIN ".$bd_origen.".tbad_staff as t_s ON t_s.id_staff=t_f.id_staff 
							LEFT JOIN ".$bd_origen.".tbad_clientes as t_c ON t_c.id_cliente=t_f.id_cliente 
							LEFT JOIN ".$bd_origen.".tbge_TipoCambio as t_T ON t_T.fecha=t_f.fecha
							WHERE id_factura_lemontech > 1390 + ".(20*$i)." AND id_factura_lemontech < 1390 +1+".(20*(1+($i-0)))." 
							GROUP BY id_factura_lemontech";
		$resp = mysql_query($query,$dbh2) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh2);
		
		unset($query_ingreso);
		while( $row = mysql_fetch_assoc($resp) )
		{
			if( empty($query_ingreso) )
				{
					$query_ingreso = "INSERT INTO cobro ( id_cobro, id_usuario, codigo_cliente, 
																				monto_subtotal, porcentaje_impuesto, porcentaje_impuesto_gastos, impuesto, 
																				monto_contrato, monto_trabajos, monto, 
																				monto_thh, monto_thh_estandar, subtotal_gastos, 
																				impuesto_gastos, monto_gastos, fecha_cobro, fecha_ini,
																				fecha_fin, id_moneda, id_moneda_monto, id_moneda_base, 
																				fecha_creacion, id_contrato, opc_moneda_total, 
																				forma_cobro, estado, documento, descripcion_factura, tipo_documento_legal,
																				ind_pagado ) 
														VALUES ";
				 $query_cobro_moneda = "INSERT INTO cobro_moneda ( id_cobro, id_moneda, tipo_cambio ) VALUES ";
				 $query_cobro_asunto = "INSERT INTO cobro_asunto ( id_cobro, codigo_asunto, id_moneda ) VALUES ";
				}
			else
				{
					$query_ingreso .= ",";
					$query_cobro_moneda .= ",";
					$query_cobro_asunto .= ",";
				}
				
				$clientes = explode('//',$row['clientes']);
				if( is_array($clientes) )
					$cliente = $clientes[0];
				else
					$cliente = $clientes;
				
				if( $row['activo']=='1' )
					$estado = 'EN REVISION';
				else
					$estado = 'CREADO';
				
				if(substr($cliente,-4)=='1220') $cliente = '9999';
				$id_contrato = $row['id_ot'] + 2000;
				
				$query_con = "SELECT count(*) FROM contrato WHERE id_contrato = '".$id_contrato."'";
				$resp_con = mysql_query($query_con,$dbh_destino) or Utiles::errorSQL($query_con,__FILE__,__LINE__,$dbh_destino);
				list($cont) = mysql_fetch_array($resp_con);
				if($cont == 0) $id_contrato = '9999';
			
			$query_ingreso .= "( ".$row['id_factura_lemontech']." , '".$row['id_staff']."', '".substr($cliente,-4)."', '".$row['importe']."',
													 '".$row['Pct_IGV']."', '".$row['Pct_IGV']."', '".$row['igv']."', '".$row['importe']."', '".$row['importe']."',
													 '".$row['Total']."', '".$row['Total']."', '".$row['Total']."','0','0','0','".$row['fecha']."',
													 '".$row['fecha_ini']."', '".$row['fecha_fin']."', '".$row['moneda']."','".$row['moneda']."','1', 
													 '".$row['fecha']."', '".$id_contrato."', '".$row['moneda']."','FLAT FEE', '".$estado."', '".$row['numero']."',
											         '".addslashes($row['descripcion_factura'])."', '".$row['tipo_documento_legal']."', '".$row['ind_pagado']."' )";
			
			$query_cobro_moneda .= "( ".$row['id_factura_lemontech'].", 1, 1 ),
															( ".$row['id_factura_lemontech'].", 2, '".$row['tipo_cambio']."' ),
															( ".$row['id_factura_lemontech'].", 3, '119.07' ),
															( ".$row['id_factura_lemontech'].", 4, '228.25' ),
															( ".$row['id_factura_lemontech'].", 5, '3.741' )";
													
			$query_cobro_asunto .= "( ".$row['id_factura_lemontech'].", '".substr($cliente,-4).'-'.substr($row['id_ot'],-4)."', '".$row['moneda']."' )";
		}
		$sesion = new Sesion();
		/*
		$query = "ALTER TABLE  `cobro` ADD  `descripcion_factura` MEDIUMTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL FIRST ,
											ADD  `tipo_documento_legal` VARCHAR( 10 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER  `descripcion_factura` ,
											ADD  `ind_pagado` VARCHAR( 10 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER `tipo_documento_legal` ;";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		*/
			if( $query_ingreso != '' )
			{
				mysql_query($query_ingreso,$sesion->dbh) or Utiles::errorSQL($query_ingreso,__FILE__,__LINE__,$sesion->dbh);
				mysql_query($query_cobro_moneda,$sesion->dbh) or Utiles::errorSQL($query_cobro_moneda,__FILE__,__LINE__,$sesion->dbh);
				mysql_query($query_cobro_asunto,$sesion->dbh) or Utiles::errorSQL($query_cobro_asunto,__FILE__,__LINE__,$sesion->dbh);
			}
	}
	?>
