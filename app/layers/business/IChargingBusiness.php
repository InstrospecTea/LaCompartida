<?php

/**
* 
*/
interface IChargingBusiness extends BaseBusiness {
	
	/**
	 * Obtiene el detalle de las tarifas escalonadas asociadas al cobro.
	 * @param  number $chargeId Identificador del cobro
	 * @return array            Array que contiene las descripciones de las tarifas escalonadas.
	 */
	function getSlidingScales($chargeId);

}