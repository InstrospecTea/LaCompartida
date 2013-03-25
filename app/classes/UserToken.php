<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';

class UserToken extends Objeto {
	function findById($id) {
		echo $sql = "SELECT `user_token`.`id`, `user_token`.`auth_token`, `user_token`.`app_key`,
				`user_token`.`created`, `user_token`.`modified`
			FROM `user_token`
			WHERE `user_token`.`id`=:id";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('id', $id);
		$Statement->execute();

		$user_token_data = $Statement->fetchObject();

		var_dump($user_token_data); exit;

		if (is_object($user_token_data)) {
			return $user_token_data;
		} else {
			return false;
		}
	}

	function findByAuthToken($auth_token) {
		$sql = "SELECT `user_token`.`id` FROM `user_token` WHERE `user_token`.`auth_token`=:auth_token";
		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('auth_token', $auth_token);
		$Statement->execute();
		$user_token_data = $Statement->fetchObject();

		// if not exist the auth_token then return error
		if (!is_object($user_token_data)) {
			return false;
		} else {
			return $this->findById($user_token_data->id);
		}
	}

	function save($data) {
		if (!isset($data['id']) || empty($data['id'])) {
			return false;
		}

		$user_token_data = $this->findById($data['id']);

		// if exist the auth_token then replace for the new one
		if (is_object($user_token_data)) {
			$sql = "UPDATE `user_token`
				SET `user_token`.`auth_token`=:auth_token, `user_token`.`modified`=:modified
				WHERE `user_token`.`id`=:id";

			$Statement = $this->sesion->pdodbh->prepare($sql);
			$Statement->bindParam('auth_token', $data['auth_token']);
			$Statement->bindParam('id', $user_token_data->id);
			$Statement->bindParam('modified', date('Y-m-d H:i:s'));
		} else {
			// if not exist then create the auth_token
			$sql = "INSERT INTO `user_token`
				SET `user_token`.`id`=:id, `user_token`.`auth_token`=:auth_token,
					`user_token`.`app_key`=:app_key, `user_token`.`created`=:created";

			$Statement = $this->sesion->pdodbh->prepare($sql);
			$Statement->bindParam('id', $user_token_data->id);
			$Statement->bindParam('auth_token', $data['auth_token']);
			$Statement->bindParam('app_key', $data['app_key']);
			$Statement->bindParam('created', date('Y-m-d H:i:s'));
		}

		return $Statement->execute();
	}

	function makeAuthToken($secret) {
	  $str = '';
	  for ($i = 0; $i < 7; $i++) {
	  	$str .= $this->randAlphanumeric();
	  }

	  $pos = rand(0, 24);
	  $str .= chr(65 + $pos);
	  $str .= substr(md5($str . $secret), $pos, 8);
	  return sha1($str);
	}

	function randAlphanumeric() {
		$subsets[0] = array('min' => 48, 'max' => 57); // ascii digits
		$subsets[1] = array('min' => 65, 'max' => 90); // ascii lowercase English letters
		$subsets[2] = array('min' => 97, 'max' => 122); // ascii uppercase English letters
		// random choice between lowercase, uppercase, and digits
		$s = rand(0, 2);
		$ascii_code = rand($subsets[$s]['min'], $subsets[$s]['max']);
		return chr($ascii_code);
	}
}
