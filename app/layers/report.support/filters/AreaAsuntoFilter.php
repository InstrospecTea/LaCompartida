<?php
/**
 * Filtro por area asunto:
 *
 * * Filtra por:
 * * Cobros: asunto.id_area_proyecto
 * * Trámites: asunto.id_area_proyecto
 * * Trabajos: asunto.id_area_proyecto
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Area-Asunto
 *
 */
class AreaAsuntoFilter extends AbstractUndependantFilterTranslator {

	/**
	 * Obtiene el nombre del campo que se filtrará
	 * @return String
	 */
	function getFieldName() {
		return 'asunto.id_area_proyecto';
	}

	/**
	 * Traduce el filtro para el caso de los cobros
	 * @param  Criteria $criteria Query builder asociado a los cobros
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForCharges(Criteria $criteria) {
		$this->addMatterCountSubcriteria($criteria);
		
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_left_join_with(
			'cobro_asunto',
			CriteriaRestriction::equals(
				'cobro_asunto.id_cobro',
				'cobro.id_cobro'
			)
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'cobro_asunto.codigo_asunto'
			)
		);
	}

	/**
	 * Traduce el filtro para el caso de los trámites
	 * @param  Criteria $criteria Query builder asociado a los trámites
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'tramite.codigo_asunto'
			)
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
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'trabajo.codigo_asunto'
			)
		);
	}
}