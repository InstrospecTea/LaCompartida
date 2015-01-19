<?php

class BillingBusiness extends AbstractBusiness implements IBillingBusiness {

	public function getInvoice($invoiceId) {
		$this->loadService('Invoice');
		return $this->InvoiceService->get($invoiceId);
	}

	public function getBilledFeesAmount(Invoice $invoice, Charge $charge, Currency $currency) {
		$this->loadBusiness('Searching');
		$this->loadBusiness('Coining');
		$this->loadBusiness('Charging');

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
		$saldo_honorarios = $ingreso - $egreso;

		$chargeDetail = $this->ChargingBusiness->getAmountDetailOfFees($charge, $currency);
		$charge_saldo_honorarios = $chargeDetail->get('saldo_honorarios');
		$charge_descuento_honorarios  = $chargeDetail->get('descuento_honorarios');

		$factor = $saldo_honorarios / $charge_saldo_honorarios;
		$descuento_honorarios = $factor * $charge_descuento_honorarios;
		$subtotal_honorarios = $saldo_honorarios + $descuento_honorarios;

		$amountDetail = new GenericModel();
		$amountDetail->set('subtotal_honorarios', $subtotal_honorarios, false);
	 	$amountDetail->set('descuento_honorarios', $descuento_honorarios, false);
	 	$amountDetail->set('saldo_honorarios', $saldo_honorarios, false);

	 	return $amountDetail;
	}

}