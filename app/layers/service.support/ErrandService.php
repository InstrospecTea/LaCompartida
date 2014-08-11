<?php
class ErrandService extends AbstractService implements IErrandService {

	public function getDaoLayer() {
		return 'ErrandDAO';
	}


	public function getClass() {
		return 'Errand';
	}

}