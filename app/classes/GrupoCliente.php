<?php

require_once dirname(__FILE__) . '/../conf.php';

class GrupoCliente extends Objeto {
	public static $llave_carga_masiva = 'glosa_grupo_cliente';

	public function GrupoCliente($Sesion, $fields = '', $params = '') {
		$this->tabla = 'grupo_cliente';
		$this->campo_id = 'id_grupo_cliente';
		$this->campo_glosa = 'glosa_grupo_cliente';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	/**
	 * obtiene un arreglo de grupos de clientes
	 * @param  string $ordenar_por orden del arreglo
	 * @return array arreglo de grupos de clientes
	 */
	public function obtenerGrupos($ordenar_por = '') {
		$grupos = array();

		if (empty($ordenar_por)) {
			$ordenar_por = 'grupo_cliente.glosa_grupo_cliente';
		}

		$query = "SELECT grupo_cliente.id_grupo_cliente, grupo_cliente.glosa_grupo_cliente FROM grupo_cliente ORDER BY {$ordenar_por}";
		$grupo_cliente_rs = $this->sesion->pdodbh->query($query);
		$grupos = $grupo_cliente_rs->fetchAll(PDO::FETCH_ASSOC);

		return $grupos;
	}

	/**
	 * obtiene un arreglo de grupos de clientes según formato select de clase Form
	 * @param  Object &$Sesion Objeto sesion
	 * @param  string $ordenar_por orden del arreglo
	 * @return array arreglo de grupos de clientes
	 */
	public static function obtenerGruposSelect(&$Sesion, $ordenar_por = '') {
		$GrupoCliente = new GrupoCliente($Sesion);
		$grupos = $GrupoCliente->obtenerGrupos($ordenar_por);
		$_grupos = array();

		if (!empty($grupos)) {
			foreach ($grupos as $grupo) {
				$_grupos[$grupo['id_grupo_cliente']] = $grupo['glosa_grupo_cliente'];
			}
		}

		return $_grupos;
	}
}
