<?php
/**
 * Agrupador por Glosa del Grupo (Holding) del cliente
 *
 * * Agrupa por: cliente.id_grupo_cliente
 * * Muestra: IFNULL(grupo_cliente.glosa_grupo_cliente, {$this->undefinedField()})
 * * Ordena por:  IFNULL(grupo_cliente.glosa_grupo_cliente, {$this->undefinedField()})
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Glosa-Grupo-Cliente
 */
class  extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "cliente.id_grupo_cliente";
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return "IFNULL(grupo_cliente.glosa_grupo_cliente, {$this->undefinedField()})";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return "IFNULL(grupo_cliente.glosa_grupo_cliente, {$this->undefinedField()})";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa del grupo del cliente del contrato del cobro
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_grupo_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Glosa del grupo del cliente del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_grupo_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Glosa del grupo del cliente del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_grupo_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
