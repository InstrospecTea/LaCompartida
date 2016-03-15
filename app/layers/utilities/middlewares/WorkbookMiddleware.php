<?php

class WorkbookMiddleware {

	protected $filename;
	protected $palette = [];
	protected $formats = [];
	protected $worksheets = [];

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
	 */
	public function addWorksheet($name = '') {
		$worksheet = new WorksheetMiddleware($name);

		$this->worksheets[] = $worksheet;

		return $worksheet;
	}

	/**
	 * Build and download the document
	 * @param string $filename
	 */
	public function send($filename) {
		$phpExcel = new PHPExcel();

		$this->setDocumentProperties($phpExcel);

		foreach ($this->worksheets as $key => $worksheet) {
			if ($key == 0) {
				$workSheetObj = $phpExcel->getActiveSheet();
			} else {
				$workSheetObj = $phpExcel->createSheet($key);
			}

			$this->setSheetProperties($workSheetObj, $worksheet);
			$this->setColumns($workSheetObj, $worksheet);
			$this->setRows($workSheetObj, $worksheet);
			$this->mergeCells($workSheetObj, $worksheet);
			foreach ($worksheet->getElements() as $value) {
				$cellCode = PHPExcel_Cell::stringFromColumnIndex($value['col']).($value['row'] + 1);

				$this->setData($workSheetObj, $value, $cellCode);

				if (!is_null($value['format'])) {
					$this->setFormats($workSheetObj, $value['format'], $cellCode);
				}
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
	private function setDocumentProperties($phpExcel) {
		$phpExcel->getProperties()->setCreator('LemonTech')
							 ->setLastModifiedBy('LemonTech')
							 ->setTitle($filename)
							 ->setSubject($filename)
							 ->setDescription('Reporte generado por The TimeBilling, http://thetimebilling.com/.')
							 ->setKeywords('timebilling lemontech');
	}

	/**
	 * Set sheet properties
	 * @param PHPExcel_Worksheet $workSheetObj
	 * @param WorksheetMiddleware $worksheet
	 */
	private function setSheetProperties($workSheetObj, $worksheet) {
		$workSheetObj->setTitle(utf8_encode($worksheet->getTitle()));
		$workSheetObj->getPageMargins()
							->setTop($worksheet->getMarginTop())
							->setRight($worksheet->getMarginRight())
							->setLeft($worksheet->getMarginLeft())
							->setBottom($worksheet->getMarginBottom());
		$workSheetObj->getPageSetup()->setPaperSize($worksheet->getPaper());
		$workSheetObj->setPrintGridlines($worksheet->getPrintGridlines());
		$workSheetObj->setShowGridlines($worksheet->getScreenGridlines());
		$workSheetObj->getPageSetup()->setFitToPage($worksheet->getFitPage());
		$workSheetObj->getPageSetup()->setFitToWidth($worksheet->getFitWidth());
		$workSheetObj->getPageSetup()->setFitToHeight($worksheet->getFitHeight());
	}

	/**
	 * Set columns properties
	 * @param PHPExcel_Worksheet $workSheetObj
	 * @param WorksheetMiddleware $worksheet
	 *
	 * @todo formats and levels
	 */
	private function setColumns($workSheetObj, $worksheet) {
		foreach ($worksheet->getColumns() as $value) {
			$column = PHPExcel_Cell::stringFromColumnIndex($value['firstcol']);

			$workSheetObj->getColumnDimension($column)->setWidth($value['width']);

			if (!is_null($value['hidden']) && $value['hidden']) {
				$workSheetObj->getColumnDimension($column)->setVisible(false);
			}

			//TODO: format and level.
		}
	}

	/**
	 * Set the row properties
	 * @param PHPExcel_Worksheet $workSheetObj
	 * @param WorksheetMiddleware $worksheet
	 *
	 * @todo formats and levels
	 */
	private function setRows($workSheetObj, $worksheet) {
		foreach ($worksheet->getRows() as $value) {
			$row = $value['row'] + 1;

			$workSheetObj->getRowDimension($row)->setRowHeight($value['height']);

			if (!is_null($value['hidden']) && $value['hidden']) {
				$workSheetObj->getRowDimension($row)->setVisible(false);
			}

			//TODO: format and level.
		}
	}

	/**
	 * Merge cells
	 * @param PHPExcel_Worksheet $workSheetObj
	 * @param WorksheetMiddleware $worksheet
	 */
	private function mergeCells($workSheetObj, $worksheet) {
		foreach ($worksheet->getCellsMerged() as $value) {
			$cellsMerged =
					PHPExcel_Cell::stringFromColumnIndex($value['first_col']).($value['first_row'] + 1) .
					":" .
					PHPExcel_Cell::stringFromColumnIndex($value['last_col']).($value['last_row'] + 1)
					;

			$workSheetObj->mergeCells($cellsMerged);
		}
	}

	/**
	 * Add data to cells
	 * @param PHPExcel_Worksheet $workSheet
	 * @param array $element
	 * @param string $cellCode
	 */
	private function setData($workSheet, $element, $cellCode) {
		if ($element['type'] == 'formula') {
			$element['data'] = str_replace(';', ',', $element['data']);
			$workSheet->getCell($cellCode)->setDataType(PHPExcel_Cell_DataType::TYPE_FORMULA);
		}

		$workSheet->setCellValue(
				$cellCode,
				utf8_encode($element['data'])
		);
	}

	/**
	 * Add formats to cells
	 * @param PHPExcel_Worksheet $workSheet
	 * @param FormatMiddleware $formats
	 * @param string $cellCode
	 *
	 * @todo Implement the border format
	 */
	private function setFormats($workSheet, $formats, $cellCode) {
		foreach ($formats->getElements() as $key => $formatValue) {
			if (!is_null($formatValue)) {
				switch ($key) {
					case 'size':
						$workSheet->getStyle($cellCode)->getFont()->setSize($formatValue);
						break;
					case 'align':
						$workSheet->getStyle($cellCode)->getAlignment()->setHorizontal($formatValue);
						break;
					case 'valign':
						$workSheet->getStyle($cellCode)->getAlignment()->setVertical($formatValue);
						break;
					case 'bold':
						$workSheet->getStyle($cellCode)->getFont()->setBold($formatValue);
						break;
					case 'italic':
						$workSheet->getStyle($cellCode)->getFont()->setItalic($formatValue);
						break;
					case 'color':
						$workSheet->getStyle($cellCode)->getFont()->getColor()->setARGB($formatValue);
						break;
					case 'locked':
						if ($value) {
							$workSheet->getStyle($cellCode)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);
						}
						break;
					case 'top':
						$workSheet->getStyle($cellCode)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
						break;
					case 'bottom':
						$workSheet->getStyle($cellCode)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
						break;
					case 'fgcolor':
						if (is_int($formatValue) && ($formatValue > 8 && $formatValue < 64)) {
							// the subtraction is for continue the logic of the method setCustomColor
							$rgb = $this->palette[$formatValue - 8];

							$workSheet->getStyle($cellCode)
											->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
											->getStartColor()->setRGB($this->rgb2hex($rgb));
						}
						break;
					case 'textwrap':
						$workSheet->getStyle($cellCode)->getAlignment()->setWrapText($formatValue);
						break;
					case 'numformat':
						$workSheet->getStyle($cellCode)->getNumberFormat()->setFormatCode($formatValue);
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
	 * @param PHPExcel_Worksheet $workSheet
	 *
	 * @todo Implement this method
	 */
	private function setPixmap($workSheet) {
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

		$phpExcel->setActiveSheetIndex(0);

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
