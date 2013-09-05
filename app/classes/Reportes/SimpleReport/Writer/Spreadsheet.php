<?php

require_once 'Spreadsheet/Excel/Writer.php';

class SimpleReport_Writer_Spreadsheet implements SimpleReport_Writer_IWriter {

	/**
	 * @var SimpleReport
	 */
	var $SimpleReport;

	/**
	 * fila actual del excel
	 * @var integer
	 */
	private $current_row = 0;
	private $autofilter = true;
	private $col_letters = array();

	/**
	 * una tabla (con un encabezado) para todos los grupos
	 */
	private $single_table = false;

	/**
	 * @var Spreadsheet_Excel_Writer
	 */
	private $xls = null;

	/**
	 * @var Spreadsheet_Excel_Writer_Worksheet
	 */
	private $sheet;
	private $formats = array();

	/**
	 * Para los acumuladores de la función ACUMULAR
	 */
	private $acumuladores = array();

	public function __construct(SimpleReport $simpleReport) {
		ini_set('memory_limit', '1024M');
		$this->SimpleReport = $simpleReport;
	}

	public function save($filename = null, $group_values = null, $parent_writer = null, $indent_level = 0) {
		// 1. Construir base Excel
		// Enviar headers a la pagina
		if (empty($filename)) {
			$filename = $this->SimpleReport->Config->title;
		}
		if (!$parent_writer) {
			$this->xls = new Spreadsheet_Excel_Writer();
			$this->xls->send("$filename.xls");
			// Crear worksheet
			$this->sheet = & $this->xls->addWorksheet($this->SimpleReport->Config->title);
			$this->sheet->setLandscape();
			// Definir colores y formatos
			//$this->xls->setCustomColor(35, 155, 187, 89); //verde lemontech
			$this->xls->setCustomColor(35, 220, 255, 220); //encabezados
			$this->xls->setCustomColor(36, 255, 255, 220); //?

			foreach (SimpleReport_Writer_Spreadsheet_Format::$formats as $name => $format) {
				$this->formats[$name] = & $this->xls->addFormat($format);
			}
		} else {
			$this->xls = & $parent_writer->xls;
			$this->sheet = & $parent_writer->sheet;
			$this->current_row = & $parent_writer->current_row;
			$this->formats = & $parent_writer->formats;
			$this->autofilter = false;

			$this->current_row++;
		}

		if (!empty($this->SimpleReport->filters)) {
			foreach ($this->SimpleReport->filters as $name => $value) {
				if (!empty($value)) {
					$this->sheet->writeString($this->current_row, 0, $name, $this->formats['filtros']);
					$this->sheet->writeString($this->current_row, 1, $value, $this->formats['valoresfiltros']);
					$this->current_row++;
				}
			}
			$this->current_row++;
		}

		$this->variableCells = array();
		if (!empty($this->SimpleReport->variables)) {
			if(isset($this->SimpleReport->variables[0])) {
				$header_row = $this->current_row++;
				$current_col = 1;
				$rows = array();
				$cols = array();
				foreach ($this->SimpleReport->variables as $key => $variable) {
					if(!is_numeric($key)){
						continue;
					}
					if(!isset($rows[$variable['row']])){
						$this->sheet->write($this->current_row, 0, $variable['row'], $this->formats['filtros']);
						$rows[$variable['row']] = $this->current_row++;
					}
					if(!isset($cols[$variable['col']])){
						$this->sheet->write($header_row, $current_col, $variable['col'], $this->formats['filtros']);
						$cols[$variable['col']] = $current_col++;
					}

					$this->write_cell($this->parse_param($variable['value'], null),
						$rows[$variable['row']], $cols[$variable['col']], 'number',
						isset($variable['extras']) ? $variable['extras'] : array(),
						$this->SimpleReport->variables['data'], strpos($variable['value'], '=') === 0);
					$this->variableCells[$variable['name']] = $this->xls->rowcolToCell($rows[$variable['row']], $cols[$variable['col']]);
				}
			} else {
				foreach ($this->SimpleReport->variables as $name => $value) {
					$this->sheet->write($this->current_row, 0, $name, $this->formats['filtros']);
					$this->sheet->write($this->current_row, 1, $this->parse_param($value, null), $this->formats['valoresfiltros']);
					$this->current_row++;
					$this->variableCells[$name] = '$B$' . $this->current_row;
				}
			}
			$this->current_row++;
		}



		// 1.1 Formatear todos los arreglos
		// 2. Correr query
		$result = $this->SimpleReport->RunReport($group_values);
		// 3. Ordenar y filtrar segun conf
		$columns = $this->SimpleReport->Config->VisibleColumns();

		//agrupadores de resultados
		$groups = array();
		$aux_columns = array();
		foreach ($columns as $idx => $column) {
			if ($column->group) {
				//el espacio es para q se mantenga como string y no se reseteen los indices al shiftear
				$groups[$column->group + $indent_level . ' '] = $column->field;
				unset($columns[$idx]);
			} else if (isset($column->extras['subtotal']) && $column->extras['subtotal'] && !isset($columns[$column->extras['subtotal']])) {
				$col_subtotal = new SimpleReport_Configuration_Column();
				$col_subtotal->Field($column->extras['subtotal'])
								->Title($column->extras['subtotal'])
								->Format('text')
								->Extras(array('width' => 0));
				$aux_columns[$column->extras['subtotal']] = $col_subtotal;
			}
		}
		if (!empty($aux_columns)) {
			//se insertan las columnas ocultas antes del final, para asegurar que se incluyan si se intenta seleccionar todo
			$columns = array_slice($columns, 0, -1) + $aux_columns + array_slice($columns, -1);
		}
		ksort($groups);

		// 4. Escribir en excel
		$this->groups($result, $columns, $groups, $totals, 'Total', $indent_level ? $indent_level + 1 : 0);

		// 5. Descargar
		if (!$parent_writer) {
			$this->xls->close();
			exit;
		}
	}

	private function groups($result, $columns, $groups, &$totals, $total_name, $col0 = 0) {
		if (empty($groups)) {
			return $this->table($result, $columns, $totals, $total_name, $col0);
		} else {
			if (!$col0) {
				$col0 = 0;
			}
			if (isset($this->SimpleReport->custom_format['single_table']) && !$this->single_table) {
				$this->single_table = true;
				$this->header($columns, $col0);
			}
			$col_i = key($groups) - 1;
			$column = array_shift($groups);

			$grouped_rows = array();
			if (!$result) {
				$result = array();
			}
			foreach ($result as $row) {
				$group = $row[$column];
				if (!isset($grouped_rows[$group])) {
					$grouped_rows[$group] = array();
				}
				$grouped_rows[$group][] = $row;
			}

			if (count($grouped_rows) > 1) {
				$this->autofilter = false;
			}

			$totals = array();
			foreach ($grouped_rows as $group => $rows) {
				//poner el titulo del grupo
				$s = str_pad('', $col_i * 3);
				$this->sheet->write($this->current_row, 0, $s . $group, $this->formats['encabezado']);
				$this->current_row++;

				//generar la(s) subtabla(s)
				$this->groups($rows, $columns, $groups, $subtotals, "$s Subtotal $group", $col0);

				//acumular el total de este grupo
				if (!empty($subtotals)) {
					$r = $this->current_row - count($subtotals);
					foreach ($subtotals as $g => $totals_row) {
						if (!isset($totals[$g])) {
							$totals[$g] = array('row' => $totals_row['row'], 'totals' => array());
						}
						$c = $col0;
						foreach ($columns as $col_idx => $column) {
							if (isset($totals_row['totals'][$col_idx])) {
								$totals[$g]['totals'][$col_idx][] = $this->xls->rowcolToCell($r, $c);
							}
							$c++;
						}
						$r++;
					}
				}
			}

			if (!empty($totals)) {
				$this->totales($totals, $columns, $total_name, $col0);
			}
		}
	}

	private function header($columns, $col0 = 0) {
		$col_i = $col0;
		foreach ($columns as $column) {
			$this->sheet->write($this->current_row, $col_i++, utf8_decode($column->title), $this->formats['title']);
		}
		$this->current_row++;
	}

	private function table($result, $columns, &$totals_rows, $totals_name, $col0 = 0) {
		$report = $this->SimpleReport;

		// 4.1 Headers
		$report = $this->SimpleReport;
		$repeat_header = false;
		if (isset($report->custom_format)) {
			$repeat_header = $report->custom_format['repeat_header_each_row'];
		}

		if (!$this->single_table && !$repeat_header) {
			$this->header($columns, $col0);
		}
		$this->first_row = $first_row = $this->current_row;
		$this->last_row = $first_row + count($result);

		// 4.2 Body
		$formatos_con_total = array('number', 'time');
		$totals_rows = array();
		if (empty($result)) {
			$result = array();
		}
		foreach ($result as $row_idx => $row) {
			if ($repeat_header) {
				$this->header($columns, $col0);
			}

			$col_i = $col0;
			foreach ($columns as $idx => $column) {
				if (!isset($this->col_letters[$column->name])) {
					$this->col_letters[$column->name] = $col_i;
				}
				$col_i++;
			}

			$col_i = $col0;
			foreach ($columns as $idx => $column) {
				if (in_array($column->format, $formatos_con_total) &&
								(!isset($column->extras['subtotal']) || $column->extras['subtotal'])) {
					$grupo_subtotal = isset($column->extras['subtotal']) && is_string($column->extras['subtotal']) ? $row[$column->extras['subtotal']] : '';
					if (!isset($totals_rows[$grupo_subtotal])) {
						$totals_rows[$grupo_subtotal] = array('row' => $row, 'totals' => array());
					}
					if (!isset($totals_rows[$grupo_subtotal]['totals'][$idx])) {
						$totals_rows[$grupo_subtotal]['totals'][$idx] = array();
					}

					//si el anterior era del mismo grupo, me agrego al rango
					$cell = $this->xls->rowcolToCell($this->current_row, $col_i);
					if (isset($totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row - 1])) {
						list($last_cell) = explode(':', $totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row - 1]);
						$totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row] = "$last_cell:$cell";
						unset($totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row - 1]);
					} else {
						$totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row] = "$cell:$cell";
					}
				}

				if (isset($column->extras['groupinline'])) {
					$column->extras['rowspan'] = 1;
					if (!isset($result[$row_idx + 1]) || $result[$row_idx + 1][$column->field] != $row[$column->field]) {
						$rowspan = 1;
						while (isset($result[$row_idx - $rowspan]) && $result[$row_idx - $rowspan][$column->field] == $row[$column->field]) {
							$rowspan++;
						}
						$column->extras['rowspan'] = $rowspan;
						$columns[$idx]->extras['rowspan'] = $rowspan;
					}
				}

				$inlinegroup_field = $column->extras['inlinegroup_field'];
				if (isset($inlinegroup_field) && $columns[$inlinegroup_field]->extras['rowspan'] > 0) {
					$column->extras['rowspan'] = $columns[$inlinegroup_field]->extras['rowspan'];
				}

				$this->cell($row, $column, $col_i);
				$col_i++;
			}

			$this->current_row++;

			if ($report->SubReport) {
				$values = array();
				foreach ($report->SubReport['Keys'] as $key) {
					$values[$key] = $row[$key];
				}
				$subresult = $report->SubReport['SimpleReport']->RunReport($values);
				if (!empty($subresult)) {
					$this->SimpleReport->results;
					$writer = SimpleReport_IOFactory::createWriter($report->SubReport['SimpleReport'], 'Spreadsheet');
					$writer->save('', $values, $this, $report->SubReport['Level']);
				}
				$this->current_row++;
			}
		}

		// 4.3 Totales
		$last_row = $this->current_row - 1;
		$this->current_row++; //espacio para no pescar los totales en los filtros
		$this->totales($totals_rows, $columns, $totals_name, $col0, false, $first_row, $last_row);

		// 4.4 Estilos de columnas
		for ($col_i = 0; $col_i < count($columns); $col_i++) {
			$column = array_pop(array_slice($columns, $col_i, 1));
			$widths = array('number' => 20, 'date' => 15, 'text' => 25); // 1 == 9px
			$width = isset($widths[$column->format]) ? $widths[$column->format] : 23;
			if (isset($column->extras['width'])) {
				$width = $column->extras['width'];
			}

			$this->sheet->setColumn($col_i + $col0, $col_i + $col0, $width, 0, empty($width) ? 1 : 0);
		}

		// 4.5 Autofilter
		if ($this->autofilter) {
			//este coso no soporta autofilter :(
			//$this->sheet->setAutoFilter("$col0_letter{$first_row}:{$last_col_letter}{$last_row}");

			$this->sheet->freezePanes(array($first_row));
		}
	}

	private function totales($totals_rows, $columns, $name, $col0 = 0, $subtotals = true, $first_row = 0, $last_row = 0) {
		foreach ($totals_rows as $group => $totals_row) {
			$totals = $totals_row['totals'];
			$row = $totals_row['row'];

			if ($col0) {
				$this->sheet->write($this->current_row, $col0 - 1, $name, $this->formats['text']);
			}
			$col_i = $col0;
			foreach ($columns as $idx => $column) {
				if (isset($totals[$idx])) {
					if ($subtotals) {
						$row[$column->field] = '=' . implode('+', $totals[$idx]);
						$this->cell($row, $column, $col_i, 'total_' . $column->format);
					} else if ($first_row && $last_row && isset($column->extras['subtotal'])) {
						$sum_cells = $this->xls->rowcolToCell($first_row, $col_i) . ':' . $this->xls->rowcolToCell($last_row, $col_i);
						$col_cond = $this->col_letters[$column->extras['subtotal']];
						$cond_cells = $this->xls->rowcolToCell($first_row, $col_cond) . ':' . $this->xls->rowcolToCell($last_row, $col_cond);
						$row[$column->field] = "=SUMIF($cond_cells,\"$group\",$sum_cells)";
						$this->cell($row, $column, $col_i, 'total_' . $column->format);
					} else {
						$sum_cells = count($totals_rows) > 1 || !$first_row ? implode(',', $totals[$idx]) :
										($this->xls->rowcolToCell($first_row, $col_i) . ':' . $this->xls->rowcolToCell($last_row, $col_i));
						//el implode hace q se muestre el error "a value used in the formula is of the wrong datatype",
						//aunque editando manualmente la formula (sin cambiar nada) se arregla
						$row[$column->field] = "=SUBTOTAL(9,$sum_cells)";
						$this->cell($row, $column, $col_i, 'total_' . $column->format);
					}
				}
				$col_i++;
			}
			$this->current_row++;
		}
	}

	private function write_cell($value, $row_number, $col_i, $format, $extras, $row, $is_formula) {
		if (in_array($format, array('number', 'total_number')) && (isset($extras['symbol']) || isset($extras['decimals']))) {
			$symbol = '';
			if (isset($extras['symbol'])) {
				$symbol = array_key_exists($extras['symbol'], $row) ? $row[$extras['symbol']] : $extras['symbol'];
				if (ord($symbol) == 128) {
					$symbol = 'EUR';
				}
			}
			$decimals = 2;
			if (isset($extras['decimals'])) {
				$decimals = array_key_exists($extras['decimals'], $row) ? $row[$extras['decimals']] : $extras['decimals'];
			}

			$f = $format . '_' . $symbol . '_' . $decimals;
			if (!isset($this->formats[$f])) {
				$decimals = $decimals ? '.' . str_pad('', $decimals, '0') : '';
				$symbol = $symbol ? "[$$symbol] " : '';
				$this->formats[$f] = & $this->xls->addFormat(
												SimpleReport_Writer_Spreadsheet_Format::$formats[$format] +
												array('NumFormat' => "$symbol#,###,0$decimals")
				);
			}
			$format = $f;
		}

		if ($format == 'text') {
			//reemplazar los ; por \n (si se manda directamente el \n se pierde antes de llegar aca)
			if (strpos($value, ";")) {
				$value = str_replace(";", "\n", $value);
				$format = 'text_wrap';
			}
		} else if ($format == 'date') {
			//las fechas llegan en formato SQL, pasarlas a formato excel
			$value = $value == '0000-00-00 00:00:00' ? '' : Utiles::sql2fecha($value, $this->SimpleReport->regional_format['date_format']);
		} else if ($format == 'time') {
			$value /= 24;
		}
		if ((strpos($format, 'number') !== false || $format == 'time') && is_numeric($value)) {
			$value = str_replace(',', '.', $value);
		}

		if ($is_formula) {
			$this->sheet->writeFormula($row_number, $col_i, $value, $this->formats[$format]);
		} else {
			$function = $format == 'text' ? 'writeString' : 'write';
			$this->sheet->$function($row_number, $col_i, $value, $this->formats[$format]);

			if (isset($extras['rowspan']) && $extras['rowspan'] > 1) {
				$this->sheet->mergeCells($row_number - $extras['rowspan'] + 1, $col_i, $row_number, $col_i);
			}
		}
	}

	private function cell($row, $column, $col_i, $format = null) {

		$value = '';
		$is_formula = false;
		if (strpos($column->field, '=') !== 0 || isset($row[$column->field])) {
			// es un valor normal
			$value = isset($row[$column->field]) ? $row[$column->field] : '';
		} else {
			// es una formula: reemplazar los nombres de campos por celdas
			$is_formula = true;
			if (preg_match('/=(\w+)\((.+)\)/', $column->field, $matches)) {
				switch ($matches[1]) {
					case 'ACUMULAR':
						// Si es acumular lo considero como un valor mas
						$is_formula = false;
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
						$value = $this->acumuladores[$original_param];
						break;

					default:
						$value = $this->parse_param($column->field, $row);
						break;
				}
			} else {
				$value = $this->parse_param($column->field, $row);
			}
		}

		if (empty($format)) {
			$format = $column->format;
		}
		$this->write_cell($value, $this->current_row, $col_i, $format, $column->extras, $row, $is_formula);
	}

	private function parse_param($value, $row){
		// Usar las fórmulas de Excel
		if (preg_match_all('/\$(.+?)\$/', $value, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$var = $match[1];

				if (preg_match_all('/%(\w+)%/', $var, $matches_params, PREG_SET_ORDER)) {
					foreach ($matches_params as $match_param) {
						$param = $match_param[1];
						if (isset($row[$param])) {
							$param = $row[$param];
						}
						$var = str_replace($match_param[0], $param, $var);
					}
				}

				$var = isset($this->variableCells[$var]) ? $this->variableCells[$var] : 0;
				$value = str_replace($match[0], $var, $value);
			}
		}

		if (preg_match_all('/%(\w+)%/', $value, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$param = $match[1];
				if (isset($this->col_letters[$match[1]])) {
					$param = $this->xls->rowcolToCell($this->current_row, $this->col_letters[$match[1]]);
				} else if (isset($row[$param])) {
					$param = '"' . $row[$param] . '"';
				} else if (strpos($param, '"') !== 0) {
					$param = '"' . $param . '"';
				}
				$value = str_replace($match[0], $param, $value);
			}
		}

		if (preg_match_all('/:(\w+):/', $value, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$param = $match[1];
				if (isset($this->col_letters[$match[1]])) {
					$first = $this->xls->rowcolToCell($this->first_row, $this->col_letters[$match[1]]);
					$last = $this->xls->rowcolToCell($this->last_row, $this->col_letters[$match[1]]);
					$param = "$first:$last";
				}
				$value = str_replace($match[0], $param, $value);
			}
		}

		return $value;
	}

}

