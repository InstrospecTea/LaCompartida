<?php

/**
* 
*/
interface IBillingBusiness extends BaseBusiness {

	/*
	* Obtiene una instancia de {@link Invoice} en base a su identificador primario.
	* @param $invoiceId
	* @return Charge
	*/
	public function getInvoice($invoiceId);

}
