<?php
class ProcessLockService extends AbstractService implements IProcessLockService {

	public function getDaoLayer() {
		return 'ProcessLockDAO';
	}


	public function getClass() {
		return 'ProcessLock';
	}

}
