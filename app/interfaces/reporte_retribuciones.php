<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Cliente.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Trabajo.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
//require_once Conf::ServerDir() . '/classes/Reportes/RetribucionReporte.php';

$sesion = new Sesion(array('RET'));
$pagina = new Pagina($sesion);
$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
$pagina->titulo = __('Reporte de Retribuciones');
$pagina->PrintTop();
$porcentaje_retribucion_socios = Conf::GetConf($sesion, 'RetribucionCentroCosto');
$moneda_base = Utiles::MonedaBase($sesion);

//$reporte = new RetribucionReporte($sesion, 'DETALLE');

?>
<style>
	.detail-table {
		width: 80%;
		text-align:left;
	}
	.header-labels th{
		vertical-align: middle;
		font-size: -0.4em;
		color: #060;
		text-align: left;
	}
	.group {
		color: #040;
		text-align:left;
		font-weight: bold;
	}
	.group-total {
		color: #040;
		text-align:left;
		font-weight: bold;
	}
	.subgroup {
		color: #040;
		text-align:left;
		font-weight: bold;
	}
	.subgroup.title {
		padding-left: 20px;
	}
	.detail {
		text-align:left;
	}
	.detail.title {
		padding-left: 30px;
	}
	.number-cell  {
		text-align:right !important;
	}
	.total {
		color: #040;
		text-align:left;
		font-weight: bold;
	}
	.total td, .group-total td {
		font-weight: bold;
	}
	.item-end hr{
		border-color: #080;
		border-width: 2px;
	}
	.total-separator  {
		border-top: solid 1px #080;
	}
	.group-separator {
		padding-bottom: 10px;
		border-top: solid 1px #BBB;
	}
	.item-group{
		background-color: #080;
		color: white;
	}
</style>


<form method=post name=formulario action="reporte_retribuciones.php">
	<input type="hidden" name="opc" id="opc" value='print'>
	<table width="90%"><tr><td>
				<fieldset class="border_plomo tb_base">
					<legend>
						<?php echo __('Filtros') ?>
					</legend>
					<table style=" width: 90%;" cellpadding="4">
						<tr>
							<td align=right >
								<?php echo __('Fecha desde') ?>:
							</td>
							<td align=left>
								<?php
								if (!$fecha1) {
									$fecha1 = date("d-m-Y", strtotime("- 1 month"));
									$fecha2 = date("d-m-Y");
								}
								?>
								<input type="text" name="fecha1" value="<?php echo $fecha1; ?>" id="fecha1" size="11" maxlength="10" />
								<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
							</td>
						</tr>
						<tr>
							<td align=right >
								<?php echo __('Fecha hasta') ?>:
							</td>
							<td align=left>
								<input type="text" name="fecha2" value="<?php echo $fecha2; ?>" id="fecha2" size="11" maxlength="10" />
								<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
							</td>
						</tr>
						<tr>
							<td align=right >
								<?php echo __('Considerar cobros en estado') ?>:
							</td>
							<td align=left>
								<?php
								echo Html::SelectArrayDecente(array(
									'EMITIDO,ENVIADO AL CLIENTE' => 'Emitido',
									'FACTURADO,PAGO PARCIAL' => 'Fecturado',
									'PAGADO' => 'Pagado',
									), 'estado', $estado ? $estado : 'PAGADO', '', 'Todos');
								?>
							</td>
						</tr>

						<tr>
							<td align=right>
								<?php echo __("Encargado") ?>:
							</td>
							<td align=left>
								<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]", $usuarios, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
							</td>
						</tr>

						<tr>
							<td align="right">
								<?php echo __('Calcular según') ?>:     </td>
							<td align="left">
								<?php
								echo Html::SelectArrayDecente(array('duracion_cobrada' => 'Horas Cobradas', 'monto_cobrado' => 'Valor Cobrado'), 'tipo_calculo', $tipo_calculo);
								?>
							</td>
						</tr>
						<tr>
							<td align=right >
								<?php echo __('Visualizar en Moneda') ?>:
							</td>
							<td align=left>
								<?php
								$moneda_seleccionada = $moneda_filtro ? $moneda_filtro : $moneda_base['id_moneda'];
								echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_filtro", $moneda_seleccionada, "", '','');
								?>
							</td>
						</tr>

						<tr>
							<td align=center colspan=2>
								<input type="submit" class=btn value="<?php echo __('Generar reporte') ?>" name="btn_reporte">
							</td>
						</tr>
					</table>
				</fieldset>
			</td></tr></table>
</form>

<?php
if (!empty($_POST)) {
	$wheres = array();

	$campo_fecha = 'fecha_emision';

	if (!empty($estado)) {
		$wheres[] = "cobro.estado IN('" . str_replace(',', "', '", $estado) . "')";

		$campo_fecha_estado = array(
			'EMITIDO,ENVIADO AL CLIENTE' => 'fecha_emision',
			'FACTURADO,PAGO PARCIAL' => 'fecha_facturacion',
			'PAGADO' => 'fecha_cobro',
		);
		$campo_fecha = $campo_fecha_estado[$estado];
	}

	$wheres[] = "cobro.$campo_fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . "' AND '" . Utiles::fecha2sql($fecha2) . "'";
	$wheres[] = "moneda_filtro.id_moneda = $moneda_filtro";
	if (!empty($usuarios)) {
		$wheres[] = 'usuario_responsable.id_usuario IN (' . implode(', ', $usuarios) . ')';
	}

	$where = implode(' AND ', $wheres);

	$query_encabezados = "SELECT
			cobro.id_cobro,
			documento.monto_trabajos*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as monto_trabajos,
			cobro.monto_trabajos / cobro.monto_thh_estandar as rentabilidad,
			cobro.opc_moneda_total,
			cobro.total_minutos,
			cobro.documento,
			cobro.estado,
			cobro.fecha_emision,
			cobro.$campo_fecha,
			documento.monto*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as monto,
			prm_moneda.simbolo,
			prm_moneda.cifras_decimales,
			cliente.glosa_cliente,
			GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ', ') as glosa_asuntos,
			contrato.retribucion_usuario_responsable,
			CONCAT(usuario_responsable.nombre, ' ', usuario_responsable.apellido1) as nombre_usuario_responsable,
			contrato.retribucion_usuario_secundario,
			CONCAT(usuario_secundario.nombre, ' ', usuario_secundario.apellido1) as nombre_usuario_secundario
		FROM cobro
			JOIN documento ON documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N'
			JOIN cobro_moneda as moneda_filtro ON moneda_filtro.id_cobro = cobro.id_cobro
 			JOIN prm_moneda ON moneda_filtro.id_moneda = prm_moneda.id_moneda
 			JOIN cobro_moneda as moneda_cobro ON moneda_cobro.id_cobro = cobro.id_cobro
 				AND moneda_cobro.id_moneda =cobro.opc_moneda_total
			JOIN contrato ON contrato.id_contrato = cobro.id_contrato
			JOIN cliente ON cliente.codigo_cliente = contrato.codigo_cliente
			JOIN cobro_asunto on cobro_asunto.id_cobro = cobro.id_cobro
			JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
			LEFT JOIN usuario AS usuario_responsable ON usuario_responsable.id_usuario = contrato.id_usuario_responsable
			LEFT JOIN usuario AS usuario_secundario ON usuario_secundario.id_usuario = contrato.id_usuario_secundario
		WHERE cobro.monto_subtotal > 0 AND $where
		GROUP BY cobro.id_cobro";

	echo "<!-- $query_encabezados -->";

	$response_encabezados = $sesion->pdodbh->query($query_encabezados);
	$encabezados = $response_encabezados->fetchAll(PDO::FETCH_ASSOC);

	$lista_id_cobros = array_map('reset', $encabezados);
	$id_cobros = empty($lista_id_cobros) ? '0' : implode(', ', $lista_id_cobros);

	$query_detalle = "SELECT
			trabajo.id_cobro,
			CONCAT(usuario.nombre, ' ', usuario.apellido1) as nombre,
			usuario.porcentaje_retribucion,
			area.id AS id_area,
			area.id_padre AS id_area_padre,
      IFNULL(area.id_padre, area.id) AS id_area_grupo,
			area.glosa AS glosa_area,
			IFNULL(area_padre.glosa, area.glosa) AS glosa_area_padre,
			SUM(TIME_TO_SEC(trabajo.duracion_cobrada))/60 as minutos_cobrados,
			SUM(TIME_TO_SEC(trabajo.duracion_cobrada)/3600*trabajo.tarifa_hh_estandar)*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as monto_cobrado
		FROM trabajo
			JOIN cobro_moneda as moneda_filtro ON moneda_filtro.id_cobro = trabajo.id_cobro
 			JOIN cobro_moneda as moneda_cobro ON moneda_cobro.id_cobro = trabajo.id_cobro
 				AND moneda_cobro.id_moneda = trabajo.id_moneda
			JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
			JOIN prm_area_usuario area ON area.id = usuario.id_area_usuario
			LEFT JOIN prm_area_usuario area_padre ON area_padre.id = area.id_padre
		WHERE trabajo.id_cobro IN ($id_cobros) AND moneda_filtro.id_moneda = $moneda_filtro AND trabajo.cobrable = 1
		GROUP BY trabajo.id_cobro, trabajo.id_usuario
		ORDER BY id_cobro, glosa_area_padre, area.id_padre, glosa_area";
		echo "<!-- $query_detalle -->";
	$response_detalle = $sesion->pdodbh->query($query_detalle);
	//fetch agrupado por cobro (id_cobro=>array(filas))
	$detalles = $response_detalle->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
	?>
	<table cellspacing="0" cellpading="0" border="0">
		<?php

		//Genera subtotales
		function genera_subtotal($subgrupo, &$subtotales, &$subtotalizar) {
			global $imprime_registro;
			if ($subtotalizar) {
				echo "<tr><td colspan=2></td><td colspan=10 class='total-separator'></td></tr>";
				imprime_registro(" SubTotal&nbsp;$subgrupo", $subtotales, 'subgroup');
				$subtotalizar = false;
			}
		}

		//Genera totales
		function genera_total($grupo, &$totales) {
			global $imprime_registro;
			echo "<tr><td colspan=2></td><td colspan=10 class='total-separator'></td></tr>";
			imprime_registro(" SubTotal&nbsp;$grupo", $totales, 'group-total');
		}

		//Genera totales generalse
		function genera_total_general(&$totales) {
			global $imprime_registro;
			echo "<tr><td colspan=2></td><td colspan=10 class='total-separator'></td></tr>";
			imprime_registro("TOTAL", $totales, 'total');
		}

		//verifica e imprime un corte por Grupo
		function corte_grupo($last, $current, $attr) {
			if (!isset($last) || $current[$attr] !== $last[$attr]) {
				echo "<tr><td colspan=2></td><td colspan=10 class='group-separator'></td></tr>";
				echo "<tr><td colspan=2></td><td class='title group'>" . __('Área') . ":&nbsp;" . $current['glosa_area_padre'] . "</td></tr>";
				$subtotales["porcentaje_intervencion"] += $valores["porcentaje_intervencion"];
				$subtotales["retribucion_socios"] += $valores["retribucion_socios"];
				$subtotales["retribucion_abogado"] += $valores["retribucion_abogado"];
				$subtotales["tiempo_cobrado"] += $valores["tiempo_cobrado"];
			}
		}

		//verifica e imprime un corte por SubGrupo
		function corte_subgrupo($last, $current, $attr, &$subtotalizar) {
			if (!isset($last) || $current[$attr] !== $last[$attr]) {
				if (isset($current['id_area_padre'])) {
					echo "<tr><td colspan=2></td><td class='title subgroup'>" . __('Área') . ":&nbsp;" . $current['glosa_area'] . "</td></tr>";
					$subtotalizar = true;
				}
			}
		}

		//Acumulador Genérico
		function acumula_subtotal($valores, &$subtotales) {
			$subtotales["porcentaje_intervencion"] += $valores["porcentaje_intervencion"];
			$subtotales["retribucion_socios"] += $valores["retribucion_socios"];
			$subtotales["retribucion_abogado"] += $valores["retribucion_abogado"];
			$subtotales["tiempo_cobrado"] += $valores["tiempo_cobrado"];
			$subtotales["monto_cobrado"] += $valores["monto_cobrado"];
		}

		//limpia o crea un arreglo con los datos
		function limpia_valores(&$valores) {
			$valores = array("porcentaje_intervencion" => 0,
				"retribucion_socios" => 0,
				"retribucion_abogado" => 0,
				"tiempo_cobrado" => 0,
				"monto_cobrado" => 0
			);
		}

		//imprimer un registro ya sea de detalle o de totales
		function imprime_registro($titulo, $valores, $class = '') {
			global $simbolo_moneda, $numero_decimales, $tipo_calculo;
			if ($tipo_calculo == 'duracion_cobrada'){
				$mostrar = UtilesApp::Hora2HoraMinuto($valores['tiempo_cobrado'] / 60);
			}
			else{
				$mostrar = $simbolo_moneda . ' ' . number_format($valores['monto_cobrado'], $numero_decimales);
			}
			?>
			<tr class='<?php echo $class; ?>'>
				<td colspan=2>&nbsp;</td>
				<td class='title <?php echo $class; ?>'><?php echo $titulo; ?></td>
				<td class='number-cell' colspan=1><?php echo isset($valores['portentaje_retribucion_abogado']) ? number_format($valores['portentaje_retribucion_abogado'], 2) : ''; ?></td>
				<td class='number-cell' colspan=1><?php echo $mostrar ?></td>
				<td class='number-cell' colspan=1><?php echo number_format($valores['porcentaje_intervencion'], 2); ?></td>
				<td class='number-cell' colspan=1><?php echo $simbolo_moneda . ' ' . number_format($valores['retribucion_socios'], $numero_decimales); ?></td>
				<td class='number-cell' colspan=1><?php echo $simbolo_moneda . ' ' . number_format($valores['retribucion_abogado'], $numero_decimales); ?></td>
			<tr>
				<?
			}

			$cliente_actual = '';
			$nombre_fecha = array(
				'fecha_facturacion' => __('Fecha Facturación'),
				'fecha_cobro' => __('Fecha Pago'),
			);
			foreach ($encabezados as $encabezado) {
				//imprimir encabezado
				if ($cliente_actual != $encabezado['glosa_cliente']) {
					$cliente_actual = $encabezado['glosa_cliente'];
					?>
				<tr class="item-group"><th colspan="12"><?php echo $cliente_actual; ?></th></tr>
				<?
			}
			$responsable = !empty($encabezado['nombre_usuario_responsable']) && !empty($encabezado['retribucion_usuario_responsable']);
			$secundario = !empty($encabezado['nombre_usuario_secundario']) && !empty($encabezado['retribucion_usuario_secundario']);
			?>
			<tr class='header-labels'>
				<th><?php echo __('N° Cobro'); ?></th>
				<th><?php echo __('Facturas'); ?></th>
				<th><?php echo __('Asuntos'); ?></th>
				<th><?php echo __('Fecha Emisión'); ?></th>
				<?php if ($campo_fecha != 'fecha_emision') { ?>
					<th><?php echo $nombre_fecha[$campo_fecha]; ?></th>
				<?php } ?>
				<th><?php echo __('Horas'); ?></th>
				<th><?php echo __('Monto Total'); ?></th>
				<th><?php echo __('Monto Honorarios'); ?></th>
				<?php if ($responsable) { ?>
					<th><?php echo __('Encargado Comercial'); ?></th>
					<th><?php echo __('Ret.') . ' ' . __('Encargado Comercial'); ?></th>
				<?php } if ($secundario) { ?>
					<th><?php echo __('Encargado Secundario'); ?></th>
					<th><?php echo __('Ret.') . ' ' . __('Encargado Secundario'); ?></th>
				<?php } ?>
			</tr>
			<tr>
				<td><?php echo $encabezado['id_cobro']; ?></td>
				<td><?php echo $encabezado['documento']; ?></td>
				<td><?php echo $encabezado['glosa_asuntos']; ?></td>
				<td><?php echo UtilesApp::sql2fecha($encabezado['fecha_emision'], '%d/%m/%y'); ?></td>
				<?php if ($campo_fecha != 'fecha_emision') { ?>
					<td><?php echo UtilesApp::sql2fecha($encabezado[$campo_fecha], '%d/%m/%y'); ?></td>
				<?php } ?>
				<td><?php echo UtilesApp::Hora2HoraMinuto($encabezado['total_minutos'] / 60); ?></td>
				<td><?php echo $encabezado['simbolo'] . ' ' . number_format($encabezado['monto'], $encabezado['cifras_decimales']); ?></td>
				<td><?php echo $encabezado['simbolo'] . ' ' . number_format($encabezado['monto_trabajos'], $encabezado['cifras_decimales']); ?></td>
				<?php if ($responsable) { ?>
					<td><?php echo $encabezado['nombre_usuario_responsable']; ?></td>
					<td><?php echo $encabezado['simbolo'] . ' ' . number_format($encabezado['retribucion_usuario_responsable'] * $encabezado['monto_trabajos'] / 100, $encabezado['cifras_decimales']); ?></td>
				<?php } if ($secundario) { ?>
					<td><?php echo $encabezado['nombre_usuario_secundario']; ?></td>
					<td><?php echo $encabezado['simbolo'] . ' ' . number_format($encabezado['retribucion_usuario_secundario'] * $encabezado['monto_trabajos'] / 100, $encabezado['cifras_decimales']); ?></td>
				<?php } ?>
			</tr>
			<tr><td colspan="12"><hr/></td></tr>
			<tr><td colspan="12">
					<table width="90%">
						<tr class='header-labels'>
							<th colspan='3'><?php echo __('Distribución de la Retribución'); ?></th>
							<th class='number-cell'>%<br/><?php echo __('Retribución'); ?></th>
							<?php if ($tipo_calculo == 'duracion_cobrada') { ?>
								<th class='number-cell'><?php echo __('Horas<br/>Cobradas'); ?></th>
							<?php } else { ?>
								<th class='number-cell'><?php echo __('Valor<br/>Cobrado'); ?></th>
							<?php } ?>
							<th class='number-cell'>%<br/><?php echo __('Intervención'); ?></th>
							<th class='number-cell'><?php echo __('Retribución') . '<br/>' . __('Socios') . "&nbsp;($porcentaje_retribucion_socios%)"; ?></th>
							<th class='number-cell'><?php echo __('Retribución') . '<br/>' . __('Abogados'); ?></th>
						</tr>

						<?php
						$detalles_retribucion = $detalles[$encabezado['id_cobro']];
						$last_grupo = '';
						$last_subgrupo = '';
						$last_subglosa = '';
						$subtotalizar = false;
						$simbolo_moneda = $encabezado['simbolo'];
						$numero_decimales = $encabezado['cifras_decimales'];


						$detalles_retribucion = $detalles[$encabezado['id_cobro']];
						$subtotalizar = false;
						$simbolo_moneda = $encabezado['simbolo'];
						$numero_decimales = $encabezado['cifras_decimales'];

						$last_record = null;
						limpia_valores($subtotales);
						limpia_valores($totales);
						limpia_valores($totales_generales);
						foreach ($detalles_retribucion as $retribucion) {
							if ($last_record && $last_record['id_area'] != $retribucion['id_area']) {
								genera_subtotal($last_record['glosa_area'], $subtotales, $subtotalizar);
								limpia_valores($subtotales);
							}
							if ($last_record && $last_record['id_area_grupo'] !== $retribucion['id_area_grupo']) {
								genera_total($last_record['glosa_area_padre'], $totales);
								limpia_valores($totales);
							}
							corte_grupo($last_record, $retribucion, 'glosa_area_padre');
							corte_subgrupo($last_record, $retribucion, 'id_area', $subtotalizar);
							$rentabilidad = $encabezado['rentabilidad'];
							if ($tipo_calculo == 'duracion_cobrada'){
								$porcentaje_intervencion = $retribucion['minutos_cobrados'] / $encabezado['total_minutos'] * 100;
							}
							else{
								$porcentaje_intervencion = ($retribucion['monto_cobrado']*$rentabilidad) / $encabezado['monto_trabajos'] * 100;
							}

							$valores = array("porcentaje_intervencion" => $porcentaje_intervencion,
								"portentaje_retribucion_abogado" => $retribucion['porcentaje_retribucion'],
								"retribucion_socios" => $encabezado['monto_trabajos'] * $porcentaje_intervencion * $porcentaje_retribucion_socios / (100 * 100),
								"retribucion_abogado" => $encabezado['monto_trabajos'] * $porcentaje_intervencion * $retribucion['porcentaje_retribucion'] / (100 * 100),
								"tiempo_cobrado" => $retribucion['minutos_cobrados'],
								"monto_cobrado" => $retribucion['monto_cobrado'] * $rentabilidad
							);
							imprime_registro($retribucion['nombre'], $valores, 'detail');

							$last_record = $retribucion;
							acumula_subtotal($valores, $subtotales);
							acumula_subtotal($valores, $totales);
							acumula_subtotal($valores, $totales_generales);
						}
						genera_subtotal($last_record['glosa_area'], $subtotales, $subtotalizar);
						genera_total($last_record['glosa_area_padre'], $totales);
						genera_total_general($totales_generales);
						?>

					</table>
				</td>
			</tr>
			<tr class="item-end"><td colspan="12"><hr/></td></tr>
					<?php
				}
				?>

	</table>
	<?php
}
?>
<script>
	Calendar.setup(
	{
		inputField  : "fecha1",       // ID of the input field
		ifFormat    : "%d-%m-%Y",     // the date format
		button      : "img_fecha_ini"   // ID of the button
	}
);
	Calendar.setup(
	{
		inputField  : "fecha2",       // ID of the input field
		ifFormat    : "%d-%m-%Y",     // the date format
		button      : "img_fecha_fin"   // ID of the button
	}
);
</script>
<?php
$pagina->PrintBottom();
?>