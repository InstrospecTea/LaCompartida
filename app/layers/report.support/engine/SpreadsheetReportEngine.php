<?php

require_once 'Spreadsheet/Excel/Writer.php';

class SpreadsheetReportEngine extends AbstractReportEngine implements ISpreadsheetReportEngine {

	public $engine;

	public function __construct() {
		$this->engine = new Spreadsheet_Excel_Writer();
	}

	protected function configurateReport() {
	}

	protected function buildReport($data) {
		$this->engine->send("{$this->configuration['filename']}.xls");
		$this->engine->close();
	}

}
