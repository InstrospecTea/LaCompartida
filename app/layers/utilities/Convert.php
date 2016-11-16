<?php

class Convert {
	/**
	 * Convierte cada llave-valor en UTF-8 cuando corresponda, el parámetro
	 * $encode permite realizar la acción inversa
	 * @param mixed $data Arreglo o string a modificar
	 * @param boolean $encode encode (true) o decode (false)
	 * @return mixed
	 */
	public static function utf8($data, $encode = true) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				// Previene doble codificación
				unset($data[$key]);
				$key = self::utf8($key, $encode);
				$data[$key] = self::utf8($value, $encode);
			}
		} else if (is_string($data)) {
			// ^ = XOR = or exclusivo = true && false || false && true
			if (mb_detect_encoding($data, 'UTF-8', true) == 'UTF-8' ^ $encode) {
				$data = $encode ? utf8_encode($data) : utf8_decode($data);
			}
		}
		return $data;
	}
}
