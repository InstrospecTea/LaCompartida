<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class ReportsCest
{

	public function _before()
	{
	}

	public function _after()
	{
	}

	public function successfulGetReports(ApiTester $I) {
		$I->wantTo('Get reports via API');
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->login();


		$reportCode = $I->grabFromDatabase(
			'reporte_listado',
			'tipo',
			array(
				'api_accessible' => 1
			)
		);

		if ($reportCode != 'TEST') {
			$I->haveInDatabase('reporte_listado', array('tipo' => 'TEST', 'title' => 'Reporte de test', 'api_accessible' => 1));
		}

		$I->sendGET("/reports");
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('"results":');
		$I->seeResponseContains('"id":');
		$I->seeResponseContains('"tipo":"TEST"');
	}

	public function successfulReports(ApiTester $I) {
		$I->wantTo('Get reports via API');
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->login();

		$reports = array('FACTURA_PRODUCCION', 'TRABAJOS_ASUNTO', 'FACTURA_COBRANZA', 'FACTURA_COBRANZA_APLICADA', 'GASTOS_NO_COBRABLES');

		foreach ($reports as $reportCode) {
			$I->sendGET("/reports/{$reportCode}?period_from=01-01-2015&period_to=01-12-2015&currency_id=1&format=Json");
			$I->seeResponseCodeIs(200);
			$I->seeResponseIsJSON();
			$I->seeResponseContains('"headers":');
			$I->seeResponseContains('"filters":');
		}

	}


}

