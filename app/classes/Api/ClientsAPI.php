<?php
/**
 *
 * Clase con métodos para Clientes
 *
 */
class ClientsAPI extends AbstractSlimAPI {

	public function getClients() {
		$Session = $this->session;
		$Slim = $this->slim;

		$this->validateAuthTokenSendByHeaders();

		$timestamp = $Slim->request()->params('timestamp');
		$include = $Slim->request()->params('include');
		$include_all = (!is_null($include) && $include == 'all');

		if (!is_null($timestamp) && !$this->isValidTimeStamp($timestamp)) {
			$this->halt(__('The date format is incorrect'), 'InvalidDate');
		}

		$Client = new Cliente($Session);
		$clients = $Client->findAllActive($timestamp, $include_all);

		$this->outputJson($clients);
	}

	public function getMattersOfClient($code) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($code) || $code == '') {
			$this->halt(__('Invalid client code'), 'InvalidClientCode');
		}

		$this->validateAuthTokenSendByHeaders();

		$Client = new Cliente($Session);
		$Matter = new Asunto($Session);
		$matters = array();

		// validate client code
		if (Conf::GetConf($Session, 'CodigoSecundario') == '1') {
			$client = $Client->LoadByCodigoSecundario($code);
		} else {
			$client = $Client->LoadByCodigo($code);
		}

		if ($client === false) {
			$this->halt(__("The client doesn't exist"), 'ClientDoesntExists');
		}

		$matters = $Matter->findAllByClientCode($code);

		$this->outputJson($matters);
	}

}
