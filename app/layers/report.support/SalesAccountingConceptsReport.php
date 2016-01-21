<?php

class SalesAccountingConceptsReport extends AbstractReport implements ISalesAccountingConceptsReport {

	private $format = array();

	protected function setUp() {
		$this->setOutputType('Spreadsheet');
		$this->reportEngine->engine->setCustomColor(35, 220, 255, 220);
		$this->reportEngine->engine->setCustomColor(36, 255, 255, 220);
		$this->setConfiguration('filename', __('reporte_ventas'));
	}

	protected function agrupateData($data) {
		return $data;
	}

	protected function present() {
		$this->setFormat();
		$row = 0;

		$currency = $this->parameters['display_currency']->fields['id_moneda'];
		$currency_symbol = $this->parameters['display_currency']->fields['simbolo'];

		$periodo_inicial = substr($this->parameters['start_date'], 0, 4) * 12 + substr($this->parameters['start_date'], 5, 2);
		$periodo_final = substr($this->parameters['end_date'], 0, 4) * 12 + substr($this->parameters['end_date'], 5, 2);
		$time_periodo = strtotime($this->parameters['start_date']);

		$ws = $this->reportEngine->engine->addWorksheet(__('Reportes'));
		$ws->setInputEncoding('utf-8');
		$ws->fitToPages(1, 0);
		$ws->setZoom(75);
		$ws->setColumn(1, 1, 70.00);
		$ws->setColumn(2, 2 + ($periodo_final - $periodo_inicial), 15.15);

		$row += 1;
		$ws->mergeCells($row, 1, $row, 13);
		$ws->write($row, 1, __('REPORTE TOTAL MONTOS FACTURADOS MENSUALMENTE'), $this->format['header']);

		for ($x = 2; $x < 14; $x++) {
			$ws->write($row, $x, '', $this->format['header']);
		}

		$row += 2;
		$ws->write($row, 1, __('GENERADO EL:'), $this->format['text']);
		$ws->mergeCells($row, 2, $row, 13);
		$ws->write($row, 2, date("d-m-Y H:i:s"), $this->format['text']);

		for ($x = 3; $x < 14; $x++) {
			$ws->write($row, $x, '', $this->format['text']);
		}

		$row += 1;
		$ws->write($row, 1, __('PERIODO ENTRE:'), $this->format['text']);
		$ws->mergeCells($row, 2, $row, 13);
		$ws->write($row, 2, "{$this->parameters['start_date']} HASTA {$this->parameters['end_date']}", $this->format['text']);

		for ($x = 3; $x < 14; $x++) {
			$ws->write($row, $x, '', $this->format['text']);
		}

		$row++;
		$glosa_comparacion = __('Los montos facturados se comparan con el monto THH segun') . ' ';

		if ($this->parameters['rate'] == 'monto_thh') {
			$glosa_comparacion .= __("tarifa del cliente");
		} else {
			$glosa_comparacion .= __("tarifa estandar");
		}

		$ws->write($row, 1, $glosa_comparacion, $this->format['text']);
		$ws->mergeCells($row, 1, $row, 13);

		$row += 3;

		list($x_anio_ini, $x_mes_ini, $x_dia_ini) = explode('-', $this->parameters['start_date']);
		list($x_anio_fin, $x_mes_fin, $x_dia_fin) = explode('-', $this->parameters['end_date']);

		for ($i = 0; $i <= ($periodo_final - $periodo_inicial); $i++) {
			// Se imprime el titulo del periodo
			$ws->write($row, $i + 2, date('M Y', $time_periodo), $this->format['title']);
			$time_periodo = strtotime('+1 month', $time_periodo);
		}

		$m = $x_mes_ini;

		for ($a = $x_anio_ini; $a <= $x_anio_fin; $a++) {
			if ($a == $x_anio_fin) {
				$mes_f = $x_mes_fin;
			} else {
				$mes_f = 12;
			}

			for (; $m <= $mes_f; $m++) {
				$dosdigitos = 2 - strlen($m);

				if ($dosdigitos > 0) {
					$m = "0" . $m;
				}

				$select_col .= " ,IF(DATE_FORMAT(fecha_emision,'%Y-%m')='" . $a . "-" . $m . "', id_cobro,null) AS emitido_" . $a . $m . " ";
				$select_group .= " ,group_concat(emitido_" . $a . $m . ") AS list_idcobro_" . $a . $m . " ";
			}

			$m = 1;
		}

		$where = "";

		if (is_array($this->parameters['clients'])) {
			$where = $where . " AND cobro.codigo_cliente IN ('" . join("','", $this->parameters['clients']) . "') ";
		}

		if (is_array($this->parameters['client_group'])) {
			$where .= " AND cliente.id_grupo_cliente IN ( '" . join("','", $this->parameters['client_group']) . "') ";
		}

		if (is_array($this->parameters['billing_strategy'])) {
			$where = $where . " AND cobro.forma_cobro IN ('" . join("','", $this->parameters['billing_strategy']) . "') ";
		}

		if (is_array($this->parameters['invoiced'])) {
			$where .= " AND cobro.opc_moneda_total IN ( '" . join("','", $this->parameters['invoiced']) . "') ";
		}

		$query = "SELECT
								codigo_cliente
								,glosa_cliente
								,fecha_emision
								$select_group
								FROM(SELECT
								cliente.codigo_cliente as codigo_cliente
								,cliente.glosa_cliente as glosa_cliente
								,fecha_emision
								$select_col
								FROM cobro
								LEFT JOIN cliente AS cliente ON cobro.codigo_cliente=cliente.codigo_cliente
								WHERE cobro.estado <> 'CREADO'
								AND cobro.estado <> 'EN REVISION'
								AND cobro.fecha_emision BETWEEN '{$this->parameters['start_date']} 00:00:00' AND '{$this->parameters['end_date']} 23:59:59' $where
								)ZZ
								GROUP BY codigo_cliente
								ORDER BY glosa_cliente";

		$resp = mysql_query($query, $this->Session->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->Session->dbh);

		$campo_monto = "monto_honorarios";
		$campo_monto_thh = $this->parameters['rate'];

		$glosa_comentario = array();
		$ultimo_cliente = '';

		while ($charge = mysql_fetch_array($resp)) {
			$glosa_comentario[$charge['codigo_cliente']] = array();
			$x_monto[$charge['codigo_cliente']]['glosa_cliente'] = $charge['glosa_cliente'];

			$m = $x_mes_ini;

			for ($a = $x_anio_ini; $a <= $x_anio_fin; $a++) {
				if ($a == $x_anio_fin) {
					$mes_f = $x_mes_fin;
				} else {
					$mes_f = 12;
				}

				for (; $m <= $mes_f; $m++) {
					$dosdigitos = 2 - strlen($m);

					if ($dosdigitos > 0) {
						$m = "0" . $m;
					}

					if ($charge['list_idcobro_' . $a . $m] != null) {
						$arr_idcobro_cliente = array();
						$arr_idcobro_cliente = explode(",", $charge['list_idcobro_' . $a . $m]);

						if (count($arr_idcobro_cliente) >= 0) {
							for ($o = 0; $o < count($arr_idcobro_cliente); $o++) {
								$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->Session, $arr_idcobro_cliente[$o]);
								$x_monto[$charge['codigo_cliente']]['monto_' . $a . $m] += $x_resultados[$campo_monto][$currency];
								$x_monto[$charge['codigo_cliente']]['monto_thh_' . $a . $m] += $x_resultados[$campo_monto_thh][$currency];
								$x_monto[$charge['codigo_cliente']]['id_cobro_' . $a . $m] .= $arr_idcobro_cliente[$o] . " , ";

								if ($arr_idcobro_cliente[$o] > 0) {
									$glosa_comentario[$charge['codigo_cliente']][$a . $m] = $glosa_comentario[$charge['codigo_cliente']][$a . $m] . "C" . $arr_idcobro_cliente[$o] . ": $currency_symbol" . $x_resultados[$campo_monto][$currency] . "\n";
								}
							}
						} else {
							$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->Session, $charge['list_idcobro_' . $a . $m]);
							$x_monto[$charge['codigo_cliente']]['monto_' . $a . $m] += $x_resultados[$campo_monto][$currency];
							$x_monto[$charge['codigo_cliente']]['monto_thh_' . $a . $m] += $x_resultados[$campo_monto_thh][$currency];
							$x_monto[$charge['codigo_cliente']]['id_cobro_' . $a . $m] .= $charge['list_idcobro_' . $a . $m] . " , ";

							if ($charge['list_idcobro_' . $a . $m] > 0) {
								$glosa_comentario[$charge['codigo_cliente']][$a . $m] = $glosa_comentario[$charge['codigo_cliente']][$a . $m] . "C" . $charge['list_idcobro_' . $a . $m] . ": $currency_symbol" . $x_resultados[$campo_monto][$currency] . "\n";
							}
						}
					}
				}

				$m = 1;
			}

			if ($ultimo_cliente != $charge['codigo_cliente']) {
				$ultimo_cliente = $charge['codigo_cliente'];
				$row++;
				$total_clientes++;
				$ws->write($row, 1, $x_monto[$charge['codigo_cliente']]['glosa_cliente'], $this->format['title']);

				for ($i = 0; $i <= ($periodo_final - $periodo_inicial); $i++) {
					$ws->write($row, 2 + $i, '', $this->format['currency']);
				}
			}

			$col = 1;

			$m = $x_mes_ini;

			for ($a = $x_anio_ini; $a <= $x_anio_fin; $a++) {
				if ($a == $x_anio_fin) {
					$mes_f = $x_mes_fin;
				} else {
					$mes_f = 12;
				}

				for (; $m <= $mes_f; $m++) {
					$dosdigitos = 2 - strlen($m);
					if ($dosdigitos > 0) {
						$m = "0" . $m;
					}

					$col++;

					if ($x_monto[$charge['codigo_cliente']]['monto_' . $a . $m] < $x_monto[$charge['codigo_cliente']]['monto_thh_' . $a . $m]) {
						$formato = $this->format['currency_red'];
					} else {
						$formato = $this->format['currency'];
					}

					if ($x_monto[$charge['codigo_cliente']]['monto_thh_' . $a . $m] == 0) {
						$diferencia_cobrada = "inf";
					} else {
						$diferencia_cobrada = floor(100 * ($x_monto[$charge['codigo_cliente']]['monto_' . $a . $m]) / $x_monto[$charge['codigo_cliente']]['monto_thh_' . $a . $m]) . "%";
					}

					$glosa_comentario[$charge['codigo_cliente']][$a . $m] .= "Valor THH: $currency_symbol " . $x_monto[$charge['codigo_cliente']]['monto_thh_' . $a . $m] . "\n";
					$glosa_comentario[$charge['codigo_cliente']][$a . $m] .= "Cobrado/THH :" . $diferencia_cobrada;

					if ($x_monto[$charge['codigo_cliente']]['monto_' . $a . $m] != 0) {
						$ws->writeNote($row, $col, $glosa_comentario[$charge['codigo_cliente']][$a . $m]);
						$ws->write($row, $col, number_format($x_monto[$charge['codigo_cliente']]['monto_' . $a . $m], $moneda->fields['cifras_decimales'], '.', ''), $formato);
					}
				}

				$m = 1;
			}
		}

		// TOTALES
		$fila_inicial = ($row - $total_clientes) + 2;
		$row +=1;

		if ($total_clientes > 0) {
			$ws->write($row, 1, __('Total'), $this->format['title']);

			for ($z = 2; $z <= 2 + ($periodo_final - $periodo_inicial); $z++) {
				$columna = Utiles::NumToColumnaExcel($z);
				$ws->writeFormula($row, $z, "=SUM($columna" . $fila_inicial . ":$columna" . $row . ")", $this->format['currency']);
			}
		} else {
			$ws->mergeCells($row, 2, $row, 13);
			$ws->write($row, 2, __('No se encontraron resultados'), $this->format['text']);

			for ($x = 3; $x < 14; $x++) {
				$ws->write($row, $x, '', $this->format['text']);
			}
		}
	}

	private function setFormat() {
		$currency_decimals = '';
		$currency_symbol = $this->parameters['display_currency']->fields['simbolo'];

		if (!empty($this->parameters['display_currency']->fields['cifras_decimales'])) {
			$currency_decimals = '.';
			for ($x = 0; $x < (int) $this->parameters['display_currency']->fields['cifras_decimales']; $x++) {
				$currency_decimals .= '0';
			}
		}

		$this->format['header'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 12,
				'VAlign' => 'top',
				'Align' => 'left',
				'Bold' => '1',
				'underline' => 1,
				'Color' => 'black'
			)
		);
		$this->format['text'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 11,
				'Valign' => 'top',
				'Align' => 'left',
				'Color' => 'black'
			)
		);
		$this->format['title'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 12,
				'Align' => 'center',
				'Bold' => '1',
				'FgColor' => '35',
				'Border' => 1,
				'Locked' => 1,
				'Color' => 'black'
			)
		);
		$this->format['currency'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 11,
				'VAlign' => 'top',
				'Align' => 'right',
				'Border' => 1,
				'Color' => 'black',
				'NumFormat' => "[\${$currency_symbol}] #,###,0{$currency_decimals}"
			)
		);
		$this->format['currency_red'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 11,
				'VAlign' => 'top',
				'Align' => 'right',
				'Border' => 1,
				'Bold' => 1,
				'Color' => 'red',
				'NumFormat' => "[\${$currency_symbol}] #,###,0{$currency_decimals}"
			)
		);
	}

}
