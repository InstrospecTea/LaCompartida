<?php

class NotaCobroResumenProfesional extends NotaCobroDocumento2 {

	private $lista_trabajos = array();
	private $ultimo_cobro;
	private $detalle_profesional = array();

	function GenerarSeccionResumenProfesional($parser, $theTag, $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, &$idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto) {

		global $contrato;
		global $cobro_moneda;
		global $masi;
		global $x_cobro_gastos;

		$simbolo_moneda = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'];
		$cifras_decimales = $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'];
		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);


		if (!isset($parser->tags[$theTag]))
			return;

		$html = $parser->tags[$theTag];

		switch ($theTag) {

			case 'RESUMEN_PROFESIONAL':
				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					// Obtener datos escalonados
					$chargingBusiness = new ChargingBusiness($this->sesion);
					$slidingScales = $chargingBusiness->getSlidingScalesArrayDetail($this->fields['id_cobro']);

					$cobro_valores = array();

					$cobro_valores['totales'] = array();
					$cobto_valores['datos_escalonadas'] = array();

					$this->CargarEscalonadas();
					$cobro_valores['datos_escalonadas'] = $this->escalonadas;


					$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";

					// Se seleccionan todos los trabajos del cobro, se incluye que sea cobrable ya que a los trabajos visibles
					// tambien se consideran dentro del cobro, tambien se incluye el valor del retainer del trabajo.

					if ($lang == 'es') {
						$query_categoria_lang = "prm_categoria_usuario.glosa_categoria as categoria";
					} else {
						$query_categoria_lang = "IFNULL(prm_categoria_usuario.glosa_categoria_lang, prm_categoria_usuario.glosa_categoria) as categoria";
					}

					$query = "SELECT SQL_CALC_FOUND_ROWS trabajo.duracion_cobrada,
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado,
									trabajo.id_moneda as id_moneda_trabajo,
									trabajo.id_trabajo,
									trabajo.tarifa_hh,
									trabajo.cobrable,
									trabajo.visible,
									trabajo.codigo_asunto,
									CONCAT_WS(' ', nombre, apellido1) as usr_nombre,
									$query_categoria_lang
							FROM trabajo
							JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							LEFT JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario = usuario.id_categoria_usuario
							WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
							AND trabajo.id_tramite=0
							ORDER BY trabajo.fecha ASC";
					$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);

					list($cobro_total_honorario_cobrable, $total_minutos_tmp, $detalle_trabajos) = $this->MontoHonorariosEscalonados($lista_trabajos);

					$cobro_valores['totales']['valor'] = $cobro_total_honorario_cobrable;
					$cobro_valores['totales']['duracion'] = ($total_minutos_tmp / 60);

					// Asignar datos escalonados que vienen de ChargingBusiness
					$cobro_valores['detalle'] = $slidingScales['detalle'];

					$cantidad_escalonadas = $cobro_valores['datos_escalonadas']['num'];

					$resumen_encabezado = $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

					$html = "<br /><span class=\"subtitulo_seccion\">%glosa_profesional%</span><br>";
					$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);

					if ($lang == 'es') {
						$html = str_replace('%resumen_profesional%', __('Resumen Detalle Profesional'), $html);
					} else {
						$html = str_replace('%resumen_profesional%', __('TIMEKEEPER SUMMARY'), $html);
					}

					$esc = 1;
					while ($esc <= $cantidad_escalonadas) {
						if (is_array($cobro_valores['detalle']['detalle_escalonadas'][$esc]['usuarios'])) {
							$html .= '<h4>' . __('Escalón') . " $esc: ";
							if ($cobro_valores['datos_escalonadas'][$esc]['monto'] > 0) {
								$html .= __('Monto Fijo') . ' ' . $simbolo_moneda . $this->espacio . number_format($cobro_valores['datos_escalonadas'][$esc]['monto'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . "</h4>";
							} else {
								$html .= __('Tarifa HH');
								if ($cobro_valores['datos_escalonadas'][$esc]['descuento'] > 0) {
									$html .= " con " . $cobro_valores['datos_escalonadas'][$esc]['descuento'] . "% de descuento";
								}
								$html .= "</h4>";
							}
							$html .= "<table class=\"tabla_normal\" width=\"100%\">";
							$html .= $resumen_encabezado;

							foreach ($cobro_valores['detalle']['detalle_escalonadas'][$esc]['usuarios'] as $id_usuario => $usuarios) {
								$resumen_fila = $parser->tags['PROFESIONAL_FILAS'];

								$resumen_fila = str_replace('%nombre%', $usuarios['usuario'], $resumen_fila);
								if ($this->fields['opc_ver_profesional_categoria']) {
									$resumen_fila = str_replace('%categoria%', __($usuarios['categoria']), $resumen_fila);
								} else {
									$resumen_fila = str_replace('%categoria%', '', $resumen_fila);
								}
								$resumen_fila = str_replace('%hh_demo%', Utiles::Decimal2GlosaHora(round($usuarios['duracion']/60, 2)), $resumen_fila);
								$resumen_fila = str_replace('%hh_trabajada%', '', $resumen_fila);
								if ($this->fields['opc_ver_profesional_tarifa']) {
									$resumen_fila = str_replace('%td_tarifa%', '<td align="center">%tarifa_horas_demo%</td>', $resumen_fila);
									$resumen_fila = str_replace('%td_tarifa_ajustada%', '<td align="center">%tarifa_horas_demo%</td>', $resumen_fila);
								} else {
									$resumen_fila = str_replace('%td_tarifa%', '', $resumen_fila);
									$resumen_fila = str_replace('%td_tarifa_ajustada%', '', $resumen_fila);
								}

								if ($cobro_valores['datos_escalonadas'][$esc]['escalonada_tarificada'] != 0) {
									$resumen_fila = str_replace('%tarifa_horas_demo%', number_format($usuarios['tarifa'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_fila);
								} else {
									$resumen_fila = str_replace('%tarifa_horas_demo%', '--', $resumen_fila);
								}

								if ($this->fields['opc_ver_profesional_importe']) {
									$resumen_fila = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $resumen_fila);
									$resumen_fila = str_replace('%td_importe_ajustado%', '<td align="right">%total_horas_demo%</td>', $resumen_fila);
								} else {
									$resumen_fila = str_replace('%td_importe%', '', $resumen_fila);
									$resumen_fila = str_replace('%td_importe_ajustado%', '', $resumen_fila);
								}
								$resumen_fila = str_replace('%total_horas_demo%', $simbolo_moneda . $this->espacio . number_format($usuarios['valor'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_fila);

								$resumen_fila = str_replace('%td_descontada%', '', $resumen_fila);
								$resumen_fila = str_replace('%td_cobrable%', '', $resumen_fila);
								$resumen_fila = str_replace('%td_retainer%', '', $resumen_fila);
								if ($usuarios['duracion'] > 0) {
									$html .= $resumen_fila;
								}
							}
							// Total
							$resumen_total = $parser->tags['PROFESIONAL_TOTAL'];

							$resumen_total = str_replace('%glosa%', __('Total'), $resumen_total);
							$resumen_total = str_replace('%hh_trabajada%', '', $resumen_total);
							$resumen_total = str_replace('%td_descontada%', '', $resumen_total);
							$resumen_total = str_replace('%td_cobrable%', '', $resumen_total);
							$resumen_total = str_replace('%td_retainer%', '', $resumen_total);
							$resumen_total = str_replace('%hh_demo%', Utiles::Decimal2GlosaHora(round($cobro_valores['detalle']['detalle_escalonadas'][$esc]['totales']['duracion']/60, 2)), $resumen_total);
							if ($this->fields['opc_ver_profesional_tarifa']) {
								$resumen_total = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $resumen_total);
								$resumen_total = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $resumen_total);
							} else {
								$resumen_total = str_replace('%td_tarifa%', '', $resumen_total);
								$resumen_total = str_replace('%td_tarifa_ajustada%', '', $resumen_total);
							}
							if ($this->fields['opc_ver_profesional_importe']) {
								$resumen_total = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $resumen_total);
								$resumen_total = str_replace('%td_importe_ajustado%', '<td align="right">%total_horas_demo%</td>', $resumen_total);
							} else {
								$resumen_total = str_replace('%td_importe%', '', $resumen_total);
								$resumen_total = str_replace('%td_importe_ajustado%', '', $resumen_total);
							}
							$resumen_total = str_replace('%total_horas_demo%', $simbolo_moneda . $this->espacio . number_format($cobro_valores['detalle']['detalle_escalonadas'][$esc]['totales']['valor'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_total);
							$html .= $resumen_total;
							$html .= "</table>";
						}
						$esc++;
					}
					return $html;
				}

				$columna_hrs_retainer = $this->fields['opc_ver_detalle_retainer'] && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL');


				$columna_hrs_trabajadas_categoria = $GLOBALS['columna_hrs_trabajadas_categoria'];

				$columna_hrs_trabajadas = $this->fields['opc_ver_horas_trabajadas'];

				if ($this->fields['opc_ver_profesional'] == 0) {
					return '';
				}
				// Encabezado
				$resumen_encabezado = $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

				// Filas
				$resumen_filas = array();

				//Se ve si la cantidad de horas trabajadas son menos que las horas del retainer esto para que no hayan problemas al mostrar los datos
				$han_trabajado_menos_del_retainer = (($this->fields['total_minutos'] / 60) < $this->fields['retainer_horas']) && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL');

				$retainer = false;
				$descontado = false;
				$flatfee = false;
				$incobrables = false;
				$sumary = $this->ChargeData->getSumary($asunto->fields['codigo_asunto']);
				$totales = $this->ChargeData->getTotal();

				if (is_array($totales)) {
					if ($totales['duracion_retainer'] > 0 && $this->fields['forma_cobro'] != 'PROPORCIONAL' && ($this->fields['forma_cobro'] != 'FLAT FEE' || ( Conf::GetConf($this->sesion, 'ResumenProfesionalVial') ) )) {
						$retainer = true;
					}
					//if ($totales['duracion_descontada'] > 0)
					$descontado = true;
					if ($totales['flatfee'] > 0) {
						$flatfee = true;
					}
					if ($totales['duracion_incobrables'] > 0) {
						$incobrables = true;
					}
				}

				$resumen_hrs_trabajadas = 0;
				$resumen_hrs_cobradas = 0;
				$resumen_hrs_cobradas_cob = 0;
				$resumen_hrs_retainer = 0;
				$resumen_hrs_descontadas = 0;
				$resumen_hrs_incobrables = 0;
				$resumen_hh = 0;
				$resumen_valor = 0;
				$resumen_horas_descontadas = 0;
				$resumen_horas_no_cobrables = 0;
				$resumen_horas_trabajadas_profesional = 0;

				foreach ($sumary as $prof => $data) {
					$horas_descontadas_profesional = $data['duracion_descontada'] - $data['duracion_incobrables'];
					// Calcular totales
					$resumen_hrs_trabajadas += $data['duracion'];
					$resumen_hrs_cobradas += $data['duracion_cobrada'];
					$resumen_hrs_cobradas_cob += $data['duracion_cobrada'];
					$resumen_hrs_cobradas_cob -= $data['duracion_incobrables'];
					$resumen_hrs_retainer += $data['duracion_retainer'];
					$resumen_hrs_descontadas += $data['duracion_descontada'];
					$resumen_hrs_incobrables += $data['duracion_incobrables'];
					$resumen_horas_descontadas += $horas_descontadas_profesional;
					$resumen_horas_no_cobrables += $data['duracion_incobrables'];
					$resumen_horas_trabajadas_profesional += $data['duracion'];

					$resumen_hh += $data['duracion_tarificada'];
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$resumen_valor += $data['monto_cobrado_escalonada'];
					} else {
						$resumen_valor += $data['valor_tarificada'];
					}

					$html3 = $parser->tags['PROFESIONAL_FILAS'];
					$html3 = str_replace('%username%', $data['username'], $html3);

					$html3 = str_replace('%horas_descontadas%', Utiles::Decimal2GlosaHora($horas_descontadas_profesional), $html3);
					$html3 = str_replace('%horas_no_cobrables%', $data['glosa_duracion_incobrables'], $html3);
					$html3 = str_replace('%horas_trabajadas_profesional%', $data['glosa_duracion'], $html3);
					if ($this->fields['opc_ver_profesional_iniciales'] == 1) {
						$html3 = str_replace('%nombre%', $data['username'], $html3);
					} else {
						$html3 = str_replace('%nombre%', $data['nombre_usuario'] . ' (' . $data['username'] . ')', $html3);
					}
					if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
						$html3 = str_replace('%td_tarifa%', '<td align="center">%tarifa_horas_demo%</td>', $html3);
					} else {
						$html3 = str_replace('%td_tarifa%', '', $html3);
						$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
					}
					if ($this->fields['opc_ver_profesional_importe'] == 1) {
						$html3 = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $html3);
						$html3 = str_replace('%td_importe_ajustado%', '<td align="right">%total_horas_ajustado%</td>', $html3);
					} else {
						$html3 = str_replace('%td_importe%', '', $html3);
						$html3 = str_replace('%td_importe_ajustado%', '', $html3);
					}
					//muestra las iniciales de los profesionales
					//Las iniciales fueron reemplazas por el username. Pivotal: 109198728
					$html3 = str_replace('%iniciales%', $data['username'], $html3);
					if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] || Conf::GetConf($this->sesion, 'NotaDeCobroVFC')) {
						$html3 = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%', $data['glosa_duracion_cobrada'], $html3);
					} else if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						if ($han_trabajado_menos_del_retainer) {
							$html3 = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $html3);
						} else {
							$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? $data['glosa_duracion_cobrada'] : ''), $html3);
						}
						$html3 = str_replace('%hrs_trabajadas_vio%', $data['glosa_duracion_cobrada'], $html3);
					} else {
						$html3 = str_replace('%hrs_trabajadas%', '', $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%', ($columna_hrs_trabajadas ? $data['glosa_duracion_cobrada'] : ''), $html3);
					}
					if ($han_trabajado_menos_del_retainer) {
						$html3 = str_replace('%hrs_retainer%', $data['glosa_duracion_retainer'], $html3);
					} else {
						$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? $data['glosa_duracion_retainer'] : ''), $html3);
					}
					if ($han_trabajado_menos_del_retainer && !$this->fields['opc_ver_detalle_retainer'])
						$html3 = str_replace('%hrs_retainer_vio%', '', $html3);
					else if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
						$html3 = str_replace('%hrs_retainer_vio%', $data['glosa_duracion_retainer'], $html3);
					else
						$html3 = str_replace('%hrs_retainer_vio%', '', $html3);
					$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_descontadas ? $data['glosa_duracion_descontada'] : ''), $html3);

					$html3 = str_replace('%td_horas_rebajadas%', '<td align="center">%hh_rebajada%</td>', $html3);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html3 = str_replace('%hh_rebajada%', number_format($data['duracion_descontada'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
					} else {
						$html3 = str_replace('%hh_rebajada%', $data['glosa_duracion_descontada'], $html3);
					}

					if ($this->fields['opc_ver_horas_trabajadas']) {
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html3 = str_replace('%hh_trabajada%', number_format($data['duracion'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
						} else {
							$html3 = str_replace('%hh_trabajada%', $data['glosa_duracion'], $html3);
						}
						if ($descontado) {
							$html3 = str_replace('%td_descontada%', '<td align="center">%hh_descontada%</td>', $html3);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$html3 = str_replace('%hh_descontada%', number_format($data['duracion_descontada'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
							} else {
								$html3 = str_replace('%hh_descontada%', $data['glosa_duracion_descontada'], $html3);
							}
						} else {
							$html3 = str_replace('%td_descontada%', '', $html3);
							$html3 = str_replace('%hh_descontada%', '', $html3);
						}
					} else {
						$html3 = str_replace('%td_descontada%', '', $html3);
						$html3 = str_replace('%hh_trabajada%', '', $html3);
						$html3 = str_replace('%hh_descontada%', '', $html3);
					}
					if ($retainer || $flatfee) {
						$html3 = str_replace('%td_cobrable%', '<td align="center">%hh_cobrable%</td>', $html3);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html3 = str_replace('%hh_cobrable%', number_format($data['duracion_cobrada'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
						} else {
							$html3 = str_replace('%hh_cobrable%', $data['glosa_duracion_cobrada'], $html3);
						}
						if ($retainer) {
							$html3 = str_replace('%td_retainer%', '<td align="center">%hh_retainer%</td>', $html3);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$html3 = str_replace('%hh_retainer%', number_format($data['duracion_retainer'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
							} else {
								$html3 = str_replace('%hh_retainer%', $data['glosa_duracion_retainer'], $html3);
							}
						} else {
							$html3 = str_replace('%td_retainer%', '', $html3);
							$html3 = str_replace('%hh_retainer%', '', $html3);
						}
					} else {
						$html3 = str_replace('%td_cobrable%', '', $html3);
						$html3 = str_replace('%td_retainer%', '', $html3);
						$html3 = str_replace('%hh_cobrable%', '', $html3);
						$html3 = str_replace('%hh_retainer%', '', $html3);
					}
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$html3 = str_replace('%hh_demo%', $data['glosa_duracion_cobrada'], $html3);
					} else if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html3 = str_replace('%hh_demo%', number_format($data['duracion_tarificada'], Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
					} else {
						$html3 = str_replace('%hh_demo%', $data['glosa_duracion_tarificada'], $html3);
					}
					if ($han_trabajado_menos_del_retainer) {
						$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(0), $html3);
					} else {
						$html3 = str_replace('%hh%', $data['glosa_duracion_tarificada'], $html3);
					}

					if ($this->fields['opc_ver_profesional_categoria'] == 1) {
						$html3 = str_replace('%categoria%', __($data['glosa_categoria']), $html3);
					} else {
						$html3 = str_replace('%categoria%', '', $html3);
					}

					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$html3 = str_replace('%tarifa_horas_demo%', number_format($data['monto_cobrado_escalonada'] / $data['duracion_cobrada'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%tarifa_horas%', $simbolo_moneda . $this->espacio . number_format($data['monto_cobrado_escalonada'] / $data['duracion_cobrada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
						$html3 = str_replace('%tarifa_horas_demo%', number_format($data['tarifa'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%tarifa_horas%', $simbolo_moneda . $this->espacio . number_format($data['tarifa'] > 0 ? $data['tarifa'] : 0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else {
						$html3 = str_replace('%tarifa_horas_demo%', '', $html3);
						$html3 = str_replace('%tarifa_horas%', '', $html3);
					}

					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$html3 = str_replace('%total_horas_demo%', $simbolo_moneda . $this->espacio . number_format($data['monto_cobrado_escalonada'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%total_horas%', number_format($data['monto_cobrado_escalonada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%total_horas_ajustado%', number_format($data['monto_cobrado_escalonada'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else if ($this->fields['opc_ver_profesional_importe'] == 1) {
						$html3 = str_replace('%total_horas_demo%', $simbolo_moneda . $this->espacio . number_format($data['valor_tarificada'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%total_horas%', number_format($data['valor_tarificada'] > 0 ? $data['valor_tarificada'] : 0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%total_horas_ajustado%', number_format($data['valor_tarificada'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else {
						$html3 = str_replace('%total_horas_demo%', '', $html3);
						$html3 = str_replace('%total_horas%', '', $html3);
						$html3 = str_replace('%total_horas_ajustado%', '', $html3);
					}

					$resumen_filas[$prof] = $html3;
				}
				// Se escriben después porque necesitan que los totales ya estén calculados para calcular porcentajes.
				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$total_valor = 0;
					foreach ($sumary as $prof => $data) {
						$resumen_hrs_cobradas_temp = $resumen_hrs_cobradas > 0 ? $resumen_hrs_cobradas : 1;
						$resumen_filas[$prof] = str_replace('%porcentaje_participacion%', number_format($data['duracion_cobrada'] / $resumen_hrs_cobradas_temp * 100, 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '%', $resumen_filas[$prof]);

						if ($incobrables) {
							$resumen_filas[$prof] = str_replace('%columna_horas_no_cobrables%', '<td align="center">' . $data['glosa_duracion_incobrables'] . '</td>', $resumen_filas[$prof]);
						} else {
							$resumen_filas[$prof] = str_replace('%columna_horas_no_cobrables%', '', $resumen_filas[$prof]);
						}
						if ($han_trabajado_menos_del_retainer && !$this->fields['opc_ver_detalle_retainer']) {
							$resumen_filas[$prof] = str_replace('%valor_retainer%', '', $resumen_filas[$prof]);
							$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', '', $resumen_filas[$prof]);
						} else {
							$resumen_filas[$prof] = str_replace('%valor_retainer%', $columna_hrs_retainer ? number_format($data['duracion_cobrada'] / $resumen_hrs_cobradas_temp * $this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '', $resumen_filas[$prof]);
						}
						if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
							$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', number_format($data['duracion_cobrada'] / $resumen_hrs_cobradas_temp * $this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
						} else {
							$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', '', $resumen_filas[$prof]);
						}
						if ($han_trabajado_menos_del_retainer) {
							$resumen_filas[$prof] = str_replace('%valor_cobrado_hh%', number_format(0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
						} else {
							$resumen_filas[$prof] = str_replace('%valor_cobrado_hh%', number_format($data['valor_tarificada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
							$total_valor += $data['valor_tarificada'];
						}
					}
				}
				$resumen_filas = implode($resumen_filas);

				// Total
				if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$valor_cobrado_hh = $this->fields['monto'] - UtilesApp::CambiarMoneda($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cifras_decimales);
				} else {
					$valor_cobrado_hh = $this->fields['monto'];
				}

				$html3 = $parser->tags['PROFESIONAL_TOTAL'];

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					if ($han_trabajado_menos_del_retainer) {
						$html3 = str_replace('%valor_retainer%', number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
						$html3 = str_replace('%valor_cobrado_hh%', number_format(0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					} else {
						$html3 = str_replace('%valor_retainer%', $columna_hrs_retainer ? number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '', $html3);
						$html3 = str_replace('%valor_cobrado_hh%', number_format($valor_cobrado_hh, $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					}
				}
				$html3 = str_replace('%glosa%', __('Total'), $html3);
				if ($han_trabajado_menos_del_retainer || $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas']) {
					$html3 = str_replace('%hrs_trabajadas%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas), $html3);
					$html3 = str_replace('%hrs_trabajadas_vio%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas), $html3);
				} else {
					$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas) : ''), $html3);
					$html3 = str_replace('%hrs_trabajadas_vio%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas) : ''), $html3);
				}

				if ($han_trabajado_menos_del_retainer) {
					$html3 = str_replace('%hrs_retainer%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas_cob), $html3);
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(0), $html3);
				} else {
					$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? UtilesApp::Hora2HoraMinuto($this->fields['retainer_horas']) : ''), $html3);
				}
				$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_descontadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_descontadas) : ''), $html3);

				if ($this->fields['opc_ver_horas_trabajadas']) {
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html3 = str_replace('%hh_trabajada%', number_format($resumen_hrs_trabajadas, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
					} else {
						$html3 = str_replace('%hh_trabajada%', UtilesApp::Hora2HoraMinuto(round($resumen_hrs_trabajadas, 2)), $html3);
					}
					if ($descontado) {
						$html3 = str_replace('%td_descontada%', '<td align="center">%hh_descontada%</td>', $html3);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html3 = str_replace('%hh_descontada%', number_format($resumen_hrs_descontadas, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
						} else {
							$html3 = str_replace('%hh_descontada%', Utiles::Decimal2GlosaHora(round($resumen_hrs_descontadas, 2)), $html3);
						}
					} else {
						$html3 = str_replace('%td_descontada%', '', $html3);
						$html3 = str_replace('%hh_descontada%', '', $html3);
					}
				} else {
					$html3 = str_replace('%td_descontada%', '', $html3);
					$html3 = str_replace('%hh_trabajada%', '', $html3);
					$html3 = str_replace('%hh_descontada%', '', $html3);
				}
				if ($retainer || $flatfee) {
					$html3 = str_replace('%td_cobrable%', '<td align="center">%hh_cobrable%</td>', $html3);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html3 = str_replace('%hh_cobrable%', number_format($resumen_hrs_cobradas, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
					} else {
						$html3 = str_replace('%hh_cobrable%', UtilesApp::Hora2HoraMinuto(round($resumen_hrs_cobradas, 2)), $html3);
					}
					if ($retainer) {
						$html3 = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $html3);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html3 = str_replace('%hh_retainer%', number_format($resumen_hrs_retainer, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
						} else {
							$html3 = str_replace('%hh_retainer%', UtilesApp::Hora2HoraMinuto(round($resumen_hrs_retainer, 2)), $html3);
						}
					} else {
						$html3 = str_replace('%td_retainer%', '', $html3);
						$html3 = str_replace('%hh_retainer%', '', $html3);
					}
				} else {
					$html3 = str_replace('%td_cobrable%', '', $html3);
					$html3 = str_replace('%td_retainer%', '', $html3);
					$html3 = str_replace('%hh_cobrable%', '', $html3);
					$html3 = str_replace('%hh_retainer%', '', $html3);
				}
				if ($incobrables) {
					$html3 = str_replace('%columna_horas_no_cobrables%', '<td align="center">' . UtilesApp::Hora2HoraMinuto(round($resumen_hrs_incobrables, 2)) . '</td>', $html3);
				} else {
					$html3 = str_replace('%columna_horas_no_cobrables%', '', $html3);
				}
				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$html3 = str_replace('%hh_demo%', UtilesApp::Hora2HoraMinuto(round($resumen_hrs_cobradas, 2)), $html3);
				} else if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$html3 = str_replace('%hh_demo%', number_format($resumen_hh, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html3);
				} else {
					$html3 = str_replace('%hh_demo%', UtilesApp::Hora2HoraMinuto(round($resumen_hh, 2)), $html3);
				}
				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial') && ( $this->fields['forma_cobro'] == 'PROPORCIONAL' || $this->fields['forma_cobro'] == 'RETAINER' ) && !$han_trabajado_menos_del_retainer) {
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas - $resumen_hrs_incobrables - $this->fields['retainer_horas']), $html3);
				} else {
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(round($resumen_hh, 2)), $html3);
				}
				if ($this->fields['opc_ver_profesional_importe'] == 1) {
					$html3 = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $html3);
					$html3 = str_replace('%td_importe_ajustado%', '<td align="right">%total_horas_ajustado%</td>', $html3);
					$html3 = str_replace('%total_horas_demo%', $simbolo_moneda . $this->espacio . number_format($totales['importe'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					$html3 = str_replace('%total_horas_ajustado%', number_format($totales['valor_tarificada'], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				} else {
					$html3 = str_replace('%td_importe%', '', $html3);
					$html3 = str_replace('%td_importe_ajustado%', '', $html3);
					$html3 = str_replace('%total_horas_demo%', '', $html3);
					$html3 = str_replace('%total_horas_ajustado%', '', $html3);
				}

				if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
					$html3 = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html3);
					$html3 = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html3);
				} else {
					$html3 = str_replace('%td_tarifa%', '', $html3);
					$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
				}

				$html3 = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_trabajos'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

				$html3 = str_replace('%horas_descontadas%', Utiles::Decimal2GlosaHora(round($resumen_horas_descontadas, 2)), $html3);
				$html3 = str_replace('%horas_no_cobrables%', Utiles::Decimal2GlosaHora(round($resumen_horas_no_cobrables, 2)), $html3);
				$html3 = str_replace('%horas_trabajadas_profesional%', Utiles::Decimal2GlosaHora(round($resumen_horas_trabajadas_profesional, 2)), $html3);

				$resumen_fila_total = $html3;
				$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);

				if ($lang == 'es') {
					$html = str_replace('%resumen_profesional%', __('Resumen Detalle Profesional'), $html);
				} else {
					$html = str_replace('%resumen_profesional%', __('TIMEKEEPER SUMMARY'), $html);
				}

				$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_ENCABEZADO%', $resumen_encabezado, $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $resumen_filas, $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_TOTAL%', $resumen_fila_total, $html);

				$html = str_replace('%seccion_resumen_profesional%', __('resumen_raz'), $html);
				break;

			case 'RESUMEN_PROFESIONAL_POR_CATEGORIA': //GenerarDocumento2

				if ($this->fields['opc_ver_profesional'] == 0) {
					return '';
				}

				global $columna_hrs_trabajadas;
				global $columna_hrs_retainer;
				global $columna_hrs_descontadas;
				global $sumary;

				$columna_hrs_incobrables = false;

				$array_categorias = array();
				foreach ($sumary as $id => $data) {
					array_push($array_categorias, $data['id_categoria_usuario']);
					if ($data['duracion_incobrables'] > 0)
						$columna_hrs_incobrables = true;
				}

				// Array que guardar los ids de usuarios para recorrer
				if (sizeof($array_categorias) > 0)
					array_multisort($array_categorias, SORT_ASC, $sumary);

				$array_profesionales = array();
				foreach ($sumary as $id_usuario => $data) {
					array_push($array_profesionales, $id_usuario);
				}

				// Encabezado
				$resumen_encabezado = $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);
				$html = str_replace('%RESUMEN_PROFESIONAL_ENCABEZADO%', $resumen_encabezado, $html);
				$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);

				// Partimos los subtotales de la primera categoría con los datos del primer profesional.
				$resumen_hrs_trabajadas = $sumary[$array_profesionales[0]]['duracion'];
				$resumen_hrs_cobradas = $sumary[$array_profesionales[0]]['duracion_cobrada'];
				$resumen_hrs_retainer = $sumary[$array_profesionales[0]]['duracion_retainer'];
				$resumen_hrs_descontadas = $sumary[$array_profesionales[0]]['duracion_descontada'];
				$resumen_hrs_incobrables = $sumary[$array_profesionales[0]]['duracion_incobrables'];
				$resumen_hh = $sumary[$array_profesionales[0]]['duracion_tarificada'];
				$resumen_total = $sumary[$array_profesionales[0]]['valor_tarificada'];
				// Partimos los totales con 0
				$resumen_total_hrs_trabajadas = 0;
				$resumen_total_hrs_cobradas = 0;
				$resumen_total_hrs_retainer = 0;
				$resumen_total_hrs_descontadas = 0;
				$resumen_total_hrs_incobrables = 0;
				$resumen_total_hh = 0;
				$resumen_total_total = 0;

				for ($k = 1; $k < count($array_profesionales); ++$k) {

					// El profesional actual es de la misma categoría que el anterior, solo aumentamos los subtotales de la categoría.
					if ($sumary[$array_profesionales[$k]]['id_categoria_usuario'] == $sumary[$array_profesionales[$k - 1]]['id_categoria_usuario']) {
						$resumen_hrs_trabajadas += $sumary[$array_profesionales[$k]]['duracion'];
						$resumen_hrs_cobradas += $sumary[$array_profesionales[$k]]['duracion_cobrada'];
						$resumen_hrs_retainer += $sumary[$array_profesionales[$k]]['duracion_retainer'];
						$resumen_hrs_descontadas += $sumary[$array_profesionales[$k]]['duracion_descontada'];
						$resumen_hrs_incobrables += $sumary[$array_profesionales[$k]]['duracion_incobrables'];
						$resumen_hh += $sumary[$array_profesionales[$k]]['duracion_tarificada'];
						$resumen_total += $sumary[$array_profesionales[$k]]['valor_tarificada'];
					} else {
						// El profesional actual es de distinta categoría que el anterior, imprimimos los subtotales de la categoría anterior y ponemos en cero los de la actual.
						$html3 = $parser->tags['PROFESIONAL_FILAS'];
						$html3 = str_replace('%nombre%', $sumary[$array_profesionales[$k - 1]]['glosa_categoria'], $html3);
						$html3 = str_replace('%iniciales%', $sumary[$array_profesionales[$k - 1]]['glosa_categoria'], $html3);

						$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas) : ''), $html3);
						$html3 = str_replace('%hrs_retainer%', 'jhgf'.($columna_hrs_retainer ? UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer) : ''), $html3);
						$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_incobrables ? UtilesApp::Hora2HoraMinuto($resumen_hrs_incobrables) : ''), $html3);
						$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_hh), $html3);

						$html3 = str_replace('%total_horas%', $moneda->fields['simbolo'] . $this->espacio . number_format($resumen_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

						// Se asume que dentro de la misma categoría todos tienen la misma tarifa.
						$html3 = str_replace('%tarifa_horas%', number_format($sumary[$array_profesionales[$k - 1]]['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

						// Para imprimir la siguiente categorí­a de usuarios
						$siguiente = " \n%RESUMEN_PROFESIONAL_FILAS%\n";
						$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $html3 . $siguiente, $html);

						// Aumentamos los totales
						$resumen_total_hrs_trabajadas += $resumen_hrs_trabajadas;
						$resumen_total_hrs_cobradas += $resumen_hrs_cobradas;
						$resumen_total_hrs_retainer += $resumen_hrs_retainer;
						$resumen_total_hrs_descontadas += $resumen_hrs_descontadas;
						$resumen_total_hrs_incobrables += $resumen_hrs_incobrables;
						$resumen_total_hh += $resumen_hh;
						$resumen_total_total += $resumen_total;
						// Resetear subtotales
						$resumen_hrs_trabajadas = $sumary[$array_profesionales[$k]]['duracion'];
						$resumen_hrs_cobradas = $sumary[$array_profesionales[$k]]['duracion_cobrada'];
						$resumen_hrs_retainer = $sumary[$array_profesionales[$k]]['duracion_retainer'];
						$resumen_hrs_descontadas = $sumary[$array_profesionales[$k]]['duracion_descontada'];
						$resumen_hrs_incobrables = $sumary[$array_profesionales[$k]]['duracion_incobrables'];
						$resumen_hh = $sumary[$array_profesionales[$k]]['duracion_tarificada'];
						$resumen_total = $sumary[$array_profesionales[$k]]['valor_tarificada'];
					}
				}

				// Imprimir la última categoría
				$html3 = $parser->tags['PROFESIONAL_FILAS'];
				$html3 = str_replace('%nombre%', $sumary[$array_profesionales[$k - 1]]['glosa_categoria'], $html3);
				$html3 = str_replace('%iniciales%', $sumary[$array_profesionales[$k - 1]]['glosa_categoria'], $html3);
				$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas) : ''), $html3);
				$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer) : ''), $html3);
				$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_incobrables ? UtilesApp::Hora2HoraMinuto($resumen_hrs_incobrables) : ''), $html3);
				$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_hh), $html3);
				// Se asume que dentro de la misma categoría todos tienen la misma tarifa.
				$html3 = str_replace('%tarifa_horas%', number_format($sumary[$array_profesionales[$k - 1]]['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				$html3 = str_replace('%total_horas%', $moneda->fields['simbolo'] . $this->espacio . number_format($resumen_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

				$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $html3, $html);

				//cargamos el dato del total del monto en moneda tarifa (dato se calculo en detalle cobro) para mostrar en resumen segun conf
				global $monto_cobro_menos_monto_contrato_moneda_tarifa;

				// Aumentamos los totales
				$resumen_total_hrs_trabajadas += $resumen_hrs_trabajadas;
				$resumen_total_hrs_cobradas += $resumen_hrs_cobradas;
				$resumen_total_hrs_retainer += $resumen_hrs_retainer;
				$resumen_total_hrs_descontadas += $resumen_hrs_descontadas;
				$resumen_total_hrs_incobrables += $resumen_hrs_incobrables;
				$resumen_total_hh += $resumen_hh;
				$resumen_total_total += $resumen_total;

				//se muestra el mismo valor que sale en el detalle de cobro
				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$resumen_total_total = $monto_cobro_menos_monto_contrato_moneda_tarifa;
				}

				// Imprimir el total
				$html3 = $parser->tags['RESUMEN_PROFESIONAL_TOTAL'];
				$html3 = str_replace('%glosa%', __('Total'), $html3);

				$html3 = str_replace('%hrs_trabajadas%', ($columna_hrs_trabajadas ? UtilesApp::Hora2HoraMinuto($resumen_total_hrs_cobradas) : ''), $html3);
				$html3 = str_replace('%hrs_retainer%', ($columna_hrs_retainer ? UtilesApp::Hora2HoraMinuto($resumen_total_hrs_retainer) : ''), $html3);
				$html3 = str_replace('%hrs_descontadas%', ($columna_hrs_incobrables ? UtilesApp::Hora2HoraMinuto($resumen_total_hrs_incobrables) : ''), $html3);
				$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_total_hh), $html3);
				$html3 = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($resumen_total_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				$html = str_replace('%RESUMEN_PROFESIONAL_TOTAL%', $html3, $html);
				break;

			case 'RESUMEN_PROFESIONAL_ENCABEZADO': //GenerarDocumentoComun
				$html = str_replace('%nombre%', __('Categoría profesional'), $html);
				global $columna_hrs_trabajadas_categoria;
				global $columna_hrs_retainer_categoria;
				global $columna_hrs_flatfee_categoria;
				global $columna_hrs_descontadas_categoria;
				global $columna_hrs_incobrables_categoria;

				if ($columna_hrs_retainer_categoria) {
					$html = str_replace('%hrs_retainer%', __('Hrs. Retainer'), $html);
					$html = str_replace('%hrs_mins_retainer%', __('Hrs.:Mins. Retainer'), $html);
				}

				$html = str_replace('%hrs_retainer%', $columna_hrs_flatfee_categoria ? __('Hrs. Flat Fee') : '', $html);
				$html = str_replace('%hrs_trabajadas%', $columna_hrs_trabajadas_categoria ? __('Hrs. Trabajadas') : '', $html);
				$html = str_replace('%hrs_descontadas%', $columna_hrs_incobrables_categoria ? __('Hrs. Descontadas') : '', $html);
				$html = str_replace('%hrs_mins_retainer%', $columna_hrs_flatfee_categoria ? __('Hrs.:Mins. Flat Fee') : '', $html);
				$html = str_replace('%hrs_mins_trabajadas%', $columna_hrs_trabajadas_categoria ? __('Hrs.:Mins. Trabajadas') : '', $html);
				$html = str_replace('%hrs_mins_descontadas%', $columna_hrs_descontadas_categoria ? __('Hrs.:Mins. Descontadas') : '', $html);
				// El resto se llena igual que PROFESIONAL_ENCABEZADO, pero tiene otra estructura, no debe tener 'break;'.

			case 'PROFESIONAL_ENCABEZADO':
				global $columna_hrs_trabajadas;
				global $columna_hrs_retainer;
				global $columna_hrs_descontadas;
				global $columna_hrs_trabajadas_categoria;
				global $columna_hrs_retainer_categoria;
				global $columna_hrs_flatfee_categoria;
				global $columna_hrs_descontadas_categoria;

				if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%horas_trabajadas%', __('Hrs Trabajadas'), $html);
				}

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$mostrar_columnas_retainer = $columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL';
					if ($mostrar_columnas_retainer) {
						$html = str_replace('%horas_trabajadas%', __('Hrs Trabajadas'), $html);
						$html = str_replace('%retainer%', __('RETAINER'), $html);
						$html = str_replace('%extraordinario%', __('EXTRAORDINARIO'), $html);
						$html = str_replace('%simbolo_moneda_2%', ' (' . $moneda->fields['simbolo'] . ')', $html);
					} else {
						$html = str_replace('%horas_trabajadas%', '', $html);
						$html = str_replace('%retainer%', '', $html);
						$html = str_replace('%extraordinario%', '', $html);
						$html = str_replace('%simbolo_moneda_2%', '', $html);
					}

					$html = str_replace('%nombre%', __('ABOGADO'), $html);

					if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
						$html = str_replace('%valor_hh%', __('TARIFA'), $html);
					} else {
						$html = str_replace('%valor_hh%', '', $html);
					}

					$html = str_replace('%hrs_trabajadas%', ($mostrar_columnas_retainer || $columna_hrs_trabajadas) ? __('HRS TOT TRABAJADAS') : '', $html);
					$html = str_replace('%porcentaje_participacion%', __('PARTICIPACIÓN POR ABOGADO'), $html);
					$html = str_replace('%hrs_retainer%', $mostrar_columnas_retainer ? __('HRS TRABAJADAS VALOR RETAINER') : '', $html);
					$html = str_replace('%valor_retainer%', $mostrar_columnas_retainer ? __('COBRO') . __(' HRS VALOR RETAINER') : '', $html);
					$html = str_replace('%hh%', __('HRS TRABAJADAS VALOR TARIFA'), $html);
					$html = str_replace('%valor_cobrado_hh%', __('COBRO') . __(' HRS VALOR TARIFA'), $html);
				} else {
					$html = str_replace('%horas_trabajadas%', '', $html);
				}

				//recorriendo los datos para los titulos
				$retainer = false;
				$descontado = false;
				$flatfee = false;

				$totales = $this->ChargeData->getTotal();
				if (is_array($totales)) {
					if ($totales['duracion_retainer'] > 0 && $this->fields['forma_cobro'] != 'PROPORCIONAL' && ($this->fields['forma_cobro'] != 'FLAT FEE' || Conf::GetConf($this->sesion, 'ResumenProfesionalVial'))) {
						$retainer = true;
					}
					if (($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
						$retainer = true;
					}
					if ($totales['duracion_incobrables'] > 0) {
						$descontado = true;
					}
					if ($totales['flatfee'] > 0) {
						$flatfee = true;
					}
				}

				$html = str_replace('%nombre%', __('Nombre'), $html);
				$html = str_replace('%abogado%', __('abogado_raz'), $html);
				$html = str_replace('%tiempo_raz%', __('tiempo_raz'), $html);
				$html = str_replace('%tarifa_raz%', __('tarifa_raz'), $html);
				$html = str_replace('%importe_raz%', __('importe_raz'), $html);

				if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%fayca_hrs_descontadas%', '<td align="center">' . __('Hrs. Descontadas') . '</td>', $html);
					$html = str_Replace('%td_hrs_mins_descontadas_real%', '<td align="center">' . __('Hrs. Descontadas') . '</td>', $html);
				} else {
					$html = str_replace('%fayca_hrs_descontadas%', '', $html);
					$html = str_Replace('%td_hrs_mins_descontadas_real%', '', $html);
				}

				if ($descontado || $retainer || $flatfee) {
					$html = str_replace('%hrs_trabajadas%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%hrs_mins_trabajadas%', __('Hrs.:Mins. Trabajadas'), $html);
					$columna_hrs_trabajadas = true;
					$columna_hrs_trabajadas_categoria = true;
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%hrs_trabajadas_real%', __('Hrs. Trabajadas'), $html);
						$html = str_Replace('%hrs_descontadas_real%', __('Hrs. Descontadas'), $html);
						$html = str_replace('%hrs_mins_trabajadas_real%', __('Hrs.:Mins. Trabajadas'), $html);
						$html = str_Replace('%hrs_mins_descontadas_real%', __('Hrs.:Mins. Descontadas'), $html);
					} else {
						$html = str_replace('%hrs_trabajadas_real%', '', $html);
						$html = str_Replace('%hrs_descontadas_real%', '', $html);
						$html = str_replace('%hrs_mins_trabajadas_real%', '', $html);
						$html = str_Replace('%hrs_mins_descontadas_real%', '', $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hrs_trabajadas_real%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%hrs_trabajadas%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%hrs_descontadas_real%', __('Hrs. Descontadas'), $html);
					$html = str_replace('%horas_cobrables%', '', $html);
					$html = str_replace('%hrs_mins_trabajadas_real%', __('Hrs.:Mins. Trabajadas'), $html);
					$html = str_replace('%hrs_mins_descontadas_real%', __('Hrs.:Mins. Descontadas'), $html);
					$html = str_replace('%horas_mins_cobrables%', '', $html);
				} else {
					$html = str_replace('%hrs_trabajadas%', '', $html);
					$html = str_replace('%hrs_trabajadas_real%', '', $html);
					$html = str_replace('%hrs_mins_trabajadas%', '', $html);
					$html = str_replace('%hrs_mins_trabajadas_real%', '', $html);
				}

				if ($retainer) {
					$html = str_replace('%hrs_retainer%', __('Hrs. Retainer'), $html);
					$html = str_replace('%hrs_mins_retainer%', __('Hrs.:Mins. Retainer'), $html);
					$columna_hrs_retainer = true;
					$columna_hrs_retainer_categoria = true;
				} elseif ($flatfee) {
					$html = str_replace('%hrs_retainer%', __('Hrs. Flat Fee'), $html);
					$html = str_replace('%hrs_mins_retainer%', __('Hrs.:Mins. Flat Fee'), $html);
					$columna_hrs_retainer = true;
					$columna_hrs_flatfee_categoria = true;
				} else {
					$html = str_replace('%hrs_retainer%', '', $html);
					$html = str_replace('%hrs_mins_retainer%', '', $html);
				}

				if ($descontado) {
					$html = str_replace('%columna_horas_no_cobrables_top%', '<td align="center">&nbsp;</td>', $html);
					$html = str_replace('%columna_horas_no_cobrables%', '<td align="center">' . __('HRS NO<br>COBRABLES') . '</td>', $html);
					$html = str_replace('%hrs_descontadas%', __('Hrs. Descontadas'), $html);
					$html = str_replace('%hrs_descontadas_real%', __('Hrs. Descontadas'), $html);
					$html = str_replace('%hrs_mins_descontadas%', __('Hrs.:Mins. Descontadas'), $html);
					$html = str_replace('%hrs_mins_descontadas_real%', __('Hrs.:Mins. Descontadas'), $html);
					$columna_hrs_descontadas = true;
					$columna_hrs_descontadas_categoria = true;
				} else {
					$html = str_replace('%columna_horas_no_cobrables_top%', '', $html);
					$html = str_replace('%columna_horas_no_cobrables%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
					$html = str_replace('%hrs_mins_descontadas_real%', '', $html);
					$html = str_replace('%hrs_mins_descontadas%', '', $html);
				}

				$html = str_replace('%horas_cobrables%', __('Hrs. Cobrables'), $html);
				$html = str_replace('%horas_mins_cobrables%', __('Hrs.:Mins. Cobrables'), $html);
				$html = str_replace('%hrs_trabajadas_previo%', '', $html);
				$html = str_replace('%hrs_mins_trabajadas_previo%', '', $html);
				$html = str_replace('%abogados%', __('Abogados que trabajaron'), $html);

				$html = str_replace('%horas_descontadas%', __('Hrs. Rebajadas'), $html);
				$html = str_replace('%horas_no_cobrables%', __('Hrs. No Tarificadas'), $html);
				$html = str_replace('%horas_trabajadas_profesional%', __('Hrs. Trabajadas'), $html);

				if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hh_trabajada%', __($this->fields['codigo_idioma'] . '_Hrs Trabajadas'), $html);
					$html = str_replace('%td_descontada%', '<td align=\'center\' width=\'80\'>' . __('Hrs. Castigadas') . '</td>', $html);
					if ($retainer || $flatfee) {
						$html = str_replace('%hh_cobrable%', __('Hrs Cobradas'), $html);
					} else {
						$html = str_replace('%hh_cobrable%', '', $html);
					} if ($descontado) {
						$html = str_replace('%td_descontada%', '<td align=\'center\' width=\'80\'>%hh_descontada%</td>', $html);
						$html = str_replace('%hh_descontada%', __('Hrs.:Mins. Descontadas'), $html);
					} else {
						$html = str_replace('%td_descontada%', '', $html);
						$html = str_replace('%hh_descontada%', '', $html);
					}
				} else {
					$html = str_replace('%td_descontada%', '', $html);
					$html = str_replace('%hh_trabajada%', '', $html);
					$html = str_replace('%hh_descontada%', '', $html);
				}
				if ($retainer || $flatfee) {
					$html = str_replace('%td_cobrable%', '<td align=\'center\' width=\'80\'>%hh_cobrable%</td>', $html);
					$html = str_replace('%hh_cobrable%', __('Hrs. Trabajadas'), $html);
					if ($retainer) {
						$html = str_replace('%td_retainer%', '<td align=\'center\' width=\'80\'>%hh_retainer%</td>', $html);
						$html = str_replace('%hh_retainer%', __('Hrs. Retainer'), $html);
					} else {
						$html = str_replace('%td_retainer%', '', $html);
						$html = str_replace('%hh_retainer%', '', $html);
					}
				} else {
					$html = str_replace('%td_cobrable%', '', $html);
					$html = str_replace('%td_retainer%', '', $html);
					$html = str_replace('%hh_cobrable%', '', $html);
					$html = str_replace('%hh_retainer%', '', $html);
				}

				$html = str_replace('%hh%', __('Hrs. Tarificadas'), $html);
				$html = str_replace('%tiempo%', __('Tiempo'), $html);
				$html = str_replace('%hh_mins%', __('Hrs.:Mins. Tarificadas'), $html);
				$html = str_replace('%horas%', $retainer ? __('Hrs. Tarificadas') : __('Horas'), $html);
				$html = str_replace('%horas_retainer%', $retainer ? __('Hrs. Retainer') : '', $html);
				$html = str_replace('%horas_mins%', $retainer ? __('Hrs.:Mins. Tarificadas') : __('Horas'), $html);
				$html = str_replace('%horas_mins_retainer%', $retainer ? __('Hrs.:Mins. Retainer') : '', $html);

				if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td align="center" width="60">%valor_hh%</td>', $html);
					$html = str_replace('%td_tarifa_min%', '<td align="center" width="60">'.__('Tarifa').'</td>', $html);
					$html = str_replace('%td_tarifa_simbolo%', '<td align="center" width="60">%valor_hh_simbolo%</td>', $html);
					$html = str_replace('%valor_horas%', $flatfee ? '' : __('Tarifa'), $html);
					$html = str_replace('%valor_hh%', __('TARIFA'), $html);
					$html = str_replace('%valor_hh_simbolo%', __('TARIFA') . ' (' .$moneda->fields['simbolo'] . ')', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td align="center" width="60">' . __('TARIFA') . '</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_simbolo%', '', $html);
					$html = str_replace('%valor_horas%', '', $html);
					$html = str_replace('%valor_hh%', '', $html);
					$html = str_replace('%td_tarifa_min%', '', $html);
					$html = str_replace('%valor_hh_simbolo%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}

				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);
				$html = str_replace('%simbolo_moneda%', $flatfee ? '' : ' (' . $moneda->fields['simbolo'] . ')', $html);

				if ($this->fields['opc_ver_profesional_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="right" width="70">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td align="right" width="70">%importe_ajustado%</td>', $html);
					$html = str_replace('%importe%', __($this->fields['codigo_idioma'] . '_IMPORTE'), $html);
					$html = str_replace('%importe_ajustado%', __($this->fields['codigo_idioma'] . '_IMPORTE'), $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
					$html = str_replace('%importe%', '', $html);
					$html = str_replace('%importe_ajustado%', '', $html);
				}

				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%honorarios%', __('Honorarios'), $html);

				if ($this->fields['opc_ver_profesional_categoria'] == 1){
					$html = str_replace('%categoria%', __($this->fields['codigo_idioma'] . '_CATEGORÍA'), $html);
					$html = str_replace('%categoria_min%', __('Categoría'), $html);
				} else {
					$html = str_replace('%categoria%', '', $html);
					$html = str_replace('%categoria_min%', '', $html);
				}

								$html = str_replace('%staff%', __('Staff'), $html);
				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%nombre_profesional%', __('Nombre Profesional'), $html);

				if ($lang == 'es') {
					$html = str_replace('%profesional%', __('Profesional'), $html);
					$html = str_replace('%hora_tarificada%', __('Trarifa<br>Hora'), $html);
				} else {
					$html = str_replace('%profesional%', __('Biller'), $html);
					$html = str_replace('%hora_tarificada%', __('Hourly<br>Rate'), $html);
				}
				break;
		}
		return $html;
	}
}
