<?php

class EncargadosFilter extends AbstractUndependantFilterTranslator {

	function getFieldName() {
		return 'contrato.id_usuario_responsable';
	}

	function getJoinName() {
		return 'usuario_responsable';
	}

	function getSelect() {
		if (Conf::GetConf($this->Session, 'UsaUsernameEnTodoElSistema')) {
			return "{$this->getJoinName()}.username";
		}
		return "IF({$this->getJoinName()}.id_usuario IS NULL, 'Sin Responsable', {$this->getJoinName()}.id_usuario)"
	}

	function translateForCharges(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getSelect()
		)->add_left_join_with(
			"usuario as {$this->getJoinName()}",
			CriteriaRestriction::equals(
				"{$this->getJoinName()}.id_usuario",
				'cobro.id_usuario'
			)
		);
	}

	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getFieldName(),
			"'prm_area_usuario.glosa'"
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto', 
				'tramite.codigo_asunto'
			)
		)->add_left_join_with(
			'contrato',
			CriteriaRestriction::equals(
				'contrato.id_contrato',
				'asunto.id_contrato'
			)
		)->add_left_join_with(
			"usuario as {$this->getJoinName()}",
			CriteriaRestriction::equals(
				"{$this->getJoinName()}.id_usuario",
				'contrato.id_usuario_responsable'
			)
		);
	}

	function translateForWorks(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getFieldName(),
			"'prm_area_usuario.glosa'"
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto', 
				'trabajo.codigo_asunto'
			)
		)->add_left_join_with(
			'contrato',
			CriteriaRestriction::equals(
				'contrato.id_contrato',
				'asunto.id_contrato'
			)
		)->add_left_join_with(
			"usuario as {$this->getJoinName()}",
			CriteriaRestriction::equals(
				"{$this->getJoinName()}.id_usuario",
				'contrato.id_usuario_responsable'
			)
		);
	}
}