<?php

class SimpleReport_Writer_HtmlList implements SimpleReport_Writer_IWriter {

	/**
	 * @var SimpleReport
	 */
	var $SimpleReport;
	public $formato_fecha = "%d/%m/%Y";

	public function __construct(SimpleReport $simpleReport) {
		$this->SimpleReport = $simpleReport;
	}

	public function save($filename = null) {
		// 1. Construir base
		$html = "<html>
			<head>
				<title>$filename</title>
				<style>
					ul span{font-weight: bold;}

					ol{list-style: none;}
					ol ol{list-style: upper-roman;}
					ol ol ol{list-style: upper-latin;}
					ol ol ol ol{list-style: decimal;}
				</style>
			</head>
			<body>";

		//ksort($this->configuration->columns);
		// 1.1 Formatear todos los arreglos
		// 2. Correr query
		$result = $this->SimpleReport->RunReport();

		if (empty($result)) {
			$html .= '<table class="buscador" width="90%" cellpadding="3">
					<tr><td colspan="50"><strong><em>' . __('No se encontraron resultados') . '</em></strong></td></tr>
				</table>';
		} else {
			$columns = $this->SimpleReport->Config->VisibleColumns();

			//agrupadores de resultados
			$groups = array();
			foreach ($columns as $idx => $column) {
				if ($column->group) {
					$groups[$column->group.' '] = $column->field;
					unset($columns[$idx]);
				}
			}
			ksort($groups);

			$html .= $this->groups($result, $columns, $groups);
		}

		// 4.4 Estilos de columnas
		$html .= '</body></html>';

		// 5. Descargar
//		if (empty($filename)) {
//			$filename = $this->SimpleReport->Config->title;
//		}

		return $html;
	}

	private function groups($result, $columns, $groups) {
		if (empty($groups)) {
			return $this->table($result, $columns);
		} else {
			$h = key($groups);
			$column = array_shift($groups);

			$grouped_rows = array();
			foreach ($result as $row) {
				$group = $row[$column];
				if (!isset($grouped_rows[$group])) {
					$grouped_rows[$group] = array();
				}
				$grouped_rows[$group][] = $row;
			}

			$html = '';
			foreach ($grouped_rows as $group => $rows) {
				$subgroups = $this->groups($rows, $columns, $groups);
				$html .= "
				<li>
					<h$h>$group</h$h>
					$subgroups
				</li>";
			}

			return "<ol>$html</ol>";
		}
	}

	private function table($result, $columns) {
		// 3. Ordenar y filtrar segun conf
		// 4. Escribir en excel
		// 4.1 Headers

		// 4.2 Body
		$html .= '';
		foreach ($result as $row) {
			$tds = '';
			foreach ($columns as $column) {
				$tds .= $this->td($row, $column);
			}
			$html .= "<ul>$tds</ul><br/>";
		}

		return $html;
	}

	private function td($row, $column) {
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
								$valor += $row[trim($param, '%')];
							} else if (is_numeric($param)) {
								$valor += $param;
							}
						}
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
				}
			}
		}

		switch ($column->format) {
			case 'text':
				if (strpos($valor, ";")) {
					//$valor = str_replace("", "<br />", $valor);
				}
				break;
			case 'number':
				$valor = number_format($valor, 2, ',', '.');
				if (isset($column->extras['symbol'])) {
					$symbol = array_key_exists($column->extras['symbol'], $row) ? $row[$column->extras['symbol']] : $column->extras['symbol'];
					$valor = "$symbol $valor";
				}
				break;
			case 'date':
				$valor = Utiles::sql2fecha($valor, $this->formato_fecha);
				break;
		}

		return "<li><span>{$column->title}</span>: $valor</li>";
	}

}

