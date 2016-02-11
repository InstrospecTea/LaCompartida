<?php
namespace Database;

class Conf {

	static $config_hash = array(
		'user_name' => 'admin',
		'password' => 'admin1awdx',
		'mailer' => array(
			'host' => 'email-smtp.us-east-1.amazonaws.com',
			'user_name' => 'AKIAIDG2BX4WGJMFC2TA',
			'password' => 'Aqru/Fbu3Yu7gjrYoTUhpYgEA2KFArUHQ7krh1/yjoO4',
			'receptors' => 'ttbc-devs@lemontech.cl',
			'sender' => 'migraciones@lemontech.cl',
			'sender_alias' => 'Migraciones TTB-C',
			'subject' => 'Error en Migración'
		)
	);

	static function get($key, $array = null) {
		$path = explode('.', $key);
		$focus = self::$config_hash;

		if (!is_null($array)) {
			$focus = $array;
		}

		if (count($path) == 1) {
			return self::getValue($path[0], $focus);
		} else {
			$key = array_shift($path);
			$focus = $focus[$key];
			return self::get(
				implode('.', $path),
				$focus
			);
		}
	}

	private static function getValue($key, $array) {
		if (array_key_exists($key, $array)) {
			return $array[$key];
		}
	}
}
