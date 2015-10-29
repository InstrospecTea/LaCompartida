<?php
/**
 * Filtro por encargado comercial:
 *
 * * Filtra por:
 * * Cobros: cobro.id_usuario_responsable
 * * Trámites: contrato.id_usuario_responsable
 * * Trabajos: contrato.id_usuario_responsable
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Encargado-Comercial
 *
 */
class EncargadosFilter extends AbstractUndependantFilterTranslator {

	/**
	 * Obtiene el nombre del campo que se filtrará
	 * @return String
	 */
	function getFieldName() {
		return 'contrato.id_usuario_responsable';
	}

	/**
	 * Nombre custom del join principal para evitar colisiones
	 * @return String
	 */
	function getJoinName() {
		return 'usuario_responsable_filter';
	}

	/**
	 * Traduce el filtro para el caso de los cobros
	 * @param  Criteria $criteria Query builder asociado a los cobros
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForCharges(Criteria $criteria) {
		$data = $this->getFilterData();
		if (is_array($data)) {
			$and_wheres[] = CriteriaRestriction::in(
				'cobro.id_usuario_responsable', $data
			);
			$criteria->add_restriction(
				CriteriaRestriction::and_clause(
					$and_wheres
				)
			);
		} else {
			$criteria->add_restriction(
				CriteriaRestriction::equals(
					'cobro.id_usuario_responsable',
					$data
				)
			);
		}
		return $criteria;
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
			'contrato as encargado_comercial_contrato',
			CriteriaRestriction::equals(
				'encargado_comercial_contrato.id_contrato',
				'asunto.id_contrato'
			)
		)->add_left_join_with(
			"usuario as {$this->getJoinName()}",
			CriteriaRestriction::equals(
				"{$this->getJoinName()}.id_usuario",
				'encargado_comercial_contrato.id_usuario_responsable'
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
			'contrato as encargado_comercial_contrato',
			CriteriaRestriction::equals(
				'encargado_comercial_contrato.id_contrato',
				'asunto.id_contrato'
			)
		)->add_left_join_with(
			"usuario as {$this->getJoinName()}",
			CriteriaRestriction::equals(
				"{$this->getJoinName()}.id_usuario",
				'encargado_comercial_contrato.id_usuario_responsable'
			)
		);
	}
}