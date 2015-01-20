<?

class InvoiceController extends AbstractController {
	
	public $helpers = array('EntitiesListator', array('\TTB\Html', 'Html'), 'Form', 'Paginator');

	public function feeAmountDetailTable() {
		$this->loadBusiness('Coining');
		$this->loadBusiness('Charging');
		$this->loadBusiness('Billing');
		$this->loadBusiness('Translating');

		$invoiceId = $this->data['invoice'] ? $this->data['invoice'] : $this->params['invoice'];
		$chargeId = $this->data['charge'] ? $this->data['charge'] : $this->params['charge'];
		$amount  = $this->data['amount'] > 0 ? $this->data['amount'] : $this->params['amount'];

		$charge = $this->ChargingBusiness->getCharge($chargeId);
		$currency = $this->CoiningBusiness->getCurrency($charge->get('opc_moneda_total'));
		$language = $this->TranslatingBusiness->getLanguageByCode('es');

		if (is_null($amount))  {
			$invoice = $this->BillingBusiness->getInvoice($invoiceId);
			$detail = $this->BillingBusiness->getFeesDataOfInvoiceByCharge($invoice, $charge, $currency);
		} else {
			$chargeDetail = $this->ChargingBusiness->getAmountDetailOfFees($charge, $currency);
			$chargeFees = $chargeDetail->get('saldo_honorarios');
			$chargeDiscount = $chargeDetail->get('descuento_honorarios');
			$detail = $this->BillingBusiness->getFeesDataOfInvoiceByAmounts($amount, $chargeFees, $chargeDiscount, $currency);
		}
		
		$this->set('feeDetiail', $detail);
		$this->set('currency', $currency);
		$this->set('language', $language);
		$response['detail'] = $this->renderTemplate('Charge/fee_discount_detail');
		
		$this->renderJSON($response);
	}

}