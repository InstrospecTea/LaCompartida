<?php

interface IRatingBusiness  extends BaseBusiness {

	/**
	 * Elimina una tarifa de tr�mites
	 * @param type int
	 */
	public function deleteErrandRate($id_tarifa);

	/**
	 * Actualiza una tarifa de tr�mites
	 * @param type int, array, array
	 */
	public function updateErrandRate($rate_id, $errand_rate, $rates);

	/**
	 * Inserta una tarifa de tr�mites
	 * @param type array, array
	 */
	public function insertErrandRate($errand_rate, $rates);

	/**
	 * Trae las tarifas de tr�mites
	 * @return Array
	 */
	public function getErrandsRate();

	/**
	 * Trae los campos (monedas) correspondientes a la tarifa tr�mite seleccionada para generar la tabla de llenado
	 * @return Array
	 */
	public function getErrandsRateFields();

	/**
	 * Trae los valores de la tarifa tr�mite seleccionada
	 * @param type int
	 * @return Array
	 */
	public function getErrandsRateValue($id_rate);

	/**
	 * Trae la el detalle de la tarifa tr�mite seleccionada
	 * @return Array
	 */
	public function getErrandRateDetail($id_rate);

	/**
	 * Trae el total de contratos asociados a la tarifa tr�mite que se desea eliminar
	 * @return int
	 */
	public function getContractsWithErrandRate($id_rate);

	/**
	 * Actualiza la tarifa tr�mite por defento en los contratos
	 * @return boolean
	 */
	public function updateDefaultErrandRateOnContracts($id_rate);

	/**
	 * Cuenta las tarifas de tr�mites del sistema
	 * @return int
	 */
	public function countRates();
}
