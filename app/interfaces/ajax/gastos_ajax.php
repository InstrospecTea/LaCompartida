<?php

	require_once dirname(__FILE__).'/../../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	 require_once Conf::ServerDir().'/classes/UtilesApp.php';
         require_once Conf::ServerDir().'/classes/Cliente.php';
 
	$sesion = new Sesion(array('ADM'));


		if($orden == "")
			$orden = "fecha DESC";

		if($where != '')
		{
			$where = 1;
                
			if( UtilesApp::GetConf($sesion,'CodigoSecundario') )
				{
					if( $codigo_cliente_secundario )
					{
							$where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario'";
							$cliente = new Cliente($sesion);
							$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
						if($codigo_asunto_secundario)
						{
							$asunto = new Asunto($sesion);
							$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
							$query_asuntos = "SELECT codigo_asunto_secundario FROM asunto WHERE id_contrato = '".$asunto->fields['id_contrato']."' ";
							$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos,__FILE__,__LINE__,$sesion->dbh);
							$asuntos_list_secundario = array();
							while( list($codigo) = mysql_fetch_array($resp) )
							{
								array_push($asuntos_list_secundario,$codigo);
							}
							$lista_asuntos_secundario = implode("','", $asuntos_list_secundario);
						}
					}
				}
				else 
				{
					if( $codigo_cliente )
					{
							$where .= " AND cta_corriente.codigo_cliente = '$codigo_cliente'";
							$cliente = new Cliente($sesion);
							$cliente->LoadByCodigo($codigo_cliente);
						if($codigo_asunto)
						{
							$asunto = new Asunto($sesion);
							$asunto->LoadByCodigo($codigo_asunto);
							$query_asuntos = "SELECT codigo_asunto FROM asunto WHERE id_contrato = '".$asunto->fields['id_contrato']."' ";
							$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos,__FILE__,__LINE__,$sesion->dbh);
							$asuntos_list = array();
							while( list($codigo) = mysql_fetch_array($resp) )
							{
								array_push($asuntos_list,$codigo);
							}
							$lista_asuntos = implode("','", $asuntos_list);
						}
					}
				}
			if( $fecha1 != '' ) $fecha_ini = Utiles::fecha2sql($fecha1); else $fecha_ini = '';
			if( $fecha2 != '' ) $fecha_fin = Utiles::fecha2sql($fecha2); else $fecha_fin = '';
			
			if($cobrado == 'NO')
				$where .= " AND cta_corriente.id_cobro is null ";
			if($cobrado == 'SI')
				$where .= " AND cta_corriente.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado='INCOBRABLE') ";
			if($codigo_asunto && $lista_asuntos)
				$where .= " AND cta_corriente.codigo_asunto IN ('$lista_asuntos')";
			if($codigo_asunto_secundario && $lista_asuntos_secundario)
				$where .= " AND asunto.codigo_asunto_secundario IN ('$lista_asuntos_secundario')";
			if($id_usuario_orden)
				$where .= " AND cta_corriente.id_usuario_orden = '$id_usuario_orden'";
			if($id_usuario_responsable)
				$where .= " AND contrato.id_usuario_responsable = '$id_usuario_responsable' ";
			if($id_tipo)
				$where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
			if($clientes_activos == 'activos')
				$where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
			if($clientes_activos == 'inactivos')
				$where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
			if($fecha1 && $fecha2)
				$where .= " AND cta_corriente.fecha BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
			else if($fecha1)
				$where .= " AND cta_corriente.fecha >= '".Utiles::fecha2sql($fecha1)."' ";
			else if($fecha2)
				$where .= " AND cta_corriente.fecha <= '".Utiles::fecha2sql($fecha2)."' ";
			else if(!empty($id_cobro))
				$where .= " AND cta_corriente.id_cobro='$id_cobro' ";
			
			// Filtrar por moneda del gasto
			if ($moneda_gasto != '') 	$where .= " AND cta_corriente.id_moneda=$moneda_gasto ";
		} else {
			$where = base64_decode($where);
                }
             if($where=='') $where=1;
               
		$idioma_default = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
		$idioma_default->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
		
		$total_cta = number_format(UtilesApp::TotalCuentaCorriente($sesion, $where),0,$idioma_default->fields['separador_decimales'],$idioma_default->fields['separador_miles']);

		
		$col_select =",'Si' as esCobrable ";
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
		{
			$col_select = " ,if(cta_corriente.cobrable = 1,'Si','No') as esCobrable ";
		}

		$query = "SELECT SQL_BIG_RESULT SQL_NO_CACHE 
									cta_corriente.id_movimiento,
									cta_corriente.fecha,
									cta_corriente.egreso, 
									cta_corriente.ingreso, 
									cta_corriente.monto_cobrable, 
									ifnull(cta_corriente.codigo_cliente,'-') codigo_cliente, 
									ifnull(cta_corriente.numero_documento,'-') numero_documento,
									cta_corriente.numero_ot,
									ifnull(cta_corriente.descripcion,'-') descripcion,
									cta_corriente.id_cobro,
									ifnull(asunto.glosa_asunto,'-') glosa_asunto,
									ifnull(cliente.glosa_cliente,'-') glosa_cliente, 
									prm_moneda.simbolo,
									prm_moneda.cifras_decimales,
									prm_cta_corriente_tipo.glosa as tipo, 
									cobro.estado, 
									cta_corriente.con_impuesto,
									prm_idioma.codigo_idioma,
                                                                        contrato.activo AS contrato_activo, 1 as opcion
									$col_select
								FROM cta_corriente
								LEFT JOIN asunto USING(codigo_asunto)
								LEFT JOIN prm_idioma ON asunto.id_idioma = prm_idioma.id_idioma 
								LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
								LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
								LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro
								LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
								LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo=prm_cta_corriente_tipo.id_cta_corriente_tipo
								JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
								WHERE $where ";
		
		
		
		  $resp = mysql_unbuffered_query($query, $sesion->dbh);
		//  $rows=mysql_num_rows($resp);
		
	$i=0;
		
        
        
             /*"iTotalRecords":"'.$rows.'",
             "iTotalDisplayRecords":"'.$rows.'",    
             echo '{"sEcho": 1, "aaData": [';   */ 
             echo '{ "aaData": [';
		while($fila = mysql_fetch_array($resp)) {
		     
		if ($i!=0) echo ',';
                $i++;
		   
		    $stringarray=array(
			date('d-m-Y',strtotime($fila['fecha'])),
			$fila['glosa_cliente']? removeBOM($fila['glosa_cliente']):' - ',
			$fila['glosa_asunto']? removeBOM($fila['glosa_asunto']):' - ',
			    $fila['tipo']? $fila['tipo']:' - ',
			    $fila['descripcion']? removeBOM($fila['descripcion']):' ',
	($fila['egreso']? $fila['simbolo'].' '.$fila['egreso']:' '),
			   $fila['ingreso']? $fila['simbolo'].' '.$fila['ingreso']:' ',
			   $fila['con_impuesto']? $fila['con_impuesto']:' ',
			   $fila['id_cobro']? $fila['id_cobro']:' ',
			   $fila['estado']? $fila['estado']:' ',
			   $fila['cobrable']? $fila['cobrable']:' ',
			   $fila['contrato_activo']? $fila['contrato_activo']:' ',
			   $fila['id_movimiento']);
			
			    
		 
                         echo json_encode($stringarray);    
	    }
		
	 echo "] }";
	
	/*$arrayfinal=array(
	  "iEcho"=>1,
	   "iTotalRecords"=>$rows,
	  "iTotalDisplayRecords"=>$rows, 
	    "aaData" => $stringarray
	);
	
	 echo json_encode($arrayfinal);*/
        
		function Monto(& $fila)
		{
			global $sesion;
			$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
			if( $fila->fields['codigo_idioma'] != '' )
				$idioma->Load($fila->fields['codigo_idioma']);
			else
				$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
			return $fila->fields['egreso'] > 0 ? $fila->fields[simbolo] . " " .number_format($fila->fields['monto_cobrable'],$fila->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : '';
		}
                
                 function removeBOM($string) {
            return str_replace(array('\n',"\n"),'',$string);
             }
         

		function Ingreso(& $fila)
		{
			global $sesion;
			$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
			if( $fila->fields['codigo_idioma'] != '' )
				$idioma->Load($fila->fields['codigo_idioma']);
			else
				$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
			return $fila->fields['ingreso'] > 0 ? $fila->fields['simbolo'] . " " .number_format($fila->fields['monto_cobrable'],$fila->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : '';
		}
	

?>

