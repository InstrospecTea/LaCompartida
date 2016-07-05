<?php
class FeeService extends AbstractService implements IFeeService {

	public function getDaoLayer() {
		return 'FeeDAO';
	}


	public function getClass() {
		return 'Fee';
	}

}
