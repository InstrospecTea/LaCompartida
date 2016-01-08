<?php

class AgreementService extends AbstractService implements IAgreementService {

	public function getDaoLayer() {
		return 'AgreementDAO';
	}

	public function getClass() {
		return 'Agreement';
	}
}
