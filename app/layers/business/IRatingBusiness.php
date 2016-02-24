<?php

interface IRatingBusiness  extends BaseBusiness {

	/**
	 * Elimina una tarifa de tr�mites
	 * @param type $id_tarifa
	 * @throw Exceptions
	 */
	function deleteErrandRate($id_tarifa);

	/**
	 * Trae las tarifas de tr�mites
	 * return Array
	 */
	function getErrandsRate();

}
