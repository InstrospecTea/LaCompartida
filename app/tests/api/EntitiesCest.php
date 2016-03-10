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
}