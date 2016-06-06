<?php
/**
 * ClientDAO
 * Description:
 *
 */
class PaymentDAO extends AbstractDAO implements IPaymentDAO {

	public function getClass() {
		return 'Payment';
	}

}
