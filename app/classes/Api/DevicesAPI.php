<?php
/**
 *
 * Clase con métodos para Dispositivos
 *
 */
class DevicesAPI extends AbstractSlimAPI {

	public function findOrCreateDeviceByUserId($user_id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new Usuario($Session);
		$UserDevice = new UserDevice($Session);

		$device_token = $Slim->request()->params('token');
		$last_token = $Slim->request()->params('lastToken');
		$new_device = array();

		if (is_null($device_token) || empty($device_token)) {
			$this->halt(__('Invalid token device'), 'InvalidTokenDevice');
		}

		$new_device['user_id'] = $user_id;
		$new_device['token'] = $device_token;

		if (!$User->LoadId($user_id)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			if (!is_null($last_token) && !empty($last_token)) {
				$UserDevice->updateInvalidToken($last_token, $device_token);
			}
			$device = $UserDevice->findByToken($device_token, $user_id);
			if (!is_object($device)){
				if (!$UserDevice->save($new_device)) {
					$this->halt(__('Unexpected error when saving data'), 'UnexpectedSave');
				} else {
					$device = $UserDevice->findByToken($device_token, $user_id);
				}
			}
		}

		$this->outputJson($device);
	}

	public function deleteDeviceByUserId($user_id, $token) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($user_id) || empty($user_id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		if (is_null($token) || empty($token)) {
			$this->halt(__('Invalid token device'), 'InvalidTokenDevice');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new Usuario($Session);
		$UserDevice = new UserDevice($Session);

		if (!$User->LoadId($user_id)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			$device = $UserDevice->findByToken($token, $user_id);
			if (is_object($device)) {
				if (!$UserDevice->delete($device->id)) {
					$this->halt(__('Unexpected error deleting data'), 'UnexpectedDelete');
				}
			} else {
				$this->halt(__("The user device doesn't exist"), 'UserDeviceDoesntExist');
			}
		}

		$this->outputJson(array('result' => 'OK'));
	}

}
