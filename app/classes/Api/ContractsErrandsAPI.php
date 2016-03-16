<?php
/**
 *
 * Clase con métodos para Generadores de Contratos
 *
 */
class ContractsErrandsAPI extends AbstractSlimAPI {

	public function getErrandValuesByRateId($errand_rate_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		$errand_type_id = $Slim->request()->params('errand_type_id');
		$errand_currency_id = $Slim->request()->params('errand_currency_id');

		$TramiteValor = new TramiteValor($Session);

		$values = $TramiteValor->findAll(array(
			'id_tramite_tipo' => $errand_type_id,
			'id_tramite_tarifa' => $errand_rate_id,
			'id_moneda' => $errand_currency_id
		));

		$this->outputJson($values);
	}

	public function getErrandsByContractId($contract_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		$ContratoTramite = new ContratoTramite($Session);

		$errands = $ContratoTramite->findAll(array(
			'id_contrato' => $contract_id
		));

		$this->outputJson($errands);
	}


	public function createErrandTypeInContract($contract_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		$errand_type_id = $Slim->request()->params('errand_type_id');

		$ContratoTramite = new ContratoTramite($Session);
		$ContratoTramite->Edit('id_contrato', $contract_id);
		$ContratoTramite->Edit('id_tramite_tipo', $errand_type_id);

		if ($ContratoTramite->Write()) {
			$this->outputJson($ContratoTramite->fields);
		} else {
			$this->halt(__('Cant write the Included Errand'), 'InvalidIncludedErrand');
		}
	}

	public function deleteErrandFromContract($contract_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		$included_errand_id = $Slim->request()->params('included_errand_id');

		$ContratoTramite = new ContratoTramite($Session);
		$ContratoTramite->Load($included_errand_id);

		if ($ContratoTramite->Delete()) {
			$this->outputJson(array('result' => 'OK'));
		} else {
			$this->halt(__('Cant delete the Included Errand'), 'CantDeleteIncludedErrand');
		}
	}

}
