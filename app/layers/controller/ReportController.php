<?php

class ReportController extends AbstractController {

	function agrupatedWork() {
		$this->loadBusiness('Working');
		$report = $this->WorkingBusiness->agrupatedWorkReport($this->data);
		$report->render();
		exit;
	}

	public function agrupatedWorkFilters() {
		$this->loadBusiness('Coining');

		$this->set('gropued_by_default', 'lawyer');
		$this->set('gropued_by', array('lawyer' => 'Abogado', 'client' => 'Cliente'));
		$this->set('mostrar_valores', array(__('Horas Trabajadas'), __('Horas Facturables Corregidas')));
		$this->set('monedas', $this->CoiningBusiness->currenciesToArray($this->CoiningBusiness->getCurrencies()));

		$moneda_base = $this->CoiningBusiness->getBaseCurrency();
		$this->set('moneda_base', $moneda_base->get($moneda_base->getIdentity()));

		$response['detail'] = $this->renderTemplate('Reports/agrupated_work_filters');

		$this->renderJSON($response);
	}

	public function productionByPeriod() {
		$this->loadBusiness('Working');
		$this->loadBusiness('Coining');
		$this->loadBusiness('Searching');

		$projectArea = $this->SearchingBusiness->getAssociativeArray('ProjectArea', 'id_area_proyecto', 'glosa');
		$projectType = $this->SearchingBusiness->getAssociativeArray('ProjectType', 'id_tipo_proyecto', 'glosa_tipo_proyecto');

		$this->layoutTitle = 'Reporte de Producción por Periodo';
		$this->set('cobrable_estados', array('No', 'Si'));
		$this->set('mostrar_estados', array(__('Horas Trabajadas'), __('Horas Cobradas'), __('Valor Cobrado')));
		$this->set('monedas', $this->CoiningBusiness->currenciesToArray($this->CoiningBusiness->getCurrencies()));
		$this->set('areas', $projectArea);
		$this->set('tipo_asunto', $projectType);
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

	public function areaCharge() {
		if (!empty($_REQUEST['btn_reporte'])) {
			$filter = array(
				'desde' => $_REQUEST['fecha1'],
				'hasta' => $_REQUEST['fecha2'],
				'estado' => $_REQUEST['estado'] == 'todos' ? null : $_REQUEST['estado'],
				'usuarios' => $_REQUEST['usuarios']
			);
			$this->loadBusiness('Charging');
			$this->loadBusiness('Coining');
			$baseCurrency = $this->CoiningBusiness->getBaseCurrency();
			$filter['id_moneda'] = $baseCurrency->fields['id_moneda'];
			$data = $this->ChargingBusiness->getAreaAgrupatedReport($filter);
			$this->loadReport('AreaAgrupatedCharge', 'Report');
			$this->Report->setParameters(
				array(
					'fechaIni' => $filter['desde'],
					'fechaFin' => $filter['hasta'],
					'format' => 'Spreadsheet'
				)
			);
			$this->Report->setData($data);
			$this->Report->setOutputType('XLS');
			$this->Report->setConfiguration('sesion', $this->Session);
			$this->Report->render();
		}
		$this->layoutTitle = 'Reporte Cobros por Area';
		$listaUsuarios = $this->Session->usuario->ListarActivos('', 'PRO');
		$this->set('listaUsuarios', $listaUsuarios);
		$this->set('Html', new \TTB\Html());
		$this->set('Form', new Form($this->Session));
	}
}
