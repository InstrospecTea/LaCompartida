<?php
require_once dirname(__FILE__) . '/../../app/conf.php';

if (isset($_SERVER['HTTP_ORIGIN'])) {
	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	header('Access-Control-Allow-Credentials: true');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
	}

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
		header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
	}
}

$Slim = new \Slim();
$Session = new \Sesion();

$Slim->map(':x+', function($x) {
	$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
	header($protocol . ' 200 Ok');
})->via('OPTIONS');

/**
 * @api {post} /login User Login
 * @apiName Login
 * @apiVersion 2.0.0
 * @apiGroup Session
 * @apiDescription Authenticates a users credentials and returns an AUTHENTICATION TOKEN
 *
 * @apiParam {String} user Identification (Ex: 99511620-0)
 * @apiParam {String} password Password of user
 * @apiParam {String} app_key A key provided for the application that consumes the API.
 *
 * @apiParamExample Params-Example:
 *     {
 *       "user": "99511620-0",
 *       "password": "blabla",
 *       "app_key": "ttb-mobile"
 *     }
 *
 * @apiSuccess {String} auth_token Token for future authorization
 * @apiSuccess {String} user_id  The id of the user logged in
 *
 * @apiSuccessExample Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "auth_token": "136b17e3a34db13c98ec404fa9035796b52cbf8c",
 *       "user_id": "1"
 *     }
 *
 * @apiError InvalidUserData user is not provided
 * @apiError InvalidPasswordData password is not provided
 * @apiError InvalidAppKey app_key is not provided
 * @apiError UserDoesntExist user does not exists
 * @apiError UnexpectedSave an error ocurred saving token data
 *
 * @apiErrorExample Error-Response:
 *     HTTP/1.1 400 Invalid Params
 *     {
 *       "errors": [
 *         "code": "InvalidUserData",
 *         "message": "You must provide an user identifier"
 *       ]
 *     }
 */
$Slim->post('/login', function () use ($Session, $Slim) {
	$API = new Api\V2\LoginAPI($Session, $Slim);
	$API->login();
});

/**
 * @api {get} /clients Get all clients
 * @apiName Get Clients
 * @apiVersion 2.0.0
 * @apiGroup Clients
 * @apiDescription Gets a list of clients
 *
 * @apiHeader {String} AUTHTOKEN=136b17e3a34db13c98ec404fa9035796b52cbf8c  Login Token
 *
 * @apiParam {String} updated_from updated_from=1462310903 (optional): Returns clients that have been updated after the given timestamp
 * @apiParam {String} active active=1 or active=0 (optional): Will only return clientes that have the active attribute set to the value given, the only possible values are 0 or 1. When the parameter is not sent, it won't filter by the active attribute.
 *
 * @apiParamExample Params-Example:
 *     ?updated_from=1462310903&active=1
 *
 * @apiSuccess {Integer} id Client Id
 * @apiSuccess {String} code Client code
 * @apiSuccess {String} name Name of Client
 * @apiSuccess {Integer} active [0, 1] If client is active
 *
 * @apiSuccessExample Success-Response:
 *     HTTP/1.1 200 OK
 *     [
 *       {
 *         "id": 1,
 *         "code": "00001",
 *         "name": "Lemontech S.A.",
 *         "active": 1
 *       }
 *     ]
 *
 * @apiError InvalidDate If date provided in updated_from is an invalid timestamp
 *
 * @apiErrorExample Error-Response:
 *     HTTP/1.1 400 Invalid Params
 *     {
 *       "errors": [
 *         "code": "InvalidDate",
 *         "message": "The date format is incorrect"
 *       ]
 *     }
 */
$Slim->get('/clients', function () use ($Session, $Slim) {
	$API = new Api\V2\ClientsAPI($Session, $Slim);
	$API->getUpdatedClients();
});

$Slim->get('/clients/:client_id/projects', function ($client_id) use ($Session, $Slim) {
	$API = new Api\V2\ProjectsAPI($Session, $Slim);
	$API->getProjectsOfClient($client_id);
});

$Slim->get('/projects', function () use ($Session, $Slim) {
	$API = new Api\V2\ProjectsAPI($Session, $Slim);
	$API->getUpdatedMatters();
});

$Slim->get('/projects/:project_id/payments', function ($project_id) use ($Session, $Slim) {
	$API = new Api\V2\PaymentsAPI($Session, $Slim);
	$API->getPaymentsOfMatter($project_id);
});

$Slim->get('/activities', function () use ($Session, $Slim) {
	$API = new Api\V2\ActivitiesAPI($Session, $Slim);
	$API->getAllActivitiesByProjectId();
});

$Slim->get('/areas', function () use ($Session, $Slim) {
	$API = new Api\V2\AreasAPI($Session, $Slim);
	$API->getUpdatedWorkingAreas();
});

$Slim->get('/tasks', function () use ($Session, $Slim) {
	$API = new  Api\V2\TasksAPI($Session, $Slim);
	$API->getUpdatedTasks();
});

$Slim->get('/translations', function () use ($Session, $Slim) {
	$API = new Api\V2\TranslationsAPI($Session, $Slim);
	$API->getTranslations();
});

$Slim->get('/settings', function () use ($Session, $Slim) {
	$API = new Api\V2\SettingsAPI($Session, $Slim);
	$API->getTimeTrackingSettings();
});

$Slim->get('/users/:id', function ($id) use ($Session, $Slim) {
	$API = new Api\V2\UsersAPI($Session, $Slim);
	$API->getUserById($id);
});

$Slim->get('/users/:id/time_entries', function ($id) use ($Session, $Slim) {
	$API = new Api\V2\TimeEntriesAPI($Session, $Slim);
	$API->getTimeEntriesByUserId($id);
});

$Slim->post('/users/:id/time_entries', function ($id) use ($Session, $Slim) {
	$API = new Api\V2\TimeEntriesAPI($Session, $Slim);
	$API->createTimeEntryByUserId($id);
});

$Slim->put('/users/:user_id/time_entries/:id', function ($user_id, $id) use ($Session, $Slim) {
	$API = new Api\V2\TimeEntriesAPI($Session, $Slim);
	$API->updateTimeEntryByUserId($user_id, $id);
});

$Slim->delete('/users/:user_id/time_entries/:id', function ($user_id, $id)  use ($Session, $Slim) {
	$API = new Api\V2\TimeEntriesAPI($Session, $Slim);
	$API->deleteTimeEntryByUserId($user_id, $id);
});

$Slim->run();
