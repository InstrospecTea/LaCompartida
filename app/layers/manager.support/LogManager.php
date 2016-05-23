<?php

class LogManager extends AbstractManager implements ILogManager {

	public function getLogs($table_title, $id_field) {
		$this->loadService('LogDatabase');

		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('titulo_tabla', "'{$table_title}'"),
			CriteriaRestriction::equals('id_field', $id_field)
		);

		// Antes de retornar se debe humanizar.
		return $this->LogDatabaseService->findAll($restrictions, null, array('log_db.fecha DESC'));
	}
}
