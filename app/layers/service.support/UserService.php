<?php


class UserService extends AbstractService implements IUserService {

	public function getClass() {
		return 'User';
	}

	public function getDaoLayer() {
		return 'UserDAO';
	}

	public function getCategory($id) {
		return $this->UserDAO->getCategory($id);
	}

}
