<?php

/**
 * Helper para usar AmazonSDB simplificado para solo insertar/obtener/borrar valores directamente por llave
 *
 * @author Javier
 */
use Aws\SimpleDb\SimpleDbClient;

class SimpleDb {

	static private $client;
	private $cache_time;

	public function __construct($cache_time = 600) {

		try {
			if (empty(self::$client)) {
				self::$client = SimpleDbClient::factory(Conf::AmazonKey());
			}
			$this->cache_time = $cache_time;
		} catch (Exception $e) {
		}
	}

	private function attrToArray($resp) {
		$results = array();
		foreach ($resp->body->GetAttributesResult as $result) {
			$results[] = UtilesApp::utf8izar(unserialize($result->Attribute->Value), false);
		}
		return $results;
	}

	public function get($tabla, $llave) {
		try {
			$resp = self::$client->cache($this->cache_time)->get_attributes($tabla, $llave);
			if (!$resp || !$resp->isOK()) {
				return false;
			}
			$results = $this->attrToArray($resp);
			return $results[0];
		} catch (Exception $e) {
			return false;
		}
	}

	public function put($tabla, $llave, $valores) {
		try {
			//se serializa para poder guardar cualquier cosa (SDB solo guarda strings)
			$data = array('data' => serialize(UtilesApp::utf8izar($valores)));
			self::$client->delete_cache()->get_attributes($tabla, $llave);
			return self::$client->put_attributes($tabla, $llave, $data, true)->isOK();
		} catch (Exception $e) {
			return false;
		}
	}

	public function delete($tabla, $llave) {
		try {
			self::$client->delete_cache()->get_attributes($tabla, $llave);
			return self::$client->delete_attributes($tabla, $llave)->isOK();
		} catch (Exception $e) {
			return false;
		}
	}

}
