<?
    require_once 'conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';


    $sesion = new Sesion('');
    $pagina = new Pagina ($sesion);

		if($accion == "borrar_evento")
		{
			$query = "DELETE FROM events WHERE event_id=".$id;
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
			echo(true);
		}
		else if($accion == "actualizar_evento")
		{
			list($dia_semana, $mes_letra, $dia, $anio, $hora) = split( ' ', $start_date);
			switch($mes_letra) {
						case 'Jan': $mes = '01'; break;
						case 'Feb': $mes = '02'; break;
						case 'Mar': $mes = '03'; break;
						case 'Apr': $mes = '04'; break;
						case 'May': $mes = '05'; break;
						case 'Jun': $mes = '06'; break;
						case 'Jul': $mes = '07'; break;
						case 'Aug': $mes = '08'; break;
						case 'Sep': $mes = '09'; break;
						case 'Oct': $mes = '10'; break;
						case 'Nov': $mes = '11'; break;
						case 'Dec': $mes = '12'; break;
					} 
			$start_date = $anio.'-'.$mes.'-'.$dia.' '.$hora;
			
			list($dia_semana, $mes_letra, $dia, $anio, $hora) = split( ' ', $end_date);
			switch($mes_letra) {
						case 'Jan': $mes = '01'; break;
						case 'Feb': $mes = '02'; break;
						case 'Mar': $mes = '03'; break;
						case 'Apr': $mes = '04'; break;
						case 'May': $mes = '05'; break;
						case 'Jun': $mes = '06'; break;
						case 'Jul': $mes = '07'; break;
						case 'Aug': $mes = '08'; break;
						case 'Sep': $mes = '09'; break;
						case 'Oct': $mes = '10'; break;
						case 'Nov': $mes = '11'; break;
						case 'Dec': $mes = '12'; break;
					} 
			$end_date = $anio.'-'.$mes.'-'.$dia.' '.$hora;
			
			$query = "UPDATE events SET event_name='".$event_text."', start_date='".$start_date."', end_date='".$end_date."' WHERE event_id=".$id;
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
			echo(true);
		}
		else if($accion == "agregar_evento")
		{
			list($dia_semana, $mes_letra, $dia, $anio, $hora) = split( ' ', $start_date);
			switch($mes_letra) {
						case 'Jan': $mes = '01'; break;
						case 'Feb': $mes = '02'; break;
						case 'Mar': $mes = '03'; break;
						case 'Apr': $mes = '04'; break;
						case 'May': $mes = '05'; break;
						case 'Jun': $mes = '06'; break;
						case 'Jul': $mes = '07'; break;
						case 'Aug': $mes = '08'; break;
						case 'Sep': $mes = '09'; break;
						case 'Oct': $mes = '10'; break;
						case 'Nov': $mes = '11'; break;
						case 'Dec': $mes = '12'; break;
					} 
			$start_date = $anio.'-'.$mes.'-'.$dia.' '.$hora;
			
			list($dia_semana, $mes_letra, $dia, $anio, $hora) = split( ' ', $end_date);
			switch($mes_letra) {
						case 'Jan': $mes = '01'; break;
						case 'Feb': $mes = '02'; break;
						case 'Mar': $mes = '03'; break;
						case 'Apr': $mes = '04'; break;
						case 'May': $mes = '05'; break;
						case 'Jun': $mes = '06'; break;
						case 'Jul': $mes = '07'; break;
						case 'Aug': $mes = '08'; break;
						case 'Sep': $mes = '09'; break;
						case 'Oct': $mes = '10'; break;
						case 'Nov': $mes = '11'; break;
						case 'Dec': $mes = '12'; break;
					} 
			$end_date = $anio.'-'.$mes.'-'.$dia.' '.$hora;
			
			$query = "INSERT INTO events( event_name, start_date, end_date, details ) VALUES('".$event_text."','".$start_date."','".$end_date."', 'Trabajo')";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
			echo(true);
		}
		else if($accion == "actualizacion_usuario")
		{
			if( method_exists( 'Conf','ColumnaNotificacion' ) || ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ColumnaNotificacion') != '' ) )
			{
				if(!$id_not) $id_not=0;
				if(!$corr) $corr=0;
			$query = "UPDATE usuario SET id_notificacion_tt=".$id_not."+".$corr." WHERE id_usuario='$id' AND id_notificacion_tt < ".$id_not."+".$corr;
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			echo(true);
			}
			else
			echo(false);
		}
		else if($accion == "cargar_cliente")
		{
			if ( ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) )
			{
				$codigo_asunto = 'asunto.codigo_asunto_secundario';
				$codigo_cliente = 'cliente.codigo_cliente_secundario';
				$join = 'LEFT JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente';
			}
			else
			{
				$codigo_asunto = 'asunto.codigo_asunto';
				$codigo_cliente = 'cliente.codigo_cliente';
				$join = 'LEFT JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente';
			}
			$query = "SELECT DISTINCT $codigo_cliente,glosa_cliente 
									FROM cliente $join"; 
				if( $id != "" )
					$query .= "WHERE cliente.activo=1 AND $codigo_asunto = '$id' ";   // eleciona solo el cliente correspondente al id del asunto
				$query .= "ORDER BY glosa_cliente";
			
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
		for($i = 0; $fila = mysql_fetch_assoc($resp); $i++)
	   {
			if($i > 0)
			echo("~");
		   echo(join("|",$fila));
	   }
		if($i == 0)
			echo("VACIO|");
		}
		else if($accion == "cargar_asuntos" || $accion == "cargar_asuntos_desde_campo" )
    {
    	if( $id != "" ) 
	    	$vacio = false;
			else	
				$vacio = true;
 	  	list($accion,$codigo_cli) = split("//",$accion);
    	if( $accion == "cargar_asuntos_desde_campo" && ( ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) ) )
    	{
    		$query = "SELECT codigo_cliente_secundario 
    								FROM cliente 
    								JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente  
    								WHERE codigo_asunto_secundario = '$id' ";
    		$resp = mysql_query( $query,$sesion->dbh ) or Utiles::errorSQL( $query,__FILE__,__LINE__,$sesion->dbh);
    		list( $id )=mysql_fetch_array( $resp );
    		if( !$id ) $vacio = true;
    		else $vacio = false;
    	}
    	elseif( $accion == "cargar_asuntos_desde_campo" )
    	{
    		$query = "SELECT codigo_cliente FROM asunto WHERE codigo_asunto = '$id' ";
    		$resp = mysql_query( $query, $sesion->dbh) or Utiles::errorSQL( $query,__FILE__,__LINE__,$sesion->dbh);
    		list( $id )=mysql_fetch_array( $resp );
    		if( !$id ) $vacio = true;
    		else $vacio = false;
    	}
    
    	if ( ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) )
			{
				$codigo_asunto = 'asunto.codigo_asunto_secundario';
				$codigo_cliente = 'cliente.codigo_cliente_secundario';
				$join = 'LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente ';
				$where = '';
			}
			else
			{
				$codigo_asunto = 'codigo_asunto';
				$codigo_cliente = 'codigo_cliente';
				$join = '';
				$where = '';
			}
	   $query = "SELECT $codigo_asunto,glosa_asunto
				   FROM asunto ".$join;
		if($id != "")
			$query .= " WHERE asunto.activo=1 AND $codigo_cliente = '$id' ";
		elseif( $vacio )
			$query .= " WHERE 1=0 ";
		$query .= " ORDER BY glosa_asunto ";


	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

	   for($i = 0; $fila = mysql_fetch_assoc($resp); $i++)
	   {
			if($i > 0)
				echo("~");
		   echo(join("|",$fila));
	   }
	  if( $vacio ) 
	  	echo("~noexiste");
		if($i == 0)
			echo("VACIO|");
	}
	else if($accion == 'veriguar_codigo_cliente')
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			$query = "SELECT codigo_cliente_secundario FROM cliente
								JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente
								WHERE asunto.codigo_asunto_secundario='$id'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list( $codigo_cliente_secundario ) = mysql_fetch_array($resp);
			
			if( $codigo_cliente_secundario != '' )
			{
				$query = "SELECT count(*) FROM cliente WHERE codigo_cliente_secundario='$codigo_cliente_secundario'";
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($cont) = mysql_fetch_array($resp);
			}
			else 
				$cont=0;
				
			if( $cont > 0 )
				echo $codigo_cliente_secundario;
			else
				echo false;
		}
		else
		{
			$query = "SELECT count(*) FROM cliente WHERE codigo_cliente='$id'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($cont) = mysql_fetch_array($resp);
		
			echo $cont; 
		}
	}
	else if($accion == 'cambiar_tarifa_usuario')
	{
		$query2 = "SELECT tarifa,id_tarifa,id_moneda FROM categoria_tarifa 
							WHERE id_categoria_usuario='$id_2'";
		$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
		
		while(list($tarifa,$id_tarifa,$id_moneda)=mysql_fetch_array($resp2))
			{
			$query = "INSERT usuario_tarifa SET id_tarifa = '$id_tarifa', id_moneda = '$id_moneda', 
							id_usuario = '$id', tarifa = '$tarifa'
							ON DUPLICATE KEY UPDATE tarifa = '$tarifa'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);	
			}
		echo("OK");
	}
  else if($accion == "cargar_actividades")
  {
	   	$query = "SELECT codigo_actividad,glosa_actividad
					FROM actividad
				   	WHERE codigo_asunto = '$id'
						OR codigo_asunto IS NULL
					ORDER BY glosa_actividad";
	   	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

	   	for($i = 0; $fila = mysql_fetch_assoc($resp); $i++)
	   	{
			if($i > 0)
				echo("~");
		   	echo(join("|",$fila));
	   	}
		if($i == 0)
			echo("VACIO|");
	}
	else if ($accion == 'cargar_cargos'){
		$query_clientes = "SELECT contrato.factura_razon_social, contrato.factura_direccion, contrato.rut FROM cobro
												JOIN cliente ON cobro.codigo_cliente=cliente.codigo_cliente
												JOIN contrato ON cliente.id_contrato = contrato.id_contrato 
												WHERE cliente.codigo_cliente=$id AND cobro.documento IS NULL LIMIT 1";
		
		
		//SELECT * FROM cliente WHERE id_cliente = $id";
		$query = "SELECT * FROM cobro WHERE documento IS NULL AND codigo_cliente = '$id'";
		$resp = mysql_query($query_clientes, $sesion->dbh) or Utiles::errorSQL($query_clientes,__FILE__,__LINE__,$sesion->dbh);


	   for($i = 0; $fila = mysql_fetch_assoc($resp); $i++)
	   {
			if($i > 0)
				echo("~");
		   echo(join("|",$fila));
	   }
		if($i == 0)
			echo("VACIO|");
		
	}
	elseif( $accion == "cargar_glosa_cliente" )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			{
			if( $id_asunto != '' )
				{
				$query = "SELECT codigo_cliente_secundario FROM cliente 
									JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente
									WHERE asunto.codigo_asunto_secundario='$id_asunto'";
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($codigo_cliente)=mysql_fetch_array($resp);
				}
			$codigo = 'codigo_cliente_secundario';
			}
		else
			{
			if( $id_asunto != '' )
				{
				$query = "SELECT codigo_cliente FROM asunto 
									WHERE codigo_asunto='$id_asunto'";
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($codigo_cliente)=mysql_fetch_array($resp);
				}
			$codigo = 'codigo_cliente';
			}
		$query = "SELECT glosa_cliente FROM cliente WHERE activo=1 AND ".$codigo."='$id' ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($glosa)=mysql_fetch_array($resp);
		$glosa = str_replace('/','|#slash|',$glosa);
		echo $glosa.'/'.$codigo_cliente;
	}
	elseif( $accion == "cargar_glosa_asunto" )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			$codigo = 'codigo_asunto_secundario';
		}
		else
		{
			$codigo = 'codigo_asunto';
		}
		if( $id_cliente > 0 )
		{
			$query = "SELECT glosa_asunto FROM asunto WHERE activo=1 AND ".$codigo."='$id' AND codigo_cliente='$id_cliente'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($glosa)=mysql_fetch_array($resp);
			$glosa = str_replace('/','|#slash|',$glosa);
			echo $glosa.'/'.$id;
		}
		else
		{
			echo "";
		}
	}
	elseif( $accion == "cargar_cuenta_banco" )
	{
		$query = "SELECT id_cuenta, numero FROM cuenta_banco 
							WHERE id_banco='$id'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

	   	for($i = 0; $fila = mysql_fetch_assoc($resp); $i++)
	   	{
			if($i > 0)
				echo("~");
		   	echo(join("|",$fila));
	   	}
		if($i == 0)
			echo("VACIO|");
	}
	else{
		echo("ERROR");
	}
?>
