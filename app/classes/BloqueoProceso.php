<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Clase para manejar el bloqueo de procesos.
 * La lógica se encuentra en la capa cebolla, esta clase solo se creó para ser usada por las interfaces.
 */
class BloqueoProceso {

	public function __construct($Session) {
		$this->ProcessLockingBusiness = new ProcessLockingBusiness($Session);
	}

	/**
	 * Marca como bloqueado el proceso indicado.
	 * @param type $process
	 * @param type $status
	 */
	public function lock($process, $status) {
		return $this->ProcessLockingBusiness->lock($process, $status);
	}

	/**
	 * Marca como desbloqueado el proceso indicado.
	 * @param type $process
	 */
	public function unlock($process) {
		return $this->ProcessLockingBusiness->unlock($process);
	}

	/**
	 * Actualiza el estado de un proceso bloqueado
	 * @param string $process
	 * @param string $status
	 * @return type
	 */
	public function updateStatus($process, $status) {
		return $this->ProcessLockingBusiness->updateStatus($process, $status);
	}

	/**
	 * Verifica si el proceso está bloqueado
	 * @param type $process
	 * @return type
	 * @return boolean
	 */
	public function isLocked($process) {
		return $this->ProcessLockingBusiness->isLocked($process);
	}

	/**
	 * Devuelve informacion del proceso bloqueado.
	 * @param type $process
	 * @return type
	 */
	public function getLocker($process) {
		return $this->ProcessLockingBusiness->getLocker($process);
	}

	/**
	 * Marca un proceso como notificado
	 * @param type $id
	 * @return type
	 */
	public function setNotified($id) {
		return $this->ProcessLockingBusiness->setNotified($id);
	}

	/**
	 * Obtiene las notificaciones pendientes para el usuario
	 * @param type $user_id
	 * @return boolean
	 */
	public function getNotifications($user_id) {
		return $this->ProcessLockingBusiness->getNotifications($user_id);
	}

}
