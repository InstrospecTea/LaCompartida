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
	function getSandboxResults() {
		$searchCriteria = new SearchCriteria('Charge');
		$searchCriteria->add_scope('canBeInvoiced');
		$searchCriteria->filter('incluye_honorarios')->restricted_by('equals')->compare_with('1');
		$this->loadBusiness('Searching');
		return $this->SearchingBusiness->searchByCriteria($searchCriteria);
	}

	function getSandboxListator($data) {
		$listator = new EntitiesListator($data);
		$listator->addColumn('# Cobro', 'id_cobro');
		$listator->addColumn('Cliente', 'codigo_cliente');
		$listator->addColumn('Estado', 'estado');
		return $listator->render();
	}

	function generateTemporalFile() {
		try {
			$temp = new SplFileObject('example.txt', 'rw+');
			$temp->setFlags(SPLFileObject::READ_AHEAD);
			$temp->fwrite("This is the first line\n");
			$temp->fwrite("And this is the second.\n");
			$temp->rewind();
			return $temp;
		} catch (Exception $ex) {
			throw new BusinessException('Can not create file');
		}

	}

} 