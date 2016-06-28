<?php
class ErrandValueService extends AbstractService implements IErrandValueService {

	public function getDaoLayer() {
		return 'ErrandValueDAO';
	}


	public function getClass() {
		return 'ErrandValue';
	}

}
