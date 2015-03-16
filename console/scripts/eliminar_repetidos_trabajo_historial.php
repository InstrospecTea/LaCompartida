<?php

/**
 * Clase EliminarRepetidosTrabajoHistorial
 * Realiza los registros de trabajo historial y elimina los repetidos o no modificados.
 */
class EliminarRepetidosTrabajoHistorial extends AppShell {

	private $fields = array(
		'id_trabajo',
		'accion',
		'id_trabajo_respaldo_excel',
		'fecha_trabajo',
		'fecha_trabajo_modificado',
		'descripcion',
		'descripcion_modificado',
		'duracion_cobrada',
		'duracion_cobrada_modificado',
		'id_usuario_trabajador',
		'id_usuario_trabajador_modificado',
		'duracion',
		'duracion_modificado',
		'codigo_asunto',
		'codigo_asunto_modificado',
		'cobrable',
		'cobrable_modificado',
		'tarifa_hh',
		'tarifa_hh_modificado'
	);

	private $fields_logable = array(
		'fecha_trabajo',
		'descripcion',
		'duracion_cobrada',
		'id_usuario_trabajador',
		'duracion',
		'codigo_asunto',
		'cobrable',
		'tarifa_hh'
	);


	private $id = 'id_trabajo_historial';
	private $ultimo_id = 0;
	private $primer_id = 0;

	public function main() {
		$this->out('Eliminando id_trabajo = 0');
		$query = "DELETE FROM trabajo_historial WHERE id_trabajo = 0";
		$this->Session->pdodbh->query($query);
		$this->out('- Finalizado');

		if (!empty($this->data['ultimo_id'])) {
			$this->ultimo_id = $this->data['ultimo_id'];
		}
		while (true) {
			if (!$this->revisar_historial()) {
				break;
			}
		}
	}

	/**
	 *
	 * @return boolean
	 * @throws Exception
	 */
	private function revisar_historial() {
		$this->out("Procesando desde {$this->ultimo_id}");
		$this->primer_id = $this->ultimo_id;
		try {
			$query = "SELECT * FROM trabajo_historial WHERE id_trabajo >= {$this->ultimo_id} ORDER BY id_trabajo ASC, {$this->id} LIMIT 10000";
			$historial = $this->Session->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
			if (empty($historial)) {
				throw new Exception('Nada que procesar');
			}

			$total_historial = count($historial);
			$this->out($total_historial);
			for ($x = 0; $x < $total_historial; ++$x) {
				$th1 = $historial[$x];
				if ($th1['id_trabajo'] == 0 || !$this->changed($th1)) {
					$this->delete($th1[$this->id]);
					continue;
				}
				$this->ultimo_id = $th1['id_trabajo'];
				while (true) {
					$th2 = $historial[$x + 1];
					if (($total_historial == $x + 1) || !$this->compare($th1, $th2) || $this->changed($th2)) {
						break;
					}
					$this->delete($th2[$this->id]);
					++$x;
				}
			}
		} catch (PDOException $e) {
			$this->out($e->getMessage());
			return false;
		} catch (Exception $e) {
			$this->out($e->getMessage());
			return false;
		}
		if ($this->primer_id == $this->ultimo_id) {
		//	return false;
		}
		return true;
	}

	private function delete($id) {
		try {
			$query = "DELETE FROM trabajo_historial WHERE {$this->id} = {$id}";
			$this->Session->pdodbh->query($query);
			$this->out("- $id Eliminado");
		} catch (PDOException $e) {
			$this->out('delete ' . $e->getMessage());
		} catch (Exception $e) {
			$this->out('delete' . $e->getMessage());
		}
	}

	/**
	 * Compara 2 registros según $this->fields devolviendo true si son iguales
	 * @param array $record1
	 * @param array $record2
	 * @return boolean
	 */
	private function compare($record1, $record2) {
		foreach ($this->fields as $field) {
			if ($record1[$field] !== $record2[$field]) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Revisa si el registro tiene modificaciones comparando <field> con <field>_modificado
	 * @param array $record
	 * @return boolean
	 */
	private function changed($record) {
		foreach($this->fields_logable as $field) {
			if ($record[$field] !== $record["{$field}_modificado"]) {
				return true;
			}
		}
		return false;
	}

}
