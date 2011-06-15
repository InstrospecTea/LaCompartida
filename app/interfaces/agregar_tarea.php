<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/SelectorHoras.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Tarea.php';
	require_once Conf::ServerDir().'/classes/TareaComentario.php';
	require_once Conf::ServerDir().'/classes/UsuarioExt.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('PRO'));
	$pagina = new Pagina($sesion);
	$id_usuario_actual = $sesion->usuario->fields['id_usuario'];

	$arreglo_estados = array('Por Asignar','Asignada','En Desarrollo','Por Revisar','Lista');

	$tarea = new Tarea($sesion);
	if($id_tarea)
	{
		$tarea->Load($id_tarea);
		$id_usuario_registro = $tarea->fields['usuario_registro'];
	}

	$conf_codigo_primario = true;
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		$conf_codigo_primario = false;
	
	if($tarea->loaded() && (!$codigo_cliente || !$codigo_cliente_secundario))
	{
		$codigo_cliente = $tarea->fields['codigo_cliente'];
		$codigo_asunto = $tarea->fields['codigo_asunto'];

		if( !$conf_codigo_primario )
		{
			$cliente = new Cliente($sesion);
			$asunto = new Asunto($sesion);

			$codigo_cliente_secundario = $cliente->CodigoSecundarioACodigo($codigo_cliente);
			$codigo_asunto_secundario = $asunto->CodigoSecundarioACodigo($codigo_asunto);
		}
	}


	if($opcion == "guardar")
	{
		$tarea->Edit("fecha_entrega",Utiles::fecha2sql($fecha));
		$tarea->Edit("nombre",$nombre);
		$tarea->Edit("detalle",$detalle);
		$tarea->Edit("prioridad",$prioridad);
		$tarea->Edit("alerta",$alerta);

		//Revisa el Conf si esta permitido y la función existe02-09-2010 16:54:17
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
		{
			$tarea->Edit("tiempo_estimado",UtilesApp::Decimal2Time($duracion));
		}
		else
			$tarea->Edit("tiempo_estimado",$duracion);

		if(!$id_usuario_registro)
			$tarea->Edit("usuario_registro",$id_usuario_actual);

		if( $codigo_cliente_secundario != '' )
			{
				$cliente = new Cliente($sesion);
				$codigo_cliente = $cliente->CodigoSecundarioACodigo( $codigo_cliente_secundario );
			}
		if( $codigo_asunto_secundario != '' )
			{
				$asunto = new Asunto($sesion);
				$codigo_asunto = $asunto->CodigoSecundarioACodigo( $codigo_asunto_secundario );
			}
		$tarea->Edit("codigo_cliente",$codigo_cliente);
		$tarea->Edit("codigo_asunto",$codigo_asunto);
		
		if($id_usuario_encargado)
			$tarea->Edit("usuario_encargado",$id_usuario_encargado);
		else
			$tarea->Edit("usuario_encargado",'NULL');
		if($id_usuario_revisor)
			$tarea->Edit("usuario_revisor",$id_usuario_revisor);
		else
			$tarea->Edit("usuario_revisor",'NULL');
		if($id_usuario_generador)
			$tarea->Edit("usuario_generador",$id_usuario_generador);
		else
			$tarea->Edit("usuario_generador",'NULL');
	

		if($estado == 'inicial')
		{
			if($tarea->fields['usuario_encargado'])
				$tarea->Edit("estado","Asignada");
			else
				$tarea->Edit("estado","Por Asignar");
		}
		else
		{
			$tarea->Edit("estado",$estado);
			foreach($arreglo_estados as $k => $es)
				if($estado == $es)
					$tarea->Edit("orden_estado",$k+1);
		}

		

		if ($tarea->Write())
		{
			$pagina->AddInfo(__('Tarea').' '.__('guardada con exito'));
			$js_refrescar = "window.opener.Refrescar('');";
		}
	}


	$usuario_generador = new UsuarioExt($sesion);
	if($id_usuario_generador)
		$usuario_generador->LoadId($id_usuario_generador);
	else
		$usuario_generador->LoadId($id_usuario_actual);

	$txt_pagina = $tarea->loaded() ? __('Edición de Tarea').' :: '.$tarea->fields['nombre'] : __('Ingreso de Tarea');
	$req = '<span style="color:#FF0000; font-size:10px">*</span>';


	$pagina->titulo = $txt_pagina;
	$pagina->PrintTop($popup);
?>

<script type="text/javascript">
<?=$js_refrescar?>

function CambiarEncargado()
{
	if($('id_usuario_encargado').selectedIndex == 0)
	{
		if($('estado').selectedIndex == 1)
			$('estado').selectedIndex = 0;
	}
	else
	{
		if($('estado').selectedIndex == 0)
			$('estado').selectedIndex = 1;
	}
}

function ResizeBitacora(size)
{
			currentfr = document.getElementById('bitacora'); 
			currentfr.height = size+'px'; // currentfr.Document.body.scrollHeight;
}

function ActualizarTiempoIngresado()
{
	var url = "ajax_tareas.php?accion=refrescar_tiempo_ingresado";
	url += "&id_tarea=<?=$id_tarea?>";

	new Ajax.Request(url, {asynchronous: true, parameters : '', onComplete:  CambiarTiempo});
}

function CambiarTiempo(xmlHttpRequest, responseHeader)
{
	var response = xmlHttpRequest.responseText;
	if(response)
	{
		if(response.indexOf('head')!=-1)
		{
					alert('<?=__('Sesión Caducada')?>');
					top.location.href='<?=Conf::Host()?>';
		}
		
		var tiempo = response;
		$('tiempo_ingresado').value = tiempo;
	}
}

function ActualizarEstado(indice)
{
		$('estado').selectedIndex = indice;
}

function Cerrar()
{
	window.close();
}

function Validar(form)
{
	var err = false;

	<?
		if($conf_codigo_primario)
		{
	?>
			var form_codigo_cliente = form.codigo_cliente;
			var form_codigo_asunto = form.codigo_asunto;
	<?
		}
		else
		{
	?>
			var form_codigo_cliente = form.codigo_cliente_secundario;
			var form_codigo_asunto = form.codigo_asunto_secundario;
	<?
		}
	?>

	if(!form_codigo_cliente.value)
	{
		alert('<?=__('Debe seleccionar un cliente')?>');
		form_codigo_cliente.focus();
    	return false;
	}
	if(!form_codigo_asunto.value)
	{
		alert('<?=__('Ud. debe seleccionar un').' '.__('asunto')?>');
		form_codigo_asunto.focus();
		return false;
	}

	if(form.nombre.value == '')
	{
		alert('<?=__('Debe ingresar un nombre para la Tarea')?>');
		form.nombre.focus();
		return false;
	}

	if(form.fecha.value == '')
	{
		if(confirm('<?=__('La tarea se ingresará sin Fecha de Entrega')?>'))
		{
			return true;
		}
		else
		{
			form.fecha.focus();
			return false;
		}
	}

	return true;
}

function CambiaDuracion(form, input)
{
	if(document.getElementById('duracion_cobrada') && input=='duracion')
		form.duracion_cobrada.value = form.duracion.value;
}
</script>

<? if($tarea->loaded()) 
   {
	$tarea_comentario = new TareaComentario($sesion);
	$tarea_comentario->setTarea($tarea->fields['id_tarea']);
	$tarea_comentario->setUsuario($id_usuario_actual);
	$tarea_comentario->setAsunto($tarea->fields['codigo_asunto']);
	echo $tarea_comentario->js_TareaComentario();
	echo $tarea_comentario->css_TareaComentario();
   }

echo(Autocompletador::CSS());
?>
<form method=post action="<?= $SERVER[PHP_SELF] ?>" onsubmit="return Validar(this);" id="form_gastos" autocomplete='off'>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name="gIsMouseDown" id="gIsMouseDown" value=false />
<input type=hidden name="max_hora" id="max_hora" value=999999999 />
<input type=hidden name="gRepeatTimeInMS" id="gRepeatTimeInMS" value=200 />
<input type=hidden name=id_tarea value="<?= $tarea->fields['id_tarea'] ?>" />

<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td valign="top" align="left" class="titulo" bgcolor="<?=( method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ColorTituloPagina'):Conf::ColorTituloPagina())?>">
				<?=$txt_pagina?>
			</td>
		</tr>
</table>
<br>


<table width='100%'>
	<tr>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left colspan=3>
			<?
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
					{
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
							{
								echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario,'',"320");
							}
						else
							{
								echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente,'','',"320");
							}
					}
					else
					{
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
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
		<td align=right>
			<?=__('Asunto')?>
		</td>
		<td align=left colspan=3>
			<?
				if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto ,"","CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}
			?>
			<?=$req?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Nombre')?>
		</td>
		<td align=left colspan=3>
			<input name='nombre' id='nombre' size=40 value="<?=$tarea->fields['nombre'] ? $tarea->fields['nombre'] : '' ?>" /> <?=$req?>
		</td>
	</tr>
	<tr id='descripcion_tarea'>
		<td align=right>
			<?=__('Detalle')?>
		</td>
		<td align=left colspan=3>
			<textarea id='detalle' name='detalle' cols="45" rows="3"><?=$tarea->fields['detalle']?></textarea>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Fecha de Entrega')?>
		</td>
		<td align=left colspan=1>
			<input type="text" name="fecha" value="<?=$tarea->fields['fecha_entrega'] ? Utiles::sql2date($tarea->fields['fecha_entrega']) : $fecha ?>" id="fecha" size="11" maxlength="10" />
			<div style="position:absolute; display:inline; margin-left:5px;">
				<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
			</div>
		</td>
      <td align=right>
         <?=__('Prioridad')?>
      </td>
      <td align=left>
			<select name='prioridad' id='prioridad' title="10 mayor prioridad, 1 menor prioridad" >
			<? for($i = 1; $i < 11; $i++){?>
				<option value="<?=$i?>" 
					<? if($tarea->fields['prioridad'])
					{
							if($tarea->fields['prioridad'] == $i)
								echo 'selected';
					}
					else
						if( $i == 5)
							echo 'selected';
					?> 
				> 
					<?=$i?>
				</option>
			<?}?>
			</select>
      </td>
	<tr>
	  <td align=right>
         <?=__('Alerta')?>
      </td>
	  <td align=left>
			<select name="alerta" id="alerta">
				<option value="0"  <?= $tarea->fields['alerta']==0? 'selected':'' ?> ><?=__("Sin Alerta")?></option>
				<option value="1"  <?= $tarea->fields['alerta']==1? 'selected':'' ?> ><?=__("1 día antes")?></option>
				<option value="2"  <?= $tarea->fields['alerta']==2? 'selected':'' ?> ><?=__("2 días antes")?></option>
				<option value="5"  <?= $tarea->fields['alerta']==5? 'selected':'' ?> ><?=__("5 días antes")?></option>
				<option value="10" <?= $tarea->fields['alerta']==10? 'selected':'' ?>><?=__("10 días antes")?></option>
			</select>
	  </td>
	</tr>
</tr>
	<tr>
		<td colspan=4>
			<br />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Usuario Responsable')?>
		</td>
		<td align=left width=20%>
			<?= Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario ORDER BY apellido1", "id_usuario_encargado", $tarea->fields['usuario_encargado']? $tarea->fields['usuario_encargado']:$id_usuario_actual,"onchange='CambiarEncargado();'", __('Ninguno'),'170'); ?>
		</td>
		<td align=right width=24%>
			<?=__('Estado')?>		
		</td>
		<td align=left>
			<select name=estado id=estado>
				<?
					foreach($arreglo_estados as $e)
					{
						$selected = '';
						if($id_tarea)
						{
							if($tarea->fields['estado'] == $e)
								$selected = 'selected="selected"';
						}
						else if($e == 'Asignada')
							$selected = 'selected="selected"';
						echo "<option value='".$e."' ".$selected.">".$e."</option>";
					}
				?>
			</select>
		</td>
		
	</tr>
	<tr>
		<td align=right>
			<?=__('Usuario Revisor')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario ORDER BY apellido1", "id_usuario_revisor", $tarea->fields['usuario_revisor']? $tarea->fields['usuario_revisor']:$id_usuario_actual,"", __('Ninguno'),'170'); ?>
		</td>
		<td align=right>
			<?=__('Duración Estimada')?>
		</td>
		<td align=left>

			<?
			$input_estimado =  Html::PrintTime("duracion",$tarea->fields['tiempo_estimado'],"onchange='CambiaDuracion(this.form ,\"duracion\");' ",true);
			$input_estimado = str_replace('size="6"','size="7"',$input_estimado);

			//Revisa el Conf si esta permitido y la función existe
			if( method_exists('Conf','GetConf') )
			{
				if( Conf::GetConf($sesion,'TipoIngresoHoras')=='selector' )
				{
					$tiempo_estimado = '00:00:00';
					if($tarea->fields['tiempo_estimado'])
						$tiempo_estimado = $tarea->fields['tiempo_estimado'];
						
						$input_estimado = SelectorHoras::PrintTimeSelector($sesion,"duracion", $tiempo_estimado, '');
				}
				else if( Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' )
				{
						$tiempo_estimado = '';
						if($tarea->fields['tiempo_estimado'])
							$tiempo_estimado =  UtilesApp::Time2Decimal($tarea->fields['tiempo_estimado']);

						$input_estimado = '<input type="text" name="duracion" value="" id="duracion" size="7" maxlength=4   onchange="CambiaDuracion(this.form,\"duracion\");"/>';
				}
			}
			else if (method_exists('Conf','TipoIngresoHoras'))
			{
				if(Conf::TipoIngresoHoras()=='selector')
				{
					$tiempo_estimado = '00:00:00';
					if($tarea->fields['tiempo_estimado'])
						$tiempo_estimado = $tarea->fields['tiempo_estimado'];
						
						$input_estimado = SelectorHoras::PrintTimeSelector($sesion,"duracion", $tiempo_estimado, '');
				}
				else if(Conf::TipoIngresoHoras()=='decimal')
				{
						$tiempo_estimado = '';
						if($tarea->fields['tiempo_estimado'])
							$tiempo_estimado =  UtilesApp::Time2Decimal($tarea->fields['tiempo_estimado']);

						$input_estimado = '<input type="text" name="duracion" value="" id="duracion" size="7" maxlength=4   onchange="CambiaDuracion(this.form,\"duracion\");"/>';
				}
			}

			echo $input_estimado;
			?>
		</td>
	</tr>
	<tr>
		
		
		<td align=right >
			<?=__('Usuario Mandante')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario ORDER BY apellido1", "id_usuario_generador", $tarea->fields['usuario_generador']? $tarea->fields['usuario_generador']:$id_usuario_actual,"", __('Ninguno'),'170'); ?>
		</td>
		<td align=right>
			<?=__('Duración Ingresada')?>		
		</td>
		<td align=left>
			<input readonly=readonly value='<?=$tarea->getTiempoIngresado();?>' id=tiempo_ingresado size=7 />
		</td>
	</tr>
	<tr>
		
	</tr>

	<? if($tarea->loaded()) { ?>
	<tr>
		<td align=right>
		</td>
		<td align=left colspan=3>
			
		</td>
	</tr>
	<? } ?>
	<tr>
		<td>
				<br />
		</td>
		<td align=right colspan=3>
			<?if($tarea->loaded()) { ?>
			<span style="font-size:10px;"><i><?=__('Tarea ingresada el').'&nbsp;'.Utiles::sql2fecha($tarea->fields['fecha_creacion']);?>&nbsp;&nbsp;&nbsp;&nbsp;</span>
			<?}?>
		</td>
	</tr>
	<tr>
		<td align=center colspan=4>
			<input type=submit class=btn value="<?=__('Guardar')?>" onclick='return Validar(this.form);' /> <input type=button class=btn value="<?=__('Cerrar')?>" onclick="Cerrar();" />
		</td>
	</tr>


</table>

	<? if(!$tarea->loaded()) { ?>
		<input type="hidden" name="estado" value="inicial" />
	<? } ?>

<br>
</form>

<? if($tarea->loaded()) 
   {
		//<!-- INGRESO - COMENTARIOS - AVANCES - ARCHIVOS -->
		// echo "<hr />";
		//echo $tarea_comentario->Abrir();

?>
<!-- BITACORA -->
	<hr />
	<div id="comentarios" align=center>
		

	<?  $url_iframe = 'tareas_comentarios.php?id_tarea='.$tarea->fields['id_tarea']; 
		$alto_iframe = '100';
	?>

	<iframe name='bitacora' id='bitacora' src='<?=$url_iframe ?>' frameborder=0 width=95% height=<?=$alto_iframe?>px>
	</iframe> 
	
	</div>
<? }?>

<script type="text/javascript">
if (document.getElementById('img_fecha'))
{
	Calendar.setup(
		{
			inputField	: "fecha",				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: "img_fecha"		// ID of the button
		}
	);
}
</script>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo Autocompletador::Javascript($sesion);
	}
	echo InputId::Javascript($sesion);
	echo SelectorHoras::Javascript();
	$pagina->PrintBottom($popup);
?>
