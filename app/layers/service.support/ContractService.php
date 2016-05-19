<?php

class ContractService extends AbstractService implements IContractService {

	public function getDaoLayer() {
		return 'ContractDAO';
	}

	public function getClass() {
		return 'Contract';
	}

}
