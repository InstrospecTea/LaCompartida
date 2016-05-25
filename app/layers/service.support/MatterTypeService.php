<?php

class MatterTypeService extends AbstractService implements IMatterTypeService {

	public function getDaoLayer() {
		return 'MatterTypeDAO';
	}

	public function getClass() {
		return 'MatterType';
	}

}
