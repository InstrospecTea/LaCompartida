<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class ActivitiesCest
{

	public function _before()
	{
	}

	public function _after()
	{
	}

	public function successfulGetActivities(ApiTester $I) {
		$I->wantTo('Get activities via API');
		$I->login();

		$I->haveInDatabase('actividad', array('glosa_actividad' => 'caquita', 'activo' => 1));

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/activities");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('"code":');
		$I->seeResponseContains('"name":"caquita"');
		$I->seeResponseContains('"matter_code":');
	}

}

