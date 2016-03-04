<?php

class WorkbookMiddleware {

	protected $worksheet;

	protected $palette = [];

	protected $formats = [];



	public function __construct($fileName) {
		//TODO: Implementar filename
	}

	public function setVersion() {

	}

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

	public function addFormat($properties = array()) {
		$format = new FormatMiddleware($properties);

		return $format;
	}

	public function addWorksheet($name = '') {
		$this->worksheet = new WorksheetMiddleware($name);

		return $this->worksheet;
	}

	public function send($filename) {
		// var_dump($this->worksheet);
		$phpExcel = new PHPExcel();

		$phpExcel->getProperties()->setCreator('LemonTech')
							 ->setLastModifiedBy('LemonTech')
							 ->setTitle($filename)
							 ->setSubject($filename)
							 ->setDescription('Reporte generado por The TimeBilling, http://thetimebilling.com/.')
							 ->setKeywords('timebilling lemontech');

		// $sheet = $phpExcel->createSheet();
		$phpExcel->getActiveSheet()->setTitle($this->worksheet->getTitle());

		// $phpExcel->addSheet($sheet);

		foreach ($this->worksheet->getCellsMerged() as $value) {
			$cellsMerged =
					PHPExcel_Cell::stringFromColumnIndex($value[1]).($value[0]+1) .
					":" .
					PHPExcel_Cell::stringFromColumnIndex($value[3]).($value[2]+1)
					;
// var_dump($cellsMerged);
			$phpExcel->getActiveSheet()->mergeCells($cellsMerged);

		}

// var_dump($this->formats);



		foreach ($this->worksheet->getElements() as $value) {
			$cellCode = PHPExcel_Cell::stringFromColumnIndex($value['col']).($value['row']+1);
var_dump($value);
			$phpExcel->getActiveSheet()->setCellValue(
					$cellCode,
					utf8_encode($value['data'])
			);

			if (!is_null($value['format'])) {
				$value['format']->setType($value['type']);

				foreach ($value['format']->getElements() as $key => $formatValue) {
					if (!is_null($formatValue)) {
						switch ($key) {
							case 'Size':
								$phpExcel->getActiveSheet()->getStyle($cellCode)->getFont()->setSize($formatValue);
								break;
							case 'Align':
								$phpExcel->getActiveSheet()->getStyle($cellCode)->getAlignment()->setHorizontal($formatValue);
								break;
							case 'VAlign':
								$phpExcel->getActiveSheet()->getStyle($cellCode)->getAlignment()->setVertical($formatValue);
								break;
							case 'Bold':
								$phpExcel->getActiveSheet()->getStyle($cellCode)->getFont()->setBold($formatValue);
								break;
							case 'Italic':
								$phpExcel->getActiveSheet()->getStyle($cellCode)->getFont()->setItalic($formatValue);
								break;
							case 'Color':

								break;
							case 'Locked':
								if ($value) {
									$phpExcel->getStyle($cellCode)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
								}
								break;
							case 'Top':

								break;
							case 'Bottom':

								break;
							case 'FgColor':

								break;
							case 'TextWrap':

								break;
							case 'NumFormat':
								$phpExcel->getActiveSheet()->getStyle($cellCode)->getNumberFormat()->setFormatCode($formatValue);
								break;
							case 'Border':

								break;
						}
					}
				}
			}
		}

		 // $this->downloadExcel($phpExcel, $filename);
	}

	public function close() {

	}

	private function downloadExcel($phpExcel, $filename) {
		// Redirect output to a clients web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' + $filename + '"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');
		// If you're serving to IE over SSL, then the following may be needed
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0

		$writer = PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');
		$writer->save('php://output');
	}

}
