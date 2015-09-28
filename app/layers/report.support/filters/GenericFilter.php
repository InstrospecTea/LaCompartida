<?php
/**
 * Filtro por Cualquier cosa:
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Generic
 *
 * TODO: un mapping con las tablas genéricas y sus JOINS
 *
 */
class GenericFilter extends AbstractUndependantFilterTranslator {

	private $tableName;
	private $fieldName;
	private $cancelWorksTable = array(
		'tramite'
	);
	private $cancelErrandsTable = array(
		'trabajo',
		'prm_area_trabajo'
	);
	private $cancelChargesTable = array(
		'trabajo',
		'tramite',
		'usuario',
		'prm_area_usuario',
		'prm_area_trabajo',
		'prm_categoria_usuario'
	);
	public function __construct($Session, $tableName, $fieldName, $data, $parity) {
		$this->Session = $Session;
		$this->tableName = $tableName;
		$this->fieldName = $fieldName;
		parent::__construct($Session, $data, $parity);
	}

	/**
	 * Obtiene el nombre del campo que se filtrará
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
		if (in_array($this->tableName, $this->cancelChargesTable)) {
			return $criteria;
		}
		return $this->addData(
			$this->getFilterData(),
			$criteria
		);
	}

	/**
	 * Traduce el filtro para el caso de los trámites
	 * @param  Criteria $criteria Query builder asociado a los trámites
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForErrands(Criteria $criteria) {
		if (in_array($this->tableName, $this->cancelErrandsTable)) {
			return $criteria;
		}
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
		if (in_array($this->tableName, $this->cancelWorksTable)) {
			return $criteria;
		}
		return $this->addData(
			$this->getFilterData(),
			$criteria
		);
	}
}