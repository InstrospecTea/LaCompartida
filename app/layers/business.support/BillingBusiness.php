<?php

class BillingBusiness extends AbstractBusiness implements IBillingBusiness {

	public function getInvoice($invoiceId) {
		$this->loadService('Invoice');
		return $this->InvoiceService->get($invoiceId);
	}

	/**
	* Función que carga con UNA sola query todos los invoices (facturas) de una sola vez
	* @param array $invoiceIds array de invoices id
	* @return map list de array, donde cada indice es el id del invoice pedido
	*/
	public function loadInvoices( $invoiceIds ) {
		$this->loadService('Invoice');
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Invoice');

		$searchCriteria
			->filter('id_factura')
			->restricted_by('in')
			->compare_with( $invoiceIds );

		$mapInvoices = array();
		$tmp = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		foreach ($tmp as $invoice) {
			$mapInvoices[ $invoice->get('id_factura') ] = $invoice;
		}

		return $mapInvoices;
	}

	public function getFeesDataOfInvoiceByCharge(Invoice $invoice, Charge $charge, Currency $currency) {
		$this->loadBusiness('Charging');

		$chargeData = $this->ChargingBusiness->getAmountDetailOfFees($charge, $currency);
		$chargeFees = $chargeData->get('saldo_honorarios');
		$chargeDiscount = $chargeData->get('descuento_honorarios');
		$invoiceFees = $this->getInvoiceFeesAmountInCurrency($invoice, $currency);
		$invoiceFeesData = $this->getFeesDataOfInvoiceByAmounts($invoiceFees, $chargeFees, $chargeDiscount, $currency);

		return $invoiceFeesData;
	}

	public function getFeesDataOfInvoiceByAmounts($invoiceFees, $chargeFees, $chargeDiscount, $currency) {
		$factor = 1;
		if ($chargeFees > 0) {
			$factor = $invoiceFees / $chargeFees;
		}
		$invoiceDiscount = $factor * $chargeDiscount;
		$subtotalFees = $invoiceFees + $invoiceDiscount;

		$amountDetail = new GenericModel();
		$amountDetail->set('subtotal_honorarios', $subtotalFees, false);
		$amountDetail->set('descuento_honorarios', $invoiceDiscount, false);
		$amountDetail->set('saldo_honorarios', $invoiceFees, false);

		return $amountDetail;
	}

	public function setInvoiceExchangeRates($invoiceId, $chargeId, $exchangeRatesParams = array()) {
		$exchangeRates = array();

		if (empty($exchangeRates)) {
			$exchangeRates = $this->getInvoiceExchangeRates($invoiceId, $chargeId);
		} else {
			$exchangeRates = $this->arrayToInvoiceExchangeRates($exchangeRatesParams);
		}


	}

	public function getInvoiceFeesAmountInCurrency(Invoice $invoice, Currency $currency) {
		$this->loadBusiness('Coining');
		return $this->CoiningBusiness->changeCurrency(
			$invoice->get('honorarios'),
			$this->CoiningBusiness->getCurrency(
				$invoice->get('id_moneda')
			),
			$currency);
	}

	public function getDefaultExchangeRatesByCharge($chargeId) {
		$this->loadBusiness('Charging');
		$this->loadBusiness('Coining');

		$Charge = $this->ChargingBusiness->getCharge($chargeId);

		$today = date_create('now');
		$charge_date = date_create($Charge->get('fecha_emision'));

		if ($today > $charge_date) {
			return $this->CoiningBusiness->getCurrencies();
		}

		return $this->ChargingBusiness->getDocumentExchangeRates($chargeId);
	}

	public function currenciesToInvoiceCurrencyArray($currencies) {
		$invoiceCurrencies = array();
		foreach ($currencies as $currency) {
			$invoiceCurrency = new InvoiceCurrency();
			$invoiceCurrency->set('id_moneda', $currency->get('id_moneda'));
			$invoiceCurrency->set('tipo_cambio', $currency->get('tipo_cambio'));
			$invoiceCurrency->set('glosa_moneda', $currency->get('glosa_moneda'));
			$invoiceCurrencies[] = $invoiceCurrency;
		}
		return $invoiceCurrencies;
	}

	public function getInvoiceExchangeRatesByInvoiceId($invoiceId) {
		$this->loadManager('Search');

		$SearchCriteria = new SearchCriteria('InvoiceCurrency');

		$SearchCriteria->related_with('Invoice')
			->with_direction('inner')
			->on_property('id_factura');

		$SearchCriteria->related_with('Currency')
			->with_direction('inner')
			->on_property('id_moneda');

		$SearchCriteria->filter('id_factura')
			->restricted_by('equals')->compare_with($invoiceId);

		$exchangeRates = (array) $this->SearchManager->searchByCriteria(
			$SearchCriteria,
			array('id_moneda', 'Currency.glosa_moneda', 'InvoiceCurrency.tipo_cambio')
		);

		return $exchangeRates;
	}

	public function getInvoiceExchangeRates($invoiceId, $chargeId) {
		$this->loadManager('Search');

		if (empty($invoiceId)) {
			return $this->currenciesToInvoiceCurrencyArray(
				$this->getDefaultExchangeRatesByCharge($chargeId)
			);
		}

		return $this->getInvoiceExchangeRatesByInvoiceId($invoiceId);
	}

}
