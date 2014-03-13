<?php

require_once dirname(__FILE__) . '/../conf.php';

Class CategoriaTarifa extends Objeto {

	function CategoriaTarifa($sesion, $fields = "", $params = "") {
		$this->tabla = "categoria_tarifa";
		$this->campo_id = "id_categoria_tarifa";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	// Carga a través de ID_TARIF, ID_CATEGORIA_USUARIO, ID_MONEDA la tarifa(monto)

	function LoadById($id_tarifa, $id_categoria_usuario, $id_moneda) {
		$query = "SELECT tarifa FROM categoria_tarifa WHERE id_tarifa = '$id_tarifa' AND id_moneda = '$id_moneda' AND id_categoria_usuario = '$id_categoria_usuario' LIMIT 1";
		$resp_categoria = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($tarifa_categoria) = mysql_fetch_array($resp_categoria);
		return $tarifa_categoria;
	}

	// Guardar Tarifa

	function GuardarTarifa($id_tarifa, $id_categoria_usuario, $id_moneda, $valor) {
		$valor = str_replace(',', '.', $valor);
		$this->LogCambio($id_tarifa, $id_categoria_usuario, $id_moneda, $valor);
		if ($valor == '') {
			$query = "DELETE FROM categoria_tarifa WHERE id_tarifa = '$id_tarifa' AND id_moneda = '$id_moneda' AND id_categoria_usuario = '$id_categoria_usuario'";
		} else {
			$query = "INSERT categoria_tarifa SET id_tarifa = '$id_tarifa', id_moneda = '$id_moneda',
								id_categoria_usuario = '$id_categoria_usuario', tarifa = '$valor'
								ON DUPLICATE KEY UPDATE tarifa = '$valor'";
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function TarifasCategorias($id_tarifa, $id_moneda, $prefijo = '') {
		$query = "SELECT c.glosa_categoria, IFNULL(ct.tarifa, 0) as tarifa
			FROM prm_categoria_usuario c
			LEFT JOIN categoria_tarifa ct
				ON c.id_categoria_usuario = ct.id_categoria_usuario
				AND ct.id_tarifa = $id_tarifa
				AND ct.id_moneda = $id_moneda";
		$valores = array();
		foreach ($this->sesion->pdodbh->query($query) as $tarifa) {
			$valores[$prefijo . $tarifa['glosa_categoria']] = $tarifa['tarifa'];
		}
		return $valores;
	}

	function LogCambio($id_tarifa, $id_categoria_usuario, $id_moneda, $valor) {
		$valor_original = (float) $this->LoadById($id_tarifa, $id_categoria_usuario, $id_moneda);
		$id = 1000000 * $id_tarifa + $id_categoria_usuario;
		$glosa_moneda = Moneda::GetGlosaMoneda($this->sesion, $id_moneda);

		$LogDB = new LogDB($this->sesion);
		$LogDB->Loggear($this->tabla, $id, $glosa_moneda, (float) $valor, $valor_original);
	}

}
