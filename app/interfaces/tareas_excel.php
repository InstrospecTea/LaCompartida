<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	
	require_once Conf::ServerDir().'/../app/classes/TareaBuscador.php';
	require_once Conf::ServerDir().'/../fw/classes/BuscadorExcel.php';
	
	require_once Conf::ServerDir().'/classes/Tarea.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';


	$sesion = new Sesion(array('PRO','ADM'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	/**/
	$hoy = date('d-m-Y');


	if($otras_t)
		$opciones['otras_tareas'] = 1;
	if($t_mandante)
		$opciones['tareas_mandante'] = 1;
	if($t_responsable)
		$opciones['tareas_responsable'] = 1;
	if($t_revisor)
		$opciones['tareas_revisor'] = 1;

	if($fecha_desde)
		$opciones['fecha_desde'] = $fecha_desde;
	if($fecha_hasta)
		$opciones['fecha_hasta'] = $fecha_hasta;

	if(is_array($estados))
	{
		$opciones['estado'] = $estados;
	}

	if($codigo_cliente)
		$opciones['codigo_cliente'] = $codigo_cliente;
	if($codigo_asunto)
		$opciones['codigo_asunto'] = $codigo_asunto;

	if($opc == 'buscar')
	{
			$orden = '';
			if($opciones['orden'] == 'Cliente')
				$orden = " tarea.codigo_cliente ASC, tarea.codigo_asunto ASC, ";
			else if($opciones['orden'] == 'Estado')
				$orden = " tarea.estado ASC, ";
			
			$orden .= " tarea.fecha_entrega ASC ";

			$query = Tarea::query($opciones,$id_usuario);
			$b = new BuscadorExcel($sesion, $query, "Objeto", $orden, 'Reporte de Tareas');

			$b->nombre = "busc_tareas";

			$b->AgregarFuncion(__('Plazo'),'Fecha');
			$b->AgregarEncabezado('estado',__('Estado'));
			$b->AgregarEncabezado('nombre',__('Nombre'));
			$b->AgregarEncabezado('detalle',__('Detalle'));

			$b->AgregarEncabezado('glosa_cliente',__('Cliente'));
			$b->AgregarEncabezado('glosa_asunto',__('Asunto'));
			
			if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
			{
				$b->AgregarEncabezado('username_encargado',__('Responsable'));
				$b->AgregarEncabezado('username_revisor',__('Revisor'));
				$b->AgregarEncabezado('username_generador',__('Mandante'));
			}
			else
			{
				$b->AgregarEncabezado('encargado',__('Responsable'));
				$b->AgregarEncabezado('revisor',__('Revisor'));
				$b->AgregarEncabezado('generador',__('Mandante'));
			}
			
			function Fecha(&$fila)
			{
				$fecha = Utiles::sql2date($fila->fields['fecha_entrega'],'%d-%m-%y');
				$split = explode('-',$fecha);
				if(mktime(0,0,0,$split[1],$split[0],$split[2]) <= mktime(0,0,0) )
				{
				}
				return $fecha;
			}
	}

	$b->Imprimir();
?>
