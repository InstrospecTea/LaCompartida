<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 10/27/14
 * Time: 12:02 PM
 */

class SandboxingBusiness extends AbstractBusiness implements ISandboxingBusiness{

	/**
	 * @return mixed
	 */
	function getSandboxResults($per_page = null, $page = null) {
		$searchCriteria = new SearchCriteria('Charge');
		$searchCriteria->add_scope('canBeInvoiced');
		$searchCriteria->filter('incluye_honorarios')->restricted_by('equals')->compare_with('1');
		if ($x_page) {
			$searchCriteria->Pagination->rows_per_page($per_page);
		}
		$this->loadBusiness('Searching');
		return $this->SearchingBusiness->paginateByCriteria($searchCriteria, array('codigo_cliente', 'estado'), $page);
	}

	function data() {
		$this->loadBusiness('Searching');
		$searchCriteria = new SearchCriteria('Contract');
		$searchCriteria->filter('activo')->restricted_by('equals')->compare_with("'SI'");
		$searchCriteria->related_with('Client')->on_property('codigo_cliente');

		return $this->SearchingBusiness->searchByGenericCriteria($searchCriteria, array('Contract.codigo_cliente', 'Client.glosa_cliente'));
	}
}