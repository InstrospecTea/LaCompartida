<?php

abstract class AbstractGrouperTranslator  implements IGrouperTranslator {

	private $Session;

	public function __construct(Sesion $Session) {
		$this->Session = $Session;
	}

	function getUserField() {
		if (Conf::GetConf($this->Session, 'UsaUsernameEnTodoElSistema')) {
			return "usuario.username";
		}
		return "CONCAT_WS(' ', usuario.nombre, usuario.apellido1, LEFT(usuario.apellido2, 1))";
	}

	function getUndefinedField() {
		return "'Indefinido'";
	}

}