<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Tramite.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('PRO','REV'));
	$pagina = new Pagina($sesion);

	$params_array['codigo_permiso'] = 'REV';
	$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

	// Parsear el string con la lista de ids, vienen separados por el caracter 't'.
	$i=0;
	$id = array();
	$id[$i++] = strtok($ids, "t");
	while ($id[$i-1] != false)
		$id[$i++] = strtok("t");
	array_pop($id);	// El último token es null, así que lo descartamos.

	// Cargar cada trámite en un arreglo.
	for($i=0; $i<count($id); ++$i)
	{
		$tramite[$i] = new Tramite($sesion) or die('No se pudo cargar el trámite '. $id[$i].'.');
		$tramite[$i]->Load($id[$i]);
		if( $tramite[$i]->fields['trabajo_si_no']==1 )
		{
			$t[$i] = new Trabajo($sesion) or die('No se pudo cargar el trabajo '. $id[$i].'.');
			$t[$i]->Load($id[$i]);
		}
	}
	// Para cargar los valores del primer trámite en el form.
	$id_tramite = $id[0];

	for($i=0; $i<count($id); ++$i)
	{
		if($tramite[$i]->Estado() == 'Cobrado' && $opcion != 'nuevo')
		{
			$pagina->AddError(__('Trámites masivos ya cobrados'));
			$pagina->PrintTop($popup);
			$pagina->PrintBottom($popup);
			exit;
		}
		if($tramite[$i]->Estado() == 'Revisado' && $opcion != 'nuevo')
		{
			if(!$permisos->fields['permitido'])
			{
				$pagina->AddError(__('Trámite ya revisado'));
				$pagina->PrintTop($popup);
				$pagina->PrintBottom($popup);
				exit;
			}
		}
		if($codigo_asunto != $tramite[$i]->fields['codigo_asunto'])
		{
			$cambio_asunto[$i] = true;
		}
	}

	if(!$codigo_asunto_secundario && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
	{
		//se carga el codigo secundario
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($tramite[0]->fields['codigo_asunto']);
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
		Ha cambiado el asunto del trámites se setea nuevo Id_cobro de alguno que esté creado
		y corresponda al nuevo asunto y esté entre las fechas que corresponda, sino, se setea NULL
		*/
		for($i=0; $i<count($id); ++$i)
		{
			if($cambio_asunto[$i])
			{
				$cobro = new Cobro($sesion);
				$id_cobro_cambio = $cobro->ObtieneCobroByCodigoAsunto($codigo_asunto, $tramite[$i]->fields['fecha']);
				if($id_cobro_cambio)
					{
					$tramite[$i]->Edit('id_cobro',$id_cobro_cambio);
					if($t[$i]) $t[$i]->Edit('id_cobro',$id_cobro_cambio);
					}
				else
					{
					$tramite[$i]->Edit('id_cobro','NULL');
					if($t[$i]) $t[$i]->Edit('id_cobro','NULL');
					}
			}
		}
		$contadorModificados = 0;
		$cont=0;
		for($i=0; $i<count($id); ++$i)
		{
			$cont++;
			$tramite[$i]->Edit('codigo_asunto',$codigo_asunto);
			if($t[$i]) $t[$i]->Edit('codigo_asunto',$codigo_asunto);

			if($tramite[$i]->Write() && ( $t[$i]->Write() || !$t[$i] ) )
			{
				++$contadorModificados;
			}
		}
		if($contadorModificados == 1)
		{
			$pagina->AddInfo(__('Trámite').' '.__('guardado con exito'));
			?>
				<script>
					window.opener.Refrescar();
				</script>
			<?
		}
		elseif($contadorModificados > 0)
		{
			$pagina->AddInfo($contadorModificados.' '.__('trámite').'s '.__('guardados con exito.'));
			?>
				<script>
					window.opener.Refrescar();
				</script>
			<?
		}
		#refresca el listado de horas.php cuando se graba la informacion desde el popup
	}
	else if($opcion == "eliminar")  #ELIMINAR TRÁMITES
	{
		for($i=0; $i<count($id); ++$i)
		{
			if(! $tramite[$i]->Eliminar() )
				$pagina->AddError($t[$i]->error);
		}
		if(count($id)==1)
			$pagina->AddInfo(__('Trámite').' '.__('eliminado con éxito'));
		else
			$pagina->AddInfo(count($id).' '.__('trámite').'s '.__('eliminados con éxito.'));
?>
		<script>
			window.opener.Refrescar();
		</script>
<?
	}

	/* Título opcion */
	if($opcion == '' && $id_tramite > 0)
		$txt_opcion = __('Modificación masiva de Trámites');
	else if($opcion == 'nuevo')
		$txt_opcion = __('Agregando nuevo Trámite');
	else if($opcion == '')
		$txt_opcion = '';

	#	$codigo_cliente = Utiles::Glosa($sesion,$id_cobro,'codigo_cliente','cobro','id_cobro');
	if($tramite[0])
		$codigo_cliente = $tramite[0]->get_codigo_cliente();
	$pagina->titulo = __('Modificación masiva de').' '.__('Trámites');
	$pagina->PrintTop($popup);
?>
<script type=text/javascript>
function Validar(form)
{
<?
			if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
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
			if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
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

	// Esta variable vale 0 si ningún trámite cambia su estado de "cobrable", 1 si pasan trámites no cobrables a cobrables y 2 si pasan tramites cobrables a no cobrables
	var cambiaCobrable = 0;
	var cobrableOriginal = new Array();
	var n;
<?
	for($i=0; $i<count($id); ++$i)
		if($tramite[$i]->fields['cobrable'] == 0)
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

	// Avisar si se pasan trámites no cobrables a cobrables
	if(cambiaCobrable == 1 && !confirm('Uno o más trámites no cobrables pasarán a estado cobrable. ¿Desea continuar?'))
		return false;
	// Avisar si se pasan trámites cobrables a no cobrables
	else if(cambiaCobrable == 2 && !confirm('Uno o más trámites cobrables pasarán a estado no cobrable. ¿Desea continuar?'))
		return false;

	//Valida si el asunto ha cambiado para este trámite que es parte de un cobro, si ha cambiado se emite un mensaje indicandole lo ki pa
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
	var form = document.getElementById('form_editar_tramite');
	var codigo = document.getElementById('campo_codigo_cliente').value;
	var tr = document.getElementById('tr_cliente');
	var tr2 = document.getElementById('tr_asunto');
	var al = document.getElementById('al');
	//var tbl_tramite = document.getElementById('tbl_tramite');

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
	}
	else
	{
		WCH.Discard(div);
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
	var form = document.getElementById('form_editar_tramite');
	var data = document.getElementById(div);
	hideddrivetip();
	if(accion == 'lista_asuntos')
	{
		form.campo_codigo_cliente.value = codigo;
		SetSelectInputId('campo_codigo_cliente','codigo_cliente');
<?
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo "CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos');";
		}
		else
		{
			echo "CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos');";
		}
?>
	}
	else if(accion == 'lista_tramites')
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

function UpdateTramite(id_tramite, descripcion, duracion, fecha)
{
	var form = document.getElementById('form_editar_tramite');
	
	form.duracion.value = duracion;
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
	var img2 = document.getElementById('img_asunto');

	WCH.Discard('tr_asunto');
	WCH.Discard('tr_cliente');
	tr.style['display'] = 'none';
	tr2.style['display'] = 'none';
}

function ActualizaCobro(valor)
{
	var codigosOriginales = new Array();
	var id_cobro = new Array();
	var id_tramite = new Array();
	var fecha_tramite = new Array();
<?
	for($i=0; $i<count($id); ++$i)
	{
		echo('codigosOriginales['.$i.'] = "'.$tramite[$i]->fields['codigo_asunto'] . '";');
		echo('id_tramite['.$i.'] = "'.$tramite[$i]->fields['id_tramite'] . '";');
		echo('fecha_tramite['.$i.'] = "'.$tramite[$i]->fields['fecha'] . '";');
		if($tramite[$i]->fields['id_cobro'] != NULL)
			echo("id_cobro[".$i."] = '".$tramite[$i]->fields['id_cobro']."';");
		else
			echo("id_cobro[".$i."] = null;");
	}
?>

	var form = $('form_editar_tramite');

	var cambio = false;
	var i;
	// Revisar si se ha cambiado el cobro de algún trámite, para pedir confirmación.
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
				if(! ActualizarCobroAsunto(valor, codigosOriginales[i], id_cobro[i], id_tramite[i], fecha_tramite[i]))
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

function ActualizarCobroAsunto(valor, codigo_asunto_hide, id_cobro, id_tramite, fecha_tramite_hide)
{
	var http = getXMLHTTP();
	var urlget = 'ajax.php?accion=set_cobro_tramite&codigo_asunto='+valor+'&id_tramite='+id_tramite+'&fecha='+fecha_tramite_hide+'&id_cobro_actual='+id_cobro;
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
</script>
<? echo(Autocompletador::CSS()); ?>
<style>
A:link,A:visited {font-size:9px;text-decoration: none}
A:hover {font-size:9px;text-decoration:none; color:#990000; background-color:#D9F5D3}
A:active {font-size:9px;text-decoration:none; color:#990000; background-color:#D9F5D3}
</style>
<?
if($opcion == "eliminar")
{
	echo '<button onclick="window.close();">'.__('Cerrar ventana').'</button>';
}
else
{
?>
<form id="form_editar_tramite" name=form_editar_tramite method="post" action="<?=$_SERVER[PHP_SELF]?>">
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
		<td colspan="6" class="td_transparente">&nbsp;</td>
	</tr>
	<tr>
		<td class="td_transparente">&nbsp;</td>
		<td class="td_transparente" colspan="4" align="right">
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
				<?=__('Trámite') ?>
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
				<?=__('trámite') ?>
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
<table style='border:1px solid black' id="tbl_tramite" width=90%>
	<tr>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left>
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
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,""           ,"CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320,$codigo_asunto_secundario);
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
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $tramite[0]->fields['codigo_asunto'],"","CargaIdioma(this.value); CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}
			?>
	   </td>
	</tr>

<?
	if(isset($tramite[0]) && $tramite[0]->Loaded() && $opcion != 'nuevo')
	{
		echo("<tr><td></td><td colspan=3 align=left>");
		echo("<a onclick=\"return confirm('".__('¿Desea eliminar estos trámites?')."')\" href=?opcion=eliminar&ids=".$ids."&popup=$popup><span style=\"border: 1px solid black; background-color: #ff0000;color:#FFFFFF;\">&nbsp;Eliminar trámites&nbsp;</span></a>");
		echo("</td></tr>");
	}
?>
	<tr>
		<td colspan='2' align='right'>
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

