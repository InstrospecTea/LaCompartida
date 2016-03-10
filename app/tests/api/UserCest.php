<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class UserCest
{

		public function _before()
		{

		}

		public function _after()
		{
		}

		public function successfulGetUser(ApiTester $I) {
			$I->wantTo('Get user via API');
			$I->login();

			$user_id = 1;
			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendGET("/users/{$user_id}");

			$I->seeResponseCodeIs(200);
			$I->seeResponseIsJSON();
			$I->seeResponseContains("{\"id\":{$user_id},");
		}

		public function unsuccessfulGetUser(ApiTester $I) {
			$I->wantTo('Get user via API');
			$I->login();

			$user_id = 9999999;
			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendGET("/users/{$user_id}");

			$I->seeResponseCodeIs(400);
			$I->seeResponseIsJSON();
			$I->seeResponseContains("UserDoesntExist");
		}
}

