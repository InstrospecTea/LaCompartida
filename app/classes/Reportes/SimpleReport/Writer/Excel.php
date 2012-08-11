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
	 * @var SimpleReport
	 */
	var $SimpleReport;

	public function __construct(SimpleReport $simpleReport) {
		$this->SimpleReport = $simpleReport;
		$this->xls = new PHPExcel();
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
		$this->xls->getActiveSheet()->setTitle($this->SimpleReport->Config->title);
		//ksort($this->configuration->columns);
		// 1.1 Formatear todos los arreglos
		// 2. Correr query
		$result = $this->SimpleReport->RunReport();
		// 3. Ordenar y filtrar segun conf
		// 4. Escribir en excel
		// 4.1 Headers
		//for ($i = 0; $i < count($this->configuration->columns); $i++) {
		$columns = $this->SimpleReport->Config->VisibleColumns();

		for ($col_i = 0; $col_i < count($columns); $col_i++) {
			$column = array_pop(array_slice($columns, $col_i, 1));

			$col_letter = PHPExcel_Cell::stringFromColumnIndex($col_i);

			$this->xls->getActiveSheet()
				->setCellValue("{$col_letter}1", $column->title);
		}

		// Estilo de titulos
		$this->xls->getActiveSheet()
			->getStyle("A1:{$col_letter}1")
			->applyFromArray(SimpleReport_Writer_Excel_Format::$formats['title']);

		$first_row = 2;
			
		// 4.2 Body
		$row_i = $first_row;
		foreach ($result as $row) {
			$col_i = 0;
			foreach ($columns as $column) {
				if ($column->format == 'text') {					
					$row[$column->field] = utf8_encode($row[$column->field]);
					
					if (strpos($row[$column->field], ";")) {
						$row[$column->field] = str_replace(";", "\n", $row[$column->field]);
						
						$this->xls->getActiveSheet()
								->getStyleByColumnAndRow($col_i, $row_i)
								->getAlignment()
								->setWrapText(true);
					}
				}
				
				$this->xls->getActiveSheet()
					->setCellValueByColumnAndRow($col_i, $row_i, $row[$column->field]);
				$col_i++;
			}
			$row_i++;
		}

		// 4.3 Totales
		$formatos_con_total = array('number', 'time');
		$last_row = $row_i - 1;
		$col_i = 0;
		foreach ($columns as $column) {
			if (in_array($column->format, $formatos_con_total)) {
				$col_letter = PHPExcel_Cell::stringFromColumnIndex($col_i);
				
				$this->xls->getActiveSheet()
						->setCellValueByColumnAndRow($col_i, $row_i, "=SUBTOTAL(9,{$col_letter}{$first_row}:{$col_letter}{$last_row})");
				
				// Totales en negrita
				$formato_total = SimpleReport_Writer_Excel_Format::$formats[$column->format];
				$formato_total['font'] = array('bold' => true);
				
				$this->xls->getActiveSheet()
						->getStyle("{$col_letter}{$row_i}")
						->applyFromArray($formato_total);
			}
			$col_i++;
		}
		
//		$last_row++;
		
		// 4.4 Estilos de columnas
		for ($col_i = 0; $col_i < count($columns); $col_i++) {
			$column = array_pop(array_slice($columns, $col_i, 1));

			$col_letter = PHPExcel_Cell::stringFromColumnIndex($col_i);

			$this->xls->getActiveSheet()
				->getStyle("{$col_letter}{$first_row}:{$col_letter}{$last_row}")
				->applyFromArray(SimpleReport_Writer_Excel_Format::$formats[$column->format]);

			$this->xls->getActiveSheet()
				->getColumnDimension($col_letter)
				->setAutoSize(); // Esto debería ser (true) pero Excel queda gigante, no se por que
		}

		// 4.5 Autofilter
		$first_row--;
		$last_col_letter = $col_letter;
		$this->xls->getActiveSheet()->setAutoFilter("A{$first_row}:{$last_col_letter}{$last_row}");
		
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

}

