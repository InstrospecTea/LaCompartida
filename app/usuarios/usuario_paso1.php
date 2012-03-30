<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';

	$sesion = new Sesion( array('ADM') );
	$pagina = new Pagina($sesion);
	$pagina->titulo = __('Administración de Usuarios') ;

	if ($opc=="eliminado")
		$pagina->AddInfo(__('Usuario').' '.__('eliminado con éxito'));

	if($desde == "")
			$desde = 0;
	if($x_pag == "")
			$x_pag = 20;
	if($orden == '')
			$orden = 'apellido1';

	//Opción cancelar
	if($opc == 'cancelar')
	{
		$pagina->Redirect('usuario_paso1.php');
	}
	//Opción editar
	else if($opc == 'edit')
	{
		if($cambiar_alerta_diaria=='on')
		{
			if($alerta_diaria=='on') $alerta_diaria=1; else $alerta_diaria=0;
			if($retraso_max=='') $retraso_max=0;
		}
		else
		{
			$alerta_diaria='alerta_diaria';
			$retraso_max='retraso_max';
		}
		if($cambiar_restriccion_diario=='on')
		{
			if($restriccion_diario=='')
				$restriccion_diario=0;
		}
		else
		{
			$restriccion_diario='restriccion_diario';
		}
		if($cambiar_alerta_semanal=='on')
		{
			if($alerta_semanal=='on')
				$alerta_semanal=1;
			else
				$alerta_semanal=0;
			if($restriccion_max=='')
				$restriccion_max=0;
			if($restriccion_min=='')
				$restriccion_min=0;
		}
		else
		{
			$alerta_semanal='alerta_semanal';
			$restriccion_max='restriccion_max';
			$restriccion_min='restriccion_min';
		}
		if($cambiar_restriccion_mensual=='on')
		{
			if($restriccion_mensual=='')
				$restriccion_mensual=120;
		}
		else
		{
			$restriccion_mensual='restriccion_mensual';
		}
		if($cambiar_dias_ingreso_trabajo=='on')
		{
			if($dias_ingreso_trabajo=='')
				$dias_ingreso_trabajo=7;
		}
		else
		{
			$dias_ingreso_trabajo='dias_ingreso_trabajo';
		}

		//Actualizar alerta de Usuario
		$query3 = "UPDATE usuario SET alerta_diaria=".$alerta_diaria.", alerta_semanal=".$alerta_semanal.", retraso_max=".$retraso_max.",
					restriccion_max=".$restriccion_max.", restriccion_min=".$restriccion_min.", restriccion_mensual=".$restriccion_mensual.",
					dias_ingreso_trabajo=".$dias_ingreso_trabajo.", restriccion_diario=".$restriccion_diario;
		$resp3 = mysql_query($query3,$sesion->dbh) or Utiles::errorSQL($query3,__FILE__,__LINE__,$sesion->dbh);

		//Insert para el registro de cambios Restricciones y alertas generales
		$nuevos = 'Alerta diaria: '.$alerta_diaria.',';
		$nuevos .= 'Retraso max. diaria (HH): '.$retraso_max.',';
		$nuevos .= 'Restricción min. diaria (HH): '.$restriccion_diario.',';
		$nuevos .= 'Alerta semanal: '.$alerta_semanal.',';
		$nuevos .= 'Restricción min. semanal (HH): '.$restriccion_min.',';
		$nuevos .= 'Restricción max. semanal (HH): '.$restriccion_max.',';
		$nuevos .= 'Restricción min. mensual (Hrs): '.$restriccion_mensual.',';
		$nuevos .= 'Plazo max. (días) para ingreso de trabajos: '.$dias_ingreso_trabajo;
		$query4 = "INSERT INTO usuario_cambio_historial (id_usuario,id_usuario_creador,nombre_dato,valor_original,valor_actual,fecha)";
		$query4 .= " VALUES(NULL,'".$sesion->usuario->fields['id_usuario']."','Restricciones y alertas generales',NULL,'".$nuevos."',NOW())";
		$resp = mysql_query($query4, $sesion->dbh) or Utiles::errorSQL($query4,__FILE__,__LINE__,$sesion->dbh);

		if($alerta_diaria==0)
			$alerta_diaria='';
		if($alerta_semanal==0)
			$alerta_semanal='';
	}

	$pagina->PrintTop();
	$tooltip_text = __('Para agregar un nuevo usuario ingresa su '.UtilesApp::GetConf($sesion,'NombreIdentificador').' aquí.');
?>

<script type="text/javascript">
    jQuery(document).ready(function() {

	
	   
	    jQuery('.descargaxls').click(function() {
		var activo=0;
		if(jQuery('#activo').is(':checked')) activo=1;

		var tipoxls=jQuery(this).attr('rel');

		nom=jQuery('#nombre').val();
		if(tipoxls == 'xls')
		{
			destino = '../interfaces/usuarios_xls.php?act='+activo+'&nombre='+nom;
		}
		else if(tipoxls == 'xls_vacacion')
		{
			destino = '../interfaces/usuarios_xls.php?act='+activo+'&nombre='+nom+'&vacacion=true';
		}
		else if(tipoxls == 'xls_modificaciones')
		{
			destino = '../interfaces/usuarios_xls.php?act='+activo+'&nombre='+nom+'&modificaciones=true';
		}
		top.window.location.href=destino;
		//if (console!==undefined) console.log(destino);
	    });
 	    
	     jQuery('#costos').click(function(){

		 var theform=jQuery(this).parents('form:first');
		  theform.submit();
	     });
	     jQuery('#btnbuscar').click(function(){

		 jQuery(this).parents('form:first').attr('action','usuario_paso1.php?buscar=1').submit();

	     });
        });
	function YoucangonowMichael() {
	     //if (console!==undefined) console.log('jQUI Cargado');
	     jQuery('.descargaxls').button({
		 icons: {primary: "ui-icon-xls"	}
	     }).show();
	     jQuery('#btnbuscar, #costos').button({
		 icons: {primary: "ui-icon-search"	}
	     });

	}

function RevisarRut( form )
{
	//if( Rut(form.rut.value, form.dv_rut.value ) )
		return true;

	//alert( 'El rut es inválido' );
	//return false;
}

function Listar( form, from )
{
	var nom=document.act.nombre.value;
	var activo = 0;
	if($('activo').checked==true)
		activo = 1;

	if(from == 'buscar')
		form.action = 'usuario_paso1.php?buscar=1';
	else if(from == 'xls')
	{
		form.action = '../interfaces/usuarios_xls.php?act='+activo+'&nombre=nom';
	}
	else if(from == 'xls_vacacion')
	{
		form.action = '../interfaces/usuarios_xls.php?act='+activo+'&nombre=nom&vacacion=true';
	}
	else if(from == 'xls_modificaciones')
	{
		form.action = '../interfaces/usuarios_xls.php?act='+activo+'&nombre=nom&modificaciones=true';
	}
	else
		return false;
	//alert(form.action);
	form.submit();
	//return true;
}

		function ModificaTodos( from )
		{
			if( from.cambiar_alerta_diaria.checked==true)
				{
				var alerta_diaria = from.alerta_diaria.checked;
				if( alerta_diaria == true ) alerta_diaria='\n Alerta diaria:          SI'; else alerta_diaria='\n Alerta diaria:          NO';
				var retraso_max = '\n Restraso Max:        '
				}
			else
				{
				var alerta_diaria = '';
				var retraso_max = '';
				}
			if( from.cambiar_alerta_semanal.checked==true)
				{
				var alerta_semanal = from.alerta_semanal.checked;
				if( alerta_semanal == true ) alerta_semanal='\n Alerta semanal:     SI'; else alerta_semanal='\n Alerta semanal:     NO';
				var restriccion_min = '\n Min HH:                 '
				var restriccion_max = '\n Max HH:                 '
				}
			else
				{
				var alerta_semanal = '';
				var restriccion_min = '';
				var restriccion_max = '';
				}
			if( from.cambiar_restriccion_mensual.checked==true)
				{
				var restriccion_mensual = '\n Min HH mensual: '
				}
			else
				{
				var restriccion_mensual = '';
				}
			if( from.cambiar_dias_ingreso_trabajo.checked==true)
				{
				var dias_ingreso_trabajo = '\n Max dias ingreso:  '
				}
			else
				{
				var dias_ingreso_trabajo = '';
				}

		if(confirm( alerta_diaria + alerta_semanal + retraso_max + from.retraso_max.value + restriccion_min + from.restriccion_min.value + restriccion_max + from.restriccion_max.value + restriccion_mensual + from.restriccion_mensual.value + dias_ingreso_trabajo + from.dias_ingreso_trabajo.value + '\n\n ¿Desea cambiar los restricciones y alertas de todos los usuarios?' ))
			{
			from.action="usuario_paso1.php";
			from.submit();
			}
		}
		function Cancelar(form)
			{
				form.opc.value = 'cancelar';
				form.submit();
			}

			function DisableColumna( from, valor, text)
			{
				if(text == 'alerta_diaria')
				{
					var Input1 = $('alerta_diaria');
					var Input2 = $('retraso_max');
 					var check = $(valor);

					if(check.checked)
					{
						Input1.disabled= false;
						Input2.disabled= false;
						Input2.style.background="#FFFFFF";
					}
					else
					{
						Input1.checked = false;
						Input1.disabled = true;
						Input2.value = '';
						Input2.disabled = true;
						Input2.style.background="#EEEEEE";
					}
				}
				else if(text == 'alerta_semanal')
				{
					var Input1 = $('alerta_semanal');
					var Input2 = $('restriccion_min');
					var Input3 = $('restriccion_max');
 					var check = $(valor);

					if(check.checked)
					{
						Input1.disabled= false;
						Input2.disabled= false;
						Input3.disabled= false;
						Input2.style.background="#FFFFFF";
						Input3.style.background="#FFFFFF";
					}
					else
					{
						Input1.checked = false;
						Input1.disabled = true;
						Input2.value = '';
						Input2.disabled = true;
						Input3.value = '';
						Input3.disabled = true;
						Input2.style.background="#EEEEEE";
						Input3.style.background="#EEEEEE";
					}
				}
				if(text == 'alerta_mensual')
				{
					var Input1 = $('restriccion_mensual');
 					var check = $(valor);

					if(check.checked)
					{
						Input1.disabled= false;
						Input1.style.background="#FFFFFF";
					}
					else
					{
						Input1.value = '';
						Input1.disabled = true;
						Input1.style.background="#EEEEEE"
					}
				}
				if(text == 'dias_ingreso')
				{
					var Input1 = $('dias_ingreso_trabajo');
 					var check = $(valor);

					if(check.checked)
					{
						Input1.disabled= false;
						Input1.style.background="#FFFFFF";
					}
					else
					{
						Input1.value = '';
						Input1.disabled = true;
						Input1.style.background="#EEEEEE";
					}
				}
				if(text == 'restriccion_diario')
				{
					var Input1 = $('restriccion_diario');
					var check = $(valor);

					if(check.checked)
					{
						Input1.disabled= false;
						Input1.style.background="#FFFFFF";
					}
					else
					{
						Input1.value = '';
						Input1.disabled = true;
						Input1.style.background="#EEEEEE";
					}
				}
			}

</script>
<table width="96%" align="left">
	<tr>
		<td width="20">&nbsp;</td>
		<td valign="top">

<?
  if( strtolower(UtilesApp::GetConf($sesion,'NombreIdentificador'))=='rut' )	{ ?>
			<form action="usuario_paso2.php" method="post" onsubmit="return RevisarRut(this);">
<?  }
	else
		{ ?>
			<form action="usuario_paso2.php" method="post">
<?  } ?>

<table width=100% class="tb_base">
	<tr>
		<td valign="top" class="subtitulo" align="left" colspan="2">
			<? echo __('Ingrese ') . UtilesApp::GetConf($sesion,'NombreIdentificador') . __(' del usuario')?>:
			<hr class="subtitulo_linea_plomo"/>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<strong><?php echo  UtilesApp::GetConf($sesion,'NombreIdentificador'); ?></strong>
		</td>
		<td valign="top" class="texto" align="left">
			<?   if( strtolower(UtilesApp::GetConf($sesion,'NombreIdentificador'))=='rut' ) { ?>
				<input type="text" name="rut" value="" size="10" onMouseover="ddrivetip('<?=$tooltip_text?>')" onMouseout="hideddrivetip()" />-<input type="text" name="dv_rut" value="" maxlength=1 size="1" />
			<? } else { ?>
				<input type="text" name="rut" value="" size="17" onMouseover="ddrivetip('<?=$tooltip_text?>')" onMouseout="hideddrivetip()" />
			<? } ?>
				&nbsp;
			<?
			if( $sesion->usuario->fields['id_visitante'] == 0 )
				echo "<br><input type=\"submit\" class=btn name=\"boton\" value=\"".__('Aceptar')."\" />";
			else
				echo "<br><input type=\"button\" class=btn name=\"boton\" value=\"".__('Aceptar')."\" onclick=\"alert('Usted no tiene derecho para agegar un usuario nuevo');\" />";
			?>
		</td>
	</tr>
	<tr>
		<td valign="top"><img src="https://files.thetimebilling.com/templates/default/img/pix.gif" border="0" width="1" height="5" alt='' /></td>
	</tr>
</table>
<br/>
</form>
				</td>
		</tr>

	<tr><td></td>
		<td>
			<table width=100% class="tb_base">
				<tr>
					<td>
				<table width=100%>
					<tr>
							<td valign="top" class="subtitulo" align="left" colspan="2">
							<?=__('Modificacion de Datos para todos los usuarios ')?>:
							<hr class="subtitulo_linea_plomo"/>
							</td>
						</tr>
				</table>
		</td>
	</tr>

	<tr><td>
		<form name="form_usuario" method="post" enctype="multipart/form-data">
			 <input type="hidden" name="opc" value="edit" />
	<fieldset class="table_blanco">
		<legend><?=__('Restricciones y alertas')?></legend>
		<table width=100%>
		<tr>
			<td width=38% align="right"></td>
			<td width=10% align="right"></td>
			<td width=15% align="left"></td>
			<td width=20% align="right"></td>
			<td width=17% align="center">
				Cambiar valores
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="alerta_diaria"><?=__('Alerta Diaria')?></label> <input type="checkbox" id="alerta_diaria" name="alerta_diaria" <?=$cambiar_alerta_diaria=='on' ? '' : disabled ?> <?=$alerta_diaria!='' ? "checked" : "" ?> />
			</td>
			<td align="right">
				<?=__('Retraso max.')?>
			</td>
			<td align="left">
				<input type="text" style="background-color: #EEEEEE;" size=10 value="<?=$retraso_max > 0 ? $retraso_max : '' ?>" id="retraso_max" name="retraso_max" <?=$cambiar_alerta_diaria=='on' ? '' : disabled ?> />
			</td>
			<td align="left"></td>
			<td align="center">
				<input type="checkbox" id="cambiar_alerta_diaria" name="cambiar_alerta_diaria" <?=$cambiar_alerta_diaria!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'alerta_diaria');"/>
			</td>
		</tr>
		<tr>
			<td align="right">
				&nbsp;
			</td>
			<td align="right">
				<?=__('Min HH.')?>
			</td>
			<td align="left">
				<input type="text" style="background-color: #EEEEEE;" size=10 value="<?=$restriccion_diario > 0 ? $restriccion_diario : '' ?>" id="restriccion_diario" name="restriccion_diario" <?=$cambiar_restriccion_diario =='on' ? '' : disabled ?> />
			</td>
			<td align="left"></td>
			<td align="center">
				<input type="checkbox" id="cambiar_restriccion_diario" name="cambiar_restriccion_diario" <?=$cambiar_restriccion_diario!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'restriccion_diario');"/>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="alerta_semanal"><?=__('Alerta Semanal')?></label> <input type="checkbox" id="alerta_semanal" name="alerta_semanal" <?=$cambiar_alerta_semanal=='on' ? '' : disabled ?> <?=$alerta_semanal!='' ? "checked" : "" ?> />
			</td>
			<td align="right">
				<?=__('Mín. HH')?>
			</td>
			<td align="left">
				<input type="text" style="background-color: #EEEEEE;" size=10 value="<?=$restriccion_min > 0 ? $restriccion_min : '' ?>" id="restriccion_min" name="restriccion_min" <?=$cambiar_alerta_semanal=='on' ? '' : disabled ?> />
			</td>
			<td align="left">
				<?=__('Máx. HH')?> <input type="text" style="background-color: #EEEEEE;" size=10 value="<?=$restriccion_max > 0 ? $restriccion_max : '' ?>" id="restriccion_max" name="restriccion_max" <?=$cambiar_alerta_semanal=='on' ? '' : disabled ?> />
			</td>
			<td align="center">
				<input type="checkbox" id="cambiar_alerta_semanal" name="cambiar_alerta_semanal" <?=$cambiar_alerta_semanal!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'alerta_semanal');"/>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="restriccion_mensual"><?=__('Mínimo mensual de horas')?></label>
			</td>
			<td>&nbsp;</td>
			<td align="left">
				<input type="text" size="10" style="background-color: #EEEEEE;" <?=Html::Tooltip("Para no recibir alertas mensuales ingrese 0.")?> value="<?=$restriccion_mensual > 0 ? $restriccion_mensual : '' ?>" id="restriccion_mensual" name="restriccion_mensual" <?=$cambiar_restriccion_mensual=='on' ? '' : disabled ?> />
			</td>
			<td align="left"></td>
			<td align="center">
				<input type="checkbox" id="cambiar_restriccion_mensual" name="cambiar_restriccion_mensual" <?=$cambiar_restriccion_mensual!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'alerta_mensual');" />
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="dias_ingreso_trabajo"><?=__('Plazo máximo (en días) para ingreso de trabajos')?></label>
			</td>
			<td>&nbsp;</td>
			<td align="left">
				<input type="text" size="10" style="background-color: #EEEEEE;" value="<?=$dias_ingreso_trabajo > 0 ? $dias_ingreso_trabajo : '' ?>" id="dias_ingreso_trabajo" name="dias_ingreso_trabajo" <?=$cambiar_dias_ingreso_trabajo=='on' ? '' : disabled ?> />
			</td>
			<td align="left"></td>
			<td align="center">
				<input type="checkbox" id="cambiar_dias_ingreso_trabajo" name="cambiar_dias_ingreso_trabajo" <?=$cambiar_dias_ingreso_trabajo!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'dias_ingreso');"/>
			</td>
		</tr>

		</table>
		</fieldset>
		<fieldset class="table_blanco">
			<legend><?=__('Guardar datos')?></legend>
			<table width=100%>
			<tr><td align="center">
			<? if( $sesion->usuario->fields['id_visitante'] == 0 )
						echo "<input type=\"button\" value=\"".__('Guardar')."\" class=btn onclick=\"ModificaTodos(this.form);\"  /> &nbsp;&nbsp;";
				 else
				 		echo "<input type=\"button\" value=\"".__('Guardar')."\" class=btn onclick=\"alert('Usted no tiene derecho para modificar estos valores.');\" /> &nbsp;&nbsp;";
			?>
			<input type="button" value="<?=__('Cancelar')?>" onclick="Cancelar(this.form);" class=btn />
		</td></tr>
		</table>
		</fieldset>
   </form>
 </td>
</tr>
</table>
<br/>
</td>
</tr>

		<tr><td></td><td>
		<table width=100% class="tb_base">
		<tr>
				<td valign="top" class="subtitulo" align="left" colspan="2">
						<?=__('Lista de Usuarios')?>:
						<hr class="subtitulo_linea_plomo"/>
				</td>
		</tr>
		<tr>
				<td colspan=2>
				<?=__('Para buscar ingresa el nombre del usuario o parte de él.')?>
				</td>
		</tr>
		<tr>
				<td valign="top" align="left" colspan="2"><img src="https://files.thetimebilling.com/templates/default/img/pix.gif" border="0" width="1" height="10"></td>
		</tr>
		<tr>
				<td valign="top" align="center" style="white-space:nowrap">
			<form name="act"  method="post">
					  <input type="checkbox" name="activo" value="1" id="activo" checked />solo activos &nbsp;&nbsp;&nbsp;
						<strong>Nombre</strong>
						<input onkeydown="if(event.keyCode==13)Listar(this.form,'buscar')" type="text" name="nombre" id="nombre" value="<?=$nombre?>" size="20" />
						&nbsp;&nbsp; 
						
						&nbsp; <a href="#" id="btnbuscar"   rel="buscar">Buscar</a>
						&nbsp; <a href="#" class="descargaxls" style="display:none;" rel="xls">Descargar Listado</a>
						&nbsp; <a href="#" class="descargaxls"  style="display:none;" rel="xls_vacacion">Descargar Vacaciones</a>
						&nbsp; <a href="#" class="descargaxls"  style="display:none;" rel="xls_modificaciones">Descargar Modificaciones</a>
						
			</form>
				    
				</td>
		</tr>
		<tr>
				<td colspan=2>
		<br />
<?
		$query = "SELECT * FROM prm_permisos WHERE codigo_permiso<>'ALL' ORDER BY glosa";
		$permisos = new ListaObjetos($sesion,"",$query);

		if($activo)
			$activo="activo=1";
		else
			$activo="1";

		echo "<center>";
		$query = "SELECT SQL_CALC_FOUND_ROWS * FROM usuario WHERE $activo AND (nombre LIKE '%$nombre%' OR apellido1 LIKE '%$nombre%' OR apellido2 LIKE '%$nombre%') ";
		$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
		$b->AgregarEncabezado("apellido1",__('Apellido Paterno'),"align=left");
		$b->AgregarEncabezado("apellido2",__('Apellido Materno'),"align=left");
		$b->AgregarEncabezado("nombre",__('Nombre'),"align=left");
		$b->AgregarFuncion("Activo","PrintActivo","align=center");
		for($i = 0; $i < $permisos->num; $i++)
		{
			$perm = $permisos->Get($i);
			$b->AgregarFuncion($perm->fields[glosa],"PrintCheck","align=center");
		}
		$b->AgregarFuncion("",'Opciones',"align=center");
		$b->color_mouse_over = "#bcff5c";
		$b->Imprimir();
		echo "</center>";
?>
				</td>
		</tr>
</table>

		<tr><td colspan="2">&nbsp;</td></tr>

<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ReportesAvanzados') ) || ( method_exists('Conf','ReportesAvanzados') && Conf::ReportesAvanzados() ) )
		{ ?>
		<tr><td></td><td>
		<table width=100%>
		<tr>
				<td valign="top" class="subtitulo" align="left" colspan="2">
						<?=__('Costo por usuario')?>:
						<hr class="subtitulo"/>
				</td>
		</tr>
		<tr>
				<td colspan=2>
				<a  id="costos" style="margin:auto;width:200px;" href="<?=Conf::RootDir().'/app/interfaces/costos.php' ?>"  ><?=__('Editar costo mensual por usuario') ?></a>
				</td>
		</tr>
		</table>
<? } ?>
		<tr><td colspan="2">&nbsp;</td></tr>
</table>

<?
	function Opciones(& $fila)
	{
			global $sesion;
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) )
				{
					return "<a href=usuario_paso2.php?rut=".$fila->fields[rut]." title='Editar usuario'><img border=0 src=".Conf::ImgDir()."/ver_persona_nuevo.gif alt='Editar' /></a>";
				}
			else
				{
					return "<a href=usuario_paso2.php?rut=".$fila->fields[rut]." title='Editar usuario'><img border=0 src=".Conf::ImgDir()."/ver_persona.gif alt='Editar' /></a>";
				}
	}
	function PrintCheck(& $fila)
	{
		global $sesion, $permisos;
		static $i = 0;

		if($i == $permisos->num)
			$i = 0;
		$permiso = $permisos->Get($i);
		$permiso = $permiso->fields[codigo_permiso];

		$query = "SELECT COUNT(*) FROM usuario_permiso WHERE id_usuario='".$fila->fields['id_usuario']."' AND codigo_permiso='$permiso'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$i++;
				list($count) = mysql_fetch_array($resp);
		if ( UtilesApp::GetConf($sesion,'UsaDisenoNuevo') )	{
				if($count > 0)
					return "<div class='permiso on' id='". $fila->fields['id_usuario'].';'.$permiso."'><img src='".Conf::ImgDir()."/check_nuevo.gif' alt='OK' /></div>";
				else
					return "<div class='permiso off' id='". $fila->fields['id_usuario'].';'.$permiso."'><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' alt='NO' /></div>";
			}
		else
			{
				if($count > 0)
					return "<img src=".Conf::ImgDir()."/check.gif alt='OK' />";
				else
					return "<img src=".Conf::ImgDir()."/cruz_roja.gif alt='NO' />";
			}
	}
	function PrintActivo(& $fila)
	{
		global $sesion;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) )
			{
				if($fila->fields[activo])
					return "<img src=".Conf::ImgDir()."/check_nuevo.gif alt='OK' />";
				else
					return "<img src=".Conf::ImgDir()."/cruz_roja_nuevo.gif alt='NO' />";
			}
		else
			{
				if($fila->fields[activo])
					return "<img src=".Conf::ImgDir()."/check.gif alt='OK' />";
				else
					return "<img src=".Conf::ImgDir()."/cruz_roja.gif alt='NO' />";
			}
	}
	$pagina->PrintBottom();
?>
