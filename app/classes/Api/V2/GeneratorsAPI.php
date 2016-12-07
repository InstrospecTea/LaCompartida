<?php

namespace Api\V2;

/**
 * Clase con métodos para Generador (Contratos)
 */
class GeneratorsAPI extends AbstractSlimAPI {

	public function updateAgreementGenerator($agreement_id, $generator_id) {
		$this->validateAuthTokenSendByHeaders();

		if (empty($agreement_id) || !is_numeric($agreement_id)) {
			$this->halt(__('Invalid agreement ID'), 'InvalidAgreementId');
		}

		if (empty($generator_id) || !is_numeric($generator_id)) {
			$this->halt(__('Invalid generator ID'), 'InvalidGeneratorId');
		}

		$generator = [];
		$params = $this->params;

		$generator['percent_generator'] = $params['percent_generator'];
		$generator['category_id'] = $params['category_id'];
		$generator['user_id'] = $params['user_id'];

		$Generator = new \GeneratorManager($this->session);
		$Generator->updateAgreementGenerator($generator, $generator_id);
	}

	public function createAgreementGenerator($agreement_id) {
		$this->validateAuthTokenSendByHeaders();

		if (is_null($agreement_id) || empty($agreement_id)) {
			$this->halt(__('Invalid agreement ID'), 'InvalidAgreementId');
		}

		$generator = [];
		$params = $this->params;

		$generator['percent_generator'] = $params['percent_generator'];
		$generator['user_id'] = $params['user_id'];
		$generator['category_id'] = $params['category_id'];
		$generator['client_id'] = $params['client_id'];
		$generator['agreement_id'] = $agreement_id;

		$Generator = new \GeneratorManager($this->session);
		$Generator->createAgreementGenerator($generator);
	}

	public function deleteAgreementGenerator($agreement_id, $generator_id) {
		$this->validateAuthTokenSendByHeaders();

		if (is_null($agreement_id) || empty($agreement_id)) {
			$this->halt(__('Invalid agreement ID'), 'InvalidAgreementId');
		}

		if (is_null($generator_id) || empty($generator_id)) {
			$this->halt(__('Invalid generator ID'), 'InvalidGeneratorId');
		}

		$Generator = new \GeneratorManager($this->session);
		$result = $Generator->deleteAgreementGenerator($generator_id);

		outputJson($result);
	}
}
