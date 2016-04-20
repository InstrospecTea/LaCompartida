<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para login en API
 *
 */
class LoginAPI extends AbstractSlimAPI {

	public function login() {
		$Session = $this->session;
		$Slim = $this->slim;

		$UserToken = new \UserToken($Session);

		$params = array();
		if ($Slim->request()->params('user')) {
			$params['user'] = $Slim->request()->params('user');
			$params['password'] = $Slim->request()->params('password');
			$params['app_key'] = $Slim->request()->params('app_key');
		} else {
			$params = json_decode($Slim->request()->getBody(), true);
		}

		$user = $params['user'];
		$password = $params['password'];
		$app_key = $params['app_key'];
		$auth_token = $UserToken->makeAuthToken($user);
		$dv = null;

		if (empty($user)) {
			$this->halt(__('Invalid user data'), 'InvalidUserData');
		}

		if (empty($password)) {
			$this->halt(__('Invalid password data'), 'InvalidPasswordData');
		}

		if (empty($app_key)) {
			$this->halt(__('Invalid application key data'), 'InvalidAppKey');
		}

		if (strtolower(\UtilesApp::Getconf($Session, 'NombreIdentificador')) == 'rut') {
			$user_array = preg_split('/[-]/', $user);
			$user = $user_array[0];
			if (count($user_array) == 2) {
				$dv = $user_array[1];
			}
		}

		if (!$Session->login($user, $dv, $password)) {
			$this->halt(__('User or password is incorrect'), 'UserDoesntExist');
		} else {
			$user_token_data = array(
				'user_id' => $Session->usuario->fields['id_usuario'],
				'auth_token' => $auth_token,
				'app_key' => $app_key
			);

			if (!$UserToken->save($user_token_data)) {
				$this->halt(__('Unexpected error when saving data'), 'UnexpectedSave');
			}
		}

		$this->getAppIdByAppKey($app_key);

		$this->outputJson(
			array(
				'auth_token' => $auth_token,
				'user_id' => $Session->usuario->fields['id_usuario']
			)
		);
	}

}
