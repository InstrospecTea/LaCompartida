<?php

interface IChargingBusiness {

	/**
	 * Elimina un cobro
	 * @param type $id_cobro
	 * @throw Exceptions
	 */
	function delete($id_cobro);

}