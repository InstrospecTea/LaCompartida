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

	public function salesAccountingConcepts() {
		$this->loadBusiness('Searching');
		$this->loadBusiness('Coining');
		$this->loadBusiness('Charging');

		if (!empty($this->data)) {
			$Report = $this->ChargingBusiness->getSalesAccountingConceptsReport($this->data);
			$Report->render();
			exit;
		} else {
			$this->layoutTitle = __('Reporte de Ventas');

			$restrictions = array(CriteriaRestriction::equals('Client.activo', 1));
			$client_code = Conf::GetConf($this->Session, 'CodigoSecundario') ? 'codigo_cliente_secundario' : 'codigo_cliente';
			$clients = $this->SearchingBusiness->getAssociativeArray('Client', $client_code, 'glosa_cliente', $restrictions);
			$this->set('clients', $clients);

			$this->set('client_group', $this->SearchingBusiness->getAssociativeArray('ClientGroup', 'id_grupo_cliente', 'glosa_grupo_cliente'));
			$this->set('billing_strategy', $this->SearchingBusiness->getAssociativeArray('BillingStrategy', 'forma_cobro', 'descripcion'));
			$this->set('currency', $this->SearchingBusiness->getAssociativeArray('Currency', 'id_moneda', 'glosa_moneda'));
			$base_currency = $this->CoiningBusiness->getBaseCurrency();
			$this->set('base_currency', $base_currency->fields['id_moneda']);

			$this->set('rate',
				array(
					'monto_thh' => __('Tarifa del cliente'),
					'monto_thh_estandar' => __('Tarifa estandar')
				)
			);

			if (empty($this->data['start_date'])) {
				$this->data['start_date'] = date('d-m-Y', strtotime('-1 month'));
			}
		}
	}
}
