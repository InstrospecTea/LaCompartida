<?php

class LogDatabaseService extends AbstractService implements ILogDatabaseService {

	public function getDaoLayer() {
		return 'LogDatabaseDAO';
	}

	public function getClass() {
		return 'LogDatabase';
	}

}
