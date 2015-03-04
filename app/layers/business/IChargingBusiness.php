<?php

interface IChargingBusiness  extends BaseBusiness {

	/**
	 * Elimina un cobro
	 * @param type $id_cobro
	 * @throw Exceptions
	 */
	function delete($id_cobro);

	/**
	 * Elimina el documento de un cobro.
	 * @param type $id_cobro
	 * @param type $estado
	 * @param type $hay_pagos
	 */
	function overrideDocument($id_cobro = null, $estado = 'CREADO', $hay_pagos = false);

	/**
	 * Verifica si existe un cobro segun su ID.
	 * @param int $id_cobro
	 * @return boolean
	 */
	public function doesChargeExists($id_cobro);

	/**
	 * Obtiene una instancia de {@link Document} en base a una instancia de {@link Charge}
	 * @param $charge
	 * @return Document
	 */
	public function getChargeDocument(Charge $charge);

	/**
	 * Obtiene el detalle de las tarifas escalonadas asociadas al cobro.
	 * @param  number $chargeId Identificador del cobro
	 * @return array            Array que contiene las descripciones de las tarifas escalonadas.
	 */
	public function getSlidingScales($chargeId);

	/**
	 * 
	 * @param  array  $slidingScales [description]
	 * @param  Language $language      [description]
	 * @param  Currency $currency      [description]
	 * @return string               [description]
	 */
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
