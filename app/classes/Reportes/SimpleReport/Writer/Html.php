<?php

//require_once SIMPLEREPORT_ROOT . '../Excel/PHPExcel.php';

/**
 * @author matias.orellana
 */
class SimpleReport_Writer_Html implements SimpleReport_Writer_IWriter {

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
		$html = '<form id="form_buscador">';

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
					$groups[$column->group] = $column->field;
					unset($columns[$idx]);
				}
			}
			ksort($groups);

			$html .= $this->groups($result, $columns, $groups);
		}

		// 4.4 Estilos de columnas
		$html .= '</form>';

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
				<div>
					<h$h style=\"text-align: left\">$group</h$h>
					$subgroups
				</div>";
			}

			return $html;
		}
	}

	private function table($result, $columns) {
		// 3. Ordenar y filtrar segun conf
		// 4. Escribir en excel
		// 4.1 Headers
		$html .= '<table class="buscador" width="90%" cellpadding="3"><thead><tr class="encabezado">';

		for ($col_i = 0; $col_i < count($columns); $col_i++) {
			$column = array_pop(array_slice($columns, $col_i, 1));

			$attrs = isset($column->extras['attrs']) ? $column->extras['attrs'] : '';
			$html .= '<td class="encabezado" ' . $attrs . '>' . utf8_decode($column->title) . '</th>';
		}

		$html .= '</tr></thead>';

		$formatos_con_total = array('number', 'time');
		$totals_rows = array();

		// 4.2 Body
		$html .= '<tbody>';
		$row_i = 0;
		foreach ($result as $row_idx => $row) {
			$tds = '';
			foreach ($columns as $col_idx => $column) {
				if (in_array($column->format, $formatos_con_total) &&
					(!isset($column->extras['subtotal']) || $column->extras['subtotal'])) {
					$grupo_subtotal = isset($column->extras['subtotal']) && is_string($column->extras['subtotal']) ? $row[$column->extras['subtotal']] : '';
					if (!isset($totals_rows[$grupo_subtotal])) {
						$totals_rows[$grupo_subtotal] = array('row' => $row, 'totals' => array());
					}
					$totals_rows[$grupo_subtotal]['totals'][$col_idx] += $row[$column->field];
				}

				if (isset($column->extras['groupinline'])) {
					if (isset($result[$row_idx - 1]) && $result[$row_idx - 1][$column->field] == $row[$column->field]) {
						$row_i--; //para q al pasar a la sgte fila siga con el mismo color
						continue;
					}

					$rowspan = 1;
					while (isset($result[$row_idx + $rowspan]) && $result[$row_idx + $rowspan][$column->field] == $row[$column->field]) {
						$rowspan++;
					}
					$column->extras['rowspan'] = $rowspan;
				}

				$tds .= $this->td($row, $column);
			}

			$color = $row_i++ % 2 ? 'eeeeee' : 'ffffff';
			$html .= "<tr bgcolor=\"#$color\">$tds</tr>";
		}

		// 4.3 Totales
		if (!empty($totals_rows)) {
			$html .= '<tr class="subtotal" style="border-top:1px solid #000"><td colspan="' . count($columns) . '" align="left">Totales</td></tr>';
			foreach ($totals_rows as $totals_row) {
				$totals = $totals_row['totals'];
				$row = $totals_row['row'];
				$html .= '<tr class="subtotal">';

				foreach ($columns as $idx => $column) {
					if (isset($totals[$idx])) {
						$row[$column->field] = $totals[$idx];
						$html .= $this->td($row, $column);
					} else {
						$html .= '<td>&nbsp;</td>';
					}
				}

				$html .= '</tr>';
			}
		}

		return $html . '</tbody></table>';
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
					$valor = str_replace(";", "<br />", $valor);
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

		$attrs = isset($column->extras['attrs']) ? $column->extras['attrs'] : '';
		if (isset($column->extras['rowspan'])) {
			$attrs .= ' rowspan="' . $column->extras['rowspan'] . '"';
		}

		$class = 'buscador';
		if (isset($column->extras['class'])) {
			$class .= ' ' . $column->extras['class'];
		}

		return "<td class=\"$class\" $attrs>$valor</td>";
	}

}

