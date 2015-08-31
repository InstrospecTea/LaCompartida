<?php
/**
 * Agrupador por Profesional:
 *
 * * Agrupa por: usuario.id_usuario
 * * Muestra: Full name o  username según configuracion
 * * Ordena por: Full name o  username según configuracion
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Profesional
 */
class ProfesionalGrouper extends AbstractGrouperTranslator {

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
		return $this->getUserField();
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return $this->getUserField();
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Indefinido para cobros
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getUndefinedField(), 'profesional')
			->add_grouping($this->getUndefinedField())
			->add_ordering($this->getUndefinedField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Full name o  username según conf del usuario que realizó el trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'profesional')
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
	 * Full name o  username según conf del usuario que hizo el trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'profesional')
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
