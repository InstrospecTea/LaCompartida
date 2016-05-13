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
		$this->loadBusiness('Parameterizing');

		if (!empty($this->data)) {
			$this->autoRender = false;

			if (empty($this->data['start_date']) || empty($this->data['end_date'])) {
				throw new Exception(__('Filtros de fecha sin contenido'));
			}

			if (!DateTime::createFromFormat('Y-m-d', $this->data['start_date'])) {
				$this->data['start_date'] = date('Y-m-d', strtotime($this->data['start_date']));
			}
			if (!DateTime::createFromFormat('Y-m-d', $this->data['end_date'])) {
				$this->data['end_date'] = date('Y-m-d', strtotime($this->data['end_date']));
			}

			$Currency = $this->CoiningBusiness->getCurrency($this->data['display_currency']);
			if (empty($Currency)) {
				throw new Exception(__('No existe la moneda seleccionada'));
			}

			$this->data['display_currency'] = $Currency;
			if (!empty($this->data['company'])) {
				$Company = $this->ParameterizingBusiness->get('Company', $this->data['company']);
				$this->data['company_name'] = $Company->fields['glosa_estudio'];
			}

			$Report = $this->ChargingBusiness->getSalesAccountingConceptsReport($this->data);
			$Report->render();
		} else {
			$this->layoutTitle = __('Reporte de Ventas');

			$restrictions = array(CriteriaRestriction::equals('Client.activo', 1));
			$clients = $this->SearchingBusiness->getAssociativeArray('Client', 'codigo_cliente', 'glosa_cliente', $restrictions);
			$this->set('clients', $clients);

			$this->set('client_group', $this->SearchingBusiness->getAssociativeArray('ClientGroup', 'id_grupo_cliente', 'glosa_grupo_cliente'));
			$this->set('billing_strategy', $this->SearchingBusiness->getAssociativeArray('BillingStrategy', 'forma_cobro', 'descripcion'));
			$this->set('currency', $this->SearchingBusiness->getAssociativeArray('Currency', 'id_moneda', 'glosa_moneda'));
			$base_currency = $this->CoiningBusiness->getBaseCurrency();
			$this->set('base_currency', $base_currency->fields['id_moneda']);
			$this->set('companies', $this->SearchingBusiness->getAssociativeArray('Company', 'id_estudio', 'glosa_estudio'));

			if (empty($this->data['start_date'])) {
				$this->data['start_date'] = date('d-m-Y', strtotime('-1 month'));
			}
		}
	}

	public function clientOldDebtAccountingConcepts() {
		$this->loadBusiness('Searching');

		if (!empty($this->data)) {
			$this->loadBusiness('Charging');

			$this->data['client_code'] = $this->data['codigo_cliente'];
			$this->data['client_secondary_code'] = $this->data['codigo_cliente_secundario'];
			$this->data['matter_code'] = $this->data['matter_code'];
			$this->data['matter_secondary_code'] = $this->data['codigo_asunto_secundario'];

			if (Conf::GetConf($this->Session, 'CodigoSecundario')) {
				$Client = new Cliente($this->Session);
				$Client->LoadByCodigoSecundario($this->data['client_secondary_code']);
				if ($Client->Loaded()) {
					$this->data['client_code'] = $Client->fields['codigo_cliente'];
				}

				$Matter = new Asunto($this->Session);
				$Matter->LoadByCodigoSecundario($this->data['matter_secondary_code']);
				if ($Matter->Loaded()) {
					$this->data['matter_code'] = $Matter->fields['codigo_asunto'];
				}
			}
			$parameters = $this->data;
			$parameters['client_code'] = $this->data['client_code'];
			$parameters['matter_code'] = $this->data['matter_code'];
			$parameters['end_date'] = date('Y-m-d', strtotime($this->data['end_date']));
			$parameters['format'] = $this->data['option'] == 'buscar' ? 'Html' : 'Spreadsheet';

			$Report = $this->ChargingBusiness->getClientOldDebtAccountingConceptsReport($parameters);

			if ($parameters['format'] == 'Html') {
				$this->set('Report', $Report);
			} else {
				$this->autoRender = false;
				$Report->render();
			}
		}

		$this->layoutTitle = __('Reporte Antigüedad Deudas Clientes');
		$this->set('billing_type', array(
			array('1', __('Sólo Honorarios')),
			array('2', __('Sólo Gastos')),
			array('3', __('Sólo Mixtas (Honorarios y Gastos)'))
		));
		$this->set('client_group', $this->SearchingBusiness->getAssociativeArray('ClientGroup', 'id_grupo_cliente', 'glosa_grupo_cliente'));
	}
}
