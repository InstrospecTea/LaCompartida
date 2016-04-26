<?php

class WorkingAreaService extends AbstractService implements IWorkingAreaService {

	public function getDaoLayer() {
		return 'WorkingAreaDAO';
	}

	public function getClass() {
		return 'WorkingArea';
	}

}
