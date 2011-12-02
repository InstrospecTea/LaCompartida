<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('PRO','REV'));
	$pagina = new Pagina($sesion);

	$params_array['codigo_permiso'] = 'REV';
	$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	$params_array['codigo_permiso'] = 'PRO';
	$permiso_profesional = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

$where_query_listado_completo = mysql_real_escape_string(base64_decode($listado));
$where_query_listado_completo = str_replace("\'","'",$where_query_listado_completo);
$where_query_listado_completo = str_replace(";","",$where_query_listado_completo);
$where_query_listado_completo = ereg_replace("[dD][rR][oO][pP]","",$where_query_listado_completo);
$where_query_listado_completo = ereg_replace("[dD][eE][lL][eE][tT][eE]","",$where_query_listado_completo);
$where_query_listado_completo = ereg_replace("[aA][lL][tT][eE][rR][ ]*[tT][aA][bB][lL][eE]","",$where_query_listado_completo);


	if($where_query_listado_completo)
	{
	$query_listado_completo = "SELECT trabajo.id_trabajo 
																			 FROM trabajo 
																			 JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto 
																			 LEFT JOIN actividad ON trabajo.codigo_actividad=actividad.codigo_actividad 
																			 LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente 
																			 LEFT JOIN cobro ON cobro.id_cobro=trabajo.id_cobro 
																			 LEFT JOIN contrato ON asunto.id_contrato=contrato.id_contrato 
																			 LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario 
																			WHERE $where_query_listado_completo"; 
	}
	
	if($query_listado_completo)
	{
		//$query = mcrypt_decrypt(MCRYPT_CRYPT,Conf::Hash(),$listado_completo,MCRYPT_ENCRYPT);
		$resp = mysql_query($query_listado_completo, $sesion->dbh) or Utiles::errorSQL($query_listado_completo,__FILE__,__LINE__,$sesion->dbh);
		$ids="";
		while($trabajo_temporal_query=mysql_fetch_array($resp))
		{
			$ids.="t".$trabajo_temporal_query['id_trabajo'];
		}
	}
	

	// Parsear el string con la lista de ids, vienen separados por el caracter 't'.
	$i=0;
	$id = array();
	$id[$i++] = strtok($ids, "t");
	while ($id[$i-1] != false)
		$id[$i++] = strtok("t");
	array_pop($id);	// El último token es null, así que lo descartamos.

	// Cargar cada trabajo en un arreglo.
	for($i=0; $i<count($id); ++$i)
	{
		$t[$i] = new Trabajo($sesion) or die('No se pudo cargar el trabajo '. $id[$i].'.');
		$t[$i]->Load($id[$i]);
	}
	// Para cargar los valores del primer trabajo en el form.
	$id_trabajo = $id[0];

	//$cobrable = 0;
	$total_minutos_cobrables=0;
	$total_minutos_trabajados=0;
	$horas_cobrables=0;
	$minutos_cobrables=0;
	for($i=0; $i<count($id); ++$i)
	{
		if($t[$i]->Estado() == 'Cobrado' && $opcion != 'nuevo')
		{
			$pagina->AddError(__('Trabajos masivos ya cobrados'));
			$pagina->PrintTop($popup);
			$pagina->PrintBottom($popup);
			exit;
		}
		if($t[$i]->Estado() == 'Revisado' && $opcion != 'nuevo')
		{
			if(!$permisos->fields['permitido'])
			{
				$pagina->AddError(__('Trabajo ya revisado'));
				$pagina->PrintTop($popup);
				$pagina->PrintBottom($popup);
				exit;
			}
		}
		if($codigo_asunto != $t[$i]->fields['codigo_asunto'])
		{
			$cambio_asunto[$i] = true;
		}
		list($h,$m,$s)=split(':',$t[$i]->fields['duracion_cobrada']);
		$minutos=($h*60)+$m;
		$total_minutos_cobrables+=$minutos;//se calcula en minutos porque el intervalo es en minutos

		list($h,$m,$s)=split(':',$t[$i]->fields['duracion']);
		$minutos=($h*60)+$m;
		//echo $minutos.' min. <br>';

		$total_minutos_trabajados+=$minutos;//se calcula en minutos porque el intervalo es en minutos
	}
	if(!isset($total_duracion_cobrable_horas) && !isset($total_duracion_cobrable_minutos))
	{
		$total_duracion_cobrable_horas=floor($total_minutos_cobrables/60);
		$total_duracion_cobrable_minutos=floor($total_minutos_cobrables%60);
	}

	if(!$codigo_asunto_secundario && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
	{
		//se carga el codigo secundario
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($t[0]->fields['codigo_asunto']);
		$codigo_asunto_secundario=$asunto->fields['codigo_asunto_secundario'];
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($asunto->fields['codigo_cliente']);
		$codigo_cliente_secundario=$cliente->fields['codigo_cliente_secundario'];
		$codigo_cliente=$asunto->fields['codigo_cliente'];
	}

	/* OPCION -> Guardar else Eliminar */
	if($opcion == "guardar")
	{
		$valida = true;
		$asunto = new Asunto($sesion);
		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
			$codigo_asunto=$asunto->fields['codigo_asunto'];
		}
		else
		{
			$asunto->LoadByCodigo($codigo_asunto);
		}

		/*
		Ha cambiado el asunto del trabajo se setea nuevo Id_cobro de alguno que esté creado
		y corresponda al nuevo asunto y esté entre las fechas que corresponda, sino, se setea NULL
		*/
		for($i=0; $i<count($id); ++$i)
		{
			if($cambio_asunto[$i])
			{
				$cobro = new Cobro($sesion);
				$id_cobro_cambio = $cobro->ObtieneCobroByCodigoAsunto($codigo_asunto, $t[$i]->fields['fecha']);
				if($id_cobro_cambio)
					$t[$i]->Edit('id_cobro',$id_cobro_cambio);
				else
					$t[$i]->Edit('id_cobro','NULL');
			}
		}
		$contadorModificados = 0;
		$tiempo_total_minutos_editado=($total_duracion_cobrable_horas*60)+$total_duracion_cobrable_minutos;//total escrito por usuario en minutos

		if($total_minutos_cobrables)
			$divisor=$tiempo_total_minutos_editado/$total_minutos_cobrables;
		else if( $tiempo_total_minutos_editado > 0 && $total_minutos_cobrables == 0)
		{//Si el divisor es 0, cambiamos el divisor por el numero de trabajos. Más adelante se debe cambiar la duracion de cada trabajo (0) por 1 minuto.
			$divisor= $tiempo_total_minutos_editado/$total_minutos_trabajados;
			$forzar_editado_divisor_cero = true;
		}
	
		$cont=0;
		for($i=0; $i<count($id); ++$i)
		{
			$cont++;
			$t[$i]->Edit('codigo_asunto', $codigo_asunto);
			
			if(!$permiso_profesional->fields['permitido'] || $permisos->fields['permitido']) {
				if(!$cobrable)
				{
					$t[$i]->Edit('cobrable', '0');
					$t[$i]->Edit('visible', $visible?'1':'0');
				}
				else
				{
					$t[$i]->Edit('cobrable','1');
					$t[$i]->Edit('visible','1');
				}
			}
			if($asunto->fields['cobrable']==0)//Si el asunto no es cobrable
			{
				$t[$i]->Edit("cobrable",'0');
				$pagina->AddInfo(__('El Trabajo ').$t[$i]->fields['id_trabajo'].__(' se guardó como NO COBRABLE (Por Maestro).'));
			}

			//Se modifica las horas cobrables de los trabajos con prorrateo según nuevo/total
			//Se tiene en cuenta que no puede quedar entremedio del intervalo
			if($cont==1)$tiempo_total_minutos_temporal=0;

			if($forzar_editado_divisor_cero)
				$total_minutos_cobrables = $total_minutos_trabajados;
			if($tiempo_total_minutos_editado!=$total_minutos_cobrables || $forzar_editado_divisor_cero)
			{
				list($h,$m,$s)=split(':',$t[$i]->fields['duracion_cobrada']);
				$minutos=($h*60)+$m;
				//Si no tenia horas cobrables, se hace la proporcion de todo trabajo como si hubiese tenido 1 min.
				if($forzar_editado_divisor_cero)
				{
					list($h,$m,$s)=split(':',$t[$i]->fields['duracion']);
					$minutos=($h*60)+$m;
				}
				$tiempo_trabajo_minutos_contador+=$minutos;
				
				$tiempo_trabajo_minutos_temporal=$tiempo_trabajo_minutos_contador*$divisor;
				
				//echo $tiempo_trabajo_minutos_temporal." = ".$tiempo_trabajo_minutos_contador." * ".$divisor." <br>";

				$tiempo_trabajo_minutos_editado=$tiempo_trabajo_minutos_temporal-$tiempo_total_minutos_temporal;
				if( method_exists('Conf','GetConf') && ( (1000*$tiempo_trabajo_minutos_editado)%(1000*Conf::GetConf($sesion,'Intervalo'))!=0 ) )
				{
					$tiempo_restante=((1000*$tiempo_trabajo_minutos_editado)%(1000*Conf::GetConf($sesion,'Intervalo')))/1000;
					$tiempo_trabajo_minutos_editado-=$tiempo_restante;
				}
				else if( method_exists('Conf','Intervalo') && (1000*$tiempo_trabajo_minutos_editado)%(1000*Conf::Intervalo())!=0 )
				{
					$tiempo_restante=((1000*$tiempo_trabajo_minutos_editado)%(1000*Conf::Intervalo()))/1000;
					$tiempo_trabajo_minutos_editado-=$tiempo_restante;
				}
				if($i==(count($id)-1))
				{
					$tiempo_trabajo_minutos_editado=$tiempo_total_minutos_editado-$tiempo_total_minutos_temporal;
				}
				else
				{
					$tiempo_total_minutos_temporal+=$tiempo_trabajo_minutos_editado;
				}
				//echo " Editado: ".$tiempo_trabajo_minutos_editado."<br>";


				if($tiempo_trabajo_minutos_editado >= 1440)
					$dia_sobrepasado = true;
				$t[$i]->Edit('duracion_cobrada',UtilesApp::Decimal2Time($tiempo_trabajo_minutos_editado/60));
				
				
			}

			
		}
		for($i=0; $i<count($id); ++$i)
		{
			if(!$dia_sobrepasado && $t[$i]->Write())
			{
				++$contadorModificados;
			}
		}

		if($dia_sobrepasado)				
				$pagina->AddError(__('No se pudo modificar los ').__('trabajo').'s. '.__('Una duración sobrepasó las 24 horas.'));
		if($contadorModificados == 1)
		{
			$pagina->AddInfo(__('Trabajo').' '.__('Guardado con exito'));
			?>
				<script>
					window.opener.Refrescar();
				</script>
			<?
		}
		elseif($contadorModificados > 0)
		{
			$pagina->AddInfo($contadorModificados.' '.__('trabajo').'s '.__('guardados con exito.'));
			?>
				<script>
					window.opener.Refrescar();
				</script>
			<?
		}
		#refresca el listado de horas.php cuando se graba la informacion desde el popup

		unset($id_trab);
	}
	else if($opcion == "eliminar") #ELIMINAR TRABAJOS
	{
		for($i=0; $i<count($id); ++$i)
		{
			if(! $t[$i]->Eliminar() )
				$pagina->AddError($t[$i]->error);
		}
		if(count($id)==1)
			$pagina->AddInfo(__('Trabajo').' '.__('eliminado con éxito'));
		else
			$pagina->AddInfo(count($id).' '.__('trabajo').'s '.__('aliminados con éxito.'));
?>
		<script>
			window.opener.Refrescar();
		</script>
<?
	}

	/* Título opcion */
	if($opcion == '' && $id_trabajo > 0)
		$txt_opcion = __('Modificación masiva de Trabajos');
	else if($opcion == 'nuevo')
		$txt_opcion = __('Agregando nuevo Trabajo');
	else if($opcion == '')
		$txt_opcion = '';

	#	$codigo_cliente = Utiles::Glosa($sesion,$id_cobro,'codigo_cliente','cobro','id_cobro');
	if($t[0])
		$codigo_cliente = $t[0]->get_codigo_cliente();
	$pagina->titulo = __('Modificación masiva de').' '.__('Trabajos');
	$pagina->PrintTop($popup);
?>
<script type=text/javascript>
function Validar(form)
{
<?
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			{
				echo "if(!form.codigo_asunto_secundario.value){";
			}
			else
			{
				echo "if(!form.codigo_asunto.value){";
			}
?>
			alert("<?=__('Debe seleccionar un').' '.__('asunto')?>");
<?
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			{
				echo "form.codigo_asunto_secundario.focus();";
			}
			else
			{
				echo "form.codigo_asunto.focus();";
			}
?>
		return false;
	}

	// Esta variable vale 0 si ningún trabajo cambia su estado de "cobrable", 1 si pasan trabajos no cobrables a cobrables y 2 si pasan trabajos cobrables a no cobrables
	var cambiaCobrable = 0;
	var cobrableOriginal = new Array();
	var n;
<?
	for($i=0; $i<count($id); ++$i)
		if($t[$i]->fields['cobrable'] == 0)
			echo("cobrableOriginal[".$i."] = 0;");
		else
			echo("cobrableOriginal[".$i."] = 1;");
?>
	for(i in cobrableOriginal)
		if(form.cobrable.checked && cobrableOriginal[i] == 0)
		{
			cambiaCobrable = 1;
			break;
		}
		else if(!form.cobrable.checked && cobrableOriginal[i] == 1)
		{
			cambiaCobrable = 2;
			break;
		}

	// Avisar si se pasan trabajos no cobrables a cobrables
	if(cambiaCobrable == 1 && !confirm('Uno o más trabajos no cobrables pasarán a estado cobrable. ¿Desea continuar?'))
		return false;
	// Avisar si se pasan trabajos cobrables a no cobrables
	else if(cambiaCobrable == 2 && !confirm('Uno o más trabajos cobrables pasarán a estado no cobrable. ¿Desea continuar?'))
		return false;

	//Valida si el asunto ha cambiado para este trabajo que es parte de un cobro, si ha cambiado se emite un mensaje indicandole lo ki pa
	if(ActualizaCobro(form.codigo_asunto.value))
		return true;
	return false;
}

/*Clear los elementos*/
function DivClear(div, dvimg)
{
	var left_data = document.getElementById('left_data');
	var content_data = document.getElementById('content_data');
	var right_data = document.getElementById('right_data');
	left_data.innerHTML = '';
	content_data.innerHTML = '';
	right_data.innerHTML = '';

	var content = document.getElementById('content_data2');
	var right = document.getElementById('right_data2');
	content.innerHTML = '';
	right.innerHTML = '';

	if( div == 'tr_cliente' )
	{
		var img = document.getElementById( 'img_asunto' );
		img.innerHTML = '<img src="<?=Conf::ImgDir()?>/mas.gif" border="0" title="Mostrar" class="mano_on" onClick="ShowDiv(\'tr_asunto\',\'inline\',\'img_asunto\');">';
	}
	else
	{
		var img = document.getElementById( 'img_historial' );
		img.innerHTML = '<img src="<?=Conf::ImgDir()?>/mas.gif" border="0" title="Mostrar" class="mano_on" onClick="ShowDiv(\'tr_cliente\',\'inline\',\'img_historial\');">';
	}
}

function ShowDiv(div, valor, dvimg)
{
	var div_id = document.getElementById(div);
	var img = document.getElementById(dvimg);
	var form = document.getElementById('form_editar_trabajo');
	var codigo = document.getElementById('campo_codigo_cliente').value;
	var tr = document.getElementById('tr_cliente');
	var tr2 = document.getElementById('tr_asunto');
	var al = document.getElementById('al');
	//var tbl_trabajo = document.getElementById('tbl_trabajo');

	DivClear(div, dvimg);

	if( div == 'tr_asunto' && codigo == '')
	{
		tr.style['display'] = 'none';
		alert("<?=__('Debe seleccionar un cliente')?>");
		form.codigo_cliente.focus();
		return false;
	}

	div_id.style['display'] = valor;
	/* FADE
	if(valor == 'inline')
		var fade = true;
	else
		var fade = false;
	setTimeout("MSG('"+div+"',"+fade+")",10);
	*/

	if( div == 'tr_cliente' )
	{
		WCH.Discard('tr_asunto');
		tr2.style['display'] = 'none';
		Lista('lista_clientes','left_data','','');
	}
	else if( div == 'tr_asunto' )
	{
		WCH.Discard('tr_cliente');
		tr.style['display'] = 'none';
		Lista('lista_asuntos','content_data2',codigo,'2');
	}

	/*Cambia IMG*/
	if(valor == 'inline')
	{
		WCH.Apply('tr_asunto');
		WCH.Apply('tr_cliente');
		img.innerHTML = '<img src="<?=Conf::ImgDir()?>/menos.gif" border="0" title="Ocultar" class="mano_on" onClick="ShowDiv(\''+div+'\',\'none\',\''+dvimg+'\');">';
	}
	else
	{
		WCH.Discard(div);
		img.innerHTML = '<img src="<?=Conf::ImgDir()?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\''+div+'\',\'inline\',\''+dvimg+'\');">';
	}
}

/*
AJAX Lista de datos historial
accion -> llama ajax
div -> que hace update
codigo -> codigo del parámetro necesario SQL
div_post -> id div posterior onclick
*/
function Lista(accion, div, codigo, div_post)
{
	var form = document.getElementById('form_editar_trabajo');
	var data = document.getElementById(div);
	hideddrivetip();
	if(accion == 'lista_asuntos')
	{
		form.campo_codigo_cliente.value = codigo;
		SetSelectInputId('campo_codigo_cliente','codigo_cliente');
<?
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo "CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos');";
		}
		else
		{
			echo "CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos');";
		}
?>
	}
	else if(accion == 'lista_trabajos')
	{
		form.campo_codigo_asunto.value = codigo;
		SetSelectInputId('campo_codigo_asunto','codigo_asunto');
<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsoActividades') ) || ( method_exists('Conf','UsoActividades') && Conf::UsoActividades() ) )
	 { ?>
		CargarSelect('codigo_asunto','codigo_actividad','cargar_actividades');
<? }?>
	}

	var http = getXMLHTTP();

	if(div == 'content_data')
	{
		var right_data = document.getElementById('right_data');
		right_data.innerHTML = '';
	}

		var vurl = 'ajax_historial.php?accion='+accion+'&codigo='+codigo+'&div_post='+div_post+'&div='+div;
	http.open('get', vurl, true);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			data.innerHTML = response;
		}
	};
	http.send(null);
}

function UpdateTrabajo(id_trabajo, descripcion, codigo_actividad, duracion, duracion_cobrada, cobrable, visible, fecha)
{
	var form = document.getElementById('form_editar_trabajo');
	form.campo_codigo_actividad.value = codigo_actividad;
	SetSelectInputId('campo_codigo_actividad','codigo_actividad');

	form.duracion.value = duracion;
	if( document.getElementById('duracion_cobrada') )
		form.duracion_cobrada.value = duracion_cobrada;
	form.cobrable.checked = cobrable > 0 ? true : false;
	form.visible.checked = visible > 0 ? true : false;
	form.descripcion.value = descripcion;
	var fecha_arr = fecha.split('-',3);
	var m = document.getElementById('fecha_Month_ID');
	var d = document.getElementById('fecha_Day_ID');
	var a = document.getElementById('fecha_Year_ID');
	for( i=0; i<m.options.length; i++ )
	{
		if( parseInt(m.options[i].value) == parseInt(fecha_arr[1]-1) )
		{
			m.options[i].selected = true;
			fecha_Object.changeMonth(m);
		}
	}
	for(i=0; i<d.options.length; i++)
	{
		if( parseInt(d.options[i].text) == fecha_arr[2] )
		{
			d.options[i].selected = true;
			fecha_Object.changeDay(d);
		}
	}
	if(fecha_arr[0])
	{
		a.value = fecha_arr[0];
		fecha_Object.fixYear(a);
		fecha_Object.checkYear(a);
	}

	form.fecha.value = fecha;
	var tr = document.getElementById('tr_cliente');
	var tr2 = document.getElementById('tr_asunto');
	var img = document.getElementById('img_historial');
	var img2 = document.getElementById('img_asunto');

	WCH.Discard('tr_asunto');
	WCH.Discard('tr_cliente');
	tr.style['display'] = 'none';
	tr2.style['display'] = 'none';

	img.innerHTML = '<img src="<?=Conf::ImgDir()?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\'tr_cliente\',\'inline\',\'img_historial\');">';

	img2.innerHTML = '<img src="<?=Conf::ImgDir()?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\'tr_asunto\',\'inline\',\'img_asunto\');">';
}

function ActualizaCobro(valor)
{
	var codigosOriginales = new Array();
	var id_cobro = new Array();
	var id_trabajo = new Array();
	var fecha_trabajo = new Array();
<?
	for($i=0; $i<count($id); ++$i)
	{
		echo('codigosOriginales['.$i.'] = "'.$t[$i]->fields['codigo_asunto'] . '";');
		echo('id_trabajo['.$i.'] = "'.$t[$i]->fields['id_trabajo'] . '";');
		echo('fecha_trabajo['.$i.'] = "'.$t[$i]->fields['fecha'] . '";');
		if($t[$i]->fields['id_cobro'] != NULL)
			echo("id_cobro[".$i."] = '".$t[$i]->fields['id_cobro']."';");
		else
			echo("id_cobro[".$i."] = null;");
	}
?>

	var form = $('form_editar_trabajo');

	var cambio = false;
	var i;
	// Revisar si se ha cambiado el cobro de algún trabajo, para pedir confirmación.
	for (i=0; i<codigosOriginales.length; ++i)
	{
		if(codigosOriginales[i] != valor && id_cobro[i] != null)
		{
			cambio = true;
			break;
		}
	}
	if(cambio)
	{
		if(confirm('Ud. está modificando un trabajo que pertenece <?php echo __('al cobro'); ?>: '+id_cobro[i]+' . Si acepta, el trabajo se desvinculará de este <?php echo __('cobro'); ?> y eventualmente se vinculará a <?php echo __('un cobro'); ?> pendiente para el nuevo asunto en caso de que exista.'))
		{
		var hacer_submit = true;
			for (i in codigosOriginales)
			{
				if(! ActualizarCobroAsunto(valor, codigosOriginales[i], id_cobro[i], id_trabajo[i], fecha_trabajo[i]))
					hacer_submit = false;
			}
			if(hacer_submit)
				form.submit();
			return true;
		}
		else
		{
			return false;
		}
	}
	return true;
}

function ActualizarCobroAsunto(valor, codigo_asunto_hide, id_cobro, id_trabajo, fecha_trabajo_hide)
{
	var http = getXMLHTTP();
	var urlget = 'ajax.php?accion=set_cobro_trabajo&codigo_asunto='+valor+'&id_trabajo='+id_trabajo+'&fecha='+fecha_trabajo_hide+'&id_cobro_actual='+id_cobro;
	http.open('get',urlget, true);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
		}
	};
	http.send(null);
	return true;
}

//Cuando se le saca el check de cobrable se hace visible = 0
function CheckVisible()
{
	if(!$('chkCobrable').checked)
	{
		<? if($permisos->fields['permitido']) { ?>
			$('chkVisible').checked=false;
		<?
			}
			else
			{
		?>
			$('hiddenVisible').value=0;
		<?}?>
	}
}
</script>
<style>
A:link,A:visited {font-size:9px;text-decoration: none}
A:hover {font-size:9px;text-decoration:none; color:#990000; background-color:#D9F5D3}
A:active {font-size:9px;text-decoration:none; color:#990000; background-color:#D9F5D3}
</style>
<?
echo(Autocompletador::CSS());
if($opcion == "eliminar")
{
	echo '<button onclick="window.close();">'.__('Cerrar ventana').'</button>';
}
else
{
?>
<form id="form_editar_trabajo" name=form_editar_trabajo method="post" action="<?=$_SERVER[PHP_SELF]?>">
<input type=hidden name=opcion value="guardar" />
<?
	if( $opcion != 'nuevo' )
	{
?>
<input type=hidden name='edit' value="<?= $opcion == 'edit' ? 1 : '' ?>" id='edit' />
<?
	}
	else
	{
?>
<input type=hidden name='nuevo' value="<?= $opcion == 'nuevo' ? 1 : '' ?>" id='nuevo' />
<?
	}
?>
<input type=hidden name=popup value='<?=$popup?>' id="popup">

<!-- TABLA HISTORIAL -->
<table id="tr_cliente" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td colspan="7" class="td_transparente">&nbsp;</td>
	</tr>
	<tr>
		<td class="td_transparente">&nbsp;</td>
		<td class="td_transparente" colspan="5" align="right">
			<img style="filter:alpha(opacity=100);" src="<?=Conf::ImgDir()?>/cruz_roja_13.gif" border="0" class="mano_on" alt="Ocultar" onClick="ShowDiv('tr_cliente','none','img_historial');">
		</td>
		<td class="td_transparente">&nbsp;</td>
	</tr>
	<tr>
		<td width="5%" class="td_transparente">&nbsp;</td>
		<td width="30%" id="leftcolumn" class="box_historial">
			<div id="titulos">
				<?=__('Cliente') ?>
			</div>
			<div id="left_data" class="span_data"></div>
		</td>
		<td class="td_transparente">
		</td>
		<td width="30%" id="content" class="box_historial">
			<div id="titulos">
				<?=__('Asunto') ?>
			</div>
			<div id="content_data" class="span_data"></div>
		</td>
		<td class="td_transparente">
		</td>
		<td width="30%" id="rightcolumn" class="box_historial">
			<div id="titulos">
				<?=__('Trabajo') ?>
			</div>
			<div id="right_data" class="span_data"></div>
		</td>
		<td width="5%" class="td_transparente">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="7" class="td_transparente" style="height:190px">&nbsp;</td>
	</tr>
</table>
<!-- TABLA SOBRE ASUNTOS -->
<table id="tr_asunto" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td colspan="6" class="td_transparente">&nbsp;</td>
	</tr>
	<tr>
		<td class="td_transparente">&nbsp;</td>
		<td align="right" colspan="4" class="td_transparente">
			<img src="<?=Conf::ImgDir()?>/cruz_roja_13.gif" border="0" class="mano_on" alt="Ocultar" onClick="ShowDiv('tr_asunto','none','img_asunto');">
		</td>
		<td class="td_transparente">&nbsp;</td>
	</tr>
	<tr>
		<td width="5%" class="td_transparente">&nbsp;</td>
		<td width="45%" id="content" class="box_historial">
			<div id="titulos">
				<?=__('Asunto') ?>
			</div>
			<div id="content_data2" class="span_data"></div>
		</td>
		<td class="td_transparente">
		</td>
		<td width="45%" id="rightcolumn" class="box_historial">
			<div id="titulos">
				<?=__('Trabajo') ?>
			</div>
			<div id="right_data2" class="span_data"></div>
		</td>
		<td class="td_transparente">
		</td>
		<td width="5%" class="td_transparente">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6" class="td_transparente" style="height:190px">&nbsp;</td>
	</tr>
</table>
<?
if($txt_opcion)
{
?>
<table style='border:1px solid black' <?=$txt_opcion ? 'style=display:inline' : 'style=display:none'?> width=90%>
	<tr>
		<td align=left><span style=font-weight:bold; font-size:9px; backgroundcolor:#c6dead><?=$txt_opcion?></span></td>
	</tr>
</table>
<br>
<?
}
?>
<table style='border:1px solid black' id="tbl_trabajo" width=90%>
	<tr>
		<td align=center>
			<span id="img_historial" onMouseover="ddrivetip('Historial de trabajos ingresados')" onMouseout="hideddrivetip()"><img src="<?=Conf::ImgDir()?>/mas.gif" border="0" class="mano_on" id="img_historial" onClick="ShowDiv('tr_cliente','inline','img_historial');"></span>&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario);
		else	
			echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
	}
	else
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"" ,"CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320,$codigo_asunto_secundario);
		}
		else
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320,$codigo_asunto);
		}
	}
?>
		</td>
	 </tr>
	 <tr>
		<td align='center'>
			<span id="img_asunto"><img src="<?=Conf::ImgDir()?>/mas.gif" border="0" id="img_asunto" class="mano_on" onMouseover="ddrivetip('Historial de trabajos ingresados')" onMouseout="hideddrivetip()" onClick="ShowDiv('tr_asunto','inline','img_asunto');"></span>&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
		<td align='right'>
			 <?=__('Asunto')?>
		</td>
		<td align=left>
			<?

					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargaIdioma(this.value);CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $t[0]->fields['codigo_asunto'],"","CargaIdioma(this.value); CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}

?>
		</td>
	</tr>
	<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsoActividades') ) || ( method_exists('Conf','UsoActividades') && Conf::UsoActividades() ) )
			{ ?>
				<tr>
					<td colspan="2" align=right>
						<?=__('Actividad')?>
					</td>
					<td align=left>
						<?= InputId::Imprimir($sesion,"actividad","codigo_actividad","glosa_actividad", "codigo_actividad", $t[0]->fields[codigo_actividad]) ?>
					</td>
				</tr>
	<?  }
		else
			{ ?>
	<input type="hidden" name="codigo_actividad" id="codigo_actividad">
	<input type="hidden" name="campo_codigo_actividad" id="campo_codigo_actividad">
	<? }?>
<?
	if($permisos->fields['permitido'])
	{
?>
	<tr>
		<td colspan="2" align=right>
			<?=__('Total Horas') ?>
		</td>
		<td align=left>
			<input type="text" name="total_duracion_cobrable_horas" size=5 value=<?=$total_duracion_cobrable_horas ?> />
			&nbsp;<?=__('Hrs')?>&nbsp;
			<input type="text" name="total_duracion_cobrable_minutos" size=5 value=<?=$total_duracion_cobrable_minutos ?> />
			&nbsp;<?=__('Min')?>&nbsp;&nbsp;&nbsp;<span style="color:red;font-size:7pt"><?=__('(se modificará la duración cobrable de los trabajos seleccionados)') ?></span>
		</td>
	</tr>

<? } ?>
<?
	if($permisos->fields['permitido'])
		$where = "usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'";
	else
		$where = "usuario_secretario.id_secretario = '".$sesion->usuario->fields['id_usuario']."'
							OR usuario.id_usuario IN ('$id_usuario','" . $sesion->usuario->fields['id_usuario'] . "')";
	$where .= " AND usuario.visible=1";
?>

<?php if(!$permiso_profesional->fields['permitido'] || $permisos->fields['permitido']) { ?>
	<tr>
		<td colspan="2" align=right>
			<?=__('Cobrable')?><br/>
		</td>
		<td align="left">
			<input type="checkbox" name="cobrable" value="1"
			<?
			for($i=0; $i<count($id); ++$i)
					// Si por lo menos uno de los trabajos seleccionados es cobrable el checkbox "cobrable" aparece seleccionado por defecto.
					if($t[$i]->fields['cobrable'] == 1)
					{
						echo 'checked="checked"';
						$hay_alguno_cobrable = true;
						break;
					}
			?>
			id="chkCobrable" onClick="CheckVisible();" />
			&nbsp;&nbsp;
			<div id="divVisible" <?= $hay_alguno_cobrable? 'style="display:none"':'style="display:inline"'?> >
			<? if($permisos->fields['permitido']) { ?>
				<?=__('Visible')?>
				<input type="checkbox" name="visible" value="1"
<?
			for($i=0; $i<count($id); ++$i)
					// Si por lo menos uno de los trabajos seleccionados es cobrable el checkbox "visible" aparece seleccionado por defecto.
					if($t[$i]->fields['visible'] == 1)
					{
						echo 'checked="checked"';
						break;
					}
?>
				id="chkVisible" onMouseover="ddrivetip('Trabajo será visible en la <?php echo __('Nota de Cobro'); ?>')" onMouseout="hideddrivetip()" />
			<? }
				else
				{
			?>
				<input type="hidden" name="visible" value="<?= $t[0]->fields['visible'] ? $t[0]->fields['visible'] : 1 ?>" id="hiddenVisible" />
			<?
				}
			?>
			</div>
		</td>
	</tr>
<?php } ?>
<?
	if(isset($t[0]) && $t[0]->Loaded() && $opcion != 'nuevo')
	{
		echo("<tr><td colspan=2></td><td colspan=3 align=left>");
		echo("<a onclick=\"return confirm('".__('¿Desea eliminar estos trabajos?')."')\" href=?opcion=eliminar&ids=".$ids."&popup=$popup><span style=\"border: 1px solid black; background-color: #ff0000;color:#FFFFFF;\">&nbsp;Eliminar trabajos&nbsp;</span></a>");
		echo("</td></tr>");
	}
?>
	<tr>
		<td colspan='3' align='right'>
			<input type="hidden" name="opcion" value="guardar" />
			<input type="hidden" name="ids" value="<? echo(''.$ids); ?>" />
			<input type="submit" class="btn" value="<?=__('Guardar')?>" onclick="return Validar(this.form);" />
		</td>
	</tr>
</table>
</form>

<?
}
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo(Autocompletador::Javascript($sesion));
	}
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom($popup);
	function SplitDuracion($time)
	{
		list($h,$m,$s) = split(":",$time);
		return $h.":".$m;
	}
	function Substring($string)
	{
		if(strlen($string) > 250)
			return substr($string, 0, 250)."...";
		else
			return $string;
	}
?>
<script language="javascript" type="text/javascript">
$('chkCobrable').observe('click',
	function(evento){
		if(!this.checked)
		{
			$('divVisible').style['display']="inline";
		}
		else
		{
			$('divVisible').style['display']="none";
		}
	});
</script>