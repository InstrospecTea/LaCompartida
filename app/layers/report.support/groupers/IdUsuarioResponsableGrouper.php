<?php

class IdUsuarioResponsableGrouper extends AbstractGrouperTranslator {

	function getGroupField() {
		return "usuario_responsable.id_usuario";
	}

	function getSelectField() {
		return $this->getUserAcountManagerField();
	}

	function getOrderField() {
		return "usuario_responsable.id_usuario";
	}

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getGroupField(), 'id_usuario_responsable')
			->add_select($this->getSelectField(), 'nombre_usuario_responsable')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('contrato',
				CriteriaRestriction::equals('contrato.id_contrato', 'cobro.id_contrato')
			)
			->add_left_join_with(array('usuario', 'usuario_responsable'),
				CriteriaRestriction::equals(
					'usuario_responsable.id_usuario', 'contrato.id_usuario_responsable'
				)
			);

		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getGroupField(), 'id_usuario_responsable')
			->add_select($this->getSelectField(), 'nombre_usuario_responsable')
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
				array('usuario', 'usuario_responsable'),
				CriteriaRestriction::equals(
					'usuario_responsable.id_usuario', 'contrato.id_usuario_responsable'
				)
			);

		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getGroupField(), 'id_usuario_responsable')
			->add_select($this->getSelectField(), 'nombre_usuario_responsable')
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
				array('usuario', 'usuario_responsable'),
				CriteriaRestriction::equals(
					'usuario_responsable.id_usuario', 'contrato.id_usuario_responsable'
				)
			);

		return $Criteria;
	}
}