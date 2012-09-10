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
	private $current_row;
	private $subtotals = array();
	private $sum_subtotals = false;
	private $autofilter = true;
	private $col_letters = array();
	public $formato_fecha = "%d/%m/%Y";

	/**
	 * @var Spreadsheet_Excel_Writer
	 */
	private $xls = null;

	/**
	 * @var Spreadsheet_Excel_Writer_Worksheet
	 */
	private $sheet;
	private $formats = array();

	public function __construct(SimpleReport $simpleReport) {
		ini_set('memory_limit', '1024M');
		$this->SimpleReport = $simpleReport;
		$this->xls = new Spreadsheet_Excel_Writer();
	}

	public function save($filename = null) {
		// 1. Construir base Excel
		// Enviar headers a la pagina
		if (empty($filename)) {
			$filename = $this->SimpleReport->Config->title;
		}
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

		$this->current_row = 0;

		// 4. Escribir en excel
		$this->groups($result, $columns, $groups);

		//fila con totales cuando hay mas de un subtotal
		if ($this->sum_subtotals) {
			foreach ($this->subtotals as $cols) {
				$this->current_row++;
				foreach ($cols as $subtotal) {
					$column = $subtotal['column'];
					$row = $subtotal['row'];
					$row[$column->field] = '=' . implode('+', $subtotal['cells']);

					$this->cell($row, $column, $subtotal['col_i'], 'total');
				}
			}
		}

		// 5. Descargar
		$this->xls->close();
		exit;
	}

	private function groups($result, $columns, $groups, $col0 = 0) {
		if (empty($groups)) {
			return $this->table($result, $columns, $col0);
		} else {
			$col_i = key($groups) - 1;
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
				$this->sheet->write($this->current_row, $col_i, $group, $this->formats['encabezado']);
				$this->current_row++;

				//generar la(s) subtabla(s)
				$this->groups($rows, $columns, $groups, $col_i + 1);
			}
		}
	}

	private function table($result, $columns, $col0 = 0) {
		// 4.1 Headers
		$col_i = $col0;
		foreach ($columns as $idx => $column) {
			$this->sheet->write($this->current_row, $col_i++, utf8_decode($column->title), $this->formats['title']);
		}

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
					$cell = $this->xls->rowcolToCell($this->current_row, $col_i);
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
					if (!isset($result[$row_idx + 1]) || $result[$row_idx + 1][$column->field] != $row[$column->field]) {
						$rowspan = 1;
						while (isset($result[$row_idx - $rowspan]) && $result[$row_idx - $rowspan][$column->field] == $row[$column->field]) {
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
					$sum_cells = count($totals_rows) > 1 ? implode(',', $totals[$idx]) :
						($this->xls->rowcolToCell($first_row, $col_i) . ':' . $this->xls->rowcolToCell($last_row, $col_i));
					//el implode hace q se muestre el error "a value used in the formula is of the wrong datatype",
					//aunque editando manualmente la formula (sin cambiar nada) se arregla
					$row[$column->field] = "=SUBTOTAL(9,$sum_cells)";
					$this->cell($row, $column, $col_i, 'total');

					if (!isset($this->subtotals[$group][$col_i])) {
						if (!isset($this->subtotals[$group])) {
							$this->subtotals[$group] = array();
						}
						$this->subtotals[$group][$col_i] = array(
							'col_i' => $col_i,
							'column' => $column,
							'row' => $row,
							'cells' => array()
						);
					} else {
						$this->sum_subtotals = true;
					}
					$this->subtotals[$group][$col_i]['cells'][] = $this->xls->rowcolToCell($this->current_row, $col_i);
				}
				$col_i++;
			}
			$this->current_row++;
		}

		// 4.4 Estilos de columnas
		for ($col_i = 0; $col_i < count($columns); $col_i++) {
			$column = array_pop(array_slice($columns, $col_i, 1));
			$widths = array('number' => 20, 'date' => 15, 'text' => 25); // 1 == 9px
			$width = isset($widths[$column->format]) ? $widths[$column->format] : 23;
			if (isset($column->extras['width'])) {
				$width = $column->extras['width'];
			}

			$this->sheet->setColumn($col_i + $col0, $col_i + $col0, $width);
		}

		// 4.5 Autofilter
		if ($this->autofilter) {
			//este coso no soporta autofilter :(
			//$this->sheet->setAutoFilter("$col0_letter{$first_row}:{$last_col_letter}{$last_row}");

			$this->sheet->freezePanes(array($first_row));
		}
	}

	private function cell($row, $column, $col_i, $format = null) {
		if (empty($format)) {
			$format = $column->format;
		}
		if (in_array($format, array('number', 'total')) && isset($column->extras['symbol'])) {
			$symbol = array_key_exists($column->extras['symbol'], $row) ? $row[$column->extras['symbol']] : $column->extras['symbol'];
			if (ord($symbol) == 128) {
				$symbol = 'EUR';
			}

			if (!isset($this->formats[$format . '_' . $symbol])) {
				$this->formats[$format . '_' . $symbol] = & $this->xls->addFormat(
						SimpleReport_Writer_Spreadsheet_Format::$formats[$format] +
						array('NumFormat' => "[$$symbol] #,###,0.00")
				);
			}

			$format = $format . '_' . $symbol;
		}

		if (!isset($this->col_letters[$column->field])) {
			$this->col_letters[$column->field] = $col_i;
		}

		$value = '';
		if (strpos($column->field, '=') !== 0) {
			$value = $row[$column->field];
			if ($format == 'text') {
				//reemplazar los ; por \n (si se manda directamente el \n se pierde antes de llegar aca)
				if (strpos($value, ";")) {
					$value = str_replace(";", "\n", $value);
					$format = 'text_wrap';
				}
			} else if ($format == 'date') {
				//las fechas llegan en formato SQL, pasarlas a formato excel
				$value = Utiles::sql2fecha($value, $this->formato_fecha);
			}

			$this->sheet->write($this->current_row, $col_i, $value, $this->formats[$format]);

			if (isset($column->extras['rowspan']) && $column->extras['rowspan'] > 1) {
				$this->sheet->mergeCells($this->current_row - $column->extras['rowspan'] + 1, $col_i, $this->current_row, $col_i);
			}
		} else {
			//es una formula: reemplazar los nombres de campos por celdas
			$value = $column->field;
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

			$this->sheet->writeFormula($this->current_row, $col_i, $value, $this->formats[$format]);
		}
	}

}

