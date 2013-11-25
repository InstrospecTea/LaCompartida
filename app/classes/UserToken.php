<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';

class UserToken extends Objeto {
	/**
	 * Find by ID
	 * Return an array with next elements:
	 * 	id, user_id, auth_token, app_key, expiry_date, created and modified
	 */
	function findById($id) {
		$sql = "SELECT `user_token`.`id`, `user_token`.`user_id`, `user_token`.`auth_token`, `user_token`.`app_key`,
				`user_token`.`expiry_date`, `user_token`.`created`, `user_token`.`modified`
			FROM `user_token`
			WHERE `user_token`.`id`=:id";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('id', $id);
		$Statement->execute();

		$user_token_data = $Statement->fetchObject();

		if (is_object($user_token_data)) {
			return $user_token_data;
		} else {
			return false;
		}
	}

	/**
	 * Find by auth token
	 * Returns an array with next elements:
	 * 	id, user_id, auth_token, app_key, expiry_date, created and modified
	 */
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

	/**
	 * Save data
	 * returns a bool if the update or insert completed successfully
	 */
	function save($data) {
		if (!isset($data['user_id']) || empty($data['user_id'])) {
			return false;
		}

		$user_token_data = null;

		if (isset($data['id'])) {
			$user_token_data = $this->findById($data['id']);
		}
		$expiry_date = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s'))) . ' +1 month');
		// if exist the auth_token then replace for the new one
		if (is_object($user_token_data)) {
			$sql = "UPDATE `user_token` SET `user_token`.`modified`=:modified, `user_token`.`expiry_date`=:expiry_date WHERE `user_token`.`user_id`=:id";
			$Statement = $this->sesion->pdodbh->prepare($sql);
			$Statement->bindParam('modified', date('Y-m-d H:i:s'));
			$Statement->bindParam('expiry_date', date('Y-m-d H:i:s', $expiry_date));
			$Statement->bindParam('id', $user_token_data->id);
		} else {
			// if not exist then create the auth_token
			$sql = "INSERT INTO `user_token`
				SET `user_token`.`user_id`=:user_id, `user_token`.`auth_token`=:auth_token,
					`user_token`.`app_key`=:app_key, `user_token`.`created`=:created, `user_token`.`expiry_date`=:expiry_date";

			$Statement = $this->sesion->pdodbh->prepare($sql);
			$Statement->bindParam('user_id', $data['user_id']);
			$Statement->bindParam('auth_token', $data['auth_token']);
			$Statement->bindParam('app_key', $data['app_key']);
			$Statement->bindParam('expiry_date', date('Y-m-d H:i:s', $expiry_date));
			$Statement->bindParam('created', date('Y-m-d H:i:s'));
		}

		return $Statement->execute();
	}

	/**
	 * Make a Auth Token
	 * returns a string with a auth token
	 */
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

	/**
	 * returns a random char alphanumeric
	 */
	function randAlphanumeric() {
		$subsets[0] = array('min' => 48, 'max' => 57); // ascii digits
		$subsets[1] = array('min' => 65, 'max' => 90); // ascii lowercase English letters
		$subsets[2] = array('min' => 97, 'max' => 122); // ascii uppercase English letters
		// random choice between lowercase, uppercase, and digits
		$s = rand(0, 2);
		$ascii_code = rand($subsets[$s]['min'], $subsets[$s]['max']);
		return chr($ascii_code);
	}

	/**
	 * Delete data
	 * returns true if the delete completed successfully, else false
	 */
	function delete($id) {
		if (!isset($id) || empty($id)) {
			return false;
		}

		$sql = "DELETE FROM `user_token` WHERE `user_token`.`id`=:id";
		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('id', $id);

		return $Statement->execute();
	}

	/**
	 * Delete all user_tokens of an user
	 * returns true if the delete completed successfully, else false
	 */
	function deleteAll($user_id) {
		if (!isset($user_id) || empty($user_id)) {
			return false;
		}
		$sql = "DELETE FROM `user_token` WHERE `user_token`.`user_id`=:user_id";
		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('user_id', $user_id);

		return $Statement->execute();
	}
}
