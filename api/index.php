<?php

require '../fw/classes/Slim/Slim.php';
require '../app/miconf.php';

$app = new Slim();

define('_AUTH_TOKEN', 'lemontech#99511620');
define('_USER', '99511620');
define('_PASSWORD', 'admin.asdwsx');
define('_SECURE_SITE', 'https://lemontech.thetimebilling.com');

$app->post('/login', function () {
	$_app = Slim::getInstance();
	$response = array();

	$user = $_app->request()->params('user');
	$password = $_app->request()->params('password');
	$secure_site = $_app->request()->params('secure_site');

	$auth_token = _AUTH_TOKEN;

	try {
		$db = getConnection();
		$response['auth_token'] = $auth_token;
	} catch(Exception $e) {
		$_app->halt(500, 'POST /login | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/clients', function () {
	$response = array();
	$_app = Slim::getInstance();

	validateAuthTokenSendByHeaders();

	try {
		$db = getConnection();
		$client = array(
			'code' => 'C666',
			'name' => 'LEMONTECH',
			'address' => 'AV TECNOLIMON'
		);
		$response[] = $client;
	} catch(Exception $e) {
		$_app->halt(500, 'GET /clients | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/clients/:code/matters', function ($code) {
	$response = array();
	$_app = Slim::getInstance();

	validateAuthTokenSendByHeaders();

	try {
		$db = getConnection();
		$client = array(
			'code' => $code,
			'name' => 'LEMONTECH'
		);
		$response[] = $client;
	} catch(Exception $e) {
		$_app->halt(500, 'GET /clients/:code/matters | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/matters', function () {
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
		$_app->halt(500, 'GET /matters | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/activities', function () {
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
		$_app->halt(500, 'GET /activities | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/areas', function () {
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
		$_app->halt(500, 'GET /areas | ' . $e->getMessage());
	}

	echo json_encode($response);
});

$app->get('/tasks', function () {
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
		$_app->halt(500, 'GET /tasks | ' . $e->getMessage());
	}

	echo json_encode($response);
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

function getConnection() {
	$dbhost = DBHOST;
	$dbuser = DBUSER;
	$dbpass = DBPASS;
	$dbname = DBNAME;
	try {
		$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	} catch(PDOException $e) {
		$_app = Slim::getInstance();
		$_app->halt(500, 'Error connection MySQL | ' . $e->getMessage());
	}
}

function validateAuthTokenSendByHeaders() {
	$_app = Slim::getInstance();
	$_req = $_app->request();
	$auth_token = $_req->headers('AUTH_TOKEN');

	if ($auth_token != _AUTH_TOKEN) {
		$_app->halt(500, 'Error invalid AUTH_TOKEN');
	}
}

?>
