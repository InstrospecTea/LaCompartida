<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once dirname(__FILE__).'/../classes/AlertaCron.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Reporte.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';


	$sesion = new Sesion (null, true);
	$alerta = new AlertaCron ($sesion);

    set_time_limit(500);

	function dia_semana($d)
	{
		switch($d)
		{
			case 1:
				return __('Lunes');
			case 2:
				return __('Martes');
			case 3:
				return __('Miércoles');
			case 4:
				return __('Jueves');
			case 5:
				return __('Viernes');
			case 6:
				return __('Sábado');
		}
		return __('Domingo');
	}

	function get_inner_html( $node )
	{
		$innerHTML= '';
		$children = $node->childNodes;
		foreach ($children as $child) {
			$innerHTML .= $child->ownerDocument->saveXML( $child );
		}
		return $innerHTML;
	}


	function getTabla($s,$agrupadores)
	{

		// No interesa que se generen los warnings en PHP.
		libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->loadHTML( $s );

		$xpath = new DOMXPath($dom);

		$query_header = '//table[@id="tabla_planilla"]';
		$headers = $xpath->query($query_header);

		$query = '//table[@id="tabla_planilla_2"]';
		$entries = $xpath->query($query);

		//Debo eliminar los primeros X nodos de cada tr:
		$tr_query = './tbody/tr';
		$tr_entries = $xpath->query($tr_query,$entries->item(0));


		$nodesToDelete = array();
		foreach($tr_entries as $tr)
		{
			$quitar = 0;
			if($tr->childNodes)
			{
				$largo = $tr->childNodes->length;
				if($largo > 2*($agrupadores+1))
					$quitar = $largo-2*$agrupadores;

				for($i = 0; $i < $quitar; $i++)
					if($tr->childNodes->item($i))
						$nodesToDelete[] = $tr->childNodes->item($i);
			}
		}
		foreach( $nodesToDelete as $domElement )
		{
			$domElement->parentNode->removeChild($domElement);
		}

		$sub_query = './/td';
		$sub_entries = $xpath->query($sub_query,$entries->item(0));
		foreach($sub_entries as $celda)
		{
			$micro_query = '//a';
			$as = $xpath->query($micro_query,$celda);
			foreach($as as $a)
			{
				$a->setAttribute('style','color:black; text-decoration:none; cursor:default;');
				$a->setAttribute('href','javascript:void(0)');
				$a->setAttribute('onclick','');

			}
			//while($an->item(0))
			//	$celda->removeChild($an->item(0));
		}


		$output = "<table style='font-family: Tahoma, Arial, Geneva, sans-serif;'>".get_inner_html( $headers->item(0)).'</table>';
		$output .= "<table style='border:1px solid #CCC; border-collapse:collapse; font-family: Tahoma, Arial, Geneva, sans-serif;'>".get_inner_html( $entries->item(0)).'</table>';
		return $output;
	}

	function getReportePlanilla($args)
	{
		$tipo_dato = $args['tipo_dato'];
		$tipo_dato_comparado = $args['tipo_dato_comparado'];
		$vista = $args['vista'];
		$id_moneda = $args['id_moneda'];
		$prop = $args['prop'];
		$fecha_ini = $args['fecha_ini'];
		$fecha_fin = $args['fecha_fin'];
		$campo_fecha = $args['campo_fecha'];
		$email = 1;

		$ver = __("Cargar versión actualizada de este reporte en el sistema:")." ";
		$ver .= "<a href=".Conf::Server().Conf::Rootdir()."/app/interfaces/reporte_avanzado.php?mis_reportes_elegido=".$args['reporte'].' >Reporte</a> ';
		$ver .= "<a href=".Conf::Server().Conf::Rootdir()."/app/interfaces/reporte_avanzado_planilla.php?tipo_dato=".$tipo_dato."&vista=".$vista."&id_moneda=".$id_moneda."&prop=".$prop."&clientes=&usuarios=&fecha_ini=".$fecha_ini."&fecha_fin=".$fecha_fin."&campo_fecha=".$campo_fecha."&tipo_dato_comparado=".$tipo_dato_comparado.' >Planilla</a> ';
		$ver .= "<a href=".Conf::Server().Conf::Rootdir()."/app/interfaces/reporte_avanzado_grafico.php?tipo_grafico=barras&tipo_dato=".$tipo_dato."&vista=".$vista."&id_moneda=".$id_moneda."&prop=".$prop."&clientes=&usuarios=&fecha_ini=".$fecha_ini."&fecha_fin=".$fecha_fin."&campo_fecha=".$campo_fecha."&tipo_dato_comparado=".$tipo_dato_comparado.' >Barras</a> <br>';
		$ver .= "<i>(requiere sesión iniciada en el navegador)</i>";

		ob_start();
			require Conf::ServerDir().'/../app/interfaces/reporte_avanzado_planilla.php';
		$out = ob_get_clean();

		$tabla = getTabla($out,sizeof(explode('-',$vista)));

		$tabla .= '<br><br>'.$ver;
		return $tabla;
	}

	/*Genero fecha_ini,fecha_fin para la semana pasada y el mes pasado.*/
	$week = date('W');
	$year = date('Y');
	$lastweek=$week-1;
	if ($lastweek==0){
		$lastweek = 52;
		$year--;
	}
	$lastweek=sprintf("%02d", $lastweek);
	$semana_pasada_ini = date('d-m-Y',strtotime("$year". "W$lastweek"."1"));
	$semana_pasada_fin = date('d-m-Y',strtotime("$year". "W$lastweek"."7"));


	$last_month = strtotime("-".(date('j'))." day");
	$mes_pasado_ini = "01".date('-m-Y',$last_month);
	$mes_pasado_fin = date('t-m-Y',$last_month);

	$actual_ini = "01-01-".date('Y');
	$actual_fin = date('d-m-Y');



	/* Query de Usuarios a los que se les envia Reportes */
	$query_usuarios = "		SELECT usuario.id_usuario,
							CONCAT(usuario.apellido1,' ',usuario.nombre)	as nombre_usuario,
							usuario.nombre									as nombre_pila
							FROM usuario
							JOIN usuario_permiso ON usuario.id_usuario = usuario_permiso.id_usuario AND usuario_permiso.codigo_permiso = 'REP'
							WHERE usuario.activo = 1 ";
	$result_usuarios = mysql_query($query_usuarios , $sesion->dbh) or Utiles::errorSQL($query_usuarios,__FILE__,__LINE__,$sesion->dbh);

	/*Por cada usuario de Reportes: */
	while ($row = mysql_fetch_array($result_usuarios))
    {
		$id_usuario = $row['id_usuario'];
		$nombre_pila = $row['nombre_pila'];

		/*Repaso sus Reportes*/
		$query_mis_reportes = "SELECT reporte, glosa, segun, envio FROM usuario_reporte WHERE id_usuario = '".$id_usuario."';";
		$resp_mis_reportes = mysql_query($query_mis_reportes,$sesion->dbh) or Utiles::errorSQL($query_mis_reportes,__FILE__,__LINE__,$sesion->dbh);

		while( list($reporte_encontrado,$nombre_reporte_encontrado,$segun_reporte_encontrado,$envio_reporte_encontrado) = mysql_fetch_array($resp_mis_reportes) )
		{
				/*Tiene una alerta. Vemos si es hoy*/
				if($envio_reporte_encontrado > 0)
				{
					$datos = explode('.',$reporte_encontrado);

					$enviar = false;
					if($datos[2] == 'semanal' && $envio_reporte_encontrado == date('N'))
						$enviar = true;
					if( ($datos[2] == 'mensual' || $datos[2] == 'anual' ) && $envio_reporte_encontrado == date('j'))
						$enviar = true;

					//Enviamos el mail
					if($enviar || $todo_periodo == 'asda123qasdwMWAdngvvesdrseASDaCAFVASCW' )
					{
						$tds = explode(',',$datos[0]);
						$ags = explode(',',$datos[1]);

						$args = array();

						$args['reporte'] = $reporte_encontrado;

						$args['tipo_dato'] = $tds[0];
						$args['tipo_dato_comparado'] = '';
						if(sizeof($tds)>1)
							$args['tipo_dato_comparado'] = $tds[1];

						$args['vista'] = implode('-',$ags);
						$args['id_moneda'] = 2;
						$args['prop'] = 'estandar';
						$args['campo_fecha'] = $segun_reporte_encontrado;

						$campo_fecha_usado = 'trabajo';
						if($segun_reporte_encontrado == 'corte')
							$campo_fecha_usado = __('fecha de corte del cobro');
						if($segun_reporte_encontrado == 'emision')
							$campo_fecha_usado = __('fecha de emisión del cobro');

						$args['fecha_ini'] = $mes_pasado_ini;
						$args['fecha_fin'] = $mes_pasado_fin;

						if($datos[2] == 'semanal')
						{
							$args['fecha_ini'] = $semana_pasada_ini;
							$args['fecha_fin'] = $semana_pasada_fin;
						}
						else if($datos[2] == 'anual')
						{
							$args['fecha_ini'] = $actual_ini;
							$args['fecha_fin'] = $actual_fin;
						}

						$out = getReportePlanilla($args);

						$s = __("Estimado/a")." ".$nombre_pila.":";
						$s .= "<br />&nbsp;&nbsp;&nbsp;".__("El reporte")." '".$nombre_reporte_encontrado."' ";

						$s .= __("se ha generado para el periodo del ").$args['fecha_ini']." ".__("al")." ".$args['fecha_fin']." (".__("según fecha del")." ".$campo_fecha_usado.").";

						$periodicidad = __('día')." ".str_pad($envio_reporte_encontrado, 2, '0', STR_PAD_LEFT)." ".__('del mes')." ";
						if($datos[2] == 'semanal')
								$periodicidad = dia_semana($envio_reporte_encontrado);

						/* Periodo */
						$s .= "<br />&nbsp;&nbsp;&nbsp;".__("Este reporte está configurado para enviarse cada")." ".$periodicidad.".";

						$s .= $out;

						if($mensajito == 'asda123qasdwMWAdngvvesdrseASDaCAFVASCW')
							echo $s.'<br />----------<br />';

						if($argv[1]=='correo')
							$alerta->EnviarAlertaProfesional($id_usuario,$s, $sesion, false);
					}
				}
		}


	}

?>