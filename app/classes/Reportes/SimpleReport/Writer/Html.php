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
	/**
	 * Para el uso de la función ACUMULAR
	 */
	private $acumuladores = array();

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

			$this->variables = array();
			if (!empty($this->SimpleReport->variables)) {
				$html .= '<fieldset><legend>Variables</legend><table>';
				if(isset($this->SimpleReport->variables[0])) {
					$vars = array();
					foreach ($this->SimpleReport->variables as $key => $variable) {
						if(!is_numeric($key)){
							continue;
						}
						$value = $this->parse_field($variable['value'], array(), true);
						if(!isset($vars[$variable['row']])) {
							$vars[$variable['row']] = array();
						}
						$variable['value'] = $value;
						$vars[$variable['row']][$variable['col']] = $variable;
						$this->variables[$variable['name']] = $value;
					}

					$html .= '<tr><td/>';
					foreach (array_keys(reset($vars)) as $col) {
						$html .= "<th>$col</th>";
					}
					$html .= '</tr>';

					foreach ($vars as $row => $cols) {
						$html .= "<tr><th align=\"right\">$row</th>";
						foreach ($cols as $variable) {
							$html .= $this->format_td($variable['value'], 'number', $variable['extras'], '', $this->SimpleReport->variables['data']);
						}
						$html .= '</tr>';
					}
					$html .= '</table></fieldset>';
				} else {
					foreach ($this->SimpleReport->variables as $name => $value) {
						$value = $this->parse_field($value, array(), true);
						$html .= "<tr><td align=\"right\">$name:</td><td>$value</td></tr>";
						$this->variables[$name] = $value;
					}
					$html .= '</table></fieldset>';
				}
			}

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
				$html .= "<script>
					jQuery('.ver-detalle').click(function() {
						sr_sender = jQuery(this);
						sr_detalle = sr_sender.closest('tr').next().find('.subreport');
						sr_detalle.toggle();
						sr_imglink = sr_sender.find('img');
						if (sr_detalle.css('display') == 'none') {
							sr_imglink.attr('src', '//static.thetimebilling.com/images/mas.gif');
						} else {
							sr_imglink.attr('src', '//static.thetimebilling.com/images/menos.gif');
						}
						return false;
					});
					</script>";
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
			$html .= '<table class="buscador" width="100%" cellpadding="3">';
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
					$columns[$column->field]->extras['rowspan'] = $rowspan;
				}

				$inlinegroup_field = $column->extras['inlinegroup_field'];
				if (isset($inlinegroup_field)) {
					if (isset($result[$row_idx - 1]) && $result[$row_idx - 1][$inlinegroup_field] == $row[$inlinegroup_field]) {
						continue;
					}
					$column->extras['rowspan'] = $columns[$inlinegroup_field]->extras['rowspan'];
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
				// Calcular las columnas que no tienen total para el colspan de la glosa
				$i = 0;
				foreach ($columns as $idx => $column) {
					if (isset($totals[$idx])) {
						break;
					}
					$i++;
				}
				$colspan_total = $i;
				foreach ($columns as $idx => $column) {
					if (isset($totals[$idx])) {
						$row[$column->field] = $totals[$idx];
						$html .= $this->td($row, $column);
					} else {
						if ($colspan_total == $i && $i > 0) {
							$html .=  "<td colspan='$colspan_total' class='level$level'>$name&nbsp;</td>";
							$colspan_total--;
						} else if ($colspan_total) {
							$colspan_total--;
						} else {
							$html .= '<td ' . (isset($column->extras['attrs']) ? $column->extras['attrs'] : '') . '/>';
						}
					}
				}
				$i += 1;
				$html .= '</tr>';
			}
		}

		return $html;
	}

	private function parse_field($field, $row, $isnum = false){
		$valor = '';
		if (strpos($field, '=') !== 0) {
			if (isset($row[$field])) {
				$valor = $row[$field];
			} else if ($isnum) {
				$valor = 0;
			} else {
				$valor = '';
			}
		} else {
			if (preg_match('/=(.+)\/(.+)/', $field, $matches)) {
				return $this->parse_field('=' . $matches[1], $row, true) / $this->parse_field('=' . $matches[2], $row, true);
			}

			//es una formula: reemplazar los nombres de campos por celdas
			if (preg_match('/=(\w+)\((.+)\)/', $field, $matches)) {
				switch ($matches[1]) {
					case 'SUM':
						$valor = 0;
						$params = explode(',', $matches[2]);
						foreach ($params as $param) {
							$valor += $this->parse_param($param, $row, true, 0);
						}
						break;
					case 'AVERAGE':
						$valor = 0;
						$params = explode(',', $matches[2]);
						foreach ($params as $param) {
							$valor += $this->parse_param($param, $row, true, 0);
						}
						$valor /= count($params);
						break;
					case 'PRODUCT':
						$valor = 1;
						$params = explode(',', $matches[2]);
						foreach ($params as $param) {
							$valor *= $this->parse_param($param, $row, true, 1);
						}
						break;
					case 'CONCATENATE':
						$params = explode(',', $matches[2]);
						foreach ($params as $param) {
							$valor .= $this->parse_param($param, $row, false, '');
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
			} else { //es una referencia a otra columna o variable
				$valor = $this->parse_param(trim($field, '='), $row, $isnum, $isnum ? 0 : '');
			}
		}
		return $valor;
	}

	private function td(&$row, $column, $extras ='') {
		$valor = $this->parse_field($column->field, $row, $column->format == 'number');
		$row[$column->name] = $valor;
		return $this->format_td($valor, $column->format, $column->extras, $extras, $row);
	}

	private function format_td($valor, $format, $extras, $extra_text, $row) {
		switch ($format) {
			case 'text':
				if (strpos($valor, ";")) {
					$valor = str_replace(";", "<br />", $valor);
				}
				break;
			case 'number':
				$decimals = 2;
				if (isset($extras['decimals'])) {
					$decimals = array_key_exists($extras['decimals'], $row) ? $row[$extras['decimals']] : $extras['decimals'];
				}
				$valor = number_format($valor, $decimals, $this->SimpleReport->regional_format['decimal_separator'], $this->SimpleReport->regional_format['thousands_separator']);
				if (isset($extras['symbol'])) {
					$symbol = array_key_exists($extras['symbol'], $row) ? $row[$extras['symbol']] : $extras['symbol'];
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

		$attrs = isset($extras['attrs']) ? $extras['attrs'] : '';
		if (isset($extras['rowspan'])) {
			$attrs .= ' rowspan="' . $extras['rowspan'] . '"';
		}

		$class = 'buscador';
		if (isset($extras['class'])) {
			$class .= ' ' . $extras['class'];
		}

		return "<td class=\"$class\" $attrs>$extra_text$valor</td>";
	}

	private function parse_param($param, $row, $numeric = true, $default = null){
		$param = trim($param);
		if (strpos($param, '"') === 0) {
			return trim($param, '"');
		}

		if (preg_match_all('/%(\w+)%/', $param, $matches_fields, PREG_SET_ORDER)) {
			foreach ($matches_fields as $match_field) {
				$value = $field = $match_field[1];
				if (isset($row[$field])) {
					$value = $row[$field];
				}
				$param = str_replace($match_field[0], $value, $param);
			}
		}

		if (preg_match_all('/\$(.+?)\$/', $param, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$value = $var = $match[1];
				$value = isset($this->variables[$var]) ? $this->variables[$var] : $default;
				$param = str_replace($match[0], $value, $param);
			}
		}

		if($numeric) {
			$param = str_replace(',', '.', $param);
			if(!is_numeric($param)) $param = $default;
		} else if (!$param) {
			$param = $default;
		}
		return $param;
	}
}

