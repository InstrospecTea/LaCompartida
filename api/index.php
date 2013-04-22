<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$app = new Slim();

define(MIN_TIMESTAMP, 315532800);
define(MAX_TIMESTAMP, 4182191999);

$app->post('/login', function () {
	$Session = new Sesion();
	$UserToken = new UserToken($Session);
	$Slim = Slim::getInstance();

	$user = $Slim->request()->params('user');
	$password = $Slim->request()->params('password');
	$app_key = $Slim->request()->params('app_key');
	$auth_token = $UserToken->makeAuthToken($user);

	if (is_null($user) || $user == '') {
		halt(__("Invalid user data"), "InvalidUserData");
	}

	if (is_null($password) || $password == '') {
		halt(__("Invalid password data"), "InvalidPasswordData");
	}

	if (is_null($app_key) || $app_key == '') {
		halt(__("Invalid application key data"), "InvalidAppKey");
	}

	if (!$Session->login($user, null, $password)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		$user_token_data = array(
			'user_id' => $Session->usuario->fields['id_usuario'],
			'auth_token' => $auth_token,
			'app_key' => $app_key
		);

		if (!$UserToken->save($user_token_data)) {
			halt(__("Unexpected error when saving data"), "UnexpectedSave");
		}
	}

	outputJson(array(
		'auth_token' => $auth_token,
		'user_id' => $Session->usuario->fields['id_usuario']
		)
	);
});

$app->get('/clients', function () {
	$Session = new Sesion();
	$Client = new Cliente($Session);

	$clients = array();
	$user_id = validateAuthTokenSendByHeaders();
	$clients = $Client->findAllActive();
	outputJson($clients);
});

$app->get('/clients/:code/matters', function ($code) {
	if (is_null($code) || $code == '') {
		halt(__("Invalid client code"), "InvalidClientCode");
	}
	$Session = new Sesion();
	$Client = new Cliente($Session);
	$Matter = new Asunto($Session);

	$matters = array();

	// validate client code
	if (UtilesApp::GetConf($Session, 'CodigoSecundario') == '1') {
		$client = $Client->LoadByCodigoSecundario($code);
	} else {
		$client = $Client->LoadByCodigo($code);
	}

	if ($client === false) {
		halt(__("The client doesn't exist"), "ClientDoesntExists");
	}

	$user_id = validateAuthTokenSendByHeaders();
	$matters = $Matter->findAllByClientCode($code);

	outputJson($matters);
});

$app->get('/matters', function () {
	$Session = new Sesion();
	$Matter = new Asunto($Session);

	$matters = array();

	$user_id = validateAuthTokenSendByHeaders();
	$matters = $Matter->findAllActive();

	outputJson($matters);
});

$app->get('/activities', function () {
	$Session = new Sesion();
	$Activity = new Actividad($Session);
	$activities = array();

	$user_id = validateAuthTokenSendByHeaders();
	$activities = $Activity->findAll();

	outputJson($activities);
});

$app->get('/areas', function () {
	$Session = new Sesion();
	$WorkArea = new AreaTrabajo($Session);
	$work_areas = array();

	$user_id = validateAuthTokenSendByHeaders();
	$work_areas = $WorkArea->findAll();

	outputJson($work_areas);
});

$app->get('/tasks', function () {
	$Session = new Sesion();
	$Task = new Tarea($Session);
	$tasks = array();

	$user_id = validateAuthTokenSendByHeaders();
	$tasks = $Task->findAll();

	outputJson($tasks);
});

$app->get('/translations', function () {
	$Session = new Sesion();
	$Translation = new Translation($Session);
	$translations = array();

	$user_id = validateAuthTokenSendByHeaders();
	$translation_files = $Translation->findAllActive();

	if (is_array($translation_files) && !empty($translation_files)) {
		$_LANG = array();
		foreach ($translation_files as $translation_file) {
			include Conf::ServerDir() . '/lang/' . $translation_file['file'];
		}
		if (is_array($_LANG) && !empty($_LANG)) {
			array_push($translations, array('code' => 'Matters', 'value' => $_LANG['Asuntos']));
			array_push($translations, array('code' => 'Works', 'value' => $_LANG['Trabajos']));
			array_push($translations, array('code' => 'Clients', 'value' => $_LANG['Clientes']));
		}
	}

	outputJson($translations);
});

$app->get('/settings', function () {
	$Session = new Sesion();
	$settings = array();

	$user_id = validateAuthTokenSendByHeaders();

	if (is_array($Session->arrayconf) && !empty($Session->arrayconf)) {
		if ($Session->arrayconf['Intervalo']) {
			array_push($settings, array('code' => 'IncrementalStep', 'value' => $Session->arrayconf['Intervalo']));
		}
	}

	outputJson($settings);
});

$app->get('/users/:id', function ($id) {
	if (is_null($id) || empty($id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	$Session = new Sesion();
	$User = new Usuario($Session);
	$user = array();

	//$user_id = validateAuthTokenSendByHeaders();
	if (!$User->LoadId($id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		$max_daily_minutes = method_exists('Conf','CantidadHorasDia') ? Conf::CantidadHorasDia() : 1439;
		$user = array(
			'id' => (int) $User->fields['id_usuario'],
			'code' => $User->fields['rut'],
			'name' => $User->fields['apellido1'] . ' ' . $User->fields['apellido2'] . ' ' . $User->fields['nombre'],
			'weekly_alert' => !empty($User->fields['alerta_semanal']) ? (int) $User->fields['alerta_semanal'] : null,
			'daily_alert' =>  !empty($User->fields['alerta_diaria']) ? (int) $User->fields['alerta_diaria'] : null,
			'min_daily_hours' => !empty($User->fields['restriccion_diario']) ? (float) $User->fields['restriccion_diario'] : null,
			'max_daily_hours' => (float) ($max_daily_minutes / 60.0),
			'min_weekly_hours' => !empty($User->fields['restriccion_min']) ? $User->fields['restriccion_min'] : null,
			'max_weekly_hours' => !empty($User->fields['restriccion_max']) ? $User->fields['restriccion_max'] : null,
		);
	}

	outputJson($user);
});

$app->get('/users/:id/works', function ($id) {
	if (is_null($id) || empty($id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	$user_id = validateAuthTokenSendByHeaders();

	$Session = new Sesion();
	$User = new Usuario($Session);
	$Work = new Trabajo($Session);
	$Slim = Slim::getInstance();
	$works = array();

	$before = $Slim->request()->params('before');
	$after = $Slim->request()->params('after');

	if (!is_null($before)) {
		$before = isValidTimeStamp($before) ? date('Y-m-d H:i:s', $before) : null;
	}

	if (!is_null($after)) {
		$after = isValidTimeStamp($after) ? date('Y-m-d H:i:s', $after) : null;
	}

	if (!$User->LoadId($id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		$works = $Work->findAllWorksByUserId($id, $before, $after);
	}

	outputJson($works);
});

$app->put('/users/:id/works', function ($id) {
	if (is_null($id) || empty($id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	$user_id = validateAuthTokenSendByHeaders();

	$Session = new Sesion();
	$User = new Usuario($Session);
	$Work = new Trabajo($Session);
	$Slim = Slim::getInstance();
	$work = array();

	$work['date'] = $Slim->request()->params('date');
	$work['created_date'] = $Slim->request()->params('created_date');
	$work['duration'] = (float) $Slim->request()->params('duration');
	$work['notes'] = $Slim->request()->params('notes');
	$work['rate'] = (float) $Slim->request()->params('rate');
	$work['requester'] = $Slim->request()->params('requester');

	$work['activity_code'] = $Slim->request()->params('activity_code');
	$work['area_code'] = $Slim->request()->params('area_code');
	$work['matter_code'] = $Slim->request()->params('matter_code');
	$work['task_code'] = $Slim->request()->params('task_code');
	$work['user_id'] = (int) $Slim->request()->params('user_id');
	$work['billable'] = (int) $Slim->request()->params('billable');
	$work['visible'] = (int) $Slim->request()->params('visible');

	if (!is_null($work['date']) && isValidTimeStamp($work['date'])) {
		$work['date'] = date('Y-m-d H:i:s', $work['date']);
	} else {
		halt(__("The date format is incorrect"), "InvalidDate");
	}

	if (!is_null($work['created_date']) && isValidTimeStamp($work['created_date'])) {
		$work['created_date'] = date('Y-m-d H:i:s', $work['created_date']);
	} else {
		halt(__("The created date format is incorrect"), "InvalidCreationDate");
	}

	if (!is_null($work['duration'])) {
		$work['duration'] = date('H:i:s', mktime(0, $work['duration'], 0, 0, 0, 0));
	} else {
		halt(__("The duration format is incorrect"), "InvalidDuration");
	}

	if (!$User->LoadId($id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		$validate = $Work->validateDataOfWork($work);
		if ($validate['error'] == true) {
			halt($validate['description'], "ValidationError");
		} else {
			if (!$Work->save($work)) {
				if (!is_null($Work->error) && !empty($Work->error)) {
					halt($Work->error, "ValidationError");
				} else {
					halt(__("Unexpected error when saving data"), "UnexpectedSave");
				}
			} else {
				$work = $Work->findById($Work->fields['id_trabajo']);
			}
		}
	}

	outputJson($work);
});

$app->post('/users/:user_id/works/:id', function ($user_id, $id) {
	if (is_null($user_id) || empty($user_id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	if (is_null($id) || empty($id)) {
		halt(__("Invalid work ID"), "InvalidWorkID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$Session = new Sesion();
	$User = new Usuario($Session);
	$Work = new Trabajo($Session);
	$Slim = Slim::getInstance();
	$work = array();

	$work['id'] = $id;
	$work['date'] = $Slim->request()->params('date');
	$work['duration'] = (float) $Slim->request()->params('duration');
	$work['notes'] = $Slim->request()->params('notes');
	$work['rate'] = (float) $Slim->request()->params('rate');
	$work['requester'] = $Slim->request()->params('requester');
	$work['activity_code'] = $Slim->request()->params('activity_code');
	$work['area_code'] = $Slim->request()->params('area_code');
	$work['matter_code'] = $Slim->request()->params('matter_code');
	$work['task_code'] = $Slim->request()->params('task_code');
	$work['user_id'] = (int) $user_id;
	$work['billable'] = (int) $Slim->request()->params('billable');
	$work['visible'] = (int) $Slim->request()->params('visible');

	if (!is_null($work['date']) && isValidTimeStamp($work['date'])) {
		$work['date'] = date('Y-m-d H:i:s', $work['date']);
	} else {
		halt(__("The date format is incorrect"), "InvalidDate");
	}

	if (!is_null($work['duration'])) {
		$work['duration'] = date('H:i:s', mktime(0, $work['duration'], 0, 0, 0, 0));
	} else {
		halt(__("The duration format is incorrect"), "InvalidDuration");
	}

	if (!$User->LoadId($user_id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		if (!$Work->Load($id)) {
			halt(__("The work doesn't exist"), "WorkDoesntExist");
		} else {
			$validate = $Work->validateDataOfWork($work);
			if ($validate['error'] == true) {
				halt($validate['description'], "ValidationError");
			} else {
				if (!$Work->save($work)) {
					if (!is_null($Work->error) && !empty($Work->error)) {
						halt($Work->error, "ValidationError");
					} else {
						halt(__("Unexpected error when saving data"), "UnexpectedSave");
					}
				} else {
					$work = $Work->findById($Work->fields['id_trabajo']);
				}
			}
		}
	}

	outputJson($work);
});

$app->delete('/users/:user_id/works/:id', function ($user_id, $id) {
	if (is_null($user_id) || empty($user_id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	if (is_null($id) || empty($id)) {
		halt(__("Invalid work ID"), "InvalidWorkID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$Session = new Sesion();
	$User = new Usuario($Session);
	$Work = new Trabajo($Session);

	if (!$User->LoadId($user_id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		if (!$Work->Load($id)) {
			halt(__("The work doesn't exist"), "WorkDoesntExist");
		} else {
			if (!$Work->Eliminar()) {
				if (!is_null($Work->error) && !empty($Work->error)) {
					halt($Work->error, "ValidationError");
				} else {
					halt(__("Unexpected error deleting data"), "UnexpectedDelete");
				}
			}
		}
	}

	outputJson("OK");

});

$app->run();

function validateAuthTokenSendByHeaders() {
	$Session = new Sesion();
	$UserToken = new UserToken($Session);
	$Slim = Slim::getInstance();
	$Request = $Slim->request();

	$auth_token = $Request->headers('AUTHTOKEN');
	$user_token_data = $UserToken->findByAuthToken($auth_token);

	// if not exist the auth_token then return error
	if (!is_object($user_token_data)) {
		halt(__('Invalid AUTH TOKEN'), "SecurityError");
	} else {
		return $user_token_data->user_id;
	}
}

function halt($error_message = null, $error_code = null, $halt_code = 400) {
	$errors = array();
	$Slim = Slim::getInstance();
	switch ($halt_code) {
		case 200:
			$Slim->halt(200);
			break;

		default:
			array_push($errors, array('message' => $error_message, 'code' => $error_code));
			$Slim->halt($halt_code, json_encode(array('errors' => $errors)));
			break;
	}
}

function outputJson($response) {
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header('Content-type: application/json; charset=utf-8');
	$response = UtilesApp::utf8izar($response);
	array_walk_recursive($response, function(&$x) { if (is_string($x)) $x = trim($x); });
	echo json_encode($response);
	exit;
}

function isValidTimeStamp($timestamp) {
	return ($timestamp >= MIN_TIMESTAMP)
	&& ($timestamp <= MAX_TIMESTAMP);
}
