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

		$invoice = $this->BillingBusiness->getInvoice($invoiceId);
		$charge = $this->ChargingBusiness->getCharge($chargeId);
		$currency = $this->CoiningBusiness->getCurrency($charge->get('opc_moneda_total'));
		$language = $this->TranslatingBusiness->getLanguageByCode('es');

		$detail = $this->BillingBusiness->getBilledFeesAmount($invoice, $charge, $currency);
		
		$this->set('feeDetiail', $detail);
		$this->set('currency', $currency);
		$this->set('language', $language);
		$response['detail'] = $this->renderTemplate('Charge/fee_discount_detail');
		
		$this->renderJSON($response);
	}

}