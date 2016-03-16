<?php
/**
 *
 * Clase con métodos para Actividades
 *
 */
class ActivitiesAPI extends AbstractSlimAPI {

	public function getActivities() {
		$Session = $this->session;
		$Slim = $this->slim;

		$this->validateAuthTokenSendByHeaders();

		$include = $Slim->request()->params('include');
		$include_all = (!is_null($include) && $include == 'all');

		$include_all = false;

		$Activity = new \Actividad($Session);
		$activities = $Activity->findAll($include_all);

		$this->outputJson($activities);
	}

}
