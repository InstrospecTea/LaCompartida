<?php

use Aws\DynamoDb\DynamoDbClient;

class DynamoDB {

	private $client;

	public function __construct(Array $config = []) {
		$config += Conf::AmazonKey();
		$this->client = DynamoDbClient::factory($config);
	}

	public function get($request) {
		$result = $this->client->getItem($request);
		$values = [];
		foreach ($result['Item'] as $tipo => $valor) {
			if (is_string($valor)) {
				$values[$tipo] = $valor;
			} else if (isset($valor['S'])) {
				$values[$tipo] = $valor['S'];
			} else if (isset($valor['N'])) {
				$values[$tipo] = $valor['N'];
			}
		}
		return $values;
	}

}
