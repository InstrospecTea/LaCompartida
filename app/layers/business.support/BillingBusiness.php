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
		$invoiceFees = $this->getInvoiceFeesAmount($invoice, $charge, $currency);
		$invoiceFeesData = $this->getFeesDataOfInvoiceByAmounts($invoiceFees, $chargeFees, $chargeDiscount, $currency);
	 	return $invoiceFeesData;
	}

	public function getFeesDataOfInvoiceByAmounts($invoiceFees, $chargeFees, $chargeDiscount, $currency) {
		$factor = $invoiceFees / $chargeFees;
		$invoiceDiscount = $factor * $chargeDiscount;
		$subtotalFees = $invoiceFees + $invoiceDiscount;

		$amountDetail = new GenericModel();
		$amountDetail->set('subtotal_honorarios', $subtotalFees, false);
	 	$amountDetail->set('descuento_honorarios', $chargeDiscount, false);
	 	$amountDetail->set('saldo_honorarios', $invoiceFees, false);

	 	return $amountDetail;
	}

	public function getInvoiceFeesAmount(Invoice $invoice, Charge $charge, Currency $currency) {
		$this->loadBusiness('Searching');
		$this->loadBusiness('Coining');
   		$searchCriteria = new SearchCriteria('Invoice');
   		$searchCriteria->related_with('InvoiceCharge');
   		$searchCriteria->filter($invoice->getIdentity())->restricted_by('equals')->compare_with($invoice->get($invoice->getIdentity()));
   		$searchCriteria->filter('id_cobro')->restricted_by('equals')->compare_with($charge->get($charge->getIdentity()))->for_entity('InvoiceCharge');
		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);
		$ingreso = 0;
		$egreso = 0;
		$saldo_honorarios = 0;
		foreach ($results as $invoice) {
			$invoiceCurrency = $this->CoiningBusiness->getCurrency($invoice->get('id_moneda'));
			$total = $this->CoiningBusiness->changeCurrency($invoice->get('honorarios'), $invoiceCurrency, $currency);
			if ($invoice->get('id_documento_legal') != 2) { //:O
				$ingreso += $total;
			} else {
				$egreso += $total;
			}
		}
		return ($ingreso - $egreso);
	}

}