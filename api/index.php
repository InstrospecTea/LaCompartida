<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$app = new Slim();

$app->post('/login', function () {
	$Session = new Sesion();
	$UserToken = new UserToken($Session);
	$Slim = Slim::getInstance();

	$user = $Slim->request()->params('user');
	$password = $Slim->request()->params('password');
	$app_key = $Slim->request()->params('app_key');
	$auth_token = $UserToken->makeAuthToken($user);

	if (!$Session->login($user, null, $password)) {
		halt("The user doesn't exist");
	} else {
		$user_token_data = array(
			'id' => $Session->usuario->fields['id_usuario'],
			'auth_token' => $auth_token,
			'app_key' => $app_key
		);

		if (!$UserToken->save($user_token_data)) {
			halt("Unexpected error when saving data");
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
		halt("Invalid client code");
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
		halt("The client doesn't exist");
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
			foreach ($_LANG as $key => $value) {
				array_push($translations, array('code' => $key, 'value' => $value));
			}
		}
	}

	outputJson($translations);
});

$app->get('/settings', function () {
	$Session = new Sesion();
	$settings = array();

	$user_id = validateAuthTokenSendByHeaders();

	if (is_array($Session->arrayconf) && !empty($Session->arrayconf)) {
		foreach ($Session->arrayconf as $key => $value) {
			array_push($settings, array('code' => $key, 'value' => $value));
		}
	}

	outputJson($settings);
});

$app->get('/users/:id', function ($id) {
	if (is_null($id) || empty($id)) {
		halt("Invalid user ID");
	}

	$Session = new Sesion();
	$User = new Usuario($Session);
	$user = array();

	//$user_id = validateAuthTokenSendByHeaders();

	if (!$User->LoadId($id)) {
		halt("The user doesn't exist");
	} else {
		$user = array(
			'id' => (int) $User->fields['id_usuario'],
			'code' => $User->fields['rut'],
			'name' => $User->fields['apellido1'] . ' ' . $User->fields['apellido2'] . ' ' . $User->fields['nombre']
		);

	}

	outputJson($user);
});

$app->get('/users/:id/works', function ($id) {
	if (is_null($id) || empty($id)) {
		halt("Invalid user ID");
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
		halt("The user doesn't exist");
	} else {
		$works = $Work->findAllWorksByUserId($id, $before, $after);
	}

	outputJson($works);
});

$app->put('/users/:id/works', function ($id) {
	if (is_null($id) || empty($id)) {
		halt("Invalid user ID");
	}

	$user_id = validateAuthTokenSendByHeaders();

	$Session = new Sesion();
	$User = new Usuario($Session);
	$Work = new Trabajo($Session);
	$Slim = Slim::getInstance();
	$work = array();

	$work['date'] = $Slim->request()->params('date');
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

	if (!is_null($work['date']) || !isValidTimeStamp($work['date'])) {
		$work['date'] = date('Y-m-d H:i:s', $work['date']);
	} else {
		halt("The date format is incorrect");
	}

	if (!is_null($work['duration'])) {
		$work['duration'] = date('H:i:s', mktime(0, $work['duration'], 0, 0, 0, 0));
	} else {
		halt("The duration format is incorrect");
	}

	if (!$User->LoadId($id)) {
		halt("The user doesn't exist");
	} else {
		$validate = $Work->validateDataOfWork($work);
		if ($validate['error'] == true) {
			halt($validate['description']);
		} else {
			if (!$Work->save($work)) {
				halt($validate['description']);
			} else {
				$work['id'] = $Work->fields['id_trabajo'];
			}
		}
	}

	outputJson($work);
});

$app->run();

function validateAuthTokenSendByHeaders() {
	$Session = new Sesion();
	$UserToken = new UserToken($Session);
	$Slim = Slim::getInstance();
	$Request = $Slim->request();

	$auth_token = $Request->headers('AUTH_TOKEN');
	$user_token_data = $UserToken->findByAuthToken($auth_token);

	// if not exist the auth_token then return error
	if (!is_object($user_token_data)) {
		halt('Invalid AUTH_TOKEN');
	} else {
		return $user_token_data->id;
	}
}

function halt($error_message = null, $error_code = null, $halt_code = 400) {
	$errors = array();
	$Slim = Slim::getInstance();
	array_push($errors, array('message' => $error_message, 'code' => $error_code));
	$Slim->halt($halt_code, json_encode(array('errors' => $errors)));
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
	return ((string) (int) $timestamp === $timestamp)
	&& ($timestamp <= PHP_INT_MAX)
	&& ($timestamp >= ~PHP_INT_MAX);
}
