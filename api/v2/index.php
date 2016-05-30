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

/**
 * @api {get} /clients/:client_id/projects Get Projects of Client
 * @apiName Get Projects
 * @apiVersion 2.0.0
 * @apiGroup Clients
 * @apiDescription Gets the list of all projects of a client.
 *
 * @apiHeader {String} AUTHTOKEN=136b17e3a34db13c98ec404fa9035796b52cbf8c  Login Token
 *
 * @apiParam {String} client_id The :client_id corresponds to a client id attribute.
 *
 * @apiSuccess {Integer} id Project Id
 * @apiSuccess {String} code Project code
 * @apiSuccess {String} name Name of Project
 * @apiSuccess {Integer} active [0, 1] If client is active
 * @apiSuccess {Integer} client_id Id of parent client
 * @apiSuccess {Integer} project_area_id Projects' Area
 * @apiSuccess {Integer} project_type_id Projects' Type
 * @apiSuccess {String} language_code Language code of Project
 * @apiSuccess {String} language_name Language name of Project
 * @apiSuccess {String} created_at Creation date
 * @apiSuccess {String} updated_at Date Updated date
 * @apiSuccess {String} currency_code Currency Code
 *
 * @apiSuccessExample Success-Response:
 *     HTTP/1.1 200 OK
 *     [
 *      {
 *         "id": 1,
 *         "code": "0001-0001",
 *         "name": "Asesorías Generales",
 *         "active": 1,
 *         "client_id": 1,
 *         "project_area_id": 1,
 *         "project_type_id": 1,
 *         "language_code": "es",
 *         "language_name": "Español"
 *         "created_at": "2014-06-03 11:58:38",
 *         "updated_at": "2014-06-03 11:58:38",
 *         "currency_code": "COLP"
 *       }
 *     ]
 *
 * @apiError InvalidClientCode If client id is not provided
 * @apiError ClientDoesntExists If client does not exists
 *
 * @apiErrorExample Error-Response:
 *     HTTP/1.1 400 Invalid Params
 *     {
 *       "errors": [
 *         "code": "InvalidClientCode",
 *         "message": "The client doesn't exist"
 *       ]
 *     }
 */
$Slim->get('/clients/:client_id/projects', function ($client_id) use ($Session, $Slim) {
	$API = new Api\V2\ProjectsAPI($Session, $Slim);
	$API->getProjectsOfClient($client_id);
});

/**
 * @api {get} /projects Get All Projects
 * @apiName Get Projects
 * @apiVersion 2.0.0
 * @apiGroup Projects
 * @apiDescription Gets a list of all projects.
 *
 * @apiHeader {String} AUTHTOKEN=136b17e3a34db13c98ec404fa9035796b52cbf8c  Login Token
 *
 * @apiParam {String} client_id Corresponds to a client id attribute.
 * @apiParam {String} updated_from updated_from=1462310903 (optional): Returns projects that have been updated after the given timestamp
 * @apiParam {String} active active=1 or active=0 (optional): Will only return projects that have the active attribute set to the value given, the only possible values are 0 or 1. When the parameter is not sent, it won't filter by the active attribute.
 *
 * @apiSuccess {Integer} id Project Id
 * @apiSuccess {String} code Project code
 * @apiSuccess {String} name Name of Project
 * @apiSuccess {Integer} active [0, 1] If client is active
 * @apiSuccess {Integer} client_id Id of parent client
 * @apiSuccess {Integer} project_area_id Projects' Area
 * @apiSuccess {Integer} project_type_id Projects' Type
 * @apiSuccess {String} language_code Language code of Project
 * @apiSuccess {String} language_name Language name of Project
 * @apiSuccess {String} created_at Creation date
 * @apiSuccess {String} updated_at Date Updated date
 * @apiSuccess {String} currency_code Currency Code
 *
 * @apiSuccessExample Success-Response:
 *     HTTP/1.1 200 OK
 *     [
 *      {
 *         "id": 1,
 *         "code": "0001-0001",
 *         "name": "Asesorías Generales",
 *         "active": 1,
 *         "client_id": 1,
 *         "project_area_id": 1,
 *         "project_type_id": 1,
 *         "language_code": "es",
 *         "language_name": "Español",
 *         "created_at": "2014-06-03 11:58:38",
 *         "updated_at": "2014-06-03 11:58:38",
 *         "currency_code": "COLP"
 *       }
 *     ]
 *
 * @apiError InvalidClientCode If client id is not provided
 * @apiError ClientDoesntExists If client does not exists
 *
 * @apiErrorExample Error-Response:
 *     HTTP/1.1 400 Invalid Params
 *     {
 *       "errors": [
 *         "code": "InvalidClientCode",
 *         "message": "The client doesn't exist"
 *       ]
 *     }
 */
$Slim->get('/projects', function () use ($Session, $Slim) {
	$API = new Api\V2\ProjectsAPI($Session, $Slim);
	$API->getUpdatedMatters();
});

/**
 * @api {get} /projects/:project_id/payments Get Payments
 * @apiName Get Payments
 * @apiVersion 2.0.0
 * @apiGroup Projects
 * @apiDescription Gets a list of all payments of one project
 *
 * @apiHeader {String} AUTHTOKEN=136b17e3a34db13c98ec404fa9035796b52cbf8c  Login Token
 *
 * @apiParam {String} project_id Corresponds to a project id attribute.
 *
 * @apiSuccess {Integer} id Payment Id
 * @apiSuccess {String} project_code Project code
 * @apiSuccess {String} date Date of payment
 * @apiSuccess {Numeric} amount Amount of payment
 * @apiSuccess {String} name Name of payment
 * @apiSuccess {Integer} project_id Project Id
 *
 * @apiSuccessExample Success-Response:
 *     HTTP/1.1 200 OK
 *     [
 *       {
 *         "project_code": "000134-0001",
 *         "id": "238",
 *         "date": "2015-02-09 11:14:42",
 *         "amount": "1194414",
 *         "name": "Pago de Factura # 001-10186",
 *         "project_id": "205"
 *       },
 *       {
 *         "project_code": "000134-0001",
 *         "id": "1045",
 *         "date": "2015-12-17 18:14:01",
 *         "amount": "11666474",
 *         "name": "Pago de Factura # 001-11152, 001-11154, 001-11155, 001-11156, 001-11157",
 *         "project_id": "205"
 *       }
 *     ]
 *
 */
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

/**
 * @api {get} /users/:id/time_entries Get TimeEntries
 * @apiName Get TimeEntries
 * @apiVersion 2.0.0
 * @apiGroup TimeEntries
 * @apiDescription Get a list of time entries (works, jobs, etc..whatever you want)
 *
 * @apiHeader {String} AUTHTOKEN=136b17e3a34db13c98ec404fa9035796b52cbf8c  Login Token
 *
 * @apiParam {String} id Id of an user (view Login Response)
 * @apiParam {String} date (timestamp, optional) Returns time entries betweeen monday to sunday of date
 * @apiParam {String} string_date (date YYYY-MM-DD, optional) Returns time entries betweeen monday to sunday of string_date
 * @apiParam {String} embed (optional) A list of embed relations
 *
 * @apiParamExample Params-Example:
 *     ?string_date=2016-05-10&embed=project
 *
 * @apiSuccess {Integer} id Id of a Time Entry
 * @apiSuccess {Float} date Date of work
 * @apiSuccess {String} string_date Date of work in string format
 * @apiSuccess {Float} created_at Creation date of time entry
 * @apiSuccess {String} string_created_at Creation date of time entry in string format
 * @apiSuccess {Float} duration Duration in Minutes
 * @apiSuccess {String} description Notes of a time entry
 * @apiSuccess {Integer} user_id user that did work
 * @apiSuccess {Integer} billable [1, 0] determine if a time entry is billable
 * @apiSuccess {Integer} visible  [1, 0] determine if a time entry is visible and not billable
 * @apiSuccess {Integer} read_only [1, 0] determine if a time entry is locked by an invoice or in review process
 * @apiSuccess {Integer} client_id  Id of client
 * @apiSuccess {Integer} project_id Id of project
 * @apiSuccess {Integer} activity_id Id of activity
 * @apiSuccess {Integer} area_id Id of Area
 * @apiSuccess {Integer} task_id Id of Task
 * @apiSuccess {String} requester Name of requester
 * @apiSuccess {Project} project If embed=project is provided, then returns a project entity
 *
 * @apiSuccessExample Success-Response:
 *     HTTP/1.1 200 OK
 *     [
 *       {
 *         "id": 1,
 *         "date": "121283477",
 *         "string_date": "2015-04-10",
 *         "created_at": "121283477",
 *         "string_created_at": "2015-04-10",
 *         "duration": 120,
 *         "description": "writing a letter",
 *         "user_id": 1,
 *         "billable": 1,
 *         "visible": 1,
 *         "read_only": 0,
 *         "client_id": 1,
 *         "project_id": 2,
 *         "activity_id": 3,
 *         "area_id": 4,
 *         "task_id": 5,
 *         "requester": "Mario Lavandero",
 *         "project": {
 *           "id": 2,
 *           "code": "0001-0002",
 *           "name": "Asesorías Financieras",
 *           "active": 1,
 *           "client_id": 1,
 *           "project_area_id": 1,
 *           "project_type_id": 1,
 *           "language_code": "es",
 *           "language_name": "Español"
 *         }
 *       }
 *     ]
 *
 * @apiError InvalidUserID If param :id is empty
 * @apiError UserDoesntExist If User does not exsists
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
