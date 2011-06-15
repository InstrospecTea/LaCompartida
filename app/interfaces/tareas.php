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
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('PRO','ADM'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	/**/
	$pagina->titulo = __('Listado de Tareas');

	/**/
	$hoy = date('d-m-Y');
	
	$pagina->PrintTop();

	$arreglo_estados = array('Por Asignar','Asignada','En Desarrollo','Por Revisar','Lista');
	$expandido = ' style = "display:none;" ';
	$opc = 'buscar';

	//Inicializador de Datos de Busqueda. Pueden provenir de:
	// Form (incluidas paginas del buscador
	// Url (si el popup, o los headers) invocaron Refrescar()

	if($otras_t)
		$opciones['otras_tareas'] = 1;
	if($t_mandante)
		$opciones['tareas_mandante'] = 1;
	if($t_responsable)
		$opciones['tareas_responsable'] = 1;
	if($t_revisor)
		$opciones['tareas_revisor'] = 1;
	if($t_encargado)
		$opciones['tareas_encargado'] = 1;

	if($fecha_desde)
		$opciones['fecha_desde'] = $fecha_desde;
	if($fecha_hasta)
		$opciones['fecha_hasta'] = $fecha_hasta;


	if($estados_elegidos)
		$estados = explode(',',$estados_elegidos);
	if(!is_array($estados)&&$estados)
		$estados = array($estados);
	if(is_array($estados))
	{
		$opciones['estado'] = $estados;
		$expandido = '';
	}
	else if(!$incluir_historicas)
	{
		$opciones['estado'] = array('Por Asignar','Asignada','En Desarrollo','Por Revisar');
	}

	if($fecha_desde || $fecha_hasta)
	{
		$expandido = '';
	}

	if( !($opciones['tareas_mandante'] || $opciones['tareas_responsable'] || $opciones['tareas_revisor'] || $opciones['otras_tareas'] || $opciones['tareas_encargado']) )
	{
		$opciones['tareas_mandante'] = true;
		$opciones['tareas_responsable'] = true;
		$opciones['tareas_revisor'] = true;
		$opciones['tareas_encargado'] = true;
	}

	$conf_codigo_primario = true;
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		$conf_codigo_primario = false;


	if(	$conf_codigo_primario )
	{
		if($cc)
			$codigo_cliente = $cc;
		if($ca)
			$codigo_asunto = $ca;
	}
	else
	{
		if($cc)
		{
			$codigo_cliente_secundario = $cc;
			$cliente = new Cliente($sesion);
			$codigo_cliente = $cliente->codigoSecundarioACodigo($codigo_cliente_secundario);
		}
		if($ca)
		{
			$codigo_asunto_secundario = $ca;
			$asunto = new Asunto($sesion);
			$codigo_asunto = $asunto->codigoSecundarioACodigo($codigo_asunto_secundario);
		}
	}

	if($codigo_cliente)
		$opciones['codigo_cliente'] = $codigo_cliente;
	if($codigo_asunto)
		$opciones['codigo_asunto'] = $codigo_asunto;



	if($orden_click)
		$opciones['orden'] = $orden_click;
	else if($orden_select)
		$opciones['orden'] = $orden_select;
?>

<script type="text/javascript">

function Validar()
{
	var form = $('formulario');
	form.action = 'tareas.php';
	return true;
}

function Excel()
{
	var form = $('formulario');
	form.action = 'tareas_excel.php';
	return true;
}

function ToggleExpandido()
{
	var a = $$(".expandido"); 					

	for (var n = 0; n < a.length; n++ )
		a[n].toggle();

	$('mas_columnas').toggle();
	$('menos_columnas').toggle();
}

function NuevaTarea()
{
	<?	if(	!$conf_codigo_primario )
		{ 
	?>
			var codigo_cliente_secundario = '';
			var codigo_asunto_secundario = '';
			
			if( $('codigo_cliente_secundario').value )
			codigo_cliente_secundario = '&codigo_cliente_secundario='+$('codigo_cliente_secundario').value;
			if( $('codigo_asunto_secundario').value )
			codigo_asunto_secundario = '&codigo_asunto_secundario='+$('codigo_asunto_secundario').value;
			
			var urlo = "agregar_tarea.php?popup=1"+codigo_cliente_secundario+codigo_asunto_secundario;
	<?  
		}
		else
		{ 
	?>
			var codigo_cliente = '';
			var codigo_asunto = '';

		
			if($('codigo_cliente').value)
			codigo_cliente = '&codigo_cliente='+$('codigo_cliente').value;
			codigo_asunto = '&codigo_asunto='+$('codigo_asunto').value;
		
			var urlo = "agregar_tarea.php?popup=1"+codigo_cliente+codigo_asunto;
	<?
		} 
	?>

	nuevaVentana('Agregar_Tarea',730,470,urlo,'scrollbars=yes, top=100, left=125');
}

function AbrirTarea(id_tarea)
{
	var urlo = "agregar_tarea.php?popup=1&id_tarea="+id_tarea;
	nuevaVentana('Agregar_Tarea',730,470,urlo,'scrollbars=yes, top=100, left=125');
}

//Ya que los cambios en el popup de Tarea cambian los datos, es importante refrescar la pagina con todos los parametros anteriores.
function Refrescar(orden)
{
	var opc= $('opc').value;

	<?
		if(	!$conf_codigo_primario )
		{
	?>
		var codigo_cliente = $('codigo_cliente_secundario').value;
		var codigo_asunto = $('codigo_asunto_secundario').value;
	<?
		} 
		else 
		{
	?>
		var codigo_cliente = $('codigo_cliente').value;
		var codigo_asunto = $('codigo_asunto').value;
	<? 
		} 
	?>

	var url = "tareas.php?opc="+opc+"&cc="+codigo_cliente+"&ca="+codigo_asunto+"&buscar=1";

	if($('otras_tareas').checked)
		url += "&otras_t=1";
	if($('tareas_mandante').checked)
		url += "&t_mandante=1";
	if($('tareas_responsable').checked)
		url += "&t_responsable=1";
	if($('tareas_revisor').checked)
		url += "&t_revisor=1";
	if($('tareas_encargado').checked)
		url += "&t_encargado=1";
	

	if($('buscador_tarea_desde'))
		url += "&desde="+$('buscador_tarea_desde').value;

	var opciones_estados = $('estados').options;
	var estados_elegidos = new Array();
	 for (var i = 0; i < opciones_estados.length; i++)
		if (opciones_estados[ i ].selected)
			estados_elegidos.push(opciones_estados[ i ].value);
	if(estados_elegidos.length > 0)
		url += '&estados_elegidos='+estados_elegidos.join(',');
	else if($('incluir_historicas').checked)
		url += '&incluir_historicas=1';
	

	if(orden)
		url += "&orden_click="+orden;
	else if($('orden'))
		url += "&orden_select="+$('orden').value;

	self.location.href= url;
}


</script>
<? echo(Autocompletador::CSS()); ?>
<form name=formulario id=formulario method=post action='tareas.php' autocomplete='off'>
<input type=hidden name=opc id=opc value=buscar />
<input type=hidden name=id_usuario id=id_usuario value=<?=$id_usuario?> />

<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>

<?
if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
		$width_tabla = 'width="90%"';
	else 
		$width_tabla = 'width="95%"';
?>
<!-- Fin calendario DIV -->
<center>
<table <?=$width_tabla ?>><tr><td>
<fieldset class="tb_base" id='field_filtros' style='display:inline;width: 100%;'>
	<legend>
		<span style="cursor:pointer" onclick="ToggleExpandido()" >
			<b>Filtros de Tareas</b>
			<span id='mas_columnas'   <?= $expandido==''? 'style="display:none"':'' ?> >
				<a href='#mas' style='font-size:9px; color:#184;' >expandir filtros</a>
			</span>
			<span id='menos_columnas' <?= $expandido==''? '':'style="display:none"' ?> >
				<a href='#menos' style='font-size:9px; color:#184;' >ocultar filtros</a>
			</span> 
		</span>
	</legend>

<table id=tbl_bitacora width='99%' border=0 cellpadding=3 cellspacing=3>
	<tr>
		<td align=right width='18%'>
		</td>
		<td align=left colspan=2 valign=top>
			<input type=checkbox name=opciones[tareas_responsable] id=tareas_responsable value=1 <?=$opciones['tareas_responsable'] ? 'checked' : '' ?> />
			<label for=tareas_responsable><?=__('Tareas en que soy Reponsable')?></label> 
		</td>
		<td>
			<input type=checkbox name=opciones[tareas_mandante] id=tareas_mandante value=1 <?=$opciones['tareas_mandante'] ? 'checked' : '' ?> />
			<label for=tareas_mandante><?=__('Tareas en que soy Mandante')?></label> 
		</td>
	</tr>
	<tr>
		<td align=right>
		</td>
		<td align=left colspan=2 width=36% valign=top>
			<input type=checkbox name=opciones[tareas_revisor] id=tareas_revisor value=1 <?=$opciones['tareas_revisor'] ? 'checked' : '' ?> />
			<label for=tareas_revisor><?=__('Tareas en que soy Revisor')?></label> 
		</td>
		<td>
			<input type=checkbox name=opciones[otras_tareas] id=otras_tareas value=1 <?=$opciones['otras_tareas'] ? 'checked' : '' ?> />
			<label for=otras_tareas><?=__('Tareas en las que no estoy relacionado.')?></label> 
		</td>
	</tr>
		<tr>
		<td align=right>
		</td>
		<td align=left colspan=2 width=36% valign=top>
			<input type=checkbox name=opciones[tareas_encargado] id=tareas_encargado value=1 <?=$opciones['tareas_encargado'] ? 'checked' : '' ?> />
			<label for=tareas_revisor><?=__('Clientes en los que soy encargado')?></label> 
		</td>
	</tr>
	<tr>
		<td align=right>Cliente:</td>
		<td align=left colspan=4>
			<?
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
					{
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
							{
								echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario,'',"220");
							}
						else
							{
								echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente,'','',"220");
							}
					}
					else
					{
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						{
							echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,""           ,"CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 220,$codigo_asunto_secundario);
						}
						else
						{
							echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 220,$codigo_asunto);
						}
					}
			?>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<? if(is_array($estados)) $incluir_historicas_disabled='disabled=disabled'; ?>
			<input type=checkbox name='incluir_historicas' id='incluir_historicas' <?=$incluir_historicas_disabled?> <?=$incluir_historicas? 'checked=checked':'' ?> />
			
			<label for=incluir_historicas style='font-size:10px;'/><?=__('Incluir Tareas Históricas')?></label>
		</td>
	</tr>
	<tr>
		<td align=right>Asunto:</td>
		<td align=left colspan=4>
		<?
				#Para Asuntos 
				if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 220,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto ,""," CargarSelectCliente(this.value);", 220,$codigo_cliente);
					}
		?>
		</td>
	</tr>
	<tr class=expandido <?=$expandido?>>
		<td align=right>
			<?=__('Estado')?>:
		</td>
		<td align=left width=20%>
			<select name="estados[]" id="estados" multiple size=<?=sizeof($arreglo_estados)?> onchange="if(this.selectedIndex==-1) $('incluir_historicas').disabled=false; else $('incluir_historicas').disabled=true; ">
				<?
					foreach($arreglo_estados as $e)
					{
						$selected = '';
						if(in_array($e,$estados))
							$selected = 'selected="selected"';
						echo "<option value='".$e."' ".$selected.">".$e."</option>";
					}
				?>
			</select>
		</td>
		<td colspan=2>
			<table width=100%>
				<tr>
					<td align=right width=26%>
						<?=__('Plazo desde')?>:
					</td>
					<td>
						<input type="text" name="fecha_desde" value="<?=$fecha_desde?>" id="fecha_desde" size="11" maxlength="10" />
						<div style="position:absolute; display:inline; margin-left:5px;">
							<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_desde" style="cursor:pointer" />
						</div>
					</td>
				</tr>
				<tr>
					<td align=right>
						<?=__('Plazo hasta')?>:
					</td>
					<td>
						<input type="text" name="fecha_hasta" value="<?=$fecha_hasta?>" id="fecha_hasta" size="11" maxlength="10" />
						<div style="position:absolute; display:inline; margin-left:5px;">
							<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_hasta" style="cursor:pointer" />
						</div>
					</td>
				</tr>
				<tr>
					<td align=right>
						<?=__('Ordenar por')?>:
					</td>
					<td>
						<?$arreglo_orden = array('Prioridad','Plazo','Cliente','Estado','Tarea','Novedad');?>
						<select name="opciones[orden]" id="orden">
							<? 
								foreach($arreglo_orden as $o)
								{
									$sel = '';
									if($opciones['orden']==$o)
										$sel =  'selected=selected';
									echo "<option value='".$o."' ".$sel.">".__($o)."</option>";
								}
							?>
						</select>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align=left colspan=2>
			<input type='submit' value='Buscar' class='btn'  onclick="return Validar();" >
			&nbsp;
			<input type='submit' value='Exportar a Excel' class='btn' onclick="return Excel();" >
		</td>
		<td align=right>
			<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="NuevaTarea()" title="Agregar Tarea"><?=__('Agregar')?> <?=__('tarea')?></a>
		</td>
	</tr>
</table>
</fieldset>
</td></tr></table>
</center>
<br>

<style>
	.contenedor_descripcion
	{
		overflow:hidden; white-space:nowrap; display: block;
	}
	.contenedor_descripcion_intermedio
	{
		overflow:hidden; white-space:nowrap; display:block; width: 300%;
	}
	.descripcion
	{
		color: #555;
	}
	.relacion_mia
	{
		font-weight: bold;
		color: #00B;
	}
	.relacion_otro
	{
		font-weight: bold;
		color: #2B0;
	}
	.relacion_none
	{
		font-weight: bold;
		color: #AAA;
	}
</style>

<?
	if($opc == 'buscar')
	{
		if( $codigo_cliente != '' )
			$opciones['codigo_cliente'] = $codigo_cliente;
		if( $codigo_cliente_secundario != '' )
			{
				$cliente = new Cliente($sesion);
				$opciones['codigo_cliente'] = $cliente->CodigoSecundarioACodigo( $codigo_cliente_secundario );
			}
		if( $codigo_asunto != '' )
			$opciones['codigo_asunto'] = $codigo_asunto;
		if( $codigo_asunto_secundario != '' ) 
			{
				$asunto = new Asunto($sesion);
				$opciones['codigo_asunto'] = $asunto->CodigoSecundarioACodigo( $codigo_asunto_secundario );
			}
		$orden = " tarea.prioridad DESC, ";
		if($opciones['orden'] == 'Cliente')
			$orden = " tarea.codigo_cliente ASC, tarea.codigo_asunto ASC, ";
		else if($opciones['orden'] == 'Estado')
			$orden = " tarea.orden_estado ASC, ";
		else if($opciones['orden'] == 'Tarea')
			$orden = " tarea.nombre ASC, ";
		else if($opciones['orden'] == 'Novedad')
			$orden = " tarea.fecha_ultima_novedad DESC, ";
		else if($opciones['orden'] == 'Plazo')
			$orden = '';
		
		$orden .= " tarea.fecha_entrega ASC ";

			$query = Tarea::query($opciones,$id_usuario);
			$b = new TareaBuscador($sesion, $query, "Objeto", $desde, 20, $orden);

			$ordenador_novedad = 'style="cursor:pointer" onclick="Refrescar(\'Novedad\');" ';
			$ordenador_plazo = "<a class=\"encabezado\" href=\"#\" onclick=\"Refrescar('Plazo')\" title=\"Ordenar registros\">";
			$ordenador_prioridad = "<a class=\"encabezado\" href=\"#\" onclick=\"Refrescar('Prioridad')\" title=\"Ordenar por Prioridad\">";
			$ordenador_tarea = "<a class=\"encabezado\" href=\"#\" onclick=\"Refrescar('Tarea')\" title=\"Ordenar registros\">";
			$ordenador_cliente = "<a class=\"encabezado\" href=\"#\" onclick=\"Refrescar('Cliente')\" title=\"Ordenar registros\">";

			$b->nombre = "busc_tareas";
			$b->AgregarFuncion($ordenador_plazo.__('Plazo').'</a>','Fecha', "align=left");	
			$b->AgregarFuncion($ordenador_prioridad.__('P').'</a>','Prioridad', "align=left");	
#			$b->AgregarEncabezado("prioridad ",__('P'),"align=left");
			$b->AgregarFuncion(__(''),'Estado', "align=left");
			$b->AgregarFuncion($ordenador_tarea.__('Tarea').'</a>','Tarea_Descripcion', "align=left style='overflow:hidden;' ");
			$b->AgregarFuncion(__('Relación&nbsp;&nbsp;Actor'),'Relaciones', "align=left");
			$b->AgregarFuncion($ordenador_cliente.__('Cliente').'</a>','Cliente_Asunto', "align=left style='overflow:hidden;' ");
			$b->AgregarFuncion("<img ".$ordenador_novedad." title='".__('Novedades')."' src='".Conf::ImgDir()."/ver_encuesta16.gif' />",'Novedades', "align=right");
			$b->funcion_argumento_tr = "funcion_argumento_tr";

			function funcion_argumento_tr(&$fila)
			{
				$h = " style='cursor:pointer;' ";
				$h .= " onclick='AbrirTarea(".$fila->fields['id_tarea'].")' "; 
				return $h;
			}

			function Relaciones(&$fila)
			{
				global $sesion;
				global $id_usuario;
				if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
				{
					$titulo_encargado = 'Responsable: '.$fila->fields['username_encargado'];
					$titulo_revisor = 'Revisor: '.$fila->fields['username_revisor'];
					$tilulo_generador = 'Generador: '.$fila->fields['username_generador'];
				}
				else
				{
					$titulo_encargado = 'Responsable: '.$fila->fields['encargado'];
					$titulo_revisor = 'Revisor: '.$fila->fields['revisor'];
					$titulo_generador = 'Mandante: '.$fila->fields['generador'];
				}
				
				if($id_usuario == $fila->fields['id_encargado'])
					$clase_encargado = 'relacion_mia';
				else if($fila->fields['id_encargado'])
					$clase_encargado = 'relacion_otro';
				else
				{
					$clase_encargado = 'relacion_none';
					$titulo_encargado = 'Sin Responsable';
				}

				if($id_usuario == $fila->fields['id_revisor'])
					$clase_revisor = 'relacion_mia';
				else if($fila->fields['id_revisor'])
					$clase_revisor = 'relacion_otro';
				else
				{
					$clase_revisor = 'relacion_none';
					$titulo_revisor = 'Sin Revisor';
				}

				if($id_usuario == $fila->fields['id_generador'])
					$clase_generador = 'relacion_mia';
				else if($fila->fields['id_generador'])
					$clase_generador = 'relacion_otro';
				else
				{
					$clase_generador = 'relacion_none';
					$titulo_generador = 'Sin Mandante';
				}


				$h .= "&nbsp;&nbsp;<span class='".$clase_encargado."' title='".$titulo_encargado."'>R</span>&nbsp;";
				$h .= "<span class='".$clase_revisor."' title='".$titulo_revisor."'>V</span>&nbsp;";
				$h .= "<span class='".$clase_generador."' title='".$titulo_generador."'>M</span>&nbsp;&nbsp;&nbsp;&nbsp;";

				$mostrar_username = ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') );
				switch($fila->fields['estado'])
				{
					case 'Asignada': case 'En Desarrollo':
						if($mostrar_username)
							$actor = $fila->fields['username_encargado'];
						else
							$actor = $fila->fields['mini_encargado'];
						$titulo = $titulo_encargado;
						break;
					case 'Por Revisar':
						if($mostrar_username)
							$actor = $fila->fields['username_revisor'];
						else
							$actor = $fila->fields['mini_revisor'];
						$titulo = $titulo_revisor;
						break;
					default: 
						if($mostrar_username)
							$actor = $fila->fields['username_generador'];
						else
							$actor = $fila->fields['mini_generador'];
						$titulo = $titulo_generador;
				}
				if($actor == '')
					$actor = '---';

				$h .= "<span class='Actor' title='".$titulo."'>".$actor."</span>";
				return $h;
			}

			function Tarea_Descripcion(&$fila)
			{
				$tarea = $fila->fields['nombre'];
				$descripcion = $fila->fields['detalle'];

				$in = "<span class=sujeto>".$tarea."</span>&nbsp;<span class=descripcion>- ".$descripcion.'</span>';
				return "	<div class='contenedor_descripcion'>
								<div class='contenedor_descripcion_intermedio'>
									<div class='contenedor_descripcion'>
										".$in."
									</div>
								</div>
							</div>";
			}

         function Prioridad(&$fila)
         {
            $prioridad = $fila->fields['prioridad'] ;

            return  "<span class=sujeto>".$prioridad."</span>&nbsp";
            $in = "<span class=sujeto>".$prioridad."</span>&nbsp";
            return " <div class='contenedor_descripcion'>
                        <div class='contenedor_descripcion_intermedio'>
                           <div class='contenedor_descripcion'>
                              ".$in."
                           </div>
                        </div>
                     </div>";
         }

			function Cliente_Asunto(&$fila)
			{
				$cliente = $fila->fields['glosa_cliente'];
				$asunto = $fila->fields['glosa_asunto'];
				
				$in = "<span class=sujeto>".$cliente."</span>&nbsp;<span class=descripcion>- ".$asunto.'</span>';
				return "	<div class='contenedor_descripcion'>
								<div class='contenedor_descripcion_in'>
									<div class='contenedor_descripcion'>
										".$in."
									</div>
								</div>
							</div>";
			}

			function Novedades(&$fila)
			{
				global $sesion;
				$id_usuario = $sesion->usuario->fields['id_usuario'];
				$id_tarea = $fila->fields['id_tarea'];
				$h = '0';

				$query = "		SELECT 
												tarea.id_tarea, 
												COUNT(tarea_comentario.id_comentario) AS comentarios, 
												COUNT(tarea_comentario_usuario.id_comentario) AS vistos
											FROM tarea
												JOIN tarea_comentario ON (tarea_comentario.id_tarea = tarea.id_tarea)
												JOIN usuario
												LEFT JOIN tarea_comentario_usuario ON (tarea_comentario_usuario.id_comentario = tarea_comentario.id_comentario AND tarea_comentario_usuario.id_usuario = '$id_usuario')
											WHERE usuario.id_usuario = '$id_usuario' AND tarea.id_tarea = '$id_tarea'
											GROUP BY tarea.id_tarea
											";
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				if($row = mysql_fetch_array($resp))
				{
					if(!$row['vistos'])
						$row['vistos'] = 0;
					if(!$row['comentarios'])
						$row['comentarios'] = 0;
					$nuevos = $row['comentarios'] - $row['vistos'];

					$h = $row['comentarios'];
					if($nuevos)
					{
						$espacio = '&nbsp;&nbsp;';
						if($row['comentarios'] >= 10)
							$espacio = '&nbsp;';
						$h = '<span title="'.$nuevos.' Novedades"><b>('.$nuevos.')</b></span>'.$espacio.$h;
					} 
				}
				return $h;
			}

			function Fecha(&$fila)
			{
				$fecha = Utiles::sql2date($fila->fields['fecha_entrega'],'%d-%m-%y');
				$split = explode('-',$fecha);
				if(mktime(0,0,0,$split[1],$split[0],$split[2]) <= mktime(0,0,0) )
				{
					if($fila->fields['estado'] != 'Lista')
						$fecha = '<span style="color:#B00" title="Atrasada"  >'.$fecha.'</span>';
				}

				return $fecha;
			}
			function Estado(&$fila)
			{
				return Tarea::IconoEstado($fila->fields['estado']);
			}

			function Opciones(&$fila)
			{
					global $sesion;
					$id_tarea = $fila->fields['id_tarea'];
			
					$o .= "<a href='javascript:void(0)' onclick=\"AbrirTarea(".$id_tarea.")\" ><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar></a>&nbsp;";
					$o .= "<a target=_parent href='javascript:void(0)' onclick=\"EliminarTarea(".$id_tarea.")\" ><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 title=Eliminar></a>";
				
					return $o;
			}

		$b->color_mouse_over = "#bcff5c";

		//Para crear las celdas interrumpidas, debo cambiar el Nodo <table> de Buscador. Para ello aplico esta función sobre el output:
		function cambiarHeader($buffer)
		{
			$antiguo = '<table cellpadding="3" class="buscador" width="100%">';
			$nuevo = '<table ellpadding="3" class="buscador" width="100%" style="table-layout:fixed;">
						<col width=8%>
						<col width=2%>
						<col width=2%>
						<col width=46%>
						<col width=12%>
						<col width=23%>
						<col width=7%>
			';
			return str_replace($antiguo,$nuevo,$buffer);
		}
		echo "<center>";
		ob_start("cambiarHeader");
			$b->Imprimir('',array(''),false);
		ob_end_flush();
		echo "</center>";
	}

	echo InputId::Javascript($sesion);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo Autocompletador::Javascript($sesion);
	}
?>
</form>
<script type="text/javascript">
	if (document.getElementById('img_fecha_desde'))
	{
		Calendar.setup(
			{
				inputField	: "fecha_desde",				// ID of the input field
				ifFormat		: "%d-%m-%Y",			// the date format
				button			: "img_fecha_desde"		// ID of the button
			}
		);
	}
	if (document.getElementById('img_fecha_hasta'))
	{
		Calendar.setup(
			{
				inputField	: "fecha_hasta",				// ID of the input field
				ifFormat		: "%d-%m-%Y",			// the date format
				button			: "img_fecha_hasta"		// ID of the button
			}
		);
	}
</script>
<?
	$pagina->PrintBottom($popup);
?>
