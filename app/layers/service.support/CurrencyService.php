<?php

class CurrencyService extends AbstractService implements ICurrencyService {

	public function getDaoLayer() {
		return 'CurrencyDAO';
	}

	public function getClass() {
		return 'Currency';
	}

}
