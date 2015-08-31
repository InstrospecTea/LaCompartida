<?php
/**
 * Agrupador por Glosa asunto con Código:
 *
 * * Agrupa por: asunto.codigo_asunto
 * * Muestra: asunto.codigo_asunto concatenado con asunto.glosa_asunto
 * * Ordena por: asunto.codigo_asunto concatenado con asunto.glosa_asunto
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Glosa-Asunto-Codigo
 */
class GlosaAsuntoConCodigoGrouper extends AbstractGrouperTranslator {

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
		return "CONCAT(asunto.codigo_asunto, ': ', asunto.glosa_asunto)";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return "CONCAT(asunto.codigo_asunto, ': ', asunto.glosa_asunto)";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa Código de cada asunto incluido en la liquidación
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto_con_codigo')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('cobro_asunto',
				CriteriaRestriction::equals('cobro_asunto.id_cobro', 'cobro.id_cobro'))
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'cobro_asunto.codigo_asunto'));

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Glosa y Código del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto_con_codigo')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'tramite.codigo_asunto')
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Glosa y código del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto_con_codigo')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto')
			);

		return $Criteria;
	}
}
