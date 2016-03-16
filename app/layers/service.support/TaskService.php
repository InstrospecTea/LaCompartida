<?php

class TaskService extends AbstractService implements ITaskService {

	public function getDaoLayer() {
		return 'TaskDAO';
	}

	public function getClass() {
		return 'Task';
	}

}
