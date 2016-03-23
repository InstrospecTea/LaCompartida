<?php

class WorkbookMiddleware {

	protected $filename;
	protected $palette = [];
	protected $formats = [];
	protected $worksheets = [];

	protected $phpExcel;
	protected $workSheetObj;
	protected $indexsheet;

	/**
	 * Construct of the class
	 * @param string $fileName
	 */
	public function __construct($filename) {
		$this->filename = $filename;
		$this->indexsheet = 0;

		$this->phpExcel = new PHPExcel();
		$this->setDocumentProperties($phpExcel);
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
		if ($this->indexsheet <= 0) {
			$this->workSheetObj = $this->phpExcel->getActiveSheet();
			$this->indexsheet++;
		} else {
			$this->workSheetObj = $this->phpExcel->createSheet($this->indexsheet);
			$this->indexsheet++;
		}

		$this->workSheetObj->setTitle(utf8_encode($name));

		return $this;
	}

	/**
	 *
	 * @param int $row
	 * @param int $col
	 */
	public function rowcolToCell($row, $col) {
		if (is_int($row) && is_int($col)) {
			return PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);
		} else {
			return null;
		}
	}

	/**
	 *
	 * @param string $filename
	 */
	public function send($filename) {
		$this->filename = $filename;
	}

	/**
	 * Download the document
	 */
	public function close() {
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $this->filename . '"');
		header('Cache-Control: max-age=0');
		header('Cache-Control: max-age=1'); // IE 9
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: cache, must-revalidate');
		header('Pragma: public');

		$this->phpExcel->setActiveSheetIndex(0);

		$writer = PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel5');
		$writer->setPreCalculateFormulas(true);

		$writer->save('php://output');

		unset($this->workSheetObj);
		unset($this->phpExcel);
	}

	/**
	 * Set document properties
	 * @param PHPExcel $phpExcel
	 */
	private function setDocumentProperties($phpExcel) {
		$this->phpExcel->getProperties()->setCreator('LemonTech')
							 ->setLastModifiedBy('LemonTech')
							 ->setTitle($filename)
							 ->setSubject($filename)
							 ->setDescription('Reporte generado por The TimeBilling, http://thetimebilling.com/.')
							 ->setKeywords('timebilling lemontech');
	}

	/**
	 * Add formats to cells
	 * @param array $formats
	 * @param int $row
	 * @param int $col
	 */
	private function setFormat($format, $row, $col) {
		$cellCode = PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);

		foreach ($format->getElements() as $key => $formatValue) {
			if (!is_null($formatValue)) {
				switch ($key) {
					case 'size':
						$this->workSheetObj->getStyle($cellCode)->getFont()->setSize($formatValue);
						break;
					case 'align':
						$this->workSheetObj->getStyle($cellCode)->getAlignment()->setHorizontal($formatValue);
						break;
					case 'valign':
						$this->workSheetObj->getStyle($cellCode)->getAlignment()->setVertical($formatValue);
						break;
					case 'bold':
						$this->workSheetObj->getStyle($cellCode)->getFont()->setBold($formatValue);
						break;
					case 'italic':
						$this->workSheetObj->getStyle($cellCode)->getFont()->setItalic($formatValue);
						break;
					case 'color':
						$this->workSheetObj->getStyle($cellCode)->getFont()->getColor()->setARGB($formatValue);
						break;
					case 'locked':
						if ($value) {
							$this->workSheetObj->getStyle($cellCode)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);
						}
						break;
					case 'top':
						if (strval($formatValue) == '1') {
							$this->workSheetObj->getStyle($cellCode)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
						} else if (strval($formatValue) == '2') {
							$this->workSheetObj->getStyle($cellCode)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);
						}
						break;
					case 'bottom':
						if (strval($formatValue) == '1') {
							$this->workSheetObj->getStyle($cellCode)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
						} else if (strval($formatValue) == '2') {
							$this->workSheetObj->getStyle($cellCode)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);
						}
						break;
					case 'fgcolor':
						$formatValue = is_string($formatValue) ? intval($formatValue) : $formatValue;

						if (is_int($formatValue) && ($formatValue > 8 && $formatValue < 64)) {
							// the subtraction is for continue the logic of the method setCustomColor
							$rgb = $this->palette[$formatValue - 8];

							$this->workSheetObj->getStyle($cellCode)
											->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
											->getStartColor()->setRGB($this->rgb2hex($rgb));
						}
						break;
					case 'textwrap':
						$this->workSheetObj->getStyle($cellCode)->getAlignment()->setWrapText($formatValue);
						break;
					case 'numformat':
						$this->workSheetObj->getStyle($cellCode)->getNumberFormat()->setFormatCode($formatValue);
						break;
					case 'border':
						if (strval($formatValue) == '1') {
							$this->workSheetObj->getStyle($cellCode)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
						} else if (strval($formatValue) == '2') {
							$this->workSheetObj->getStyle($cellCode)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK);
						}
						break;
					case 'underline':
						if (strval($formatValue) == '1') {
							$this->workSheetObj->getStyle($cellCode)->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
						} else if (strval($formatValue) == '2') {
							$this->workSheetObj->getStyle($cellCode)->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_DOUBLE);
						}
						break;
					case 'textrotation':
						switch (intval($formatValue)) {
							case 90:
								$this->workSheetObj->getRowDimension($row + 1)->setRowHeight(-1);
								$this->workSheetObj->getStyle($cellCode)->getAlignment()->setTextRotation(-90);
								break;
							case 270:
								$this->workSheetObj->getRowDimension($row + 1)->setRowHeight(-1)(true);
								$this->workSheetObj->getStyle($cellCode)->getAlignment()->setTextRotation(90);
								break;
						}
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

	/* Worksheet Methods*/

	/**
	 * Set the type of papper
	 * http://www.osakac.ac.jp/labs/koeda/tmp/phpexcel/Documentation/API/PHPExcel_Worksheet/PHPExcel_Worksheet_PageSetup.html#methodsetPaperSize
	 * @param string $size
	 */
	public function setPaper($size = 0) {
		$this->workSheetObj->getPageSetup()->setPaperSize($size);
	}

	/**
	 * Hide printed Gridlines
	 */
	public function hideGridlines() {
		$this->workSheetObj->setPrintGridlines(false);
	}

	/**
	 * Hide document Gridlines
	 */
	public function hideScreenGridlines() {
		$this->workSheetObj->setShowGridlines(false);
	}

	/**
	 * Set document margins
	 * @param float $margin
	 */
	public function setMargins($margin) {
		$this->workSheetObj->getPageMargins()
											->setTop($margin)
											->setRight($margin)
											->setLeft($margin)
											->setBottom($margin);
	}

	/**
	 * Set fit to pages
	 * @param int $width
	 * @param int $height
	 */
	public function fitToPages($width, $height) {
		$this->workSheetObj->getPageSetup()
											->setFitToPage(true)
											->setFitToWidth($width)
											->setFitToHeight($height);
	}

	/**
	 * Add a column element (properties)
	 * @param int $firstcol
	 * @param int $lastcol
	 * @param int $width
	 * @param FormatMiddleware $format
	 * @param boolean $hidden
	 * @param int $level
	 */
	public function setColumn($firstcol, $lastcol, $width, $format = null, $hidden = false, $level = 0) {
		$column = PHPExcel_Cell::stringFromColumnIndex($firstcol);

		$this->workSheetObj->getColumnDimension($column)->setWidth($width);

		if ($hidden) {
			$this->workSheetObj->getColumnDimension($column)->setVisible(false);
		}

		//TODO: format and level.
	}

	/**
	 * Add a row element (properties)
	 * @param int $row
	 * @param int $height
	 * @param FormatMiddleware $format
	 * @param boolean $hidden
	 * @param int $level
	 */
	public function setRow($row, $height, $format = null, $hidden = false, $level = 0) {
		$row = $row + 1;

		$this->workSheetObj->getRowDimension($row)->setRowHeight($height);

		if ($hidden) {
			$this->workSheetObj->getRowDimension($row)->setVisible(false);
		}

		//TODO: format and level.
	}

	/**
	 * Add cells merged
	 * @param int $first_row
	 * @param int $first_col
	 * @param int $last_row
	 * @param int $last_col
	 */
	public function mergeCells($first_row, $first_col, $last_row, $last_col) {
		$cellsMerged =
					PHPExcel_Cell::stringFromColumnIndex($first_col).($first_row + 1) .
					":" .
					PHPExcel_Cell::stringFromColumnIndex($last_col).($last_row + 1)
					;

		$this->workSheetObj->mergeCells($cellsMerged);
	}

	/**
	 * Add data to cell
	 * @param int $row
	 * @param int $col
	 * @param string $token
	 * @param FormatMiddleware $format
	 */
	public function write($row, $col, $token, $format = null) {
		$cellCode = PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);

		$this->workSheetObj->setCellValue(
				$cellCode,
				mb_detect_encoding($token, 'UTF-8', true) ? $token : utf8_encode($token)
		);

		if (!is_null($format)) {
			$this->setFormat($format, $row, $col);
		}
	}

	/**
	 * Add data to cell
	 * @param int $row
	 * @param int $col
	 * @param string $token
	 * @param FormatMiddleware $format
	 */
	public function writeString($row, $col, $token, $format = null) {
		$cellCode = PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);

		$this->workSheetObj->setCellValueExplicit(
				$cellCode,
				mb_detect_encoding($token, 'UTF-8', true) ? $token : utf8_encode($token),
				PHPExcel_Cell_DataType::TYPE_STRING
		);

		if (!is_null($format)) {
			$this->setFormat($format, $row, $col);
		}
	}

		/**
	 * Add number to cell
	 * @param int $row
	 * @param int $col
	 * @param number $num
	 * @param FormatMiddleware $format
	 */
	public function writeNumber($row, $col, $num, $format = null) {
		$cellCode = PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);

		$this->workSheetObj->setCellValueExplicit(
				$cellCode,
				mb_detect_encoding($num, 'UTF-8', true) ? $num : utf8_encode($num),
				PHPExcel_Cell_DataType::TYPE_NUMERIC
		);

		if (!is_null($format)) {
			$this->setFormat($format, $row, $col);
		}
	}

	/**
	 * Add formula to cell
	 * @param int $row
	 * @param int $col
	 * @param string $formula
	 * @param FormatMiddleware $format
	 */
	public function writeFormula($row, $col, $formula, $format = null) {
		$cellCode = PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);

		$formula = str_replace(';', ',', $formula);

		$this->workSheetObj->setCellValueExplicit(
				$cellCode,
				mb_detect_encoding($formula, 'UTF-8', true) ? $formula : utf8_encode($formula),
				PHPExcel_Cell_DataType::TYPE_FORMULA
		);

		if (!is_null($format)) {
			$this->setFormat($format, $row, $col);
		}
	}

	/**
	 * Add formula to cell
	 * @param int $row
	 * @param int $col
	 * @param string $note
	 */
	public function writeNote($row, $col, $note) {
		$cellCode = PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);

		$this->workSheetObj->getComment($cellCode)->setAuthor('The TimeBilling');

		$this->workSheetObj->getComment($cellCode)
											->getText()->createTextRun($note);
	}

	/**
	 * Set landscape orientation
	 */
	public function setLandscape() {
		$this->workSheetObj->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	}

	/**
	 * Freeze panes
	 * @param array $panes
	 */
	public function freezePanes($panes) {
		$row = is_null($panes[0]) ? 1 : $panes[0] + 1;
		$col = is_null($panes[1]) ? 0 : $panes[1];

		$this->workSheetObj->freezePaneByColumnAndRow($col, $row);
	}

	/**
	 * Set input encoding
	 * @param string $encode
	 *
	 * @todo implement
	 */
	public function setInputEncoding($encode) {

	}

	/**
	 * Set sheet zoom
	 * @param int $scale
	 */
	public function setZoom($scale) {
		$this->workSheetObj->getSheetView()->setZoomScale($scale);
	}

}
