<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class ClientsCest
{

	public function _before()
	{
	}

	public function _after()
	{
	}

	public function successfulGetClients(ApiTester $I) {
		$I->wantTo('Get clients via API');
		$I->login();

		$user_id = 1;
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/clients");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('{"code":');
		$I->seeResponseContains('"name":');
		$I->seeResponseContains('"address":');
		$I->seeResponseContains('"active":');
	}

	public function successfulGetMattersOfClient(ApiTester $I) {
		$I->wantTo('Get matters of client via API');
		$I->login();

		$client_code = $I->someClient();

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/clients/{$client_code}/matters");
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
	}

	public function unsuccessfulGetMattersOfClient(ApiTester $I) {
		$I->wantTo('Get matters of non-client via API');
		$I->login();

		$client_code = '999999999';

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/clients/{$client_code}/matters");
		$I->seeResponseCodeIs(400);
		$I->seeResponseIsJSON();
		$I->seeResponseContains("ClientDoesntExists");
	}

}

