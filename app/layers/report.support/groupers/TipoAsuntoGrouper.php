<?php
/**
 * Agrupador por tipo de Asunto:
 *
 * * Agrupa por:  prm_tipo_proyecto.glosa_tipo_proyecto
 * * Muestra: prm_tipo_proyecto.glosa_tipo_proyecto
 * * Ordena por: prm_tipo_proyecto.glosa_tipo_proyecto
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Tipo-Asunto
 *
 */
class TipoAsuntoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'prm_tipo_proyecto.glosa_tipo_proyecto';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'prm_tipo_proyecto.glosa_tipo_proyecto';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'prm_tipo_proyecto.glosa_tipo_proyecto';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * @return void
	 */
	function translateForCharges(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			'tipo_asunto'
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
			'prm_tipo_proyecto',
			CriteriaRestriction::equals(
				'prm_tipo_proyecto.id_tipo_proyecto',
				'asunto.id_tipo_asunto'
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
			'tipo_asunto'
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
			'prm_tipo_proyecto',
			CriteriaRestriction::equals(
				'prm_tipo_proyecto.id_tipo_proyecto',
				'asunto.id_tipo_asunto'
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
			'tipo_asunto'
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
			'prm_tipo_proyecto',
			CriteriaRestriction::equals(
				'prm_tipo_proyecto.id_tipo_proyecto',
				'asunto.id_tipo_asunto'
			)
		);
	}
}