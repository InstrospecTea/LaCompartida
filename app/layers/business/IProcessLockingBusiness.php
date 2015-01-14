<?php

interface IProcessLockingBusiness {

	/**
	 * Registra un proceso como bloqueado
	 * @param string $process
	 * @param string $status
	 * @param string $post JSON que representa los datos de un formulario
	 */
	public function lock($process, $status = '', $post = '');

	/**
	 * Marca un proceso como desbloqueado
	 * @param type $process
	 */
	public function unlock($process);

	/**
	 * actualiza el estado del proceso bloqueado
	 * @param type $process
	 * @param type $status
	 */
	public function updateStatus($process, $status);

	/**
	 * Verifica si el proceso está bloqueado
	 * @param type $process
	 */
	public function isLocked($process);

	/**
	 * Obtiene los datos del proceso bloqueado
	 * @param type $process
	 */
	public function getLocker($process);

	/**
	 * Marca el proceso como notificado
	 * @param type $id
	 */
	public function setNotified($id);

	/**
	 * Obtiene las notificaciones pendientes del usuario
	 * @param type $user_id
	 */
	public function getNotifications($user_id);
}