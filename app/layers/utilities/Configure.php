<?php

/**
 * Permite consultar las configuraciones sin pasar todo el tiempo la Sesión
 */
abstract class Configure {

	static private $Session;

	static public function setSession(Sesion $Session) {
		self::$Session = $Session;
	}

	static public function read($key) {
		return Conf::GetConf(self::$Session, $key);
	}

}