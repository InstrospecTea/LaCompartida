<?php

namespace Api\V2;

/**
 * Clase con métodos para Generador (Contratos)
 */
class GeneratorAPI extends AbstractSlimAPI {

	public function getContractGenerators($contract_id) {
		$this->validateAuthTokenSendByHeaders();

		if (empty($contract_id) || !is_numeric($contract_id)) {
			$this->halt(__('Invalid contract ID'), 'InvalidContractId');
		}

		$Generator = new \GeneratorManager($this->session);
		$result = $Generator->getContractGenerators($contract_id);

		$this->outputJson($result);
	}

	public function updateContractGenerator($contract_id, $generator_id) {
		$this->validateAuthTokenSendByHeaders();

		if (empty($contract_id) || !is_numeric($contract_id)) {
			$this->halt(__('Invalid contract ID'), 'InvalidContractId');
		}

		if (empty($generator_id) || !is_numeric($generator_id)) {
			$this->halt(__('Invalid generator ID'), 'InvalidGeneratorId');
		}

		$generator = [];
		$params = $this->params;

		$generator['percent_generator'] = $params['percent_generator'];
		$generator['category_id'] = $params['category_id'];

		$Generator = new \GeneratorManager($this->session);
		$Generator->updateContractGenerator($generator, $generator_id);
	}

	public function createContractGenerator($contract_id) {
		$this->validateAuthTokenSendByHeaders();

		if (is_null($contract_id) || empty($contract_id)) {
			$this->halt(__('Invalid contract ID'), 'InvalidContractId');
		}

		$generator = [];
		$params = $this->params;

		$generator['percent_generator'] = $params['percent_generator'];
		$generator['user_id'] = $params['user_id'];
		$generator['category_id'] = $params['category_id'];
		$generator['client_id'] = $params['client_id'];
		$generator['contract_id'] = $contract_id;

		$Generator = new \GeneratorManager($this->session);
		$Generator->createContractGenerator($generator);
	}

	public function deleteContractGenerator($contract_id, $generator_id) {
		$this->validateAuthTokenSendByHeaders();

		if (is_null($contract_id) || empty($contract_id)) {
			$this->halt(__('Invalid contract ID'), 'InvalidContractId');
		}

		if (is_null($generator_id) || empty($generator_id)) {
			$this->halt(__('Invalid generator ID'), 'InvalidGeneratorId');
		}

		$Generator = new \GeneratorManager($this->session);
		$result = $Generator->deleteContractGenerator($generator_id);

		outputJson($result);
	}
}
