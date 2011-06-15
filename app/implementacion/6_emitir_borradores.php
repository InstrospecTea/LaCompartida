<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	
	$sesion = new Sesion();
	$cobro = new Cobro($sesion);

ini_set("memory_limit","128M");

	
	set_time_limit(1000);
			$query = "SELECT cobro.id_cobro, cobro.id_usuario, cobro.codigo_cliente, cobro.id_contrato, contrato.id_carta, cobro.estado,
								cobro.opc_papel,contrato.id_carta, cobro.fecha_creacion 
								FROM cobro
								LEFT JOIN contrato ON cobro.id_contrato = contrato.id_contrato
								AND cobro.estado IN ( 'CREADO', 'EN REVISION' )";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
			while($cob = mysql_fetch_assoc($resp))
			{
				if( $cobro->Load($cob['id_cobro']) )
				{
					$cobro->Edit('id_carta',$cob['id_carta']);
					$ret = $cobro->GuardarCobro(true);
					$cobro->Edit('etapa_cobro','5');
					$cobro->Edit('fecha_emision',$cob['fecha_creacion']);
					if( $cob['estado']=='CREADO' )
						$cobro->Edit('estado','INCOBRABLE');
					else
						$cobro->Edit('estado','EMITIDO');
					if( $ret == '' )
					{
						$his = new Observacion($sesion);
						$his->Edit('fecha',date('Y-m-d H:i:s'));
						$his->Edit('comentario',__('COBRO EMITIDO'));
						$his->Edit('id_usuario',$sesion->usuario->fields['id_usuario']);
						$his->Edit('id_cobro',$cobro->fields['id_cobro']);
						$his->Write();
						$cobro->Write();
					}
				}
			}
	?>
