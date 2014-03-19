<?
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion();
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$pagina->titulo = __('Listado de Comentarios');
	$pagina->PrintTop(1);

	$infos = array();
	$errors = array();

	$arreglo_estados = array('Por Asignar','Asignada','En Desarrollo','Por Revisar','Lista');


	$tarea	= new Tarea($sesion);
	$tarea->Load($id_tarea);

	if($opcion == 'eliminar')
	{
		$tarea_comentario = new TareaComentario($sesion);
		$trabajo = new Trabajo($sesion);

		if($id_comentario)
		{
			$tarea_comentario->Load($id_comentario);

			$id_trabajo = $tarea_comentario->fields['id_trabajo'];
			if($id_trabajo)
			{
				$trabajo->Load($id_trabajo);

				if($trabajo->Eliminar())
					$infos[] = __('El Trabajo ha sido eliminado satisfactoriamente');
				else
					$errors[] = $trabajo->error;
			}

			if($tarea_comentario->Eliminar())
			{
					$infos[] = __('El Detalle ha sido eliminado satisfactoriamente');
					$js .= "parent.ActualizarTiempoIngresado();";
			}
				else
					$errors[] =__('Error al eliminar Detalle');
		}
	}

	if($opcion == 'guardar')
	{
		$tarea_comentario	= new TareaComentario($sesion);
		$asunto				= new Asunto($sesion);
		$trabajo			= new Trabajo($sesion);
		$archivo			= new Archivo($sesion);

		if($id_comentario)
		{
			$tarea_comentario->Load($id_comentario);
		}
		else
		{
			$tarea->Edit('fecha_ultima_novedad',date("Y-m-d H-i-s"));
			//La primera vez que se ingresa un Detalle, puede cambiar el estado de la Tarea.
			if($estado_comentario != $tarea->fields['estado'])
			{
				$tarea->Edit('estado',$estado_comentario);
				foreach($arreglo_estados as $k => $es)
					if($estado_comentario == $es)
						$tarea->Edit("orden_estado",$k+1);
			}

			if($tarea->Write())
			{
				if($estado_comentario != $tarea->fields['estado'])
				{
					$infos[] = __('El estado de la Tarea ha sido cambiado con éxito');
					$js .= "parent.ActualizarEstado(".array_search($estado_comentario,$arreglo_estados).");";
				}
			}
			else
			{
				if($estado_comentario != $tarea->fields['estado'])
				{
					$errors[] = __('Error al cambiar el estado de la Tarea');
				}
			}
		}

		if($id_trabajo)
			$trabajo->Load($id_trabajo);
		if($id_archivo)
			$archivo->Load($id_archivo);

		$asunto->LoadByCodigo($tarea->fields['codigo_asunto']);


		//Revisa el Conf si esta permitido y la función existe
		if($duracion_avance)
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
				{
					$duracion_avance = UtilesApp::Decimal2Time($duracion_avance);
				}

		$tarea_comentario->Edit('id_tarea',$id_tarea);
		$tarea_comentario->Edit('id_usuario',$id_usuario);
		$tarea_comentario->Edit('comentario',$descripcion_avance);
		if($duracion_avance)
			$tarea_comentario->Edit('duracion_avance',$duracion_avance);
		$tarea_comentario->Edit('fecha_avance',Utiles::fecha2sql($fecha_avance));
		$tarea_comentario->Edit('estado',$estado_comentario);

		### Subiendo archivo ###
		if( $archivo_comentario['name'] != '' && !$id_archivo)
		{
				$archivo_subir = $archivo_comentario['tmp_name'];
				$subir = fopen($archivo_subir, 'r');
				$contenido = fread($subir, filesize($archivo_subir));
				fclose($subir);

				$archivoname = UtilesApp::slug(substr($archivo_comentario['name'], 0, strpos($archivo_comentario['name'], '.')));
				$archivoext = substr($archivo_comentario['name'], stripos($archivo_comentario['name'], '.'));

				$name = "/tarea_comentario/{$id_tarea}/{$archivoname}{$archivoext}";
				$url_file = UtilesApp::UploadToS3($name, $contenido, $archivo_comentario['type']);
	            if($url_file!=''){
	            	$id_file = $tarea_comentario->saveEmptyFile($asunto->fields['id_contrato'],$archivo_comentario['name'],$archivo_comentario['type'], '',NULL,$url_file);
	            	if($id_file!=""){
						$tarea_comentario->Edit('id_archivo',$id_file);
						$infos[] = __('Archivo').' '.__('Guardado con exito');
					}else{
						$errors[] = $archivo->error;
					}
	            }
		}
		if($ingresa_trabajo && (!$id_trabajo) && $duracion_avance)
		{
			$trabajo->Edit('id_usuario',$id_usuario);
			$trabajo->Edit('codigo_asunto',$asunto->fields['codigo_asunto']);
			$trabajo->Edit("duracion",$duracion_avance);
			$trabajo->Edit("duracion_cobrada",$duracion_avance);
			$trabajo->Edit('descripcion',$descripcion_avance);
			$trabajo->Edit('fecha',Utiles::fecha2sql($fecha_avance));

			if($asunto->fields['cobrable']==0) //Si el asunto no es cobrable, cambia cobrable
				$trabajo->Edit("cobrable",'0');
			else
				$trabajo->Edit("cobrable",'1');

			//Solicitante del Trabajo = Mandante de la Tarea??

			if($trabajo->Write())
			{
				$infos[] = __('Trabajo').' '.__('Guardado con exito');
				$tarea_comentario->Edit('id_trabajo',$trabajo->fields['id_trabajo']);
			}
			else
			{
				$errors[] = __("Error al ingresar el Trabajo.");
			}
		}

		if($tarea_comentario->Write())
		{
				$infos[] = __('Avance').' '.__('Guardado con exito');
				$id_ultimo_comentario_ingresado = $tarea_comentario->fields['id_comentario'];
				$js .= "parent.ActualizarTiempoIngresado();";
		}
		else
				$errors[] = __("Error al ingresar el Avance.");
	}

?>
<script type="text/javascript">

		function EliminarComentario(id_comentario)
		{
			var url = 'tareas_comentarios.php?id_tarea=<?=$id_tarea?>';
			url += '&opcion=eliminar&id_comentario='+id_comentario;

			if(confirm('<?=__('¿Está seguro que desea eliminar el Detalle?')?>'))
			{
				self.location.href= url;
			}
		}

		function RegistrarVisita()
		{
			var url = "ajax_tareas.php?accion=registrar_visita";
			url += "&id_tarea=<?=$id_tarea?>";

			new Ajax.Request(url, {asynchronous: true, parameters : '', onComplete:  void(0)});
		}

		function CargarComentario(id_comentario)
		{
			var url = "ajax_tareas.php?accion=cargar_comentario";
			url += "&id_tarea=<?=$id_tarea?>";
			url += "&id_comentario="+id_comentario;

			new Ajax.Request(url, {asynchronous: true, parameters : '', onComplete:  ShowComentario});
		}

		function ShowComentario(xmlHttpRequest, responseHeader)
		{
			var response = xmlHttpRequest.responseText;
			if(response)
			{
				if(response.indexOf('head')!=-1)
				{
							alert('<?=__('Sesión Caducada')?>');
							top.location.href='<?=Conf::Host()?>';
				}

				response = response.replace('REEMPLAZAR_FOR_HEAD','head');
				if(response != 'FAIL')
				{
					var campos = response.split('|');

					$('id_comentario').value = campos[0];
					$('descripcion_avance').value = campos[3];
					$('fecha_avance').value = campos[4];
					$('duracion_avance').value = campos[5];

					// Trabajo
					if(campos[6])
					{
						var url = "editar_trabajo.php?id_trabajo="+campos[6]+"&popup=1";
						var span = "<span class='link_trabajo' ";
						span += "onclick=\"nuovaFinestra('Editar_Trabajo',550,450,'"+url+"','');\" ";
						span +=	"style = 'cursor:pointer; text-decoration:underline;' >";


						$('display_trabajo').innerHTML = span+'Trabajo N° '+campos[6]+'</span>';
						$('id_trabajo').value = campos[6];

						$('celda_trabajo').hide();
						$('display_trabajo').show();
					}
					else
					{
						$('id_trabajo').value = '';
						$('ingresa_trabajo').checked = false;
						$('ingresa_trabajo').disabled = false;
						$('celda_trabajo').show();
						$('display_trabajo').hide();
					}

					// Archivo
					if(campos[7])
					{
						var url = "ver_archivo.php?id_archivo="+campos[7];
						var anchor = '<a href="'+url+'">';

						$('display_archivo').innerHTML = anchor+campos[8]+'</a>';

						$('id_archivo').value = campos[7];
						$('display_archivo').show();
						$('celda_archivo').hide();
					}
					else
					{
						$('id_archivo').value = '';
						$('celda_archivo').show();
						$('display_archivo').hide();
					}

					<?  // Por cada estado, 'estado_comentario' puede cambiar a ese estado.
						$elector = array();
						foreach($arreglo_estados as $i => $e)
							$elector[] = "if(campos[9] == '".$e."') $('estado_comentario').selectedIndex = ".$i.";";
						echo implode(' else ',$elector);
					?>

					$('Nuevo-Editar').innerHTML = '<?=__('Editar Detalle')?>';

					$("AgregarAvance").show();
					$("SpanAgregar").hide();
					$('descripcion_avance').focus();
					Resize();
				}
			}
			return true;
		}

		function Resize()
		{
			height = $('contenido_bitacora').offsetHeight;
			parent.ResizeBitacora(height+21);
		}

		function CancelarAvance()
		{
			$('AgregarAvance').hide();
			$('SpanAgregar').show();

			$('descripcion_avance').value = '';
			$('fecha_avance').value = '<?=date('d-m-Y')?>';
			$('duracion_avance').value = '';

			$('ingresa_trabajo').disabled = true;
			$('ingresa_trabajo').checked = true;
			$('id_comentario').value = '';
			$('id_trabajo').value = '';
			$('id_archivo').value = '';
			$('Nuevo-Editar').innerHTML = '<?=__('Nuevo Detalle')?>';

			$('celda_trabajo').show();
			$('display_trabajo').hide();

			$('celda_archivo').show();
			$('display_archivo').hide();

			$('estado_comentario').selectedIndex = <?=array_search($tarea->fields['estado'],$arreglo_estados)?>;
		}

		function Validar(form)
		{
			if(!form.descripcion_avance.value)
			{
				alert('Debe ingresar una descripción.');
				form.descripcion_avance.focus();
				return false;
			}
			<?  //Revisa el Conf si el TipoIngresoHoras es 'decimal': se agrega esta revisión de Duracion a ValidarAvance().
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
				{
						?>
						if(form.duracion_avance.value)
						{
							var dur= form.duracion_avance.value.replace(",",".");
							if(isNaN(dur))
							{
								alert('<?=__('Solo se aceptan valores numéricos')?>');
								form.duracion_avance.focus();
								return false;
							}
							var decimales=dur.split(".");
							if(decimales[1])
							if(decimales[1].length > 1 )
							{
								alert('<?=__('Solo se permite ingresar un decimal')?>');
								form.duracion_avance.focus();
								return false;
							}
						}
						<?
				}
			?>
			return true;
		}
		<?=$js?>
		RegistrarVisita();
</script>

	<div id='contenido_bitacora'>

	<? /* INFO */
		if(!empty($infos))
		{
			?>
				<table class="info" width="100%">
					<tbody>
						<? foreach($infos as $info)
							{
								echo '<tr>  <td style="font-size: 12px;" valign="top" align="left">';
									echo $info;
								echo '</td> </tr>';
							}
						?>
					</tbody>
				</table>
			<?
		}
	?>
	<? /* ERRORES */
		if(!empty($errors))
		{
			?>
				<table class="error" width="100%">
					<tbody>
						<? foreach($errors as $error)
							echo '<tr>  <td style="font-size: 12px;" valign="top" align="left">';
								echo $error;
							echo '</td> </tr>';
						?>
					</tbody>
				</table>
			<?
		}
		if(!empty($errors)||!empty($infos))
		echo "<br />";
	?>
	<!--  /* Titulo y Agregar Bitácora */ -->
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
			<tr>
				<td valign="top" align="left" class="titulo" bgcolor="<?=(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ColorTituloPagina'):Conf::ColorTituloPagina())?>">
					<?=__('Bitácora')?>
				</td>
				<td align="right" style="font-size:14px;" bgcolor="<?=(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ColorTituloPagina'):Conf::ColorTituloPagina())?>">
					<span id='SpanAgregar' style='cursor:pointer' onclick='$("AgregarAvance").show(); $("SpanAgregar").hide(); Resize();'>
						<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0>
						<?= $editar? __('Editar Detalle'):__('Agregar Detalle')?>&nbsp;&nbsp;
					</span>
				</td>
			</tr>
		</table>
	<br>


	<!--Input duración -->
	<?
				$oc = "$('ingresa_trabajo').disabled = false;";
				$input_duracion = Html::PrintTime("duracion_avance",'','onchange="'.$oc.'"',true);

				if( method_exists('Conf','GetConf') )
				{
					if(Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal')
					{
								$input_duracion = '<input type="text" name="duracion_avance"  id="duracion_avance" value="" size="6" maxlength=4  onchange="'.$oc.'" />';
					}
					else if(Conf::GetConf($sesion,'TipoIngresoHoras')=='selector')
					{
								$input_duracion = SelectorHoras::PrintTimeSelector($sesion,"duracion_avance",'00:00:00',14,$oc);
					}
				}
				else if(method_exists('Conf','TipoIngresoHoras'))
				{
					if(Conf::TipoIngresoHoras()=='decimal')
					{
								$input_duracion = '<input type="text" name="duracion_avance"  id="duracion_avance" value="" size="6" maxlength=4  onchange="'.$oc.'" />';
					}
					else if(Conf::TipoIngresoHoras()=='selector')
					{
								$input_duracion = SelectorHoras::PrintTimeSelector($sesion,"duracion_avance",'00:00:00',14,$oc);
					}
				}
	?>

	<div id="AgregarAvance" class="TipoAgregar" style="display:none;"  >
	<form id="form_avance" name=form_avance method="post" action="<?=$_SERVER[PHP_SELF]?>" enctype="multipart/form-data">
	<input type=hidden name=opcion value="guardar"/>
	<input type=hidden name=id_tarea value='<?=$id_tarea?>' />
	<input type=hidden name=id_comentario id=id_comentario value='' />
	<input type=hidden name="gIsMouseDown" id="gIsMouseDown" value=false />
	<input type=hidden name="gRepeatTimeInMS" id="gRepeatTimeInMS" value=200 />
	<input type=hidden name=max_hora id=max_hora value=14 />
	<input type=hidden name=id_trabajo id=id_trabajo value='' />
	<input type=hidden name=id_archivo id=id_archivo value='' />
	<fieldset>
		<legend><span id='Nuevo-Editar'><?=__('Nuevo Detalle')?></span></legend>
							<table id='tbl_ingreso_avance' class='tabla_avance' >
								<tr>
									<td align=right width='25%'>
										<label for='fecha_avance'><?=__('Fecha')?></label>
									</td>
									<td align=left width='45%'>
										<input type='text' name='fecha_avance' id='fecha_avance' value='<?=date('d-m-Y')?>'  size='11' maxlength='10' />
										<div style="position:absolute; display:inline;  margin-left:5px;"">
											<img src='<?=Conf::ImgDir()?>/calendar.gif' id='img_fecha_avance' style='cursor:pointer' />
										</div>
									</td>
									<td>
										&nbsp;
									</td>
									<!-- display_trabajo: espacio para Link al Trabajo, si se carga Avance que ya lo posee. -->
									<td style='display:none; min-width:235px;'  align=left id='display_trabajo'>
									</td>
									<td align=left style='min-width:235px;' id='celda_trabajo'>
										<label for='ingresa_trabajo'><?=__('Ingresar como Trabajo')?>:&nbsp;</label>
										<input type='checkbox' name='ingresa_trabajo' id='ingresa_trabajo' disabled=disabled checked=checked title="<?=__('Ingrese Duración para activar')?>" />
									</td>
								<tr>
									<td align=right>
										<label for='duracion_avance'><?=__('Duración')?></label>
									</td>
									<td align=left>
										<?=$input_duracion?>
									</td>
									<td>
										&nbsp;
									</td>

									<td align=left>
										<label for='ingresa_cobrable'><?=__('Estado de Tarea')?>:&nbsp;</label>
										<select name=estado_comentario id=estado_comentario>
											<?
												foreach($arreglo_estados as $e)
												{
													$selected = '';
													if($tarea->fields['estado'] == $e)
														$selected = 'selected="selected"';
													echo "<option value='".$e."' ".$selected.">".$e."</option>";
												}
											?>
										</select>
									</td>
								</tr>
								<tr>
									<td align=right>
										<label for='descripcion_avance'><?=__('Descripción')?></label>
									</td>

									<td align=left colspan=3>
											<textarea name="descripcion_avance" id="descripcion_avance" rows=3 cols=40 style='overflow:auto'></textarea>
									</td>
								</tr>
								<tr>
									<td align=right>Documento:</td>
									<td colspan=3 align=left id='celda_archivo'>
										<input type=file name=archivo_comentario id=archivo_comentario size=64>
									</td>
									<!-- display_archivo: espacio para bajar el archivo, si se carga un Avance que ya lo posee. -->
									<td style='display:none;'  colspan=3 align=left id='display_archivo'>
										&nbsp;
									</td>
								</tr>
								<tr>
									<td align=center colspan=4>
										<input type=submit class=btn value=<?=__('Enviar')?> onclick="return Validar(this.form);" />
										&nbsp;
										<input type=button name=pend value=<?=__('Cancelar')?> onclick="CancelarAvance();" class='btn'>
									</td>
								</tr>
							</table>
			</fieldset>
		</form>
		</div>


<?
	if($orden == "")
		$orden = " tarea_comentario.fecha_creacion DESC";

	$query = "SELECT		SQL_CALC_FOUND_ROWS *, tarea_comentario.fecha_creacion AS fecha_creacion_comentario,
							CONCAT(usuario.apellido1,' ',usuario.nombre) as nombre_usuario,
							IF(tcu.id_comentario IS NULL,1,0) AS novedad
				FROM		tarea_comentario
				JOIN		usuario ON (tarea_comentario.id_usuario = usuario.id_usuario)
				LEFT JOIN	tarea_comentario_usuario AS tcu ON (tcu.id_comentario = tarea_comentario.id_comentario AND tcu.id_usuario = '".$id_usuario."')
				WHERE		id_tarea = ".$id_tarea;

	$x_pag = 6;

	$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);

	$b->nombre = "busc_comentarios";

    $b->AgregarFuncion(__('Fecha'),			'Fecha',			"align=center");
	$b->AgregarFuncion(__('Usuario'),		"NombreUsuario",	"align=center");
	$b->AgregarFuncion(__('Descripción'),	"Comentario",		"align=center");
	$b->AgregarFuncion(__(''),				'Estado',			"align=center");
	$b->AgregarFuncion(__('Duración'),		'Duracion',			"align=center");
	$b->AgregarFuncion(__('Trabajo'),		'Trabajo',			"align=center");
	$b->AgregarFuncion(__('Opción'),		'Opciones',			"align=right");


    //$b->AgregarFuncion(__('Hora'),'Hora');

	function Duracion(&$fila)
	{
		$h = '--';
		$duracion = $fila->fields['duracion_avance'];
		if($duracion)
		{
			$duracion = split(':',$duracion);
			$h = $duracion[0].':'.$duracion[1];
		}

		if($fila->fields['novedad'])
			$h = '<b>'.$h.'</b>';
		return $h;
	}

	function NombreUsuario(&$fila)
	{
		$h = $fila->fields['nombre_usuario'];
		if($fila->fields['novedad'])
			$h = '<b>'.$h.'</b>';
		return $h;
	}

	function Comentario(&$fila)
	{
		$h = $fila->fields['comentario'];
		if($fila->fields['novedad'])
			$h = '<b>'.$h.'</b>';
		return $h;
	}

	function Trabajo(&$fila)
	{
		$id_trabajo = $fila->fields['id_trabajo'];
		if($id_trabajo)
		{
			return '<span
							class="link_trabajo"
							onclick="nuovaFinestra(\'Editar_Trabajo\',550,450,\'editar_trabajo.php?id_trabajo='.$id_trabajo.'&popup=1\',\'\');"
							style=" cursor:pointer; text-decoration:underline; "
							>N° '.$id_trabajo.'</span>';
		}
		return '--';


	}

	function Fecha(&$fila)
	{
		$h = Utiles::sql2date($fila->fields['fecha_avance'],'%d-%m-%y');
		if($fila->fields['novedad'])
		{
			$h = '<b>'.$h.'</b>';
		}
		return $h;
	}
	function Hora(&$fila)
	{
		return $fila->fields['fecha_creacion_comentario'];
	}
	function Estado(&$fila)
	{
		return Tarea::IconoEstado($fila->fields['estado']);
	}

	function Opciones(&$fila)
	{
			global $sesion;
			global $id_ultimo_comentario_ingresado;
			$id_com = $fila->fields['id_comentario'];
			if(!$id_com)
				$id_com = $id_ultimo_comentario_ingresado;

			$id_archivo = $fila->fields['id_archivo'];
			if($id_archivo)
			{
				$archivo = new Archivo($sesion);
				$archivo->Load($id_archivo);
				$nombre_archivo = $archivo->fields['archivo_nombre'];


				$o .= '<a href="ver_archivo.php?id_archivo='.$id_archivo.'">
							<img src="'.Conf::ImgDir().'/ver_16.gif" border=0 title="Ver documento: '.$nombre_archivo.'" /></a>&nbsp;';
			}

			$o .= "<a href='javascript:void(0)' onclick=\"CargarComentario(".$id_com.")\" ><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar></a>&nbsp;";
			$o .= "<a target=_parent href='javascript:void(0)' onclick=\"EliminarComentario(".$id_com.")\" ><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 title=Eliminar></a>";

			return $o;
	}

	$b->color_mouse_over = "#DF9862";
	$b->Imprimir();
?>
</div>

<? echo SelectorHoras::Javascript(); ?>
<script type="text/javascript">
	window.onload=function(){Resize()}


	if (document.getElementById('img_fecha_avance'))
	{
		Calendar.setup(
			{
				inputField	: "fecha_avance",				// ID of the input field
				ifFormat		: "%d-%m-%Y",			// the date format
				button			: "img_fecha_avance"		// ID of the button
			}
		);
	}

</script>
