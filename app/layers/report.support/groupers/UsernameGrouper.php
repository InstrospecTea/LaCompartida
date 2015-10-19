<?php
/**
 * Agrupador por Username:
 *
 * * Agrupa por: usuario.id_usuario
 * * Muestra: usuario.username
 * * Ordena por: usuario.username
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Username
 */
class UsernameGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'usuario.id_usuario';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'usuario.username';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'usuario.username';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Indefinido para cobros
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getUndefinedField(), 'username')
			->add_select($this->getUndefinedField(), 'id_usuario')
			->add_grouping($this->getUndefinedField())
			->add_ordering($this->getUndefinedField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * username del usuario del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'username')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with(
			'usuario',
				CriteriaRestriction::equals(
					'usuario.id_usuario',
					'tramite.id_usuario'
				)
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * username del usuario del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'username')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with(
			'usuario',
				CriteriaRestriction::equals(
					'usuario.id_usuario',
					'trabajo.id_usuario'
				)
			);

		return $Criteria;
	}
}