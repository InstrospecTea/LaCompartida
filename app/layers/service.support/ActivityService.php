<?php

class ActivityService extends AbstractService implements IActivityService {

	public function getDaoLayer() {
		return 'ActivityDAO';
	}

	public function getClass() {
		return 'Activity';
	}

}
