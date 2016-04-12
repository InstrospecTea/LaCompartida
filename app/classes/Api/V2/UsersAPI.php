<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para Users
 *
 */
class UsersAPI extends AbstractSlimAPI {

	static $UserEntity = array(
		array('id' => 'id_usuario'),
		array('code' => 'rut'),
		array('name' => 'full_name'),
		'settings',
		'permissions'
	);

	public function getUserById($id) {
		$Slim = $this->slim;
		$this->validateAuthTokenSendByHeaders();

		if (is_null($id) || empty($id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		$UsersBusiness = new \UsersBusiness($this->session);
		$includes = array('settings');
		$user = $UsersBusiness->getUserById($id, $includes);

		if (empty($user)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		}
		// user permissions
		$roles = $UsersBusiness->getRoles($id);
		$permissions = ApiAuth::userPermissions($roles);
		$user->set('permissions', $permissions);
		$this->present($user, self::$UserEntity);
	}

}
