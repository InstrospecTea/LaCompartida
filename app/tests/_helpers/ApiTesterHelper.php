<?php
namespace Codeception\Module;

// here you can define custom functions for ApiTester

class ApiTesterHelper extends \Codeception\Module
{

	protected $token;

	function login() {
		$username = '99511620';
		$password = 'Etropos2015';

		if (!$this->token) {
			$this->getModule('REST')->sendPOST(
				'/login',
				array(
					'user' => $username,
					'password' => $password,
					'app_key' => 'ttb-mobile'
				)
			);
			$this->token = json_decode($this->getModule('REST')->response)->auth_token;
		}

		$this->getModule('REST')->headers['AUTHTOKEN'] = $this->token;
	}

	function someClient() {
		$this->getModule('REST')->sendGET(
			'/clients'
		);
		$client_code = json_decode($this->getModule('REST')->response);
		return $client_code[0]->code;
	}

	function someProject() {
		$this->getModule('REST')->sendGET(
			'/matters'
		);
		$matters = json_decode($this->getModule('REST')->response);
		return $matters[0];
	}

	function createTimeEntry() {
		$project = $this->someProject();
		$user_id = 1;

		$timeEntry = array(
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
			'user_id' => $user_id,
			'billable' => 1,
			'visible' => 1
		);

		$this->getModule('REST')->sendPUT("/users/{$user_id}/works", $timeEntry);
		$timeEntry = json_decode($this->getModule('REST')->response);
		return $timeEntry;
	}
}
