<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	
	require_once Conf::ServerDir().'/classes/Tarea.php';
	require_once Conf::ServerDir().'/classes/TareaComentario.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Archivo.php';
	
	
    $sesion = new Sesion('');
	$pagina = new Pagina ($sesion);

	$html = 'EXITO';
	
	if($accion == 'cargar_comentario')
	{
		$h = array();

		$query = "SELECT * FROM tarea_comentario WHERE id_comentario = '$id_comentario'";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

		if($row = mysql_fetch_array($resp))
		{
			$com = str_replace('|','!',$row['comentario']);
			$com = str_replace('head','REEMPLAZAR_POR_HEAD',$com);

			$duracion = $row['duracion_avance'];
			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
			{
				$duracion = UtilesApp::Time2Decimal($duracion);
			}
			
			$nombre_archivo = '';
			if( $row['id_archivo'] )
			{
				$archivo = new Archivo($sesion);
				$archivo->Load($row['id_archivo']);
				$nombre_archivo = $archivo->fields['archivo_nombre'];
			}


			$h[] = $row['id_comentario'];
			$h[] = $row['id_tarea'];
			$h[] = $row['id_usuario'];
			$h[] = $com;
			$h[] = Utiles::sql2date($row['fecha_avance']);
			$h[] = $duracion;
			$h[] = $row['id_trabajo'];
			$h[] = $row['id_archivo'];
			$h[] = $nombre_archivo;
			$h[] = $row['estado'];
			
			echo implode('|',$h);
		}
		else
			echo 'FAIL';
		
	}
	if($accion == 'refrescar_tiempo_ingresado')
	{
		$tarea = new Tarea($sesion);
		$tarea->Load($id_tarea);
		$r = $tarea->getTiempoIngresado();

		echo $r;
	}
	if($accion == 'registrar_visita')
	{
		$id_usuario = $sesion->usuario->fields['id_usuario'];
		if($id_usuario && $id_tarea)
		{
			$query = "  INSERT INTO tarea_comentario_usuario (id_comentario, id_usuario)
						SELECT tarea_comentario.id_comentario, usuario.id_usuario
									FROM tarea
										JOIN tarea_comentario ON (tarea_comentario.id_tarea = tarea.id_tarea)
										JOIN usuario
										LEFT JOIN tarea_comentario_usuario ON (tarea_comentario_usuario.id_comentario = tarea_comentario.id_comentario AND tarea_comentario_usuario.id_usuario = '$id_usuario')
									WHERE usuario.id_usuario = '$id_usuario' AND tarea.id_tarea = '$id_tarea'
									AND tarea_comentario_usuario.id_comentario IS NULL

						";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		}
		echo 'OK';
	}
?>