<?php

class CurrencyChargeService extends AbstractService implements IBaseService {

	public function getDaoLayer() {
		return 'CurrencyChargeDAO';
	}

	public function getClass() {
		return 'CurrencyCharge';
	}

}
