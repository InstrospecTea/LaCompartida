<?php
/**
 * Agrupador por Código de Asunto Secundario:
 *
 * * Agrupa por: asunto.codigo_asunto_secundario
 * * Muestra: asunto.codigo_asunto_secundario
 * * Ordena por: asunto.codigo_asunto_secundario
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Codigo-Asunto-Secundario
 */
class CodigoAsuntoSecundarioGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'asunto.codigo_asunto_secundario';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'asunto.codigo_asunto_secundario';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'asunto.codigo_asunto_secundario';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Código de cada asunto incluido en la liquidación
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$this->addMatterCountSubcriteria($Criteria);

		$Criteria
			->add_select($this->getSelectField(), 'codigo_asunto')
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Código del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'codigo_asunto')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'tramite.codigo_asunto')
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Código del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'codigo_asunto')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto')
			);

		return $Criteria;
	}
}
