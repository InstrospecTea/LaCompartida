<?php

/**
* 
*/
interface IChargingBusiness extends BaseBusiness {

	/**
	 * Obtiene una instancia de {@link Charge} en base a su identificador primario.
	 * @param $chargeId
	 * @return Charge
	 */
	public function getCharge($chargeId);

	/**
	 * Obtiene una instancia de {@link Document} en base a una instancia de {@link Charge}
	 * @param $charge
	 * @return Document
	 */
	public function getChargeDocument(Charge $charge);

	/**
	 * Obtiene el detalle de las tarifas escalonadas asociadas al cobro.
	 * @param  number $chargeId Identificador del cobro
	 * @param  string $languageCode
	 * @return array            Array que contiene las descripciones de las tarifas escalonadas.
	 */
	public function getSlidingScales($chargeId, $languageCode);

	public function getSlidingScalesDetailTable(array $slidingScales, $language, $currency);


	/**
	 * Obtiene un detalle del monto de honorarios de la liquidación
	 *
	 * @param  charge Es una instancia de {@link Charge} de la que se quiere obtener la información.
	 * @param  currency Es una instancia de {@link Currency} para obtener los datos en moneda específica.
	 * @return GenericModel  
	 * 
	 * [
	 *   	subtotal_honorarios 	=> valor
	 *		descuento 				=> valor
	 *		neto_honorarios			=> valor
	 * ]
	 * 
	 */
	function getAmountDetailOfFees(Charge $charge, Currency $currency);


}
