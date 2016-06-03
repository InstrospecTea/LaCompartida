<?php

class SettingService extends AbstractService implements ISettingService {

	public function getDaoLayer() {
		return 'SettingDAO';
	}

	public function getClass() {
		return 'Setting';
	}

}
