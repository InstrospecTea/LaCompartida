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



}
