<?php

class ProcessLockingBusiness extends AbstractBusiness implements IProcessLockingBusiness {

	public function __construct(Sesion $Session) {
		parent::__construct($Session);
		$this->loadService('ProcessLock');
	}

	public function lock($process, $status = '', $post = '') {
		if ($this->isLocked($process)) {
			return true;
		}

		$usuario = $this->sesion->usuario->fields;
		$entity = new ProcessLock();
		$entity->set('proceso', $process);
		$entity->set('estado', $status);
		$entity->set('id_usuario', $usuario['id_usuario']);
		$entity->set('nombre_usuario', trim("{$usuario['apellido1']} {$usuario['apellido2']}, {$usuario['nombre']}"));
		$entity->set('bloqueado', 1);
		$entity->set('datos_post', empty($post) ? null : $post);
		$saved = $this->ProcessLockService->saveOrUpdate($entity);
		return $saved;
	}

	public function unlock($process) {
		if (!$this->isLocked($process)) {
			return false;
		}

		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('proceso', "'$process'"),
			CriteriaRestriction::equals('bloqueado', 1)
		);
		$entity = $this->ProcessLockService->findFirst($restrictions, array('id', 'bloqueado'));

		if ($entity === false) {
			return false;
		}
		$entity->set('bloqueado', '0');
		$saved = $this->ProcessLockService->saveOrUpdate($entity);
		return $saved !== false;
	}

	public function updateStatus($process, $status) {
		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('proceso', "'$process'"),
			CriteriaRestriction::equals('bloqueado', 1)
		);
		$entity = $this->ProcessLockService->findFirst($restrictions, array('id', 'estado'));
		if ($entity === false) {
			return false;
		}

		$entity->set('estado', $status);
		$saved = $this->ProcessLockService->saveOrUpdate($entity);
		return $saved;
	}

	public function isLocked($process) {
		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('proceso', "'$process'"),
			CriteriaRestriction::equals('bloqueado', 1)
		);
		$entity = $this->ProcessLockService->findFirst($restrictions, array('id'));
		return $entity !== false;
	}

	public function getLocker($process) {
		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('proceso', "'$process'"),
			CriteriaRestriction::equals('bloqueado', 1)
		);
		$entity = $this->ProcessLockService->findFirst($restrictions, array('proceso', 'estado', 'id_usuario', 'nombre_usuario'));
		return $entity;
	}

	public function setNotified($id) {
		$restrictions = CriteriaRestriction::equals('id', $id);

		$entity = $this->ProcessLockService->findFirst($restrictions, array('id', 'notificado'));
		if ($entity === false) {
			return false;
		}

		$entity->set('notificado', '1');

		$saved = $this->ProcessLockService->saveOrUpdate($entity);
		return $saved !== false;
	}

	public function getNotifications($user_id) {
		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('id_usuario', $user_id),
			CriteriaRestriction::equals('bloqueado', '0'),
			CriteriaRestriction::equals('notificado', '0')
		);
		$entities = $this->ProcessLockService->findAll($restrictions, array('id', 'proceso', 'estado', 'fecha_modificacion'));
		return $entities;
	}

}
