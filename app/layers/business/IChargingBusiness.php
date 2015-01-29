<?php

interface IChargingBusiness {

	/**
	 * Verifica si existe un cobro segun su ID.
	 * @param int $id_cobro
	 * @return boolean
	 */
	public function doesChargeExists($id_cobro);

}