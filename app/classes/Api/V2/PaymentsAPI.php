<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para Projectos
 *
 */
class PaymentsAPI extends AbstractSlimAPI {

	static $PaymentsEntity = array(
		array('id' => 'id_documento_neteo'),
		array('project_id' => 'id_asunto'),
		array('project_code' => 'codigo_asunto'),
		array('name' => 'glosa_documento'),
		array('date' => 'fecha_creacion')
	);

	public function getPaymentsOfMatter($matter_id) {
		$this->validateAuthTokenSendByHeaders();

		$PaymentManager = new \PaymentManager($this->session);
		$results = $PaymentManager->getPaymentsOfMatter($matter_id);

		$this->present($results, self::$PaymentsEntity);
	}
}
