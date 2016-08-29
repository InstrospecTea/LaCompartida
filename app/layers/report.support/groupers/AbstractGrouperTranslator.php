<?php

/**
 * Abstracto para traducir keys de grupos en campos de tablas
 */
abstract class AbstractGrouperTranslator  implements IGrouperTranslator {

	private $Session;

	/**
	 * Constructor
	 * @param Sesion $Session La sessi�n para acceso a datos y configuraci�n
	 */
	public function __construct(Sesion $Session) {
		$this->Session = $Session;
	}

	/**
 * Obtiene la coluna correcta para c�digo cliente
 * @return String Campo Codigo de cliente
 */
	function getClientCodeField() {
	if (Conf::read('CodigoSecundario')) {
			return 'cliente.codigo_cliente_secundario';
		} else {
			return 'cliente.codigo_cliente';
		}
	}

	/**
 * Obtiene la coluna correcta para c�digo asunto
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
	 * Obtiene el campo de la tabla de usuarios correspondiente para Usuario Secundario
	 * @return String
	 */
	function getSecondUserAcountManagerField() {
		return $this->getUserFieldBy('usuario_secundario');
	}

	/**
	 * Dependiendo de la configuraci�n UsaUsernameEnTodoElSistema devuelve
	 * username o una concatenaci�n del nombre y apellidos para agrupar
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

	function addMatterCountSubcriteria($Criteria) {
		$SubCriteria = new Criteria();
		$SubCriteria->add_from('cobro_asunto')
			->add_select('id_cobro')
			->add_select('count(codigo_asunto)', 'total_asuntos')
			->add_grouping('id_cobro');

		$Criteria->add_left_join_with_criteria(
			$SubCriteria,
			'asuntos_cobro',
			CriteriaRestriction::equals('asuntos_cobro.id_cobro', 'cobro.id_cobro')
		);
	}
}
