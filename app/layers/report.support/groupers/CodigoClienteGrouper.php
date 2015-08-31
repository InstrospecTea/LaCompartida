<?php
/**
 * Agrupador por Código de cliente:
 *
 * * Agrupa por: cliente.codigo_cliente
 * * Muestra: cliente.codigo_cliente'
 * * Ordena por: cliente.codigo_cliente
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Codigo-Cliente
 */
class CodigoClienteGrouper extends AbstractGrouperTranslator {

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
		return 'cliente.codigo_cliente';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'cliente.codigo_cliente';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Código del cliente de cada asunto incluido en la liquidación
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'codigo_cliente')
			->add_left_join_with('cliente', 'asunto.codigo_cliente = cliente.codigo_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Código del cliente del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'codigo_cliente')
			->add_left_join_with('asunto', 'asunto.codigo_asunto = trabajo.codigo_asunto')
			->add_left_join_with('cliente', 'asunto.codigo_cliente = cliente.codigo_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * código del cliente del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'codigo_cliente')
			->add_left_join_with('asunto', 'asunto.codigo_asunto = trabajo.codigo_asunto')
			->add_left_join_with('cliente', 'asunto.codigo_cliente = cliente.codigo_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
