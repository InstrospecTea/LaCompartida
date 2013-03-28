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

	$user_id = validateAuthTokenSendByHeaders();

	if (!$User->LoadId($id)) {
		halt("The user doesn't exist");
	} else {
		$user = array(
			'id' => (int) $User->fields['id_usuario'],
			'identification_number' => $User->fields['rut'],
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
	$response = array();
	$_app = Slim::getInstance();

	validateAuthTokenSendByHeaders();

	$creation_date = $_app->request()->params('creation_date');
	$date = $_app->request()->params('date');
	$duration = $_app->request()->params('duration');
	$notes = $_app->request()->params('notes');
	$rate = $_app->request()->params('rate');
	$read_only = $_app->request()->params('read_only');
	$requester = $_app->request()->params('requester');
	$activity_code = $_app->request()->params('activity_code');
	$area_code = $_app->request()->params('area_code');
	$client_code = $_app->request()->params('client_code');
	$matter_code = $_app->request()->params('matter_code');
	$task_code = $_app->request()->params('task_code');
	$user_id = $_app->request()->params('user_id');
	$billable = $_app->request()->params('billable');
	$visible = $_app->request()->params('visible');

	try {
		$db = getConnection();
		$work = array(
			'id' => 1,
			'creation_date' => strtotime(date('Y-m-d H:i:s')),
			'date' => strtotime(date('Y-m-d H:i:s')),
			'duration' => date('i'),
			'notes' => 'notes',
			'rate' => 1.1,
			'read_only' => 0,
			'requester' => 'requester',
			'activity_code' => 'C9090',
			'area_code' => 'C9090',
			'client_code' => 'C9090',
			'matter_code' => 'C9090',
			'task_code' => 'C9090',
			'user_id' => $id,
			'billable' => 1,
			'visible' => 1,
		);
		$response[] = $work;
	} catch(Exception $e) {
		$_app->halt(500, 'PUT /users/:id/works | ' . $e->getMessage());
	}

	echo json_encode($response);
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

function halt($message = null, $code = 500) {
	$Slim = Slim::getInstance();
	$Request = $Slim->request();
	$Slim->halt($code, $Slim->request()->getMethod() . ' | ' . $Request->getPath() . ' | ' . $message);
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
