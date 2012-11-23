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
	/**
	 * una tabla (con un encabezado) para todos los grupos
	 */
	private $single_table = false;

	public function __construct(SimpleReport $simpleReport) {
		$this->SimpleReport = $simpleReport;
	}

	public function save($filename = null, $group_values = null) {
		// 1. Construir base
		$html = '<form id="form_buscador">';

		//ksort($this->configuration->columns);
		// 1.1 Formatear todos los arreglos
		// 2. Correr query
		$result = $this->SimpleReport->RunReport($group_values);

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
					$groups[$column->group . ' '] = $column->field;
					unset($columns[$idx]);
				}
			}
			ksort($groups);

			$groups_html = $this->groups($result, $columns, $groups, $totals, 'Total');
			if($this->single_table){
				$groups_html = '<table class="buscador" width="90%" cellpadding="3">' .
					$this->header($columns) .
					$groups_html .
					'</table>';
			}
			$html .= $groups_html;
		}

		// 4.4 Estilos de columnas
		$html .= '</form>';

		// 5. Descargar
//		if (empty($filename)) {
//			$filename = $this->SimpleReport->Config->title;
//		}
		if ($this->SimpleReport->custom_format['collapsible']) {
				$html .= "<script>jQuery('.ver-detalle').click(function(){
					jQuery(this).closest('tr').next().find('.subreport').toggle();
					return false;
				});</script>";
		}

		return $html;
	}

	private function groups($result, $columns, $groups, &$totals, $total_name, $h = 1) {
		if (empty($groups)) {
			return $this->table($result, $columns, $totals, $total_name, $h);
		} else {
			if(isset($this->SimpleReport->custom_format['single_table'])){
				$this->single_table = $this->SimpleReport->custom_format['single_table'];
			}
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
			$totals = array();
			foreach ($grouped_rows as $group => $rows) {
				$subgroups = $this->groups($rows, $columns, $groups, $subtotals, 'Subtotal ' . $group, $h+1);
				//totales
				if($this->single_table) {
					$html .= "
						<tr>
							<td colspan='1000'>
								<h$h style=\"text-align: left\">$group</h$h>
							</td>
						</tr>
						$subgroups";
					//acumular el total de este grupo
					if(!empty($subtotals)){
						foreach ($subtotals as $g => $totals_row) {
							if (!isset($totals[$g])) {
								$totals[$g] = array('row' => $totals_row['row'], 'totals' => array());
							}
							foreach($totals_row['totals'] as $col_idx => $val){
								$totals[$g]['totals'][$col_idx] += $val;
							}
						}
					}
				} else {
					$html .= "
						<div>
							<h$h style=\"text-align: left\">$group</h$h>
							$subgroups
						</div>";
				}
			}

			if(!empty($totals)) {

				$html .= $this->totales($totals, $columns, $total_name, $h);
			}

			return $html;
		}
	}

	private function header($columns){
		$tds = '';
		for ($col_i = 0; $col_i < count($columns); $col_i++) {
			$column = array_pop(array_slice($columns, $col_i, 1));

			$attrs = isset($column->extras['attrs']) ? $column->extras['attrs'] : '';
			$tds .= '<td class="encabezado" ' . $attrs . '>' . utf8_decode($column->title) . '</th>';
		}
		return "<thead><tr class='encabezado'> $tds </tr></thead>";
	}

	private function table($result, $columns, &$totals_rows, $totals_name, $h) {
		// 3. Ordenar y filtrar segun conf
		// 4. Escribir en excel
		// 4.1 Headers
		$report = $this->SimpleReport;
		$collapsible = '';
		$repeat_header = false;
		if (isset($report->custom_format)) {
			$odd_color = $report->custom_format['odd_color'];
			$repeat_header = $report->custom_format['repeat_header_each_row'];
			if ($report->custom_format['collapsible']) {
				$collapsible_string = "<a href='#' class='ver-detalle'><img src='//static.thetimebilling.com/images/mas.gif' title='Ver Detalle'/></a>&nbsp;&nbsp;";
			}
		}

		$html = '';
		$html_encabezado = $this->header($columns);

		if(!$this->single_table){
			$html .= '<table class="buscador" width="90%" cellpadding="3">';
			if (!$repeat_header) {
				$html .= $html_encabezado;
			}
		}

		$formatos_con_total = array('number', 'time');
		$totals_rows = array();

		// 4.2 Body
		$html .= '<tbody>';
		$row_i = 0;
		foreach ($result as $row_idx => $row) {
			$tds = '';
			$collapsible = $collapsible_string;
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

				$tds .= $this->td($row, $column, $collapsible);
				$collapsible  = '';
			}

			$color = $row_i++ % 2 ? 'eeeeee' : 'ffffff';

			if (isset($odd_color)) {
					$color = $odd_color;
			}
			if ($repeat_header) {
				$html .= $html_encabezado;
			}

			$html .= "<tr bgcolor=\"#$color\">$tds</tr>";

			//Subreport Logic
			if (isset($report->SubReport)) {
				$values = array();
				foreach ($report->SubReport['Keys'] as $key) {
					$values[$key] = $row[$key];
				}
				$writer = SimpleReport_IOFactory::createWriter($report->SubReport['SimpleReport'], 'Html');
				$subreport_table = $writer->save('', $values);
				if (isset($collapsible_string) && !empty($collapsible_string)) {
					$display = 'none';
				} else {
					$display = '';
				}
				$html .= "<tr bgcolor=\"#$color\"><td colspan=" . count($columns) . "><div style='display:$display' class='subreport'>$subreport_table</div></td></tr>";
			}

		}

		// 4.3 Totales
		$html .= $this->totales($totals_rows, $columns, $totals_name, $h);

		return $html . '</tbody>' . ($this->single_table ? '' : '</table>');
	}

	private function totales($totals_rows, $columns, $name = 'Totales', $level=1){
		$html = '';
		if (!empty($totals_rows)) {
			$i = 0;
			foreach ($totals_rows as $totals_row) {
				$totals = $totals_row['totals'];
				$row = $totals_row['row'];
				if ($i==0) {
					$html .= '<tr class="subtotal" style="border-top:1px solid #000">';
				} else {
					$html .= '<tr class="subtotal">';
				}
				foreach ($columns as $idx => $column) {
					if (isset($totals[$idx])) {
						$row[$column->field] = $totals[$idx];
						$html .= $this->td($row, $column);
					} else {
						$html .=  "<td class='level$level'>$name&nbsp;</td>";
						$name = '';
					}
				}
				$i+=1;
				$html .= '</tr>';
			}
		}

		return $html;
	}

	private function td($row, $column, $extras ='') {
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
				$decimals = 2;
				if (isset($column->extras['decimals'])) {
					$decimals = array_key_exists($column->extras['decimals'], $row) ? $row[$column->extras['decimals']] : $column->extras['decimals'];
				}
				$valor = number_format($valor, $decimals, $this->SimpleReport->regional_format['decimal_separator'], $this->SimpleReport->regional_format['thousands_separator']);
				if (isset($column->extras['symbol'])) {
					$symbol = array_key_exists($column->extras['symbol'], $row) ? $row[$column->extras['symbol']] : $column->extras['symbol'];
					$valor = "$symbol&nbsp;$valor";
				}
				break;
			case 'date':
				$valor = Utiles::sql2fecha($valor, $this->SimpleReport->regional_format['date_format']);
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

		return "<td class=\"$class\" $attrs>$extras$valor</td>";
	}

}

