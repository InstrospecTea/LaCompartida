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

	outputJson(array('auth_token' => $auth_token));
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
		halt("Invalid code client");
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
	$response = array();
	$_app = Slim::getInstance();

	validateAuthTokenSendByHeaders();

	try {
		$db = getConnection();
		$client = array(
			'code' => 'C999666',
			'name' => 'LEMONTECH'
		);
		$response[] = $client;
	} catch(Exception $e) {
		$_app->halt(500, 'GET /translations | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/settings', function () {
	$response = array();
	$_app = Slim::getInstance();

	validateAuthTokenSendByHeaders();

	try {
		$db = getConnection();
		$client = array(
			'code' => 'C999666',
			'name' => 'LEMONTECH'
		);
		$response[] = $client;
	} catch(Exception $e) {
		$_app->halt(500, 'GET /settings | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/users/:id', function ($id) {
	$response = array();
	$_app = Slim::getInstance();

	validateAuthTokenSendByHeaders();

	try {
		$db = getConnection();
		$client = array(
			'id' => $id,
			'name' => 'LEMONTECH'
		);
		$response[] = $client;
	} catch(Exception $e) {
		$_app->halt(500, 'GET /users/:id | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/users/:id/works', function ($id) {
	$response = array();
	$_app = Slim::getInstance();

	validateAuthTokenSendByHeaders();

	$after = $_app->request()->params('after');
	$before = $_app->request()->params('before');

	try {
		$db = getConnection();
		$work = array(
			'id' => $id,
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
		$_app->halt(500, 'GET /users/:id/works | ' . $e->getMessage());
	}

	echo json_encode($response);
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
	array_walk_recursive($response, function(&$x) { $x = trim($x); });
	echo json_encode($response);
	exit;
}
