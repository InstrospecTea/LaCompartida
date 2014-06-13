<?php

/**
 * @author abraham.barrera
 * Export to JSON any report and subreports
 *
 */
class SimpleReport_Writer_Json implements SimpleReport_Writer_IWriter {

	/**
	 * @var SimpleReport
	 */
	var $SimpleReport;

	/**
	 * Para el uso de la función ACUMULAR
	 */
	private $acumuladores = array();

	public function __construct(SimpleReport $simpleReport) {
		$this->SimpleReport = $simpleReport;
	}

	public function save($filename = null, $group_values = null) {
		$result = $this->SimpleReport->RunReport($group_values);
		$columns = $this->SimpleReport->Config->VisibleColumns();
		$filters = $this->SimpleReport->filters;
		$json = array('headers' => $columns, 'filters' => $filters);
		if (!empty($result)) {
			$json['results'] = $this->table($result, $columns);
		}
		if (!$group_values) {
			return $this->outputJson($json);
		} else {
			return $json;
		}
	}

	function outputJson($response) {
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header('Content-type: application/json; charset=utf-8');
		$response = UtilesApp::utf8izar($response);
		array_walk_recursive($response, function(&$x) { if (is_string($x)) $x = trim($x); });
		echo json_encode($response);
		exit;
	}

	private function table($result, $columns) {
		// 1. Ordenar y filtrar segun conf
		// 2. Escribir en json
		$report = $this->SimpleReport;
		$json = array();

		foreach ($result as $row_idx => $row) {
			$row_array = array();
			foreach ($columns as $col_idx => $column) {
				$row_array = array_merge($row_array, $this->element($row, $column));
			}
			if (isset($report->SubReport)) {
				$values = array();
				foreach ($report->SubReport['Keys'] as $key) {
					$values[$key] = $row[$key];
				}
				$writer = SimpleReport_IOFactory::createWriter($report->SubReport['SimpleReport'], 'Json');
				$subreport_json = $writer->save('', $values);
				$row_array = array_merge($row_array, array('detail' => $subreport_json));
			}
			$json[] = $row_array;
		}
		return $json;
	}

	private function element($row, $column, $extras ='') {
		$valor = '';
		if (strpos($column->field, '=') !== 0) {
			$valor = $row[$column->field];
		} else {
			//es una formula: reemplazar los nombres de campos por celdas
			if (preg_match('/=(\w+)\((.+)\)/', $column->field, $matches)) {
				switch ($matches[1]) {
					case 'SUM':
						$valor = 0;
						$params = explode(',', $matches[2]);
						foreach ($params as $param) {
							$param = trim($param);
							if (strpos($param, '%') === 0) {
								$valor += str_replace(',', '.', $row[trim($param, '%')]);
							} else if (is_numeric($param)) {
								$valor += $param;
							}
						}
						break;
					case 'CONCATENATE':
						$params = explode(',', $matches[2]);
						foreach ($params as $param) {
							$param = trim($param);
							if (strpos($param, '%') === 0) {
								$valor .= $row[trim($param, '%')];
							} else {
								$valor .= trim($param, '"');
							}
						}
						break;

					case 'ACUMULAR':
						$original_param = $matches[2];
						$params = explode(',', $original_param);
						foreach ($params as $param) {
							$param = trim($param);
							if (strpos($param, '%') === 0) {
								$param_field = trim($param, '%');
								if (!array_key_exists($original_param, $this->acumuladores)) {
									$this->acumuladores[$original_param] = 0;
								}
								$this->acumuladores[$original_param] += $row[$param_field];
							}
						}
						$valor = $this->acumuladores[$original_param];
						break;
				}
			}
		}

		switch ($column->format) {
			case 'text':
				if (strpos($valor, ";")) {
					$valor = str_replace(";", "\n", $valor);
				}
				break;
			case 'date':
				// El componente Excel reconoce las fechas sin formato
				// $valor = Utiles::sql2fecha($valor, $this->SimpleReport->regional_format['date_format']);
				break;
			case 'time':
				$valor = UtilesApp::Hora2HoraMinuto($valor);
				break;
		}

		$attrs = isset($column->extras['attrs']) ? $column->extras['attrs'] : '';
		if (isset($column->extras['rowspan'])) {
			$attrs .= ' rowspan="' . $column->extras['rowspan'] . '"';
		}

		$class = 'buscador';
		if (isset($column->extras['class'])) {
			$class .= ' ' . $column->extras['class'];
		}

		if ($column->format == 'number') {
			return array($column->field =>  (float) $valor);
		} else {
			return array($column->field => "$extras$valor");
		}

	}

}

