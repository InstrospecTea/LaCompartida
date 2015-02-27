<?php

class AdvancesController extends AbstractController {

	protected $prermisions = array('COB');
	public $helpers = array('EntitiesListator', 'Form', 'Paginator');

	public function get_list() {
		$this->layoutTitle = __('Adelantos');
		if ($this->request['isAjax']) {
			$this->layout = 'ajax';
			$desde = $this->data['xdesde'];
		}

		if (isset($this->params['codigo_cliente'])) {
			$this->data['codigo_cliente'] = $this->params['codigo_cliente'];
		}
		if (isset($this->params['pago_honorarios'])) {
			$this->data['pago_honorarios'] = $this->params['pago_honorarios'];
		}
		if (isset($this->params['pago_gastos'])) {
			$this->data['pago_gastos'] = $this->params['pago_gastos'];
		}
		if (isset($this->params['id_contrato'])) {
			$this->data['id_contrato'] = $this->params['id_contrato'];
		}
		if (isset($this->params['moneda'])) {
			$this->data['moneda'] = $this->params['moneda'];
		}
		if (isset($this->params['elegir_para_pago'])) {
			$this->data['elegir_para_pago'] = true;
		}

		$page = empty($this->params['page']) ? null : $this->params['page'];
		$this->loadBusiness('Advancing');
		$searchResult = $this->AdvancingBusiness->getList($this->data, 20, $page);
		$this->set('listResults', $searchResult->get('data'));
		$this->set('Pagination', $searchResult->get('Pagination'));
	}

}
