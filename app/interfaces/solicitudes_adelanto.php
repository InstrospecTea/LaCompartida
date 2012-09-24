<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/classes/Autocompletador.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/SolicitudAdelanto.php';

$Sesion = new Sesion(array('COB','PRO'));
$Pagina = new Pagina($Sesion);

$SolicitudAdelanto = new SolicitudAdelanto($Sesion);
$SolicitudAdelanto->Fill($_REQUEST);

switch ($_REQUEST['accion']) {
	case 'eliminar':
		if ($SolicitudAdelanto->Delete()) {
			$Pagina->AddInfo(__('Solicitud de adelanto').' '.__('eliminada con éxito'));
		} else {
			$Pagina->AddError($SolicitudAdelanto->error);
		}
		break;
		
	case 'descargar':
		$SolicitudAdelanto->DownloadWord();
		break;
	case 'xls':
		$SolicitudAdelanto->DownloadExcel();
		break;
}

$Pagina->titulo = __('Solicitudes de Adelanto');
$Pagina->PrintTop();
?>
<table width="90%">
	<tr>
		<td>
			<form method="POST" name="form_solicitudes_adelanto" action="solicitudes_adelanto.php" id="form_solicitudes_adelanto">
				<input  id="xdesde"  name="xdesde" type="hidden" value="">
				<input type="hidden" name="accion" id="accion" value="buscar">
				<!-- Calendario DIV -->
				<div id="calendar-container" style="width:221px; position:absolute; display:none;">
					<div class="floating" id="calendar"></div>
				</div>
				<!-- Fin calendario DIV -->
				<fieldset class="tb_base" style="width: 100%;border: 1px solid #BDBDBD;">
					<legend><?php echo __('Filtros') ?></legend>
					<table style="border: 0px solid black" width='720px'>
						<tr>
							<td align="right">
								<label for="id_solicitud_adelanto"><?php echo __('N° Solicitud'); ?></label>
							</td>
							<td align="left">
								<input type="text" size="6" name="id_solicitud_adelanto" id="id_solicitud_adelanto" value="<?php echo $SolicitudAdelanto->fields['id_solicitud_adelanto']; ?>" />
							</td>
						</tr>
						<tr>
							<td align="right" width="30%">
								<label for="codigo_cliente"><?php echo __('Nombre Cliente'); ?></label>
							</td>
							<td colspan="3" align="left">
								<?php echo UtilesApp::CampoCliente($Sesion, $SolicitudAdelanto->fields['codigo_cliente'], $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
							</td>
						</tr>
						<tr>
							<td align="right" width="30%">
								<label for="id_contrato"><?php echo __('Asuntos'); ?></label>
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
								<label for="fecha_desde"><?php echo __('Fecha Desde') ?></label>
							</td>
							<td align="left">
								<input type="text" name="fecha_desde" value="<?php echo $SolicitudAdelanto->extra_fields['fecha_desde']; ?>" id="fecha_desde" size="11" maxlength="10" />
								<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_desde" style="cursor:pointer" />
							</td>
							<td align="left" colspan="2">
								<label for="fecha_hasta"><?php echo __('Fecha Hasta') ?></label>
								<input type="text" name="fecha_hasta" value="<?php echo $SolicitudAdelanto->extra_fields['fecha_hasta']; ?>" id="fecha_hasta" size="11" maxlength="10" />
								<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_hasta" style="cursor:pointer" />
							</td>
						</tr>
						<tr>
							<td align="right">
								<label for="estado"><?php echo __('Estado') ?></label>
							</td>
							<td colspan="2" align="left">
								<?php echo Html::SelectArray(SolicitudAdelanto::GetEstados(), "estado", $SolicitudAdelanto->fields['estado'], 'id="estado"', __('Todos'), ''); ?>
							</td>
						</tr>
						<tr>
							<td></td>
							<td colspan=2 align=left>
								<input name="boton_buscar" id="boton_buscar" type="submit" value="<?php echo __('Buscar') ?>" class="btn"  onclick="javascript:this.form.accion.value = 'buscar'"/>
								<input name="boton_excel" id="boton_excel" type="submit" value="<?php echo __('Descargar Excel') ?>" class="btn"  onclick="javascript:this.form.accion.value = 'xls'"/>
								<?php // echo ReporteExcel::DrawDownloadButton($Sesion, 'SOLICITUDES_ADELANTO'); ?>
							</td>
							<td width='40%' align="right">
								<img src="<?php echo Conf::ImgDir() ?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('solicitud_adelanto')" title="Agregar Solicitud Adelanto"><?php echo __('Agregar') ?> <?php echo __('solicitud adelanto') ?></a>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</td>
	</tr>
</table>
<?php
if ($_REQUEST['accion'] == 'buscar') {

	if ($orden == "") {
		$orden = "glosa_cliente";
	}
	
	$x_pag = 25;

	$b = new Buscador($Sesion, $SolicitudAdelanto->SearchQuery(), "SolicitudAdelanto", $desde, $x_pag, $orden);
	$b->AgregarEncabezado("id_solicitud_adelanto", __('N°'), "align=center");
	$b->AgregarEncabezado("fecha", __('Fecha Solicitud'), "align=center");
	$b->AgregarEncabezado("glosa_cliente", __('Cliente'), "align=left");
	$b->AgregarEncabezado("descripcion", __('Descripción'), "align=left");
	$b->AgregarFuncion(__('Monto'), 'FormatoMonto', "align=right");
	$b->AgregarEncabezado("estado", __('Estado'), "align=left");
	$b->AgregarEncabezado("username", __('Solicitante'), "align=left");
	$b->AgregarFuncion(__('Opciones'), 'Opciones', "align=right");
	$b->color_mouse_over = "#bcff5c";
	$b->Imprimir();
}

function FormatoMonto(&$fila) {
	global $Sesion;
	$monto_solicitado = UtilesApp::PrintFormatoMoneda($Sesion, $fila->fields['monto'], $fila->fields['id_moneda']);
	$monto_adelantos = UtilesApp::PrintFormatoMoneda($Sesion, $fila->fields['monto_adelantos'], $fila->fields['id_moneda']);
	$saldo_adelantos = UtilesApp::PrintFormatoMoneda($Sesion, $fila->fields['saldo_adelantos'], $fila->fields['id_moneda']);
	$cantidad_adelantos = $fila->fields['cantidad_adelantos'];
	
	if ($cantidad_adelantos > 0) {
		$s = ($cantidad_adelantos > 1) ? 's' : '';
		$title = "$cantidad_adelantos adelanto$s por $monto_adelantos (saldo: $saldo_adelantos)";
	} else {
		$title = "Sin adelantos";
	}
	
	return "<span title=\"$title\">$monto_solicitado</span>";
}

function Opciones(& $fila) {
	global $Sesion;

	$boton_descargar = '<a href="solicitudes_adelanto.php?accion=descargar&id_solicitud_adelanto=' . $fila->fields['id_solicitud_adelanto'] . '" title="Descargar Solicitud">'
		. '<img src="' . Conf::ImgDir() . '/doc.gif" border="0" alt="Descargar Solicitud" /></a>';
	
	$boton_editar = '<a href="javascript:void(0);" onclick="AgregarNuevo(\'solicitud_adelanto\', ' . $fila->fields['id_solicitud_adelanto'] . ');" title="Editar Solicitud">'
		. '<img src="' . Conf::ImgDir() . '/editar_on.gif" border="0" alt="Editar Solicitud" /></a>';
	
	$boton_eliminar = '<a href="javascript:void(0);" onclick="if (confirm(\'¿'. __('Está seguro de eliminar la') . ' ' . __('solicitud de adelanto') . '?\')) EliminaSolicitudAdelanto(' . $fila->fields['id_solicitud_adelanto'] . ');">'
		. '<img src="' . Conf::ImgDir() . '/cruz_roja_nuevo.gif" border="0" alt="Eliminar" /></a>';
	
	return "$boton_descargar $boton_editar $boton_eliminar";
}
?>
<script type="text/javascript">
	function AgregarNuevo(tipo, id)
	{
		var url_extension = '';
		if (!isNaN(id)) {
			url_extension = '&id_solicitud_adelanto=' + id;
		} else {
<?php if (UtilesApp::GetConf($Sesion, 'CodigoSecundario')) { ?>
			var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
			var url_extension = "&codigo_cliente_secundario=" + codigo_cliente_secundario;
<?php } else { ?>
			var codigo_cliente = $('codigo_cliente').value;
			var url_extension = "&codigo_cliente=" + codigo_cliente;
<?php } ?>
			url_extension += '&id_contrato=' + $('id_contrato').value;
		}
		if (tipo == 'solicitud_adelanto') {
			var urlo = "agregar_solicitud_adelanto.php?popup=1" + url_extension;
			return	nuovaFinestra('Agregar_SolicitudAdelanto', 720, 500, urlo, 'top=100, left=125');
		}
	}
	
	function EliminaSolicitudAdelanto(id_solicitud)
	{
		self.location.href = "solicitudes_adelanto.php?id_solicitud_adelanto="+id_solicitud+"&accion=eliminar&buscar=1&desde=<?php echo ($desde) ? $desde : '0'?>";
		return true;
	}
	
	Calendar.setup({ inputField	: "fecha_desde", ifFormat : "%d-%m-%Y", button : "img_fecha_desde" });
	Calendar.setup({ inputField	: "fecha_hasta", ifFormat : "%d-%m-%Y", button : "img_fecha_hasta" });
	
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
				jQuery('#td_selector_contrato').html(data);
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
<?php

$Pagina->PrintBottom();