<?php

class MatterService extends AbstractService implements IMatterService {

	public function getDaoLayer() {
		return 'MatterDAO';
	}

	public function getClass() {
		return 'Matter';
	}

}
