<?php

/**
* 
*/
class ChargingBusiness extends AbstractBusiness implements IChargingBusiness {

	const CALCULATION_TYPE_OLD = 0;
	const CALCULATION_TYPE_NEW = 1;

	/**
	 * Obtiene una instancia de {@link Charge} en base a su identificador primario.
	 * @param $chargeId
	 * @return Charge
	 */
	public function getCharge($chargeId) {
		$this->loadService('Charge');
		return $this->ChargeService->get($chargeId);
	}

	/**
	 * Obtiene una instancia de {@link Document} en base a una instancia de {@link Charge}
	 * @param $charge
	 * @return Document
	 */
	public function getChargeDocument(Charge $charge) {
		$this->loadBusiness('Searching');
		$searchCriteria = new SearchCriteria('Document');
		$searchCriteria->filter('id_cobro')->restricted_by('equals')->compare_with($charge->get($charge->getIdentity()));
		$results = $this->SearchingBusiness->searchbyCriteria($searchCriteria);
		return $results && count($result) > 0 ? $results[0] : null;
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


	public function getUserFee($userId, $feeId, $currencyId) {
		$searchCriteria = new SearchCriteria('UserFee');
		$searchCriteria->filter('id_usuario')->restricted_by('equals')->compare_with($userId);
		$searchCriteria->filter('id_moneda')->restricted_by('equals')->compare_with($currencyId);
		$searchCriteria->filter('id_tarifa')->restricted_by('equals')->compare_with($feeId);
		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchbyCriteria($searchCriteria);
		return $results[0];
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
	public function getAmountDetailOfFees(Charge $charge, Currency $currency) {
		$this->loadBusiness('Coining');
		$document = $this->getChargeDocument($charge);
		if (!empty($document)) {
			$documentCurrency = $this->CoiningBusiness->getCurrency($document->get('id_moneda'));
		} else {
			$documentCurrency = $currency;
		}

	 	$result = $this->processCharge($charge, $currency);
	 	
	 	if ($documentCurrency->get($documentCurrency->getIdentity()) != $currency->get($currency->getIdentity())) {
			$documentResult = $this->processCharge($charge, $currency);
		} else {
			$documentResult = $result;
		}

		$modalidad_calculo = $charge->get('modalidad_calculo');
	 	
	 	$subtotal_honorarios = 0;
	 	$descuento_honorarios = 0;
	 	$saldo_honorarios = 0;
	 	$saldo_disponible_trabajos = 0;
	 	$saldo_disponible_tramites = 0;
	 	$saldo_gastos_con_impuestos = 0;
	 	$saldo_gastos_sin_impuestos = 0;
	 	$monto_iva = 0;

	 	if ($modalidad_calculo == ChargingBusiness::CALCULATION_TYPE_NEW) {
	 		$subtotal_honorarios = $result['subtotal_honorarios'];
			$descuento_honorarios = $result['descuento_honorarios'];
			$saldo_honorarios = $subtotal_honorarios - $descuento_honorarios;
			$saldo_disponible_trabajos = $saldo_trabajos = $result['monto_trabajos'] - $descuento_honorarios;
			if ($saldo_disponible_trabajos < 0) {
				$saldo_disponible_tramites = $saldo_tramites = $result['monto_tramites'] + $saldo_disponible_trabajos;
				$saldo_disponible_trabajos = 0;
			} else {
				$saldo_disponible_tramites = $saldo_tramites = $result['monto_tramites'];
			}
		}
 		//Código que debería estar obsoleto
		if ($modalidad_calculo == ChargingBusiness::CALCULATION_TYPE_OLD) {
			$chargeCurrency = $this->CoiningBusiness->getCurrency($charge->get('id_moneda'));
			$chargeCurrency = $this->CoiningBusiness->setCurrencyAmountByCharge($chargeCurrency, $charge);
			$currency = $this->CoiningBusiness->setCurrencyAmountByCharge($currency, $charge);
			$descuento_honorarios = $charge->get('descuento');
			if ($charge->get('porcentaje_impuesto') > 0) {
				$honorarios_original = $charge->get('monto_subtotal') - $descuento_honorarios;
			} else {
				$honorarios_original = $charge->get('monto_subtotal');
			}

			$saldo_honorarios = $this->CoiningBusiness->changeCurrency($honorarios_original, $chargeCurrency, $currency);

			//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
			if ((($charge->get('total_minutos') / 60) < $charge->get('retainer_horas')) 
				&& ($charge->get('forma_cobro') == 'RETAINER' 
					|| $charge->get('forma_cobro') == 'PROPORCIONAL')
				&& $charge->get('id_moneda') != $charge->get('id_moneda_monto')) {
				$saldo_honorarios = $this->CoiningBusiness->changeCurrency($honorarios_original, $chargeCurrency, $currency); 
			}

			//Caso flat fee
			$monto_tramites = $charge->get('monto_tramites');
			if ($charge->get('forma_cobro') == 'FLAT FEE'
				 && $charge->get('id_moneda') != $charge->get('id_moneda_monto')
				 && $charge->get('id_moneda_monto') == $charge->get('opc_moneda_total')
				 && empty($descuento_honorarios) && empty($monto_tramites)) {
				$saldo_honorarios = $charge->get('monto_contrato');
			}
			$saldo_honorarios = $this->CoiningBusiness->changeCurrency($saldo_honorarios, $currency, $currency);
			$subtotal_honorarios = $saldo_honorarios + $descuento_honorarios;
		}

		if ($saldo_honorarios < 0) {
			$saldo_honorarios = 0;
		}

		$saldo_gastos_con_impuestos = $documentResult['subtotal_gastos'] - $documentResult['subtotal_gastos_sin_impuesto'];
		if ($saldo_gastos_con_impuestos < 0) {
			$saldo_gastos_con_impuestos = 0;
		}

		$saldo_gastos_sin_impuestos = $documentResult['subtotal_gastos_sin_impuesto'];
		if ($saldo_gastos_sin_impuestos < 0) {
			$saldo_gastos_sin_impuestos = 0;
		}

		if ($charge->get('porcentaje_impuesto') > 0 || $charge->get('porcentaje_impuesto_gastos') > 0) {
			$monto_iva = $documentResult['monto_iva'];
		} else {
			$monto_iva = 0;
		}

		$amountDetail = new GenericModel();
		$amountDetail->set('subtotal_honorarios', $subtotal_honorarios, false);
	 	$amountDetail->set('descuento_honorarios', $descuento_honorarios, false);
	 	$amountDetail->set('saldo_honorarios', $saldo_honorarios, false);
	 	$amountDetail->set('saldo_disponible_trabajos', $saldo_disponible_trabajos, false);
	 	$amountDetail->set('saldo_disponible_tramites', $saldo_disponible_tramites, false);
	 	$amountDetail->set('saldo_gastos_con_impuestos', $saldo_gastos_con_impuestos, false);
	 	$amountDetail->set('saldo_gastos_sin_impuestos', $saldo_gastos_sin_impuestos, false);
	 	$amountDetail->set('monto_iva', $monto_iva, false);

		return $amountDetail;
	}

	public function getAmountDetailOfFeesTable($detail, $currency, $language) {
		$listator = new EntitiesListator();

		$fees = new GenericModel();
		$fees->set('title', __('Subtotal Honorarios'), false);
		$fees->set('amount', $detail->get('subtotal_honorarios'), false);
		
		$discount = new GenericModel();
		$discount->set('title', __('Descuento'), false);
		$discount->set('amount', $detail->get('descuento_honorarios'), false);

		$total = new GenericModel();
		$total->set('title', __('Total'), false);
		$total->set('amount', $detail->get('saldo_honorarios'), false);
		
		$listator->loadEntities(array($fees, $discount, $total));
		$listator->setNumberFormatOptions($currency, $language);
		$listator->addColumn('Detalle', 'title');
		$listator->addColumn('Monto', 'amount');
		return $listator->render();
	}

	public function getBilledAmount(Charge $charge, Currency $currency) {
		$this->loadBusiness('Searching');
		$this->loadBusiness('Coining');
		
   		$searchCriteria = new SearchCriteria('Invoice');
   		$searchCriteria->related_with('InvoiceCharge');
   		$searchCriteria->filter('id_estado')->restricted_by('not_in')->compare_with(array(3, 5));
   		$searchCriteria->filter('id_cobro')->restricted_by('equals')->compare_with($charge->get($charge->getIdentity()))->for_entity('InvoiceCharge');
		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		$ingreso = 0;
		$egreso = 0;
		$monto_facturado = 0;
		foreach ($results as $invoice) {
			$invoiceCurrency = $this->CoiningBusiness->getCurrency($invoice->get('id_moneda'));
			$total = $this->CoiningBusiness->changeCurrency($invoice->get('total'), $invoiceCurrency, $currency);
			if ($invoice->get('id_documento_legal') != 2) { //:O
				$ingreso += $total;
			} else {
				$egreso += $total;
			}
		}
		$monto_facturado = $ingreso - $egreso;
		return $monto_facturado;
	}

	private function processCharge(Charge $charge, Currency $currency) {
		$currency_id = $currency->get($currency->getIdentity());
		$charge_id = $charge->get($charge->getIdentity());
		$result = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $charge_id);
		$process = array();
		foreach ($result as $key => $value) {
			$process[$key] = $value[$currency_id];
		}
		return $process;
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
			if (count($works)) {
				$scale->set('amount', $result['scaleAmount'], false);
			}
			$scale->set('chargeCurrency', $charge->get('id_moneda'));
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
