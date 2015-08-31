<?php
/**
 * Agrupador por área de Asunto:
 *
 * * Agrupa por:  prm_area_proyecto.glosa
 * * Muestra: prm_area_proyecto.glosa
 * * Ordena por: prm_area_proyecto.glosa
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Area-Asunto
 *
 */
class AreaAsuntoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'prm_area_proyecto.glosa';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'prm_area_proyecto.glosa';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'prm_area_proyecto.glosa';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * @return void
	 */
	function translateForCharges(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			'area_asunto'
		)->add_ordering(
			$this->getOrderField()
		)->add_grouping(
			$this->getGroupField()
		)->add_left_join_with(
			'cobro_asunto',
			CriteriaRestriction::equals(
				'cobro_asunto.id_cobro',
				'cobro.id_cobro'
			)
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'cobro_asunto.codigo_asunto'
			)
		)->add_left_join_with(
			'prm_area_proyecto',
			CriteriaRestriction::equals(
				'prm_area_proyecto.id_area_proyecto',
				'asunto.id_area_proyecto'
			)
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * @return void
	 */
	function translateForErrands(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			'area_asunto'
		)->add_ordering(
			$this->getOrderField()
		)->add_grouping(
			$this->getGroupField()
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'tramite.codigo_asunto'
			)
		)->add_left_join_with(
			'prm_area_proyecto',
			CriteriaRestriction::equals(
				'prm_area_proyecto.id_area_proyecto',
				'asunto.id_area_proyecto'
			)
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * @return void
	 */
	function translateForWorks(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			'area_asunto'
		)->add_ordering(
			$this->getOrderField()
		)->add_grouping(
			$this->getGroupField()
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'trabajo.codigo_asunto'
			)
		)->add_left_join_with(
			'prm_area_proyecto',
			CriteriaRestriction::equals(
				'prm_area_proyecto.id_area_proyecto',
				'asunto.id_area_proyecto'
			)
		);
	}
}