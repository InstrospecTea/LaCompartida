<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class ErrandsCest
{

	public function _before()
	{
	}

	public function _after()
	{
	}

	public function successfulGetErrandValues(ApiTester $I) {
		$I->wantTo('Get reports via API');
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->login();

		$baseErrandRateId = $I->grabFromDatabase(
			'tramite_tarifa',
			'id_tramite_tarifa',
			array(
				'tarifa_defecto' => 1
			)
		);

		$errandTypeId = $I->grabFromDatabase(
			'tramite_valor',
			'id_tramite_tipo',
			array(
				'id_tramite_tarifa' => $baseErrandRateId
			)
		);

		$errandCurrencyId = $I->grabFromDatabase(
			'tramite_valor',
			'id_moneda',
			array(
				'id_tramite_tarifa' => $baseErrandRateId
			)
		);

		$I->sendGET(
			"/errand_rates/{$baseErrandRateId}/values",
			array(
				'errand_type_id' => $errandTypeId,
				'errand_currency_id' => $errandCurrencyId
			)
		);

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
	}

	public function successfulGetContractIncludedErrands(ApiTester $I) {
		$I->wantTo('Get errands in contract via API');
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->login();

		$clientCode = $I->someClient();
		$clientId = $I->getClientDataFromDb($clientCode, 'id_cliente');
		$clientContractId = $I->getClientDataFromDb($clientCode, 'id_contrato');

		$I->sendGET("/contracts/{$clientContractId}/included_errands");
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
	}

}


