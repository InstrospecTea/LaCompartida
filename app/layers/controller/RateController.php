<?php

class RateController extends AbstractController {

	public $helpers = array(array('\TTB\Html', 'Html'), 'Form');

	/**
	 * Carga la página principal del módulo
	 * @return mixed
	 */
	public function ErrandsRate() {
		$this->layoutTitle = __('Ingreso de Tarifas de Trámites');

		$this->loadBusiness('Rate');
		$this->loadBusiness('Coining');

		$rates = $this->RateBusiness->getErrandsRate();
		$errands_rate_fields = $this->RateBusiness->getErrandsRateFields();

		$errands_rate_table = array();

		foreach ($errands_rate_fields as $errand) {
			$coin_errand = new stdClass();
			$coin_errand->id_moneda = $errand['id_moneda'];
			$coin_errand->id_tramite_tipo = $errand['id_tramite_tipo'];
			$errands_rate_table[$errand['glosa_tramite']][] = $coin_errand;
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
		$this->loadBusiness('Rate');
		$errands_rate_values = $this->RateBusiness->getErrandsRateValue($this->params['id_tarifa']);
		$errand_rate_detail = $this->RateBusiness->getErrandRateDetail($this->params['id_tarifa']);

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
		$this->loadBusiness('Rate');
		$num_contracts = $this->RateBusiness->getContractsWithErrandRate($this->params['id_tarifa']);

		$this->renderJSON($num_contracts);
	}

	/**
	 * Cambia la tarifa trámite por defecto de los contratos
	 * @return Object
	 */
	public function changeDefaultErrandRateOnContracts() {
		$this->loadBusiness('Rate');
		$result = $this->RateBusiness->updateDefaultErrandRateOnContracts($this->params['id_tarifa']);

		$response = new stdClass();
		$response->success = $result;

		$this->renderJSON($response);
	}

	/**
	 * Elimina una tarifa trámite
	 * @return Object
	 */
	public function deleteErrandRate() {
		$this->loadBusiness('Rate');
		$total_rates = $this->RateBusiness->countRates();

		$response = new stdClass();

		if ($total_rates > 1) {
			$result = $this->RateBusiness->deleteErrandRate($this->params['id_tarifa']);
			if ($result == true) {
				$response->success = true;
				$response->message = utf8_encode(__('La tarifa trámite se ha eliminado satisfactoriamente'));
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
		$this->loadBusiness('Rate');
		$errand_rate_id = $this->params['params']['rate_id'];
		$response = new stdClass();

		if (!empty($errand_rate_id)) {
			$rates = $this->params['params']['rates'];

			foreach ($rates as $key => $value) {
				$rates[$key]['id_tramite_tarifa'] = $errand_rate_id;
			}

			if (isset($this->params['params']['glosa_tramite_tarifa'])) {
				$errand_rate['glosa_tramite_tarifa'] = $this->params['params']['glosa_tramite_tarifa'];
			}

			if (isset($this->params['params']['tarifa_defecto'])) {
				$errand_rate['tarifa_defecto'] = $this->params['params']['tarifa_defecto'];
			}

			$result = $this->RateBusiness->updateErrandRate($errand_rate_id, $errand_rate, $rates);

			$response->success = $result ? true : false;
			$response->message = $result ? __('La tarifa se ha modificado satisfactoriamente') : __('Ha ocurrido un problema');
		} else {
			$rates = $this->params['params']['rates'];

			if (isset($this->params['params']['glosa_tramite_tarifa'])) {
				$errand_rate['glosa_tramite_tarifa'] = "'{$this->params['params']['glosa_tramite_tarifa']}'";
			}

			if (isset($this->params['params']['tarifa_defecto'])) {
				$errand_rate['tarifa_defecto'] = $this->params['params']['tarifa_defecto'];
			}

			$result = $this->RateBusiness->insertErrandRate($errand_rate, $rates);

			$response->success = $result->success ? true : false;
			$response->message = $result->success ? __('La tarifa se ha creado satisfactoriamente') : __('Ha ocurrido un problema: ' . $result->message);
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
