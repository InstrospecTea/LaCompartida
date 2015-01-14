<?php

class ChargeController extends AbstractController {

	public function slidingScaleDetail($chargeId) {
		//$chargeId = $this->data['chargeId'];
		$this->loadBusiness('Charging');
		$slidingScales = $this->ChargingBusiness->getSlidingScales($chargeId);
		$charge = $this->ChargingBusiness->getCharge($chargeId);
		$response['detail'] = $this->ChargingBusiness->getSlidingScalesDetailTable($slidingScales);
		$this->renderJSON($response);
	}

}