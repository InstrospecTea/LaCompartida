<?php

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

class DynamoDb {

	private $client;

	public function __construct(Array $config = []) {
		$config += Conf::AmazonKey();
		try {
			$this->client = DynamoDbClient::factory($config);
		} catch (DynamoDbException $e) {
			throw new Exception('The item could not be retrieved.');
		}
	}

	public function get($request) {
		try {
			$result = $this->client->getItem($request);
		} catch (DynamoDbException $e) {
			throw new Exception('The item could not be retrieved.');
		}
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
