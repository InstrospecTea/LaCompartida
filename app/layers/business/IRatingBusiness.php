<?php

interface IRatingBusiness  extends BaseBusiness {

	/**
	 * Elimina una tarifa de trámites
	 * @param type $id_tarifa
	 * @throw Exceptions
	 */
	function deleteErrandRate($id_tarifa);

	/**
	 * Trae las tarifas de trámites
	 * return Array
	 */
	function getErrandsRate();

}
