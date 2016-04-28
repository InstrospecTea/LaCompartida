<?php

class Conf {

	private static $statics = [];
	private static $configs = [];
	private static $has_memcache;
	private static $Sesion;

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
	 */
	public static function setSession($Sesion) {
		if (empty(self::$Sesion)) {
			self::$Sesion = $Sesion;
		}
	}

	/**
	 * Devuelve los valores escritos por Conf::write().
	 * @param type $conf
	 * @return type
	 */
	public function read($conf) {
		if(isset(self::$statics[$conf])) {
			return self::$statics[$conf];
		} else if (isset(self::$configs[$conf])) {
			return self::$configs[$conf];
		} else if (!empty(self::$Sesion)) {
			return self::GetConf(self::$Sesion, $conf);
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

	/**
	 * Obtener configuraciones desde la base de datos
	 *
	 * @param object $Sesion
	 * @param string $conf
	 * @return string
	 *
	 * Ahora comprueba si existe el array $Sesion->arrayconf para llenarlo una sola vez y consultar de él de ahí en adelante.
	 * Si no, intenta usar memcache
	 * @deprecated usar en su lugar Conf::setSession() y Conf::read()
	 */
	public static function GetConf(Sesion $Sesion, $conf) {
		global $memcache;
		self::setSession($Sesion);
		// nunca se sabe si correrán este código en una máquina sin MC. Primero se comprueba con isset para evitar un warning de undefined variable.
		self::$has_memcache = isset($memcache) && is_object($memcache);

		// Prioridad sobre los conf
		if (isset(self::$statics[$conf])) {
			// 1) Primera Prioridad: Siempre es más barato leer un método static de la clase conf que obtenerlo de memcache o de la base de datos.
			return self::{$conf}();
		} else if (count(self::$Sesion->arrayconf) > 0) {
			// 2) Segunda prioridad: leer de la memoria. Existe variable caching?
			// 2.1) Usar variable desde caching
			$array_conf = self::$Sesion->arrayconf;
			return $array_conf[$conf];
		} else if (self::$has_memcache && $array_conf = UtilesApp::utf8izar(json_decode($memcache->get(DBNAME . '_config'), true), false)) {
			// 3) Tercera prioridad: existe memcache y la llave de configuración está vigente.
			self::write($array_conf);
			self::$Sesion->arrayconf = $array_conf;
			return $array_conf[$conf];
		} else {
			// 4) Cuarta prioridad: tengo que obtener el dato de la BD, aprovecho de llenar el dato en memoria y en memcache.
			// 4.1) compruebo conexión a la BD para consultar array de configuraciones
			return self::getFromDb($conf, $memcache);
		}
	}

	private static function getFromDb($conf, $memcache) {
		if (!isset(self::$Sesion->pdodbh)) {
			return false;
		}
		$query = "SELECT glosa_opcion, valor_opcion FROM configuracion";
		$bd_configs = self::$Sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_NUM | PDO::FETCH_GROUP);
		foreach ($bd_configs as $glosa => $valor) {
			self::write($glosa, $valor[0][0]);
			self::$Sesion->arrayconf[$glosa] = $valor[0][0];
		}

		// 4.2) Si existe memcache, fijo la llave usando lo obtenido en 4.1
		if (self::$has_memcache) {
			$memcache->set(self::dbName() . '_config', json_encode(UtilesApp::utf8izar(self::$Sesion->arrayconf)), false, 120);
		}

		return self::$Sesion->arrayconf[$conf];
	}

}
