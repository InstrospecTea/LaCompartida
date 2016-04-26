<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para Actividades
 *
 */
class ActivitiesAPI extends AbstractSlimAPI {

	static $ActivityWithProjectEntity = array(
		array('id' => 'id_actividad'),
		array('code' => 'codigo_actividad'),
		array('name' => 'glosa_actividad'),
		array('active' => 'activo'),
		array('project_id' => 'id_asunto'),
		array('project_area_id' => 'id_area_proyecto'),
		array('project_type_id' => 'id_tipo_proyecto')
	);

	public function getAllActivitiesByProjectId() {
		$this->validateAuthTokenSendByHeaders();

		$ActivitiesBusiness = new \ActivitiesBusiness($this->session);

		$projectId = $this->params['project_id'];
		$active = $this->params['active'];
		$all = $this->params['all'];

		$allActivities = !empty($all);

		$activities = $ActivitiesBusiness->getActivitesByMatterId($projectId, $active, $allActivities);

		$this->present($activities, self::$ActivityWithProjectEntity);
	}

}
