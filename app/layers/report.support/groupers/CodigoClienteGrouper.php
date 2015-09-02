<?php
/**
 * Agrupador por C�digo de cliente:
 *
 * * Agrupa por: cliente.codigo_cliente
 * * Muestra: cliente.codigo_cliente'
 * * Ordena por: cliente.codigo_cliente
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Codigo-Cliente
 */
class CodigoClienteGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupar� la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'client_grouper.codigo_cliente';
	}

	/**
	 * Obtiene el campo de grupo que se devolver� en el SELECT de la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getSelectField() {
		return 'client_grouper.codigo_cliente';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenar� la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getOrderField() {
		return 'client_grouper.codigo_cliente';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * C�digo del cliente de cada asunto incluido en la liquidaci�n
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria->add_select(
			$this->getSelectField(), 'codigo_cliente'
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
	 * Traduce los keys de agrupadores a campos para la query de Tr�mites
	 * C�digo del cliente del asunto del tr�mite
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
	 * c�digo del cliente del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria->add_select(
			$this->getSelectField(),
			'codigo_cliente'
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
