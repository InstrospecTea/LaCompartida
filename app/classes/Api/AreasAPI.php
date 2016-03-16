<?php
/**
 *
 * Clase con métodos para Areas
 *
 */
class AreasAPI extends AbstractSlimAPI {

	public function getAreas() {
		$Session = $this->session;
		$Slim = $this->slim;

		$this->validateAuthTokenSendByHeaders();

		$WorkArea = new AreaTrabajo($Session);
		$work_areas = $WorkArea->findAll();

		$this->outputJson($work_areas);
	}

}
