<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class EntitiesCest
{

	public function _before()
	{
	}

	public function _after()
	{
	}

	public function successfulGetAreas(ApiTester $I) {
		$I->wantTo('Get areas via API');
		$I->login();

		$I->haveInDatabase('prm_area_trabajo', array('glosa' => 'caquita'));

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/areas");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('"code":');
		$I->seeResponseContains('"name":"caquita"');
	}

	public function successfulGetTasks(ApiTester $I) {
		$I->wantTo('Get tasks via API');
		$I->login();

		$I->haveInDatabase('tarea', array('nombre' => 'taskita'));

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/tasks");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('"code":');
		$I->seeResponseContains('"name":"taskita"');
	}

	public function successfulGetTranslations(ApiTester $I) {
		$I->wantTo('Get translations via API');
		$I->login();

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/translations");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('"code":"Matters"');
		$I->seeResponseContains('"value":"Asuntos"');
		$I->seeResponseContains('"code":"Works"');
		$I->seeResponseContains('"value":"Trabajos"');
		$I->seeResponseContains('"code":"Clients"');
		$I->seeResponseContains('"value":"Clientes"');
	}

	public function successfulGetSettings(ApiTester $I) {
		$I->wantTo('Get settings via API');
		$I->login();

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/settings");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('"code":"IncrementalStep"');
		$I->seeResponseContains('"code":"UseActivities"');
		$I->seeResponseContains('"code":"AllowBillable"');
		$I->seeResponseContains('"code":"MaxWorkDuration"');
	}

	public function successfulGetCurrencies(ApiTester $I) {
		$I->wantTo('Get currencies via API');
		$I->login();

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/currencies");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('"id_moneda"');
		$I->seeResponseContains('"glosa_moneda"');
		$I->seeResponseContains('"glosa_moneda_plural"');
		$I->seeResponseContains('"tipo_cambio"');
		$I->seeResponseContains('"cifras_decimales"');
		$I->seeResponseContains('"simbolo"');
		$I->seeResponseContains('"codigo"');
		$I->seeResponseContains('"simbolo_factura"');
	}
}
