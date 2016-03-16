<?php

/**
 * Class ProcessLock
 * Clase que representa un proceso bloqueado en TheTimeBilling.
 */
class ProcessLock extends Entity {

    public function getIdentity() {
        return 'id';
    }

    public function getPersistenceTarget() {
        return 'bloqueo_procesos';
    }

	public function getTableDefaults() {
		return array();
	}

	protected function getFixedDefaults() {
		return array();
	}
}
