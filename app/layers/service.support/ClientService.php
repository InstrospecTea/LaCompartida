<?php

class ClientService extends AbstractService implements IClientService {

	public function getDaoLayer() {
		return 'ClientDAO';
	}

	public function getClass() {
		return 'Client';
	}

	public function getByCode($client_code, $fields = null) {
		return $this->findFirst(CriteriaRestriction::equals('codigo_cliente', $client_code), $fields);
	}
}
