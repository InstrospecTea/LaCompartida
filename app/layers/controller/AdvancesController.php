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

	public function isUsedInPay($id_cobro, $id_documento_pago) {
		$this->loadModel('Documento');
		$this->Documento->LoadByCobro($id_cobro, 'id_documento');

		$criteria = new Criteria($this->Session);
		$result = $criteria
				->add_select('count(1) > 0', 'used')
				->add_from('neteo_documento')
				->add_restriction(CriteriaRestriction::and_clause(
						CriteriaRestriction::equals('id_documento_cobro', $this->Documento->fields['id_documento']),
						CriteriaRestriction::equals('id_documento_pago', $id_documento_pago)
				))
				->run();
		$this->renderJSON($result[0]);
	}

}
