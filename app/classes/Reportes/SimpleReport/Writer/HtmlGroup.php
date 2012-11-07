<?php

class SimpleReport_Writer_HtmlGroup implements SimpleReport_Writer_IWriter {

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
		//ksort($this->configuration->columns);
		// 1.1 Formatear todos los arreglos
		// 2. Correr query
		$result = $this->SimpleReport->RunReport();

		if (empty($result)) {
			return '<table class="buscador" width="90%" cellpadding="3">
					<tr><td colspan="50"><strong><em>' . __('No se encontraron resultados') . '</em></strong></td></tr>
				</table>';
		}

		$columns = $this->SimpleReport->Config->VisibleColumns();

		//agrupadores de resultados
		$groups = array();
		$groups_columns = array();
		foreach ($columns as $idx => $column) {
			if ($column->group) {
				$groups[$column->group . ' '] = $column->field;
				$groups_columns[$column->field] = $column;
				unset($columns[$idx]);
			}
		}
		ksort($groups);

		$data = array();
		foreach ($result as $row) {
			$x = &$data;
			foreach ($groups as $group) {
				$x[$row[$group]][$group] = $row[$group];
				$x[$row[$group]]['count']++;
				foreach ($columns as $column) {
					if ($column->field != 'count') {
						switch ($column->format) {
							case 'number':
								$x[$row[$group]][$column->field] += $row[$column->field];
								break;
							//TODO: rangos para fechas? concatenaciones para textos?
							default:
								$x[$row[$group]][$column->field] = $row[$column->field];
						}
						if (isset($column->extras['symbol']) && array_key_exists($column->extras['symbol'], $row)) {
							$x[$row[$group]][$column->extras['symbol']] = $row[$column->extras['symbol']];
						}
						if (isset($column->extras['decimals']) && array_key_exists($column->extras['decimals'], $row)) {
							$x[$row[$group]][$column->extras['decimals']] = $row[$column->extras['decimals']];
						}
					}
				}
				$x = &$x[$row[$group]]['detalles'];
			}
		}
		$this->rowspan($data);

//		print_r($groups);
//		print_r($columns);
//		print_r($data);

		$html = '<table><tr>';
		foreach ($groups_columns as $group) {
			$html .= "<th>$group->title</th>";
			foreach ($columns as $column) {
				$html .= "<th>$column->title</th>";
			}
		}
		$html .= '</tr>';

		$html .= $this->trs($data, $columns, $groups_columns);

		$html .= '</table>';

		// 4.4 Estilos de columnas
		// 5. Descargar
//		if (empty($filename)) {
//			$filename = $this->SimpleReport->Config->title;
//		}

		$datos = array();
		$labels = array();
		$column = reset($columns);
		foreach($data as $grupo => $valores){
			$datos[] = $valores[$column->field];
			$labels[] = $this->escape($grupo);
		}

		$s = '&chd=t:' . implode(',', $datos) . '&chxl=1:|' . implode('|', array_reverse($labels)). '|';
		$h = min(count($datos) * 30 + 60, 1000);
		$g = reset($groups_columns);
		$t = '&chtt=' . $this->escape($column->title . ' por ' . $g->title);
		$html .= '<img height="'.$h.'" width="600" src="https://chart.googleapis.com/chart?cht=bhg&chs=600x'.$h.'&chxt=x,y&chds=a'.$s.$t.'"/>';
		$s = '&chd=t:' . implode(',', $datos) . '&chl=' . implode('|', array_reverse($labels));
		$html .= '<img height=300 width=550 src="https://chart.googleapis.com/chart?cht=p&chs=550x300&chxt=x,y&chds=a'.$s.$t.'"/>';

		return $html;
	}

	private function escape($s){
		return urlencode(str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ñ', '°', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'), array('a', 'e', 'i', 'o', 'u', 'n', '', 'A', 'E', 'I', 'O', 'U', 'N'), $s));
	}
	
	private function rowspan(&$data) {
		if (empty($data)) {
			return 1;
		}
		$r = 0;
		foreach (array_keys($data) as $group) {
			$data[$group]['rowspan'] = $this->rowspan($data[$group]['detalles']);
			$r += $data[$group]['rowspan'];
		}
		return $r;
	}

	private function trs($data, $columns, $groups_columns, $tr = true) {
		$html = '';
		$group_column = array_shift($groups_columns);
		$group_class = $group_column->extras['class'];
		$group_column->extras['class'] .= ' group';
		foreach ($data as $group => $group_data) {
			if ($tr) {
				$html .= '<tr>';
			}

			$r = $group_data['rowspan'] > 1 ? " rowspan=\"{$group_data['rowspan']}\"" : '';

			$group_column->extras['rowspan'] = $group_data['rowspan'];
			$html .= $this->td($group_data, $group_column);
			foreach ($columns as $column) {
				$column->extras['rowspan'] = $group_data['rowspan'];
				$class = $column->extras['class'];
				$column->extras['class'] = "$group_class $class";
				$html .= $this->td($group_data, $column);
				$column->extras['class'] = $class;
			}

			if (!empty($group_data['detalles'])) {
				$html .= $this->trs($group_data['detalles'], $columns, $groups_columns, false);
			} else if (!$tr) {
				$html .= '</tr>';
			}

			if (!$tr) {
				$tr = true;
			}
		}

		$group_column->extras['class'] = $group_class;
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

		$attrs = isset($column->extras['attrs']) ? $column->extras['attrs'] : '';

		switch ($column->format) {
			case 'text':
				if (strpos($valor, ";")) {
					$valor = str_replace(";", "<br />", $valor);
				}
				break;
			case 'number':
				$decimals = 2;
				if (isset($column->extras['decimals'])) {
					$decimals = array_key_exists($column->extras['decimals'], $row) ? $row[$column->extras['decimals']] : $column->extras['decimals'];
				}
				$valor = number_format($valor, $decimals, ',', '.');
				if (isset($column->extras['symbol'])) {
					$symbol = array_key_exists($column->extras['symbol'], $row) ? $row[$column->extras['symbol']] : $column->extras['symbol'];
					$valor = "$symbol $valor";
					$attrs .= ' nowrap="nowrap"';
				}
				break;
			case 'date':
				$valor = Utiles::sql2fecha($valor, $this->formato_fecha);
				break;
		}

		if (isset($column->extras['rowspan']) && $column->extras['rowspan'] > 1) {
			$attrs .= ' rowspan="' . $column->extras['rowspan'] . '"';
		}

		$class = '';
		if (isset($column->extras['class'])) {
			$class = ' class="' . $column->extras['class'] . '"';
		}

		return "<td $class $attrs>$valor</td>";
	}

}

