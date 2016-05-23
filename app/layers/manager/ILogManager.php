<?php

interface ILogManager extends BaseManager {
	/**
	 * Obtiene la bitácora de una tabla en cierto movimiento
	 * @param 	string $table_title
	 * @param 	string $id_field
	 * @return 	Array humanizado
	 */
	public function getLogs($table_title, $id_field)
}
