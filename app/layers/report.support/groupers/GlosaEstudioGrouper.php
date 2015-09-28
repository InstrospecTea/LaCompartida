<?php
/**
 * Agrupador por Glosa estudio:
 *
 * * Agrupa por: asunto.codigo_asunto
 * * Muestra: asunto.glosa_asunto
 * * Ordena por: asunto.glosa_asunto
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Glosa-Asunto
 */
class GlosaEstudioGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'glosa_estudio';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'prm_estudio.glosa_estudio';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'glosa_estudio';
	}

	function getUndefinedValue() {
		return sprintf("'%s'", __('Indefinido'));
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa de cada asunto incluido en la liquidación
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select(CriteriaRestriction::ifnull($this->getSelectField(), $this->getUndefinedValue()), 'glosa_estudio')
			->add_select(CriteriaRestriction::ifnull('cobro.id_estudio', CriteriaRestriction::ifnull('prm_estudio.id_estudio', $this->getUndefinedValue())), 'id_estudio')
			->add_left_join_with('prm_estudio', CriteriaRestriction::equals('cobro.id_estudio', 'prm_estudio.id_estudio'))
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
			->add_select(CriteriaRestriction::ifnull($this->getSelectField(), CriteriaRestriction::ifnull('estudio_contrato.glosa_estudio', $this->getUndefinedValue())), 'glosa_estudio')
			->add_select(CriteriaRestriction::ifnull('cobro.id_estudio', CriteriaRestriction::ifnull('estudio_contrato.id_estudio', $this->getUndefinedValue())), 'id_estudio')
			->add_left_join_with('prm_estudio', CriteriaRestriction::equals('cobro.id_estudio', 'prm_estudio.id_estudio'))
			->add_left_join_with(array('prm_estudio', 'estudio_contrato'), CriteriaRestriction::equals('contrato.id_estudio', 'estudio_contrato.id_estudio'))
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Glosa del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select(CriteriaRestriction::ifnull($this->getSelectField(), CriteriaRestriction::ifnull('estudio_contrato.glosa_estudio', $this->getUndefinedValue())), 'glosa_estudio')
			->add_select(CriteriaRestriction::ifnull('cobro.id_estudio', CriteriaRestriction::ifnull('estudio_contrato.id_estudio', $this->getUndefinedValue())), 'id_estudio')
			->add_left_join_with('prm_estudio', CriteriaRestriction::equals('cobro.id_estudio', 'prm_estudio.id_estudio'))
			->add_left_join_with(array('prm_estudio', 'estudio_contrato'), CriteriaRestriction::equals('contrato.id_estudio', 'estudio_contrato.id_estudio'))
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
