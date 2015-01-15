<?php

/**
* 
*/
class ChargingBusiness extends AbstractBusiness implements IChargingBusiness {

	/**
	 * Obtiene una instancia de {@link Charge} en base a su identificador primario.
	 * @param $chargeId
	 * @return Charge
	 */
	public function getCharge($chargeId) {
		$this->loadService('Charge');
		return $this->ChargeService->get($chargeId);
	}

	public function getUserFee($userId, $feeId, $currencyId) {
		$searchCriteria = new SearchCriteria('UserFee');
		$searchCriteria->filter('id_usuario')->restricted_by('equals')->compare_with($userId);
		$searchCriteria->filter('id_moneda')->restricted_by('equals')->compare_with($currencyId);
		$searchCriteria->filter('id_tarifa')->restricted_by('equals')->compare_with($feeId);
		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchbyCriteria($searchCriteria);
		return $results[0];
	}

	public function getSlidingScalesDetailTable(array $slidingScales, $currency, $language) {
		$listator = new EntitiesListator();
		$listator->loadEntities($slidingScales);
		$listator->setNumberFormatOptions($currency, $language);
		$listator->addColumn('#', 'scale_number');
		$listator->addColumn('Monto Bruto', 'amount');
		$listator->addColumn('% Descuento', 'discountRate');
		$listator->addColumn('Descuento', 'discount');
		$listator->addColumn('Monto Neto', 'netAmount');
		$listator->totalizeFields(array('Monto Neto'));
		return $listator->render();
	}

	public function getSlidingScales($chargeId, $languageCode) {
		$this->loadService('Charge');
		$this->loadBusiness('Working');
		$this->loadBusiness('Translating');
		$charge = $this->ChargeService->get($chargeId);
		$language = $this->TranslatingBusiness->getLanguageByCode($languageCode);
		$slidingScales = $this->constructScaleObjects($charge);
		$works = $this->WorkingBusiness->getWorksByCharge($chargeId);
		$works = $works->toArray();
		$slidingScales = $this->processSlidngScales($works, $slidingScales, $charge);
		$slidingScales = $this->processSlidingScalesDiscount($slidingScales);
		$slidingScales = $this->proceessSlidingScalesLanguages($slidingScales, $language);
		return $slidingScales;
	}

	private function getWorkedHours(Work $work) {
		$workTimeDetail = explode(':',$work->get('duracion_cobrada'));
		$minutes = 0;
		$minutes += $workTimeDetail[0] * 60;
		$minutes += $workTimeDetail[1];
		return $minutes/60;
	}

	/**
	 * Obtiene la instancia de {@link Charge} asociada al identificador $id.
	 * @param $id
	 * @return mixed
	 */
	function getCharge($id) {
		$this->loadService('Charge');
		return $this->ChargeService->get($id);
	}

	/**
	 * Obtiene un detalle del monto de honorarios de la liquidación
	 *
	 * @param  charge Es una instancia de {@link Charge} de la que se quiere obtener la información.
	 * @return GenericModel  
	 * 
	 * [
	 *   	subtotal_honorarios 	=> valor
	 *		descuento 				=> valor
	 *		neto_honorarios			=> valor
	 * ]
	 * 
	 */
	function getAmountDetailOfFees(Charge $charge) {
		$charge_id = $charge->get($charge->getIdentity());
	 	$result = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $charge_id, array(), 0, true);
	 	$subtotal_honorarios = $result['subtotal_honorarios'][$charge->get('opc_moneda_total')];
	 	$descuento = $result['descuento_honorarios'][$charge->get('opc_moneda_total')];
	 	$neto_honorarios = $subtotal_honorarios - $descuento;
	 	
		$detail = new GenericModel();
		$detail->set('subtotal_honorarios', $subtotal_honorarios);
		$detail->set('descuento', $descuento);
		$detail->set('neto_honorarios', $neto_honorarios);

		return $detail;
	}


	private function getTotalWorkedHours(array $works) {
		$minutes = 0;
		for ($i = 0; $i < count($works); $i++) {
			$work = $works[$i];
			$minutes += $this->getWorkedHours($work);
		}
		return $minutes/60;
	}

	private function getSlidingScale(Charge $charge, $scaleNumber) {
		$slidingScale = new GenericModel();
		$scaleLabel = "esc$scaleNumber";
		$slidingScale->set('scaleLabel', $scaleLabel, false);
		$slidingScale->set('scale_number', $scaleNumber, false);
		$slidingScale->set('discountRate', $charge->get("{$scaleLabel}_descuento"), false);
		$slidingScale->set('hours', $charge->get("{$scaleLabel}_tiempo"), false);
		$slidingScale->set('feeId', $charge->get("{$scaleLabel}_id_tarifa"), false);
		$slidingScale->set('currencyId', $charge->get("{$scaleLabel}_id_moneda"), false);
		return $slidingScale;
	}

	public function constructScaleObjects($charge) {
		$slidingScales = array();
		$slidingScales[] = $this->getSlidingScale($charge, 1);
		$slidingScales[] = $this->getSlidingScale($charge, 2);
		$slidingScales[] = $this->getSlidingScale($charge, 3);
		$slidingScales[] = $this->getSlidingScale($charge, 4);
		return $slidingScales;
	}

	private function processSlidngScales($works, $slidingScales, $charge) {
		foreach ($slidingScales as $scale) {
			$result = $this->slidingScaleTimeCalculation($works, $scale, $charge);
			$works = $result['works'];
			$scale->set('amount', $result['scaleAmount'], false);
			$scale->set('chargeCurrency', $charge->get('id_moneda'));
			if (!count($works)) {
				break;
			}
		}
		return $slidingScales;
	}

	private function processSlidingScalesDiscount($slidingScales) {
		foreach ($slidingScales as $slidingScale) {
			$slidingScale = $this->processSlidingScaleDiscount($slidingScale);
		}
		return $slidingScales;
	}

	private function processSlidingScaleDiscount($slidingScale) {
		if (!is_null($slidingScale->get('amount'))) {
			$amount = $slidingScale->get('amount');
			$slidingScale->set('discount',
				$amount * ($slidingScale->get('discountRate') / 100),
				false
			);
			$slidingScale->set('netAmount',
				$amount - $slidingScale->get('discount'),
				false
			);
		}
		return $slidingScale;
	}

	private function proceessSlidingScalesLanguages($slidingScales, $language) {
		foreach ($slidingScales as $slidingScale) {
			$slidingScale = $this->proceessSlidingScaleLanguages($slidingScale, $language);
		}
		return $slidingScales;
	}

	private function proceessSlidingScaleLanguages($slidingScale, $language) {
		$this->loadBusiness('Coining');
		$slidingScale->set('amount',
			$this->CoiningBusiness->formatAmount(
				$slidingScale->get('amount'), 
				$this->CoiningBusiness->getCurrency($slidingScale->get('chargeCurrency')),
				$language
			)
		);
		$slidingScale->set('discount',
			$this->CoiningBusiness->formatAmount(
				$slidingScale->get('discount'), 
				$this->CoiningBusiness->getCurrency($slidingScale->get('chargeCurrency')),
				$language
			)
		);
		$slidingScale->set('netAmount',
			$this->CoiningBusiness->formatAmount(
				$slidingScale->get('netAmount'), 
				$this->CoiningBusiness->getCurrency($slidingScale->get('chargeCurrency')),
				$language
			)
		);
		return $slidingScale;
	}

	private function slidingScaleTimeCalculation($works, $scale, $charge, $scaleAmount = 0) {
		$this->loadBusiness('Coining');
		$remainingScaleHours = $scale->get('hours');
		for ($work = array_shift($works); !empty($work); $work = array_shift($works)) {
			//Tomo las horas del trabajo de las horas restantes, si el trabajo ya fue usado para llenar un escalón,
			// o de las horas trabajadas, si es primera vez que se utiliza el trabajo para llenar el escalón.
			if ($work->get('remainingHours')) {
				$workedHours = $work->get('remainingHours');
			} else {
				$workedHours = $this->getWorkedHours($work);
			}
			//Si es el último escalón, entonces se utilizan todas las horas, por lo que el valor debe restarse
			// completamente.
			if ($scale->get('scale_number') == 4) {
				$remainingScaleHours = $workedHours;
			}
			$remainingWorkHours = $workedHours - $remainingScaleHours;
			$remainingScaleHours = $remainingScaleHours - $workedHours;
			if ($remainingWorkHours <= 0) {
				//Se acabaron las horas del trabajo al intentar llenar la bolsa de horas del escalón.
				//Transformar las horas en dinero
				$userFee = $this->getUserFee($work->get('id_usuario'), $scale->get('feeId'), $scale->get('currencyId'));
				//Cambio de moneda del monto
				$chargeCurrency = $this->CoiningBusiness->getCurrency($charge->get('id_moneda'));
				$scaleCurrency = $this->CoiningBusiness->getCurrency($scale->get('currencyId'));
				$chargeCurrency = $this->CoiningBusiness->setCurrencyAmountByCharge($chargeCurrency, $charge);
				$amount = $workedHours * $userFee->get('tarifa');
				$scaleAmount += $this->CoiningBusiness->changeCurrency($amount, $scaleCurrency, $chargeCurrency);
				if ($remainingScaleHours == 0) {
					// El trabajo se acabó y además se llenó la bolsa del escalón. Hay que cambiar el escalón.
					return array('works' => $works, 'scaleAmount' => $scaleAmount);
				} else {
					//Aun hay horas en el escalón. Hay que cambiar el trabajo.
					continue;
				}
			} else {
				//El trabajo aun tiene horas y la bolsa de horas del escalón ya se llenó. Hay que cambiar el escalón.
				$userFee = $this->getUserFee($work->get('id_usuario'), $scale->get('feeId'), $scale->get('currencyId'));
				//Cambio de moneda del monto
				$chargeCurrency = $this->CoiningBusiness->getCurrency($charge->get('id_moneda'));
				$scaleCurrency = $this->CoiningBusiness->getCurrency($scale->get('currencyId'));
				$chargeCurrency = $this->CoiningBusiness->setCurrencyAmountByCharge($chargeCurrency, $charge);
				$amount = ($remainingScaleHours + $workedHours) * $userFee->get('tarifa');
				$scaleAmount += $this->CoiningBusiness->changeCurrency($amount, $scaleCurrency, $chargeCurrency);
				$work->set('remainingHours', $remainingWorkHours);
				array_unshift($works, $work);
				return array('works' => $works, 'scaleAmount' => $scaleAmount);
			}
		}
		return array('works' => $works, 'scaleAmount' => $scaleAmount);
	}





}
