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

	public function getMattersByClientCode() {
		$Session = $this->session;
		$Slim = $this->slim;

		$this->validateAuthTokenSendByHeaders();
		$Matter = new Asunto($Session);

		$code = $Slim->request()->params('client_code');
		if (!empty($code)) {
			if (Conf::GetConf($Session, 'CodigoSecundario') == '1') {
				$client = $Client->LoadByCodigoSecundario($code);
			} else {
				$client = $Client->LoadByCodigo($code);
			}

			if ($client === false) {
				$this->halt(__("The client doesn't exist"), 'ClientDoesntExists');
			}

			$matters = $Matter->findAllByClientCode($code);
		} else {
			$timestamp = $Slim->request()->params('timestamp');
			$include = $Slim->request()->params('include');
			$include_all = (!is_null($include) && $include == 'all');
			if (!is_null($timestamp) && !$this->isValidTimeStamp($timestamp)) {
				$this->halt(__('The date format is incorrect'), 'InvalidDate');
			}

			$matters = $Matter->findAllActive($timestamp, $include_all);
		}

		$this->outputJson($matters);
	}

}
