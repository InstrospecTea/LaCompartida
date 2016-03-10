<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class TimeEntriesCest
{

		public function _before()
		{
		}

		public function _after()
		{
		}

		public function successfulGetTimeEntries(ApiTester $I) {
			$I->wantTo('Get time entries via API');
			$I->login();

			$userId = 1;
			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendGET("/users/{$userId}/works");

			$I->seeResponseCodeIs(200);
			$I->seeResponseIsJSON();
		}

		public function successfulCreateTimeEntry(ApiTester $I) {
			$I->wantTo('Create time entry');
			$I->login();

			$project = $I->someProject();
			$userId = 1;

			$time_entry = array(
				'date' => time(),
				'created_date' => time(),
				'duration' => 120,
				'notes' => 'description of time entry',
				'rate' => 0,
				'requester' => 'someone',
				'activity_code' => '',
				'area_code' => '',
				'matter_code' => $project->code,
				'task_code' => '',
				'user_id' => $userId,
				'billable' => 1,
				'visible' => 1
			);

			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendPUT("/users/{$userId}/works", $time_entry);

			$I->seeResponseCodeIs(200);
			$I->seeResponseIsJSON();
		}

		public function successfulUpdateTimeEntry(ApiTester $I) {
			$I->wantTo('Update time via API');
			$I->login();

			$timeEntry = $I->createTimeEntry();
			$userId = $timeEntry->user_id;
			$entryId = $timeEntry->id;
			$newDuration = 50;

			$timeEntryUpdates = array(
				'date' => time(),
				'created_date' => time(),
				'duration' => $newDuration,
				'notes' => 'new time entry',
				'rate' => 0,
				'requester' => 'someone',
				'activity_code' => '',
				'area_code' => '',
				'matter_code' => $timeEntry->matter_code,
				'task_code' => '',
				'user_id' => $userId,
				'billable' => 1,
				'visible' => 1
			);

			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendPOST("/users/{$userId}/works/{$entryId}", $timeEntryUpdates);

			$I->seeResponseCodeIs(200);
			$I->seeResponseIsJSON();
			$I->seeResponseContains("\"duration\":{$newDuration}");
		}

		public function successfulRemoveTimeEntry(ApiTester $I) {
			$I->wantTo('Update time via API');
			$I->login();

			$timeEntry = $I->createTimeEntry();
			$userId = $timeEntry->user_id;
			$entryId = $timeEntry->id;

			$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
			$I->sendDELETE("/users/{$userId}/works/{$entryId}");

			$I->seeResponseCodeIs(200);
			$I->seeResponseIsJSON();
			$I->seeResponseContains('{"result":"OK"}');
		}


}

