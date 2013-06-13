<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';

class UserDevice extends Objeto {
	/**
	 * Find by token
	 * Return an array with next elements:
	 * 	id, user_id, token, created and modified
	 */
	function findByToken($token, $user_id) {

		$sql = "SELECT `user_device`.`id`
			FROM `user_device`
			WHERE `user_device`.`user_id` = :user_id
				AND `user_device`.`token` = :token";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('token', $token);
		$Statement->bindParam('user_id', $user_id);
		$Statement->execute();

		$user_device_data = $Statement->fetchObject();

		if (is_object($user_device_data)) {
			return $this->findById($user_device_data->id);
		} else {
			return false;
		}
	}

	/**
	 * Find by ID
	 * Return an array with next elements:
	 * 	id, user_id, token, created and modified
	 */
	function findById($id) {
		$sql = "SELECT `user_device`.`id`, `user_device`.`user_id`, `user_device`.`token`, `user_device`.`created`, `user_device`.`modified`
			FROM `user_device`
			WHERE `user_device`.`id`=:id";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('id', $id);
		$Statement->execute();

		$user_device_data = $Statement->fetchObject();

		if (is_object($user_device_data)) {
			return $user_device_data;
		} else {
			return false;
		}
	}

	/**
	 * Find by User ID
	 * Return an array with device tokens
	 */
	public function tokensByUserId($user_id) {
		$sql = "SELECT `user_device`.`token`
			FROM `user_device`
			WHERE `user_device`.`user_id`=:user_id";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('user_id', $user_id);
		$Statement->execute();
		$tokens = array();
		while ($device = $Statement->fetch(PDO::FETCH_OBJ)) {
			array_push($tokens, $device->token);
		}
		return $tokens;
	}

	/**
	 * Update all devices with an invalid token
	 */
	function updateInvalidToken($last_token, $device_token) {
		if (is_null($last_token)) {
			return false;
		}

		if (is_null($device_token)) {
			return false;
		}

		$sql = "UPDATE `user_device`
				SET `user_device`.`modified` 	= :modified,
						`user_device`.`token` 		= :device_token
				WHERE `user_device`.`token`		= :last_token";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('modified', date('Y-m-d H:i:s'));
		$Statement->bindParam('device_token', $device_token);
		$Statement->bindParam('last_token', $last_token);

		return $Statement->execute();
	}

	/**
	 * Save data
	 * returns true if the update or insert completed successfully
	 */
	function save($data) {
		if (!isset($data['user_id']) || empty($data['user_id'])) {
			return false;
		}

		if (!isset($data['token']) || empty($data['token'])) {
			return false;
		}

		$user_device_data = $this->findByToken($data['token']);

		// if exist the auth_token then replace for the new one
		if (is_object($user_device_data)) {
			$sql = "UPDATE `user_device`
				SET `user_device`.`modified`=:modified
				WHERE `user_device`.`user_id`=:user_id AND `user_device`.`token`=:token";

			$Statement = $this->sesion->pdodbh->prepare($sql);
			$Statement->bindParam('modified', date('Y-m-d H:i:s'));
		} else {
			// if not exist then create the auth_token
			$sql = "INSERT INTO `user_device`
				SET `user_device`.`user_id`=:user_id, `user_device`.`token`=:token, `user_device`.`created`=:created";

			$Statement = $this->sesion->pdodbh->prepare($sql);
			$Statement->bindParam('created', date('Y-m-d H:i:s'));
		}

		$Statement->bindParam('user_id', $data['user_id']);
		$Statement->bindParam('token', $data['token']);

		return $Statement->execute();
	}

	/**
	 * Delete data
	 * returns true if the delete completed successfully, else false
	 */
	function delete($id) {
		if (!isset($id) || empty($id)) {
			return false;
		}

		$sql = "DELETE FROM `user_device` WHERE `user_device`.`id`=:id";
		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('id', $id);

		return $Statement->execute();
	}

	/**
	 * Delete data by Token
	 * returns true if the delete completed successfully, else false
	 */
	function deleteByToken($token) {
		if (!isset($token) || empty($token)) {
			return false;
		}

		$sql = "DELETE FROM `user_device` WHERE `user_device`.`token`=:token";
		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('token', $token);

		return $Statement->execute();
	}
}
