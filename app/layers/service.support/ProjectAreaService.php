<?php

class ProjectAreaService extends AbstractService implements IProjectAreaService {

	public function getDaoLayer() {
		return 'ProjectAreaDAO';
	}

	public function getClass() {
		return 'ProjectArea';
	}

}
