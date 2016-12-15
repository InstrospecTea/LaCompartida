<?php
/**
 * Filtro por grupo cliente:
 *
 * * Filtra por:
 * * Cobros: El cliente al que pertenecen los asuntos del cobro seg�n el Grupo-Cliente.
 * * Tr�mites: El cliente al que pertenece el asunto del tr�mite seg�n el Grupo-Cliente.
 * * Trabajos: El cliente al que pertenece el asunto del trabajo seg�n el Grupo-Cliente.
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Grupo-Cliente
 *
 */
class GrupoClientesFilter extends AbstractUndependantFilterTranslator {

	/**
	 * Obtiene el nombre del campo que se filtrar�
	 * @return String
	 */
	function getFieldName() {
		return 'cliente.id_grupo_cliente';
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
		)
		->add_left_join_with(
			'cliente as filtro_cliente',
			CriteriaRestriction::equals(
				'filtro_cliente.codigo_cliente', 'cobro.codigo_cliente'
			)
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
		)
		->add_left_join_with(
			'cliente as filtro_cliente',
			CriteriaRestriction::equals(
				'filtro_cliente.codigo_cliente',
				'asunto.codigo_cliente'
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
		)
		->add_left_join_with(
			'cliente as filtro_cliente',
			CriteriaRestriction::equals(
				'filtro_cliente.codigo_cliente',
				'asunto.codigo_cliente'
			)
		);
	}
}