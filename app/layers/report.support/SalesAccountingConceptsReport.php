<?php

class SalesAccountingConceptsReport extends AbstractReport implements ISalesAccountingConceptsReport {

	protected function setUp() {
		$this->setOutputType('Spreadsheet');
		$this->reportEngine->engine->setCustomColor(35, 220, 255, 220);
		$this->reportEngine->engine->setCustomColor(36, 255, 255, 220);

		$this->setConfiguration('filename', __('reporte_ventas'));
	}

	protected function agrupateData($data) {
		return $data;
	}

	protected function present() {
		$ws = $this->reportEngine->engine->addWorksheet(__('Reportes'));
		$ws->write(1, 1, __('REPORTE TOTAL MONTOS FACTURADOS MENSUALMENTE'));
	}

}
