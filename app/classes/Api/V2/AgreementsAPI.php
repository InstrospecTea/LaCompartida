<?php

namespace Api\V2;

/**
 * Clase con métodos para Agreements (Contratos)
 */
class AgreementsAPI extends AbstractSlimAPI {

	public function getAgreementGenerators($agreement_id) {
		$this->validateAuthTokenSendByHeaders();

		$embed = $this->params['embed'];

		if (empty($agreement_id) || !is_numeric($agreement_id)) {
			$this->halt(__('Invalid agreement ID'), 'InvalidAgreementId');
		}

		if (empty($embed)) {
			$this->halt(__('Invalid embed I need at least one'), 'InvalidEmbed');
		}

		$Agreement = new \AgreementManager($this->session);
		$embed = explode(',', $embed);

		$result = $Agreement->getAgreementGenerators($agreement_id, $embed);

		$this->outputJson($result);
	}
}
