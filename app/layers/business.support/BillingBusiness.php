<?php

class BillingBusiness extends AbstractBusiness implements IBillingBusiness {

	public function getInvoice($invoiceId) {
		$this->loadService('Invoice');
		return $this->InvoiceService->get($invoiceId);
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