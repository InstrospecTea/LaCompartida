<?php
class ChargeService extends AbstractService implements IChargeService {
	public function getDaoLayer() {
		return 'ChargeDAO';
	}

	public function getClass() {
		return 'Charge';
	}

	public function saveOrUpdate($charge, $writeLog) {
		return parent::saveOrUpdate($charge, $writeLog);
	}

}
