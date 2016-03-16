<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$Session = new Sesion();
$Slim = new Slim();

$Slim->post('/login', function () use ($Session, $Slim) {
	$API = new LoginAPI($Session, $Slim);
	$API->login();
});

$Slim->get('/clients', function () use ($Session, $Slim) {
	$API = new ClientsAPI($Session, $Slim);
	$API->getClients();
});

$Slim->get('/clients/:code/matters', function ($code) use ($Session, $Slim) {
	$API = new ClientsAPI($Session, $Slim);
	$API->getMattersOfClient($code);
});

$Slim->get('/matters', function () use ($Session, $Slim) {
	$API = new MattersAPI($Session, $Slim);
	$API->getMatters();
});

$Slim->get('/activities', function () use ($Session, $Slim) {
	$API = new ActivitiesAPI($Session, $Slim);
	$API->getActivities();
});

$Slim->get('/areas', function () use ($Session, $Slim) {
	$API = new AreasAPI($Session, $Slim);
	$API->getAreas();
});

$Slim->get('/tasks', function () use ($Session, $Slim) {
	$API = new TasksAPI($Session, $Slim);
	$API->getTasks();
});

$Slim->get('/translations', function () use ($Session, $Slim) {
	$API = new TranslationsAPI($Session, $Slim);
	$API->getTranslations();
});

$Slim->get('/settings', function () use ($Session, $Slim) {
	$API = new SettingsAPI($Session, $Slim);
	$API->getSettings();
});

$Slim->get('/users/:id', function ($id) use ($Session, $Slim) {
	$API = new UsersAPI($Session, $Slim);
	$API->getUserById($id);
});

$Slim->get('/users/:id/works', function ($id) use ($Session, $Slim) {
	$API = new TimeEntriesAPI($Session, $Slim);
	$API->getTimeEntriesByUserId($id);
});

$Slim->put('/users/:id/works', function ($id) use ($Session, $Slim) {
	$API = new TimeEntriesAPI($Session, $Slim);
	$API->createTimeEntryByUserId($id);
});

$Slim->post('/users/:user_id/works/:id', function ($user_id, $id) use ($Session, $Slim) {
	$API = new TimeEntriesAPI($Session, $Slim);
	$API->updateTimeEntryByUserId($user_id, $id);
});

$Slim->delete('/users/:user_id/works/:id', function ($user_id, $id)  use ($Session, $Slim) {
	$API = new TimeEntriesAPI($Session, $Slim);
	$API->deleteTimeEntryByUserId($user_id, $id);
});

$Slim->put('/users/:user_id/device', function ($user_id) use ($Session, $Slim) {
	$API = new DevicesAPI($Session, $Slim);
	$API->findOrCreateDeviceByUserId($user_id);
});

$Slim->delete('/users/:user_id/device/:token', function ($user_id, $token) use ($Session, $Slim) {
	$API = new DevicesAPI($Session, $Slim);
	$API->deleteDeviceByUserId($user_id, $token);
});

$Slim->post('/users/:id', function ($id) use ($Session, $Slim) {
	$API = new UsersAPI($Session, $Slim);
	$API->updateUserSettings($id);
});

$Slim->get('/clients/:client_id/contracts/:contract_id/generators', function ($client_id, $contract_id) use ($Session, $Slim) {
	$API = new ContractsGeneratorsAPI($Session, $Slim);
	$API->getGenerators($client_id, $contract_id);
});

$Slim->post('/clients/:client_id/contracts/:contract_id/generators/:generator_id', function ($client_id, $contract_id, $generator_id) use ($Session, $Slim) {
	$API = new ContractsGeneratorsAPI($Session, $Slim);
	$API->updateGenerator($client_id, $contract_id, $generator_id);
});

$Slim->put('/clients/:client_id/contracts/:contract_id/generators', function ($client_id, $contract_id) use ($Session, $Slim) {
	$API = new ContractsGeneratorsAPI($Session, $Slim);
	$API->createGenerator($client_id, $contract_id);
});

$Slim->delete('/clients/:client_id/contracts/:contract_id/generators/:generator_id', function ($client_id, $contract_id, $generator_id) use ($Session, $Slim) {
	$API = new ContractsGeneratorsAPI($Session, $Slim);
	$API->deleteGenerator($client_id, $contract_id, $generator_id);
});

$Slim->get('/reports/:report_code', function ($report_code) use ($Session, $Slim) {
	$API = new ReportsAPI($Session, $Slim);
	$API->getReportByCode($report_code);
});

$Slim->get('/reports', function () use ($Session, $Slim) {
	$API = new ReportsAPI($Session, $Slim);
	$API->getReports();
});

$Slim->get('/currencies', function () use ($Session, $Slim) {
	$API = new CurrenciesAPI($Session, $Slim);
	$API->getCurrencies();
});

$Slim->get('/errand_rates/:errand_rate_id/values', function ($errand_rate_id) use ($Session, $Slim) {
	$API = new ContractsErrandsAPI($Session, $Slim);
	$API->getErrandValuesByRateId($errand_rate_id);
});

$Slim->get('/contracts/:contract_id/included_errands', function ($contract_id) use ($Session, $Slim) {
	$API = new ContractsErrandsAPI($Session, $Slim);
	$API->getErrandsByContractId($contract_id);
});

$Slim->post('/contracts/:contract_id/included_errands', function ($contract_id) use ($Session, $Slim) {
	$API = new ContractsErrandsAPI($Session, $Slim);
	$API->createErrandTypeInContract($contract_id);
});

$Slim->delete('/contracts/:contract_id/included_errands', function ($contract_id) use ($Session, $Slim) {
	$API = new ContractsErrandsAPI($Session, $Slim);
	$API->deleteErrandFromContract($contract_id);
});

$Slim->post('/invoices/:id/build', function ($id) use ($Session, $Slim) {
	$API = new InvoicesAPI($Session, $Slim);
	$API->createDTEByInvoiceId($id);
});

$Slim->get('/invoices/:id/document', function ($id) use ($Session, $Slim) {
	$API = new InvoicesAPI($Session, $Slim);
	$API->getDTEByInvoiceId($id);
});

$Slim->get('/LogDB/:titulo_tabla/:id_field', function($titulo_tabla, $id_field)  use ($Slim, $Session, $Log) {
	$API = new LogsAPI($Session, $Slim);
	$API->getDBLogByTitle($titulo_tabla, $id_field);
});

$Slim->map('/release-list', function () use ($Session, $Slim) {
	$API = new ApplicationsAPI($Session, $Slim);
	$API->getReleasesList();
})->via('GET', 'POST');

$Slim->map('/release-download', function () use ($Session, $Slim) {
	$API = new ApplicationsAPI($Session, $Slim);
	$API->downloadRelease();
})->via('GET', 'POST');

$Slim->map('/app-track', function () use ($Session, $Slim) {
	$API = new ApplicationsAPI($Session, $Slim);
	$API->trackAnalytic();
})->via('GET', 'POST');

$Slim->run();
