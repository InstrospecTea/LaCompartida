<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$Slim = new Slim();
$Session = new Sesion();

define(MIN_TIMESTAMP, 315532800);
define(MAX_TIMESTAMP, 4182191999);

// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT');

$Slim->post('/login', function () use ($Session, $Slim) {
	$UserToken = new UserToken($Session);

	$user = $Slim->request()->params('user');
	$password = $Slim->request()->params('password');
	$app_key = $Slim->request()->params('app_key');
	$auth_token = $UserToken->makeAuthToken($user);
	$dv = null;
	if (is_null($user) || $user == '') {
		halt(__("Invalid user data"), "InvalidUserData");
	}

	if (is_null($password) || $password == '') {
		halt(__("Invalid password data"), "InvalidPasswordData");
	}

	if (is_null($app_key) || $app_key == '') {
		halt(__("Invalid application key data"), "InvalidAppKey");
	}

	if (strtolower(UtilesApp::Getconf($Session, 'NombreIdentificador')) == 'rut') {
		$user_array =	preg_split('/[-]/', $user);
		$user = $user_array[0];
		if (count($user_array) == 2) {
			$dv = $user_array[1];
		}
	}

	if (!$Session->login($user, $dv, $password)) {
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

	outputJson(
		array(
			'auth_token' => $auth_token,
			'user_id' => $Session->usuario->fields['id_usuario']
		)
	);
});

$Slim->get('/clients', function () use ($Session, $Slim) {
	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$timestamp = $Slim->request()->params('timestamp');

	if (!is_null($timestamp) && !isValidTimeStamp($timestamp)) {
		halt(__("The date format is incorrect"), "InvalidDate");
	}

	$Client = new Cliente($Session);
	$clients = $Client->findAllActive($timestamp);

	outputJson($clients);
});

$Slim->get('/clients/:code/matters', function ($code) use ($Session) {
	if (is_null($code) || $code == '') {
		halt(__("Invalid client code"), "InvalidClientCode");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

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

	$matters = $Matter->findAllByClientCode($code);

	outputJson($matters);
});

$Slim->get('/matters', function () use ($Session, $Slim) {
	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$timestamp = $Slim->request()->params('timestamp');

	if (!is_null($timestamp) && !isValidTimeStamp($timestamp)) {
		halt(__("The date format is incorrect"), "InvalidDate");
	}

	$Matter = new Asunto($Session);
	$matters = $Matter->findAllActive($timestamp);

	outputJson($matters);
});

$Slim->get('/activities', function () use ($Session) {
	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$Activity = new Actividad($Session);
	$activities = $Activity->findAll();

	outputJson($activities);
});

$Slim->get('/areas', function () use ($Session) {
	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$WorkArea = new AreaTrabajo($Session);
	$work_areas = $WorkArea->findAll();

	outputJson($work_areas);
});

$Slim->get('/tasks', function () use ($Session) {
	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$Task = new Tarea($Session);
	$tasks = $Task->findAll();

	outputJson($tasks);
});

$Slim->get('/translations', function () use ($Session) {
	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$translations = array();
	array_push($translations, array('code' => 'Matters', 'value' => __('Asuntos')));
	array_push($translations, array('code' => 'Works', 'value' => __('Trabajos')));
	array_push($translations, array('code' => 'Clients', 'value' => __('Clientes')));

	outputJson($translations);
});

$Slim->get('/settings', function () use ($Session) {
	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$settings = array();

	if (is_array($Session->arrayconf) && !empty($Session->arrayconf)) {
		if ($Session->arrayconf['Intervalo']) {
			array_push($settings, array('code' => 'IncrementalStep', 'value' => $Session->arrayconf['Intervalo']));
		}

		if ($Session->arrayconf['CantidadHorasDia']) {
			array_push($settings, array('code' => 'TotalDailyTime', 'value' => $Session->arrayconf['CantidadHorasDia']));
		}

		if ($Session->arrayconf['UsarAreaTrabajos']) {
			array_push($settings, array('code' => 'UseWorkingAreas', 'value' => $Session->arrayconf['UsarAreaTrabajos']));
		}

		if ($Session->arrayconf['UsoActividades']) {
			array_push($settings, array('code' => 'UseActivities', 'value' => $Session->arrayconf['UsoActividades']));
		}

		if ($Session->arrayconf['GuardarTarifaAlIngresoDeHora']) {
			array_push($settings, array('code' => 'UseWorkRate', 'value' => $Session->arrayconf['GuardarTarifaAlIngresoDeHora']));
		}

		if ($Session->arrayconf['OrdenadoPor']) {
			array_push($settings, array('code' => 'UseRequester', 'value' => $Session->arrayconf['OrdenadoPor']));
		}

		if ($Session->arrayconf['TodoMayuscula']) {
			array_push($settings, array('code' => 'UseUppercase', 'value' => $Session->arrayconf['TodoMayuscula']));
		}

		if ($Session->arrayconf['PermitirCampoCobrableAProfesional']) {
			array_push($settings, array('code' => 'AllowBillable', 'value' => $Session->arrayconf['PermitirCampoCobrableAProfesional']));
		}

		if ($Session->arrayconf['MaxDuracionTrabajo']) {
			array_push($settings, array('code' => 'MaxWorkDuration', 'value' => $Session->arrayconf['MaxDuracionTrabajo']));
		}
	}

	outputJson($settings);
});

$Slim->get('/users/:id', function ($id) use ($Session) {
	if (is_null($id) || empty($id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$User = new Usuario($Session);
	$user = array();

	if (!$User->LoadId($id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		$max_daily_minutes = method_exists('Conf','CantidadHorasDia') ? Conf::CantidadHorasDia() : 1439;
		$user = array(
			'id' => (int) $User->fields['id_usuario'],
			'code' => $User->fields['rut'],
			'name' => $User->fields['nombre'] . ' ' . $User->fields['apellido1'] . ' ' . $User->fields['apellido2'],
			'weekly_alert' => !empty($User->fields['alerta_semanal']) ? (int) $User->fields['alerta_semanal'] : null,
			'daily_alert' =>  !empty($User->fields['alerta_diaria']) ? (int) $User->fields['alerta_diaria'] : null,
			'min_daily_hours' => !empty($User->fields['restriccion_diario']) ? (float) $User->fields['restriccion_diario'] : null,
			'max_daily_hours' => (float) ($max_daily_minutes / 60.0),
			'min_weekly_hours' => !empty($User->fields['restriccion_min']) ? $User->fields['restriccion_min'] : null,
			'max_weekly_hours' => !empty($User->fields['restriccion_max']) ? $User->fields['restriccion_max'] : null,
			'days_track_works' => !empty($User->fields['dias_ingreso_trabajo']) ? $User->fields['dias_ingreso_trabajo'] : null,
			'receive_alerts' => !empty($User->fields['receive_alerts']) ? $User->fields['receive_alerts'] : 0,
			'alert_hour' => !empty($User->fields['alert_hour']) ? time2seconds($User->fields['alert_hour']) : 0
		);
	}

	outputJson($user);
});

$Slim->get('/users/:id/works', function ($id) use ($Session, $Slim) {
	if (is_null($id) || empty($id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$User = new Usuario($Session);
	$Work = new Trabajo($Session);

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

$Slim->put('/users/:id/works', function ($id) use ($Session, $Slim) {
	if (is_null($id) || empty($id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$User = new Usuario($Session);
	$Work = new Trabajo($Session);

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

$Slim->post('/users/:user_id/works/:id', function ($user_id, $id) use ($Session, $Slim) {
	if (is_null($user_id) || empty($user_id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	if (is_null($id) || empty($id)) {
		halt(__("Invalid work ID"), "InvalidWorkID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$User = new Usuario($Session);
	$Work = new Trabajo($Session);

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

$Slim->delete('/users/:user_id/works/:id', function ($user_id, $id)  use ($Session) {
	if (is_null($user_id) || empty($user_id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	if (is_null($id) || empty($id)) {
		halt(__("Invalid work ID"), "InvalidWorkID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

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

	outputJson(array('result' => 'OK'));
});

$Slim->put('/users/:user_id/device', function ($user_id) use ($Session, $Slim) {
	if (is_null($user_id) || empty($user_id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$User = new Usuario($Session);
	$UserDevice = new UserDevice($Session);

	$device_token = $Slim->request()->params('token');
	$last_token = $Slim->request()->params('lastToken');
	$new_device = array();

	if (is_null($device_token) || empty($device_token)) {
		halt(__("Invalid token device"), "InvalidTokenDevice");
	}

	$new_device['user_id'] = $user_id;
	$new_device['token'] = $device_token;

	if (!$User->LoadId($user_id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		if (!is_null($last_token) && !empty($last_token)) {
			$UserDevice->updateInvalidToken($last_token, $device_token);
		}
		$device = $UserDevice->findByToken($device_token, $user_id);
		if (!is_object($device)){
			if (!$UserDevice->save($new_device)) {
				halt(__("Unexpected error when saving data"), "UnexpectedSave");
			} else {
				$device = $UserDevice->findByToken($device_token, $user_id);
			}
		}
	}

	outputJson($device);
});

$Slim->delete('/users/:user_id/device/:token', function ($user_id, $token) use ($Session) {
	if (is_null($user_id) || empty($user_id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	if (is_null($token) || empty($token)) {
		halt(__("Invalid token device"), "InvalidTokenDevice");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$User = new Usuario($Session);
	$UserDevice = new UserDevice($Session);

	if (!$User->LoadId($user_id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		$device = $UserDevice->findByToken($token, $user_id);
		if (is_object($device)) {
			if (!$UserDevice->delete($device->id)) {
				halt(__("Unexpected error deleting data"), "UnexpectedDelete");
			}
		} else {
			halt(__("The user device doesn't exist"), "UserDeviceDoesntExist");
		}
	}

	outputJson(array('result' => 'OK'));
});

$Slim->post('/users/:id', function ($id) use ($Session, $Slim) {
	if (is_null($id) || empty($id)) {
		halt(__("Invalid user ID"), "InvalidUserID");
	}

	$auth_token_user_id = validateAuthTokenSendByHeaders();

	$User = new Usuario($Session);
	$receive_alerts = (int) $Slim->request()->params('receive_alerts');
	$alert_hour = $Slim->request()->params('alert_hour');

	if (!$User->LoadId($id)) {
		halt(__("The user doesn't exist"), "UserDoesntExist");
	} else {
		$User->Edit('receive_alerts', $receive_alerts);
		$User->Edit('alert_hour', date('H:i:s', $alert_hour));

		if (!$User->Write()) {
			halt(__("Unexpected error when saving data"), "UnexpectedSave");
		}

		$max_daily_minutes = method_exists('Conf','CantidadHorasDia') ? Conf::CantidadHorasDia() : 1439;
		$user = array(
			'id' => (int) $User->fields['id_usuario'],
			'code' => $User->fields['rut'],
			'name' => $User->fields['nombre'] . ' ' . $User->fields['apellido1'] . ' ' . $User->fields['apellido2'],
			'weekly_alert' => !empty($User->fields['alerta_semanal']) ? (int) $User->fields['alerta_semanal'] : null,
			'daily_alert' =>  !empty($User->fields['alerta_diaria']) ? (int) $User->fields['alerta_diaria'] : null,
			'min_daily_hours' => !empty($User->fields['restriccion_diario']) ? (float) $User->fields['restriccion_diario'] : null,
			'max_daily_hours' => (float) ($max_daily_minutes / 60.0),
			'min_weekly_hours' => !empty($User->fields['restriccion_min']) ? $User->fields['restriccion_min'] : null,
			'max_weekly_hours' => !empty($User->fields['restriccion_max']) ? $User->fields['restriccion_max'] : null,
			'days_track_works' => !empty($User->fields['dias_ingreso_trabajo']) ? $User->fields['dias_ingreso_trabajo'] : null,
			'receive_alerts' => !empty($User->fields['receive_alerts']) ? $User->fields['receive_alerts'] : 0,
			'alert_hour' => !empty($User->fields['alert_hour']) ? time2seconds($User->fields['alert_hour']) : 0
		);

		outputJson($user);
	}
});

$Slim->post('/invoices/:id/build', function ($id) use ($Session, $Slim) {
	if (isset($id)) {
		$Invoice = new Factura($Session);
		$Invoice->Load($id);
		if (!$Invoice->Loaded()) {
			halt(__("Invalid invoice Number"), "InvalidInvoiceNumber");
		}	else {
			$data = array('Factura' => $Invoice, 'ExtraData' => 'TextoInvoice');
			$Slim->applyHook('hook_genera_factura_electronica', &$data);
			$error = $data['Error'];
			if ($error) {
				halt($error['Message'] ? $error['Message'] : __($error['Code']), $error['Code'], 400, $data['ExtraData']);
			} else {
				outputJson(array('invoice_url' => $data['InvoiceURL'], 'extra_data' => $data['ExtraData']));
			}
		}
	} else {
		halt(__("Invalid invoice Number"), "InvalidInvoiceNumber");
	}
});

$Slim->get('/invoices/:id/document', function ($id) use ($Session, $Slim) {
	$format = is_null($Slim->request()->params('format')) ? 'pdf' : $Slim->request()->params('format');
	if (isset($id)) {
		$Invoice = new Factura($Session);
		$Invoice->Load($id);
		if (!$Invoice->Loaded()) {
			halt(__("Invalid invoice Number"), "InvalidInvoiceNumber");
		}	else {
			if ($format == 'pdf') {
				$url = $Invoice->fields['dte_url_pdf'];
				$name = array_shift(explode('?', basename($url)));
				downloadFile($name, 'application/pdf', file_get_contents($url));
			} else {
				if ($format == 'xml') {
					downloadFile("invoice_$id.xml", 'text/xml', $Invoice->fields['dte_xml']);
				} else {
					halt(__("Invalid document format"), "InvalidDocumentFormat");
				}
			}
		}
	} else {
		halt(__("Invalid invoice Number"), "InvalidInvoiceNumber");
	}
});

$Slim->map('/release-list', function () use ($Session, $Slim) {
	$response = array(
		"success" => "true",
		"releases" => array(
			array(
				"version" => "2.1.3.GA",
				"manifest" => "MANIFEST CONTENTS OF UPDATED APP",
				"release_notes" => "COSITAS NUEVAS"
			),
			array(
				"version" => "2.1.4.GA",
				"manifest" => "MANIFEST CONTENTS OF UPDATED APP",
				"release_notes" => "COSITAS NUEVAS"
			)
		)
	);
	outputJson($response);

/*
mid - Ti.Platform.id (example: ‘841b4cfedc1d13e027bf0c56a0cb7d8e’)
limit - 1 (default in Client SDK)
guid - Ti.App.getGUID() (example: ‘0d776399-a82e-48c5-a711-5d0f158d4cfe’)
os - Ti.platform (example: ‘osx’)
ostype - Ti.Platform.ostype (example: ‘32bit’)*/

})->via('GET', 'POST');

$Slim->run();

function downloadFile($name, $type, $content) {
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: $type");
	header('Content-Description: File Transfer');
	header("Content-Disposition: attachment; filename=$name");
	echo $content;
}

function validateAuthTokenSendByHeaders() {
	global $Session, $Slim;

	$UserToken = new UserToken($Session);

	$Request = $Slim->request();
	$auth_token = $Request->headers('AUTHTOKEN');
	$user_token_data = $UserToken->findByAuthToken($auth_token);

	// if not exist the auth_token then return error
	if (!is_object($user_token_data)) {
		halt(__('Invalid AUTH TOKEN'), "SecurityError");
	} else {
		// verify if the token is expired
		// date_default_timezone_set("UTC");
		$now = time();
		$expiry_date = strtotime($user_token_data->expiry_date);
		if ($expiry_date < $now) {
			if ($UserToken->delete($user_token_data->id)) {
				halt(__('Expired AUTH TOKEN'), "SecurityError");
			} else {
				halt(__("Unexpected error deleting data"), "UnexpectedDelete");
			}
		}

		return $user_token_data->user_id;
	}
}

function halt($error_message = null, $error_code = null, $halt_code = 400, $data = '') {
	$errors = array();
	$Slim = Slim::getInstance();
	switch ($halt_code) {
		case 200:
			$Slim->halt(200);
			break;

		default:
			array_push($errors, array('message' => UtilesApp::utf8izar($error_message), 'code' => $error_code));
			$Slim->halt($halt_code, json_encode(array('errors' => $errors, 'extra_data' => $data)));
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

function time2seconds($time = '00:00:00') {
	list($hours, $mins, $secs) = explode(':', $time);
	return ($hours * 3600 ) + ($mins * 60 ) + $secs;
}

function isValidTimeStamp($timestamp) {
	return ($timestamp >= MIN_TIMESTAMP)
	&& ($timestamp <= MAX_TIMESTAMP);
}
