<?
 /* 
	* ANTES DE CORRER ESE SCRIPT ES IMPORTANTE AGREGAR LA COLUMNA ID_TRABAJO 
	* A LA TABLA TBFI_HORAS PARA QUE EL SCRIPT TIENE REFERENCIA AL TRABAJO 
	* ESPECIFICO
	*/
		require_once dirname(__FILE__).'/../conf.php';
		require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
		require_once Conf::ServerDir().'/../app/classes/Debug.php';
		require_once Conf::ServerDir().'/../app/implementacion/0_instrucciones_y_configuraciones.php';
		
		ini_set("memory_limit","128M");
		set_time_limit(1000);
		
		$dbh2 = @mysql_connect(ConfImplementacion::dbHost(), ConfImplementacion::dbUser(),ConfImplementacion::dbPass()) or die(mysql_error());
		mysql_select_db(ConfImplementacion::dbName()) or mysql_error($dbh2);
		$bd_origen = ConfImplementacion::dbName();
		
		$query_max_trabajo = "SELECT MAX(id_trabajo) FROM ".$bd_origen.".tbfi_horas";
		$resp = mysql_query($query_max_trabajo,$dbh2) or Utiles::errorSQL($query_max_trabajo,__FILE__,__LINE__,$dbh2);
		list($max_trabajo) = mysql_fetch_array($resp);
		
		$max = floor( ( $max_trabajo / 10000 ) + 1 );
		
		for( $i=0;$i<500;$i++ )
		{
		$query = "SELECT
								id_trabajo,
								IF(t_o.id_ot IS NOT NULL,t_o.id_ot,'9999') as id_ot,
								t_h.id_cia as id_cia,
								IF(t_s.id_staff IS NOT NULL,t_s.id_staff,'9999') as id_staff,
								IF(t_h.fecha IS NOT NULL,t_h.fecha,'1900-01-01') as fecha,
								SEC_TO_TIME( 60 * t_h.minutos ) as duracion,
								t_h.datemodify as fecha_modificacion,
								t_h.descripcion ,
								IF(t_o.id_cliente IS NOT NULL,t_o.id_cliente,'9999') as cliente 
							FROM tbfi_horas as t_h 
							LEFT JOIN tbfi_ot as t_o ON t_h.id_ot=t_o.id_ot AND t_h.id_cia = t_o.id_cia
							LEFT JOIN tbad_staff as t_s ON t_s.id_staff=t_h.id_staff
							WHERE t_h.id_trabajo > ".(154095+1*$i)." AND t_h.id_trabajo <= ".(154095+1*(($i-0)+1))." 
							GROUP BY id_trabajo";
		$resp = mysql_query($query,$dbh2) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh2);
		
		unset($query_ingreso);
		while( $row = mysql_fetch_assoc($resp) )
		{
			if( empty($query_ingreso) )
				{
					$query_ingreso = "INSERT INTO trabajo ( id_trabajo, codigo_asunto, 
														id_usuario, fecha, descripcion, duracion, duracion_cobrada,fecha_creacion,fecha_modificacion) 
														VALUES ";
				}
			else
				$query_ingreso .= ",";
				
			if( $asuntos_duplicados[$row['id_ot']] == 'modificar' && $row['id_cia'] == 2 ) 
				{
					$id_ot = ($row['id_ot']-0)+9900;
					$asuntos_duplicados[$row['id_ot']] = '';
					$cliente = $row['cliente'];
				}
			else
				{
					$id_ot = $row['id_ot'];
					$cliente = $row['cliente'];
				}
				
				if( $row['id_staff'] == '0122' ) $row['id_staff'] = '9999';
				
				if( substr($cliente,-4).'-'.substr($id_ot,-4) == '1212-2225' ) $id_ot = '0001';
				if( substr($cliente,-4).'-'.substr($id_ot,-4) == '0125-2231' ) $id_ot = '2223';
				if( substr($cliente,-4).'-'.substr($id_ot,-4) == '0125-2244' ) $id_ot = '2223';
				if( substr($cliente,-4).'-'.substr($id_ot,-4) == '0683-2240' ) $id_ot = '1566';
				if( substr($cliente,-4).'-'.substr($id_ot,-4) == '1150-2246' ) {$id_ot = '9999';$cliente = '9999';}
				
			$query_ingreso .= "( ".$row['id_trabajo']." , '".substr($cliente,-4).'-'.substr($id_ot,-4)."', ".$row['id_staff'].", '".$row['fecha']."', 
												'".addslashes($row['descripcion'])."', '".$row['duracion']."', '".$row['duracion']."', '".$row['fecha']."', '".$row['fecha_modificacion']."' )";
		}
		mysql_close($dbh2);
		$sesion = new Sesion();
		
			if( $query_ingreso != '' )
			{
				mysql_query($query_ingreso,$sesion->dbh) or Utiles::errorSQL($query_ingreso,__FILE__,__LINE__,$sesion->dbh);
				$dbh2 = @mysql_connect(ConfImplementacion::dbHost(), ConfImplementacion::dbUser(),ConfImplementacion::dbPass()) or die(mysql_error());
				mysql_select_db(ConfImplementacion::dbName()) or mysql_error($dbh2);
			}
		}
	?>
