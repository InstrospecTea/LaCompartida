<?php
/**
 * Filtro por categoría de usuario:
 *
 * * Filtra por:
 * * Cobros: no aplica
 * * Trámites: usuario.id_categoria_usuario
 * * Trabajos: usuario.id_categoria_usuario
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Filtro:-Categoria-Usuario
 *
 */
class CategoriaUsuarioFilter extends AbstractUndependantFilterTranslator {

	/**
	 * Obtiene el nombre del campo que se filtrará
	 * @return String
	 */
	function getFieldName() {
		return 'cat_usuario_filter_usuario.id_categoria_usuario';
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
	 * @param  Criteria $criteria Query builder asociado a los trámites
	 * @return Criteria Query builder con las restricciones del filtro ya aplicadas.
	 */
	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_left_join_with(
			'usuario cat_usuario_filter_usuario',
			CriteriaRestriction::equals(
				'cat_usuario_filter_usuario.id_usuario',
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
			'usuario cat_usuario_filter_usuario',
			CriteriaRestriction::equals(
				'cat_usuario_filter_usuario.id_usuario',
				'trabajo.id_usuario'
			)
		);
	}
}