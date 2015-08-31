<?php
/**
 * Agrupador por Glosa cliente:
 *
 * * Agrupa por: cliente.codigo_cliente
 * * Muestra: cliente.glosa_cliente
 * * Ordena por: cliente.glosa_cliente
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Glosa-Cliente
 */
class GlosaClienteGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'cliente.codigo_cliente';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'cliente.glosa_cliente';
	}
	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'cliente.glosa_cliente';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa del cliente de cada asunto incluido en la liquidación
	 * @return void
	 */

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('contrato',
				CriteriaRestriction::equals('contrato.id_contrato', 'cobro.id_contrato')
			)
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'contrato.codigo_cliente')
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Glosa del cliente del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente')
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
	 * Glosa del cliente del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente')
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
