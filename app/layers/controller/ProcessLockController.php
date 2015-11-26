<?php

class ProcessLockController extends AbstractController {

	public function __construct() {
		parent::__construct();
		$this->LoadBusiness('ProcessLocking');
	}

	public function is_locked($process) {
		$this->renderJSON(array('locked' => $this->ProcessLockingBusiness->isLocked($process)));
	}

	public function was_notified($process) {
		$this->renderJSON($this->ProcessLockingBusiness->wasNotified($process));
	}

	public function get_locker($process) {
		$this->renderJSON($this->ProcessLockingBusiness->getLocker($process)->fields);
	}

	public function set_notified($id) {
		$this->renderJSON(array('notified' => $this->ProcessLockingBusiness->setNotified($id)));
	}

	public function get_process_locked($process) {
		$user_id = $this->Session->usuario->fields['id_usuario'];
		$process = $this->ProcessLockingBusiness->getProcessLockedByUserId($user_id, $process);
		$this->renderJSON($process->fields);
	}

	public function get_process_lock_not_notified($process) {
		$user_id = $this->Session->usuario->fields['id_usuario'];
		$process = $this->ProcessLockingBusiness->getProcessLockNotNotifiedByUserId($user_id, $process);
		$this->renderJSON($process->fields);
	}

	public function get_notification_html($id) {
		$entity = $this->ProcessLockingBusiness->getProcessLockById($id);
		$this->data = $this->ProcessLockingBusiness->getNotificationHtml($entity);
		$this->render('/elements/plain_text', 'ajax');
	}

	/**
	 * Ejecuta un shell con el mismo nombre del proceso
	 * @param type $process es el nombre de clase de la Shell que será ejecutada.
	 */
	public function exec($process) {
		if ($this->request['method'] != 'post') {
			$this->renderJSON(array('error' => 'No se permite este metodo de ejecución.'));
		}
		if ($this->ProcessLockingBusiness->isLocked($process)) {
			$this->renderJSON(array('error' => 'El proceso indicado se encuentra bloqueado.', 'locker' => $this->ProcessLockingBusiness->getLocker($process)));
		}
		$shell = Utiles::underscoreize(Cobro::PROCESS_NAME);
		$data = $this->data;
		$data['user_id'] = $this->Session->usuario->fields['id_usuario'];
		$data['form']['cobrosencero'] = (empty($this->data['cobrosencero'])? 0 : 1);
		$data['form']['cobros_en_revision'] = (empty($this->data['cobros_en_revision'])? 0 : 1);
		$log_folder = Log::getFolder();
		$shell_cmd = sprintf("%s/console/console %s --domain=%s --subdir=%s --data='%s' >> {$log_folder}/ProcessLocking.exec.log &", ROOT_PATH, $shell, SUBDOMAIN, ROOTDIR, json_encode($data));
		exec($shell_cmd);
		$this->autoRender = false;
		$this->renderJSON(array('executing' => true));
	}

}
