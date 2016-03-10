<?php
use \ApiTester;
use Codeception\Util\Stub;

class LoginCest
{

		public function _before()
		{
		}

		public function _after()
		{
		}

		public function successfulLoginTest(ApiTester $I) {
			$I->wantTo('login an user via API');
			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendPOST('/login', array('user' => '99511620', 'password' => 'Etropos2015', 'app_key' => 'ttb-mobile'));

			$I->seeResponseCodeIs(200);
			$I->seeResponseIsJSON();
			$I->seeResponseContains('{"auth_token"');
		}

		public function unsuccessfulLoginTest(ApiTester $I) {
			$I->wantTo('login a wrong user via API');
			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendPOST('/login', array('user' => '88400519', 'password' => 'Etropos2015', 'app_key' => 'ttb-mobile'));

			$I->seeResponseCodeIs(400);
			$I->seeResponseIsJSON();
			$I->seeResponseContains('El usuario o el password es incorrecto');
		}

		public function unsuccessfulLoginAppKeyTest(ApiTester $I) {
			$I->wantTo('login an user without app-key');
			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendPOST('/login', array('user' => '99511620', 'password' => 'Etropos2015'));

			$I->seeResponseCodeIs(400);
			$I->seeResponseIsJSON();
			$I->seeResponseContains('InvalidAppKey');
		}

}

