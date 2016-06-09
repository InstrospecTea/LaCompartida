<?php
class ErrandRateService extends AbstractService {#} implements IErrandService {

	public function getDaoLayer() {
		return 'ErrandRateDAO';
	}


	public function getClass() {
		return 'ErrandRate';
	}

}
