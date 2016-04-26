<?php
use \ApiTester;
use Codeception\Util\Stub;
use Helpers\ApiTesterHelper;

class ContractsGeneratorsCest
{

	public function _before()
	{
	}

	public function _after()
	{
	}

	public function successfulGetGenerator(ApiTester $I) {
		$I->wantTo('Get Generators');
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->login();

		$clientCode = $I->someClient();
		$clientId = $I->getClientDataFromDb($clientCode, 'id_cliente');
		$clientContractId = $I->getClientDataFromDb($clientCode, 'id_contrato');

		$I->sendGET("/clients/{$clientId}/contracts/{$clientContractId}/generators");

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
	}

	public function successfulCreateGenerator(ApiTester $I) {
		$I->wantTo('Create Generator');
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->login();

		$clientCode = $I->someClient();
		$clientId = $I->getClientDataFromDb($clientCode, 'id_cliente');
		$clientContractId = $I->getClientDataFromDb($clientCode, 'id_contrato');

		$generatorData = array(
			'percent_generator' => 50,
			'user_id' => 1
		);

		$I->sendPUT("/clients/{$clientId}/contracts/{$clientContractId}/generators", $generatorData);

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
		$I->seeResponseContains('true');

		$I->sendGET("/clients/{$clientId}/contracts/{$clientContractId}/generators");
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();
		$I->seeResponseContains('"id_contrato_generador":');
		$I->seeResponseContains('"id_cliente":');
		$I->seeResponseContains('"id_contrato":');
		$I->seeResponseContains('"area_usuario":');
		$I->seeResponseContains('"id_usuario":');
		$I->seeResponseContains('"nombre":');
		$I->seeResponseContains('"porcentaje_genera":"50"');
	}

	public function successfulUpdateGenerator(ApiTester $I) {
		$I->wantTo('Update Generator');
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->login();

		$userId = 1;
		$clientCode = $I->someClient();
		$clientId = $I->getClientDataFromDb($clientCode, 'id_cliente');
		$clientContractId = $I->getClientDataFromDb($clientCode, 'id_contrato');

		$generatorData = array(
			'percent_generator' => 50,
			'user_id' => $userId
		);
		$I->sendPUT("/clients/{$clientId}/contracts/{$clientContractId}/generators", $generatorData);

		$generatorId = $I->grabFromDatabase(
			'contrato_generador',
			'id_contrato_generador',
			array(
				'id_cliente' => $clientId,
				'id_contrato' => $clientContractId,
				'id_usuario' => $userId,
				'porcentaje_genera' => 50
			)
		);

		$generatorData = array(
			'percent_generator' => 100,
			'user_id' => 1
		);

		$I->sendPOST("/clients/{$clientId}/contracts/{$clientContractId}/generators/{$generatorId}", $generatorData);
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('true');
	}


	public function successfulDeleteGenerator(ApiTester $I) {
		$I->wantTo('Delete Generator');
		$I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
		$I->login();

		$userId = 1;
		$clientCode = $I->someClient();
		$clientId = $I->getClientDataFromDb($clientCode, 'id_cliente');
		$clientContractId = $I->getClientDataFromDb($clientCode, 'id_contrato');

		$generatorData = array(
			'percent_generator' => 50,
			'user_id' => $userId
		);
		$I->sendPUT("/clients/{$clientId}/contracts/{$clientContractId}/generators", $generatorData);

		$generatorId = $I->grabFromDatabase(
			'contrato_generador',
			'id_contrato_generador',
			array(
				'id_cliente' => $clientId,
				'id_contrato' => $clientContractId,
				'id_usuario' => $userId,
				'porcentaje_genera' => 50
			)
		);

		$generatorData = array(
			'percent_generator' => 100,
			'user_id' => 1
		);

		$I->sendDELETE("/clients/{$clientId}/contracts/{$clientContractId}/generators/{$generatorId}", $generatorData);
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJSON();

		$I->seeResponseContains('{"result":"OK"}');
	}

}

