<?php

/**
 *
 * Clase con métodos para Actividades
 *
 */
class ActivitiesAPI extends AbstractSlimAPI {

	static $ActivitiesWithProjectEntity = array(
		array('id' => 'id_actividad'),
		array('code' => 'codigo_actividad'),
		array('name' => 'glosa_actividad'),
		array('active' => 'activo'),
		array('project_id' => 'id_asunto')
	);

	public function getActivities() {
		$Session = $this->session;
		$Slim = $this->slim;

		$this->validateAuthTokenSendByHeaders();

		$include = $Slim->request()->params('include');
		$include_all = (!is_null($include) && $include == 'all');

		$include_all = false;

		$Activity = new Actividad($Session);
		$activities = $Activity->findAll($include_all);

		$this->outputJson($activities);
	}

	public function getAllActivitiesByProjectId() {
		$Slim = $this->slim;
		// $this->validateAuthTokenSendByHeaders();
		$ActivitiesBusiness = new ActivitiesBusiness($this->session);

		$projectId = $Slim->request()->params('project_id');
		$active = $Slim->request()->params('active');

		$activities = $ActivitiesBusiness->getActivitesByMatterId($projectId, $active);

		$this->present($activities, self::$ActivitiesWithProjectEntity);
	}

}
