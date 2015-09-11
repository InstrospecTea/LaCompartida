<?php
/**
 * Filtro por Cualquier cosa:
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Generic
 *
 * TODO: un mapping con las tablas gen�ricas y sus JOINS
 *
 */
class GenericFilter extends AbstractUndependantFilterTranslator {

	private $tableName;
	private $fieldName;

	public function __construct($Session, $tableName, $fieldName, $data, $parity) {
		$this->Session = $Session;
		$this->tableName = $tableName;
		$this->fieldName = $fieldName;
		parent::__construct($Session, $data, $parity);
	}

	/**
	 * Obtiene el nombre del campo que se filtrar�
	 * @return String
	 */
	function getFieldName() {
		return "{$this->tableName}.{$this->fieldName}";
	}

	/**
	 * Traduce el filtro para el caso de los cobros
	 * @param  Criteria $criteria Query builder asociado a los cobros
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForCharges(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		);
	}

	/**
	 * Traduce el filtro para el caso de los tr�mites
	 * @param  Criteria $criteria Query builder asociado a los tr�mites
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		);
	}

	/**
	 * Traduce el filtro para el caso de los trabajos
	 * @param  Criteria $criteria Query builder asociado a los trabajos
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForWorks(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		);
	}
}