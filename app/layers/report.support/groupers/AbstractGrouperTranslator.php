<?php

abstract class AbstractGrouperTranslator  implements IGrouperTranslator {

	private $Session;

	public function __construct(Sesion $Session) {
		$this->Session = $Session;
	}

	function getUserField() {
		return $this->getUserFieldBy('usuario');
	}

	function getUserAcountManagerField() {
		return $this->getUserFieldBy('usuario_responsable');
	}

	function getUserFieldBy($table) {
		if (Conf::GetConf($this->Session, 'UsaUsernameEnTodoElSistema')) {
			return "{$table}.username";
		}
		return "CONCAT_WS(' ', {$table}.nombre, {$table}.apellido1, LEFT({$table}.apellido2, 1))";
	}

	function getUndefinedField() {
		return "'Indefinido'";
	}

}