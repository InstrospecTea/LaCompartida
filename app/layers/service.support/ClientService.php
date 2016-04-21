<?php

class ClientService extends AbstractService implements IClientService {

	public function getDaoLayer() {
		return 'ClientDAO';
	}

	public function getClass() {
		return 'Client';
	}

}
