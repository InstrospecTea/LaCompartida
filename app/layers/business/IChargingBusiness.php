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
	 * Obtiene el detalle de las tarifas escalonadas asociadas al cobro.
	 * @param  number $chargeId Identificador del cobro
	 * @param  string $languageCode
	 * @return array            Array que contiene las descripciones de las tarifas escalonadas.
	 */
	public function getSlidingScales($chargeId, $languageCode);

	public function getSlidingScalesDetailTable(array $slidingScales, $language, $currency);

}