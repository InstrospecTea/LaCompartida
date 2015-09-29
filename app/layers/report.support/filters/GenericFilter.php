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
	 * Obtiene el join apropiado para la tabla
	 * @return Function retorna función que
	 */
	function addJoinsForTable(Criteria $Criteria, $kind) {
		$kind = ucfirst($kind);
		$join = "{$this->tableName}{$kind}Join";

		if (method_exists($this, $join)) {
			$this->$join($Criteria);
		}
	}

	function usuarioWorkJoin($Criteria) {
		$Criteria->add_left_join_with(
			'usuario',
			CriteriaRestriction::equals(
				'usuario.id_usuario',
				'trabajo.id_usuario'
			)
		);
	}

	function usuarioErrandJoin($Criteria) {
		$Criteria->add_left_join_with(
			'usuario',
			CriteriaRestriction::equals(
				'usuario.id_usuario',
				'tramite.id_usuario'
			)
		);
	}

	function clienteWorkJoin($Criteria) {
		$Criteria->add_left_join_with(
			'cliente',
			CriteriaRestriction::equals(
				'cliente.codigo_cliente',
				'asunto.codigo_cliente'
			)
		);
	}

	function clienteErrandJoin($Criteria) {
		$Criteria->add_left_join_with(
			'cliente',
			CriteriaRestriction::equals(
				'cliente.codigo_cliente',
				'asunto.codigo_cliente'
			)
		);
	}

	function clienteChargeJoin($Criteria) {
		$Criteria->add_left_join_with(
			'cliente',
			CriteriaRestriction::equals(
				'cliente.codigo_cliente',
				'asunto.codigo_cliente'
			)
		);
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
		$this->addJoinsForTable($criteria, 'charge');
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
		$this->addJoinsForTable($criteria, 'errand');
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
		$this->addJoinsForTable($criteria, 'work');
		return $this->addData(
			$this->getFilterData(),
			$criteria
		);
	}
}