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
	 * @return array            Array que contiene las descripciones de las tarifas escalonadas.
	 */
	function getSlidingScales($chargeId);

}