<?php
require_once dirname(dirname(__FILE__)) . '/conf.php';

$sesion = new Sesion(array('REP'));
//Revisa el Conf si esta permitido
if (!Conf::GetConf($sesion, 'ReportesAvanzados')) {
	header("location: reportes_especificos.php");
}

$dias_semana = array(
	__('No enviar'),
	__('Lunes'),
	__('Martes'),
	__('Miércoles'),
	__('Jueves'),
	__('Viernes'),
	__('Sábado'),
	__('Domingo')
);

$dias_mes = array(__('No enviar'));
for ($i = 1; $i <= 30; ++$i) {
	$dias_mes[] = sprintf('%s %02d %s', __('día'), $i, __('del mes'));
}

$meses = array(
	1 => __('Enero'),
	2 => __('Febrero'),
	3 => __('Marzo'),
	4 => __('Abril'),
	5 => __('Mayo'),
	6 => __('Junio'),
	7 => __('Julio'),
	8 => __('Agosto'),
	9 => __('Septiembre'),
	10 => __('Octubre'),
	11 => __('Noviembre'),
	12 => __('Diciembre')
);

$anios = array();
for ($i = (date('Y') - 5); $i < (date('Y') + 5); ++$i) {
	$anios[$i] = $i;
}

$pagina = new Pagina($sesion);
$Form = new Form();
$id_usuario = $sesion->usuario->fields['id_usuario'];

$mis_reportes = array();
$query_mis_reportes = "SELECT reporte, glosa, segun, envio, id_reporte FROM usuario_reporte WHERE id_usuario = '$id_usuario'";
$resp_mis_reportes = mysql_query($query_mis_reportes, $sesion->dbh) or Utiles::errorSQL($query_mis_reportes, __FILE__, __LINE__, $sesion->dbh);
while ($reporte_encontrado = mysql_fetch_assoc($resp_mis_reportes)) {
	$mis_reportes[] = $reporte_encontrado;
}

/* REPORTE AVANZADO. ESTA PANTALLA SOLO TIENE INPUTS DEL USUARIO. SUBMIT LLAMA AL TIPO DE REPORTE SELECCIONADO */
$pagina->titulo = __('Resumen actividades profesionales');

$tipos_de_dato = array(
	'horas_trabajadas',
	'horas_cobrables',
	'horas_no_cobrables',
	'horas_castigadas',
	'horas_visibles',
	'horas_cobradas',
	'horas_por_cobrar',
	'horas_pagadas',
	'horas_por_pagar',
	'horas_incobrables',
	'valor_cobrado',
	'valor_por_cobrar',
	'valor_pagado',
	'valor_por_pagar',
	'valor_incobrable',
	'rentabilidad',
	'valor_hora',
	'diferencia_valor_estandar',
	'valor_estandar',
	'valor_trabajado_estandar',
	'rentabilidad_base',
	'costo',
	'costo_hh'
);

if ($debug == 1) {
	$tipos_de_dato[] = 'valor_pagado_parcial';
	$tipos_de_dato[] = 'valor_por_pagar_parcial';
}

$tipos_de_dato_select = array();
foreach ($tipos_de_dato as $tipo) {
	$tipos_de_dato_select[$tipo] = __($tipo);
}

$otros = array(
	'codigo_contrato' => 'Código ' . __('Contrato'),
	'usuarios' => __('Profesional'),
	'clientes' => __('Cliente'),
	'Semana pasada' => __('Semana pasada'),
	'Mes pasado' => __('Mes pasado'),
	'Año en curso' => __('Año en curso'),
	'Trabajo' => __('Trabajo'),
	'Corte' => __('Corte'),
	'Facturacion' => __('Facturación'),
	'Envío' => __('Envío'),
	'Emisión' => __('Emisión')
);

$estados_cobro = array(
	'CREADO',
	'EMITIDO',
	'EN REVISION',
	'ENVIADO AL CLIENTE',
	'FACTURADO',
	'INCOBRABLE',
	'PAGADO',
	'PAGO PARCIAL'
);

if (Conf::GetConf($sesion, 'CodigoSecundario')) {
	$agrupadores = array(
		'glosa_cliente',
		'codigo_cliente_secundario',
		'codigo_asunto',
		'glosa_asunto_con_codigo',
		'profesional',
		'estado',
		'id_cobro',
		'forma_cobro',
		'tipo_asunto',
		'area_asunto',
		'categoria_usuario',
		'area_usuario',
		'dia_emision',
		'glosa_grupo_cliente',
		'id_usuario_responsable',
		'mes_reporte',
		'dia_reporte',
		'mes_emision',
		'grupo_o_cliente',
		'solicitante'
	);
} else {
	$agrupadores = array(
		'glosa_cliente',
		'codigo_cliente',
		'codigo_asunto',
		'glosa_asunto_con_codigo',
		'profesional',
		'estado',
		'id_cobro',
		'forma_cobro',
		'tipo_asunto',
		'area_asunto',
		'categoria_usuario',
		'area_usuario',
		'dia_emision',
		'glosa_grupo_cliente',
		'id_usuario_responsable',
		'mes_reporte',
		'dia_reporte',
		'mes_emision',
		'grupo_o_cliente',
		'solicitante'
	);
}

if (Conf::GetConf($sesion, 'UsarAreaTrabajos')) {
	$agrupadores[] = 'area_trabajo';
}

if (Conf::GetConf($sesion, 'EncargadoSecundario')) {
	$agrupadores[] = 'id_usuario_secundario';
}

if ($debug == 1) {
	$agrupadores[] = 'id_trabajo';
	$agrupadores[] = 'dia_corte';
	$agrupadores[] = 'id_contrato';
}

$ReporteAvanzado = new ReporteAvanzado($sesion);

$ReporteAvanzado->comparar = $comparar;
$ReporteAvanzado->tipo_dato_comparado = $tipo_dato_comparado;
$ReporteAvanzado->tipo_dato = empty($tipo_dato) ? null : $tipo_dato;
$ReporteAvanzado->proporcionalidad = $proporcionalidad;
$ReporteAvanzado->id_moneda = $id_moneda;

$ReporteAvanzado->glosa_dato['codigo_asunto'] = "Código " . __('Asunto');
$ReporteAvanzado->glosa_dato['horas_trabajadas'] = "Total de Horas Trabajadas";
$ReporteAvanzado->glosa_dato['horas_cobrables'] = __("Total de Horas Trabajadas en asuntos Facturables");
$ReporteAvanzado->glosa_dato['horas_no_cobrables'] = __("Total de Horas Trabajadas en asuntos no Facturables");
$ReporteAvanzado->glosa_dato['horas_castigadas'] = __("Diferencia de Horas Cobrables con las Horas que ve el cliente en nota de Cobro");
$ReporteAvanzado->glosa_dato['horas_visibles'] = __("Horas que ve el Cliente en nota de cobro (tras revisión)");
$ReporteAvanzado->glosa_dato['horas_cobradas'] = __("Horas Visibles en Cobros que ya fueron Emitidos");
$ReporteAvanzado->glosa_dato['horas_por_cobrar'] = "Horas Visibles que aún no se Emiten al Cliente";
$ReporteAvanzado->glosa_dato['horas_pagadas'] = __("Horas Cobradas en Cobros con estado Pagado");
$ReporteAvanzado->glosa_dato['horas_por_pagar'] = __("Horas Cobradas que aún no han sido pagadas");
$ReporteAvanzado->glosa_dato['horas_incobrables'] = __("Horas en Cobros Incobrables");
$ReporteAvanzado->glosa_dato['valor_por_cobrar'] = __("Valor monetario estimado que corresponde a cada Profesional en horas por cobrar");
$ReporteAvanzado->glosa_dato['valor_cobrado'] = __("Valor monetario que corresponde a cada Profesional, en un Cobro ya Emitido");
$ReporteAvanzado->glosa_dato['valor_incobrable'] = __("Valor monetario que corresponde a cada Profesional, en un Cobro Incobrable");
$ReporteAvanzado->glosa_dato['valor_pagado'] = __("Valor Cobrado que ha sido Pagado");
$ReporteAvanzado->glosa_dato['valor_por_pagar'] = __("Valor Cobrado que aún no ha sido pagado");
$ReporteAvanzado->glosa_dato['rentabilidad'] = __("Valor Cobrado / Valor Estándar");
$ReporteAvanzado->glosa_dato['valor_hora'] = __("Valor Cobrado / Horas Cobradas");
$ReporteAvanzado->glosa_dato['diferencia_valor_estandar'] = __("Valor Cobrado - Valor Estándar");
$ReporteAvanzado->glosa_dato['valor_estandar'] = __("Valor Cobrado, si se hubiera usado THH Estándar");
$ReporteAvanzado->glosa_dato['valor_trabajado_estandar'] = __("Horas Trabajadas por THH Estándar, para todo Trabajo");
$ReporteAvanzado->glosa_dato['rentabilidad_base'] = __("Valor Cobrado / Valor Trabajado Estándar");
$ReporteAvanzado->glosa_dato['costo'] = __("Costo para la firma, por concepto de sueldos");
$ReporteAvanzado->glosa_dato['costo_hh'] = __("Costo HH para la firma, por concepto de sueldos");

$glosa_boton['planilla'] = "Despliega una Planilla con deglose por cada Agrupador elegido.";
$glosa_boton['excel'] = "Genera la Planilla como un Documento Excel.";
$glosa_boton['tabla'] = "Genera un Documento Excel con una tabla cruzada.";
$glosa_boton['barra'] = "Despliega un Gráfico de Barras, usando el primer Agrupador.";
$glosa_boton['torta'] = "Despliega un Gráfico de Torta, usando el primer Agrupador.";
$glosa_boton['dispersion'] = "Despliega un Gráfico de Dispersión, usando el primer Agrupador.";

$explica_periodo_trabajo = 'Incluye todo Trabajo con fecha en el Periodo';
$explica_periodo_cobro = 'Sólo considera Trabajos en Cobros con fecha de corte en el Periodo';
$explica_periodo_emision = 'Sólo considera Trabajos en Cobros con fecha de emisión en el Periodo';
$explica_periodo_envio = 'Sólo considera Trabajos en Cobros con fecha de envío en el Periodo';
$explica_periodo_facturacion = 'Sólo considera Trabajos en Cobros con fecha de facturación en el Periodo';

/* Calculos de fechas */
$hoy = date("Y-m-d");
if (!$fecha_anio) {
	$fecha_anio = date('Y');
}

if (!$fecha_mes) {
	$fecha_mes = date('m');
}

/* Genero fecha_ini,fecha_fin para la semana pasada y el mes pasado. */
$week = date('W');
$year = date('Y');
$lastweek = $week - 1;

if ($lastweek == 0) {
	$lastweek = 52;
	--$year;
}

$last_week = sprintf("%02d", $lastweek);
$last_month = strtotime("-" . (date('j')) . " day");

$selector_periodos = array(
	'semana_pasada' => array(
		date('d-m-Y', strtotime("{$year}W{$last_week}1")),
		date('d-m-Y', strtotime("{$year}W{$last_week}7"))
	),
	'mes_pasado' => array(
		'01' . date('-m-Y', $last_month),
		date('d-m-Y', $last_month)
	),
	'actual' => array(
		'01-01-' . date('Y'),
		date('d-m-Y')
	)
);

if (!isset($numero_agrupadores)) {
	$numero_agrupadores = 1;
}

if (!$popup) {
	$pagina->PrintTop($popup);
	?>
	<style type="text/css">
		td.boton_tipo_dato {
			cursor:pointer;
		}
		td.boton_tipo_dato,
		td.boton_disabled{
			width:100px;
			height:25px;
			font-size: 11px;
			vertical-align: middle;
			text-align: center;
			border: solid 2px;
		}
		td.boton_tipo_dato {
			background-color: #e0ffe0;
		}
		td.boton_normal {
			border-color: #e0ffe0;
		}
		td.boton_presionado {
			border-color: red;
		}
		td.boton_comparar {
			border-color: blue;
		}
		td.boton_disabled {
			border-color: #e5e5e5;
			background-color: #e5e5e5;
			color: #444444;
			cursor: default;
		}
		td.borde_rojo {
			border: solid 1px red;
		}
		td.borde_azul {
			border: solid 1px blue;
		}
		td.borde_blanco {
			border: solid 1px white;
		}
		input.btn{
			margin:3px;
		}
		.borde_abajo {
			width:10px;
			font-size: 3px;
			border-bottom-style: dotted;
			border-width: 1px;
		}
		.borde_derecha {
			font-size: 3px;
			width:10px;
			border-right-style: dotted;
			border-width: 1px;
		}
		.nada {
			font-size: 3px;
			width:10px;
			height:7px;
		}
		span.rojo,
		span.azul {
			display: inline-block;
			width: 1.5em;
			height: 1.5em;
		}
		span.rojo {
			background: #F00;
		}
		span.azul {
			background: #00F;
		}
		.agrupador {
			font-size: 10px;
			margin-top: 2px;
			margin-bottom: 2px;
			margin-left:6px;
			width:110px;
		}
	</style>

	<script type="text/javascript" src="<?php echo Conf::RootDir(); ?>/app/js/reporte_avanzado.js"></script>
	<script type="text/javascript">
		var selector_periodos = <?php echo json_encode($selector_periodos); ?>;
		var urlAjaxReporteAvanzado = '<?php echo Conf::RootDir(); ?>/app/interfaces/ajax/reporte_avanzado.php';
		var buttonsReporte = {
			'<?php echo __('Guardar') ?>': GuardarReporte,
			'<?php echo __('Cancelar') ?>': function() {
				jQuery(this).dialog('close');
			}
		};

		/*Traduce los codigos utilizados para mostrarlos al usuario*/
		function __(s) {
			switch (s) {
				<?php
				$_langs = array_merge($tipos_de_dato, $agrupadores);
				foreach ($_langs as $td) {
					printf("case '%s': return '%s';\n", $td, __($td));
				}
				foreach ($otros as $k => $v) {
					printf("case '%s': return '%s';\n", $k, $v);
				}
				?>
				default:
					return s;
			}
		}

	</script>
	<?php
}
?>

<form method="post" name="formulario" action="" id="formulario" autocomplete="off">
	<input type="hidden" name="opc" id="opc" value="print">
	<input type="hidden" name="debug" id="debug" value="<?php echo $debug; ?>">
	<?php if (!$popup) { ?>
		<!-- Calendario DIV -->
		<div id="calendar-container" style="width:221px; position:absolute; display:none;">
			<div class="floating" id="calendar"></div>
		</div>
		<!-- Fin calendario DIV -->

		<!-- MIS REPORTES -->
		<table width="90%">
			<tr>
				<td align="center">
					<fieldset width="100%" class="border_plomo tb_base" align="center">
						<legend><?php echo __('Mis Reportes') ?></legend>
						<div>
							<div style="float:right" align=right>
								<?php echo $Form->button(__('Nuevo Reporte'), array('onclick' => 'NuevoReporte()')); ?>
							</div>
							<div>
								<select name="mis_reportes_elegido" id="mis_reportes"  >
									<option value="0"><?php echo __('Seleccione Reporte...') ?></option>
									<?php
									$estilo_eliminar_reporte = 'style="display:none"';
									if (empty($mis_reportes)) {
										echo '<option value="0">-- ' . __('No se han agregado reportes') . '. --</option>';
									} else {
										$j = 1;
										foreach ($mis_reportes as $indice_reporte => $mi_reporte) {
											$selected_mi_reporte = '';
											if ($mi_reporte['reporte'] == $nuevo_reporte || $mis_reportes_elegido == $mi_reporte['reporte']) {
												$selected_mi_reporte = 'selected="selected"';
												$estilo_eliminar_reporte = '';
											}
											$glosa = $mi_reporte['glosa'];
											if (empty($glosa)) {
												$glosa = "Reporte $indice_reporte";
											}

											$glosa = sprintf('%02d) %s', $j, $glosa);
											if (is_null(json_decode($mi_reporte['reporte']))) {
												$tpl_option = '<option %s data-reporte="%s" value="%s" data-envio="%s" data-segun="%s" data-glosa="%s">%s</option>' . "\n";
												printf($tpl_option, $selected_mi_reporte, $mi_reporte['reporte'], $mi_reporte['id_reporte'], $mi_reporte['envio'], $mi_reporte['segun'], $mi_reporte['glosa'], $glosa);
											} else {
												$tpl_option = '<option %s data-reporte="%s" value="%s" data-glosa="%s">%s</option>' . "\n";
												printf($tpl_option, $selected_mi_reporte, str_replace('"', "'", $mi_reporte['reporte']), $mi_reporte['id_reporte'], $mi_reporte['glosa'], $glosa);
											}
											++$j;
										}
									}
									?>
								</select>
								<span id="span_editar_reporte" <?php echo $estilo_eliminar_reporte ?> >&nbsp;<a style='color:#009900' href="javascript:void(0)" onclick="EditarReporte();"><?php echo __('Editar') ?></a></span>&nbsp;
								<span id="span_eliminar_reporte" <?php echo $estilo_eliminar_reporte ?> >&nbsp;<a style='color:#CC1111' href="javascript:void(0)" onclick="EliminarReporte();"><?php echo __('Eliminar') ?></a></span>
								<input type=hidden name='nuevo_reporte' id='nuevo_reporte' />
								<input type=hidden name='nuevo_reporte_envio' id='nuevo_reporte_envio' />
								<input type=hidden name='nuevo_reporte_segun' id='nuevo_reporte_segun' />
							</div>

							<div id="div_nuevo_reporte" style="display:none;">
								<div style="display:none;">
									<span id="label_nuevo_reporte"><?php echo __('Nuevo Reporte') ?></span>
									<span id="label_editar_reporte"><?php echo __('Editar Reporte') ?></span>
								</div>
								<table style="width: 100%" id="div_nuevo_reporte_text">
									<tbody>
										<tr>
											<td align=right><?php echo __("Nombre") ?>:</td>
											<td>
												<input type="text" name="nombre_reporte" id="nombre_reporte"/>
												<input type="hidden" name="id_reporte_editado" id="id_reporte_editado" value="0"/>
											</td>
										</tr>
										<tr>
											<td align=right><?php echo __("Tipos de Datos") ?>:</td>
											<td><span id="tipos_datos_nuevo_reporte"></span></td>
										</tr>
										<tr>
											<td align=right><?php echo __("Agrupar por") ?>:</td>
											<td><span id="agrupadores_nuevo_reporte"></span></td>
										</tr>
										<tr>
											<td  align=right><?php echo __("Periodo") ?>:</td>
											<td><span id="periodo_nuevo_reporte"></span></td>
										</tr>
										<tr>
											<td  align=right><?php echo __("Según") ?>:</td>
											<td><span id="segun_nuevo_reporte"></span></td>
										</tr>
										<tr id = 'reporte_envio' style="display: none">
											<td align=right><?php echo __('Enviar cada') ?>:</td>
											<td>
												<span id='reporte_envio_selector' style="<?php echo $fecha_corta == 'selector' || !$fecha_corta ? '' : 'display:none;' ?>" ><i><?php echo __("Debe seleccionar un periodo de reporte") ?>.</i></span>
												<span id='reporte_envio_semana' style="<?php echo $fecha_corta == 'semanal' ? '' : 'display:none;' ?>">
													<?php echo Html::SelectArrayDecente($dias_semana, 'reporte_envio_semana', 0, 'id="select_reporte_envio_semana"', '', '90px'); ?>
												</span>
												<span id='reporte_envio_mes' style="<?php echo $fecha_corta == 'mensual' || $fecha_corta == 'anual' ? '' : 'display:none;' ?>">
													<?php echo Html::SelectArrayDecente($dias_mes, 'reporte_envio_mes', 0, 'id="select_reporte_envio_mes"', '', '90px'); ?>
												</span>
												<br>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</fieldset>

					<!-- SELECTOR DE FILTROS -->
					<fieldset width="100%" class="border_plomo tb_base" align="center">
						<legend id="fullfiltrostoggle" style="cursor:pointer">
							<span id="filtros_img"><img src= "<?php echo Conf::ImgDir() ?><?php echo $filtros_check ? '/menos.gif' : '/mas.gif' ?>" border="0" ></span>
							<?php echo __('Filtros') ?>
						</legend>
						<input type="checkbox" name="filtros_check" id="filtros_check" value="1" <?php echo $filtros_check ? 'checked' : '' ?> style="display:none;" />
						<center>
							<table id="mini_filtros"   style=" width:95%; <?php echo $filtros_check ? 'display:none' : '' ?> " cellpadding="0" cellspacing="3" >
								<tr valign="top">
									<td style="width:470px;"  rowspan="7">
										<div id="filtrosimple">
											<div id="profesional" style="float:left;display:inline-block;" >
												<b><?php echo __('Profesional') ?>:</b><br/>
												<?php
												$query = "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC";
												echo Html::SelectQuery($sesion, $query, "usuarios[]", $usuarios, '', "Todos", "200");
												?>
											</div>
											<div id="cliente" style="float:right;padding-right:10px;display:inline-block;" >
												<b><?php echo __('Cliente') ?>:</b><br/>
												<?php
												$query = 'SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC';
												echo Html::SelectQuery($sesion, $query, "clientes[]", $clientes, '', "Todos", "200");
												?>
											</div>
										</div>

										<!-- SELECTOR FILTROS EXPANDIDO -->
										<?php
										$largo_select = 6;

										if (Conf::GetConf($sesion, 'ReportesAvanzados_FiltrosExtra')) {
											$filtros_extra = true;
											$largo_select = 11;
										}
										?>

										<div id="full_filtros" style="<?php echo $filtros_check ? '' : 'display:none;' ?> ">
											<table>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_clientes" id="check_clientes" value="1" onchange="$$('.cliente_full').invoke('toggle')" <?php echo $check_clientes ? 'checked' : '' ?> />
														<label for="check_clientes">
															<b><?php echo __('Clientes') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'cliente_full' style='width:200px;<?php echo $check_clientes ? "display:none;" : "" ?>'>
															<label for="check_clientes" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'cliente_full' style="<?php echo $check_clientes ? "" : "display:none;" ?>">
															<?php echo Html::SelectQuery($sesion, "SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientesF[]", $clientesF, "class=\"selectMultiple\" multiple size=" . $largo_select . " ", "", "300"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_profesionales" id="check_profesionales" value="1" onchange="$$('.prof_full').invoke('toggle')" <?php echo $check_profesionales ? 'checked' : '' ?> />
														<label for="check_profesionales">
															<b><?php echo __('Profesionales') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'prof_full' style='width:200px;<?php echo $check_profesionales ? "display:none;" : "" ?>'>
															<label for="check_profesionales" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'prof_full' style="<?php echo $check_profesionales ? "" : "display:none;" ?>">
															<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuariosF[]", $usuariosF, "class=\"selectMultiple\" multiple size=" . $largo_select . " ", "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_encargados" id="check_encargados" value="1" onchange="$$('.encargados_full').invoke('toggle')" <?php echo $check_encargados ? 'checked' : '' ?> />
														<label for="check_encargados">
															<b><?php echo __('Encargado Comercial') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'encargados_full' style='width:200px;<?php echo $check_encargados ? "display:none;" : "" ?>'>
															<label for="check_encargados" style="cursor:pointer;" ><hr></label>
														</div>
														<div class = 'encargados_full' style="<?php echo $check_encargados ? "" : "display:none;" ?>" >
															<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "encargados[]", $encargados, "class=\"selectMultiple\" multiple size=" . $largo_select . " ", "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_area_prof" id="check_area_prof" value="1" onchange="$$('.area_prof_full').invoke('toggle')" <?php echo $check_area_prof ? 'checked' : '' ?> />
														<label for="check_area_prof">
															<b><?php echo __('Área Profesional') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class='area_prof_full' style='width:200px;<?php echo $check_area_prof ? "display:none;" : "" ?>'>
															<label for="check_area_prof" style="cursor:pointer"><hr></label>
														</div>
														<div class='area_prof_full' style="<?php echo $check_area_prof ? "" : "display:none;" ?>">
															<?php echo AreaUsuario::SelectAreas($sesion, "areas[]", $areas, 'class="selectMultiple" multiple="multiple" size="4" ', "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_cat_prof" id="check_cat_prof" value="1" onchange="$$('.cat_prof_full').invoke('toggle')" <?php echo $check_cat_prof ? 'checked' : '' ?> />
														<label for="check_cat_prof">
															<b><?php echo __('Categoría Profesional') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'cat_prof_full' style='width:200px;<?php echo $check_cat_prof ? "display:none;" : "" ?>'>
															<label for="check_cat_prof" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'cat_prof_full' style="<?php echo $check_cat_prof ? "" : "display:none;" ?>">
															<?php echo Html::SelectQuery($sesion, "SELECT id_categoria_usuario, glosa_categoria FROM prm_categoria_usuario ORDER BY glosa_categoria", "categorias[]", $categorias, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_area_asunto" id="check_area_asunto" value="1" onchange="$$('.area_asunto_full').invoke('toggle')" <?php echo $check_area_asunto ? 'checked' : '' ?> />
														<label for="check_area_asunto">
															<b><?php echo __('Área de Asunto') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'area_asunto_full' style='width:200px;<?php echo $check_area_asunto ? "display:none;" : "" ?>'>
															<label for="check_area_asunto" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'area_asunto_full' style="<?php echo $check_area_asunto ? "" : "display:none;" ?>" >
															<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_area_proyecto", "areas_asunto[]", $areas_asunto, "class=\"selectMultiple\" multiple size=5 ", "", "200"); ?>
														</div>
													</td>
												</tr>
												<?php if ($filtros_extra) { ?>
													<tr valign=top>
														<td align=right>
															<input type="checkbox" name="check_tipo_asunto" id="check_tipo_asunto" value="1" onchange="$$('.tipo_asunto_full').invoke('toggle')" <?php echo $check_tipo_asunto ? 'checked' : '' ?> />
															<label for="check_tipo_asunto">
																<b><?php echo __('Tipo de Asunto') ?>:&nbsp;&nbsp;</b>
															</label>
														</td>
														<td align=left>
															<div class = 'tipo_asunto_full' style='width:200px;<?php echo $check_tipo_asunto ? "display:none;" : "" ?>'>
																<label for="check_tipo_asunto" style="cursor:pointer;" ><hr></label>
															</div>
															<div class = 'tipo_asunto_full' style="<?php echo $check_tipo_asunto ? "" : "display:none;" ?>" >
																<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_proyecto", "tipos_asunto[]", $tipos_asunto, "class=\"selectMultiple\" multiple size=5 ", "", "200"); ?>
															</div>
														</td>
													</tr>
												<?php } ?>

												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_estado_cobro" id="check_estado_cobro" value="1" onchange="$$('.estado_cobro_full').invoke('toggle')" <?php echo $check_estado_cobro ? 'checked' : '' ?> />
														<label for="check_estado_cobro">
															<b><?php echo __('Estado de Cobro') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'estado_cobro_full' style='width:200px;<?php echo $check_estado_cobro ? "display:none;" : "" ?>'>
															<label for="check_estado_cobro" style="cursor:pointer;" ><hr></label>
														</div>
														<div class = 'estado_cobro_full' style="<?php echo $check_estado_cobro ? "" : "display:none;" ?>" >
															<select name='estado_cobro[]' id='estado_cobro[]' class="SelectMultiple" multiple="multiple" size="8" style="width:200px" >
																<?php foreach ($estados_cobro as $ec) { ?>
																	<option value="<?php echo $ec ?>" <?php if ($estado_cobro) if (in_array($ec, $estado_cobro)) echo "selected";  ?> ><?php echo __($ec) ?></option>
																<?php } ?>
															</select>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_moneda_contrato" id="check_moneda_contrato" value="1" onchange="$$('.moneda_contrato_full').invoke('toggle')" <?php echo $check_moneda_contrato ? 'checked' : '' ?> />
														<label for="check_moneda_contrato">
															<b><?php echo __('Moneda del Contrato') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'moneda_contrato_full' style='width:200px;<?php echo $check_moneda_contrato ? "display:none;" : "" ?>'>
															<label for="check_moneda_contrato" style="cursor:pointer;" ><hr></label>
														</div>
														<div class = 'moneda_contrato_full' style="<?php echo $check_moneda_contrato ? "" : "display:none;" ?>" >
															<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda", "moneda_contrato[]", $moneda_contrato, "class=\"selectMultiple\" multiple size=5 ", "", "200"); ?>
														</div>
													</td>
												</tr>
											</table>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<table>
											<tr>
												<td align="center" colspan="2">
													<b><?php echo __('Periodo') ?>:</b>
												</td>
												<td align="center" colspan="2" >
													<b><?php echo __('Según') ?>:</b>
												</td>
											</tr>
											<tr>
												<td align=right>
													<input type="radio" name="fecha_corta" id="fecha_corta_semana" value="semanal" <?php if ($fecha_corta == 'semanal') echo 'checked="checked"'; ?>  />
												</td>
												<td align=left>
													<label for="fecha_corta_semana"><?php echo __("Semana pasada") ?></label>
												</td>
												<td align=right>
													<span title="<?php echo __($explica_periodo_trabajo) ?>">
														<input type="radio" name="campo_fecha" id="campo_fecha_trabajo" value="trabajo"
														<?php if ($campo_fecha == 'trabajo' || $campo_fecha == '') echo 'checked="checked"'; ?>
															   onclick ="SincronizarCampoFecha()" />
													</span>
												</td>
												<td align=left>
													<label for="campo_fecha_trabajo"  title="<?php echo __($explica_periodo_trabajo) ?>"><?php echo __("Trabajo") ?></label>
												</td>
											</tr>
											<tr>
												<td align=right>
													<input type="radio" name="fecha_corta" id="fecha_corta_mes" value="mensual" <?php if ($fecha_corta == 'mensual') echo 'checked="checked"'; ?> />
												</td>
												<td align=left>
													<label for="fecha_corta_mes"><?php echo __("Mes pasado") ?></label>
												</td>
												<td align=right>
													<span title="<?php echo __($explica_periodo_cobro) ?>">
														<input type="radio" name="campo_fecha" id="campo_fecha_cobro" value="cobro"
														<?php
														if ($campo_fecha == 'cobro') {
															echo 'checked="checked"';
														}
														?>
															   onclick ="SincronizarCampoFecha()" />
													</span>
												</td>
												<td align=left>
													<label for="campo_fecha_cobro" title=""><?php echo __("Corte") ?></label>
												</td>
											</tr>
											<tr>
												<td align=right>
													<input type="radio" name="fecha_corta" id="fecha_corta_anual" value="anual" <?php if ($fecha_corta == 'anual') echo 'checked="checked"' ?> />
												</td>
												<td align=left>
													<label for="fecha_corta_anual"><?php echo __("Año en curso") ?></label>
												</td>
												<td align=right>
													<span title="<?php echo __($explica_periodo_emision) ?>">
														<input type="radio" name="campo_fecha" id="campo_fecha_emision" value="emision"
														<?php
														if ($campo_fecha == 'emision') {
															echo 'checked="checked"';
														}
														?>
															   onclick ="SincronizarCampoFecha()" />
													</span>
												</td>
												<td align=left>
													<label for="campo_fecha_emision" title="<?php echo __($explica_periodo_emision) ?>"><?php echo __("Emisión") ?></label>
												</td>
											</tr>
											<tr>
												<td align="right">
													&nbsp;
												</td>
												<td align="left">
													&nbsp;
												</td>
												<td align="right">
													<span title="<?php echo __($explica_periodo_envio) ?>">
														<input type="radio" name="campo_fecha" id="campo_fecha_envio" value="envio"
														<?php
														if ($campo_fecha == 'envio') {
															echo 'checked="checked"';
														}
														?>
															   onclick="SincronizarCampoFecha()" />
													</span>
												</td>
												<td align="left">
													<label title="<?php echo __($explica_periodo_envio) ?>" for="campo_fecha_envio"><?php echo __('Envio'); ?></label>
												</td>
											</tr>
											<tr>
												<td align="right">
													&nbsp;
												</td>
												<td align="left">
													&nbsp;
												</td>
												<td align="right">
													<span title="<?php echo __($explica_periodo_facturacion) ?>">
														<input type="radio" name="campo_fecha" id="campo_fecha_facturacion" value="facturacion"
														<?php
														if ($campo_fecha == 'facturacion') {
															echo 'checked="checked"';
														}
														?>
															   onclick="SincronizarCampoFecha()" />
													</span>
												</td>
												<td align="left">
													<label title="<?php echo __($explica_periodo_facturacion) ?>" for="campo_fecha_facturacion"><?php echo __('Facturación'); ?></label>
												</td>
											</tr>
											<tr>
												<td align=right>
													<input type="radio" name="fecha_corta" id="fecha_corta_selector" value="selector" <?php if ($fecha_corta == 'selector' || !$fecha_corta) echo 'checked="checked"'; ?> />
												</td>
												<td align=left colspan=3>
													<span onclick="jQuery('#fecha_corta_selector').click()">
														<?php echo Html::SelectArrayDecente($meses, 'fecha_mes', $fecha_mes, 'id="fecha_mes"', '', '90px'); ?>
														<?php echo Html::SelectArrayDecente($anios, 'fecha_anio', $fecha_anio, 'id="fecha_anio"', '', '55px'); ?>
													</span>
												</td>
											</tr>
											<tr>
												<!-- PERIODOS -->
												<td align=right>
													<input type="radio" name="fecha_corta" id="fecha_periodo" value="selector" <?php if ($fecha_corta == 'selector' || !$fecha_corta) echo 'checked="checked"'; ?> />
												</td>
												<td align="left" colspan="3">
													<div id="periodo_rango">
														<input type="text" name="fecha_ini" class="fechadiff" value="<?php echo date("d-m-Y", strtotime("$hoy - 1 month")) ?>" id="fecha_ini" size="11" maxlength="10" />
														<?php echo __('al') ?>
														<input type="text" name="fecha_fin" class="fechadiff"  value="<?php echo date("d-m-Y", strtotime("$hoy")) ?>" id="fecha_fin" size="11" maxlength="10" />
													</div>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</center>
					</fieldset>

					<!-- SELECTOR TIPO DE DATO -->
					<br/>
					<fieldset align="center" width="90%" class="border_plomo tb_base">
						<legend onClick="MostrarOculto('tipo_dato')" style="cursor:pointer">
							<span id="tipo_dato_img"><img src= "<?php echo Conf::ImgDir() ?>/mas.gif" border="0" ></span>
							<?php echo __('Tipo de Dato') ?>
						</legend>
						<input type="checkbox" name="tipo_dato_check" id="tipo_dato_check" value="1" <?php echo $tipo_dato_check ? 'checked' : '' ?> style="display:none;" />
						<center>
							<table id="mini_tipo_dato" >
								<tr>
									<td id="td_dato" class="<?php echo $comparar ? 'borde_rojo' : 'borde_blanco' ?>">
										<?php echo Html::SelectArrayDecente($tipos_de_dato_select, 'tipo_dato', $tipo_dato, 'id="tipo_dato" data-color="rojo"', '', '180px'); ?>
									</td>
									<td>
										<span id="vs" style="<?php echo $comparar ? '' : 'display: none;' ?>">
											<?php echo __(" Vs. ") ?>
										</span>
									</td>
									<td id="td_dato_comparado" class="borde_azul" style="<?php echo $comparar ? '' : 'display: none;' ?>" >
										<?php echo Html::SelectArrayDecente($tipos_de_dato_select, 'tipo_dato_comparado', $tipo_dato, 'id="tipo_dato_comparado" data-color="azul"', '', '180px'); ?>
									</td>
								</tr>
							</table>
						</center>
						<!-- SELECTOR TIPO DE DATO EXPANDIDO-->
						<table id="full_tipo_dato" style="border-collapse:separate; border: 0px solid black; width:730px; display: none; padding:10px; margin:auto;" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<?php echo $ReporteAvanzado->celda('horas_trabajadas') ?>
								<?php echo $ReporteAvanzado->borde_abajo(2) ?>
								<?php echo $ReporteAvanzado->celda('horas_cobrables') ?>
								<?php echo $ReporteAvanzado->borde_abajo(2) ?>
								<?php echo $ReporteAvanzado->celda('horas_visibles') ?>
								<?php echo $ReporteAvanzado->borde_abajo(2) ?>
								<?php echo $ReporteAvanzado->celda('horas_cobradas') ?>
								<?php echo $ReporteAvanzado->borde_abajo(2) ?>
								<?php echo $ReporteAvanzado->celda('horas_pagadas') ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada(1) ?>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada(1) ?>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada(1) ?>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada(1) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(9) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada() ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda('horas_no_cobrables') ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda('horas_castigadas') ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda('horas_por_cobrar') ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda('horas_por_pagar') ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(6) ?>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada(3) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(12) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(8) ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda('horas_incobrables') ?>
								<?php echo $ReporteAvanzado->nada(3) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(1) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(13) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->celda_disabled('valor_trabajado') ?>
								<?php echo $ReporteAvanzado->borde_abajo(2) ?>
								<?php echo $ReporteAvanzado->celda_disabled('valor_cobrable') ?>
								<?php echo $ReporteAvanzado->borde_abajo(2) ?>
								<?php echo $ReporteAvanzado->celda_disabled('valor_visible') ?>
								<?php echo $ReporteAvanzado->borde_abajo(2) ?>
								<?php echo $ReporteAvanzado->celda('valor_cobrado') ?>
								<?php echo $ReporteAvanzado->borde_abajo(2) ?>
								<?php echo $ReporteAvanzado->celda('valor_pagado') ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada() ?>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada() ?>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada() ?>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada() ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(9) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->celda('valor_trabajado_estandar') ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda_disabled('valor_no_cobrable') ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda_disabled('valor_castigado') ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda('valor_por_cobrar') ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda('valor_por_pagar') ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(6) ?>
								<?php echo $ReporteAvanzado->borde_derecha() ?>
								<?php echo $ReporteAvanzado->nada(3) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(12) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(8) ?>
								<?php echo $ReporteAvanzado->borde_abajo() ?>
								<?php echo $ReporteAvanzado->celda('valor_incobrable') ?>
								<?php echo $ReporteAvanzado->nada(3) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(1) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(13) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->titulo_proporcionalidad() ?>
								<?php echo $ReporteAvanzado->nada(2) ?>
								<?php echo $ReporteAvanzado->moneda() ?>
								<?php echo $ReporteAvanzado->nada(2) ?>
								<?php echo $ReporteAvanzado->celda('valor_estandar') ?>
								<?php echo $ReporteAvanzado->nada(2) ?>
								<?php echo $ReporteAvanzado->celda('diferencia_valor_estandar') ?>
								<?php echo $ReporteAvanzado->nada(2) ?>
								<?php echo $ReporteAvanzado->celda('valor_hora'); ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(1) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(1) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(12) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->select_proporcionalidad() ?>
								<?php echo $ReporteAvanzado->nada(2) ?>
								<?php echo $ReporteAvanzado->select_moneda() ?>
								<?php echo $ReporteAvanzado->nada(5) ?>
								<?php echo $ReporteAvanzado->celda('rentabilidad_base') ?>
								<?php echo $ReporteAvanzado->nada(2) ?>
								<?php echo $ReporteAvanzado->celda('rentabilidad') ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(1) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(1) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(12) ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->tinta() ?>
								<?php echo $ReporteAvanzado->nada(8) ?>
								<?php echo $ReporteAvanzado->celda('costo') ?>
								<?php echo $ReporteAvanzado->nada(2) ?>
								<?php echo $ReporteAvanzado->celda('costo_hh') ?>
							</tr>
							<tr>
								<?php echo $ReporteAvanzado->nada(9) ?>
							</tr>

						</table>
					</fieldset>

					<!-- SELECTOR DE VISTA -->
					<br>
					<fieldset align="center" width="90%" class="border_plomo tb_base">
						<legend><?php echo __('Vista') ?></legend>
						<table style="border: 0px solid black; width:730px" cellpadding="0" cellspacing="4">
							<tr>
								<td colspan=6 align=left>
									<div style="float:left">
										<img src="<?php echo Conf::ImgDir() ?>/menos.gif" onclick="Agrupadores(-1)" style='cursor:pointer;' />
										<img src="<?php echo Conf::ImgDir() ?>/mas.gif" onclick="Agrupadores(1)" style='cursor:pointer;' />
										<?php echo __('Agrupar por') ?>:&nbsp;
										<input type="hidden" name="numero_agrupadores" id="numero_agrupadores" value="<?php echo $numero_agrupadores ?>" />
										<input type="hidden" name=vista id="vista" value='' />
									</div>
									<div style="float:left" id="agrupadores">
										<?php
										$ya_elegidos = array();
										$input_valor_previo = '<input type="hidden" id="agrupador_valor_previo_%s" value="%s" data-text="%s"/>';
										for ($i = 0; $i < 6; $i++) {
											echo '<span id="span_agrupador_' . $i . '"';
											if ($i >= $numero_agrupadores)
												echo ' style="display:none;" ';
											echo '>';
											echo '<select name="agrupador[' . $i . ']" id="agrupador_' . $i . '" class="agrupador" >';
											$elegido = false;
											$valor_previo = '';
											foreach ($agrupadores as $key => $v) {
												if (!in_array($v, $ya_elegidos)) {
													echo '<option value="' . $v . '" ';
													if (isset($agrupador[$i])) {
														if ($agrupador[$i] == $v) {
															echo 'selected';
															$valor_previo = sprintf($input_valor_previo, $i, $v, __($v));
															$ya_elegidos[] = $v;
														}
													} else if (!$elegido) {
														echo 'selected';
														$valor_previo = sprintf($input_valor_previo, $i, $v, __($v));
														$elegido = true;
														$ya_elegidos[] = $v;
													}
													echo ">" . __($v);
													echo "</option>";
												}
											}
											echo '</select></span>';
											echo $valor_previo;
										}
										?>
									</div>
								</td>
							</tr>
							<tr>
								<td align="center" colspan="5">
									<br/>
									<?php
									echo $Form->icon_button('Planilla', 'code', array('name' => 'runreporte', 'id' => 'runreporte'));
									echo $Form->icon_button('Excel', 'xls', array('name' => 'excel', 'id' => 'excel', 'title' => 'Genera la Planilla como un Documento Excel.', 'onclick' => "Generar(jQuery('#formulario').get(0), 'excel');"));
									echo $Form->icon_button('Dispersión', 'icon-chart', array('name' => 'dispersion', 'id' => 'dispersion', 'title' => 'Genera la Planilla como un Documento Excel.', 'onclick' => "Generar(jQuery('#formulario').get(0), 'dispersion');"));
									echo $Form->icon_button('Tabla', 'icon-table', array('name' => 'tabla', 'id' => 'tabla', 'title' => 'Genera un Documento Excel con una tabla cruzada.', 'onclick' => "Generar(jQuery('#formulario').get(0), 'tabla');"));
									echo $Form->icon_button('Barras', 'icon-bar', array('name' => 'barras', 'id' => 'barras', 'title' => 'Despliega un Gráfico de Barras, usando el primer Agrupador.', 'onclick' => "Generar(jQuery('#formulario').get(0), 'barra');"));
									echo $Form->icon_button('Gráfico Torta', 'pie-chart', array('name' => 'circular', 'id' => 'circular', 'title' => 'Despliega un Gráfico de Torta, usando el primer Agrupador.', 'onclick' => "Generar(jQuery('#formulario').get(0), 'circular');"));
									echo $Form->script();
									?>
								</td>
								<td style="width: 100px; font-size: 11px;">
									<label for="comparar"><?php echo __('Comparar') ?>:</label> <input type="checkbox" name="comparar" id="comparar" value="1" title='Comparar' />
								</td>
							</tr>
							<tr>
								<td colspan="6">
									<table cellpadding="2" cellspacing="5">
										<tr>
											<td>
												<input type="checkbox" name="orden_barras_max2min" id="orden_barras_max2min" value="1"
												<?php
												if (isset($orden_barras_max2min) || !isset($tipo_dato))
													echo 'checked="checked"';
												?>
													   title=<?php echo __('Ordenar Gráfico de Barras de Mayor a Menor') ?>/>
												<label for="orden_barras_max2min"><?php echo __('Gráficar de Mayor a Menor') ?></label>
											</td>
											<td>
												<span id = "limite_check" <?php if (!isset($orden_barras_max2min) && isset($tipo_dato)) echo 'style= "display: none; "'; ?>>
													<input type="checkbox" name="limitar" id="limite_checkbox" value="1" <?php echo $limitar ? 'checked="checked"' : '' ?> />
													<label for="limite_checkbox"><?php echo __('y mostrar sólo') ?></label> &nbsp;
													<input type="text" name="limite" value="<?php echo $limite ? $limite : '5' ?>" id="limite" size="2" maxlength="2" /> &nbsp;
													<?php echo __("resultados superiores") ?>
												</span>
											</td>
											<td>
												<span id = "agupador_check">
													<input type="checkbox" name="agrupar" id="agrupador_checkbox" value="1" <?php echo $agrupar ? 'checked' : '' ?> />
													<label for="agrupador_checkbox"><?php echo __('agrupando el resto') ?></label>. &nbsp;
												</span>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</fieldset>
				</td>
			</tr>
		</table>
	<?php } ?>
</form>

<?php if ($opc && $opc != 'nuevo_reporte' && $opc != 'eliminar_reporte') { ?>
	<div class="resizable" id="iframereporte">
		<div class="divloading">&nbsp;</div>
		<iframe  class="resizableframe" onload="iframelista();" name="planilla" id="planilla" src="<?php echo $url_iframe; ?>" frameborder="0" style="display:none; width:730px; height:<?php echo $alto; ?>px;"></iframe>
	</div>
<?php } else { ?>
	<div class="resizable" id="iframereporte"></div>
<?php } ?>

<?php
$pagina->PrintBottom($popup);
