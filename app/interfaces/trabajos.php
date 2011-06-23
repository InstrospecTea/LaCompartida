<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';


	$sesion = new Sesion(array('PRO','REV','ADM','COB'));
	$pagina = new Pagina($sesion);

	$params_array['codigo_permiso'] = 'REV';
	$p_revisor = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

	$params_array['codigo_permiso'] = 'COB';
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	if($p_cobranza->fields['permitido'])
		$p_revisor->fields['permitido'] = true;

	$params_array['codigo_permiso'] = 'PRO';
	$p_profesional = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

	if($id_cobro)
	{
		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);
		
		if(!$cobro->Load($id_cobro))
			$pagina->FatalError(__('Cobro inválido'));
			
		if($buscar != 1) {
			if($fecha_ini=='' || $fecha_ini=='00-00-0000' || $fecha_ini == NULL )
				$fecha_ini=Utiles::sql2date($cobro->fields['fecha_ini']);
			
				if($fecha_fin=='' || $fecha_fin=='00-00-0000' || $fecha_fin == NULL )
					$fecha_fin=Utiles::sql2date($cobro->fields['fecha_fin']);
				}
	}

	if($p_revisor->fields['permitido'] && $accion == "eliminar")
	{
		$trabajo = new Trabajo($sesion);
		$trabajo->Load($id_trabajo);
		if(!$trabajo->Eliminar())
			$pagina->AddError($asunto->error);
		else
			$pagina->AddInfo(__('Trabajo').' '.__('eliminado con éxito'));
	}

	##Seteando FECHAS a formato SQL
	if($fecha_ini != '')
		$fecha_ini	= Utiles::fecha2sql($fecha_ini);
	else
		$fecha_ini = Utiles::fecha2sql($fecha_ini,'0000-00-00');

	if($fecha_fin != '')
		$fecha_fin	= Utiles::fecha2sql($fecha_fin);
	else
		$fecha_fin = Utiles::fecha2sql($fecha_fin,'0000-00-00');

	if($id_cobro == 'Indefinido')
	{
		$cobro_nulo = true;
		unset($id_cobro);
	}

	#Si estamos en un cobro
	if($cobro)
	{
		if($opc == "buscar")
		{
			//Significa que se apreto el boton buscar asi que hay que considerarlas nuevas fechas
			if($fecha_ini != '0000-00-00' && $fecha_ini != '')
				$cobro->Edit('fecha_ini', $fecha_ini);
			else
				$cobro->Edit('fecha_ini',NULL);

			if($fecha_fin != '0000-00-00' && $fecha_fin != '')
				$cobro->Edit('fecha_fin', $fecha_fin);
			else{
				$fecha_hoy = date("Y-m-d",time());
				$cobro->Edit('fecha_fin', $fecha_hoy);
			}
			$cobro->Write();
		}
		else //En caso de que no estoy buscando debo setear fecha ini y fecha fin
		{
			$fecha_ini = $cobro->fields['fecha_ini'];
			$fecha_fin = $cobro->fields['fecha_fin'];
		}
	}

	// Calculado aquÃ­ para que la variable $select_usuario estÃ© disponible al generar la tabla de trabajos.
	if($p_revisor->fields['permitido'])
		$where_usuario = '';
	else
		$where_usuario = "AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=".$sesion->usuario->fields[id_usuario].") OR usuario.id_usuario=".$sesion->usuario->fields[id_usuario].")";
	$select_usuario = Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ".$where_usuario." ORDER BY nombre ASC","id_usuario",$id_usuario,'','Todos','200');

	$where = base64_decode($where);
	if( $where == '')
		$where .= 1;
	if($id_usuario != '')
		$where .= " AND trabajo.id_usuario= ".$id_usuario;
	else if(!$p_revisor->fields['permitido']) // Se buscan trabajos de los usuarios a los que se puede revisar.
		$where .= " AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=".$sesion->usuario->fields[id_usuario].") OR usuario.id_usuario=".$sesion->usuario->fields[id_usuario].") ";
	if($revisado == 'NO')
		$where.= " AND trabajo.revisado = 0 ";
	if($revisado == 'SI')
		$where.= " AND trabajo.revisado = 1 ";
	if($codigo_asunto != '' || $codigo_asunto_secundario != "")
	{
		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			$where.= " AND asunto.codigo_asunto_secundario = '$codigo_asunto_secundario' ";
		}
		else
		{
			$where.= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
		}
	}
	if($cobrado == 'NO')
		$where .= " AND ( trabajo.id_cobro is null OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
	if($cobrado == 'SI')
		$where .= " AND trabajo.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado = 'INCOBRABLE') ";

	if($from == 'reporte')
	{
		if($id_cobro)
			$where .= " AND trabajo.id_cobro = $id_cobro ";

		if($mes)
			$where .= " AND DATE_FORMAT(trabajo.fecha, '%m-%y') = '$mes' ";

		if($cobro_nulo)
			$where .= " AND trabajo.id_cobro IS NULL ";

		if($estado)
		if($estado != 'abiertos')
		{
			if($estado == 'Indefinido')
				$where .= " AND cobro.id_cobro IS NULL";
			else
				$where .= " AND cobro.estado = '$estado' ";
		}

		if($lis_clientes)
			$where .= " AND cliente.codigo_cliente IN (".$lis_clientes.") ";
		if($lis_usuarios)
			$where .= " AND usuario.id_usuario IN (".$lis_usuarios.") ";

	}

	//Estos filtros son tambien para la pag. mis horas
	if($activo)
	{
		if($activo== 'SI')
			$activo = 1;
		else
			$activo = 0;

    $where .= " AND a1.activo = $activo ";
	}
	if($codigo_cliente != "" || $codigo_cliente_secundario != "")
	{
		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			$where .= " AND cliente.codigo_cliente_secundario ='$codigo_cliente_secundario' ";
		}
		else
		{
			$where .= " AND cliente.codigo_cliente ='$codigo_cliente' ";
		}
	}
	#SQL FECHAS
	if($fecha_ini != '' and $fecha_ini != 'NULL' and $fecha_ini != '0000-00-00')
		$where .= " AND trabajo.fecha >= '".$fecha_ini."' ";

	if($fecha_fin != '' and $fecha_fin != 'NULL' and $fecha_fin != '0000-00-00')
		$where .= " AND trabajo.fecha <= '".$fecha_fin."' ";

	if(isset($cobro)) // Es decir si es que estoy llamando a esta pantalla desde un cobro
	{
		$cobro->LoadAsuntos();
		$query_asuntos = implode("','", $cobro->asuntos);
		$where .= " AND trabajo.codigo_asunto IN ('$query_asuntos') ";
		//$where .= " AND trabajo.cobrable = 1";
		if($opc == 'buscar')
			$where .= " AND (cobro.estado IS NULL OR trabajo.id_cobro = '$id_cobro')";
		else
			$where .= " AND trabajo.id_cobro = '$id_cobro'";
	}

	if($cobrable == 'SI')
		$where .= " AND trabajo.cobrable = 1";
	if($cobrable == 'NO')
		$where .= " AND trabajo.cobrable <> 1";

	//Filtros que se mandan desde el reporte Periodico
	if($id_grupo)
	{
		if($id_grupo == 'NULL')
			$where .= " AND cliente.id_grupo_cliente IS NULL";
		else
			$where .= " AND cliente.id_grupo_cliente = $id_grupo";
	}
	if($clientes)
		$where .= "	AND cliente.codigo_cliente IN ('".base64_decode($clientes)."')";

	if($usuarios)
		$where .= "	AND usuario.id_usuario IN (".base64_decode($usuarios).")";
		
		$where .= " AND trabajo.id_tramite = 0 ";
	
	if($id_encargado_comercial)
		$where .= " AND contrato.id_usuario_responsable = '$id_encargado_comercial' ";
	
	#TOTAL HORAS
	$query = "SELECT 
							SUM(TIME_TO_SEC(if(trabajo.cobrable=1,duracion_cobrada,0)))/3600 AS total_duracion, 
							SUM(TIME_TO_SEC(duracion))/3600 AS total_duracion_trabajada
						FROM trabajo
						JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
	          LEFT JOIN actividad ON trabajo.codigo_actividad=actividad.codigo_actividad
	          LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
	          LEFT JOIN cobro ON cobro.id_cobro=trabajo.id_cobro
	          LEFT JOIN contrato ON asunto.id_contrato =contrato.id_contrato
            LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario 
	          LEFT JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
	          WHERE $where ";
  $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
  list($total_duracion,$total_duracion_trabajada) = mysql_fetch_array($resp);

	#BUSCAR
	$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS *,
											trabajo.id_cobro,
											trabajo.revisado, 
											trabajo.id_trabajo, 
											trabajo.codigo_asunto,
											trabajo.cobrable,
											prm_moneda.simbolo as simbolo,
											asunto.codigo_cliente as codigo_cliente, 
											contrato.id_moneda as id_moneda_asunto, 
											asunto.id_asunto AS id,
											trabajo.fecha_cobro as fecha_cobro_orden, 
											trabajo.descripcion, 
											IF( trabajo.cobrable = 1, 'SI', 'NO') as glosa_cobrable, 
											trabajo.visible, 
											cobro.estado as estado_cobro, 
											CONCAT_WS(' ',usuario.nombre,usuario.apellido1) as usr_nombre, 
											usuario.username, 
											usuario.id_usuario, 
											CONCAT_WS('<br>',DATE_FORMAT(trabajo.duracion,'%H:%i'), 
											DATE_FORMAT(duracion_cobrada,'%H:%i')) as duracion,
											TIME_TO_SEC(trabajo.duracion)/3600 as duracion_horas, 
											trabajo.tarifa_hh, 
											tramite_tipo.id_tramite_tipo,
	              			DATE_FORMAT(trabajo.fecha_cobro,'%e-%c-%x') AS fecha_cobro, 
	              			cobro.estado, 
	              			asunto.forma_cobro, 
	              			asunto.monto, 
	              			asunto.glosa_asunto,
	              			contrato.descuento, 
	              			tramite_tipo.glosa_tramite, 
	              			trabajo.fecha, 
	              			contrato.id_tarifa  
	              FROM trabajo
	              JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
	              LEFT JOIN actividad ON trabajo.codigo_actividad=actividad.codigo_actividad
	              LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
	              LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
	              LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
	              LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
	              LEFT JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
	              LEFT JOIN tramite ON trabajo.id_tramite=tramite.id_tramite
	              LEFT JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
	              WHERE $where ";
	if($check_trabajo == 1 && isset($cobro) && !$excel)	//Check_trabajo vale 1 cuando aprietan boton buscar
	{
		$query2 = "UPDATE trabajo SET id_cobro = NULL WHERE id_cobro='$id_cobro'";
		$resp = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
		$lista_trabajos = new ListaTrabajos($sesion,'',$query);
		for($x=0;$x<$lista_trabajos->num;$x++)
		{
			$trabajo = $lista_trabajos->Get($x);
			$emitir_trabajo = new Trabajo($sesion);
			$emitir_trabajo->Load($trabajo->fields['id_trabajo']);
			$emitir_trabajo->Edit('id_cobro',$id_cobro);
			$emitir_trabajo->Write();
		}
	}
	//Se hace la lista para la edición de TODOS los trabajos del query
	//A la página de editar multiples trabajos se le pasa encriptado el where
	//de esta manera no se sobrecarga esta página
	//Esta comentado hasta encontrar una buena manera de encriptarlo
	//$query_listado_completo=mcrypt_encrypt(MCRYPT_CRYPT,Conf::Hash(),$where,MCRYPT_ENCRYPT);
	
	
	
	if($orden == "")
		$orden = " trabajo.fecha ASC, trabajo.descripcion";
	if(stristr($orden,".") === FALSE)
		$orden = str_replace("codigo_asunto","a1.codigo_asunto",$orden);

	$x_pag = 15;
	$b = new Buscador($sesion, $query, "Trabajo", $desde, $x_pag, $orden);
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_gastos";
	$b->titulo = __('Listado de').' '.__('trabajos');
	if($p_revisor->fields['permitido'])
		$b->titulo .= "<table width=100%><tr><td align=right valign=top><span style='font-size:10px'><b>".__('Total horas trabajadas').": </b>".number_format($total_duracion_trabajada,1)."</span></td></tr></table>";
	$b->titulo .= "<table width=100%><tr><td align=right valign=top><span style='font-size:10px'><b>".__('Total horas cobrables').": </b>".number_format($total_duracion,1)."</span></td></tr></table>";
	$b->AgregarFuncion("Editar",'Editar',"align=center nowrap");
	$b->AgregarEncabezado("trabajo.fecha",__('Fecha'));
	$b->AgregarEncabezado("cliente.glosa_cliente",__('Cliente'),"align=left");
	$b->AgregarEncabezado("asunto.codigo_asunto",__('Asunto'),"align=left");
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsoActividades') ) || ( method_exists('Conf','UsoActividades') && Conf::UsoActividades() ) )
	{
		$b->AgregarEncabezado("actividad.glosa_actividad",__('Actividad'),"align=left");
	}
	$b->AgregarEncabezado("glosa_cobrable",__('Cobrable'),"","","");
	if($p_revisor->fields['permitido'])
		$glosa_duracion=__('Hrs Trab./Cobro.');
	else
		$glosa_duracion=__('Hrs trab.');
	$b->AgregarEncabezado("duracion",$glosa_duracion,"","","SplitDuracion");
	if($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'])
		$b->AgregarEncabezado("trabajo.id_cobro",__('Cobro'),"align=left");
	#$b->AgregarEncabezado("estado",__('Estado'),"align=left");
	if($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'] || strlen($select_usuario) > 164)
		$b->AgregarEncabezado("usr_nombre",__('Usuario'),"align=left");
	#if($p_adm->fields['permitido'])
	$b->AgregarFuncion("Opc.",'Opciones',"align=center nowrap");
	$b->color_mouse_over = "#bcff5c";
	$b->funcionTR = "funcionTR";

	if($excel)
	{
		if($p_cobranza->fields['permitido'])
			$orden = "cliente.glosa_cliente,contrato.id_contrato,asunto.glosa_asunto,trabajo.fecha,trabajo.descripcion";
		$b1 = new Buscador($sesion, $query, "Trabajo", $desde, '', $orden);
		$lista = $b1->lista;

//			require_once Conf::ServerDir().'/interfaces/cobros_generales2.php';
//			exit;

		if($p_cobranza->fields['permitido'] && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CobranzaExcel') ) || ( method_exists('Conf','CobranzaExcel') && Conf::CobranzaExcel() ) ) )
			require_once('cobros_generales.xls.php');
		else
			require_once('cobros3.xls.php');
		exit;
	}

	if($word)
	{
				include dirname(__FILE__).'/cobro_doc.php';
				exit;
	}

	$pagina->titulo = __('Listado de trabajos');
	$pagina->PrintTop($popup);
?>
<script>
function GrabarCampo(accion,id_trabajo,cobro,valor)
{
    var http = getXMLHTTP();
    if(valor)
       valor = '1';
    else
      valor = '0';

    loading("Actualizando opciones");
    http.open('get', 'ajax_grabar_campo.php?accion=' + accion + '&id_trabajo=' + id_trabajo + '&id_cobro=' + cobro + '&valor=' + valor);
    http.onreadystatechange = function()
    {
        if(http.readyState == 4)
        {
            var response = http.responseText;
            var update = new Array();
            if(response.indexOf('OK') == -1)
            {
              alert(response);
            }
            offLoading();
        }
  	};
    http.send(null);
}

function Refrescar()
{
//todo if $motivo=="cobros",$motivo=="horas"
<?
	if($desde)
		echo "var pagina_desde = '&desde=".$desde."';";
	else
		echo "var pagina_desde = '';";
	if($orden)
		echo "var orden = '&orden=" . $orden . "';";
	else
		echo "var orden = '';";
	if ($motivo == "horas")
	{
		if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
			{
?>
				var cliente = 'codigo_cliente_secundario='+$('codigo_cliente_secundario').value;
				var asunto = 'codigo_asunto_secundario='+$('codigo_asunto_secundario').value;
<?
			}
		else
			{
?>
				var cliente = 'codigo_cliente='+$('codigo_cliente').value;
				var asunto = 'codigo_asunto='+$('codigo_asunto').value;
<?
			}
?>

	var usuario = $('id_usuario').value;
	var cobrable = $('cobrable').value;
	var revisado = $('revisado').value;
	var cobrado = $('cobrado').value;
	var fecha_ini = $('fecha_ini').value;
	var fecha_fin = $('fecha_fin').value;
	var url = "trabajos.php?from=horas&id_usuario="+usuario+"&cobrable="+cobrable+"&motivo=horas&revisado="+revisado+"&cobrado="+cobrado+"&"+asunto+"&fecha_ini="+fecha_ini+"&fecha_fin="+fecha_fin+"&popup=1&opc=buscar"+pagina_desde+orden+"&"+cliente;
<?
	}
	elseif ($motivo == "cobros")
	{
?>
	var fecha_ini = $('fecha_ini').value;
	var fecha_fin = $('fecha_fin').value;
	var url = "trabajos.php?id_cobro=<?=$id_cobro?>&motivo=cobros&popup=1&fecha_ini="+fecha_ini+"&fecha_fin="+fecha_fin+pagina_desde+orden;

<?	}?>

	self.location.href= url;
}




function GuardarCampoTrabajo(id,campo,valor)
{
	var http = getXMLHTTP();
    var url = 'ajax.php?accion=actualizar_trabajo&id=' + id + '&campo=' + campo + '&valor=' + valor;

    loading("Actualizando campo");
    http.open('get', url);
    http.onreadystatechange = function()
    {
       if(http.readyState == 4)
       {
          var response = http.responseText;

                /*if(response.indexOf('OK') == -1)
                {
                    alert(response);
                }*/

          offLoading();
       }
    };
    http.send(null);
}

	/*
		Event.observe(window, 'load', init, false);
		function init() {
			// Over ride some of the default options.
			EditInPlace.defaults['type'] = 'text';
			EditInPlace.defaults['save_url'] = 'edit.php';

			// Basic example.
			//EditInPlace.makeEditable({ id: 'desc' });

			// Double click and selected text example.
			EditInPlace.makeEditable({
				id: 'twoclicks',
				click: 'dblclick',
				select_text: true,
				ajax_data: {
					db_id: 12345,
					username: 'devnull'
				}
			});
			// Example that starts out as an empty string and will cancel
			// the form when clicked away from.
			EditInPlace.makeEditable({ id: 'desc', on_blur: 'cancel' });

			// Select / Option list example.
			EditInPlace.makeEditable({
				id: 'desc',
				type: 'select',
				on_clic : this.value,
				save_url: 'optionedit.php',
				options: {
					white: 'White',
					black: 'Black',
					green: 'Green',
					darkgreen: 'Dark Green',
					lightgreen: 'Light Green',
					pink: 'Pink',
					1: 'Yes',
					2: 'No'
				}
			});


			// Textarea example.
			//EditInPlace.makeEditable({ id: 'desc', type: 'textarea', on_blur: 'cancel' });
	}*/

// Basado en http://snipplr.com/view/1696/get-elements-by-class-name/
function getElementsByClassName(classname)
{
	node = document.getElementsByTagName("body")[0];
	var a = [];
	var re = new RegExp('\\b' + classname + '\\b');
	var els = node.getElementsByTagName("*");
	for(var i=0,j=els.length; i<j; i++)
		if(re.test(els[i].className))a.push(els[i]);
	return a;
}
// Función para seleccionar todos las filas para editar, basada en la de phpMyAdmin
function seleccionarTodo(valor)
{
	var rows = getElementsByClassName('buscador')[0].getElementsByTagName('tr');
	var checkbox;
	// Se selecciona fila por medio porque cada trabajo ocupa dos filas de la tabla y el checkbox para editar está en la primera fila de cada trabajo.
	for (var i=0; i<rows.length; i+=2)
	{
		checkbox = rows[i].getElementsByTagName( 'input' )[0];
		if ( checkbox && checkbox.type == 'checkbox' && checkbox.disabled == false) {
			checkbox.checked = valor;
		}
	}
	return true;
}
// Encuentra los id de los trabajos seleccionados para editar, depende del id del primer <tr> que contiene al trabajo.
// Los id quedan en un string separados por el caracter 't'.
function getIdTrabajosSeleccionados()
{
	var rows = getElementsByClassName('buscador')[0].getElementsByTagName('tr');
	var checkbox;
	var ids = '';
	// Se revisa fila por medio porque cada trabajo ocupa dos filas de la tabla y el checkbox para editar está en la primera fila de cada trabajo.
	for (var i=0; i<rows.length; i+=2)
	{
		checkbox = rows[i].getElementsByTagName( 'input' )[0];
		if (checkbox.checked == true) {
			ids += rows[i].id;
		}
	}
	return ids;
}
// Intenta editar múltiples trabajos, genera un error si no hay trabajos seleccionados.
function editarMultiplesArchivos()
{
	// Los id de los trabajos seleccionados están en un solo string separados por el caracter 't'.
	// La página editar_multiples_trabajos.php se encarga de parsear este string.
	var ids = getIdTrabajosSeleccionados();
	if(ids != '')
		nuevaVentana('Editar_múltiples_trabajos', 700, 500, 'editar_multiples_trabajos.php?ids='+ids+'&popup=1','');
	else
		alert('Debe seleccionar por lo menos un trabajo para editar.');
}

function EditarTodosLosArchivos()
{
	var where = $('where_query_listado_completo').value;
	nuevaVentana('Editar_multiples_trabajos', 700, 450, 'editar_multiples_trabajos.php?popup=1&listado='+where, '');
}
</script>
<? echo(Autocompletador::CSS()); ?>
<form method='get' name="form_trabajos" id="form_trabajos">
<input type='hidden' name='opc' id='opc' value='buscar'>
<input type='hidden' name='id_cobro' id='id_cobro' value='<?=$id_cobro ?>'>
<input type='hidden' name='buscar' id='id_cobro' value='1'>
<input type='hidden' name='popup' id)='popup' value='<?=$popup?>'>
<input type='hidden' name='motivo' id='motivo' value='<?=$motivo?>'>
<input type='hidden' name='id_usuario' id='id_usuario' value='<?=$id_usuario?>'>
<input type='hidden' name='check_trabajo' id='check_trabajo' value=''>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<center>
<table width="90%" align="center"><tr><td>
<fieldset class="tb_base" width="100%" style="border: 1px solid #BDBDBD;">
<legend><?=__('Filtros')?></legend>
<table style="border: 0px solid black;" >
<?

	if($motivo != "cobros")
	{
		if( $p_revisor->fields['permitido'])
		{
?>
	<tr>
		<td align=right>
			<?=__('Cobrado')?>
		</td>
		<td align='left'>
			<?=Html::SelectQuery($sesion,"SELECT codigo_si_no, codigo_si_no FROM prm_si_no","cobrado",$cobrado,'','Todos','60')?>
			<?=__('Cobrable')?> <?=Html::SelectQuery($sesion,"SELECT codigo_si_no, codigo_si_no FROM prm_si_no","cobrable",$cobrable,'','Todos','60')?>
			<?=__('Revisado')?> <?=Html::SelectQuery($sesion,"SELECT codigo_si_no, codigo_si_no FROM prm_si_no","revisado",$revisado,'','Todos','60')?>
		</td>
	</tr>
<?
		}
?>
   	<tr>
        <td align=right>
            <?=__('Nombre Cliente')?>
        </td>
        <td nowrap align='left' colspan=3>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo Autocompletador::ImprimirSelector($sesion, '',$codigo_cliente_secundario);
		else	
			echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
	}
	else
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320, $codigo_asunto);
		else
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320, $codigo_asunto);
	}
?>
				</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Asunto')?>
		</td>
		<td nowrap align='left' colspan=3>
			<?
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto,"", "CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}
?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Encargado Comercial')?>
		</td>
		<td align='left' colspan=3>
			<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='SOC' ORDER BY nombre ASC","id_encargado_comercial",$id_encargado_comercial,'','Todos','200'); ?>
		<td>
	</tr>
<? 
		// Explicacion adicional: Esa condición "strlen($select_usuario) > 164" esta validando si hay mas usuarios
		// que solamente Admin Lemontech
		if(strlen($select_usuario) > 164) // Depende de que no cambie la funciÃ³n Html::SelectQuery(...)
		{
?>
	<tr>
		<td align=right>
			<?=__('Usuario')?>
		</td>
		<td align='left' colspan=3>
			<?=$select_usuario?>
		</td>
	</tr>
<?
		}
	}
  	### Validando fecha
  	$hoy = date('Y-m-d');
  	
  	if( $fecha_ini != '0000-00-00' )
  		{
  			if( Utiles::es_fecha_sql($fecha_ini) )
		 			$fecha_ini = Utiles::sql2date($fecha_ini);
		 	}
		else
			$fecha_ini = '';
		if( $fecha_fin != '0000-00-00' )
			{
  			if( Utiles::es_fecha_sql($fecha_fin) )
					$fecha_fin = Utiles::sql2date($fecha_fin);
			}
		else
			$fecha_fin = '';
?>
		<tr>
			<td align=right colspan=1>
					<?=__('Fecha desde')?>:
			</td>
			<td align=left colspan=3>
				<input type="text" name="fecha_ini" value="<?=$fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
				<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />&nbsp;&nbsp;&nbsp;&nbsp;
				<?=__('Fecha hasta')?>:&nbsp;
				<input type="text" name="fecha_fin" value="<?=$fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
				<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
			</td>
		</tr>
		<tr>
			<td></td>
			<td colspan='3'  align=left>
				<input name='boton_buscar' id='boton_buscar' type='submit' class=btn onclick="this.form.check_trabajo.value=1"  value=<?=__('Buscar')?>>
			</td>
		</tr>
</table>
</fieldset>
</td></tr></table>
</center>
</form>

<?
	if(isset($cobro) || $opc == 'buscar')
	{
		echo "<center>";
		$b->Imprimir('', array('check_trabajo')); //Excluyo Checktrabajo);
?>
		<a href="#" onclick="seleccionarTodo(true); return false;">Seleccionar todo</a>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a href="#" onclick="seleccionarTodo(false); return false;">Desmarcar todo</a>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a href="#" onclick="editarMultiplesArchivos(); return false;" title="Editar múltiples trabajos">Editar seleccionados</a>
		<br />
		<input type='hidden' name='where_query_listado_completo' id='where_query_listado_completo' value='<?=urlencode(base64_encode($where))?>'>
		<a href="#" onclick="EditarTodosLosArchivos(); return false;" title="Editar trabajos de todo el listado">Editar trabajos de todo el listado</a>
		<br />
		<input type=button class=btn value="<?=__('Descargar listado a Excel')?>" onclick="window.open('trabajos.php?id_cobro=<?=$id_cobro?>&excel=1&motivo=<?=$motivo?>&where=<?=urlencode(base64_encode($where))?>')">
		<br />
	</center>
		<!--<input type=button class=btn value="<?=__('Descargar Archivo a Word')?>" onclick="window.open('trabajos.php?id_cobro=<?=$id_cobro?>&word=1&motivo=<?=$motivo?>&where=<?=urlencode(base64_encode($where))?>')">-->
<?
	}
	function Cobrable(& $fila)
	{
		global $sesion;
		global $id_cobro;
		global $motivo;
		#$checked = "";
		#$checked = "checked";
			if($fila->fields['id_cobro'] == $id_cobro)
				$checked = "checked";
			else
				$checked = "";

			#if($fila->fields['id_cobro'] == $id_cobro)
			#	$checked = "checked";
	#		if($checked == "checked")
				$Check = "<input type='checkbox' $checked onclick=GrabarCampo('cobrar_trabajo','".$fila->fields['id_trabajo']."',$id_cobro,'');>";
	#		else
	#			$Check = "<input type='checkbox' onchange=GrabarCampo('cobrar_trabajo','".$fila->fields['id_trabajo']."','','')>";
		return $Check;
	}
	function Revisado(& $fila)
	{
		global $sesion;
		global $motivo;
		#$checked = "";
		#$checked = "checked";
		if($fila->fields['revisado'] == 1)
			$checked = "checked";
		else
			$checked = "";

		#if($fila->fields['id_cobro'] == $id_cobro)
		#   $checked = "checked";

		$Check = "<input type='checkbox' $checked onmouseover=\"ddrivetip('Para marcar un trabajo como revisado haga click aquí.&lt;br&gt;Los trabajos revisados no se desplegarán en este listado la próxima vez.')\" onmouseout=\"hideddrivetip();\" onchange=\"GuardarCampoTrabajo(".$fila->fields['id_trabajo'].",'revisado',this.checked ? 1 : 0)\">";
		return $Check;
	}

	function Opciones(& $trabajo)
	{
		$img_dir = Conf::ImgDir();
		global $motivo;
		$id_cobro = $trabajo->fields['id_cobro'];
		global $sesion;
		global $p_profesional;
		global $p_revisor;

		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);

		if($motivo == 'cobros')
		{
			$opc_html = Cobrable($trabajo);
		}
		$id_asunto = $fila->fields['id_asunto'];

		if($p_revisor->fields['permitido'])
		{
			if($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($trabajo->fields['id_cobro']))
				$opc_html.= "<a href=# onclick=\"nuevaVentana('Editar_Trabajo',600,500,'editar_trabajo.php?id_cobro=".$id_cobro."&id_trabajo=".$trabajo->fields[id_trabajo]."&popup=1','');\" title=".__('Editar')."><img src=$img_dir/editar_on.gif border=0></a>";
			else
				$opc_html.= "<a href=# onclick=\"alert('".__('No se puede modificar este trabajo.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.')."');\" title=\"".__('Cobro ya Emitido al Cliente')."\"><img src=$img_dir/editar_off.gif border=0></a>";

		}
		elseif($p_profesional->fields['permitido'])
		{
			if($trabajo->Estado()== 'Revisado')
				$opc_html .= "<img src=$img_dir/candado_16.gif border=0 title='".__('Este trabajo ya ha sido revisado')."'>";
			else
			{
				if($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($trabajo->fields['id_cobro']))
					$opc_html.= "<a href=# onclick=\"nuevaVentana('Editar_Trabajo',550,450,'editar_trabajo.php?id_cobro=".$id_cobro."&id_trabajo=".$trabajo->fields[id_trabajo]."&popup=1','');\" title=".__('Editar')."><img src=$img_dir/editar_on.gif border=0></a>";
				else
					$opc_html.= "<a href=# onclick=\"alert('".__('No se puede modificar este trabajo.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.')."');\" title=\"".__('Cobro ya Emitido al Cliente')."\" ><img src=$img_dir/editar_off.gif border=0></a>";
			}
		}
		else
			$opc_html .= "<img src=$img_dir/candado_16.gif border=0 title='".__('Usted no tiene permiso de Revisor')."'>";

		return $opc_html;
	}
	function SplitDuracion($time)
	{
		list($h,$m,$s) = split(":",$time);
		if($h > 0 || $s > 0)
			return $h.":".$m;
	}
	function funcionTR(& $trabajo)
	{
		global $sesion;
		global $id_cobro;
		global $p_revisor;
		global $p_cobranza;
		global $p_profesional;
		global $select_usuario;
		static $i = 0;

		if($trabajo->fields['id_tramite'] > 0)
		{
			$query = "SELECT glosa_tramite FROM tramite_tipo 
								JOIN tramite USING(id_tramite_tipo) 
								WHERE tramite.id_tramite=".$trabajo->fields['id_tramite'];
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($glosa_tramite)=mysql_fetch_array($resp);
		}
		
		
		if($i % 2 == 0)
			$color = "#dddddd";
		else
			$color = "#ffffff";
		
		if($trabajo->fields['id_tramite_tipo'] == 0)
		$tarifa = Funciones::Tarifa($sesion,$trabajo->fields['id_usuario'],$trabajo->fields['id_moneda_asunto'],$trabajo->fields['codigo_asunto']);
		else
		$tarifa = Funciones::TramiteTarifa($sesion, $trabajo->fields['id_tramite_tipo'],$trabajo->fields['id_moneda_asunto'],$trabajo->fields['codigo_asunto']); 
		list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);
		$duracion = $h + ($m > 0 ? ($m / 60) :'0');
		$total = round($tarifa * $duracion, 2);
		$total_horas += $duracion;
		#	if(substr($h,0,1)=='0')
		#		$h=substr($h,1);
		$dur_cob = "$h:$m";
		$formato_fecha = "%d/%m/%y";
		$fecha = Utiles::sql2fecha($trabajo->fields[fecha],$formato_fecha);
		if( $trabajo->fields['id_tramite_tipo'] > 0 ) {
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\">";
		$html .= "<td colspan=9><strong>".$trabajo->fields['glosa_tramite']."</strong></td></tr>";
		}
		$html .= "<tr id=\"t".$trabajo->fields[id_trabajo]."\" bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\">";
		$html .= '<td><input type="checkbox" onmouseover="ddrivetip(\'Para editar múltiples trabajos haga click aquí.\')" onmouseout="hideddrivetip();" ></td>';
		$html .= "<td>$fecha</td>";
		$html .= "<td>".$trabajo->fields[glosa_cliente]."</td>";
		$html .= "<td><a title='".$trabajo->fields['glosa_asunto']."'>".$trabajo->fields['glosa_asunto']."</a></td>";
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsoActividades') ) || ( method_exists('Conf','UsoActividades') && Conf::UsoActividades() ) )
		{
			if( $trabajo->fields['glosa_actividad'] == '')
				$trabajo->fields['glosa_actividad'] ='No Definida';
			$html .= "<td nowrap>". $trabajo->fields['glosa_actividad']."</td>";
		}
		$html .= "<td align=center>";
		$html .= $trabajo->fields['cobrable'] == 1 ? "SI" : "NO" ;
		if($p_cobranza->fields['permitido']&&$trabajo->fields['cobrable'] == 0)
		{
			$html .= $trabajo->fields['visible'] == 1 ? '<br>(visible)' : '<br>(no visible)' ;
		}
		$html .= "</td>";
		$duracion = $trabajo->fields['duracion'];
		//echo $duracion;
		if(!$p_revisor->fields['permitido'])
		{
			list($duracion_trabajada, $duracion_cobrada) = split('<br>',$trabajo->fields['duracion']);
			$duracion = $duracion_trabajada;
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
			{
					$duracion = UtilesApp::Time2Decimal($duracion_trabajada);
			}
		}
		else
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
			{
					list($duracion_trabajada, $duracion_cobrada) = split('<br>',$trabajo->fields['duracion']);
					$duracion = UtilesApp::Time2Decimal($duracion_trabajada) . "<br>" . UtilesApp::Time2Decimal($duracion_cobrada);
			}
		}
		//echo $duracion."fin<br>";
		if($p_cobranza->fields['permitido'])
		{
			$editar_cobro = "<a href='javascript:void(0)' onclick=\"nuevaVentana('Generar Cobro',750,660,'cobros5.php?popup=1&id_cobro=".$trabajo->fields['id_cobro']."');\"'>".$trabajo->fields['id_cobro']."</a>";
		}
		elseif($p_revisor->fields['permitido'])
		{
			$editar_cobro = $trabajo->fields['id_cobro'];
		}

        $html .= "<td align=center>".$duracion."</td>";
        if( $p_cobranza->fields['permitido'] || $p_revisor->fields['permitido'] )
        	$html .= "<td>".$editar_cobro."</td>";
        #$html .= "<td>".$trabajo->Estado()."</td>";
        if($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'] || strlen($select_usuario) > 164)
        	{
        		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsernameEnListaDeTrabajos') ) || ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') ) )
        			$html .= "<td>".$trabajo->fields['username']."</td>";
        		else
		        	$html .= "<td>".substr($trabajo->fields['nombre'],0,1).". ".$trabajo->fields['apellido1']."</td>";
		      }
				$html .= '<td align=center>'.Opciones(& $trabajo).'</td>';
        $html .= "</tr>";
        $html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B; \">";

			$desc_colspan=7;
				if($p_cobranza->fields['permitido'])
			$desc_colspan=8;
        if($p_revisor->fields['permitido'])
			$desc_colspan=5;
		$html .= "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
        $html .= "<td><strong>Desc.</strong></td><td colspan=$desc_colspan align=left>".stripslashes($trabajo->fields['descripcion'])."</td>";

		if( $p_revisor->fields['permitido'])
		{
			$html .= '<td>Rev.'.Revisado(& $trabajo).'</td>';
			$html .= "<td colspan=2 align=center><strong>".__('Tarifa')."</strong><br>".Utiles::glosa($sesion,$trabajo->fields[id_moneda_asunto],'simbolo','prm_moneda','id_moneda')." ".$tarifa."</td>";
		}
		$html .= "</tr>\n";
        $i++;
        return $html;
	}
?>
<script type="text/javascript">
Calendar.setup(
	{
		inputField	: "fecha_ini",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_ini"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_fin",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_fin"		// ID of the button
	}
);
</script>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo(Autocompletador::Javascript($sesion));
	}
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom($popup);
?>
