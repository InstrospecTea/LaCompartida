<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/classes/Autocompletador.php';
require_once Conf::ServerDir() . '/classes/Asunto.php';
require_once Conf::ServerDir() . '/classes/Contrato.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/UsuarioExt.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/SolicitudAdelanto.php';

$Sesion = new Sesion(array('COB'));
$Pagina = new Pagina($Sesion);

$SolicitudAdelanto = new SolicitudAdelanto($Sesion);

if ($_POST['opcion'] == 'guardar') {
	$SolicitudAdelanto->Fill($_REQUEST, true);
	$SolicitudAdelanto->Edit('id_usuario_ingreso', $Sesion->usuario->fields['id_usuario']);

	if ($SolicitudAdelanto->Write()) {
		$Pagina->AddInfo(__('Solicitud de Adelanto guardada con éxito'));

		if ($_REQUEST['notificar_solicitante']) {
			$SolicitudAdelanto->NotificarSolicitante();
		}
	} else {
		$Pagina->AddError(__('Por favor corrija lo siguiente: ') . implode(', ', $SolicitudAdelanto->error));
	}
} else {
	if ($_REQUEST['id_solicitud_adelanto'] != '') {
		$SolicitudAdelanto->Load($_REQUEST['id_solicitud_adelanto']);
		unset($_REQUEST['id_solicitud_adelanto']);
	}

	$SolicitudAdelanto->Fill($_REQUEST);
}


$popup = $_REQUEST['popup'];

$Pagina->titulo = __('Solicitud de Adelanto');

if ($SolicitudAdelanto->Loaded()) {
	$Pagina->titulo = __('Edición') . ' de ' . $Pagina->titulo . ' N° ' . $SolicitudAdelanto->fields['id_solicitud_adelanto'];

	if (!empty($SolicitudAdelanto->fields['id_contrato'])) {
		$Asunto = new Asunto($Sesion);
		$Asunto->LoadByContrato($SolicitudAdelanto->fields['id_contrato']);
		$codigo_asunto = $Asunto->fields['codigo_asunto'];
	}
}

$Pagina->PrintTop($popup);

echo $msg;
?>
<br />
<table width="90%" id="txt_pagina">
	<tr>
		<td align="left"><strong><?php echo $Pagina->titulo; ?></strong></td>
	</tr>
</table>
<br />
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<form method="POST" action="#" id="form_agregar_solicitud_adelanto" name="form_agregar_solicitud_adelanto" autocomplete="OFF">
	<input type="hidden" name="opcion" value="guardar" />
	<input type="hidden" name="notificar_solicitante" value="0" />
	<input type="hidden" name="id_solicitud_adelanto" value="<?php echo $SolicitudAdelanto->fields['id_solicitud_adelanto']; ?>" />
	<table id="tabla_informacion" style="border: 1px solid black;" width='90%'>
		<tr>
			<td align="right">
				<label for="fecha"><?php echo __('Fecha') ?></label>
			</td>
			<td align="left">
				<input type="text" name="fecha" value="<?php echo $documento->fields['fecha'] ? Utiles::sql2date($documento->fields['fecha']) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
				<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
			</td>
		</tr>
		<tr>
			<td align="right" width="30%">
				<label for="codigo_cliente"><?php echo __('Nombre Cliente'); ?></label>
			</td>
			<td colspan="3" align="left" id="td_selector_cliente">
				<?php echo UtilesApp::CampoCliente($Sesion, $SolicitudAdelanto->fields['codigo_cliente'], $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
			</td>
		</tr>
		<tr>
			<td align="right" width="30%">
				<label for="codigo_cliente"><?php echo __('Asunto'); ?></label>
			</td>
			<td colspan="3" align="left" id="td_selector_contrato">
				<?php
				$Contrato = new Contrato($Sesion);
				echo $Contrato->ListaSelector($SolicitudAdelanto->fields['codigo_cliente'], '', $SolicitudAdelanto->fields['id_contrato']);
				?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="monto"><?php echo __('Monto'); ?></label>
			</td>
			<td align="left">
				<input name="monto" id="monto" size="10" value="<?php echo $SolicitudAdelanto->fields['monto']; ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label for="id_moneda"><?php echo __('Moneda'); ?></label>
				<?php echo Html::SelectArray(Moneda::GetMonedas($Sesion), "id_moneda", $SolicitudAdelanto->fields['id_moneda'], 'id="id_moneda"', '', "80px"); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="descripcion"><?php echo __('Descripción'); ?></label>
			</td>
			<td align="left">
				<textarea name="descripcion" id="descripcion" cols="45" rows="3"><?php echo $SolicitudAdelanto->fields['descripcion']; ?></textarea>
			</td>
		</tr>
		<tr>
			<td align="right" width="30%">
				<label for="id_usuario_solicitante"><?php echo __('Usuario solicitante'); ?></label>
			</td>
			<td align="left">
				<?php
				$usuario_solicitante = empty($SolicitudAdelanto->fields['id_usuario_solicitante']) ?
					$Sesion->usuario->fields['id_usuario'] : $SolicitudAdelanto->fields['id_usuario_solicitante'];
				echo Html::SelectArray(UsuarioExt::GetUsuariosActivos($Sesion), "id_usuario_solicitante", $usuario_solicitante, 'id="id_usuario_solicitante"', '', '200px');
				?>
			</td>
		</tr>
		<?php if ($SolicitudAdelanto->Loaded()) { ?>
		<tr>
			<td align="right" width="30%">
				<label for="estado"><?php echo __('Estado'); ?></label>
			</td>
			<td align="left">
				<?php echo Html::SelectArray(SolicitudAdelanto::GetEstados(), "estado", $SolicitudAdelanto->fields['estado'], 'id="estado"', '', '100px'); ?>
			</td>
		</tr>
		<tr>
			<td align="right" width="30%">
				<label for="id_template"><?php echo __('Formato Carta'); ?></label>
			</td>
			<td align="left">
				<?php echo Html::SelectArray(Template::GetAll($Sesion, 'SOLICITUD_ADELANTO'), "id_template", $SolicitudAdelanto->fields['id_template'], 'id="id_template"', '', 'width="200px"'); ?>
			</td>
		</tr>
		<?php } ?>
	</table>
	<br />
	<table style="border: 0px solid black;" width="90%">
		<tr>
			<td align=left>
				<input type="submit" class="btn" value="<?php echo __('Guardar'); ?>" onclick="return Validar(this.form);" />
				<input type="button" class="btn" value="<?php echo __('Cerrar'); ?>" onclick="return Cerrar();" />
			</td>
			<td>
				<?php if ($SolicitudAdelanto->Loaded()) { ?>
				<a href="ingresar_documento_pago.php?popup=1&adelanto=1&id_solicitud_adelanto=<?php echo $SolicitudAdelanto->fields['id_solicitud_adelanto']; ?>">Ingresar un adelanto para esta solicitud</a>
				<?php } ?>
			</td>
		</tr>
	</table>
</form>
<script type="text/javascript">
	function Validar(form)
	{
		// alert(monto);
		console.log(form);
        if(isNaN(form.monto.value) || form.monto.value == '')
		{
			console.log(form.monto);
			alert('<?php echo __('Debe ingresar un monto') ?>');
			$('monto').focus();
			return false;
		}

<?php
if (UtilesApp::GetConf($Sesion, 'CodigoSecundario')) {
	if (UtilesApp::GetConf($Sesion, 'TipoSelectCliente') == 'autocompletador') {
		?>
				var cod_cli_seg = document.getElementById('codigo_cliente_secundario');
	<?php } else { ?>
				var cod_cli_seg = document.getElementById('campo_codigo_cliente_secundario');
	<?php } ?>
			if (cod_cli_seg == '-1' || cod_cli_seg == "") {
				alert('<?php echo __('Debe ingresar un cliente'); ?>');
				return false;
			}
	<?php
} else {
	if (UtilesApp::GetConf($Sesion, 'TipoSelectCliente') == 'autocompletador') {
		?>
				var cod_cli = document.getElementById('codigo_cliente');
	<?php } else { ?>
				var cod_cli = document.getElementById('campo_codigo_cliente');
	<?php } ?>
			if (cod_cli == '-1' || cod_cli == "") {
				alert('<?php echo __('Debe ingresar un cliente') ?>');
				return false;
			}
<?php } ?>

		if (form.descripcion.value == "") {
			alert('<?php echo __('Debe ingresar una descripción'); ?>');
			form.descripcion.focus();
			return false;
		}

		var estado_anterior = '<?php echo $SolicitudAdelanto->fields['estado']; ?>';

		if (form.estado.value == "DEPOSITADO" && estado_anterior != form.estado.value) {
			if (confirm('¿Desea notificar al solicitante la disponibilidad del adelanto?')) {
				form.notificar_solicitante.value = true;
			}
		}
	}

	Calendar.setup({ inputField	: "fecha", ifFormat : "%d-%m-%Y", button : "img_fecha" });

	var valor_anterior_codigo;
	var campo_cliente;

	jQuery(document).ready(function () {
		// Cargar contratos on select
		campo_cliente = jQuery('input[name^="codigo_cliente"], select[name^="codigo_cliente"]');
		campo_cliente.change(ActualizarContratos);
		valor_anterior_codigo = campo_cliente.val();
		window.setInterval(ComprobarCodigos, 500, campo_cliente.val());
	});

	function ActualizarContratos() {
		url = root_dir + '/app/ajax.php?accion=cargar_contratos&codigo_cliente=' + jQuery(this).val();
		jQuery.ajax({
			url: url,
			success: function (data) {
				var valor = jQuery('#id_contrato').val();
				jQuery('#td_selector_contrato').html(data);
				jQuery('#id_contrato').val(valor);
			}
		});
	}

	function ComprobarCodigos(valor_nuevo) {
		if (valor_anterior_codigo != valor_nuevo) {
			campo_cliente.change();
			valor_anterior_codigo = valor_nuevo;
		}
	}
</script>
<?php if ($SolicitudAdelanto->Loaded()) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function() {

			// Listado de adelantos relacionados con la solicitud
			jQuery("#listado_adelantos").load('ajax/lista_adelantos_ajax.php?ajax=1&id_solicitud_adelanto=<?php echo $SolicitudAdelanto->fields['id_solicitud_adelanto']; ?>', function() {
				jQuery('.pagination ul li a').each(function() {
					valrel = jQuery(this).attr('href').replace("javascript:PrintLinkPage('",'').replace("');", '');
					jQuery(this).attr({'href':'#', 'class':'printlinkpage','rel':valrel});
				});
			});

			jQuery('.printlinkpage').live('click',function() {
				multi = jQuery("input[name=x_pag]").val();
				//alert(multi);
				valrel = multi * (jQuery(this).attr('rel') - 1);
				jQuery('#xdesde').val(valrel);
				jQuery.post('ajax/lista_adelantos_ajax.php?ajax=1&id_solicitud_adelanto=<?php echo $SolicitudAdelanto->fields['id_solicitud_adelanto']; ?>', { xdesde: valrel },
				function(data) {
					jQuery("#listado_adelantos").html(data);

					jQuery('.pagination ul li a').each(function() {
						valrel = jQuery(this).attr('href').replace("javascript:PrintLinkPage('",'').replace("');", '');
						jQuery(this).attr({'href':'#', 'class':'printlinkpage','rel':valrel});
					});
				});
				jQuery("#listado_adelantos").html(DivLoading);
			});
		});
	</script>
	<div id="listado_adelantos"></div>
<?php } ?>
<?php
$Pagina->PrintBottom($popup);