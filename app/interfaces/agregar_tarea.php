<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion();
$pagina = new Pagina($sesion);
$id_usuario_actual = $sesion->usuario->fields['id_usuario'];
$Tarea = new Tarea($sesion);
$Form = new Form();
if ($id_tarea) {
	$Tarea->Load($id_tarea);
	$id_usuario_registro = $Tarea->fields['usuario_registro'];
}

$conf_codigo_primario = true;
if (Conf::GetConf($sesion, 'CodigoSecundario')) {
	$conf_codigo_primario = false;
}
if ($Tarea->loaded() && (!$codigo_cliente || !$codigo_cliente_secundario)) {
	$codigo_cliente = $Tarea->fields['codigo_cliente'];
	$codigo_asunto = $Tarea->fields['codigo_asunto'];
	if (!$conf_codigo_primario) {
		$cliente = new Cliente($sesion);
		$asunto = new Asunto($sesion);
		$codigo_cliente_secundario = $cliente->CodigoSecundarioACodigo($codigo_cliente);
		$codigo_asunto_secundario = $asunto->CodigoSecundarioACodigo($codigo_asunto);
	}
}

if ($opcion == "guardar") {
	$Tarea->Edit("fecha_entrega", Utiles::fecha2sql($fecha));
	$Tarea->Edit("nombre", $nombre);
	$Tarea->Edit("detalle", $detalle);
	$Tarea->Edit("prioridad", $prioridad);
	$Tarea->Edit("alerta", $alerta);

	//Revisa el Conf si esta permitido y la funci�n existe02-09-2010 16:54:17
	if ((Conf::GetConf($sesion, 'TipoIngresoHoras') == 'decimal')) {
		$Tarea->Edit("tiempo_estimado", UtilesApp::Decimal2Time($duracion));
	} else {
		$Tarea->Edit("tiempo_estimado", $duracion);
	}
	if (!$id_usuario_registro) {
		$Tarea->Edit("usuario_registro", $id_usuario_actual);
	}
	if ($codigo_cliente_secundario != '') {
		$cliente = new Cliente($sesion);
		$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
	}
	if ($codigo_asunto_secundario != '') {
		$asunto = new Asunto($sesion);
		$codigo_asunto = $asunto->CodigoSecundarioACodigo($codigo_asunto_secundario);
	}
	$Tarea->Edit("codigo_cliente", $codigo_cliente);
	$Tarea->Edit("codigo_asunto", $codigo_asunto);

	if ($id_usuario_encargado) {
		$Tarea->Edit("usuario_encargado", $id_usuario_encargado);
	} else {
		$Tarea->Edit("usuario_encargado", 'NULL');
	}
	if ($id_usuario_revisor) {
		$Tarea->Edit("usuario_revisor", $id_usuario_revisor);
	} else {
		$Tarea->Edit("usuario_revisor", 'NULL');
	}
	if ($id_usuario_generador) {
		$Tarea->Edit("usuario_generador", $id_usuario_generador);
	} else {
		$Tarea->Edit("usuario_generador", 'NULL');
	}
	if ($estado == 'inicial') {
		if ($Tarea->fields['usuario_encargado']) {
			$Tarea->Edit("estado", "Asignada");
		} else {
			$Tarea->Edit("estado", "Por Asignar");
		}
	} else {
		$Tarea->Edit("estado", $estado);
		foreach ($Tarea->estados as $k => $es) {
			if ($estado == $es) {
				$Tarea->Edit("orden_estado", $k + 1);
			}
		}
	}
	if ($Tarea->Write()) {
		$pagina->AddInfo(__('Tarea') . ' ' . __('guardada con exito'));
		$js_refrescar = "window.opener.Refrescar('');";
	}
}


$usuario_generador = new UsuarioExt($sesion);
if ($id_usuario_generador) {
	$usuario_generador->LoadId($id_usuario_generador);
} else {
	$usuario_generador->LoadId($id_usuario_actual);
}

$dias_alerta = array(
	1 => __('1 d�a antes'),
	2 => __('2 d�as antes'),
	5 => __('5 d�as antes'),
	10 => __('10 d�as antes')
);

$txt_pagina = $Tarea->loaded() ? __('Edici�n de Tarea') . ' :: ' . $Tarea->fields['nombre'] : __('Ingreso de Tarea');
$req = '<span style="color:#FF0000; font-size:10px">*</span>';

$pagina->titulo = $txt_pagina;
$pagina->PrintTop($popup);
?>

<script type="text/javascript">
	var campo_cliente = '<?= $conf_codigo_primario ? 'codigo_cliente' : 'codigo_cliente_secundario'; ?>';
	var campo_asunto = '<?= $conf_codigo_primario ? 'codigo_asunto' : 'codigo_asunto_secundario'; ?>';
<?= $js_refrescar; ?>
	function CambiarEncargado() {
		if ($('id_usuario_encargado').selectedIndex == 0) {
			if ($('estado').selectedIndex == 1)
				$('estado').selectedIndex = 0;
		} else {
			if ($('estado').selectedIndex == 0)
				$('estado').selectedIndex = 1;
		}
	}
	function ResizeBitacora(size) {
		currentfr = document.getElementById('bitacora');
		currentfr.height = size + 'px'; // currentfr.Document.body.scrollHeight;
	}
	function ActualizarTiempoIngresado() {
		var url = "ajax_tareas.php?accion=refrescar_tiempo_ingresado";
		url += "&id_tarea=<?= $id_tarea; ?>";
		new Ajax.Request(url, {asynchronous: true, parameters: '', onComplete: CambiarTiempo});
	}
	function CambiarTiempo(xmlHttpRequest, responseHeader) {
		var response = xmlHttpRequest.responseText;
		if (response) {
			if (response.indexOf('head') != -1) {
				alert('<?= __('Sesi�n Caducada'); ?>');
				top.location.href = '<?= Conf::Host() ?>';
			}
			var tiempo = response;
			$('tiempo_ingresado').value = tiempo;
		}
	}
	function ActualizarEstado(indice) {
		$('estado').selectedIndex = indice;
	}
	function Validar() {
		if (!jQuery('#' + campo_cliente).val()) {
			alert('<?= __('Debe seleccionar un cliente'); ?>');
			jQuery('#' + campo_cliente).focus();
			return false;
		}
		if (!jQuery('#' + campo_cliente).val()) {
			alert('<?= __('Ud. debe seleccionar un') . ' ' . __('asunto'); ?>');
			jQuery('#' + campo_cliente).focus();
			return false;
		}
		if (jQuery('#nombre').val() == '') {
			alert('<?= __('Debe ingresar un nombre para la Tarea'); ?>');
			jQuery('#nombre').focus();
			return false;
		}
		if (jQuery('#fecha').val() == '') {
			if (confirm('<?= __('La tarea se ingresar� sin Fecha de Entrega'); ?>')) {
				return true;
			}
			jQuery('#fecha').focus();
			return false;
		}
		if (jQuery('#id_usuario_revisor').val() == '') {
			alert('<?= __('Debe ingresar un usuario revisor para la Tarea'); ?>');
			jQuery('#id_usuario_revisor').focus();
			return false;
		}
		return true;
	}
	function CambiaDuracion(form, input) {
		if (document.getElementById('duracion_cobrada') && input == 'duracion')
			form.duracion_cobrada.value = form.duracion.value;
	}
	jQuery(document).ready(function () {
		if (document.getElementById('img_fecha')) {
			Calendar.setup(
				{
					inputField: "fecha",
					ifFormat: "%d-%m-%Y",
					button: "img_fecha"
				}
			);
		}
	});
</script>
<?php
if ($Tarea->loaded()) {
	$TareaComentario = new TareaComentario($sesion);
	$TareaComentario->setTarea($Tarea->fields['id_tarea']);
	$TareaComentario->setUsuario($id_usuario_actual);
	$TareaComentario->setAsunto($Tarea->fields['codigo_asunto']);
	echo $TareaComentario->js_TareaComentario();
	echo $TareaComentario->css_TareaComentario();
}
echo(Autocompletador::CSS());
?>
<form method=post action="" id="form_tareas" autocomplete='off'>
	<input type='hidden' name='opcion' value="guardar" />
	<input type='hidden' name="gIsMouseDown" id="gIsMouseDown" value='false' />
	<input type='hidden' name="max_hora" id="max_hora" value='999999999' />
	<input type='hidden' name="gRepeatTimeInMS" id="gRepeatTimeInMS" value='200' />
	<input type='hidden' name='id_tarea' value="<?= $Tarea->fields['id_tarea'] ?>" />
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td valign="top" align="left" class="titulo" bgcolor="<?php Conf::GetConf($sesion, 'ColorTituloPagina') ?>">
				<?= $txt_pagina; ?>
			</td>
		</tr>
	</table>
	<br>
	<table width='100%'>
		<tr>
			<td align='right'>
				<?= __('Cliente'); ?>
			</td>
			<td align='left' colspan='3' style='white-space: nowrap;'>
				<?php
				if (Conf::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador') {
					if (Conf::GetConf($sesion, 'CodigoSecundario')) {
						echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario, '', "320");
					} else {
						echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente, '', '', "320");
					}
				} else {
					if (Conf::GetConf($sesion, 'CodigoSecundario')) {
						echo InputId::Imprimir($sesion, "cliente", "codigo_cliente_secundario", "glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario, "", "CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320, $codigo_asunto_secundario);
					} else {
						echo InputId::Imprimir($sesion, "cliente", "codigo_cliente", "glosa_cliente", "codigo_cliente", $codigo_cliente, "", "CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320, $codigo_asunto);
					}
				}
				?> <?= $req; ?>
			</td>
		</tr>
		<tr>
			<td align='right'>
				<?= __('Asunto'); ?>
			</td>
			<td align='left' colspan='3' style='white-space: nowrap;'>
				<?php
				if (Conf::GetConf($sesion, 'CodigoSecundario')) {
					echo InputId::Imprimir($sesion, "asunto", "codigo_asunto_secundario", "glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario, "", "CargarSelectCliente(this.value);", 320, $codigo_cliente_secundario);
				} else {
					echo InputId::Imprimir($sesion, "asunto", "codigo_asunto", "glosa_asunto", "codigo_asunto", $codigo_asunto, "", "CargarSelectCliente(this.value);", 320, $codigo_cliente);
				}
				?>
				<?= $req; ?>
			</td>
		</tr>
		<tr>
			<td align='right'>
				<?= __('Nombre'); ?>
			</td>
			<td align='left' colspan='3' style='white-space: nowrap;'>
				<input name='nombre' id='nombre' size='40' value="<?= $Tarea->fields['nombre'] ? $Tarea->fields['nombre'] : '' ?>" /> <?= $req; ?>
			</td>
		</tr>
		<tr id='descripcion_tarea'>
			<td align='right'>
				<?= __('Detalle'); ?>
			</td>
			<td align='left' colspan='3'>
				<textarea id='detalle' name='detalle' cols="45" rows="3"><?= $Tarea->fields['detalle']; ?></textarea>
			</td>
		</tr>
		<tr>
			<td align='right'>
				<?= __('Fecha de Entrega'); ?>
			</td>
			<td align='left' colspan='1'>
				<?= \TTB\Html::PrintCalendar('fecha', $Tarea->fields['fecha_entrega'] ? Utiles::sql2date($Tarea->fields['fecha_entrega']) : $fecha); ?>
			</td>
			<td align='right'>
				<?= __('Prioridad'); ?>
			</td>
			<td align='left'>
				<select name='prioridad' id='prioridad' title="10 mayor prioridad, 1 menor prioridad" >
					<?php for ($i = 1; $i < 11; $i++) { ?>
						<option value="<?= $i ?>"
						<?php
						if ($Tarea->fields['prioridad']) {
							if ($Tarea->fields['prioridad'] == $i) {
								echo 'selected';
							}
						} else
						if ($i == 5) {
							echo 'selected';
						}
						?>
										>
						<?= $i ?>
						</option>
						<?php }?>
					</select>
				</td>
			<tr>
				<td align='right'>
					<?= __('Alerta'); ?>
				</td>
				<td align='left'>
					<?= $Form->select('alerta', $dias_alerta, $Tarea->fields['alerta'], array('empty' => __('Sin Alerta'))); ?>
				</td>
			</tr>
			</tr>
			<tr>
				<td colspan='4'>
					<br />
				</td>
			</tr>
			<tr>
				<td align='right'>
					<?= __('Usuario Responsable'); ?>
				</td>
				<td align='left' width='20%'>
					<?php $id_usuario_encargado = empty($id_tarea) ? $id_usuario_actual : $Tarea->fields['usuario_encargado']; ?>
					<?= $Form->select('id_usuario_encargado', $usuario_generador->ListarActivos(null, false, $id_usuario_encargado), $id_usuario_encargado, array('empty' => __('Ninguno'), 'onclick' => 'CambiarEncargado()')); ?>
				</td>
				<td align='right' width='24%'>
					<?= __('Estado'); ?>
				</td>
				<td align='left'>
					<?= $Form->select('estado', array_combine($Tarea->estados, $Tarea->estados), empty($id_tarea) ? 'Asignada' : $Tarea->fields['estado'], array('empty' => __('Ninguno'))); ?>
				</td>
			</tr>
			<tr>
				<td align='right'>
					<?= __('Usuario Revisor'); ?>
				</td>
				<td align='left' style='white-space: nowrap;'>
					<?=
					$Form->select('id_usuario_revisor', $usuario_generador->ListarActivos(null, false, $Tarea->fields['usuario_revisor']), $Tarea->fields['usuario_revisor']);
					?> <?= $req; ?>
				</td>
				<td align='right'>
					<?= __('Duraci�n Estimada'); ?>
				</td>
				<td align='left'>
					<?php
					$input_estimado = Html::PrintTime("duracion", $Tarea->fields['tiempo_estimado'], "onchange='CambiaDuracion(this.form ,\"duracion\");' ", true);
					$input_estimado = str_replace('size="6"', 'size="7"', $input_estimado);
					if (Conf::GetConf($sesion, 'TipoIngresoHoras') == 'selector') {
						$tiempo_estimado = '00:00:00';
						if ($Tarea->fields['tiempo_estimado']) {
							$tiempo_estimado = $Tarea->fields['tiempo_estimado'];
							$input_estimado = SelectorHoras::PrintTimeSelector($sesion, "duracion", $tiempo_estimado, '');
						}
					} else if (Conf::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
						$tiempo_estimado = '';
						if ($Tarea->fields['tiempo_estimado']) {
							$tiempo_estimado = UtilesApp::Time2Decimal($Tarea->fields['tiempo_estimado']);
						}
						$input_estimado = '<input type="text" name="duracion" value="" id="duracion" size="7" maxlength=4   onchange="CambiaDuracion(this.form,\"duracion\");"/>';
					}
					echo $input_estimado;
					?>
				</td>
			</tr>
			<tr>
				<td align='right' >
					<?= __('Usuario Mandante'); ?>
				</td>
				<td align='left'>
					<?= $Form->select('id_usuario_generador', $usuario_generador->ListarActivos(null, false, $Tarea->fields['usuario_generador']), $Tarea->fields['usuario_generador'], array('empty' => __('Ninguno'))); ?>
				</td>
				<td align='right'>
					<?= __('Duraci�n Ingresada'); ?>
				</td>
				<td align='left'>
					<input readonly='readonly' value='<?= $Tarea->getTiempoIngresado(); ?>' id='tiempo_ingresado' size='7' />
				</td>
			</tr>
			<tr>
			</tr>
		<?php if ($Tarea->loaded()) { ?>
				<tr>
					<td align='right'>
					</td>
					<td align='left' colspan='3'>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td>
					<br />
				</td>
				<td align='right' colspan='3'>
					<?php if ($Tarea->loaded()) { ?>
						<span style="font-size:10px;">
							<?= __('Tarea ingresada el') . '&nbsp;' . Utiles::sql2fecha($Tarea->fields['fecha_creacion']); ?>
						</span>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td align='center' colspan='4'>
					<?= $Form->submit(__('Guardar')); ?>
					<?= $Form->button(__('Cerrar'), array('id' => 'cerrar')); ?>
				</td>
			</tr>
		</table>
		<?php if (!$Tarea->loaded()) { ?>
			<input type="hidden" name="estado" value="inicial" />
		<?php } ?>
		<br />
	</form>
		<?php if ($Tarea->loaded()) { ?>
		<hr />
		<div id="comentarios" align='center'>
		<?php
		$url_iframe = 'tareas_comentarios.php?id_tarea=' . $Tarea->fields['id_tarea'];
		$alto_iframe = '100';
		?>
			<iframe name='bitacora' id='bitacora' src='<?= $url_iframe; ?>' frameborder='0' width='95%' height='<?= $alto_iframe ?>px'>
			</iframe>
		</div>
		<?php } ?>

	<script type="text/javascript">
		(function ($) {
			$('#cerrar').on('click', function (event) {
				event.preventDefault();
				window.close();
			});
			$('#form_tareas').on('submit', function () {
				return Validar();
			});
		})(jQuery);

	</script>
	<?php
	if (Conf::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador') {
		echo Autocompletador::Javascript($sesion);
	}
	echo InputId::Javascript($sesion);
	echo SelectorHoras::Javascript();
	$pagina->PrintBottom($popup);
