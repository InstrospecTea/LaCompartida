<?php
class ErrandValueService extends AbstractService {#} implements IErrandService {

	public function getDaoLayer() {
		return 'ErrandValueDAO';
	}


	public function getClass() {
		return 'ErrandValue';
	}

}
