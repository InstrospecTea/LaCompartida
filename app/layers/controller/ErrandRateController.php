<?php

class ErrandRateController extends AbstractController {

	public $helpers = array(array('\TTB\Html', 'Html'), 'Form');

	public function __construct() {
		parent::__construct();
		$this->loadManager('ErrandRate');
	}

	/**
	 * Carga la página principal del módulo
	 * @return mixed
	 */
	public function ErrandsRate() {
		$this->layoutTitle = __('Ingreso de Tarifas de Trámites');
		$this->loadBusiness('Coining');

		$rates = $this->ErrandRateManager->getErrandsRate();
		$errands_rate_fields = $this->ErrandRateManager->getErrandsRateFields();

		$errands_rate_table = array();

		foreach ($errands_rate_fields as $errand_rate) {
			$coin_errand = new stdClass();
			$coin_errand->id_moneda = $errand_rate['id_moneda'];
			$coin_errand->id_tramite_tipo = $errand_rate['id_tramite_tipo'];
			$errands_rate_table[$errand_rate['glosa_tramite']][] = $coin_errand;
		}

		$this->set('rates', $rates);
		$this->set('errands_rate_table', $errands_rate_table);
		$this->set('coins', $this->CoiningBusiness->currenciesToArray($this->CoiningBusiness->getCurrencies()));
	}

	/**
	 * Retorna los valores de cada casillero de tarifa trámite
	 * @return Object
	 */
	public function ErrandsRateValue() {
		$errands_rate_values = $this->ErrandRateManager->getErrandsRateValue($this->params['id_tarifa']);
		$errand_rate_detail = $this->ErrandRateManager->getErrandRateDetail($this->params['id_tarifa']);

		$response = new stdClass();
		$response->errand_rate_detail = $errand_rate_detail;
		$response->errands_rate_values = $errands_rate_values;

		$this->renderJSON($response);
	}

	/**
	 * Retorna la cantidad de contratos que tiene la tarifa trámite seleccionada
	 * @return int
	 */
	public function contractsWithErrandRate() {
		$num_contracts = $this->ErrandRateManager->getContractsWithErrandRate($this->params['id_tarifa']);

		$this->renderJSON($num_contracts);
	}

	/**
	 * Cambia la tarifa trámite por defecto de los contratos
	 * @return Object
	 */
	public function changeDefaultErrandRateOnContracts() {
		$result = $this->ErrandRateManager->updateDefaultErrandRateOnContracts($this->params['id_tarifa']);

		$response = new stdClass();
		$response->success = $result;

		$this->renderJSON($response);
	}

	/**
	 * Elimina una tarifa trámite
	 * @return Object
	 */
	public function deleteErrandRate() {
		$total_rates = $this->ErrandRateManager->countErrandsRates();

		$response = new stdClass();

		if ($total_rates > 1) {
			$result = $this->ErrandRateManager->deleteErrandRate($this->params['id_tarifa']);
			if ($result == true) {
				$response->success = true;
				$response->message = utf8_encode(__('La tarifa trámite se ha eliminado satisfactoriamente'));
				$response->default_errand_rate = $this->ErrandRateManager->getDefaultErrandRate();
			} else {
				$response->success = false;
				$response->message = __('Ha ocurrido un problema');
			}
		} else {
			$response->success = false;
			$response->message = __('Al menos debe quedar una tarifa activa en el sistema');
		}

		$this->renderJSON($response);
	}

	/**
	 * Guarda una tarifa trámite
	 * @return Object
	 */
	public function saveErrandRate() {
		$errand_rate_id = $this->params['params']['rate_id'];
		$response = new stdClass();

		if (!empty($errand_rate_id)) {
			$rates = $this->params['params']['rates'];
			$errand_rate['id_tramite_tarifa'] = $errand_rate_id;

			foreach ($rates as $key => $value) {
				$rates[$key]['id_tramite_tarifa'] = $errand_rate_id;
			}

			if (isset($this->params['params']['glosa_tramite_tarifa'])) {
				$errand_rate['glosa_tramite_tarifa'] = $this->params['params']['glosa_tramite_tarifa'];
			}

			if (isset($this->params['params']['tarifa_defecto'])) {
				$errand_rate['tarifa_defecto'] = $this->params['params']['tarifa_defecto'];
			}

			$result = $this->ErrandRateManager->updateErrandRate($errand_rate, $rates);

			$response->success = $result ? true : false;
			$response->message = $result ? __('La tarifa se ha modificado satisfactoriamente') : __('Ha ocurrido un problema');
			$response->rate_id = null;
		} else {
			$rates = $this->params['params']['rates'];

			if (isset($this->params['params']['glosa_tramite_tarifa'])) {
				$errand_rate['glosa_tramite_tarifa'] = "'{$this->params['params']['glosa_tramite_tarifa']}'";
			}

			if (isset($this->params['params']['tarifa_defecto'])) {
				$errand_rate['tarifa_defecto'] = $this->params['params']['tarifa_defecto'];
			}

			$result = $this->ErrandRateManager->insertErrandRate($errand_rate, $rates);

			$response->success = $result->success ? true : false;
			$response->message = $result->success ? __('La tarifa se ha creado satisfactoriamente') : __('Ha ocurrido un problema: ' . $result->message);
			$response->rate_id = $result->rate_id;
		}

		$this->renderJSON($response);
	}

	/**
	 * Retorna al JS el texto traducido
	 * @return Object
	 */
	public function ErrandsRateMessages() {
		$response = new stdClass();

		$response->confirm_cambio_tarifa = utf8_encode('¿' . __('Confirma cambio de tarifa') . '?');
		$response->tarifa_posee = utf8_encode(__('La tarifa posee'));
		$response->contratos_asociados = utf8_encode(__('contratos asociados.
Si continua se le asignará la tarifa estándar a los contratos afectados.
¿Está seguro de continuar?.'));
		$response->seguro_eliminar = utf8_encode('¿' . __('Está seguro de eliminar la') . ' ' . __('tarifa') . '?');
		$response->seguro_eliminar_valor = utf8_encode(__('¿Está seguro de querer eliminar la tarifa?
Esto puede provocar inconsistencia de datos en los trámites ya creados.'));

		$this->renderJSON($response);
	}
}
