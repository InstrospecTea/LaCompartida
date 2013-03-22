<?php
require_once dirname(__FILE__) . '/../fw/classes/Slim/Slim.php';
require_once dirname(__FILE__) . '/../app/conf.php';

$app = new Slim();

$app->post('/login', function () {
	$_app = Slim::getInstance();
	$_db = getConnection();
	$response = array();

	$user = $_app->request()->params('user');
	$password = $_app->request()->params('password');
	$app_key = $_app->request()->params('app_key');

	$password_encryption = md5($password);
	$auth_token = makeAuthToken($user);

	$sql = "SELECT `user`.id_usuario AS `id` FROM `usuario` AS `user` WHERE `user`.`rut`=:user AND `user`.`password`=:password";
	$_stmt = $_db->prepare($sql);
	$_stmt->bindParam('user', $user);
	$_stmt->bindParam('password', $password_encryption);
	$_stmt->execute();
	$user_data = $_stmt->fetchObject();

	if (is_object($user_data)) {
		$sql = "SELECT `user_token`.`id` FROM `user_token` WHERE `user_token`.`id`=:id";
		$_stmt = $_db->prepare($sql);
		$_stmt->bindParam('id', $user_data->id);
		$_stmt->execute();
		$user_token_data = $_stmt->fetchObject();

		// if exist the auth_token then replace for the new one
		if (is_object($user_token_data)) {
			$sql = "UPDATE `user_token` SET `user_token`.`auth_token`=:auth_token, `user_token`.`modified`=:modified WHERE `user_token`.`id`=:id";
			$_stmt = $_db->prepare($sql);
			$_stmt->bindParam('auth_token', $auth_token);
			$_stmt->bindParam('id', $user_data->id);
			$_stmt->bindParam('modified', date('Y-m-d H:i:s'));
			$_stmt->execute();
		} else {
			// if not exist then create the auth_token
			$sql = "INSERT INTO `user_token` SET `user_token`.`id`=:id, `user_token`.`auth_token`=:auth_token, `user_token`.`app_key`=:app_key, `user_token`.`created`=:created";
			$_stmt = $_db->prepare($sql);
			$_stmt->bindParam('id', $user_data->id);
			$_stmt->bindParam('auth_token', $auth_token);
			$_stmt->bindParam('app_key', $app_key);
			$_stmt->bindParam('created', date('Y-m-d H:i:s'));
			$_stmt->execute();
		}

		$response['auth_token'] = $auth_token;
	} else {
		halt("The user doesn't exist");
	}

	outputJson($response);
});

$app->get('/clients', function () {
	$response = array();
	$_app = Slim::getInstance();
	$_db = getConnection();

	$user_id = validateAuthTokenSendByHeaders();
	$active = 1;

	$sql = "SELECT `client`.`codigo_cliente` AS `code`, `client`.`glosa_cliente` AS `name`, `contract`.`direccion_contacto` AS `address` FROM `cliente` AS `client` INNER JOIN `contrato` AS `contract` ON `contract`.`id_contrato`=`client`.`id_contrato` WHERE `client`.`activo`=:active ORDER BY `client`.`glosa_cliente`";
	$_stmt = $_db->prepare($sql);
	$_stmt->bindParam('active', $active);
	$_stmt->execute();

	while ($client_data = $_stmt->fetch(PDO::FETCH_OBJ)) {
		$client = array(
			'code' => utf8_encode($client_data->code),
			'name' => utf8_encode($client_data->name),
			'address' => utf8_encode(trim($client_data->address))
		);
		array_push($response, $client);
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
		halt('Error connection MySQL > ' . $e->getMessage());
	}
}

function validateAuthTokenSendByHeaders() {
	$_db = getConnection();
	$_app = Slim::getInstance();
	$_req = $_app->request();

	$auth_token = $_req->headers('AUTH_TOKEN');

	$sql = "SELECT `user_token`.`id` FROM `user_token` WHERE `user_token`.`auth_token`=:auth_token";
	$_stmt = $_db->prepare($sql);
	$_stmt->bindParam('auth_token', $auth_token);
	$_stmt->execute();
	$user_token_data = $_stmt->fetchObject();

	// if not exist the auth_token then return error
	if (!is_object($user_token_data)) {
		halt('Invalid AUTH_TOKEN');
	} else {
		return $user_token_data->id;
	}
}

function makeAuthToken($secret) {
  $str = '';
  for ($i = 0; $i < 7; $i++) {
  	$str .= randAlphanumeric();
  }
  $pos = rand(0, 24);
  $str .= chr(65 + $pos);
  $str .= substr(md5($str . $secret), $pos, 8);
  return sha1($str);
}

function randAlphanumeric() {
	$subsets[0] = array('min' => 48, 'max' => 57); // ascii digits
	$subsets[1] = array('min' => 65, 'max' => 90); // ascii lowercase English letters
	$subsets[2] = array('min' => 97, 'max' => 122); // ascii uppercase English letters
	// random choice between lowercase, uppercase, and digits
	$s = rand(0, 2);
	$ascii_code = rand($subsets[$s]['min'], $subsets[$s]['max']);
	return chr($ascii_code);
}

function halt($message = null, $code = 500) {
	$_app = Slim::getInstance();
	$_req = $_app->request();
	$_app->halt($code, $_app->request()->getMethod() . ' | ' . $_req->getPath() . ' | ' . $message);
}

function outputJson($response) {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');

	//TODO: apply utf8 to array

	echo json_encode($response);
	exit;
}
