<?php
namespace Database;

class Conf {

	static $credentials = array(
		'user_name' => 'admin',
		'password' => 'admin1awdx'
	);

	static function getUserName() {
		return self::$credentials['user_name'];
	}

	static function getPassword() {
		return self::$credentials['password'];
	}
}
