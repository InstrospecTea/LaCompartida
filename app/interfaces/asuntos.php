<?php
require_once dirname(dirname(__FILE__)) . '/conf.php';
require_once dirname(dirname(__FILE__)) . '/classes/Html.php';

$Sesion = new Sesion(array('DAT', 'COB', 'SASU'));
$Pagina = new Pagina($Sesion);

$AreaProyecto = new AreaProyecto($Sesion);
$PrmTipoProyecto = new PrmTipoProyecto($Sesion);

/*
 * Configuraciones utilizadas en la interfaz
 */

# Descripción = Habilita autocompletador de asuntos.
$selectclienteasuntoespecial = Conf::GetConf($Sesion, 'SelectClienteAsuntoEspecial') == '1' ? true : false;

# Descripcion = El ambiente del cliente utiliza un codigo personalizado para...
# manejo de codígos de cliente y asunto en interfaces.
$usocodigosecundario = Conf::GetConf($Sesion, 'CodigoSecundario') == '1' ? true : false;

$Html = new \TTB\Html;

$formato_fecha = UtilesApp::ObtenerFormatoFecha($Sesion);

if ($excel) {
	$Asunto = new Asunto($Sesion);
	$Asunto->DownloadExcel(compact('activo', 'id_grupo_cliente', 'codigo_asunto', 'glosa_asunto', 'codigo_cliente', 'codigo_cliente_secundario', 'fecha1', 'fecha2', 'motivo', 'id_usuario', 'id_area_proyecto', 'opc', 'id_tipo_asunto'), 'id_grupo_cliente');
}

if ($Sesion->usuario->Es('DAT') && $accion == "eliminar") {
	$Asunto = new Asunto($Sesion);
	$Asunto->Load($id_asunto);
	if (!$Asunto->Eliminar()) {
		$Pagina->AddError($Asunto->error);
	} else {
		$Pagina->AddInfo(__('Asunto') . ' ' . __('eliminado con éxito'));
		$buscar = 1;
	}
}

$hide_areas = '';
if ($Sesion->usuario->Es('SASU')) {
	$hide_areas = 'style="display: none;"';
}

$GrupoCliente = new GrupoCliente($Sesion);

$Pagina->titulo = __('Listado de') . ' ' . __('Asuntos');
$Pagina->PrintTop($popup);
$Form = new Form;

?>

<script type="text/javascript">
	var usocodigosecundario = '<?php echo $usocodigosecundario; ?>';

	jQuery(document).ready(function() {
		jQuery("#agregar_asunto").click(function() {
			var codigo_cliente = '<?php echo $codigo_cliente; ?>';

			if (codigo_cliente == '') {
				if (usocodigosecundario != '1') {
					codigo_cliente = jQuery("#campo_codigo_cliente, #codigo_cliente").val();
				} else {
					codigo_cliente = jQuery("#campo_codigo_cliente_secundario, #codigo_cliente_secundario").val();
				}
 			}

			nuovaFinestra('Agregar_Asunto', 850, 600, 'agregar_asunto.php?codigo_cliente=' + codigo_cliente + '&popup=1&motivo=agregar_proyecto');
		});
	});

	function GrabarCampo(accion, asunto, cobro, valor) {
		if (valor) {
			valor = 'agregar';
		} else {
			valor = 'eliminar';
		}

		loading("Actualizando opciones");
		var url = 'ajax_grabar_campo.php';
		var datos = {accion: accion, codigo_asunto: asunto, id_cobro: cobro, valor: valor};
		jQuery.get(url, datos, function(respuesta) {
				if (respuesta != 'OK') {
					alert(respuesta);
				}
				offLoading();
		}, 'text');
	}

	function Listar(form, from) {
		if (from == 'buscar') {
			form.action = 'asuntos.php?buscar=1';
		} else if (from == 'xls') {
			form.action = 'asuntos.php?excel=1';
		} else if (from == 'facturacion_xls') {
			form.action = 'asuntos_facturacion_xls.php';
		} else {
			return false;
		}

		form.submit();
		return true;
	}

	function EliminaAsunto(from, id_asunto) {
		<?php
		if ($codigo_cliente) {
			echo "var codigo_cliente = '&codigo_cliente={$codigo_cliente}';";
		} else {
			echo "var codigo_cliente = '';";
		}
		?>

		var form = document.getElementById('form');
		if (from == 'agregar_cliente') {
			form.action = 'asuntos.php?buscar=1&accion=eliminar&id_asunto=' + id_asunto + codigo_cliente + '&from=agregar_cliente&popup=1';
		} else {
			form.action = 'asuntos.php?buscar=1&accion=eliminar&id_asunto=' + id_asunto + codigo_cliente + '&from=' + from;
		}

		form.submit();
		return true;
	}
</script>

<form method="post" name="form" id="form" autocomplete="OFF">
	<input type="hidden" name="busqueda" value="TRUE">
	<?php if ($id_cobro == '' && ($Sesion->usuario->Es('DAT') || $Sesion->usuario->Es('SASU'))) { ?>
		<table style="border: 0px solid black" width="100%">
			<tr>
				<td></td>
				<td colspan="3" align="right">
					<?php echo $Form->icon_button(__('Agregar') . ' ' . __('Asunto'), 'agregar', array('id' => 'agregar_asunto')); ?>
				</td>
			</tr>
		</table>
	<?php } ?>

	<?php if ($opc != 'entregar_asunto' && $from != 'agregar_cliente') { ?>
		<fieldset class="tb_base"  width="90%">
			<legend><?php echo __('Filtros'); ?></legend>
			<table style="border: 0 none" width='90%'>
				<tr>
					<td colspan="4">&nbsp;</td>
				</tr>
				<tr>
					<td class="ar" style="font-weight:bold;">
						<?php echo __('Activo'); ?>
					</td>
					<td class="al" style="width:80px;" >
						<?php echo $Html::SelectSiNo('activo', $activo); ?>
					</td>

					<td class="ar" style="font-weight:bold;">
						<?php echo __('Cobrable'); ?>
					</td>
					<td class="al">
						<?php echo $Html::SelectSiNo('cobrable', $cobrable); ?>
					</td>
				</tr>
				<tr>
					<td class="ar" style="font-weight:bold;">
						<b><?php echo __('Grupo'); ?></b>&nbsp;
					</td>
					<td class="al">
						<?php echo Html::SelectArrayDecente($GrupoCliente->Listar(), 'id_grupo_cliente', $id_grupo_cliente, '', 'Ninguno', '280px'); ?>
					</td>
				</tr>
				<tr>
					<td class="ar" style="font-weight:bold;">
						<?php echo __('Cliente'); ?>
					</td>
					<td nowrap class="al" colspan="3">
						<?php UtilesApp::CampoCliente($Sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
					</td>
				</tr>

				<tr>
					<td width="25%" class="ar" style="font-weight:bold;">
						<?php echo __('C&oacute;digo asunto'); ?>
					</td>
					<td nowrap class="al" colspan="4">
						<?php UtilesApp::CampoAsunto($Sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, 320, null, $glosa_asunto, false); ?>
					</td>
				</tr>

				<tr>
					<td class="ar" style="font-weight:bold;">
						<?php echo __('Fecha creaci&oacute;n'); ?>
					</td>
					<td nowrap class="al" colspan="3">
						<input onkeydown="if (event.keyCode == 13) Listar(this.form, 'buscar');" type="text" name="fecha1" class="fechadiff" value="<?php echo $fecha1; ?>" id="fecha1" size="11" maxlength="10" />
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<?php echo __('Hasta'); ?>
						<input onkeydown="if (event.keyCode == 13) Listar(this.form, 'buscar');" type="text" name="fecha2" class="fechadiff" value="<?php echo $fecha2; ?>" id="fecha2" size="11" maxlength="10" />
					</td>
				</tr>
				<tr>
					<td class="ar" style="font-weight:bold;">
						<?php echo __('Usuario'); ?>
					</td>
					<td class="al" colspan="3">
						<?php echo Html::SelectArrayDecente($Sesion->usuario->ListarActivos(), 'id_usuario', $id_usuario, '', 'Todos', '300px'); ?>
					</td>
				</tr>
				<tr>
					<td class="ar" style="font-weight:bold;">
						<?php echo __('&Aacute;rea'); ?>
					</td>
					<td class="al" colspan="3">
						<?php echo Html::SelectArrayDecente($AreaProyecto->Listar('ORDER BY orden ASC'), 'id_area_proyecto', $id_area_proyecto, '', 'Todos', '300px'); ?>
					</td>
				</tr>
				<tr>
					<td class="ar" style="font-weight:bold;">
						<?php echo __('Categoría de asunto'); ?>
					</td>
					<td class="al" colspan="3">
						<?php echo Html::SelectArrayDecente($PrmTipoProyecto->Listar('ORDER BY orden ASC'), 'id_tipo_asunto', $id_tipo_asunto, '', 'Todos', '300px'); ?>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td class="al" colspan="3">
						<?php echo $Form->icon_button(__('Buscar'), 'find', array('onclick' => "Listar(jQuery('#form').get(0), 'buscar')")); ?>
						<?php echo $Form->icon_button(__('Descargar listado a Excel'), 'xls', array('onclick' => "Listar(jQuery('#form').get(0), 'xls')")); ?>
						<?php echo $Form->icon_button(__('Descargar Información Comercial a Excel'), 'xls', array('onclick' => "Listar(jQuery('#form').get(0), 'facturacion_xls')")); ?>
					</td>
				</tr>
			</table>
		</fieldset>
	<?php } ?>
</form>
<?php

echo $Form->script();

if ($busqueda) {
	$link = 'Opciones';
} else {
	$link = __('Cobrar');
	$link.= " <br /><a href='asuntos.php?codigo_cliente={$codigo_cliente}&opc=entregar_asunto&id_cobro={$id_cobro}&popup=1&motivo=cobros&checkall=1'>" . __('Marcar Todos') . "</a>";
	$link.= " <br /><a href='asuntos.php?codigo_cliente={$codigo_cliente}&opc=entregar_asunto&id_cobro={$id_cobro}&popup=1&motivo=cobros&uncheckall=1'>" . __('Desmarcar Todos') . "</a>";
}

$CobroAsunto = new CobroAsunto($Sesion);

if ($checkall == '1') {
	$CobroAsunto->agregarAsuntos($id_cobro, $codigo_cliente, true);
}

if ($uncheckall == '1') {
	$CobroAsunto->eliminarAsuntos($id_cobro);
}

global $query, $where, $b;
$where = 1;

if ($buscar || $opc == "entregar_asunto") {
	if ($activo) {
		if ($activo == 'SI')
			$activo = 1;
		else
			$activo = 0;
		$where .= " AND a1.activo = $activo ";
	}

	if (!empty($id_grupo_cliente)) {
		$where .= " AND id_grupo_cliente = $id_grupo_cliente ";
	}

	if ($_POST['cobrable'] == 'SI') {
		$where .= " AND a1.cobrable=1 ";
	} else if ($_POST['cobrable'] == 'NO') {
		$where .= " AND a1.cobrable=0 ";
	}

	if ($codigo_asunto != '' || $codigo_asunto_secundario != '') {
		if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
			$where .= " AND a1.codigo_asunto_secundario Like '$codigo_asunto_secundario%'";
		} else {
			$where .= " AND a1.codigo_asunto Like '$codigo_asunto%'";
		}
	} else if ($glosa_asunto != '') {
		$nombre = strtr($glosa_asunto, ' ', '%');
		$where .= " AND a1.glosa_asunto Like '%{$glosa_asunto}%'";
	}

	if ($codigo_cliente || $codigo_cliente_secundario) {
		if ($usocodigosecundario && !$codigo_cliente) {
			$cliente = new Cliente($Sesion);
			if ($cliente->LoadByCodigoSecundario($codigo_cliente_secundario)) {
				$codigo_cliente = $cliente->fields['codigo_cliente'];
			}
		}
	}

	if ($opc == "entregar_asunto") {
		if ($id_contrato) {
			$where .= " AND (a1.codigo_cliente = '$codigo_cliente' OR a1.id_contrato='$id_contrato') ";
		} else {
			$where .= " AND a1.codigo_cliente = '$codigo_cliente' ";
		}
	} else if ($codigo_cliente) {
		$where .= " AND cliente.codigo_cliente = '$codigo_cliente' ";
	}

	if ($motivo == "cobros") {
		#$where .= " AND a1.activo='1' AND a1.cobrable = '1'";
		# Se cambia para que se pueda desmarcar un asunto no cobrable si es que venÃ­a premarcado en el contrato al generar el cobro
		$where .= " AND a1.activo='1'";
	}

	if ($fecha1 || $fecha2) {
		$where .= " AND a1.fecha_creacion BETWEEN '" . Utiles::fecha2sql($fecha1) . "' AND '" . Utiles::fecha2sql($fecha2) . " 23:59:59'";
	}

	if ($id_usuario) {
		$where .= " AND a1.id_encargado = '$id_usuario' ";
	}
	if ($id_area_proyecto) {
		$where .= " AND a1.id_area_proyecto = '$id_area_proyecto' ";
	}
	if ($id_tipo_asunto) {
		$where .= " AND a1.id_tipo_asunto = '$id_tipo_asunto' ";
	}

	// Este query es mejorable, se podría sacar horas_no_cobradas y horas_trabajadas, pero ya no se podría ordenar por estos campos.
	$query = "SELECT SQL_CALC_FOUND_ROWS cliente.glosa_cliente, a1.glosa_asunto, a1.codigo_asunto, a1.codigo_asunto_secundario, a1.id_moneda, a1.activo,
		a1.fecha_creacion, contrato.codigo_contrato, a1.id_asunto,
		(
			SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600
			FROM trabajo AS t2
				LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
			WHERE 1
				AND t2.codigo_asunto=a1.codigo_asunto
				AND t2.cobrable = 1
		) AS horas_no_cobradas,
		(
			SELECT SUM(TIME_TO_SEC(duracion))/3600
			FROM trabajo AS t3
			WHERE t3.codigo_asunto = a1.codigo_asunto AND t3.cobrable = 1
		) AS horas_trabajadas,
		ca.id_cobro AS id_cobro_asunto,
		(SELECT MAX(fecha_fin) FROM cobro AS c1 WHERE c1.id_contrato = a1.id_contrato) as fecha_ultimo_cobro";

	($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_query_asuntos') : false;

	$query .= " FROM asunto AS a1
		LEFT JOIN cliente ON cliente.codigo_cliente = a1.codigo_cliente
		LEFT JOIN cobro_asunto AS ca ON (ca.codigo_asunto = a1.codigo_asunto AND ca.id_cobro = '$id_cobro')
		LEFT JOIN contrato ON contrato.id_contrato = a1.id_contrato
		WHERE $where
		GROUP BY a1.codigo_asunto";

	if (empty($orden)) {
		$orden = "a1.activo DESC, horas_no_cobradas DESC, a1.glosa_asunto";
	}

	if (stristr($orden, ".") === FALSE) {
		$orden = str_replace("codigo_asunto", "a1.codigo_asunto", $orden);
	}

	$x_pag = ($motivo == "cobros") ? 15 : 10;

	$b = new Buscador($Sesion, $query, "Asunto", $desde, $x_pag, $orden);
	$b->formato_fecha = "$formato_fecha";
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_gastos";
	$b->titulo = __('Listado de') . ' ' . __('Asuntos');

	if ($usocodigosecundario) {
		$b->AgregarEncabezado("codigo_asunto_secundario", __('Código'), " class='al'  style='white-space:nowrap;' ");
	} else {
		$b->AgregarEncabezado("codigo_asunto", __('Código'), " class='al' style='white-space:nowrap;' ");
	}

	$b->AgregarEncabezado("glosa_cliente", __('Cliente'), "class='al'");
	($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_buscador_asuntos') : false;
	$b->AgregarEncabezado("glosa_asunto", __('Asunto'), "class='al'");
	$b->AgregarEncabezado("activo", __('Activo'), "align=left");
	$b->AgregarEncabezado("horas_trabajadas", __('Horas Trabajadas'), "class='al'");
	$b->AgregarEncabezado("horas_no_cobradas", __('Horas a cobrar'), "class='al'");
	$b->AgregarEncabezado("fecha_ultimo_cobro", __('Fecha último cobro'));
	$b->AgregarEncabezado("fecha_creacion", __('Fecha de creación"'));

	if ($Sesion->usuario->Es('DAT') || $Sesion->usuario->Es('SASU')) {
		$b->AgregarFuncion("$link", 'Opciones', "align=center' nowrap");
	}
	$b->color_mouse_over = "#bcff5c";
	$b->Imprimir();
}

function Cobrable(& $fila) {
	global $id_cobro;
	$checked = '';

	if ($fila->fields['id_cobro_asunto'] == $id_cobro and $id_cobro != '') {
		$checked = 'checked';
	}

	$codigo_asunto = $fila->fields['codigo_asunto'];
	$Check = sprintf('<input type="checkbox" %s onchange="%s" />', $checked, "GrabarCampo('agregar_asunto', '$codigo_asunto', $id_cobro, this.checked)");
	return $Check;
}

function Opciones(& $fila) {
	global $Sesion;
	global $checkall;
	global $motivo, $from;
	global $Form;

	if ($motivo == 'cobros') {
		return Cobrable($fila);
	}

	$id_asunto = $fila->fields['id_asunto'];

	if ($Sesion->usuario->Es('SASU')) {
		return "<a target='_parent' href=agregar_asunto.php?id_asunto=$id_asunto><img src='//static.thetimebilling.com/images/editar_on.gif' border=0 title=Editar actividad></a>";
	} else {
		$opciones = $Form->image_link('editar_on.gif', "agregar_asunto.php?id_asunto=$id_asunto", array('title' => 'Editar actividad', 'target' => '_parent'));
		$adb = __('Está seguro de eliminar el') . ' ' . __('asunto');
		$opciones .= $Form->image_link('cruz_roja_nuevo.gif', false, array('title' => 'Eliminar actividad', 'onclick' => "if  (confirm('¿$adb?')) EliminaAsunto('{$from}', {$id_asunto});"));
		$opciones .= $Form->image_link('lupa.gif', false, array('title' => 'Ver historial', 'id' => "asunto_{$fila->fields['id_asunto']}", 'class' => 'logdialog', 'rel' => 'asunto'));
		return $opciones;
	}
}

function SplitDuracion($time) {
	list($h, $m, $s) = split(":", $time);
	if ($h > 0 || $s > 0 || $m > 0) {
		return $h . ":" . $m;
	}
}

function funcionTR(& $Asunto) {
	global $formato_fecha;
	static $i = 0;

	$color = ($i % 2 == 0) ? '#DDDDDD' : '#FFFFFF';

	$fecha = Utiles::sql2fecha($Asunto->fields['fecha_ultimo_cobro'], $formato_fecha, "N/A");
	$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B; \">";
	$html .= "<td align=center>" . $Asunto->fields['codigo_asunto'] . "</td>";
	$html .= "<td align=center>" . $Asunto->fields['glosa_asunto'] . "</td>";
	$html .= "<td align=center>" . $fecha . "</td>";
	$html .= "<td align=center>" . Cobrable($Asunto) . "</td>";
	$html .= "</tr>";
	$i++;
	return $html;
}

$Pagina->PrintBottom($popup);
