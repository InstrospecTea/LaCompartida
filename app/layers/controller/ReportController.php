<?php

class ReportController extends AbstractController {

	function agrupatedWork() {
		$this->loadBusiness('Working');
		$report = $this->WorkingBusiness->agrupatedWorkReport($this->data);
		$report->render();
	}

	public function productionByPeriod() {
		$this->layoutTitle = 'Reporte de Producción por Periodo';
		$this->set('cobrable_estados', array('No', 'Si'));
		$this->set('mostrar_estados', array('total_horas' => 'Horas Trabajadas', 'total_horas_cobradas' => 'Horas Cobradas', 'total_valor_cobrado' => 'Valor Cobrado'));

		$this->loadBusiness('Working');
		if (empty($this->data)) {
			$this->info('Seleccione algún filtro de búsqueda');	
		} else {
			$report = $this->WorkingBusiness->productionByPeriodReport($this->data);
			$this->set('report', $report);
			if ($this->data['opc'] == 'Spreadsheet') {
				$report->render();
			}
		}
	}
}