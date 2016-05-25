<?php

class MatterExtraService extends AbstractService implements IMatterExtraService {

	public function getDaoLayer() {
		return 'MatterExtraDAO';
	}

	public function getClass() {
		return 'MatterExtra';
	}

	public function getByMatterId($matter_id) {
		return $this->findFirst(CriteriaRestriction::equals('id_asunto', $matter_id));
	}
}
