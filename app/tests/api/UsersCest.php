<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class UsersCest
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

		$userId = 1;
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/users/{$userId}");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
		$I->seeResponseContains("{\"id\":{$userId},");
	}

	public function unsuccessfulGetUser(ApiTester $I) {
		$I->wantTo('Get user via API');
		$I->login();

		$userId = 9999999;
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendGET("/users/{$userId}");

		$I->seeResponseCodeIs(400);
		$I->seeResponseIsJSON();
		$I->seeResponseContains("UserDoesntExist");
	}

	public function successfulUpdateUserSettings(ApiTester $I) {
		$I->wantTo('Update user settings via API');
		$I->login();

		$userId = 1;
		$userData = array(
			'receive_alerts' => 1,
			'alert_hour' => 48600
		);

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendPOST("/users/{$userId}", $userData);

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains("\"id\":");
		$I->seeResponseContains("\"code\":");
		$I->seeResponseContains("\"name\":");
		$I->seeResponseContains("\"weekly_alert\":");
		$I->seeResponseContains("\"daily_alert\":");
		$I->seeResponseContains("\"min_daily_hours\":");
		$I->seeResponseContains("\"max_daily_hours\":");
		$I->seeResponseContains("\"min_weekly_hours\":");
		$I->seeResponseContains("\"max_weekly_hours\":");
		$I->seeResponseContains("\"days_track_works\":");
		$I->seeResponseContains("\"receive_alerts\":1");
		$I->seeResponseContains("\"alert_hour\":");
	}

	public function successfulCreateDevice(ApiTester $I) {
		$I->wantTo('Create device via API');
		$I->login();

		$userId = 1;
		$token = 'XXXXXXXXXXXXXXXXX';

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendPUT(
			"/users/{$userId}/device",
			array(
				'token' => $token,
				'lastToken' => ''
			)
		);

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
		$I->seeResponseContains("\"id\":");
		$I->seeResponseContains("\"user_id\":\"{$userId}\",\"token\":\"{$token}\"");
	}

	public function successfulCreateDeviceTokenWithoutLast(ApiTester $I) {
		$I->wantTo('Create device without last token via API');
		$I->login();

		$userId = 1;
		$token = 'XXXXXXXXXXXXXXXXX';

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendPUT(
			"/users/{$userId}/device",
			array(
				'token' => $token
			)
		);

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
		$I->seeResponseContains("\"id\":");
		$I->seeResponseContains("\"user_id\":\"{$userId}\",\"token\":\"{$token}\"");
	}

	public function successfulDeleteDeviceToken(ApiTester $I) {
		$I->wantTo('Delete a device token via API');
		$I->login();

		$userId = 1;
		$token = 'XXXXXXXXXXXXXXXXX';

		$I->createDeviceToken($userId, $token);

		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->sendDELETE("/users/{$userId}/device/{$token}");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
		$I->seeResponseContains("{\"result\":\"OK\"}");
	}


}

