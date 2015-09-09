<?php
/**
 * Filtro por cliente:
 *
 * * Filtra por:
 * * Cobros: El cliente al que pertenecen los asuntos del cobro.
 * * Trámites: El cliente al que pertenece el asunto del trámite.
 * * Trabajos: El cliente al que pertenece el asunto del trabajo.
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Cliente
 *
 */
class ClientesFilter extends AbstractUndependantFilterTranslator {

	/**
	 * Obtiene el nombre del campo que se filtrará
	 * @return String
	 */
	function getFieldName() {
		return 'filtro_cliente.codigo_cliente';
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
		)->add_left_join_with(
			'cobro_asunto as filtro_cliente_cobro_asunto',
			CriteriaRestriction::equals(
				'filtro_cliente_cobro_asunto.id_cobro', 'cobro.id_cobro'
			)
		)->add_left_join_with(
			'asunto as filtro_cliente_asunto',
			CriteriaRestriction::equals(
				'filtro_cliente_asunto.codigo_asunto', 'filtro_cliente_cobro_asunto.codigo_asunto'
			)
		)->add_left_join_with(
			'cliente as filtro_cliente',
			CriteriaRestriction::equals(
				'filtro_cliente.codigo_cliente', 'filtro_cliente_asunto.codigo_cliente'
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
			'asunto as filtro_cliente_asunto',
			CriteriaRestriction::equals(
				'filtro_cliente_asunto.codigo_asunto',
				'tramite.codigo_asunto'
			)
		)->add_left_join_with(
			'cliente as filtro_cliente',
			CriteriaRestriction::equals(
				'filtro_cliente.codigo_cliente',
				'filtro_cliente_asunto.codigo_cliente'
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
			'asunto as filtro_cliente_asunto',
			CriteriaRestriction::equals(
				'filtro_cliente_asunto.codigo_asunto',
				'trabajo.codigo_asunto'
			)
		)->add_left_join_with(
			'cliente as filtro_cliente',
			CriteriaRestriction::equals(
				'filtro_cliente.codigo_cliente',
				'filtro_cliente_asunto.codigo_cliente'
			)
		);
	}
}