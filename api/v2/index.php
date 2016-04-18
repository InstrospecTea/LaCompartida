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

$Slim->post('/login', function () use ($Session, $Slim) {
	$API = new Api\V2\LoginAPI($Session, $Slim);
	$API->login();
});

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

