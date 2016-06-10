<?php
class ErrandRateService extends AbstractService implements IErrandRateService {

	public function getDaoLayer() {
		return 'ErrandRateDAO';
	}


	public function getClass() {
		return 'ErrandRate';
	}

}
