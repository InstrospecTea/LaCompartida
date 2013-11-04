<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/classes/UsuarioExt.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/classes/Funciones.php';

$sesion = new Sesion(array('ADM'));
$pagina = new Pagina($sesion);
$rut_limpio = Utiles::LimpiarRut($rut);
$usuario = new UsuarioExt($sesion, $rut_limpio);

$validaciones_segun_config = method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'ValidacionesCliente');
$obligatorio = '<span style="color:#ff0000;font-size:10px;vertical-align:top;">*</span>';

if ($opc == "eliminar") {
	$usuario_eliminar = new UsuarioExt($sesion, $rut_limpio);

	if (!$usuario_eliminar->Eliminar()) {
		$pagina->AddError($usuario_eliminar->error);
	} else {
		$pagina->Redirect('usuario_paso1.php?opc=eliminado');
	}
}

$modulo_retribuciones_activo = Conf::GetConf($sesion, 'UsarModuloRetribuciones') || false;
$id_categoria_anterior = $usuario->fields['id_categoria_usuario'];

if ($opc == 'edit') {
	//Arreglo Original, antes de guardar los cambios $arr1
	$arr1 = $usuario->fields;

	$usuario->Edit('rut', Utiles::LimpiarRut($rut));
	$usuario->Edit('dv_rut', $dv_rut);
	$usuario->Edit('nombre', $nombre);
	$usuario->Edit('apellido1', $apellido1);
	$usuario->Edit('apellido2', $apellido2);

	if (empty($username) and !$validaciones_segun_config) {
		$username = $nombre . ' ' . $apellido1 . ' ' . $apellido2;
	}

	$usuario->Edit('username', $username);
	$usuario->Edit('centro_de_costo', $centro_de_costo);

	if ($modulo_retribuciones_activo){
		$usuario->Edit('porcentaje_retribucion', $porcentaje_retribucion);
	}

	$usuario->Edit('id_categoria_usuario', $id_categoria_usuario);
	$usuario->Edit('id_area_usuario', $id_area_usuario);
	$usuario->Edit('telefono1', $telefono1);
	$usuario->Edit('telefono2', $telefono2);
	$usuario->Edit('email', $email);
	$usuario->Edit('activo', $activo);
	$usuario->Edit('visible', $activo == 1 ? 1 : $visible);
	$usuario->Edit('restriccion_min', $restriccion_min);
	$usuario->Edit('restriccion_max', $restriccion_max);
	$usuario->Edit('restriccion_mensual', $restriccion_mensual);

	if ($dias_ingreso_trabajo == "") {
		$dias_ingreso_trabajo = 30;
	}

	$usuario->Edit('dias_ingreso_trabajo', $dias_ingreso_trabajo);
	$usuario->Edit('retraso_max', $retraso_max);
	$usuario->Edit('restriccion_diario', $restriccion_diario);
	$usuario->Edit('alerta_diaria', $alerta_diaria);
	$usuario->Edit('alerta_semanal', $alerta_semanal);
	$usuario->Edit('alerta_revisor', $alerta_revisor);

	$usuario->Validaciones($arr1, $pagina, $validaciones_segun_config);
	$errores = $pagina->GetErrors();

	if (empty($errores)) {

		//Compara y guarda cambios en los datos del Usuario
		$usuario->GuardaCambiosUsuario($arr1, $usuario->fields);

		if ($usuario->loaded) {

			if ($usuario->Write()) {
				CargarPermisos();
				$usuario->GuardarSecretario($usuario_secretario);
				$usuario->GuardarRevisado($arreglo_revisados);

				if ( $id_categoria_anterior != $usuario->fields['id_categoria_usuario']) {
					$usuario->GuardarTarifaSegunCategoria($usuario->fields['id_usuario'], $usuario->fields['id_categoria_usuario']);	
				}
				
				$usuario->GuardarVacacion($vacaciones_fecha_inicio, $vacaciones_fecha_fin);
				$pagina->AddInfo(__('Usuario editado con �xito.'));
			} else {
				$pagina->AddError($usuario->error);
			}

		} else {

			
			$new_password = Utiles::NewPassword();
			$usuario->Edit('password', md5($new_password));

			if ($usuario->Write()) {
				CargarPermisos();
				$usuario->GuardarSecretario($usuario_secretario);
				$usuario->GuardarRevisado($arreglo_revisados);
				$usuario->GuardarTarifaSegunCategoria($usuario->fields['id_usuario'], $usuario->fields['id_categoria_usuario']);

				$pagina->AddInfo(__('Usuario ingresado con �xito, su nuevo password es') . ' ' . $new_password);
			} else {
				$pagina->AddError($usuario->error);
			}
		}

		$lista_monedas = new ListaObjetos($sesion, "", "SELECT * FROM prm_moneda");
		for ($x = 0; $x < $lista_monedas->num; $x++) {
			$moneda = $lista_monedas->Get($x);
			if ($mon_costo[$moneda->fields['id_moneda']] != 0)
				$usuario->GuardarCosto($moneda->fields['id_moneda'], $mon_costo[$moneda->fields['id_moneda']]);
		}
	}

} else if ($opc == 'pass' and $usuario->loaded) {
 
 	if (isset($genpass)) {

		if ($genpass > 0) {
			$new_password = Utiles::NewPassword();
		}

		$usuario->Edit('reset_password_by', 'A');
		$usuario->Edit('password', md5($new_password));
	}

	$force_reset = (isset($force_reset_password) && $force_reset_password == '1') ? $force_reset_password : 0;
	$usuario->Edit('force_reset_password', $force_reset);

	if ($usuario->Write()) {
		
		$pagina->AddInfo(__('Contrase�a modificada con �xito'));
		
		if ($genpass > 0) {
			$pagina->AddInfo(__('Nueva contrase�a:') . ' ' . $new_password);
		}

	} else {
		$pagina->AddError($usuario->error);
	}

} elseif ($opc == 'cancelar') {
	
	$pagina->Redirect('usuario_paso1.php');

} elseif ($opc == 'elimina_vacacion' and $usuario->loaded) {
	
	if ($usuario->EliminaVacacion($vacacion_id_tmp, $usuario->fields['id_usuario'])) {
		$pagina->AddInfo(__('Se ha eliminado correctamente el dato de vacaciones.'));
	}
		
}

//Lista de vacaciones
$usuario_vacaciones = array();

if ($usuario->loaded) {
	$usuario_vacaciones = $usuario->ListaVacacion($usuario->fields['id_usuario']);
	$usuario_historial = $usuario->ListaCambios($usuario->fields['id_usuario']);
}

$pagina->titulo = __('Administraci�n - Usuarios');
$pagina->PrintTop();

if ($usuario->loaded) {
	$dv_rut = $usuario->fields['dv_rut'];
}
	
$lista_monedas = new ListaObjetos($sesion, "", "SELECT * FROM prm_moneda");
$tooltip_select = Html::Tooltip("Para seleccionar m�s de un criterio o quitar la selecci�n, presiona la tecla <strong>CTRL</strong> al momento de hacer <strong>clic</strong>.");
?>

<script type="text/javascript" src="https://static.thetimebilling.com/js/typewatch.js"></script>

<script type="text/javascript">

	<?php if ($usuario->loaded) { ?>

	function CheckActivo(activo)
	{
		if (!activo.checked) {
			$('divVisible').style['display']="inline";
		} else {
			$('divVisible').style['display']="none";
			}
	}
	<?php } ?>

	function Cancelar(form)
	{
		form.opc.value = 'cancelar';
		form.submit();
	}

	var necesitaConfirmar = false;
	
	function Validar(form)
	{
		if (form.email.value == "") {
			alert("Debe ingresar el e-mail del usuario");
			return false;
		}

		if (form.nombre.value == "")
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
		if (confirm('� <?php echo __('Est� seguro de eliminar el') . " " . __('usuario') ?> ?'))
		location.href="usuario_paso2.php?rut=<?php echo $usuario->fields['rut'] ?>&opc=eliminar";
	}

	function Cambiar_Usuario_Categoria(id_usuario,id_origen,accion)
	{
		if(confirm('�Desea cambiar todas las tarifas del abogado a esta categor�a?'))
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
					alert( 'Tarifas actualizados con �xito.' );
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
			return "Usted ha modificado los usuarios revisados sin guardar los cambios. Si contin�a cerrando la p�gina perder� los cambios realizados.";
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
	<input type="hidden" name="rut" value="<?php echo $rut ?>" />
	<input type="hidden" name="dv_rut" value="<?php echo $dv_rut ?>" />
	<input type="hidden" name="vacacion_id_tmp" id="vacacion_id_tmp" value="" />
	<fieldset>
		<legend><?php echo __('Datos b�sicos') ?></legend>
		<table>

			<tr>
				<td valign="top" class="texto" align="right">
					<strong>

						<?php 
						$nombre_identificador = ( method_exists('Conf', 'GetConf') ? Conf::GetConf($sesion, 'NombreIdentificador') : Conf::NombreIdentificador() ); 
						echo $nombre_identificador 
						?>

					</strong>
				</td>
				<td valign="top" class="texto" align="left">
					
					<?php 

					if ( $nombre_identificador == 'RUT' ) {
						$separador = '-'; 
					} else { 
						$separador = ''; 
					} ?>

					<strong> <?php echo $rut ?> <?php echo $separador ?> <?php echo $dv_rut ?> </strong>
				</td>
			</tr>

			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('Nombre Completo') ?><span class="req">*</span>
				</td>
				<td valign="top" class="texto" align="left">
					<input type="text" name="nombre" value="<?php echo $usuario->fields['nombre'] ? $usuario->fields['nombre'] : $nombre ?>" size="30" style=""/>
				</td>
			</tr>

			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('Apellido Paterno') ?><span class="req">*</span>
				</td>
				<td valign="top" class="texto" align="left">
					<input type="text" name="apellido1" value="<?php echo $usuario->fields['apellido1'] ? $usuario->fields['apellido1'] : $apellido1 ?>" size="20" style=""/>
				</td>
			</tr>

			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('Apellido Materno') ?>
				</td>
				<td valign="top" class="texto" align="left">
					<input type="text" name="apellido2" value="<?php echo $usuario->fields['apellido2'] ? $usuario->fields['apellido2'] : $apellido2 ?>" size="20" style=""/>
				</td>
			</tr>

			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('C�digo Usuario') ?>
					<?php if ($validaciones_segun_config) echo $obligatorio ?>
				</td>
				<td valign="top" class="texto" align="left">
					<input type="text" name="username" id="username" value="<?php echo $usuario->fields['username'] ? $usuario->fields['username'] : $username ?>" size="20" style=""/>
				</td>
			</tr>

			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('Centro de Costo') ?>
				</td>
				<td valign="top" class="texto" align="left">
					<input type="text" name="centro_de_costo" id="centro_de_costo" value="<?php echo $usuario->fields['centro_de_costo'] ? $usuario->fields['centro_de_costo'] : $centro_de_costo ?>" size="20" style=""/>
					&nbsp;
					<i>(<?php echo __('para integraci�n contable') ?>)</i>
				</td>
			</tr>

			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('Categor�a Usuario') ?>
					<?php if ($validaciones_segun_config) echo $obligatorio ?>
				</td>
				<td valign="top" class="texto" align="left">
					<?php echo Html::SelectQuery($sesion, 'SELECT id_categoria_usuario,glosa_categoria FROM prm_categoria_usuario ORDER BY id_categoria_usuario', 'id_categoria_usuario', $usuario->fields['id_categoria_usuario'] ? $usuario->fields['id_categoria_usuario'] : $id_categoria_usuario, $usuario->loaded ? "onchange=Cambiar_Usuario_Categoria('" . $usuario->fields['id_usuario'] . "','id_categoria_usuario','cambiar_tarifa_usuario'); " : "") ?>
				</td>
			</tr>
			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('�rea Usuario') ?>
					<?php if ($validaciones_segun_config) echo $obligatorio ?>
				</td>
				<td valign="top" class="texto" align="left">

					<?php
					$query_areas = 'SELECT id, glosa FROM prm_area_usuario ORDER BY glosa';
					if ($modulo_retribuciones_activo) {
						$query_areas = 'SELECT area.id, CONCAT(REPEAT("&nbsp;", IF(ISNULL(padre.id), 0, 5)), area.glosa) FROM prm_area_usuario AS area
										LEFT JOIN prm_area_usuario AS padre ON area.id_padre = padre.id
										ORDER BY  IFNULL(padre.glosa, area.glosa), padre.glosa, area.glosa ASC ';
					}
					
					echo Html::SelectQuery($sesion, $query_areas, 'id_area_usuario', $usuario->fields['id_area_usuario'] ? $usuario->fields['id_area_usuario'] : $id_area_usuario)
 					?>
				</td>
			</tr>
			
			<?php if ($modulo_retribuciones_activo) { ?>
			
				<tr>
					<td valign="top" class="texto" align="right">
						<?php echo __('Porcentaje de Retribuci�n') ?>
					</td>
					<td valign="top" class="texto" align="left">
						<?php	echo '<input type="text" size="6" value="' . $usuario->fields['porcentaje_retribucion']  . '" name="porcentaje_retribucion" />%'; ?>
					</td>
				</tr>

			<?php }	?>

			<tr><td>&nbsp;</td></tr>  <!-- spacer -->

			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('Tel�fono') ?>
				</td>
				<td valign="top" class="texto" align="left">
					<input type="text" name="telefono1" value="<?php echo $usuario->fields['telefono1'] ? $usuario->fields['telefono1'] : $telefono1 ?>" size="16"/>
				</td>
			</tr>

			<tr>
				<td valign="top" class="texto" align="right">
					<?php echo __('E-Mail') ?>
					<span class="req">*</span>
				</td>
				<td valign="top" class="texto" align="left">
					<input type="text" name="email" value="<?php echo $usuario->fields['email'] ? $usuario->fields['email'] : $email ?>" size="30"/>
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
					<input id="activo" type="checkbox" name="activo" value="1" <?php echo (($usuario->fields['activo'] || $activo || !$usuario->loaded ) ? 'checked' : '') ?> <?php if ($usuario->loaded) { ?> onClick="CheckActivo(this);" <?php } ?>/>
					<label for="activo"><?php echo __('Usuario Activo') ?></label><br/>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-size: 9px;"><?php echo __('(s�lo los usuarios activos pueden ingresar al sistema)') ?></span>
				</td>
			</tr>

			<?php if ($usuario->loaded) { ?>
			<tr>
				<td valign="top" class="texto" align="right">
					&nbsp;
				</td>
				<td valign="top" class="texto" align="left">
					<div id=divVisible <?php if ($usuario->fields['activo'] == 1) echo 'style="display:none"'; else echo 'style="display:inline"' ?>>
						<input type=checkbox name=visible value=1 <?php echo $usuario->fields['visible'] == 1 ? "checked" : "" ?> id="chkVisible" onMouseover="ddrivetip('Usuario visible en listados')" onMouseout="hideddrivetip()">
						<label for="visible"><?php echo __('Visible en Listados') ?></label>
					</div>
				</td>
			</tr>
			<?php } ?>

		</table>
	</fieldset>

	<fieldset>
		<legend><?php echo __('Permisos') ?></legend>
		<table id="chkpermisos" class="buttonset" >
		<?php echo Html::PrintCheckbox($sesion, $usuario->permisos, 'codigo_permiso', 'glosa', 'permitido'); ?>
		<?php
		if (!$usuario->loaded) {
			echo "<em>Debe agregar el usuario para poder asignarle permisos</em>";
		}
		?>
		</table>
	</fieldset>

	<fieldset>
		<legend onClick="Expandir('secretario')" style="cursor:pointer">
			<span id="secretario_img"><img src= "<?php echo Conf::ImgDir() ?>/mas.gif" border="0" ></span>
			<?php echo __('Usuario secretario de') ?>
		</legend>
		
		<table id="secretario_tabla" style="display:none">
			<tr>
				<td>

				<?php
				$where = '';
				if ($usuario->loaded) {	$where = "id_usuario <> " . $usuario->fields['id_usuario'];	}
				if (!$where) { $where = 1; }
				
				$lista_usuarios = new ListaObjetos($sesion, '', "SELECT id_usuario,CONCAT_WS(' ',nombre,apellido1,apellido2) as name FROM usuario WHERE activo=1 AND $where ORDER BY nombre");
				
				echo "<select name='usuario_secretario[]' id='usuario_secretario' multiple size=6 $tooltip_select  style='width: 200px;'>";
				
				for ($x = 0; $x < $lista_usuarios->num; $x++) {
					$us = $lista_usuarios->Get($x);
				?>
				
				<option value='<?php echo $us->fields['id_usuario'] ?>' <?php echo $usuario->LoadSecretario($us->fields['id_usuario']) ? "selected" : "" ?>><?php echo $us->fields['name'] ?></option>
					<?php
				} echo "</select>";	?>

				</td>
			</tr>
		</table>

	</fieldset>

	<fieldset>
		<legend onClick="Expandir('revisor')" style="cursor:pointer">
			<span id="revisor_img"><img src= "<?php echo Conf::ImgDir() ?>/mas.gif" border="0" ></span>
			<?php echo __('Usuario revisor de') ?>
		</legend>

		<table id="revisor_tabla" style='display:none'>
			<tr>
				<td align="right">
					<?php echo __('Usuarios disponibles') ?>:
				</td>
				<td align="left">
					<?php echo $usuario->select_no_revisados() ?>
				</td>
				<td>
					<input type="button" class="btn" value="<?php echo __('A�adir') ?>" onclick="AgregarUsuarioRevisado()"/>
				</td>
			</tr>
			<tr>
				<td align="right">
					<?php echo __('Usuarios revisados') ?>:
				</td>
				<td align="left">
					<?php echo $usuario->select_revisados(); ?>
				</td>
				<td>
					<input type="button" class="btn" value="<?php echo __('Eliminar') ?>" onclick="EliminarUsuarioRevisado()"/>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset>
		<legend onClick="Expandir('restricciones')" style="cursor:pointer">
			<span id="restricciones_img"><img src= "<?php echo Conf::ImgDir() ?>/mas.gif" border="0" ></span>
			<?php echo __('Restricciones y alertas') ?>
		</legend>
		<table id="restricciones_tabla" style='display:none'>
			<tr>
				<td width="18%"align="right">
					<label for="alerta_diaria" align="right"><?php echo __('Alerta Diaria') ?></label>
				</td>
				<td width="10%">
					<input type="checkbox" id="alerta_diaria" name="alerta_diaria" <?php echo $usuario->fields['alerta_diaria'] ? "checked" : "" ?> value=1 />
				</td>
				<td width="54%" colspan="3" align="right">
					<?php echo __('Retraso m�ximo en el ingreso de horas') ?>
				</td>
				<td width="18%">
					<input type="text" size="10" value="<?php echo $usuario->fields['retraso_max'] ?>" name="retraso_max" />
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
				<td colspan="3" align="right">
				<?php echo __('M�nimo de horas por d�a') ?>
				</td>
				<td colspan="1">
					<input type="text" size="10" value="<?php echo $usuario->fields['restriccion_diario'] ?>" name="restriccion_diario" />
				</td>
			</tr>
			<tr>
				<td width="18%" align="right">
					<label for="alerta_semanal" align="right"><?php echo __('Alerta Semanal') ?></label>
				</td>
				<td width="10%">
					<input type="checkbox" id="alerta_semanal" name="alerta_semanal" <?php echo $usuario->fields['alerta_semanal'] ? "checked" : "" ?> value=1 />
				</td>
				<td width="18%" align="right">
					<?php echo __('M�n. HH') ?>
				</td>
				<td width="18%">
					<input type="text" size=10 value="<?php echo $usuario->fields['restriccion_min'] ?>" name="restriccion_min" />
				</td>
				<td width=18% align="right">
					<?php echo __('M�x. HH') ?>
				</td>
				<td width="18%">
					<input type="text" size=10 value="<?php echo $usuario->fields['restriccion_max'] ?>" name="restriccion_max" />
				</td>
			</tr>
			<tr>
				<td colspan="5" align="right">
					<label for="alerta_revisor"><?php echo __('Resumen de horas semanales de abogados revisados') ?></label>
				</td>
				<td>
					<input type="checkbox" id="alerta_revisor" name="alerta_revisor" <?php echo $usuario->fields['alerta_revisor'] ? "checked" : "" ?> value=1 />
				</td>
			</tr>
			<tr>
				<td colspan="5" align="right">
					<label for="restriccion_mensual"><?php echo __('M�nimo mensual de horas') ?></label>
				</td>
				<td>
					<input type="text" size="10" <?php echo Html::Tooltip("Para no recibir alertas mensuales ingrese 0.") ?> value="<?php echo $usuario->fields['restriccion_mensual'] ?>" id="restriccion_mensual" name="restriccion_mensual" />
				</td>
			</tr>

			<?php
			if ($usuario->loaded) {
			
				$params_array['codigo_permiso'] = 'COB';
				$permiso_cobranza = $usuario->permisos->Find('FindPermiso', $params_array);
			
				if (!$permiso_cobranza->fields['permitido']) {
			
					echo '<tr>';
						
						echo '<td colspan="5" align="right">';
							echo '<label for="dias_ingreso_trabajo">'. __('Plazo m�ximo (en d�as) para ingreso de trabajos') . '</label>';
						echo '</td>';

						echo '<td>';
							echo '<input type="text" size="10" value="'.$usuario->fields['dias_ingreso_trabajo'].'" id="dias_ingreso_trabajo" name="dias_ingreso_trabajo" />';
						echo '</td>';

					echo '</tr>';
				}
			}
			?>

		</table>
	</fieldset>


	<!-- Vacacciones -->
	<fieldset>
		<legend onClick="Expandir('vacaciones')" style="cursor:pointer">
			<span id="vacaciones_img"><img src= "<?php echo Conf::ImgDir() ?>/mas.gif" border="0" ></span>
			<?php echo __('Vacaciones') ?>
		</legend>
		<table id="vacaciones_tabla" style='display:none;' width="400px">
			<tr>
				<td colspan="3" align="left"><?php echo __('Seleccione las fecha para ingresar el periodo de vacacciones.'); ?></td>
			</tr>
			<tr>
				<td align="right"><label for="alerta_diaria" align="right"><?php echo __('Fecha inicio') ?></label></td>
				<td colspan="2" align="left">
					<input type="text" name="vacaciones_fecha_inicio" value="" id="vacaciones_fecha_inicio" class="cls_fecha_vacaciones" size="11" maxlength="10"/>
					<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_vacaciones_fecha_inicio" style="cursor:pointer" />
				</td>
			</tr>
			<tr>
				<td align="right"><label for="alerta_diaria" align="right"><?php echo __('Fecha fin') ?></label></td>
				<td colspan="2" align="left">
					<input type="text" name="vacaciones_fecha_fin" value="" id="vacaciones_fecha_fin" class="cls_fecha_vacaciones" size="11" maxlength="10"/>
					<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_vacaciones_fecha_fin" style="cursor:pointer" />
					&nbsp;&nbsp;<input type="button" value="<?php echo __('Guardar') ?>" id="btn_guardar_vacacion" class=btn />
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
			
			<?php
			if (!empty($usuario_vacaciones)) {
				foreach ($usuario_vacaciones as $k => $vaca) {
			?>
				<tr>
					<td style="border:1px solid #ccc; text-align:center;"><?php echo $vaca['fecha_inicio']; ?></td>
					<td style="border:1px solid #ccc; text-align:center;"><?php echo $vaca['fecha_fin']; ?></td>
					<td style="border:1px solid #ccc; text-align: center"><img src= "<?php echo Conf::ImgDir() ?>/eliminar.gif" id="vacacion_<?php echo $vaca['id']; ?>" border="0" style="cursor:pointer;" class="cls_eliminar_vacacion" title="Eliminar registro" ></td>
				</tr>
			<?php
				}
			}
			?>
		</table>
	</fieldset>

	<!-- Historial -->
	<fieldset>
		
		<legend onClick="Expandir('historial')" style="cursor:pointer">
			<span id="historial_img"><img src= "<?php echo Conf::ImgDir() ?>/mas.gif" border="0" ></span>
			<?php echo __('Historial') ?>
		</legend>
		<?php $usuario->TablaHistorial($usuario_historial); ?>
	</fieldset>

	<div style="both:clear">&nbsp;</div>

	<fieldset>
		<legend><?php echo __('Guardar datos') ?></legend>
			<?php if ($sesion->usuario->fields['id_visitante'] == 0) { ?>
				<input type="submit" value="<?php echo __('Guardar') ?>" class=btn /> &nbsp;&nbsp;
			<?php } else { ?>
				<input type="button" onclick="alert('Usted se encuentra en un sistema demo, no tiene derecho de modificar datos.');" value="<?php echo __('Guardar') ?>" class=btn /> &nbsp;&nbsp;
			<?php } ?>
		
			<input type="button" value="<?php echo __('Cancelar') ?>" onclick="Cancelar(this.form);" class=btn />
		
			<?php if ($usuario->loaded && $sesion->usuario->fields['id_visitante'] == 0) { ?>
				<input type="button" onclick="Eliminar();" value='<?php echo __('Eliminar Usuario') ?>' class="btn_rojo" ></input>
			<?php } ?>
	</fieldset>

</form>

<br/><br/>

<?php
if ($usuario->loaded) {
	PasswordStrength::PrintCSS();
?>
<form  method="post" action="<?php echo $SERVER[PHP_SELF] ?>">
	<input type="hidden" name="opc" value="pass" />
	<input type="hidden" name="rut" value="<?php echo $rut ?>" />
	<input type="hidden" name="dv_rut" value="<?php echo $dv_rut ?>" />

	<fieldset>
		<legend><?php echo __('Cambio de contrase�a') ?></legend>

		<table width="100%">

			<tr>
				<td width="100" align="left">
					<strong><?php echo __('Contrase�a') ?>:</strong>
				</td>
				<td align="left">
				
				<?php
				$reset_password_by = __('Administrador');
				if ($usuario->fields['reset_password_by'] == 'U') {
					$reset_password_by = __('Usuario');
				}
				?>
				
				<span><?php echo __('Establecida por el') . " $reset_password_by" ?></span>
				<a href="#" id="change_password_link" ><?php echo __('Cambiar contrase�a') ?></a><br/>
				<div id="change_password_information" style="display:none"><?php echo __('Ingrese una nueva contrase�a para este usuario, o escoja crear una aleatoria.') ?><br/>
				<strong><?php echo __('Atenci�n') ?></strong>: <?php echo __('La contrase�a anterior ser� reemplazada e imposible de recuperar.') ?><br/><br/>

					<div style="height:35px">
						<div style="float:left">
							<input type="radio" name="genpass" value="0" id="new_pass" />
							<label for="new_pass"><?php echo __('Contrase�a nueva') ?>:</label>
							<input type="text" name="new_password" id="new_password" value="" size="16" onclick="javascript:document.getElementById('new_pass').checked='checked'"/><br/>
						</div>
				
						<?php PasswordStrength::PrintHTML(); ?>
				
					</div>
		
					<div>
						<input type="radio" name="genpass" value="1" id="rand_pass" />
						<label for="rand_pass"><?php echo __('Generar contrase�a aleatoria') ?></label>
					</div>
					
					</div>
				</td>
			</tr>
				
			<tr>
				<td width="100" align="left">
					<strong>&nbsp;</strong>
				</td>
				<td align="left">
					<?php
					$force_reset_password = $usuario->fields['force_reset_password'];
					$checked_str = ($force_reset_password && $force_reset_password == "1") ? "checked" : "";
					?>
					<label>
						<input type="checkbox" name="force_reset_password" id="force_reset_password" value="1" <?php echo $checked_str ?> />
						<span>Solicitar un cambio de contrase�a al pr&oacute;ximo inicio de sesi&oacute;n</span>
					</label>
				<td>
			</tr>
			
			<tr>
				<td align="right" colspan="2">
					<input type="submit" value="<?php echo __('Cambiar Contrase�a') ?>" size="16"/>
				</td>
			</tr>
			
		</table>
	</fieldset>
</form>

<?php
if($sesion->usuario->TienePermiso('SADM'))  echo '<a style="border:0 none;" href="'. Conf::RootDir().'/app/usuarios/index.php?switchuser='.$rut.'">Loguearse como este usuario</a>';
}

function CargarPermisos() {

	global $usuario, $pagina, $permiso, $_POST;
	$permisos_inactivos = array();
	$permisos_activos = array();
	$permisos_activados = array();
	//Obtenemos lista de permisos actual sin considerar ALL
	$lista_actual_permisos = $usuario->ListaPermisosUsuario($usuario->fields['id_usuario']);
	
	for ($i = 0; $i < $usuario->permisos->num; $i++) {
	
		$permiso = &$usuario->permisos->get($i);
	
		if ($permiso->fields['permitido'] <> $_POST[$permiso->fields['codigo_permiso']]) {
			$permisos_inactivos[] = $permiso->fields['codigo_permiso'];
			$permiso->fields['permitido'] = $_POST[$permiso->fields['codigo_permiso']];
	
			if (!$usuario->EditPermisos($permiso)) {
				$pagina->AddError($usuario->error);
			} else if (!empty($_POST[$permiso->fields['codigo_permiso']])) {
				$permisos_activados[] = $permiso->fields['codigo_permiso'];
			}

		} else {
			$permisos_activos[] = $permiso->fields['codigo_permiso'];
		}
	}

	$usuario->PermisoALL();

	//Si se agregaron permisos nuevos
	if (!empty($permisos_activados)) {
		$permisos_activados = array_merge($permisos_activados, $permisos_activos);
		$permisos_activados = implode(',', $permisos_activados);
		$permisos_activos = implode(',', $permisos_activos);
		$usuario->GuardaCambiosRelacionUsuario($usuario->fields['id_usuario'], 'permisos', $permisos_activos, $permisos_activados);

	//Si hay diferencia entre los activos vs los que se econtraban agregados es porque se quit� alguno
	} else if (count($lista_actual_permisos) <> count($permisos_activos)) {
		$permisos_activados = implode(',', $permisos_activos);
		$permisos_activos = implode(',', $lista_actual_permisos);
		$usuario->GuardaCambiosRelacionUsuario($usuario->fields['id_usuario'], 'permisos', $permisos_activos, $permisos_activados);
	}
}

?>
<script type="text/javascript">

	jQuery(document).ready(function() {

		jQuery('[name=SADM]').closest('tr').hide();
		jQuery("#chkpermisos .ui-button").live('change',function() {
			alert(jQuery(this).attr('class'));
			jQuery(this).button("option", {
				icons: { primary: this.checked ? 'ui-icon-check' : 'ui-icon-closethick' }
			});
		});

		<?php PasswordStrength::PrintJS("new_password"); ?>

		jQuery('#change_password_link').live('click', function() {
			var lang_cambiar = "<?php echo __('Cambiar contrase�a'); ?>";
		
			if (jQuery(this).text() == lang_cambiar) {
				jQuery(this).text('Cancelar');
			} else {
				jQuery(this).text(lang_cambiar);
			}

			jQuery('#change_password_information').toggle();
			jQuery('#new_password').val('');
			jQuery('#new_passa, #rand_pass').attr('checked', false);

			<?php PasswordStrength::PrintJSReset(); ?>

			return false;
		});

	});


	window.onbeforeunload = function(){
		return preguntarGuardar();
	};

	<?php if ($focus_username) { ?>
		$('username').value = '';
	<?php } ?>

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
	
	//Submit desde bot�n agregar Vacacciones
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
			if( confirm('�Est� seguro que quiere borrar las vacaciones de este usuario?') )
			{
				var ide = elemento.id;
				var tmp = ide.split('_');
				$('opc').value = 'elimina_vacacion';
				$('vacacion_id_tmp').value = tmp[1];
				$('form_usuario').submit();
			}
		});
	});

</script>

<?php
$pagina->PrintBottom();

