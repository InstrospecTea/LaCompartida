<?php
/**
 * Task
 * Description:
 *
 */
class Task extends Entity {

	/**
	 * Obtiene el nombre de la propiedad que acta como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return 'id_tarea';
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return 'tarea';
	}

	public function getTableDefaults() {
		return array();
	}

	protected function getFixedDefaults() {
		return array();
	}

}
