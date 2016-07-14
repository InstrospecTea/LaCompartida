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

	public function getInvoiceFeesAmountInCurrency(Invoice $invoice, Currency $currency) {
		$this->loadBusiness('Coining');
		return $this->CoiningBusiness->changeCurrency(
			$invoice->get('honorarios'),
			$this->CoiningBusiness->getCurrency(
				$invoice->get('id_moneda')
			),
			$currency);
	}

	public function getDefaultInvoiceCurrenciesByCurrency() {
		$currencies = $this->CoiningBusiness->getCurrencies();
		$result = array();
		foreach ($currencies as $currency) {
			$result[] = array(
				'id_moneda' => $currency->get('id_moneda'),
				'glosa_moneda' => $currency->get('glosa_moneda'),
				'tipo_cambio' => $currency->get('tipo_cambio')
			);
		}
		return $result;
	}

	public function getDefaultInvoiceCurrenciesByCharge($chargeId) {
		$this->loadBusiness('Charging');
		$this->loadBusiness('Coining');

		$Charge = $this->ChargingBusiness->getCharge($chargeId);

		$today = date_create('now');
		$charge_date = date_create($Charge->get('fecha_emision'));

		if ($today > $charge_date) {
			return $this->getDefaultInvoiceCurrenciesByCurrency();
		}

		return $this->ChargingBusiness->getDocumentCurrencies($chargeId);
	}

	public function getInvoiceCurrencies($invoiceId, $chargeId) {
		return $this->getDefaultInvoiceCurrenciesByCharge($chargeId);

		if (empty($invoiceId)) {
			return $this->getDefaultInvoiceCurrenciesByCharge($chargeId);
		}

		$Criteria = new Criteria($this->Session);
		$Criteria
			->add_select('prm_moneda.id_moneda')
			->add_select('prm_moneda.glosa_moneda')
			->add_select('factura_moneda.tipo_cambio')
			->add_from('factura_moneda')
			->add_inner_join_with('factura',
				CriteriaRestriction::equals(
					'factura_moneda.id_factura',
					'factura.id_factura'
				)
			)
			->add_inner_join_with('prm_moneda',
				CriteriaRestriction::equals(
					'factura_moneda.id_moneda',
					'prm_moneda.id_moneda'
				)
			)
			->add_restriction(
				CriteriaRestriction::equals('factura.id_factura', $invoiceId)
			);

		return $Criteria->run();
	}

}
