<?php


class PermissionService extends AbstractService implements IPermissionService {

	public function getClass() {
		return 'Permission';
	}

	public function getDaoLayer() {
		return 'PermissionDAO';
	}

}
