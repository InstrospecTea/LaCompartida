<?php

class WorkbookMiddleware {

	protected $filename;
	protected $palette = [];
	protected $formats = [];
	protected $worksheets = [];

	protected $phpExcel;
	protected $workSheetObj;
	protected $indexsheet;

	protected $writer = 'Excel2007';
	protected $extension = 'xlsx';
	protected $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

	protected $local_images = array();

	private $time = 0;
	/**
	 * Construct of the class
	 * @param string $filename
	 */
	public function __construct($filename) {
		$this->time = microtime(1);
		$this->filename = $filename;
		$this->indexsheet = 0;

		$this->phpExcel = new PHPExcel();
		$this->setDocumentProperties($this->phpExcel);

		$default = json_decode(Conf::read('FormatoExcelCobro_default'), 1);
		// Se utiliza el primer elemento ya que los que siguen corresponden al
		// cebreado y no al estilo del Excel como tal
		$format = $this->createFormatArray($default[0]);

		if (empty($format['font']['name'])) {
			$format['font']['name'] = 'Arial';
		}

		$this->phpExcel->getDefaultStyle()->applyFromArray($format);
	}

	/**
	 *
	 * @todo implement?
	 */
	public function setVersion($version) {
		if ($version == 8) {
			$this->writer = 'Excel5';
			$this->extension = 'xls';
			$this->content_type = 'application/vnd.ms-excel';
		}
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
		$file = pathinfo($this->filename);
		header("Content-Type: {$this->content_type}");
		header('Content-Disposition: attachment;filename="' . $file['filename'] . '.' . $this->extension . '"');
		header('Cache-Control: max-age=0');
		header('Pragma: public');

		$this->phpExcel->setActiveSheetIndex(0);

		// aplicar formatos a las hojas

		$writer = PHPExcel_IOFactory::createWriter($this->phpExcel, $this->writer);

		$writer->save('php://output');

		unset($this->workSheetObj);
		unset($this->phpExcel);
		$this->deleteLocalImages();
	}

	/**
	 * Set document properties
	 * @param PHPExcel $phpExcel
	 */
	private function setDocumentProperties($phpExcel) {
		$this->phpExcel->getProperties()->setCreator('Lemontech')
							 ->setLastModifiedBy('Lemontech')
							 ->setTitle($this->filename)
							 ->setSubject($this->filename)
							 ->setDescription('Reporte generado por The TimeBilling, http://thetimebilling.com/.')
							 ->setKeywords('timebilling lemontech');
	}

	/**
	 * Add formats to cells
	 * @param FormatMiddleware $format
	 * @param int $row
	 * @param int $col
	 */
	private function setFormat($format, $row, $col) {
		$formatArray = [];

		if ($row == -1) {
			$cellCode = $col;
		} else if($col == -1) {
			$cellCode = $row;
		} else {
			$cellCode = PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);
		}

		$formatArray = $this->createFormatArray($format->getElements());

		$this->workSheetObj->getStyle($cellCode)->applyFromArray($formatArray);

		return;
	}

	public function createFormatArray($format) {
		$formatArray = [];

		foreach ($format as $key => $formatValue) {
			switch (strtolower($key)) {
				case 'fontfamily':
					$formatArray['font']['name'] = $formatValue;
					break;
				case 'size':
					$formatArray['font']['size'] = $formatValue;
					break;
				case 'align':
					$formatArray['alignment']['horizontal'] = $formatValue;
					break;
				case 'valign':
					$formatArray['alignment']['vertical'] = $formatValue;
					break;
				case 'bold':
					$formatArray['font']['bold'] = $formatValue;
					break;
				case 'italic':
					$formatArray['font']['italic'] = $formatValue;
					break;
				case 'color':
					$formatArray['font']['color'] = ['argb' => $formatValue];
					break;
				case 'locked':
					$formatArray['protection'] = ['locked' => PHPExcel_Style_Protection::PROTECTION_PROTECTED];
					break;
				case 'top':
					$formatArray['borders']['top'] = ['style' => $formatValue];
					break;
				case 'bottom':
					$formatArray['borders']['bottom'] = ['style' => $formatValue];
					break;
				case 'fgcolor':
					if (is_int($formatValue)) {
						if ($formatValue > 8 && $formatValue < 64) {
							// the subtraction is for continue the logic of the method setCustomColor
							$rgb = $this->palette[$formatValue - 8];
							$formatValue = $this->rgb2hex($rgb);
						}
					}

					$formatArray['fill']['type'] = PHPExcel_Style_Fill::FILL_SOLID;
					$formatArray['fill']['startcolor'] = ['argb' => $formatValue];
					break;
				case 'textwrap':
					$formatArray['alignment']['wrap'] = $formatValue;
					break;
				case 'numformat':
					$formatArray['numberformat'] = ['code' => $formatValue];
					break;
				case 'border':
					$formatArray['borders']['allborders'] = ['style' => $formatValue];
					break;
				case 'underline':
					$formatArray['font']['underline'] = $formatValue;
					break;
				case 'textrotation':
					switch (intval($formatValue)) {
						case 90:
							$formatArray['alignment']['rotation'] = -90;
							break;
						case 270:
							$formatArray['alignment']['rotation'] = 90;
							break;
					}
					break;
			}
		}

		return $formatArray;
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
	 * Set document right margin
	 * @param float $margin
	 */
	public function setMarginRight($margin) {
		$this->workSheetObj->getPageMargins()->setRight($margin);
	}

	/**
	 * Set document left margin
	 * @param float $margin
	 */
	public function setMarginLeft($margin) {
		$this->workSheetObj->getPageMargins()->setLeft($margin);
	}

	/**
	 * Set document top margin
	 * @param float $margin
	 */
	public function setMarginTop($margin) {
		$this->workSheetObj->getPageMargins()->setTop($margin);
	}

	/**
	 * Set document bottom margin
	 * @param float $margin
	 */
	public function setMarginBottom($margin) {
		$this->workSheetObj->getPageMargins()->setBottom($margin);
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
	 * @param int $hidden
	 */
	public function setColumn($firstcol, $lastcol, $width, $format = null, $hidden = 0) {
		$column = PHPExcel_Cell::stringFromColumnIndex($firstcol);

		$this->workSheetObj->getColumnDimension($column)->setWidth($width * 1.8);

		if (is_numeric($hidden) && $hidden == 1) {
			$this->workSheetObj->getColumnDimension($column)->setVisible(false);
		}

		if(is_object($format)) {
			$this->formats[$column] = $format;
		}
	}

	/**
	 * Add a row element (properties)
	 * @param int $row
	 * @param int $height
	 * @param FormatMiddleware $format
	 */
	public function setRow($row, $height, $format = null) {
		$row = $row + 1;

		if ($height == 0) {
			$this->workSheetObj->getRowDimension($row)->setVisible(false);
		} else {
			$this->workSheetObj->getRowDimension($row)->setRowHeight($height);
		}

		if(is_object($format)) {
			$this->setFormat($format, $row, -1);
		}
	}

	/**
	 * Add cells merged
	 * @param int $first_row
	 * @param int $first_col
	 * @param int $last_row
	 * @param int $last_col
	 */
	public function mergeCells($first_row, $first_col, $last_row, $last_col) {
		if ($first_col > $last_col) {
			$temp = $first_col;
			$first_col = $last_col;
			$last_col = $temp;
		}

		$cellsMerged =
					PHPExcel_Cell::stringFromColumnIndex($first_col).($first_row + 1) .
					":" .
					PHPExcel_Cell::stringFromColumnIndex($last_col).($last_row + 1);

		$this->workSheetObj->mergeCells($cellsMerged);
	}

	/**
	 * Insert a bitmap
	 * @param int $row
	 * @param int $col
	 * @param string $bitmap
	 * @param int $x
	 * @param int $y
	 * @param int $scale_x
	 * @param int $scale_y
	 */
	public function insertBitmap($row, $col, $bitmap, $x = 0, $y = 0, $scale_x = 1, $scale_y = 1) {
		if (filter_var($bitmap, FILTER_VALIDATE_URL)) {
			$path_parts = pathinfo($bitmap);
			$data = $this->getContentCurl($bitmap);
			$bitmap = $this->writeLocalImage($data, '/tmp/' . $path_parts['basename']);
		}

		$objDrawing = new PHPExcel_Worksheet_Drawing();
		$objDrawing->setPath($bitmap)
							->setCoordinates(PHPExcel_Cell::stringFromColumnIndex($col).($row + 1))
							->setOffsetX($x)
							->setOffsetY($y)
							->setWidthAndHeight($objDrawing->getWidth() * $scale_x, $objDrawing->getHeight() * $scale_y)
							->setWorksheet($this->workSheetObj);

		unset($objDrawing);
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

		$this->mergeFormat($format, $row, $col);
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

		$this->mergeFormat($format, $row, $col);
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

		$this->mergeFormat($format, $row, $col);
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

		$this->mergeFormat($format, $row, $col);
	}

	/**
	 * Add formula to cell
	 * @param int $row
	 * @param int $col
	 * @param string $note
	 */
	public function writeNote($row, $col, $note) {
		$cellCode = PHPExcel_Cell::stringFromColumnIndex($col).($row + 1);

		$this->workSheetObj
			->getComment($cellCode)
			->setAuthor('The TimeBilling');

		$this->workSheetObj
			->getComment($cellCode)
			->getText()
			->createTextRun(utf8_encode($note));
	}

	/**
	 * Set landscape orientation
	 */
	public function setLandscape() {
		$this->workSheetObj->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	}

	/**
	 * Set portrait orientation
	 */
	public function setPortrait() {
		$this->workSheetObj->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
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

	/**
	 * Set sheet center horizontal
	 * @param int $value
	 */
	public function centerHorizontally($value){
		$this->workSheetObj->getPageSetup()->setHorizontalCentered($value == 1 ? true : false);
	}

	/**
	 * Set sheet center horizontal
	 * @param FormatMiddleware $format
	 * @param int $row
	 * @param int $col
	 */
	protected function mergeFormat($format, $row, $col) {
		$column = PHPExcel_Cell::stringFromColumnIndex($col);

		if (!is_null($format)) {
			if (isset($this->formats[$column])) {
				$format = $format->merge($this->formats[$column]);
			}

			$this->setFormat($format, $row, $col);
		} else if (!is_null($this->formats[$column])) {
			$this->setFormat($this->formats[$column], $row, $col);
		}
	}

	private function getContentCurl($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$response = curl_exec($curl);
		curl_close($curl);

		return $response;
	}

	private function writeLocalImage($data, $path) {
		$file = fopen($path,'w');
		fwrite($file, $data);
		fclose($file);

		$this->local_images[] = $path;

		return $path;
	}

	private function deleteLocalImages() {
		foreach ($this->local_images as $path) {
			unlink($path);
		}
	}
}
