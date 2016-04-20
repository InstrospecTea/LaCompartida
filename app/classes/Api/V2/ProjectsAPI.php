<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para Projectos
 *
 */
class ProjectsAPI extends AbstractSlimAPI {

	static $ProjectsEntity = array(
		array('id' => 'id_asunto'),
		array('code' => 'codigo_asunto'),
		array('name' => 'glosa_asunto'),
		array('active' => 'activo'),
		array('client_id' => 'id_cliente'),
		array('project_area_id' => 'id_area_proyecto'),
		array('project_type_id' => 'id_tipo_asunto'),
		array('language_code' => 'codigo_idioma'),
		array('language_name' => 'glosa_idioma')
	);

	public function getProjectsOfClient($client_id) {
		$Session = $this->session;
		$Slim = $this->slim;
		$this->validateAuthTokenSendByHeaders();

		if (empty($client_id)) {
			$this->halt(__('Invalid client code'), 'InvalidClientCode');
		}

		$Business = new \ClientsBusiness($this->session);
		$client = $Business->getClientById($client_id);

		if (is_null($client)) {
			$this->halt(__("The client doesn't exist"), 'ClientDoesntExists');
		}

		$results = $Business->getMattersOfClient($client);

		$this->present($results, self::$ProjectsEntity);
	}

	public function getUpdatedMatters() {
		$Session = $this->session;
		$Slim = $this->slim;
		$this->validateAuthTokenSendByHeaders();

		$client_id = $Slim->request()->params('client_id');
		if (!empty($client_id)) {
			$this->getProjectsOfClient($client_id);
		} else {
			$active = $Slim->request()->params('active');
			$updatedFrom = $Slim->request()->params('updated_from');

			if (!empty($updatedFrom) && !$this->isValidTimeStamp($updatedFrom)) {
				$this->halt(__('The date format is incorrect'), 'InvalidDate');
			}

			$Business = new \ClientsBusiness($this->session);
			$results = $Business->getUpdatedMatters($active, $updatedFrom);
			$this->present($results, self::$ProjectsEntity);
		}
	}
}
