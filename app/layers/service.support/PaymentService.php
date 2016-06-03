<?php

class PaymentService extends AbstractService implements IPaymentService {

	public function getDaoLayer() {
		return 'PaymentDAO';
	}

	public function getClass() {
		return 'Payment';
	}

}
