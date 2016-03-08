<?php

/**
 *
 * Clase con métodos para Trabajos
 *
 */
class TimeEntriesAPI extends AbstractSlimAPI {

	public function getTimeEntriesByUserId($user_id) {
		$Session = $this->session;
		$Slim = $this->slim;

	 if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new Usuario($Session);
		$Work = new Trabajo($Session);

		$works = array();

		$before = $Slim->request()->params('before');
		$after = $Slim->request()->params('after');

		if (!is_null($before)) {
			$before = $this->isValidTimeStamp($before) ? date('Y-m-d H:i:s', $before) : null;
		}

		if (!is_null($after)) {
			$after = $this->isValidTimeStamp($after) ? date('Y-m-d H:i:s', $after) : null;
		}

		if (!$User->LoadId($user_id)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			$works = $Work->findAllWorksByUserId($user_id, $before, $after);

			if (!empty($works)) {
				$secondary_code = Conf::GetConf($Session, 'CodigoSecundario');
				for ($x = 0; $x < count($works); $x++) {
					if ($secondary_code) {
						$works[$x]['client_code'] = $works[$x]['secondary_client_code'];
						$works[$x]['matter_code'] = $works[$x]['secondary_matter_code'];
					}
					unset($works[$x]['secondary_client_code']);
					unset($works[$x]['secondary_matter_code']);
				}
			}
		}

		$this->outputJson($works);
	}

	public function createTimeEntryByUserId($user_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new Usuario($Session);
		$Work = new Trabajo($Session);

		$work = array();

		$params = array();
		if ($Slim->request()->params('date')) {
			$params['date'] = $Slim->request()->params('date');
			$params['created_date'] = $Slim->request()->params('created_date');
			$params['duration'] = $Slim->request()->params('duration');
			$params['notes'] = $Slim->request()->params('notes');
			$params['rate'] = $Slim->request()->params('rate');
			$params['requester'] = $Slim->request()->params('requester');
			$params['activity_code'] = $Slim->request()->params('activity_code');
			$params['area_code'] = $Slim->request()->params('area_code');
			$params['matter_code'] = $Slim->request()->params('matter_code');
			$params['task_code'] = $Slim->request()->params('task_code');
			$params['user_id'] = $Slim->request()->params('user_id');
			$params['billable'] = $Slim->request()->params('billable');
			$params['visible'] = $Slim->request()->params('visible');
		} else {
			$params = json_decode($Slim->request()->getBody(), true);
		}

		$work['date'] = $params['date'];
		$work['created_date'] = $params['created_date'];
		$work['duration'] = (float) $params['duration'];
		$work['notes'] = $params['notes'];
		$work['rate'] = (float) $params['rate'];
		$work['requester'] = $params['requester'];
		$work['activity_code'] = $params['activity_code'];
		$work['area_code'] = $params['area_code'];
		$work['matter_code'] = $params['matter_code'];
		$work['task_code'] = $params['task_code'];
		$work['user_id'] = (int) $params['user_id'];
		$work['billable'] = (int) $params['billable'];
		$work['visible'] = (int) $params['visible'];

		if (!is_null($work['date']) && $this->isValidTimeStamp($work['date'])) {
			$work['date'] = date('Y-m-d H:i:s', $work['date']);
		} else {
			$this->halt(__('The date format is incorrect'), 'InvalidDate');
		}

		if (!is_null($work['created_date']) && $this->isValidTimeStamp($work['created_date'])) {
			$work['created_date'] = date('Y-m-d H:i:s', $work['created_date']);
		} else {
			$this->halt(__('The created date format is incorrect'), 'InvalidCreationDate');
		}

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
					if (Conf::GetConf($Session, 'CodigoSecundario')) {
						$work['client_code'] = $work['secondary_client_code'];
						$work['matter_code'] = $work['secondary_matter_code'];
					}
					unset($work['secondary_client_code']);
					unset($work['secondary_matter_code']);
				}
			}
		}

		$this->outputJson($work);
	}

	public function updateTimeEntryByUserId($user_id, $id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		if (is_null($id) || empty($id)) {
			$this->halt(__('Invalid work ID'), 'InvalidWorkID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new Usuario($Session);
		$Work = new Trabajo($Session);

		$params = array();
		if ($Slim->request()->params('date')) {
			$params['date'] = $Slim->request()->params('date');
			$params['created_date'] = $Slim->request()->params('created_date');
			$params['duration'] = $Slim->request()->params('duration');
			$params['notes'] = $Slim->request()->params('notes');
			$params['rate'] = $Slim->request()->params('rate');
			$params['requester'] = $Slim->request()->params('requester');
			$params['activity_code'] = $Slim->request()->params('activity_code');
			$params['area_code'] = $Slim->request()->params('area_code');
			$params['matter_code'] = $Slim->request()->params('matter_code');
			$params['task_code'] = $Slim->request()->params('task_code');
			$params['user_id'] = $Slim->request()->params('user_id');
			$params['billable'] = $Slim->request()->params('billable');
			$params['visible'] = $Slim->request()->params('visible');
		} else {
			$params = json_decode($Slim->request()->getBody(), true);
		}

		$work = array();
		$work = array();
		$work['id'] = $id;
		$work['date'] = $params['date'];
		$work['duration'] = (float) $params['duration'];
		$work['notes'] = $params['notes'];
		$work['rate'] = (float) $params['rate'];
		$work['requester'] = $params['requester'];
		$work['activity_code'] = $params['activity_code'];
		$work['area_code'] = $params['area_code'];
		$work['matter_code'] = $params['matter_code'];
		$work['task_code'] = $params['task_code'];
		$work['user_id'] = (int) $params['user_id'];
		$work['billable'] = (int) $params['billable'];
		$work['visible'] = (int) $params['visible'];

		if (!is_null($work['date']) && $this->isValidTimeStamp($work['date'])) {
			$work['date'] = date('Y-m-d H:i:s', $work['date']);
		} else {
			$this->halt(__('The date format is incorrect'), 'InvalidDate');
		}

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

		$this->outputJson($work);
	}

	public function deleteTimeEntryByUserId($user_id, $id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		if (is_null($id) || empty($id)) {
			$this->halt(__('Invalid work ID'), 'InvalidWorkID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new Usuario($Session);
		$Work = new Trabajo($Session);

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
