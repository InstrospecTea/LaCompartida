<?php

class TaskManager extends AbstractManager implements BaseManager {

	/**
	 * Cambia el cliente de las tareas, luego de haber cambiado el asunto de cliente
	 * @param type $new_matter_code
	 * @param type $client_code
	 * @param type $new_client_code
	 * @throws Exception
	 */
  public function fixClient($new_matter_code, $client_code, $new_client_code) {
		$Criteria = new Criteria($this->Sesion);
		$Criteria->add_from('tarea')
			->add_select('id_tarea')
			->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'$new_matter_code'"))
			->add_restriction(CriteriaRestriction::equals('codigo_cliente', "'$client_code'"));
		$tasks = $Criteria->run();
		$total_tasks = count($tasks);
		for ($i = 0; $i < $total_tasks; ++$i) {
			$task_id = $tasks[$i]['id_tarea'];
			$Tarea = $this->loadModel('Tarea', null, true);
			$Tarea->Load($task_id);
			$Tarea->Edit('codigo_cliente', $new_client_code);
			if (!$Tarea->Write()) {
				throw new Exception("No se pudo cambiar el cliente del adelanto {$task_id}");
			}
		}
	}
}
