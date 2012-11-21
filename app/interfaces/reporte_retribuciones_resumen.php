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

$sesion = new Sesion(array('RET'));
$pagina = new Pagina($sesion);
$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
$pagina->titulo = __('Reporte Resumen de Retribuciones');
$pagina->PrintTop();
$porcentaje_retribucion_socios = Conf::GetConf($sesion, 'RetribucionCentroCosto');
$moneda_base = Utiles::MonedaBase($sesion);
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
	.detail.title a {
		padding-left: -30px;
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
	.detail-separator  {
		padding-bottom: 10px;
	}
	.item-group{
		background-color: #080;
		color: white;
	}

</style>


<form method=post name=formulario action="reporte_retribuciones_resumen.php">
	<input type="hidden" name="opc" id="opc" value='print'>
	<table width="90%">
		<tr>
			<td>
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
							<td align=right>
								<?php echo __('Profesionales') ?>:
							</td>
							<td align=left>
								<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario)  WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]", $usuarios, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
							</td>
						</tr>

						<tr>
							<td align=right>
								<?php echo __('Área Usuario') ?>:
							</td>
							<td align=left>
								<?php
								$query_areas = '
													SELECT area.id, CONCAT(REPEAT("&nbsp;", IF(ISNULL(padre.id), 0, 5)), area.glosa)
														FROM prm_area_usuario AS area
															LEFT JOIN prm_area_usuario AS padre ON area.id_padre = padre.id
														ORDER BY  IFNULL(padre.glosa, area.glosa), padre.glosa, area.glosa ASC ';

								echo Html::SelectQuery($sesion, $query_areas, 'id_area_usuario', $id_area_usuario, '', 'Todas');
								?>
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
							<td align=right >
								<?php echo __('Visualizar en Moneda') ?>:
							</td>
							<td align=left>
								<?php
								$moneda_seleccionada = $moneda_filtro ? $moneda_filtro : $moneda_base['id_moneda'];
								echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_filtro", $moneda_seleccionada, "", '', '');
								?>
							</td>
						</tr>

						<tr>
							<td align="right">
								<?php echo __('Incluir Montos Retribuidos'); ?>:
							</td>
							<td align="left">
								<?php
								echo Html::SelectArrayDecente(array('SI' => __('Sólo Retribuidos'), 'NO' => __('Sólo Sin Retribuir')), 'retribuidos', isset($retribuidos) ? $retribuidos : 'NO', '', __('Todos'));
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
			</td>
		</tr>
	</table>
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

	$where = implode(' AND ', $wheres);

	$campos_retribucion = '
		contrato.retribucion_usuario_responsable,
		contrato.retribucion_usuario_secundario';
	if($retribuidos){
		$cond = $retribuidos == 'SI' ? 'IS NOT NULL' : 'IS NULL';
		$campos_retribucion = "
			IF(cobro.fecha_retribucion_responsable $cond, contrato.retribucion_usuario_responsable, 0) as retribucion_usuario_responsable,
			IF(cobro.fecha_retribucion_secundario $cond, contrato.retribucion_usuario_secundario, 0) as retribucion_usuario_secundario";
	}

	$query_encabezados = "SELECT
			cobro.id_cobro,
			cobro.monto_trabajos / cobro.monto_thh_estandar as rentabilidad,
			cobro.total_minutos,
			documento.monto_trabajos*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as monto_trabajos,
			cobro.id_usuario_responsable,
			cobro.id_usuario_secundario,
			$campos_retribucion
		FROM cobro
			JOIN documento ON documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N'
			JOIN cobro_moneda as moneda_filtro ON moneda_filtro.id_cobro = cobro.id_cobro
				AND moneda_filtro.id_moneda = $moneda_filtro
 			JOIN cobro_moneda as moneda_cobro ON moneda_cobro.id_cobro = cobro.id_cobro
 				AND moneda_cobro.id_moneda =cobro.opc_moneda_total
			JOIN contrato ON contrato.id_contrato = cobro.id_contrato
		WHERE cobro.monto_subtotal > 0 AND $where
		GROUP BY cobro.id_cobro";

	$response_encabezados = $sesion->pdodbh->query($query_encabezados);
	$encabezados = $response_encabezados->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

	$lista_id_cobros = array_keys($encabezados);
	$id_cobros = empty($lista_id_cobros) ? '0' : implode(', ', $lista_id_cobros);

	$wheres_detalle = array();

	if (!empty($usuarios)) {
		$wheres_detalle[] = 'todos.id_usuario IN (' . implode(', ', $usuarios) . ')';
	}
	if (!empty($id_area_usuario)) {
		$wheres_detalle[] = "area.id = $id_area_usuario";
	}
	$wheres_detalle[] = "todos.visible = 1 AND usuario_permiso.codigo_permiso='PRO'";

	$where_detalle = implode(' AND ', $wheres_detalle);

	$where_trabajo = '';
	if($retribuidos){
		$where_trabajo = ' AND fecha_retribucion ' . ($retribuidos == 'SI' ? 'IS NOT NULL' : 'IS NULL');
	}

	$query_detalle = "SELECT trabajo.id_cobro,
       Concat(todos.nombre, ' ', todos.apellido1)                   AS nombre,
       todos.id_usuario,
       todos.porcentaje_retribucion,
       area.id                                                      AS id_area,
       area.id_padre                                                AS id_area_padre,
       Ifnull(area.id_padre, area.id)                               AS id_area_grupo,
       area.glosa                                                   AS glosa_area,
       Ifnull(area_padre.glosa, area.glosa)                         AS glosa_area_padre,
       Sum(Time_to_sec(trabajo.duracion_cobrada)) / 60              AS minutos_cobrados,
       Sum(Time_to_sec(trabajo.duracion_cobrada) / 3600 * trabajo.tarifa_hh_estandar) *
       	( moneda_cobro.tipo_cambio ) / ( moneda_filtro.tipo_cambio ) AS monto_cobrado
		FROM   usuario AS todos
		       JOIN prm_area_usuario area
		         ON area.id = todos.id_area_usuario
		       LEFT JOIN prm_area_usuario area_padre
		              ON area_padre.id = area.id_padre
		       JOIN usuario_permiso using(id_usuario)
		       LEFT JOIN ((SELECT id_cobro,
		                         duracion_cobrada,
		                         tarifa_hh_estandar,
		                         id_usuario,
		                         id_moneda
		                  FROM   trabajo
		                  WHERE  trabajo.id_cobro IN ($id_cobros)
		                         AND trabajo.cobrable = 1 $where_trabajo ) AS trabajo
		                  JOIN cobro_moneda AS moneda_cobro
		                    ON moneda_cobro.id_cobro = trabajo.id_cobro
		                  JOIN cobro_moneda AS moneda_filtro
		                    ON moneda_filtro.id_cobro = trabajo.id_cobro
		                       AND moneda_filtro.id_moneda = $moneda_filtro
		                       AND moneda_cobro.id_moneda = trabajo.id_moneda)
		              ON todos.id_usuario = trabajo.id_usuario
		WHERE  $where_detalle
		GROUP  BY todos.id_usuario,
		          trabajo.id_cobro
		ORDER  BY glosa_area_padre,
		          area.id_padre,
		          glosa_area,
		          todos.id_usuario ";

	$response_detalle = $sesion->pdodbh->query($query_detalle);
	$detalles = $response_detalle->fetchAll(PDO::FETCH_ASSOC);

	$subtotalizar = false;
	$detalle_cobros = '';

	$query_idioma_defecto = "SELECT pi.* FROM prm_idioma pi WHERE pi.codigo_idioma = (SELECT LOWER(valor_opcion) FROM configuracion WHERE glosa_opcion = 'Idioma' )";
	$response_idioma = $sesion->pdodbh->query($query_idioma_defecto);
	$idioma = reset($response_idioma->fetchAll(PDO::FETCH_ASSOC));

	$query_moneda = "SELECT * FROM prm_moneda WHERE id_moneda = $moneda_filtro";
	$response_moneda = $sesion->pdodbh->query($query_moneda);
	$moneda = reset($response_moneda->fetchAll(PDO::FETCH_ASSOC));

	if($opc == 'marcar_retribuido'){
		$fecha = date('Y-m-d H:i:s'); //no uso NOW() para que queden todas identicas
		$campo_intervencion = $tipo_calculo == 'duracion_cobrada' ?
			'IF(c.total_minutos > 0, TIME_TO_SEC(t.duracion_cobrada) / (c.total_minutos * 60), 0)' :
			'IF(d.monto_trabajos > 0 and c.monto_thh_estandar > 0, TIME_TO_SEC(t.duracion_cobrada) / 3600 * t.tarifa_hh_estandar / c.monto_thh_estandar, 0)';
		$campo_monto = 'd.monto_trabajos * mc.tipo_cambio / mf.tipo_cambio';
		$campo_monto_intervencion = "$campo_monto * $campo_intervencion";

		$where_usuarios = !empty($usuarios) ? ' IN (' . implode(', ', $usuarios) . ') ' : ' IS NOT NULL ';

		$query_marcar = "UPDATE trabajo t
				JOIN cobro c ON c.id_cobro = t.id_cobro
				JOIN documento d ON d.id_cobro = c.id_cobro AND d.tipo_doc = 'N'
				JOIN cobro_moneda mc ON mc.id_cobro = c.id_cobro AND mc.id_moneda = c.opc_moneda_total
				JOIN cobro_moneda mf ON mf.id_cobro = c.id_cobro AND mf.id_moneda = $moneda_filtro
				JOIN usuario u ON u.id_usuario = t.id_usuario
			SET
				fecha_retribucion = '$fecha',
				id_moneda_retribucion = $moneda_filtro,
				monto_retribucion_usuario = $campo_monto_intervencion * u.porcentaje_retribucion / 100,
				monto_retribucion_area = $campo_monto_intervencion * $porcentaje_retribucion_socios / 100

			WHERE t.id_cobro IN ($id_cobros)
				AND t.cobrable = 1
				AND t.fecha_retribucion IS NULL
				AND t.id_usuario $where_usuarios";

		$sesion->pdodbh->exec($query_marcar);

		foreach(array('responsable', 'secundario') as $encargado){
			$query_marcar_encargado = "UPDATE cobro c
					JOIN documento d ON d.id_cobro = c.id_cobro AND d.tipo_doc = 'N'
					JOIN cobro_moneda mc ON mc.id_cobro = c.id_cobro AND mc.id_moneda = c.opc_moneda_total
					JOIN cobro_moneda mf ON mf.id_cobro = c.id_cobro AND mf.id_moneda = $moneda_filtro
					JOIN contrato con ON con.id_contrato = c.id_contrato
				SET
					fecha_retribucion_$encargado = '$fecha',
					monto_retribucion_$encargado = $campo_monto * con.retribucion_usuario_$encargado / 100,
					id_moneda_retribucion_$encargado = $moneda_filtro
				WHERE c.id_cobro IN ($id_cobros)
					AND c.id_usuario_$encargado $where_usuarios";

			$sesion->pdodbh->exec($query_marcar_encargado);
		}
	}

	//limpia o crea un arreglo con los datos
	function limpia_valores(&$valores) {
		$valores = array("porcentaje_intervencion" => 0,
			"portentaje_retribucion_abogado" => 0,
			"retribucion_socios" => 0,
			"retribucion_abogado" => 0,
			"tiempo_cobrado" => 0,
			"monto_cobrado" => 0,
			'retribucion_responsable' => 0,
			'retribucion_secundario' => 0,
		);
	}

	//imprimer un registro ya sea de detalle o de totales
	function imprime_registro($titulo, $valores, $class = '', $expandible = false) {
		global $tipo_calculo, $detalle_cobros;
		if ($tipo_calculo == 'duracion_cobrada')
			$mostrar = UtilesApp::Hora2HoraMinuto($valores['tiempo_cobrado'] / 60);
		else
			$mostrar = monto($valores['monto_cobrado']);
		?>
		<tr class='<?php echo $class; ?>'>
			<td colspan=2>&nbsp;</td>
			<td colspan=3 class='title <?php echo $class; ?>'>
				<?php
				echo (($expandible && !empty($detalle_cobros)) ? "<a href='#' class='ver-detalle'><img src='//static.thetimebilling.com/images/mas.gif' title='Ver Detalle'/></a>&nbsp;&nbsp;" : "&nbsp;");
				echo $titulo;
				?></td>
			<td class='number-cell' colspan=1><?php echo ($expandible ? number_format($valores['portentaje_retribucion_abogado'], 2) : ''); ?></td>
			<td class='number-cell' colspan=1><?php echo $mostrar ?></td>
			<td class='number-cell' colspan=1><?php echo monto($valores['retribucion_socios']); ?></td>
			<td class='number-cell' colspan=1><?php echo monto($valores['retribucion_abogado']); ?></td>
			<td class='number-cell' colspan=1><?php echo empty($valores['retribucion_responsable']) ? '' : monto($valores['retribucion_responsable']); ?></td>
			<td class='number-cell' colspan=1><?php echo empty($valores['retribucion_secundario']) ? '' : monto($valores['retribucion_secundario']); ?></td>
		<tr>
		<?
	}

	//acumula detalle
	function acumula_detalle_cobros($valores) {
		global $detalle_cobros, $tipo_calculo;
		if ($tipo_calculo == 'duracion_cobrada') {
			$mostrar = UtilesApp::Hora2HoraMinuto($valores['tiempo_cobrado'] / 60);
		} else {
			$mostrar = monto($valores['monto_cobrado']);
		}
		if (!empty($valores['id_cobro'])) {
			$detalle_cobros = $detalle_cobros . "
				<tr>
					<td colspan=2>&nbsp;</td>
					<td class='number-cell' colspan=1>" . $valores['id_cobro'] . "</td>
					<td class='number-cell' colspan=1>" . (isset($valores['porcentaje_intervencion']) ? number_format($valores['porcentaje_intervencion'], 2) : '') . "</td>
					<td class='number-cell' colspan=1></td>
					<td class='number-cell' colspan=1>" . (isset($valores['portentaje_retribucion_abogado']) ? number_format($valores['portentaje_retribucion_abogado'], 2) : '' ) . "</td>
					<td class='number-cell' colspan=1>$mostrar</td>
					<td class='number-cell' colspan=1>" . monto($valores['retribucion_socios']) . "</td>
					<td class='number-cell' colspan=1>" . monto($valores['retribucion_abogado']) . "</td>
					<td class='number-cell' colspan=1>" . (empty($valores['retribucion_responsable']) ? '' : monto($valores['retribucion_responsable'])) . "</td>
					<td class='number-cell' colspan=1>" . (empty($valores['retribucion_secundario']) ? '' : monto($valores['retribucion_secundario'])) . "</td>
				<tr>";
		}
	}

	function monto($monto) {
		global $moneda, $idioma;
		if ($monto === null)
			return '';
		return $moneda['simbolo'] . '&nbsp;' . number_format($monto, $moneda['cifras_decimales'], $idioma['separador_decimales'], $idioma['separador_miles']);
	}

	function imprime_detalle_cobros() {
		global $detalle_cobros;
		?>
		<tbody style='display:none'>
			<?php if (!empty($detalle_cobros)) { ?>
				<tr>
					<th colspan=2>&nbsp;</th>
					<th class='number-cell' style='width:90px'><?php echo __('N° Cobro') ?></th>
					<th class='number-cell'>%&nbsp;<?php echo __('Intervención') ?></th>
				</tr>
				<?php echo $detalle_cobros ?>
				<tr><td colspan=2></td><td colspan=10 class='detail-separator'></td></tr>
			<?php } ?>
		</tbody>
		<?php
		$detalle_cobros = '';
	}

	//Genera subtotales
	function genera_subtotal($subgrupo, &$subtotales, &$subtotalizar) {
		if ($subtotalizar) {
			echo "<tr><td colspan=2></td><td colspan=10 class='total-separator'></td></tr>";
			imprime_registro(" SubTotal&nbsp;$subgrupo", $subtotales, 'subgroup');
			$subtotalizar = false;
		}
	}

	//Genera totales
	function genera_total($grupo, &$totales) {
		echo "<tr><td colspan=2></td><td colspan=10 class='total-separator'></td></tr>";
		imprime_registro(" SubTotal&nbsp;$grupo", $totales, 'group-total');
	}

	//Genera totales generalse
	function genera_total_general(&$totales) {
		echo "<tr><td colspan=2></td><td colspan=10 class='total-separator'></td></tr>";
		imprime_registro("TOTAL", $totales, 'total');
	}

	//verifica e imprime un corte por Grupo
	function corte_grupo($last, $current, $attr) {
		if (!isset($last) || $current[$attr] !== $last[$attr]) {
			echo "<tr><td colspan=2></td><td colspan=10 class='group-separator'></td></tr>";
			echo "<tr><td colspan=2></td><td class='title group'>" . __('Área') . ":&nbsp;" . $current['glosa_area_padre'] . "</td></tr>";
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
		$subtotales["portentaje_retribucion_abogado"] = $valores["portentaje_retribucion_abogado"];
		$subtotales["retribucion_socios"] += $valores["retribucion_socios"];
		$subtotales["retribucion_abogado"] += $valores["retribucion_abogado"];
		$subtotales["tiempo_cobrado"] += $valores["tiempo_cobrado"];
		$subtotales["monto_cobrado"] += $valores["monto_cobrado"];
		$subtotales["retribucion_responsable"] += $valores["retribucion_responsable"];
		$subtotales["retribucion_secundario"] += $valores["retribucion_secundario"];
	}

	function acumula_todo($last_record, $retribucion_encabezado) {
		global $totales_usuario, $subtotales, $totales, $totales_generales;
		if (!empty($retribucion_encabezado[$last_record['id_usuario']])) {

			foreach ($retribucion_encabezado[$last_record['id_usuario']] as $id_cobro => $retribuciones) {
				$valores_ret = array("porcentaje_intervencion" => 0,
					"portentaje_retribucion_abogado" => $last_record['porcentaje_retribucion'],
					"retribucion_socios" => 0,
					"retribucion_abogado" => 0,
					"tiempo_cobrado" => 0,
					"monto_cobrado" => 0,
					"retribucion_responsable" => $retribuciones['responsable'],
					"retribucion_secundario" => $retribuciones['secundario'],
					"user_id" => $last_record['id_usuario'],
					"id_cobro" => $id_cobro
				);

				acumula_detalle_cobros($valores_ret);
				acumula_subtotal($valores_ret, $totales_usuario);
				acumula_subtotal($valores_ret, $subtotales);
				acumula_subtotal($valores_ret, $totales);
				acumula_subtotal($valores_ret, $totales_generales);
			}
		}
	}

	$last_record = null;
	limpia_valores($totales_usuario);
	limpia_valores($subtotales);
	limpia_valores($totales);
	limpia_valores($totales_generales);

	$responsable = false;
	$secundario = false;
	$retribucion_encabezado = array();
	foreach ($encabezados as $id_cobro => $encabezado) {
		$encabezado = reset($encabezado);

		if (!empty($encabezado['id_usuario_responsable']) && !empty($encabezado['retribucion_usuario_responsable']) &&
			(empty($usuarios) || in_array($encabezado['id_usuario_responsable'], $usuarios))) {
			$retribucion_encabezado[$encabezado['id_usuario_responsable']][$id_cobro]['responsable'] = $encabezado['retribucion_usuario_responsable'] * $encabezado['monto_trabajos'] / 100;
			$responsable = true;
		}
		if (!empty($encabezado['id_usuario_secundario']) && !empty($encabezado['retribucion_usuario_secundario']) &&
			(empty($usuarios) || in_array($encabezado['id_usuario_secundario'], $usuarios))) {
			$retribucion_encabezado[$encabezado['id_usuario_secundario']][$id_cobro]['secundario'] = $encabezado['retribucion_usuario_secundario'] * $encabezado['monto_trabajos'] / 100;
			$secundario = true;
		}
	}
	?>

	<table>
		<tr class='header-labels'>
			<th colspan='2'></th>
			<th colspan='3'><?php echo __('Distribución de la Retribución'); ?></th>
			<th class='number-cell'>% <br/><?php echo __('Retribución'); ?></th>
			<?php if ($tipo_calculo == 'duracion_cobrada') { ?>
				<th class='number-cell'><?php echo __('Horas<br/>Cobradas'); ?></th>
			<?php } else { ?>
				<th class='number-cell'><?php echo __('Valor<br/>Cobrado'); ?></th>
			<?php } ?>
			<th class='number-cell'><?php echo __('Retribución') . '<br/>' . __('Socios') . "&nbsp;($porcentaje_retribucion_socios%)"; ?></th>
			<th class='number-cell'><?php echo __('Retribución') . '<br/>' . __('Abogados'); ?></th>
			<?php if ($responsable) { ?>
				<th class='number-cell'><?php echo __('Retribución') . '<br/>' . __('Encargado Comercial'); ?></th>
			<?php } if (!empty($secundario)) { ?>
				<th class='number-cell'><?php echo __('Retribución') . '<br/>' . __('Encargado Secundario'); ?></th>
			<?php } ?>
		</tr>

		<?php
		foreach ($detalles as $retribucion) {
			$encabezado = $encabezados[$retribucion['id_cobro']][0];
			if ($last_record && $last_record['id_usuario'] != $retribucion['id_usuario']) {
				acumula_todo($last_record, $retribucion_encabezado);
				imprime_registro($last_record['nombre'], $totales_usuario, 'detail', true);
				limpia_valores($totales_usuario);
				imprime_detalle_cobros();
			}
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

			if ($tipo_calculo == 'duracion_cobrada') {
				$porcentaje_intervencion = $encabezado['total_minutos'] ? $retribucion['minutos_cobrados'] / $encabezado['total_minutos'] * 100 : 0;
			} else {
				$porcentaje_intervencion = $encabezado['monto_trabajos'] ? $retribucion['monto_cobrado'] * $rentabilidad / $encabezado['monto_trabajos'] * 100 : 0;
			}

			$monto_responsable = 0;
			$monto_secundario = 0;
			if (isset($retribucion_encabezado[$retribucion['id_usuario']][$retribucion['id_cobro']])) {
				$monto_responsable = (isset($retribucion_encabezado[$retribucion['id_usuario']][$retribucion['id_cobro']]['responsable']) ?
						$retribucion_encabezado[$retribucion['id_usuario']][$retribucion['id_cobro']]['responsable'] : 0);

				$monto_secundario = (isset($retribucion_encabezado[$retribucion['id_usuario']][$retribucion['id_cobro']]['secundario']) ?
						$retribucion_encabezado[$retribucion['id_usuario']][$retribucion['id_cobro']]['secundario'] : 0);

				unset($retribucion_encabezado[$retribucion['id_usuario']][$retribucion['id_cobro']]);
			}

			$valores = array("porcentaje_intervencion" => $porcentaje_intervencion,
				"portentaje_retribucion_abogado" => $retribucion['porcentaje_retribucion'],
				"retribucion_socios" => $encabezado['monto_trabajos'] * $porcentaje_intervencion * $porcentaje_retribucion_socios / (100 * 100),
				"retribucion_abogado" => $encabezado['monto_trabajos'] * $porcentaje_intervencion * $retribucion['porcentaje_retribucion'] / (100 * 100),
				"tiempo_cobrado" => $retribucion['minutos_cobrados'],
				"monto_cobrado" => ($retribucion['monto_cobrado'] * $rentabilidad),
				"retribucion_responsable" => $monto_responsable,
				"retribucion_secundario" => $monto_secundario,
				"user_id" => $retribucion['id_usuario'],
				"id_cobro" => $retribucion['id_cobro']
			);

			acumula_detalle_cobros($valores);

			$last_record = $retribucion;
			acumula_subtotal($valores, $totales_usuario);
			acumula_subtotal($valores, $subtotales);
			acumula_subtotal($valores, $totales);
			acumula_subtotal($valores, $totales_generales);
		}
		acumula_todo($last_record, $retribucion_encabezado);
		imprime_registro($last_record['nombre'], $totales_usuario, 'detail', true);
		imprime_detalle_cobros();
		genera_subtotal($last_record['glosa_area'], $subtotales, $subtotalizar);
		genera_total($last_record['glosa_area_padre'], $totales);
		genera_total_general($totales_generales);
		?>

	</table>

	<?php if($retribuidos == 'NO'){ ?>
		<br/>
		<button id="marcar_retribuido"><?php echo __('Marcar montos como retribuidos'); ?></button>
	<?php
	}
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
	jQuery(function() {
		jQuery('.ver-detalle').click(function(){
			jQuery(this).closest('tbody').next().toggle();
			return false;
		});

		jQuery('#marcar_retribuido').click(function(){
			if(confirm('<?php echo __('Se van a marcar todos los montos del reporte actual como retribuidos, y no volverán a aparecer al filtrar sólo montos sin retribuir.\n¿Está seguro que desea continuar?'); ?>')){
				jQuery('#opc').val('marcar_retribuido');
				jQuery('form').submit();
			}
			return false;
		});
	});
</script>
<?php
$pagina->PrintBottom();
?>