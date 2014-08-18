<?php
require_once dirname(__FILE__) . '/../conf.php';

class CobroAsunto extends Objeto {
	public function CobroAsunto($sesion, $fields = '', $params = '') {
		$this->tabla = 'cobro_asunto';
		$this->campo_id = 'id_cobro_asunto';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	public function LoadByCodigoAsunto($codigo, $id_cobro) {
		$query = "SELECT id_cobro_asunto FROM cobro_asunto WHERE codigo_asunto = '{$codigo}' AND id_cobro = '{$id_cobro}'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	public function agregarAsuntos($id_cobro, $codigo_cliente, $asuntos_activos = true) {
		$_asuntos_activos = $asuntos_activos ? '1' : '0';

		$query = "SELECT id_moneda, codigo_asunto FROM asunto WHERE codigo_cliente = '{$codigo_cliente}' AND activo = '{$_asuntos_activos}'";
		$rs = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while ($asunto = mysql_fetch_array($rs)) {
			$query = "INSERT INTO cobro_asunto SET id_cobro = '{$id_cobro}', codigo_asunto = '{$asunto['codigo_asunto']}', id_moneda = '{$asunto['id_moneda']}'
				ON DUPLICATE KEY UPDATE id_cobro = '{$id_cobro}', codigo_asunto = '{$asunto['codigo_asunto']}', id_moneda = '{$asunto['id_moneda']}'";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}
	}

	public function eliminarAsuntos($id_cobro) {
		$query = "DELETE FROM cobro_asunto WHERE id_cobro = '{$id_cobro}'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
	}
}
