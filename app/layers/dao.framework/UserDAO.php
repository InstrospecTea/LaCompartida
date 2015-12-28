<?php

class UserDAO extends AbstractDAO implements IUserDAO {

	public function getClass() {
		return 'User';
	}

	public function getCategory($id) {
		$Criteria = new Criteria($this->sesion);
		$user = $Criteria->add_select('usuario.id_usuario')
			->add_select('prm_categoria_usuario.glosa_categoria')
			->add_select('prm_categoria_usuario.glosa_categoria_lang')
			->add_from('usuario')
			->add_left_join_with('prm_categoria_usuario', 'usuario.id_categoria_usuario = prm_categoria_usuario.id_categoria_usuario')
			->add_restriction(CriteriaRestriction::equals('usuario.id_usuario', $id))
			->run();
		return $user[0];
	}

}
