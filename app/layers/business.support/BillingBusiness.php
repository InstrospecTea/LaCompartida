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

}
