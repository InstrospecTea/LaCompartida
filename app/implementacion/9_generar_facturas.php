<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../app/implementacion/0_instrucciones_y_configuraciones.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Documento.php';
	$sesion = new Sesion();
	
	ini_set("memory_limit","128M");
	
	$query = "SELECT id_documento_legal, codigo FROM prm_documento_legal";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	$array_codigo_a_id = array();
	while( list($id,$codigo)=mysql_query($resp) )
	{
		$array_codigo_a_id[$codigo]=$id;
	}
	
	$query = "SELECT id_cobro FROM cobro";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,$sesion->dbh);
	
	while( list($id_cobro) = mysql_fetch_array($resp) )
	{
		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);
		
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);
			
			$query2 = "SELECT id_documento_legal FROM prm_documento_legal WHERE codigo = '".$cobro->fields['tipo_documento_legal']."'";
			$resp2 = mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
			list($id_documento_legal) = mysql_fetch_array($resp2);
			
				$factura = new Factura($sesion);
				$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $id_cobro);
				$x_gastos 		= UtilesApp::ProcesaGastosCobro($sesion, $id_cobro);
				//Documentos
				$factura->Edit('RUT_cliente',$cliente->fields['rut']);
				$factura->Edit('direccion_cliente',$cliente->fields['dir_calle']);
				$factura->Edit('cliente',$cliente->fields['glosa_cliente']);
				$factura->Edit('numero',$cobro->fields['documento']);
				$factura->Edit('estado',$cobro->fields['ind_pagado']);
				$factura->Edit('codigo_cliente',$cobro->fields['codigo_cliente']);
				$factura->Edit('descripcion',$cobro->fields['descripcion_factura']);
				$factura->Edit('id_cobro',$cobro->fields['id_cobro']);
				$factura->Edit('id_moneda',$cobro->fields['opc_moneda_total']);
				$factura->Edit('id_documento_legal',$id_documento_legal);
				$factura->Edit('iva', $x_resultados['monto_iva'][$cobro->fields['opc_moneda_total']]);
				$factura->Edit('subtotal', $x_resultados['monto_subtotal'][$cobro->fields['opc_moneda_total']]);
				$factura->Edit('subtotal_gastos',$x_gastos['subtotal_gastos_con_impuestos']? $x_gastos['subtotal_gastos_con_impuestos']:'0');
				$factura->Edit('subtotal_gastos_sin_impuesto',$x_gastos['subtotal_gastos_sin_impuestos']? $x_gastos['subtotal_gastos_sin_impuestos']:'0');
				$factura->Edit('descuento_honorarios',$x_resultados['descuento'][$cobro->fields['opc_moneda_total']]);
				$factura->Edit('subtotal_sin_descuento',(string)number_format($x_resultados['monto_subtotal'][$cobro->fields['opc_moneda_total']]-$x_resultados['descuento'][$cobro->fields['opc_moneda_total']],$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales'],'.',''));
				$factura->Edit('honorarios',$x_resultados['monto'][$cobro->fields['opc_moneda_total']]);
				$factura->Edit('total',$x_resultados['monto_cobro_original_con_iva'][$cobro->fields['opc_moneda_total']]);
				$factura->Edit('gastos',$x_resultados['monto_gastos'][$cobro->fields['opc_moneda_total']]);
				$factura->Edit('fecha',$cobro->fields['fecha_cobro']? $cobro->fields['fecha_cobro']:$cobro->fields['fecha_fin']);
				if( $factura->Write() )
				{
					$documento = new Documento($sesion);
					if( $documento->LoadByCobro($id_cobro) )
					{
						$valores = array( 
							$factura->fields['id_factura'],
							$id_cobro,
							$documento->fields['id_documento'],
							$factura->fields['subtotal_sin_descuento']+$factura->fields['subtotal_gastos']+$factura->fields['subtotal_gastos_sin_impuesto'],
							$factura->fields['iva'],
							$documento->fields['id_moneda'],
							$documento->fields['id_moneda']
						);
						
						$query = "DELETE FROM factura_cobro WHERE id_factura = '".$factura->fields['id_factura']."' AND id_cobro = '".$id_cobro."' ";
						mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						
						$query = "INSERT INTO factura_cobro (id_factura, id_cobro, id_documento, monto_factura, impuesto_factura, id_moneda_factura, id_moneda_documento)
											VALUES ('".implode("','",$valores)."')";
						mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					}
				}
	}
	
	$query = "ALTER TABLE `cobro`
									  DROP `descripcion_factura`,
									  DROP `tipo_documento_legal`,
									  DROP `ind_pagado`;";
	mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	$queries = array();
	$queries[] = "UPDATE factura SET estado = 'ANULADO' WHERE estado = 'A'";
	$queries[] = "UPDATE factura SET estado = 'DADA DE BAJA' WHERE estado = 'B'";
	$queries[] = "UPDATE factura SET estado = 'COBRADA' WHERE estado = 'C'";
	$queries[] = "UPDATE factura SET estado = 'CANJEADA POR LETRA' WHERE estado = 'L'";
	$queries[] = "UPDATE factura SET estado = 'COMPENSADO' WHERE estado = 'M'";
	$queries[] = "UPDATE factura SET estado = 'NOTA DE CREDITO' WHERE estado = 'N'";
	$queries[] = "UPDATE factura SET estado = 'OBSEQUIOS' WHERE estado = 'O'";
	$queries[] = "UPDATE factura SET estado = 'PENDIENTE' WHERE estado = 'P'";
	$queries[] = "UPDATE factura SET estado = 'REGULARIZAR' WHERE estado = 'R'";
	
	foreach($queries as $query)
		{
			mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		}
	
?>
