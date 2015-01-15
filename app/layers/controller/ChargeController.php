<?php

class ChargeController extends AbstractController {

	public function slidingScaleDetail() {
		$chargeId = $this->data['charge'];
		$languageCode = $this->data['language'];
		$this->loadBusiness('Charging');
		$this->loadBusiness('Coining');
		$this->loadBusiness('Translating');
		$charge = $this->ChargingBusiness->getCharge($chargeId);
		$currency = $this->CoiningBusiness->getCurrency($charge->get('id_moneda'));
		$language = $this->TranslatingBusiness->getLanguageByCode($languageCode);
		$slidingScales = $this->ChargingBusiness->getSlidingScales($chargeId, $languageCode);
		$response['detail'] = $this->ChargingBusiness->getSlidingScalesDetailTable($slidingScales, $currency, $language);
		$this->renderJSON($response);
	}

}