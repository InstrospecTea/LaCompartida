<?php

/**
 * Abstracto para traducir keys de grupos en campos de tablas
 */
abstract class AbstractGrouperTranslator  implements IGrouperTranslator {

	private $Session;

	/**
	 * Constructor
	 * @param Sesion $Session La sessión para acceso a datos y configuración
	 */
	public function __construct(Sesion $Session) {
		$this->Session = $Session;
	}

	/**
 * Obtiene la coluna correcta para código asunto
 * @return String Campo Codigo de asunto
 */
	function getProjectCodeField() {
	if (Conf::GetConf($this->Session, 'CodigoSecundario')) {
			return 'asunto.codigo_asunto_secundario';
		} else {
			return 'asunto.codigo_asunto';
		}
	}

	/**
	 * Obtiene el campo de la tabla de usuarios correspondiente para Usuario
	 * @return String
	 */
	function getUserField() {
		return $this->getUserFieldBy('usuario');
	}

	/**
	 * Obtiene el campo de la tabla de usuarios correspondiente para Usuario Encargado
	 * @return String
	 */
	function getUserAcountManagerField() {
		return $this->getUserFieldBy('usuario_responsable');
	}

	/**
	 * Dependiendo de la configuración UsaUsernameEnTodoElSistema devuelve
	 * username o una concatenación del nombre y apellidos para agrupar
	 * @return String
	 */
	function getUserFieldBy($table) {
		if (Conf::GetConf($this->Session, 'UsaUsernameEnTodoElSistema')) {
			return "{$table}.username";
		}
		return "CONCAT_WS(' ', {$table}.nombre, {$table}.apellido1, LEFT({$table}.apellido2, 1))";
	}

	/**
	 * Devuelve el campo "Indefinido" para grupos.
	 * @return String
	 */
	function getUndefinedField() {
		return "'Indefinido'";
	}
}
