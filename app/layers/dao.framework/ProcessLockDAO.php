<?php

class ProcessLockDAO extends AbstractDAO implements IProcessLockDAO{

	public function getClass() {
		return 'ProcessLock';
	}

	/**
	 * Sobre escribe el método original para evitar la escritura de logs en db.
	 * @return boolean
	 */
	public function Write() {
		$id = $this->fields[$this->campo_id];
		$valores = array();
		if (empty($id)) {
			$query = "INSERT INTO {$this->tabla} SET ";
			$valores[] = "fecha_creacion = NOW()";
		} else {
			$query = "UPDATE {$this->tabla} SET ";
			$valores[] = "fecha_modificacion = NOW()";
		}
		foreach ($this->fields as $key => $value) {
			if ($this->campo_id != $key && $this->changes[$key]) {
				$value = mysql_real_escape_string($value);
				if ($value == 'NULL') {
					$valores[] = "$key = NULL";
				} else {
					$valores[] = "$key = '$value'";
				}
			}
		}

		$query .= implode(', ', $valores);

		if (!empty($id)) {
			$query .= " WHERE {$this->campo_id} = '{$id}'";
		}

		try {
			return $this->sesion->pdodbh->query($query);
		} catch (Exception $e) {
			Log::write('ProcessLockDAO: ' . $e->getMessaje(), Cobro::PROCESS_NAME);
		}
		return false;
	}

}