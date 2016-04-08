<?php

class SpreadsheetReportEngine extends AbstractReportEngine implements ISpreadsheetReportEngine {

	public $engine;

	public function __construct() {
		$this->engine = new WorkbookMiddleware();
	}

	protected function configurateReport() {
	}

	protected function buildReport($data) {
		$this->engine->send("{$this->configuration['filename']}.xls");
		$this->engine->close();
	}

}
