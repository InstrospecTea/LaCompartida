<?php
namespace Api\V2;

/**
 *
 * Clase con métodos para Trabajos
 *
 */
class TimeEntriesAPI extends AbstractSlimAPI {

	static $TimeEntryEntity = array(
		'id',
		'date',
		'string_date',
		array('created_at' => 'creation_date'),
		array('string_created_at' => 'string_creation_date'),
		'duration',
		array('description' => 'notes'),
		'user_id',
		'billable',
		'visible',
		'read_only',
		array('client_id' => 'id_cliente'),
		array('project_id' => 'id_asunto'),
		array('activity_id' => 'id_actividad'),
		array('area_id' => 'id_area_trabajo'),
		array('task_id' => 'id_tarea'),
		'requester',
		'project'
	);

	/** Este codigo esta obsoleto, lo ocupé para avanzar pero debe ser pasado a business */
	public function getTimeEntriesByUserId($user_id) {
		$Session = $this->session;

	 if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new \Usuario($Session);
		$Work = new \Trabajo($Session);
		$works = array();

		$string_date = $this->params['date'];

		if (array_key_exists('string_date', $this->params)) {
			$string_date = strtotime($this->params['string_date']);
		}
		if (empty($string_date)) {
			$string_date = time();
		}

		$date = date('Y-m-d', $string_date);
		$fromDate = date('Y-m-d', strtotime('monday this week', strtotime($date)));
		$toDate = date('Y-m-d', strtotime('sunday this week', strtotime($date)));

		if (!$User->LoadId($user_id)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			$embed = $this->params['embed'];
			$includeProject = !empty($embed) && ($embed === 'project');
			$works = $Work->findAllWorksByUserId($user_id, $toDate, $fromDate, $includeProject);
		}

		$this->present($works, self::$TimeEntryEntity);
	}

	function activityCodeById($id) {
		$code = null;
		$element = new \Actividad($this->session);
		$element->Load($id);
		if ($element->Loaded()) {
			$code = $element->fields['codigo_actividad'];
		}
		return $code;
	}

	function matterCodeById($id) {
		$code = null;
		$element = new \Asunto($this->session);
		$element->Load($id);
		if ($element->Loaded()) {
			if (\Conf::GetConf($this->session, 'CodigoSecundario')) {
				$code = $element->fields['codigo_asunto_secundario'];
			} else {
				$code = $element->fields['codigo_asunto'];
			}
		}
		return $code;
	}

	/** Este codigo esta obsoleto, lo ocupé para avanzar pero debe ser pasado a business */
	public function createTimeEntryByUserId($user_id) {
		$Session = $this->session;

		if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new \Usuario($Session);
		$Work = new \Trabajo($Session);

		$work = array();
		$params = $this->params;

		$work['date'] = $params['date'];
		$work['created_date'] = empty($params['created_at']) ? $params['created_date'] : $params['created_at'];
		$work['duration'] = (float) $params['duration'];
		$work['notes'] = $params['description'];
		$work['rate'] = (float) $params['rate'];
		$work['requester'] = $params['requester'];
		$work['area_code'] = $params['area_id'];
		$work['task_code'] = $params['task_id'];
		$work['user_id'] = (int) $params['user_id'];
		$work['billable'] = (int) $params['billable'];
		$work['visible'] = (int) $params['visible'];

		if (!empty($params['activity_id'])) {
			$work['activity_code'] = $this->activityCodeById($params['activity_id']);
		}

		if (!empty($params['project_id'])) {
			$work['matter_code'] = $this->matterCodeById($params['project_id']);
		}

		// overrides with new attribute
		if (!empty($params['string_created_at'])) {
			$work['created_date'] = strtotime($params['string_created_at']);
		}

		// overrides with new attribute
		if (!empty($params['string_date'])) {
			$work['date'] = strtotime($params['string_date']);
		}

		if (empty($work['date'])) {
			$this->halt(__('The date format is incorrect'), 'InvalidDate');
		}

		if (empty($work['created_date'])) {
			$this->halt(__('The date format is incorrect'), 'InvalidCreationDate');
		}

		$work['date'] = date('Y-m-d H:i:s', $work['date']);
		$work['created_date'] = date('Y-m-d H:i:s', $work['created_date']);

		if (!is_null($work['duration'])) {
			$work['duration'] = date('H:i:s', mktime(0, $work['duration'], 0, 0, 0, 0));
		} else {
			$this->halt(__('The duration format is incorrect'), 'InvalidDuration');
		}

		if (!$User->LoadId($user_id)) {
			$this->alt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			$validate = $Work->validateDataOfWork($work);
			if ($validate['error'] == true) {
				$this->halt($validate['description'], 'ValidationError');
			} else {
				if (!$Work->save($work)) {
					if (!is_null($Work->error) && !empty($Work->error)) {
						$this->halt($Work->error, 'ValidationError');
					} else {
						$this->halt(__('Unexpected error when saving data'), 'UnexpectedSave');
					}
				} else {
					$work = $Work->findById($Work->fields['id_trabajo']);
				}
			}
		}

		$this->present($work, self::$TimeEntryEntity);
	}

	/** Este codigo esta obsoleto, lo ocupé para avanzar pero debe ser pasado a business */
	public function updateTimeEntryByUserId($user_id, $id) {
		$Session = $this->session;

		if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		if (is_null($id) || empty($id)) {
			$this->halt(__('Invalid work ID'), 'InvalidWorkID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new \Usuario($Session);
		$Work = new \Trabajo($Session);

		$work = array();
		$params = $this->params;

		$work['id'] = $id;
		$work['date'] = $params['date'];
		$work['created_date'] = empty($params['created_at']) ? $params['created_date'] : $params['created_at'];
		$work['duration'] = (float) $params['duration'];
		$work['notes'] = $params['description'];
		$work['rate'] = (float) $params['rate'];
		$work['requester'] = $params['requester'];
		$work['area_code'] = $params['area_id'];
		$work['task_code'] = $params['task_id'];
		$work['user_id'] = (int) $params['user_id'];
		$work['billable'] = (int) $params['billable'];
		$work['visible'] = (int) $params['visible'];

		if (!empty($params['activity_id'])) {
			$work['activity_code'] = $this->activityCodeById($params['activity_id']);
		}

		if (!empty($params['project_id'])) {
			$work['matter_code'] = $this->matterCodeById($params['project_id']);
		}

		// overrides with new attribute
		if (!empty($params['string_created_at'])) {
			$work['created_date'] = strtotime($params['string_created_at']);
		}

		// overrides with new attribute
		if (!empty($params['string_date'])) {
			$work['date'] = strtotime($params['string_date']);
		}

		$work['date'] = date('Y-m-d H:i:s', $work['date']);
		$work['created_date'] = date('Y-m-d H:i:s', $work['created_date']);

		if (!is_null($work['duration'])) {
			$work['duration'] = date('H:i:s', mktime(0, $work['duration'], 0, 0, 0, 0));
		} else {
			$this->halt(__('The duration format is incorrect'), 'InvalidDuration');
		}

		if (!$User->LoadId($user_id)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			if (!$Work->Load($id)) {
				$this->halt(__("The work doesn't exist"), 'WorkDoesntExist');
			} else {
				$validate = $Work->validateDataOfWork($work);
				if ($validate['error'] == true) {
					$this->halt($validate['description'], 'ValidationError');
				} else {
					if (!$Work->save($work)) {
						if (!is_null($Work->error) && !empty($Work->error)) {
							$this->halt($Work->error, 'ValidationError');
						} else {
							$this->halt(__('Unexpected error when saving data'), 'UnexpectedSave');
						}
					} else {
						$work = $Work->findById($Work->fields['id_trabajo']);
					}
				}
			}
		}

		$this->present($work, self::$TimeEntryEntity);
	}

	public function deleteTimeEntryByUserId($user_id, $id) {
		$Session = $this->session;

		if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		if (is_null($id) || empty($id)) {
			$this->halt(__('Invalid work ID'), 'InvalidWorkID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new \Usuario($Session);
		$Work = new \Trabajo($Session);

		if (!$User->LoadId($user_id)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			if (!$Work->Load($id)) {
				$this->halt(__("The work doesn't exist"), 'WorkDoesntExist');
			} else {
				if (!$Work->Eliminar()) {
					if (!is_null($Work->error) && !empty($Work->error)) {
						$this->halt($Work->error, 'ValidationError');
					} else {
						$this->halt(__('Unexpected error deleting data'), 'UnexpectedDelete');
					}
				}
			}
		}

		$this->outputJson(array('result' => 'OK'));
	}
}
