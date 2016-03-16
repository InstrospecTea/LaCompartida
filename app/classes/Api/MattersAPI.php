<?php
/**
 *
 * Clase con métodos para Asuntos
 *
 */
class MattersAPI extends AbstractSlimAPI {

	public function getMatters() {
		$Session = $this->session;
		$Slim = $this->slim;

		$this->validateAuthTokenSendByHeaders();

		$timestamp = $Slim->request()->params('timestamp');
		$include = $Slim->request()->params('include');
		$include_all = (!is_null($include) && $include == 'all');
		if (!is_null($timestamp) && !$this->isValidTimeStamp($timestamp)) {
			$this->halt(__('The date format is incorrect'), 'InvalidDate');
		}

		$Matter = new Asunto($Session);
		$matters = $Matter->findAllActive($timestamp, $include_all);

		$this->outputJson($matters);
	}


}
