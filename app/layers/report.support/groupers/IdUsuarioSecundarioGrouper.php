<?php
/**
 * Agrupador por Usuario Secundario (Encargado Secundario):
 *
 * * Agrupa por: usuario_secundario.id_usuario
 * * Muestra: Full name o  username según configuracion del usuario responsable o "Sin responsable"
 * * Ordena por: usuario_secundario.id_usuario
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Usuario-Secundario
 */
class IdUsuarioSecundarioGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "usuario_secundario.id_usuario";
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		$selectField = $this->getSecondUserAcountManagerField();
		$selectValue = "IF(usuario_secundario.id_usuario IS NULL, 'Sin Resposable', {$selectField})";
		return $selectValue;
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return "usuario_secundario.id_usuario";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * El usuario encargado del contrato del cobro
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getGroupField(), 'id_usuario_secundario')
			->add_select($this->getSelectField(), 'nombre_usuario_secundario')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('contrato',
				CriteriaRestriction::equals('contrato.id_contrato', 'cobro.id_contrato')
			)
			->add_left_join_with(array('usuario', 'usuario_secundario'),
				CriteriaRestriction::equals(
					'usuario_secundario.id_usuario', 'contrato.id_usuario_secundario'
				)
			);
		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * El usuario encargado del contrato del cobro o del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getGroupField(), 'id_usuario_secundario')
			->add_select($this->getSelectField(), 'nombre_usuario_secundario')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto', CriteriaRestriction::equals('asunto.codigo_asunto', 'tramite.codigo_asunto'))
			->add_left_join_with('cobro', CriteriaRestriction::equals('cobro.id_cobro', 'tramite.id_cobro'))
			->add_left_join_with('contrato',
				CriteriaRestriction::equals(
					'contrato.id_contrato',
					CriteriaRestriction::ifnull('cobro.id_contrato', 'asunto.id_contrato')
				)
			)
			->add_left_join_with(
				array('usuario', 'usuario_secundario'),
				CriteriaRestriction::equals(
					'usuario_secundario.id_usuario', 'contrato.id_usuario_secundario'
				)
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * El usuario encargado del contrato del cobro o del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getGroupField(), 'id_usuario_secundario')
			->add_select($this->getSelectField(), 'nombre_usuario_secundario')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto', CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto'))
			->add_left_join_with('cobro', CriteriaRestriction::equals('cobro.id_cobro', 'trabajo.id_cobro'))
			->add_left_join_with('contrato',
				CriteriaRestriction::equals(
					'contrato.id_contrato',
					CriteriaRestriction::ifnull('cobro.id_contrato', 'asunto.id_contrato')
				)
			)
			->add_left_join_with(
				array('usuario', 'usuario_secundario'),
				CriteriaRestriction::equals(
					'usuario_secundario.id_usuario', 'contrato.id_usuario_secundario'
				)
			);

		return $Criteria;
	}
}
