<?php

/**
 * Helper para usar AmazonSDB simplificado para solo insertar/obtener/borrar valores directamente por llave
 *
 * @author Javier
 */
class SDB {

	private $sdb;
	private $cache_time;

	public function __construct($cache_time = 600) {
		try {
			$this->sdb = new AmazonSDB(Conf::AmazonKey());
			$this->cache_time = $cache_time;
		} catch (Exception $e) {
			//algo
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
			$resp = $this->sdb->cache($this->cache_time)->get_attributes($tabla, $llave);
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
			$this->sdb->delete_cache()->get_attributes($tabla, $llave);
			return $this->sdb->put_attributes($tabla, $llave, $data, true)->isOK();
		} catch (Exception $e) {
			return false;
		}
	}

	public function delete($tabla, $llave) {
		try {
			$this->sdb->delete_cache()->get_attributes($tabla, $llave);
			return $this->sdb->delete_attributes($tabla, $llave)->isOK();
		} catch (Exception $e) {
			return false;
		}
	}

}
