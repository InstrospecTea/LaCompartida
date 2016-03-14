<?php

class WorkbookMiddleware {

	protected $worksheet;
	protected $filename;
	protected $palette = [];
	protected $formats = [];

	/**
	 * Construct of the class
	 * @param string $fileName
	 */
	public function __construct($filename) {
		$this->filename = $filename;
	}

	/**
	 *
	 * @todo implement?
	 */
	public function setVersion() {

	}

	/**
	 * This method is copy of Spreadsheet_Excel_Writer
	 * @param string $index
	 * @param string $red
	 * @param string $green
	 * @param string $blue
	 * @return int
	 */
	public function setCustomColor($index, $red, $green, $blue) {
	  // Check that the colour index is the right range
	  if ($index < 8 or $index > 64) {
	      // TODO: assign real error codes
	      return $this->raiseError('Color index $index outside range: 8 <= index <= 64');
	  }

	  // Check that the colour components are in the right range
	  if (($red   < 0 or $red   > 255) ||
	      ($green < 0 or $green > 255) ||
	      ($blue  < 0 or $blue  > 255))
	  {
	      return $this->raiseError('Color component outside range: 0 <= color <= 255');
	  }

	  $index -= 8; // Adjust colour index (wingless dragonfly)

	  // Set the RGB value
	  $this->palette[$index] = array($red, $green, $blue, 0);

	  return ($index + 8);
	}

	/**
	 * Return a new FormatMiddleware
	 * @param array $properties
	 * @return FormatMiddleware
	 */
	public function addFormat($properties = array()) {
		$format = new FormatMiddleware($properties);

		return $format;
	}

	/**
	 * Add a new WorksheetMiddleware
	 * @param string $name
	 * @return WorksheetMiddleware
	 *
	 * @todo Implement multiple sheets
	 */
	public function addWorksheet($name = '') {
		$this->worksheet = new WorksheetMiddleware($name);

		return $this->worksheet;
	}

	/**
	 * Build and download the document
	 * @param string $filename
	 */
	public function send($filename) {
		$phpExcel = new PHPExcel();

		$this->setProperties($phpExcel);
		$this->setColumns($phpExcel);
		$this->setRows($phpExcel);
		$this->mergeCells($phpExcel);
		foreach ($this->worksheet->getElements() as $value) {
			$cellCode = PHPExcel_Cell::stringFromColumnIndex($value['col']).($value['row'] + 1);

			$this->setData($phpExcel, $value, $cellCode);

			if (!is_null($value['format'])) {
				$this->setFormats($phpExcel, $value['format'], $cellCode);
			}
		}
		$this->downloadExcel($phpExcel, $filename);
	}

	/**
	 *
	 *
	 * @todo implement?
	 */
	public function close() {

	}

	/**
	 * Set document properties
	 * @param PHPExcel $phpExcel
	 */
	private function setProperties($phpExcel) {
		$phpExcel->getProperties()->setCreator('LemonTech')
							 ->setLastModifiedBy('LemonTech')
							 ->setTitle($filename)
							 ->setSubject($filename)
							 ->setDescription('Reporte generado por The TimeBilling, http://thetimebilling.com/.')
							 ->setKeywords('timebilling lemontech');

		$phpExcel->getActiveSheet()->setTitle($this->worksheet->getTitle());
		$phpExcel->getActiveSheet()->getPageMargins()
																->setTop($this->worksheet->getMarginTop())
																->setRight($this->worksheet->getMarginRight())
																->setLeft($this->worksheet->getMarginLeft())
																->setBottom($this->worksheet->getMarginBottom());
		$phpExcel->getActiveSheet()->getPageSetup()->setPaperSize($this->worksheet->getPaper());
		$phpExcel->getActiveSheet()->setPrintGridlines($this->worksheet->getPrintGridlines());
		$phpExcel->getActiveSheet()->setShowGridlines($this->worksheet->getScreenGridlines());
		$phpExcel->getActiveSheet()->getPageSetup()->setFitToPage($this->worksheet->getFitPage());
		$phpExcel->getActiveSheet()->getPageSetup()->setFitToWidth($this->worksheet->getFitWidth());
		$phpExcel->getActiveSheet()->getPageSetup()->setFitToHeight($this->worksheet->getFitHeight());
	}

	/**
	 * Set columns properties
	 * @param PHPExcel $phpExcel
	 *
	 * @todo formats and levels
	 */
	private function setColumns($phpExcel) {
		foreach ($this->worksheet->getColumns() as $value) {
			$column = PHPExcel_Cell::stringFromColumnIndex($value['firstcol']);

			$phpExcel->getActiveSheet()->getColumnDimension($column)->setWidth($value['width']);

			if (!is_null($value['hidden']) && $value['hidden']) {
				$phpExcel->getActiveSheet()->getColumnDimension($column)->setVisible(false);
			}

			//TODO: format and level.
		}
	}

	/**
	 * Set the row properties
	 * @param PHPExcel $phpExcel
	 *
	 * @todo formats and levels
	 */
	private function setRows($phpExcel) {
		foreach ($this->worksheet->getRows() as $value) {
			$row = $value['row'] + 1;

			$phpExcel->getActiveSheet()->getRowDimension($row)->setRowHeight($value['height']);

			if (!is_null($value['hidden']) && $value['hidden']) {
				$phpExcel->getActiveSheet()->getRowDimension($row)->setVisible(false);
			}

			//TODO: format and level.
		}
	}

	/**
	 * Merge cells
	 * @param PHPExcel $phpExcel
	 */
	private function mergeCells($phpExcel) {
		foreach ($this->worksheet->getCellsMerged() as $value) {
			$cellsMerged =
					PHPExcel_Cell::stringFromColumnIndex($value['first_col']).($value['first_row'] + 1) .
					":" .
					PHPExcel_Cell::stringFromColumnIndex($value['last_col']).($value['last_row'] + 1)
					;

			$phpExcel->getActiveSheet()->mergeCells($cellsMerged);
		}
	}

	/**
	 * Add data to cells
	 * @param PHPExcel $phpExcel
	 * @param array $element
	 * @param string $cellCode
	 */
	private function setData($phpExcel, $element, $cellCode) {
		if ($element['type'] == 'formula') {
			$element['data'] = str_replace(';', ',', $element['data']);
			$phpExcel->getActiveSheet()->getCell($cellCode)->setDataType(PHPExcel_Cell_DataType::TYPE_FORMULA);
		}

		$phpExcel->getActiveSheet()->setCellValue(
				$cellCode,
				utf8_encode($element['data'])
		);
	}

	/**
	 * Add formats to cells
	 * @param PHPExcel $phpExcel
	 * @param FormatMiddleware $formats
	 * @param string $cellCode
	 *
	 * @todo Implement the border format
	 */
	private function setFormats($phpExcel, $formats, $cellCode) {
		foreach ($formats->getElements() as $key => $formatValue) {
			if (!is_null($formatValue)) {
				switch ($key) {
					case 'size':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getFont()->setSize($formatValue);
						break;
					case 'align':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getAlignment()->setHorizontal($formatValue);
						break;
					case 'valign':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getAlignment()->setVertical($formatValue);
						break;
					case 'bold':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getFont()->setBold($formatValue);
						break;
					case 'italic':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getFont()->setItalic($formatValue);
						break;
					case 'color':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getFont()->getColor()->setARGB($formatValue);
						break;
					case 'locked':
						if ($value) {
							$phpExcel->getActiveSheet()->getStyle($cellCode)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);
						}
						break;
					case 'top':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
						break;
					case 'bottom':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
						break;
					case 'fgcolor':
						if (is_int($formatValue) && ($formatValue > 8 && $formatValue < 64)) {
							// the subtraction is for continue the logic of the method setCustomColor
							$rgb = $this->palette[$formatValue - 8];

							$phpExcel->getActiveSheet()->getStyle($cellCode)
											->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
											->getStartColor()->setRGB($this->rgb2hex($rgb));
						}
						break;
					case 'textwrap':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getAlignment()->setWrapText($formatValue);
						break;
					case 'numformat':
						$phpExcel->getActiveSheet()->getStyle($cellCode)->getNumberFormat()->setFormatCode($formatValue);
						break;
					case 'border':
						// TODO: Implement
						break;
				}
			}
		}
	}

	/**
	 * Add formats to cells
	 * @param PHPExcel $phpExcel
	 *
	 * @todo Implement this method
	 */
	private function setPixmap($phpExcel) {
		// TODO: implement
	}

	/**
	 * Download the Excel
	 * @param PHPExcel $phpExcel
	 * @param string $filename
	 */
	private function downloadExcel($phpExcel, $filename) {
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		header('Cache-Control: max-age=1'); // IE 9
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: cache, must-revalidate');
		header('Pragma: public');

		$writer = PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5');
		$writer->setPreCalculateFormulas(true);

		$writer->save('php://output');
	}

	/**
	 * Convert a RGB code to hexadecimal
	 * @param string $rgb
	 * @return hexadecimal code
	 */
	private function rgb2hex($rgb) {
		if (is_array($rgb)) {
			$hex = '';
			$hex .= str_pad(dechex($rgb[0]), 2, '0', STR_PAD_LEFT);
			$hex .= str_pad(dechex($rgb[1]), 2, '0', STR_PAD_LEFT);
			$hex .= str_pad(dechex($rgb[2]), 2, '0', STR_PAD_LEFT);

			return $hex;
		} else {
			return null;
		}
	}

}
