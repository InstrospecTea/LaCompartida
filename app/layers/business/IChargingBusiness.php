<?php

interface IChargingBusiness {

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

}