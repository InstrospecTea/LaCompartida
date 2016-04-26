<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class SettingsCest
{

	public function _before()
	{
	}

	public function _after()
	{
	}

	public function successfulGetTTBSettings(ApiTester $I) {
		$I->wantTo('Get settings via API');
		$I->login();

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/settings");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('"code":"use_work_rate"');
		$I->seeResponseContains('"code":"language"');
		$I->seeResponseContains('"code":"incremental_step"');
		$I->seeResponseContains('"code":"max_work_duration"');
		$I->seeResponseContains('"code":"use_requester"');
		$I->seeResponseContains('"code":"allow_billable"');
		$I->seeResponseContains('"code":"use_uppercase"');
		$I->seeResponseContains('"code":"use_working_areas"');
		$I->seeResponseContains('"code":"use_activities"');
		$I->seeResponseContains('"code":"timezone"');
	}
}
