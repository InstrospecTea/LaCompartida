<?php

/**
 *
 * Clase con métodos para Generadores de Contratos
 *
 */
class ContractsGeneratorsAPI extends AbstractSlimAPI {

	public function getGenerators($client_id, $contract_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($client_id) || empty($client_id)) {
			$this->halt(__('Invalid client ID'), 'InvalidClientId');
		}

		if (is_null($contract_id) || empty($contract_id)) {
			$this->halt(__('Invalid contract ID'), 'InvalidContractId');
		}

		$generators = Contrato::contractGenerators($Session, $contract_id);
		$this->outputJson($generators);
	}

	public function updateGenerator($client_id, $contract_id, $generator_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($client_id) || empty($client_id)) {
			$this->halt(__('Invalid client ID'), 'InvalidClientId');
		}

		if (is_null($contract_id) || empty($contract_id)) {
			$this->halt(__('Invalid contract ID'), 'InvalidContractId');
		}

		if (is_null($generator_id) || empty($generator_id)) {
			$this->halt(__('Invalid generator ID'), 'InvalidGeneratorId');
		}

		$percent_generator = $Slim->request()->params('percent_generator');
		$generator = Contrato::updateContractGenerator($Session, $generator_id, $percent_generator);

		$this->outputJson($generator);
	}

	public function deleteGenerator($client_id, $contract_id, $generator_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($client_id) || empty($client_id)) {
			$this->halt(__('Invalid client ID'), 'InvalidClientId');
		}

		if (is_null($contract_id) || empty($contract_id)) {
			$this->halt(__('Invalid contract ID'), 'InvalidContractId');
		}

		if (is_null($generator_id) || empty($generator_id)) {
			$this->halt(__('Invalid generator ID'), 'InvalidGeneratorId');
		}

		Contrato::deleteContractGenerator($Session, $generator_id);

		$this->outputJson(array('result' => 'OK'));
	}

	public function createGenerator($client_id, $contract_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($client_id) || empty($client_id)) {
			$this->halt(__('Invalid client ID'), 'InvalidClientId');
		}

		if (is_null($contract_id) || empty($contract_id)) {
			$this->halt(__('Invalid contract ID'), 'InvalidContractId');
		}

		$percent_generator = $Slim->request()->params('percent_generator');
		$user_id = $Slim->request()->params('user_id');

		$generator = Contrato::createContractGenerator($Session, $client_id, $contract_id, $user_id, $percent_generator);

		$this->outputJson($generator);
	}

}