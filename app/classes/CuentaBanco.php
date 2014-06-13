<?php

require_once dirname(__FILE__) . '/../conf.php';

class CuentaBanco extends Objeto {

	public static $llave_carga_masiva = 'glosa';

	public function CuentaBanco($sesion, $fields = "", $params = "") {
		$this->tabla = 'cuenta_banco';
		$this->campo_id = 'id_cuenta';
		$this->campo_glosa = 'glosa';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	public function IdBancoDeCuenta($id_cuenta) {
		if (empty($id_cuenta) || !is_numeric($id_cuenta)) {
			return '';
		}
		$query = "SELECT id_banco FROM cuenta_banco
					WHERE id_cuenta = '{$id_cuenta}'";
		$qr = $this->sesion->pdodbh->query($query);
		$banco = $qr->fetch(PDO::FETCH_ASSOC);
		return empty($banco) ? 0 : $banco['id_banco'];
	}
	public function ListarDelBanco($id_banco) {
		$where = $id_banco ? "cuenta_banco.id_banco = '$id_banco' " : '0';
		$this->campo_glosa = "CONCAT(cuenta_banco.numero, IF(prm_moneda.glosa_moneda IS NOT NULL, CONCAT(' (', prm_moneda.glosa_moneda ,')'), ''))";
		$query_extra = "LEFT JOIN prm_moneda
						ON prm_moneda.id_moneda = cuenta_banco.id_moneda
					WHERE $where";
		return $this->Listar($query_extra);
	}

}
