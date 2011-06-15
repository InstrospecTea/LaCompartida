<?
	require_once dirname(__FILE__).'/../conf.php';	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';

	$sesion = new Sesion();
	$pagina = new Pagina($sesion);
	
	if($accion == "lista_clientes")
	{
		if($div_post == '2')
			$div_posterior = 'content_data2';
		else
			$div_posterior = 'content_data';
			
    $query = "SELECT DISTINCT cliente.codigo_cliente, cliente.glosa_cliente, cliente.rut, cliente.dv,
                (
                 SELECT id_trabajo FROM trabajo
                 JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
                 where trabajo.codigo_asunto = asunto.codigo_asunto AND asunto.codigo_cliente = cliente.codigo_cliente
                AND trabajo.id_usuario = '".$sesion->usuario->fields[id_usuario]."' ORDER BY id_trabajo DESC LIMIT 0,1) as max_trabajo
                FROM cliente
                ORDER BY max_trabajo DESC LIMIT 15";
    $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
    while(list($codigo_cliente,$glosa_cliente,$rut,$dv, $max_trabajo) = mysql_fetch_array($result))
    {
        if(!$max_trabajo)
            continue;
            
			echo ('<a href=\'javascript:void(0)\' onclick=\'Lista("lista_asuntos","'.$div_posterior.'","'.$codigo_cliente.'")\' class="mano_on">'.$glosa_cliente).'<br>';
		}
	}
	if($accion == "lista_asuntos")
	{
		if($div_post == '2')
			$div_posterior = 'right_data2';
		else
			$div_posterior = 'right_data';
			
		$query = "SELECT DISTINCT glosa_asunto, trabajo_asunto.codigo_asunto, IF(trabajo_asunto.fecha > asunto.fecha_creacion,trabajo_asunto.fecha,asunto.fecha_creacion ) as max_fecha
					FROM asunto 
					LEFT JOIN 
						(SELECT * from trabajo GROUP BY trabajo.codigo_asunto ORDER BY trabajo.codigo_asunto, trabajo.id_trabajo DESC) as trabajo_asunto ON asunto.codigo_asunto = trabajo_asunto.codigo_asunto 
					WHERE asunto.codigo_cliente = '$codigo'
					AND asunto.activo = 1
					ORDER BY max_fecha, trabajo_asunto.id_trabajo DESC LIMIT 15";
		$result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($glosa_asunto, $codigo_asunto) = mysql_fetch_array($result))
		{
			$tooltip = '<b>'.__('Asunto').'</b><br>'.nl2br($glosa_asunto);
			$glosa = $codigo_asunto != '' ? substr($glosa_asunto,0,20) : '<b>'.substr($glosa_asunto,0,20).' ('.__('Nuevo').')</b>';
			$bg_color = $codigo_asunto == '' ? 'style="background-color:#BAF3A7"' : '';
			echo ('<a href=\'javascript:void(0)\' onMouseover=\'ddrivetip("'.$tooltip.'")\' onMouseout=\'hideddrivetip()\' onclick=\'Lista("lista_trabajos","'.$div_posterior.'","'.$codigo_asunto.'")\' ondblclick="ShowDiv(\'tr_cliente\',\'none\',\'img_historial\');hideddrivetip();" class="mano_on" '.$bg_color.'>'.$glosa).'<br>';
		}
	}
	if($accion == "lista_trabajos")
	{
		$top = 26;
		if($div == 'right_data2')
			$top = 40;
			
		$query = "SELECT DISTINCT(codigo_asunto), id_trabajo, descripcion, codigo_actividad, duracion, duracion_cobrada, cobrable, visible, fecha,DATE_FORMAT(fecha,'%d-%m-%Y') as fecha_show
					FROM trabajo
					WHERE codigo_asunto = '$codigo'
					ORDER BY id_trabajo DESC LIMIT 15";
		$result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($codigo_asunto, $id_trabajo, $descripcion, $codigo_actividad, $duracion, $duracion_cobrada, $cobrable, $visible, $fecha, $fecha_show) = mysql_fetch_array($result))
		{
			$descripcion = htmlspecialchars($descripcion,ENT_QUOTES);
			$tooltip = '<b>'.__('Duración').'</b><br>'.$duracion.'<br><b>'.__('Fecha').'</b><br>'.$fecha_show.'<br><b>'.__('Descripción').'</b><br>'.nl2br($descripcion);
			//Revisa el Conf si esta permitido y la función existe
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal') || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
			{
				echo ('<a href=\'javascript:void(0)\' onMouseover=\'ddrivetip("'.$tooltip.'")\' onMouseout=\'hideddrivetip()\' onclick=\'UpdateTrabajo("'.$id_trabajo.'","'.$descripcion.'","'.$codigo_actividad.'","'.UtilesApp::Time2Decimal($duracion).'","'.UtilesApp::Time2Decimal($duracion_cobrada).'","'.$cobrable.'","'.$visible.'","'.$fecha.'")\' class="mano_on">'.substr($descripcion,0,$top)).'<br>';
			}
			else
			{
				echo ('<a href=\'javascript:void(0)\' onMouseover=\'ddrivetip("'.$tooltip.'")\' onMouseout=\'hideddrivetip()\' onclick=\'UpdateTrabajo("'.$id_trabajo.'","'.$descripcion.'","'.$codigo_actividad.'","'.$duracion.'","'.$duracion_cobrada.'","'.$cobrable.'","'.$visible.'","'.$fecha.'")\' class="mano_on">'.substr($descripcion,0,$top)).'<br>';
			}
		}
	}
?>