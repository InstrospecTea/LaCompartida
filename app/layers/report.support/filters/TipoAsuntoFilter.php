<?php
/**
 * Filtro por tipo asunto:
 *
 * * Filtra por:
 * * Cobros: asunto.id_tipo_asunto
 * * Tr�mites: asunto.id_tipo_asunto
 * * Trabajos: asunto.id_tipo_asunto
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Tipo-Asunto
 *
 */
class TipoAsuntoFilter extends AbstractUndependantFilterTranslator {

	/**
	 * Obtiene el nombre del campo que se filtrar�
	 * @return String
	 */
	function getFieldName() {
		return 'asunto.id_tipo_asunto';
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