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

	/**
	 * Obtiene la instancia de {@link Charge} asociada al identificador $id.
	 * @param $id
	 * @return mixed
	 */
	function getCharge($id);

	/**
	 * Obtiene un detalle del monto de honorarios de la liquidación
	 *
	 * @param  charge Es una instancia de {@link Charge} de la que se quiere obtener la información.
	 * @return GenericModel  
	 * 
	 * [
	 *   	subtotal_honorarios 	=> valor
	 *		descuento 				=> valor
	 *		neto_honorarios			=> valor
	 * ]
	 * 
	 */
	function getAmountDetailOfFees(Charge $charge);


}
