<?php

class UsuariosFilter extends AbstractUndependantFilterTranslator {

	function getFieldName() {
		return 'usuario.id_usuario';
	}

	function translateForCharges(Criteria $criteria) {
		#nothing todo here
	}

	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)
		->add_select($this->getFieldName())
		->add_left_join_with('usuario',
			CriteriaRestriction::equals('usuario.id_usuario', 'tramite.id_usuario')
		);
	}

	function translateForWorks(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)
		->add_select($this->getFieldName())
		->add_left_join_with('usuario',
			CriteriaRestriction::equals('usuario.id_usuario', 'trabajo.id_usuario')
		);
	}

}