<?
	require_once dirname(__FILE__).'/../conf.php';

	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/classes/UsuarioExt.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';

	$sesion = new Sesion( array('ADM') );
	$pagina = new Pagina($sesion);
	$rut_limpio = Utiles::LimpiarRut($rut);
	$usuario = new UsuarioExt($sesion, $rut_limpio);
	

	if($opc == "eliminar")
	{
		$usuario_eliminar = new UsuarioExt($sesion, $rut_limpio);
		if(!$usuario_eliminar->Eliminar())
			$pagina->AddError($usuario_eliminar->error);
		else
		{
			$pagina->Redirect('usuario_paso1.php?opc=eliminado');
		}
	}

	if($opc == 'edit')
	{
		$query = "SELECT count(*) FROM usuario WHERE username = '".addslashes($username)."' AND username != '".addslashes($usuario->fields['username'])."'";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($cantidad) = mysql_fetch_array($resp);
		
		$focus_username = false;
		if($cantidad > 0)
		{
			$pagina->AddError(__('El Código Usuario ingresado ya existe.'));
			$focus_username = true;
		}
		else
		{
			if($email == "")
				$pagina->AddError(__('Debe ingresar el e-mail del usuario'));
	
			$usuario->Edit('rut', Utiles::LimpiarRut($rut));
			$usuario->Edit('dv_rut', $dv_rut);
			$usuario->Edit('nombre', $nombre);
			$usuario->Edit('apellido1', $apellido1);
			$usuario->Edit('apellido2', $apellido2);
			if(!$username)
				$usuario->Edit('username', $nombre.' '.$apellido1.' '.$apellido2);
			else
				$usuario->Edit('username', $username);
			$usuario->Edit('id_categoria_usuario', $id_categoria_usuario);
			$usuario->Edit('id_area_usuario', $id_area_usuario);
			$usuario->Edit('telefono1', $telefono1);
			$usuario->Edit('telefono2', $telefono2);
			$usuario->Edit('dir_calle', $dir_calle);
			$usuario->Edit('dir_numero', $dir_numero);
			$usuario->Edit('dir_depto', $dir_depto);
			$usuario->Edit('dir_comuna', $dir_comuna);
			$usuario->Edit('email', $email);
			$usuario->Edit('activo', $activo);
			$usuario->Edit('visible', $activo==1 ? 1 : $visible);
			$usuario->Edit('restriccion_min', $restriccion_min);
			$usuario->Edit('restriccion_max', $restriccion_max);
			//$usuario->Edit('restriccion_mensual', $restriccion_mensual);
			if($dias_ingreso_trabajo == "") { $dias_ingreso_trabajo = 30; }
			$usuario->Edit('dias_ingreso_trabajo', $dias_ingreso_trabajo);
			$usuario->Edit('retraso_max', $retraso_max);
			$usuario->Edit('restriccion_diario', $restriccion_diario);
			$usuario->Edit('alerta_diaria', $alerta_diaria);
			$usuario->Edit('alerta_semanal', $alerta_semanal);
			$usuario->Edit('alerta_revisor', $alerta_revisor);
			$usuario->Edit('id_moneda_costo', $id_moneda_costo);
	
	 		if( $usuario->loaded )
			{
				if( $usuario->Write() )
				{
					CargarPermisos();
	        $usuario->GuardarSecretario($usuario_secretario);
					$usuario->GuardarRevisado($arreglo_revisados);
					$usuario->GuardarTarifaSegunCategoria($usuario->fields['id_usuario'],$usuario->fields['id_categoria_usuario']);
					$usuario->GuardarVacacion($vacaciones_fecha_inicio, $vacaciones_fecha_fin);
					$pagina->AddInfo( __('Usuario editado con éxito.'));
				}
				else
				{
					$pagina->AddError( $usuario->error );
				}
			}
			else
			{
				$new_password = Utiles::NewPassword();
				$usuario->Edit('password', md5( $new_password ) );
	
				if( $usuario->Write() )
				{
					CargarPermisos();
					$usuario->GuardarSecretario($usuario_secretario);
					$usuario->GuardarRevisado($arreglo_revisados);
					$usuario->GuardarTarifaSegunCategoria($usuario->fields['id_usuario'],$usuario->fields['id_categoria_usuario']);
					$pagina->AddInfo( __('Usuario ingresado con éxito, su nuevo password es').' '.$new_password );
				}
				else
				{
					$pagina->AddError( $usuario->error );
				}
			}
	
			$lista_monedas = new ListaObjetos($sesion,"","SELECT * FROM prm_moneda");
			for($x=0;$x<$lista_monedas->num;$x++)
			{
				$moneda = $lista_monedas->Get($x);
				if($mon_costo[$moneda->fields['id_moneda']] != 0)
					$usuario->GuardarCosto($moneda->fields['id_moneda'],$mon_costo[$moneda->fields['id_moneda']]);
			}
		}
	}
	else if($opc == 'pass' and $usuario->loaded)
	{
		if($genpass > 0)
			$new_password = Utiles::NewPassword();

		$usuario->Edit('password', md5( $new_password ) );

		if( $usuario->Write() )
		{
			$pagina->AddInfo( __('Contraseña modificada con éxito') );

			if($genpass > 0)
				$pagina->AddInfo( __('Nueva contraseña:').' '.$new_password );
		}
		else
		{
			$pagina->AddError( $usuario->error );
		}
	}
	elseif($opc == 'cancelar')
		$pagina->Redirect('usuario_paso1.php');
	elseif($opc == 'elimina_vacacion' and $usuario->loaded)
	{
		if( $usuario->EliminaVacacion($vacacion_id_tmp,$usuario->fields['id_usuario']) )
			$pagina->AddInfo( __('Se ha eliminado correctamente el dato de vacaciones.'));
	}

	$usuario_vacaciones = $usuario->ListaVacacion($usuario->fields['id_usuario']);
	$pagina->titulo = __('Administración - Usuarios');
	$pagina->PrintTop();
  if($usuario->loaded)
		$dv_rut=$usuario->fields['dv_rut'];
  $lista_monedas = new ListaObjetos($sesion,"","SELECT * FROM prm_moneda");
  $tooltip_select = Html::Tooltip("Para seleccionar más de un criterio o quitar la selección, presiona la tecla <strong>CTRL</strong> al momento de hacer <strong>clic</strong>.");
?>
<script language="javascript" type="text/javascript">
<? if($usuario->loaded) { ?>
function CheckActivo(activo)
{
	if(!activo.checked)
	{
		$('divVisible').style['display']="inline";
	}
	else
	{
		$('divVisible').style['display']="none";
	}
}
<? } ?>
function Cancelar(form)
{
	form.opc.value = 'cancelar';
	form.submit();
}

var necesitaConfirmar = false;
function Validar(form)
{
	if(form.email.value == "")
	{
		alert("Debe ingresar el e-mail del usuario");
		return false;
	}
	if(form.nombre.value == "")
	{
		alert("Debe ingresar el nombre del usuario");
		return false;
	}
	if(form.apellido1.value == "")
	{
		alert("Debe ingresar el apellido del usuario");
		return false;
	}
	ArregloRevisados();
	necesitaConfirmar = false;
	return true;
}
function Eliminar()
{
	if (confirm('¿<?=__('Está seguro de eliminar el')." ".__('usuario')?>?'))
		location.href="usuario_paso2.php?rut=<?=$usuario->fields['rut'] ?>&opc=eliminar";
}

function Cambiar_Usuario_Categoria(id_usuario,id_origen,accion)
		{ 
		if(confirm('¿Desea cambiar todas las tarifas del abogado a esta categoría?'))
		 	{  
		 	document.form_usuario.submit();
				var select_origen = document.getElementById(id_origen);
				var http = getXMLHTTP();
				var vurl = root_dir + '/app/ajax.php?accion=' + accion + '&id=' + id_usuario + '&id_2=' + select_origen.value ;

				cargando = true;
				http.open('get', vurl, true);
			
				http.onreadystatechange = function()
					{
						if(http.readyState == 4)
						{
							var response = http.responseText;
							alert( 'Tarifas actualizados con éxito.' );
						}
						cargando = false;
					}
				http.send(null);
		  }
		}
		function AgregarUsuarioRevisado()
		{
			var fuera = $('usuarios_fuera');
			var dentro = $('usuarios_revisados');
			  
			  if (fuera.selectedIndex==-1) return;
			  
			  valor = fuera.value;
			  txt = fuera.options[fuera.selectedIndex].text;
			  
			  fuera.options[fuera.selectedIndex]=null;
			  
			  opc = new Option(txt,valor);
			  dentro.options[dentro.options.length]=opc;

			  necesitaConfirmar = true;
		}
		function EliminarUsuarioRevisado()
		{
			var dentro =$('usuarios_revisados');
			var fuera = $('usuarios_fuera');

			  if (dentro.selectedIndex==-1) return;
			  valor=dentro.value;
			  txt=dentro.options[dentro.selectedIndex].text;
			  dentro.options[dentro.selectedIndex]=null;
			  opc = new Option(txt,valor);
			  fuera.options[fuera.options.length]=opc;

			  necesitaConfirmar = true;
		}
		function ArregloRevisados()
		{
				var usuarios = new Array();
				var dentro = $('usuarios_revisados');
			
				for(i = 0; i < dentro.options.length; i++ )
				{
					usuarios[i] = dentro.options[i].value;
				}
				$('arreglo_revisados').value = usuarios.join('::');
		}
		function preguntarGuardar()
		{
			if (necesitaConfirmar)
			return "Usted ha modificado los usuarios revisados sin guardar los cambios. Si continúa cerrando la página perderá los cambios realizados.";
		}
		
		function Expandir(id)
		{
			var tabla = $(id+"_tabla");
			var img = $(id+"_img");
			if(tabla.style['display'] != 'none')
			{
				tabla.hide();
				img.innerHTML = "<img src='../templates/default/img/mas.gif' border='0' title='Desplegar'>";
			}
			else
			{
				tabla.show();
				img.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
			}
		}
									
</script>



<form action="usuario_paso2.php" name="form_usuario" id="form_usuario" method="post" enctype="multipart/form-data" onSubmit="return Validar(this);">
 <input type="hidden" name="opc" id="opc" value="edit" />
 <input type="hidden" name="rut" value="<?=$rut?>" />
 <input type="hidden" name="dv_rut" value="<?=$dv_rut?>" />
 <input type="hidden" name="vacacion_id_tmp" id="vacacion_id_tmp" value="" />
<fieldset>
	<legend><?=__('Datos básicos')?></legend>
	<table>
	<tr>
		<td valign="top" class="texto" align="right">
			<strong><?=( method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'NombreIdentificador') : Conf::NombreIdentificador() )?></strong>
		</td>
		<td valign="top" class="texto" align="left">
			<strong><?=$rut?>-<?=$dv_rut?></strong>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<?=__('Nombre Completo')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="nombre" value="<?=$usuario->fields['nombre'] ? $usuario->fields['nombre'] : $nombre ?>" size="30" style=""/> <span class="req">*</span>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<?=__('Apellido Paterno')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="apellido1" value="<?=$usuario->fields['apellido1'] ? $usuario->fields['apellido1'] : $apellido1 ?>" size="20" style=""/> <span class="req">*</span>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<?=__('Apellido Materno')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="apellido2" value="<?=$usuario->fields['apellido2'] ? $usuario->fields['apellido2'] : $apellido2 ?>" size="20" style=""/>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<?=__('Código Usuario')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="username" id="username" value="<?=$usuario->fields['username'] ? $usuario->fields['username'] : $username ?>" size="20" style=""/>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<?=__('Categoría Usuario')?>
		</td>
		<td valign="top" class="texto" align="left">
			<?=Html::SelectQuery($sesion,'SELECT id_categoria_usuario,glosa_categoria FROM prm_categoria_usuario ORDER BY id_categoria_usuario','id_categoria_usuario', $usuario->fields['id_categoria_usuario'] ? $usuario->fields['id_categoria_usuario'] : $id_categoria_usuario ,"onchange=Cambiar_Usuario_Categoria('".$usuario->fields['id_usuario']."','id_categoria_usuario','cambiar_tarifa_usuario'); ")?>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<?=__('Área Usuario')?>
		</td>
		<td valign="top" class="texto" align="left">
			<?=Html::SelectQuery($sesion,'SELECT id, glosa FROM prm_area_usuario ORDER BY glosa','id_area_usuario', $usuario->fields['id_area_usuario'] ? $usuario->fields['id_area_usuario'] : $id_area_usuario)?>
		</td>
	</tr>

    <tr><td>&nbsp;</td></tr>  <!-- spacer -->
	<tr style="display:none">
		<td valign="top" class="texto" align="right">
			<?=__('Dirección')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="dir_calle" value="<?=$usuario->fields['dir_calle']?>" size="30"/>
		</td>
	</tr>
	<tr style="display:none">
		<td valign="top" class="texto" align="right">
			<?=__('Número')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="dir_numero" value="<?=$usuario->fields['dir_numero']?>" size="8"/>
		</td>
	</tr>
	<tr style="display:none">
		<td valign="top" class="texto" align="right">
			<?=__('Departamento')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="dir_depto" value="<?=$usuario->fields['dir_depto']?>" size="8"/>
		</td>
	</tr>
	<tr style="display:none">
		<td valign="top" class="texto" align="right">
			<?=__('Comuna')?>
		</td>
		<td valign="top" class="texto" align="left">
              <?=Html::SelectQuery($sesion,'SELECT id_comuna,glosa_comuna FROM prm_comuna ORDER BY glosa_comuna','dir_comuna', $usuario->fields['dir_comuna'])?>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<?=__('Teléfono')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="telefono1" value="<?=$usuario->fields['telefono1'] ? $usuario->fields['telefono1'] : $telefono1 ?>" size="16"/>
		</td>
	</tr>
	<tr style="display:none">
		<td valign="top" class="texto" align="right">
			<?=__('Teléfono')?> 2
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="telefono2" value="<?=$usuario->fields['telefono2'] ? $usuario->fields['telefono2'] : $telefono2 ?>" size="16"/>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<?=__('E-Mail')?>
		</td>
		<td valign="top" class="texto" align="left">
			<input type="text" name="email" value="<?=$usuario->fields['email'] ? $usuario->fields['email'] : $email ?>" size="30"/> <span class="req">*</span>
		</td>
	</tr>

	<tr>
    		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			&nbsp;
		</td>
		<td valign="top" class="texto" align="left">
			<input id="activo" type="checkbox" name="activo" value="1" <?=(($usuario->fields['activo'] || $activo )?'checked':'')?> <? if($usuario->loaded) { ?> onClick="CheckActivo(this);" <? } ?>/>
			<label for="activo"><?=__('Usuario Activo')?></label><br/>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-size: 9px;"><?=__('(sólo los usuarios activos pueden ingresar al sistema)')?></span>
		</td>
	</tr>
<? if($usuario->loaded) { ?>
	<tr>
		<td valign="top" class="texto" align="right">
			&nbsp;
		</td>
		<td valign="top" class="texto" align="left">
			<div id=divVisible <? if($usuario->fields['activo'] == 1) echo 'style="display:none"'; else echo 'style="display:inline"'?>>
				<input type=checkbox name=visible value=1 <?= $usuario->fields['visible'] == 1 ? "checked" : "" ?> id="chkVisible" onMouseover="ddrivetip('Usuario visible en listados')" onMouseout="hideddrivetip()">
				<label for="visible"><?=__('Visible en Listados')?></label>
			</div>
		</td>
	</tr>
<? } ?>
	</table>
	</fieldset>

	<fieldset>
	<legend><?=__('Permisos')?></legend>
	<table>
                <?=Html::PrintCheckbox($sesion, $usuario->permisos, 'codigo_permiso', 'glosa', 'permitido');?>
<?
	if(!$usuario->loaded)
		echo "<em>Debe agregar el usuario para poder asignarle permisos</em>";
?>
		</table>
		</fieldset>

		<fieldset>
		<legend onClick="Expandir('secretario')" style="cursor:pointer">
			<span id="secretario_img"><img src= "<?=Conf::ImgDir()?>/mas.gif" border="0" ></span>
			<?=__('Usuario secretario de')?>
		</legend>
		<table id="secretario_tabla" style="display:none">
			<tr>
				<td>
<?

		$where = '';
		if($usuario->loaded)
		{
			$where =  "id_usuario <> ".$usuario->fields['id_usuario'];
		}
		if(!$where)
			$where = 1;
		$lista_usuarios = new ListaObjetos($sesion,'',"SELECT id_usuario,CONCAT_WS(' ',nombre,apellido1,apellido2) as name FROM usuario
																						WHERE activo=1 AND $where
																						ORDER BY nombre");
        echo  "<select name='usuario_secretario[]' id='usuario_secretario' multiple size=6 $tooltip_select  style='width: 200px;'>";
		for($x=0;$x<$lista_usuarios->num;$x++)
		{
			$us = $lista_usuarios->Get($x);
?>
			<option value='<?=$us->fields['id_usuario']?>' <?=$usuario->LoadSecretario($us->fields['id_usuario']) ? "selected" : ""?>><?=$us->fields['name']?></option>
<?
		}
		echo "</select>";
?>
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset>
		<legend onClick="Expandir('revisor')" style="cursor:pointer">
			<span id="revisor_img"><img src= "<?=Conf::ImgDir()?>/mas.gif" border="0" ></span>
			<?=__('Usuario revisor de')?>
		</legend>
<?
		// El siguiente código está comentado porque se ejecuta en el fieldset 'Usuario secretario de', descomentar si se modifica el fieldset 'Usuario secretario de'
		/*
		$where = '';
		if($usuario->loaded)
		{
			$where =  "id_usuario <> ".$usuario->fields['id_usuario'];
		}
		if(!$where)
			$where = 1;
		$lista_usuarios = new ListaObjetos($sesion,'',"SELECT id_usuario,CONCAT_WS(' ',nombre,apellido1,apellido2) as name FROM usuario WHERE $where ORDER BY nombre");
		*/
?>
		<table id="revisor_tabla" style='display:none'>
			<tr>
				<td align=right>
					<?=__('Usuarios disponibles')?>:
				</td>
				<td align=left>
						<?=$usuario->select_no_revisados()?>
				</td>
				<td>
						<input type=button class="btn" value="<?=__('Añadir')?>" onclick="AgregarUsuarioRevisado()"/>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?=__('Usuarios revisados')?>:
				</td>
				<td align=left>
						<?=$usuario->select_revisados();?>
				</td>
				<td>
						<input type=button class="btn" value="<?=__('Eliminar')?>" onclick="EliminarUsuarioRevisado()"/> 
				</td>
			</tr>
		</table>
		</fieldset>
		<fieldset>
		<legend onClick="Expandir('restricciones')" style="cursor:pointer">
			<span id="restricciones_img"><img src= "<?=Conf::ImgDir()?>/mas.gif" border="0" ></span>
			<?=__('Restricciones y alertas')?>
		</legend>
		<table id="restricciones_tabla" style='display:none'>
		<tr>
			<td width=18% align="right">
				<label for="alerta_diaria" align="right"><?=__('Alerta Diaria')?></label>
			</td>
			<td width=10%>
				<input type="checkbox" id=alerta_diaria name=alerta_diaria <?=$usuario->fields['alerta_diaria'] ? "checked":""?> value=1 />
			</td>
			<td width=54% colspan=3 align="right">
				<?=__('Retraso máximo en el ingreso de horas')?>
			</td>
			<td width=18%>
				<input type="text" size=10 value="<?=$usuario->fields['retraso_max']?>" name="retraso_max" />
			</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
			<td colspan="3" align="right">
				<?=__('Mínimo de horas por día')?>
			</td>
			<td colspan="1">
				<input type="text" size=10 value="<?=$usuario->fields['restriccion_diario']?>" name="restriccion_diario" />
			</td>
		</tr>
		<tr>
			<td width=18% align="right">
				<label for="alerta_semanal" align="right"><?=__('Alerta Semanal')?></label> 
			</td>
			<td width=10%>
				<input type="checkbox" id="alerta_semanal" name="alerta_semanal" <?=$usuario->fields['alerta_semanal'] ? "checked":""?> value=1 />
			</td>
			<td width=18% align="right">
				<?=__('Mín. HH')?> 
			</td>
			<td width=18%>
				<input type="text" size=10 value="<?=$usuario->fields['restriccion_min']?>" name="restriccion_min" />
			</td>
			<td width=18% align="right">
				<?=__('Máx. HH')?> 
			</td>
			<td width=18%>
				<input type="text" size=10 value="<?=$usuario->fields['restriccion_max']?>" name="restriccion_max" />
			</td>
		</tr>
		<tr>
			<td colspan="5" align="right">
				<label for="alerta_revisor"><?=__('Resumen de horas semanales de abogados revisados')?></label>
			</td>
			<td>
				<input type="checkbox" id="alerta_revisor" name="alerta_revisor" <?=$usuario->fields['alerta_revisor'] ? "checked" : "" ?> value=1 />
			</td>
		</tr>
		<tr>
			<td colspan="5" align="right">
				<label for="restriccion_mensual"><?=__('Mínimo mensual de horas')?></label>
			</td>
			<td>
				<input type="text" size="10" <?=Html::Tooltip("Para no recibir alertas mensuales ingrese 0.")?> value="<?=$usuario->fields['restriccion_mensual']?>" id="restriccion_mensual" name="restriccion_mensual" />
			</td>
		</tr>
<?
		if($usuario->loaded)
		{
			$params_array['codigo_permiso'] = 'COB';
			$permiso_cobranza = $usuario->permisos->Find('FindPermiso',$params_array);
			if(!$permiso_cobranza->fields['permitido'])
			{
?>
		<tr>
			<td colspan="5" align="right">
				<label for="dias_ingreso_trabajo"><?=__('Plazo máximo (en días) para ingreso de trabajos')?></label>
			</td>
			<td>
				<input type="text" size="10" value="<?=$usuario->fields['dias_ingreso_trabajo']?>" id="dias_ingreso_trabajo" name="dias_ingreso_trabajo" />
			</td>
		</tr>

<?
			}
		}
?>
		</table>
		</fieldset>
		
		
		<!-- Vacacciones -->
		<fieldset>
			<legend onClick="Expandir('vacaciones')" style="cursor:pointer">
				<span id="vacaciones_img"><img src= "<?=Conf::ImgDir()?>/mas.gif" border="0" ></span>
				<?=__('Vacaciones')?>
			</legend>
			<table id="vacaciones_tabla" style='display:none;' width="400px">
				<tr>
					<td colspan="3" align="left"><?=__('Seleccione las fecha para ingresar el periodo de vacacciones.');?></td>
				</tr>
				<tr>
					<td align="right"><label for="alerta_diaria" align="right"><?=__('Fecha inicio')?></label></td>
					<td colspan="2" align="left">
						<input type="text" name="vacaciones_fecha_inicio" value="" id="vacaciones_fecha_inicio" class="cls_fecha_vacaciones" size="11" maxlength="10"/>
		        <img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_vacaciones_fecha_inicio" style="cursor:pointer" />
		      </td>
		    </tr>
		    <tr>
		    	<td align="right"><label for="alerta_diaria" align="right"><?=__('Fecha fin')?></label></td>
					<td colspan="2" align="left">
						<input type="text" name="vacaciones_fecha_fin" value="" id="vacaciones_fecha_fin" class="cls_fecha_vacaciones" size="11" maxlength="10"/>
		        <img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_vacaciones_fecha_fin" style="cursor:pointer" />
		        &nbsp;&nbsp;<input type="button" value="<?=__('Guardar')?>" id="btn_guardar_vacacion" class=btn />
					</td>
				</tr>
				<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="3" style="font-size:11px; font-weight:bold">Lista de vacaciones ingresadas</td>
				</tr>
				<tr style="border:1px solid #454545">
					<td width="180px" style="font-weight:bold; border:1px solid #ccc; text-align:center;">Inicio</td>
					<td width="180px" style="font-weight:bold; border:1px solid #ccc; text-align:center;">Fin</td>
					<td width="40px" style="border:1px solid #ccc">&nbsp;</td>
				</tr>
<?php foreach($usuario_vacaciones as $k => $vaca): ?>
				<tr>
					<td style="border:1px solid #ccc; text-align:center;"><?php echo $vaca['fecha_inicio']; ?></td>
					<td style="border:1px solid #ccc; text-align:center;"><?php echo $vaca['fecha_fin']; ?></td>
					<td style="border:1px solid #ccc; text-align: center"><img src= "<?=Conf::ImgDir()?>/eliminar.gif" id="vacacion_<?php echo $vaca['id']; ?>" border="0" style="cursor:pointer;" class="cls_eliminar_vacacion" title="Eliminar registro" ></td>
				</tr>
<?php endforeach; ?>
			</table>
		</fieldset>
		
		<div style="both:clear">&nbsp;</div>
		
		<fieldset>
			<legend><?=__('Guardar datos')?></legend>
<?
		if( $sesion->usuario->fields['id_visitante'] == 0 )
		{
?>
					<input type="submit" value="<?=__('Guardar')?>" class=btn /> &nbsp;&nbsp;
<? 	}
		else 
		{
?>
			<input type="button" onclick="alert('Usted se encuentra en un sistema demo, no tiene derecho de modificar datos.');" value="<?=__('Guardar')?>" class=btn /> &nbsp;&nbsp;
<?  } 
?>
			<input type="button" value="<?=__('Cancelar')?>" onclick="Cancelar(this.form);" class=btn />
<? if($usuario->loaded && $sesion->usuario->fields['id_visitante'] == 0) { ?>
		<input type="button" onclick="Eliminar();" value='<?=__('Eliminar Usuario') ?>' class="btn_rojo" ></input>
<? } ?>
		</fieldset>
   </form>
		<br/><br/>
<?
    if($usuario->loaded)
    {
?>

  <form  method="post" action="<?= $SERVER[PHP_SELF] ?>">
  <input type="hidden" name="opc" value="pass" />
  <input type="hidden" name="rut" value="<?=$rut?>" />
  <input type="hidden" name="dv_rut" value="<?=$dv_rut?>" />
<fieldset>
<legend><?=__('Cambio de contraseña')?></legend>
<table width="100%">
    <tr>
        <td colspan="2" class="texto" align="left">
            <?=__('Ingrese una nueva contraseña para este usuario, o escoja crear una aleatoria.')?><br/>
            <strong><?=__('Atención')?></strong>: <?=__('La contraseña anterior será reemplazada e imposible de recuperar.')?><br/>
        </td>
    </tr>
    <tr>
        <td width="20">&nbsp;</td>
        <td class="texto" align="left">
            <input type="radio" name="genpass" value="0" id="new_pass" />
				<label for="new_pass"><?=__('Contraseña nueva')?>:</label>
            <input type="text" name="new_password" value="" size="16" onclick="javascript:document.getElementById('new_pass').checked='checked'"/><br/>
            <input type="radio" name="genpass" value="1" checked="checked" id="rand_pass" />
            	<label for="rand_pass"><?=__('Generar contraseña aleatoria')?></label>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2">
            <input type="submit" value="<?=__('Cambiar Contraseña')?>" size="16"/>
        </td>
    </tr>
	</table>
</fieldset>
  </form>
<?
    }
?>
<?
function CargarPermisos()
{
    global $usuario, $pagina, $permiso, $_POST;

    for($i = 0; $i < $usuario->permisos->num; $i++)
    {
        $permiso = &$usuario->permisos->get($i);
        if($permiso->fields['permitido'] <> $_POST[$permiso->fields['codigo_permiso']])
        {
            $permiso->fields['permitido'] = $_POST[$permiso->fields['codigo_permiso']];
            if(!$usuario->EditPermisos($permiso))
                $pagina->AddError($usuario->error);
        }
    }
    $usuario->PermisoALL();
}?>
<script>
	window.onbeforeunload = function(){
     return preguntarGuardar();
	};
	
	<? if( $focus_username ) { ?>
		$('username').value = '';
	<? } ?>
	
	String.prototype.fechaDDMMAAAA = function() {
		return this.replace(/^(\d{2})\-(\d{2})\-(\d{4})$/, "$3/$2/$1");
	}
	//Datepicker para las fechas de Vacaciones
	$$('.cls_fecha_vacaciones').each(function(elemento){
		var ide = elemento.id;
		Calendar.setup({
			inputField: ide,
			ifFormat: "%d-%m-%Y",
			button: "img_"+ide
		});
	});
	//Submit desde botón agregar Vacacciones
	$('btn_guardar_vacacion').observe('click', function(e){
		var fecha_ini = $F('vacaciones_fecha_inicio').fechaDDMMAAAA();
		var fecha_fin = $F('vacaciones_fecha_fin').fechaDDMMAAAA();
		if(fecha_ini != '' &&  fecha_fin != '')
		{
			if(fecha_ini > fecha_fin)
			{
				alert("La fecha inicio no puede ser superior a la fecha fin.");
				e.stop();
				return false;
			}
			$('form_usuario').submit();
		}
	});
	//Eliminar Vacaciones
	$$('.cls_eliminar_vacacion').each(function(elemento){
		elemento.observe('click', function(evento){ 
			evento.stop();
			var ide = elemento.id;
			var tmp = ide.split('_');
			$('opc').value = 'elimina_vacacion';
			$('vacacion_id_tmp').value = tmp[1];
			$('form_usuario').submit();
		});
	});
	
</script>
<?
    $pagina->PrintBottom();
?>
