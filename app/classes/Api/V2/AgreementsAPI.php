<?php

namespace Api\V2;

/**
 * Clase con métodos para Agreements (Contratos)
 */
class AgreementsAPI extends AbstractSlimAPI {

	public function getAgreementGenerators($agreement_id) {
		$this->validateAuthTokenSendByHeaders();

		$join = $this->params['joins'];
		if (empty($agreement_id) || !is_numeric($agreement_id)) {
			$this->halt(__('Invalid agreement ID'), 'InvalidAgreementId');
		}

		$Agreement = new \AgreementManager($this->session);
		$join = explode(',', $join);

		$result = $Agreement->getAgreementGenerators($agreement_id, $join);

		$this->outputJson($result);
	}
}
