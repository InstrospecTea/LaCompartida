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

	/** 
	* Obitne una instancia de {@link GenericModel} con datos de honorarios de una Factura
	* 
	* @param $invoice instancia de {@link Invoice}  Factura a evaluar 
	* @param $charge instancia de {@link Charge} Liquidación a considerar para la factura
	* @param $currency instancia de {@link Currency} moneda en la que se devuelven los cálculos
	*
	* @return {@link GenericModel}
	*
	* 	{
	* 		subtotal_honorarios:  Monto bruto de honorarios de la factura
	* 		descuento_honorarios: Monto de Descuento en base al prorateo de la liquidación
	* 		saldo_honorarios: subtotal_honorarios - descuento_honorarios
	* 	}
	*/
	public function getFeesDataOfInvoiceByCharge(Invoice $invoice, Charge $charge, Currency $currency);

	/** 
	* Obitne una instancia de {@link GenericModel} con datos de honorarios de una Factura
	* 
	* @param $invoiceFees Monto de honorarios de la factura (monto neto descontado)
	* @param $chargeFees Monto de honorarios de la liquidación (monto neto descontado)
	* @param $chargeDiscount Descuento que se aplicó en la liquidación 
	* @param $currency instancia de {@link Currency} moneda en la que se devuelven los cálculos
	*
	* @return {@link GenericModel}
	*
	* 	{
	* 		subtotal_honorarios:  Monto bruto de honorarios de la factura
	* 		descuento_honorarios: Monto de Descuento en base al prorateo de la liquidación
	* 		saldo_honorarios: subtotal_honorarios - descuento_honorarios
	* 	}
	*/
	public function getFeesDataOfInvoiceByAmounts($invoiceFees, $chargeFees, $chargeDiscount, $currency);


	/** 
	* Obitne el monto de honorarios de una Factura en determinada moneda
	* 
	* @param $invoice instancia de {@link Invoice}  Factura a evaluar 
	* @param $currency instancia de {@link Currency} moneda en la que se devuelven los cálculos
	*
	* @return Number  monto de honorarios de la factura
	*/
	public function getInvoiceFeesAmountInCurrency(Invoice $invoice, Currency $currency);

}