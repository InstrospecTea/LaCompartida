<?php

class ChargeService extends AbstractService implements IChargeService {

	public function getDaoLayer() {
		return 'ChargeDAO';
	}

	public function getClass() {
		return 'Charge';
	}

	public function getAgreement($client_id) {
		$results = $dao->getByClient($clientId);
		foreach $results as $result) {
			//Crear un DTO de Charge por cada result y retornar
		}

	}
}
