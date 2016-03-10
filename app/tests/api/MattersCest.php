<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class MattersCest
{

		public function _before()
		{
		}

		public function _after()
		{
		}

		public function successfulGetMatters(ApiTester $I) {
			$I->wantTo('Get matters via API');
			$I->login();

			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendGET("/matters");

			$I->seeResponseCodeIs(200);
			$I->seeResponseIsJSON();

			$I->seeResponseContains('"client_code":');
			$I->seeResponseContains('"code":');
			$I->seeResponseContains('"name":');
			$I->seeResponseContains('"language":');
			$I->seeResponseContains('"language_name":');
			$I->seeResponseContains('"active":');
		}

}

