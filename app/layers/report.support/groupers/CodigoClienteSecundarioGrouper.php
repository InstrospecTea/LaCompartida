<?php
/**
 * Agrupador por Código de cliente secundario:
 *
 * * Agrupa por: cliente.codigo_cliente_secundario
 * * Muestra: cliente.codigo_cliente_secundario
 * * Ordena por: codigo_cliente_secundario
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Codigo-Cliente-Secundario
 */
class CodigoClienteSecundarioGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'client_grouper.codigo_cliente_secundario';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'client_grouper.codigo_cliente_secundario';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'client_grouper.codigo_cliente_secundario';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Código secundario del cliente del cobro
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria->add_select(
			$this->getSelectField(), 'codigo_cliente_secundario'
		)->add_left_join_with(
			'cliente client_grouper',
			'client_grouper.codigo_cliente = cobro.codigo_cliente'
		)->add_grouping(
			$this->getGroupField()
		)->add_ordering(
			$this->getOrderField()
		);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Código secundario del cliente del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria->add_select(
			$this->getSelectField(),
			'codigo_cliente'
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'tramite.codigo_asunto'
			)
		)->add_left_join_with(
			'cliente client_grouper',
			CriteriaRestriction::equals(
				'client_grouper.codigo_cliente',
				'asunto.codigo_cliente'
			)
		)->add_grouping(
			$this->getGroupField()
		)->add_ordering(
			$this->getOrderField()
		);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Código secundario del cliente del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria->add_select(
			$this->getSelectField(),
			'codigo_cliente_secundario'
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'trabajo.codigo_asunto'
			)
		)->add_left_join_with(
			'cliente client_grouper',
			CriteriaRestriction::equals(
				'client_grouper.codigo_cliente',
				'asunto.codigo_cliente'
			)
		)->add_grouping(
			$this->getGroupField()
		)->add_ordering(
			$this->getOrderField()
		);

		return $Criteria;
	}
}
