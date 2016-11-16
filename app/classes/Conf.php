<?php

class Conf {

	private static $statics = [];
	private static $configs = [];
	private static $loaded = false;

	/**
	 * Escribe valores que serán llamados como métodos estáticos, esto mantiene
	 * compatibilidad con valores escritos en métodos en la antigua clase Conf
	 * @param string $alias nombre del método
	 * @param any|function $value valor devuelto
	 *
	 * <b>Example:</b>
	 * Conf::setStatic('staticValue', 'Static Value.');
	 * echo Conf::staticValue(); //Static Value.
	 *
	 * Conf::setStatic('myMethod', function ($arg) {
	 *     return "Hello {$arg}";
	 * });
	 * echo Conf::myMethod('myConf'); //Hello myConf
	 */
	public static function setStatic($alias, $value) {
		self::$statics[$alias] = $value;
	}

	public static function __callStatic($alias, $args) {
		$value = isset(self::$statics[$alias]) ? self::$statics[$alias] : null;
		return is_callable($value) ? call_user_func_array($value, $args) : $value;
	}

	/**
	 * Permite setear la Sesion para no tener que pasarla como argumento cada vez
	 * @param type $Sesion
	 * @deprecated
	 */
	public static function setSession($Sesion) {
		return;
	}

	/**
	 * Devuelve los valores escritos por Conf::write().
	 * @param type $conf
	 * @return type
	 */
	public static function read($conf) {
		if(isset(self::$statics[$conf])) {
			return self::$statics[$conf];
		} else if (isset(self::$configs[$conf])) {
			return self::$configs[$conf];
		}
		return null;
	}

	/**
	 * Escribe valores de configuración
	 * @param type $conf
	 * @param type $value
	 */
	public static function write($conf, $value = null) {
		if (is_array($conf)) {
			foreach ($conf as $key => $val) {
				self::write($key, $val);
			}
		}
		self::$configs[$conf] = $value;
	}

	public static function loadFromArray($confs) {
		if (self::$loaded) {
			return;
		}
		self::write($confs);
		self::$loaded = true;
	}

	/**
	 * Obtener configuraciones
	 *
	 * @param object $Sesion
	 * @param string $conf
	 * @return string
	 *
	 * @deprecated usar en su lugar Conf::read()
	 */
	public static function GetConf(Sesion $Sesion, $conf) {
		return self::read($conf);
	}

}
