<?php

class UserPermissionService extends AbstractService implements IUserPermissionService {

	public function getClass() {
		return 'UserPermission';
	}

	public function getDaoLayer() {
		return 'UserPermissionDAO';
	}

}
