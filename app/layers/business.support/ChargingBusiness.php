<?php

/**
* 
*/
class ChargingBusiness extends AbstractBusiness implements IChargingBusiness {
	
	public function getUserFee($userId, $feeId, $currencyId) {
		$searchCriteria = new SearchCriteria('UserFee');
		$searchCriteria->filter('id_usuario')->restricted_by('equals')->compare_with($userId);
		$searchCriteria->filter('id_moneda')->restricted_by('equals')->compare_with($currencyId);
		$searchCriteria->filter('id_tarifa')->restricted_by('equals')->compare_with($feeId);
		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchbyCriteria($searchCriteria);
		return $results[0];
	}

	public function getSlidingScales($chargeId) {
		$this->loadService('Charge');
		$this->loadBusiness('Working');
		$charge = $this->ChargeService->get($chargeId);
		$slidingScales = $this->constructScaleObjects($charge);
		$works = $this->WorkingBusiness->getWorksByCharge($chargeId);
		$slidingScales = $this->processSlidngScales($works, $slidingScales, $charge);
		$slidingScales = $this->processSlidingScalesDiscount($slidingScales);
		pr($slidingScales);
	}

	private function getWorkedHours(Work $work) {
		$workTimeDetail = explode(':',$work->get('duracion_cobrada'));
		$minutes = 0;
		$minutes += $workTimeDetail[0] * 60;
		$minutes += $workTimeDetail[1];
		return $minutes/60;
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

	private function slidingScaleTimeCalculation($works, $scale, $charge, $scaleAmount = 0) {
		$this->loadBusiness('Coining');
		$remainingScaleHours = $scale->get('hours');
		echo '<br/>';
		pr('Scale:'.$scale->get('scaleLabel'));
		pr('Scale Hours:'.$remainingScaleHours);
		for ($work = array_shift($works); !empty($work); $work = array_shift($works)) {
			echo '<br/>';
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
			pr('Work:'.$work->get('id_trabajo'));
			pr('Worked Hours:'.$workedHours);
			pr('Remaining Work Hours After Scale Compensation:'. $remainingWorkHours);
			pr('Remaining Scale Hours After Work Compensation:'. $remainingScaleHours);
			if ($remainingWorkHours <= 0) {
				// pr('The work is left out of hours trying to compensate scale hours...');
				//Se acabaron las horas del trabajo al intentar llenar la bolsa de horas del escalón.
				//Transformar las horas en dinero
				$userFee = $this->getUserFee($work->get('id_usuario'), $scale->get('feeId'), $scale->get('currencyId'));
				// pr('User fee:'.$userFee);
				//Cambio de moneda del monto
				$chargeCurrency = $this->CoiningBusiness->getCurrency($charge->get('id_moneda'));
				$scaleCurrency = $this->CoiningBusiness->getCurrency($scale->get('currencyId'));
				$chargeCurrency = $this->CoiningBusiness->setCurrencyAmountByCharge($chargeCurrency, $charge);
				$amount = $workedHours * $userFee->get('tarifa');
				pr('Scale amount:'.$amount);
				pr('Scale currency:'.$scaleCurrency->get('glosa_moneda_plural'));
				pr('Charge currency:'.$chargeCurrency->get('glosa_moneda_plural'));
				pr('Exchange rate:'.$chargeCurrency->get('tipo_cambio'));
				$scaleAmount += $this->CoiningBusiness->changeCurrency($amount, $scaleCurrency, $chargeCurrency);
				pr('Accumulated amount:'.$scaleAmount);
				if ($remainingScaleHours == 0) {
					// El trabajo se acabó y además se llenó la bolsa del escalón. Hay que cambiar el escalón.
					pr('The scale is filled.');
					return array('works' => $works, 'scaleAmount' => $scaleAmount);
				} else {
					//Aun hay horas en el escalón. Hay que cambiar el trabajo.
					pr('Picking next work...');
					continue;
				}
			} else {
				pr('The work still has hours left, but the scale is already filled.');
				//El trabajo aun tiene horas y la bolsa de horas del escalón ya se llenó. Hay que cambiar el escalón.
				$userFee = $this->getUserFee($work->get('id_usuario'), $scale->get('feeId'), $scale->get('currencyId'));
				pr('User fee:'.$userFee);
				//Cambio de moneda del monto
				$chargeCurrency = $this->CoiningBusiness->getCurrency($charge->get('id_moneda'));
				$scaleCurrency = $this->CoiningBusiness->getCurrency($scale->get('currencyId'));
				$chargeCurrency = $this->CoiningBusiness->setCurrencyAmountByCharge($chargeCurrency, $charge);
				$amount = ($remainingScaleHours + $workedHours) * $userFee->get('tarifa');
				pr('Scale amount:'.$amount);
				pr('Scale currency:'.$scaleCurrency->get('glosa_moneda_plural'));
				pr('Charge currency:'.$chargeCurrency->get('glosa_moneda_plural'));
				pr('Exchange rate:'.$chargeCurrency->get('tipo_cambio'));
				$scaleAmount += $this->CoiningBusiness->changeCurrency($amount, $scaleCurrency, $chargeCurrency);
				pr('Accumulated amount:'.$scaleAmount);
				pr("The remaining work hours for next scale are $remainingWorkHours");
				$work->set('remainingHours', $remainingWorkHours);
				array_unshift($works, $work);
				return array('works' => $works, 'scaleAmount' => $scaleAmount);
			}
		}
		return array('works' => $works, 'scaleAmount' => $scaleAmount);
	}





}