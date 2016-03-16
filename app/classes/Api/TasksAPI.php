<?php
/**
 *
 * Clase con métodos para Tareas
 *
 */
class TasksAPI extends AbstractSlimAPI {

	public function getTasks() {
		$Session = $this->session;
		$Slim = $this->slim;

		$this->validateAuthTokenSendByHeaders();

		$Task = new Tarea($Session);
		$tasks = $Task->findAll();

		$this->outputJson($tasks);
	}

}
