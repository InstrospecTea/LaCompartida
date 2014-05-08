<?php
require_once dirname(__FILE__) . '/../conf.php';

Class UsuarioPermiso extends Objeto {

	public $cupo_profesionales;
	public $cupo_administrativos;
	public $error;

	public function __construct($Sesion, $fields = null, $params = null) {
		$this->tabla = 'usuario_permiso';
		$this->tabla_usuario = 'usuario';
		$this->campo_id = 'id_usuario';
		$this->Sesion = $Sesion;
		$this->fields = $fields;

		$this->cupo_profesionales = (int) Conf::GetConf($this->Sesion, 'CupoUsuariosProfesionales');
		$this->cupo_administrativos = (int) Conf::GetConf($this->Sesion, 'CupoUsuariosAdministrativos');
	}

	public function existeCupo($id_usuario, $permiso) {
		$retorno = true;

		$_query = "SELECT `{$this->tabla}`.`{$this->campo_id}`,
				SUM(IF(`{$this->tabla}`.`codigo_permiso` = 'PRO', 1, 0)) AS `profesional`,
				SUM(IF(`{$this->tabla}`.`codigo_permiso` = 'SADM', 1, 0)) AS `super_administrador`
			FROM `{$this->tabla}`
				INNER JOIN `{$this->tabla_usuario}` ON `{$this->tabla_usuario}`.`{$this->campo_id}` = `{$this->tabla}`.`{$this->campo_id}`
			WHERE `{$this->tabla}`.`codigo_permiso` != 'ALL' AND `{$this->tabla}`.`{$this->campo_id}` != '{$id_usuario}' AND `{$this->tabla_usuario}`.`activo` = '1'
			GROUP BY `{$this->tabla}`.`{$this->campo_id}`";

		switch ($permiso) {
			case 'PRO':
				$query = "SELECT COUNT(*) FROM ({$_query}) AS `tmp` WHERE `profesional` = 1 AND `super_administrador` = 0;";
				$total_profesionales = (int) $this->Sesion->pdodbh->query($query)->fetch(PDO::FETCH_COLUMN, 0);
				if ($total_profesionales >= $this->cupo_profesionales) {
					$retorno = false;
				}
				break;
			default:
				$query = "SELECT COUNT(*) FROM ({$_query}) AS `tmp` WHERE `profesional` = 0 AND `super_administrador` = 0;";
				$total_administrativos = (int) $this->Sesion->pdodbh->query($query)->fetch(PDO::FETCH_COLUMN, 0);

				if ($total_administrativos >= $this->cupo_administrativos) {
					$retorno = false;
				}
		}

		return $retorno;
	}

	public function puedeAsignarPermiso($id_usuario, $permiso) {
		$retorno = true;

		if (!$this->existeCupo($id_usuario, $permiso)) {
			$retorno = false;
			// si el usuario tiene permisos de profesional también puede ser un administrativo
			if ($this->tienePermiso($id_usuario, 'PRO')) {
				$retorno = true;
			}
		}

		return $retorno;
	}

	public function puedeRevocarPermiso($id_usuario, $permiso) {
		$retorno = true;

		switch ($permiso) {
			case 'PRO':
				// si es profesional y tiene otros permisos hay que revisar si existe cupo para otro administrativo
				if ($this->esAdministrativo($id_usuario) && !$this->existeCupo($id_usuario, 'ADM')) {
					$this->error = "No se puede revocar el permiso 'PRO', no quedan cupos para usuarios administrativos";
					$retorno = false;
				}
				break;
		}

		return $retorno;
	}

	public function asignarPermiso($id_usuario, $permiso) {
		$query = "INSERT INTO `{$this->tabla}` SET `{$this->tabla}`.`{$this->campo_id}` = '{$id_usuario}', `{$this->tabla}`.`codigo_permiso` = '{$permiso}' ON DUPLICATE KEY UPDATE `{$this->tabla}`.`id_usuario` = '{$id_usuario}';";
		return $this->Sesion->pdodbh->query($query);
	}

	public function revocarPermiso($id_usuario, $permiso) {
		$query = "DELETE FROM `{$this->tabla}` WHERE `{$this->tabla}`.`{$this->campo_id}` = '{$id_usuario}' AND `{$this->tabla}`.`codigo_permiso` = '{$_POST['permiso']}';";
		return $this->Sesion->pdodbh->query($query);
	}

	public function esAdministrativo($id_usuario) {
		$query = "SELECT COUNT(*) FROM `{$this->tabla}` WHERE `{$this->tabla}`.`{$this->campo_id}` = '{$id_usuario}' AND `{$this->tabla}`.`codigo_permiso` NOT IN ('SADM', 'ALL', 'PRO');";

		$es_administrativo = (int) $this->Sesion->pdodbh->query($query)->fetch(PDO::FETCH_COLUMN, 0);

		return !empty($es_administrativo) ? true : false;
	}

	public function esProfesional($id_usuario) {
		$query = "SELECT COUNT(*) FROM `{$this->tabla}` WHERE `{$this->tabla}`.`{$this->campo_id}` = '{$id_usuario}' AND `{$this->tabla}`.`codigo_permiso` = 'PRO';";

		$es_profesional = (int) $this->Sesion->pdodbh->query($query)->fetch(PDO::FETCH_COLUMN, 0);

		return !empty($es_profesional) ? true : false;
	}

	private function tienePermiso($id_usuario, $permiso) {
		$query = "SELECT COUNT(*) FROM `{$this->tabla}` WHERE `{$this->tabla}`.`{$this->campo_id}` = '{$id_usuario}' AND `{$this->tabla}`.`codigo_permiso` = '{$permiso}';";
		$tiene_permiso = (int) $this->Sesion->pdodbh->query($query)->fetch(PDO::FETCH_COLUMN, 0);

		return !empty($tiene_permiso) ? true : false;
	}
}
