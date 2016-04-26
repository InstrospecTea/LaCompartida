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

	/**
	 * Obtiene un proceso según su id
	 * @param type $id
	 */
	public function getProcessLockById($id);

	/**
	 * Obtiene un proceso bloqueado según el id de usuario
	 * @param type $user_id
	 * @param type $process_name
	 */
	public function getProcessLockedByUserId($user_id, $process_name);

	/**
	 * Obtiene un proceso desbloqueado que no ha sido notificado según el id de usuario
	 * @param type $user_id
	 * @param type $process_name
	 */
	public function getProcessLockNotNotifiedByUserId($user_id, $process_name);

	/**
	 * Genera el html con la notificación del proceso finalizado.
	 * @deprecated Este metodo debe ser pasado a un View::element() (se utiliza en /app/classes/BloqueoProceso.php)
	 * @return string
	 */
	public function getNotificationHtml($entity);

	/**
	 * Genera un formulario con un link para volver al formulario original de donde se ejecuta el Proceso
	 * @param type $proceso
	 * @param type $data
	 * @param type $id
	 * @deprecated Este metodo debe ser pasado a un View::element() (se utiliza en /app/classes/BloqueoProceso.php)
	 * @return string
	 */
	public function getFormLink($proceso, $data, $id);
}
