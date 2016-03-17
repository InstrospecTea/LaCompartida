<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para Tareas
 *
 */
class TasksAPI extends \AbstractSlimAPI {

	static $TasksEntity = array(
		array('id' => 'id_tarea'),
		array('client_id' => 'id_cliente'),
		array('project_id' => 'id_asunto'),
		array('name' => 'nombre'),
		array('description' => 'detalle'),
		array('priority' => 'prioridad'),
		array('status' => 'estado')
	);

	public function getUpdatedTasks() {
		$Slim = $this->slim;
		$this->validateAuthTokenSendByHeaders();

		$active = $Slim->request()->params('active');
		$updatedFrom = $Slim->request()->params('updated_from');

		if (!is_null($updatedFrom) && !$this->isValidTimeStamp($updatedFrom)) {
			$this->halt(__('The date format is incorrect'), 'InvalidDate');
		}

		$Business = new \TasksBusiness($this->session);
		$results = $Business->getUpdatedTasks($active, $updatedFrom);

		$this->present($results, self::$TasksEntity);
	}

}
