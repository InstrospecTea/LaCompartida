<?php
/**
 * Filtro por area usuario:
 *
 * * Filtra por:
 * * Cobros: no aplica
 * * Tr�mites: usuario.id_area_usuario
 * * Trabajos: usuario.id_area_usuario
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Area-Usuario
 *
 */
class AreaUsuarioFilter extends AbstractUndependantFilterTranslator {

	/**
	 * Obtiene el nombre del campo que se filtrará
	 * @return String
	 */
	function getFieldName() {
		return 'usuario.id_area_usuario';
	}

	/**
	 * Traduce el filtro para el caso de los cobros
	 * @param  Criteria $criteria Query builder asociado a los cobros
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForCharges(Criteria $criteria) {
		return $criteria;
	}

	/**
	 * Traduce el filtro para el caso de los trámites
	 * @param  Criteria $criteria Query builder asociado a los tr�mites
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_left_join_with(
			'usuario',
			CriteriaRestriction::equals(
				'usuario.id_usuario',
				'tramite.id_usuario'
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
			'usuario',
			CriteriaRestriction::equals(
				'usuario.id_usuario',
				'trabajo.id_usuario'
			)
		);
	}
}