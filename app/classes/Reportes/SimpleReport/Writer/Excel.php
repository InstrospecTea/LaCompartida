<?php

require_once SIMPLEREPORT_ROOT . '../Excel/PHPExcel.php';

/**
 * Description of ReporteExcel
 * @author matias.orellana
 */
class SimpleReport_Writer_Excel implements SimpleReport_Writer_IWriter {

	/**
	 * @var PHPExcel
	 */
	var $xls;

	/**
	 * @var PHPExcel_Worksheet
	 */
	private $sheet;

	/**
	 * @var SimpleReport
	 */
	var $SimpleReport;

	/**
	 * fila actual del excel
	 * @var integer
	 */
	private $current_row;
	private $subtotals = array();
	private $sum_subtotals = false;
	private $autofilter = true;
	private $col_letters = array();
	public $formato_fecha = "%d/%m/%Y";

	public function __construct(SimpleReport $simpleReport) {
		ini_set('memory_limit', '1024M');
		$this->SimpleReport = $simpleReport;
		$this->xls = new PHPExcel();
		$this->sheet = $this->xls->getActiveSheet();
	}

//	public static function DrawDownloadButton(Sesion $Sesion, $tipo) {
//		$config = ReporteExcel_Configuration::LoadFromJson(self::GetConfiguration($Sesion, $tipo));
//
//		$columns = array();
//
//		foreach ($config->columns as $conf) {
//			$checked = $conf->visible ? ' checked="checked"' : '';
//			$columns[] = '<label><input type="checkbox"'
//				. $checked
//				. 'name="' . $conf->field . '" /> '
//				. utf8_decode($conf->title)
//				. '</label>';
//		}
//
//		$html = '<div class="btn-group">
//			<button class="btn btn-success">
//				<i class="icon-download-alt icon-white"></i>
//				Descargar Excel
//			</button>
//			<button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
//				<span class="caret"></span>
//			</button>
//			<ul class="dropdown-menu">
//				<li>' . implode('</li><li>', $columns) . '</li>
//			</ul>
//		</div>';
//
//		$javascript = '<script type="text/javascript">jQuery(document).load(function() { jQuery("ul.dropdown-menu").sortable({axis: "y"}); });</script>';
//
//		return $html . "\n" . $javascript;
//	}

	public function save($filename = null) {
		// 1. Construir base Excel
		$this->sheet->setTitle($this->SimpleReport->Config->title);

		// 1.1 Formatear todos los arreglos
		// 2. Correr query
		$result = $this->SimpleReport->RunReport();
		// 3. Ordenar y filtrar segun conf
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

		$this->current_row = 1;

		// 4. Escribir en excel
		$this->groups($result, $columns, $groups);

		//fila con totales cuando hay mas de un subtotal
		if ($this->sum_subtotals) {
			foreach ($this->subtotals as $group => $cols) {
				$this->current_row++;
				foreach ($cols as $col => $subtotal) {
					$column = $subtotal['column'];
					$row = $subtotal['row'];
					$row[$column->field] = '=' . implode('+', $subtotal['cells']);

					$this->cell($row, $column, $subtotal['col_i']);

					// Totales en negrita
					$format = SimpleReport_Writer_Excel_Format::$formats[$column->format];
					$format['font'] = array('bold' => true);
					if ($column->format == 'number' && isset($column->extras['symbol']) && isset($format['numberformat'])) {
						unset($format['numberformat']);
					}

					$this->sheet
						->getStyle("{$col}{$this->current_row}")
						->applyFromArray($format);
				}
			}
		}

		// 5. Descargar
		if (empty($filename)) {
			$filename = $this->SimpleReport->Config->title;
		}

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($this->xls, 'Excel2007');
		$objWriter->save('php://output');
		exit();
	}

	private function RunQuery() {
		return $this->statement->fetchAll();
	}

	private function groups($result, $columns, $groups, $col0 = 0) {
		if (empty($groups)) {
			return $this->table($result, $columns, $col0);
		} else {
			$col_i = key($groups) - 1;
			$col_title = PHPExcel_Cell::stringFromColumnIndex($col_i);
			$column = array_shift($groups);

			$grouped_rows = array();
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

			foreach ($grouped_rows as $group => $rows) {
				//poner el titulo del grupo
				$this->sheet->setCellValue("{$col_title}{$this->current_row}", $group);
				$format = SimpleReport_Writer_Excel_Format::$formats['text'];
				$format['font'] = array('bold' => true);
				$this->sheet
					->getStyle("{$col_title}{$this->current_row}")
					->applyFromArray($format);
				$this->current_row++;

				//generar la(s) subtabla(s)
				$this->groups($rows, $columns, $groups, $col_i + 1);
			}
		}
	}

	private function table($result, $columns, $col0 = 0) {
		$col0_letter = PHPExcel_Cell::stringFromColumnIndex($col0);
		// 4.1 Headers
		for ($col_i = 0; $col_i < count($columns); $col_i++) {
			$column = array_pop(array_slice($columns, $col_i, 1));

			$col_letter = PHPExcel_Cell::stringFromColumnIndex($col_i + $col0);

			$this->sheet->setCellValue("{$col_letter}{$this->current_row}", $column->title);
		}

		// Estilo de titulos
		$this->sheet->getStyle("$col0_letter{$this->current_row}:{$col_letter}{$this->current_row}")
			->applyFromArray(SimpleReport_Writer_Excel_Format::$formats['title']);

		// 4.2 Body
		$formatos_con_total = array('number', 'time');
		$first_row = ++$this->current_row;
		$totals_rows = array();
		foreach ($result as $row_idx => $row) {
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
					$cell = PHPExcel_Cell::stringFromColumnIndex($col_i) . $this->current_row;
					if (isset($totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row - 1])) {
						list($last_cell) = explode(':', $totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row - 1]);
						$totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row] = "$last_cell:$cell";
						unset($totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row - 1]);
					} else {
						$totals_rows[$grupo_subtotal]['totals'][$idx][$this->current_row] = $cell;
					}
				}

				if (isset($column->extras['groupinline'])) {
					$column->extras['rowspan'] = 1;
					if (!isset($result[$row_idx - 1]) || $result[$row_idx - 1][$column->field] != $row[$column->field]) {
						$rowspan = 1;
						while (isset($result[$row_idx + $rowspan]) && $result[$row_idx + $rowspan][$column->field] == $row[$column->field]) {
							$rowspan++;
						}
						$column->extras['rowspan'] = $rowspan;
					}
				}

				$this->cell($row, $column, $col_i);
				$col_i++;
			}
			$this->current_row++;
		}

		// 4.3 Totales
		$last_row = $this->current_row - 1;
		foreach ($totals_rows as $group => $totals_row) {
			$totals = $totals_row['totals'];
			$row = $totals_row['row'];

			$col_i = $col0;
			foreach ($columns as $idx => $column) {
				if (isset($totals[$idx])) {
					$col_letter = PHPExcel_Cell::stringFromColumnIndex($col_i);

					$sum_cells = count($totals_rows) > 1 ? implode(',', $totals[$idx]) : "{$col_letter}{$first_row}:{$col_letter}{$last_row}";
					$row[$column->field] = "=SUBTOTAL(9,$sum_cells)";
					$this->cell($row, $column, $col_i);

					// Totales en negrita
					$format = SimpleReport_Writer_Excel_Format::$formats[$column->format];
					$format['font'] = array('bold' => true);
					if ($column->format == 'number' && isset($column->extras['symbol']) && isset($format['numberformat'])) {
						unset($format['numberformat']);
					}

					$this->sheet
						->getStyle("{$col_letter}{$this->current_row}")
						->applyFromArray($format);

					if (!isset($this->subtotals[$group][$col_letter])) {
						if (!isset($this->subtotals[$group])) {
							$this->subtotals[$group] = array();
						}
						$this->subtotals[$group][$col_letter] = array(
							'col_i' => $col_i,
							'column' => $column,
							'row' => $row,
							'cells' => array()
						);
					} else {
						$this->sum_subtotals = true;
					}
					$this->subtotals[$group][$col_letter]['cells'][] = $col_letter . $this->current_row;
				}
				$col_i++;
			}
			$this->current_row++;
		}

		// 4.4 Estilos de columnas
		for ($col_i = 0; $col_i < count($columns); $col_i++) {
			$column = array_pop(array_slice($columns, $col_i, 1));

			$col_letter = PHPExcel_Cell::stringFromColumnIndex($col_i + $col0);

			$format = SimpleReport_Writer_Excel_Format::$formats[$column->format];
			if ($column->format == 'number' && isset($column->extras['symbol']) && isset($format['numberformat'])) {
				unset($format['numberformat']);
			}

			$this->sheet
				->getStyle("{$col_letter}{$first_row}:{$col_letter}{$last_row}")
				->applyFromArray($format);

			$widths = array('number' => 20, 'date' => 15, 'text' => 25); // 1 == 9px
			$width = isset($widths[$column->format]) ? $widths[$column->format] : 23;
			if (isset($column->extras['width'])) {
				$width = $column->extras['width'];
			}

			$this->sheet->getColumnDimension($col_letter)->setWidth($width);
			//->setAutoSize(); // Esto debería ser (true) pero Excel queda gigante, no se por que
		}

		// 4.5 Autofilter
		$first_row--;
		$last_col_letter = $col_letter;
		if ($this->autofilter) {
			$this->sheet->setAutoFilter("$col0_letter{$first_row}:{$last_col_letter}{$last_row}");
			$this->sheet->freezePane('A' . ($first_row + 1));
		}
	}

	private function cell($row, $column, $col_i) {
		if ($column->format == 'number' && isset($column->extras['symbol'])) {
			$symbol = array_key_exists($column->extras['symbol'], $row) ? $row[$column->extras['symbol']] : $column->extras['symbol'];
			if (ord($symbol) == 128) {
				$symbol = 'EUR';
			}

			$this->sheet
				->getStyleByColumnAndRow($col_i, $this->current_row)
				->getNumberFormat()
				->setFormatCode(utf8_encode("\"$symbol\" #,##0.00"));
		}

		if (!isset($this->col_letters[$column->field])) {
			$this->col_letters[$column->field] = PHPExcel_Cell::stringFromColumnIndex($col_i);
		}

		$value = '';
		if (strpos($column->field, '=') !== 0) {
			$value = $row[$column->field];
			if ($column->format == 'text') {
				//reemplazar los ; por \n (si se manda directamente el \n se pierde antes de llegar aca)
				$value = utf8_encode($value);

				if (strpos($value, ";")) {
					$value = str_replace(";", "\n", $value);

					$this->sheet
						->getStyleByColumnAndRow($col_i, $this->current_row)
						->getAlignment()
						->setWrapText(true);
				}
			} else if ($column->format == 'date') {
				//las fechas llegan en formato SQL, pasarlas a formato excel
				$value = Utiles::sql2fecha($value, $this->formato_fecha);
			}
		} else {
			//es una formula: reemplazar los nombres de campos por celdas
			$value = $column->field;
			if (preg_match_all('/%(\w+)%/', $value, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$param = $match[1];
					if (isset($this->col_letters[$match[1]])) {
						$param = $this->col_letters[$match[1]] . $this->current_row;
					} else if (isset($row[$param])) {
						$param = '"' . $row[$param] . '"';
					} else if (strpos($param, '"') !== 0) {
						$param = '"' . $param . '"';
					}
					$value = str_replace($match[0], $param, $value);
				}
			}
		}

		$this->sheet->setCellValueByColumnAndRow($col_i, $this->current_row, $value);

		if (isset($column->extras['rowspan']) && $column->extras['rowspan'] > 1) {
			$this->sheet->mergeCellsByColumnAndRow($col_i, $this->current_row, $col_i, $this->current_row + $column->extras['rowspan'] - 1);
		}
	}

}

