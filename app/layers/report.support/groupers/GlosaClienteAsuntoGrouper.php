<?php
/**
 * Agrupador por Glosa Cliente y Asunto
 *
 * * Agrupa por: asunto.codigo_asunto
 * * Muestra: cliente.glosa_cliente - asunto.codigo_asunto asunto.glosa_asunto
 * * Ordena por: cliente.glosa_cliente - asunto.codigo_asunto asunto.glosa_asunto
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Glosa-Cliente-Asunto
 */
class GlosaClienteAsuntoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return $this->getProjectCodeField();
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		$code = $this->getProjectCodeField();
		return "CONCAT(cliente.glosa_cliente, ' - ', $code, ' ', asunto.glosa_asunto)" ;
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return $this->getSelectField();
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Código de cada asunto incluido en la liquidación y Cliente del contrato
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$this->addMatterCountSubcriteria($Criteria);

		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente_asunto')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'contrato.codigo_cliente')
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Código del asunto del trámite y cliente del asunto
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente_asunto')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'tramite.codigo_asunto')
			)
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'asunto.codigo_cliente')
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Código del asunto del trabajo y cliente del asunto
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente_asunto')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto')
			)
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'asunto.codigo_cliente')
			);

		return $Criteria;
	}
}
