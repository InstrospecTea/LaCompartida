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

		$usuario = $this->Session->usuario->fields;
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
		$entities = $this->ProcessLockService->findAll($restrictions, array('id', 'proceso', 'estado', 'fecha_modificacion', 'datos_post'));
		return $entities;
	}

	public function getProcessLockById($id) {
		$restrictions = CriteriaRestriction::equals('id', $id);
		return $this->ProcessLockService->findFirst($restrictions);
	}

	public function getProcessLockedByUserId($user_id, $process_name) {
		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('id_usuario', $user_id),
			CriteriaRestriction::equals('proceso', "'{$process_name}'"),
			CriteriaRestriction::equals('bloqueado', '1')
		);
		$entities = $this->ProcessLockService->findFirst($restrictions, array('id', 'bloqueado'));
		return $entities;
	}

	public function getProcessLockNotNotifiedByUserId($user_id, $process_name) {
		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('id_usuario', $user_id),
			CriteriaRestriction::equals('proceso', "'{$process_name}'"),
			CriteriaRestriction::equals('bloqueado', '0'),
			CriteriaRestriction::equals('notificado', '0')
		);
		return $this->ProcessLockService->findFirst($restrictions);
	}

	public function getNotificationHtml($entity) {
		$proceso = \TTB\Utiles::humanize(\TTB\Utiles::underscoreize($entity->get('proceso')));
		$fecha = Utiles::sql3fecha($entity->get('fecha_modificacion'), '%d-%m-%Y a las %H:%M hrs.');
		$form_link = $this->getFormLink($entity->get('proceso'), $entity->get('datos_post'), $entity->get('id'));
		$html = "<b>{$proceso}</b><br/>{$fecha}<br/>{$entity->get('estado')}<br/>{$form_link}";
		return $html;
	}

	public function getFormLink($proceso, $data, $id) {
		$data = json_decode($data, true);
		switch ($proceso) {
			case 'GeneracionMasivaCobros':
				$url = '/app/interfaces/genera_cobros.php';
				$data['opc'] = 'buscar';
				break;
			default:
				return '';
		}
		$Form = new Form();
		$html = '';
		foreach ($data as $field => $value) {
			$html .= $Form->hidden($field, $value, array('id' => false));
		}
		$html .= $Form->Html->link('Ir al formulario', '#', array('onclick' => "ir_al_formulario($id, this); return false;"));
		return $Form->Html->tag('form', $html, array('action' => Conf::RootDir() . $url, 'method' => 'post'));
	}

}
