<?php
/**
 * Agrupador por Glosa asunto:
 *
 * * Agrupa por: asunto.codigo_asunto
 * * Muestra: asunto.glosa_asunto
 * * Ordena por: asunto.glosa_asunto
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Glosa-Asunto
 */
class GlosaAsuntoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'asunto.codigo_asunto';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'asunto.glosa_asunto';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'asunto.glosa_asunto';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa de cada asunto incluido en la liquidación
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$this->addMatterCountSubcriteria($Criteria);

		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Glosa del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'tramite.codigo_asunto')
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Glosa del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto')
			);

		return $Criteria;
	}
}
