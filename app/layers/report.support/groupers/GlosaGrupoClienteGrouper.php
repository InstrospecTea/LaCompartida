<?php
/**
 * Agrupador por Glosa del Grupo (Holding) del cliente
 *
 * * Agrupa por: cliente.id_grupo_cliente
 * * Muestra: IFNULL(grupo_cliente.glosa_grupo_cliente, {$this->getUndefinedField()})
 * * Ordena por:  IFNULL(grupo_cliente.glosa_grupo_cliente, {$this->getUndefinedField()})
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Glosa-Grupo-Cliente
 */
class GlosaGrupoClienteGrouper extends AbstractGrouperTranslator {

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
		return "IFNULL(grupo_cliente.glosa_grupo_cliente, {$this->getUndefinedField()})";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return "IFNULL(grupo_cliente.glosa_grupo_cliente, {$this->getUndefinedField()})";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa del grupo del cliente del contrato del cobro
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_grupo_cliente')
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('contrato',
				CriteriaRestriction::equals('contrato.id_contrato', 'cobro.id_contrato'))
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'contrato.codigo_cliente'))
			->add_left_join_with('grupo_cliente',
				CriteriaRestriction::equals('grupo_cliente.id_grupo_cliente', 'cliente.id_grupo_cliente'));

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
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'tramite.codigo_asunto'))
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'asunto.codigo_cliente'))
			->add_left_join_with('grupo_cliente',
				CriteriaRestriction::equals('grupo_cliente.id_grupo_cliente', 'cliente.id_grupo_cliente'));

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
			->add_select($this->getGroupField())
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto'))
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'asunto.codigo_cliente'))
			->add_left_join_with('grupo_cliente',
				CriteriaRestriction::equals('grupo_cliente.id_grupo_cliente', 'cliente.id_grupo_cliente'));

		return $Criteria;
	}
}
