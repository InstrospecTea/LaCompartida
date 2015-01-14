<?php

class ReportController extends AbstractController {

	function agrupatedWork() {
		$this->loadBusiness('Working');
		$report = $this->WorkingBusiness->agrupatedWorkReport($this->data);
		$report->render();
		exit;
	}

	public function productionByPeriod() {
		$this->loadBusiness('Working');
		$this->loadBusiness('Coining');
		$this->layoutTitle = 'Reporte de Producción por Periodo';
		$this->set('cobrable_estados', array('No', 'Si'));
		$this->set('mostrar_estados', array('Horas Trabajadas', 'Horas Cobradas', 'Valor Cobrado'));
		$this->set('monedas', $this->CoiningBusiness->currenciesToArray($this->CoiningBusiness->getCurrencies()));
		$moneda_base = $this->CoiningBusiness->getBaseCurrency();
		$this->set('moneda_base', $moneda_base->get($moneda_base->getIdentity()));
		if (!empty($this->data)) {
			$report = $this->WorkingBusiness->productionByPeriodReport($this->data);
			$this->set('report', $report);
			if ($this->data['opc'] == 'Spreadsheet') {
				$report->render();
			}
		}
	}
}