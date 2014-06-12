<?php

require_once dirname(__FILE__) . '/../conf.php';

Class UsuarioTarifa extends Objeto {

	function UsuarioTarifa($sesion, $fields = "", $params = "") {
		$this->tabla = "usuario_tarifa";
		$this->campo_id = "id_usuario_tarifa";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	// Carga a través de ID_TARIF, ID_USUARIO, ID_MONEDA la tarifa(monto)

	function LoadById($id_tarifa, $id_usuario, $id_moneda) {
		$query = "SELECT tarifa FROM usuario_tarifa WHERE id_tarifa = '$id_tarifa' AND id_moneda = '$id_moneda' AND id_usuario = '$id_usuario' LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($tarifa) = mysql_fetch_array($resp);
		return $tarifa;
	}

	// Guardando Tarifa

	function GuardarTarifa($id_tarifa, $id_usuario, $id_moneda, $valor) {
		$valor = str_replace(',', '.', $valor);
		$this->LogCambio($id_tarifa, $id_usuario, $id_moneda, $valor);
		if ($valor == '') {
			$query = "DELETE FROM usuario_tarifa WHERE id_tarifa = '$id_tarifa' AND id_moneda = '$id_moneda' AND id_usuario = '$id_usuario'";
		} else {
			$query = "INSERT usuario_tarifa SET id_tarifa = '$id_tarifa', id_moneda = '$id_moneda',
								id_usuario = '$id_usuario', tarifa = '$valor'
								ON DUPLICATE KEY UPDATE tarifa = '$valor'";
            echo $query.'<hr>';
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function LogCambio($id_tarifa, $id_usuario, $id_moneda, $valor) {
		$valor_original = (float) $this->LoadById($id_tarifa, $id_usuario, $id_moneda);
		$id = 1000000 * $id_tarifa + $id_usuario;
		$glosa_moneda = Moneda::GetGlosaMoneda($this->sesion, $id_moneda);

		$LogDB = new LogDB($this->sesion);
		$LogDB->Loggear($this->tabla, $id, $glosa_moneda, (float) $valor, $valor_original);
	}

}