<?php

require_once dirname(__FILE__) . '/../conf.php';

class TramiteTarifa extends Objeto {

	function TramiteTarifa($Sesion, $fields = '', $params = '') {
		parent::__construct($Sesion, $fields, $params, 'tramite_tarifa', 'id_tramite_tarifa', 'glosa_tramite_tarifa');
	}

	/**
	 * Carga a través de ID
	 * @param integer $id_tramite_tarifa
	 */
	function LoadById($id_tramite_tarifa) {
		$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE id_tramite_tarifa = '$id_tramite_tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	/**
	 * Elimina Tarifas
	 * @return boolean if could delete a rate returns true, otherwise return false
	 */
	function Eliminar() {
		if (!$this->Loaded()) {
			return false;
		}

		// validate if the last rate, can not be removed
		if ($this->countRates() <= 1) {
			return false;
		}

		$query = "DELETE FROM tramite_valor WHERE id_tramite_tarifa = '" . $this->fields['id_tramite_tarifa'] . "'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM tramite_tarifa WHERE id_tramite_tarifa = '" . $this->fields['id_tramite_tarifa'] . "'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	/**
	 * returns the total records of rates
	 * @return integer
	 */
	function countRates() {
		$count = 0;

		$query = "SELECT COUNT(1) FROM {$this->tabla}";
		$rs = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($rs);

		return (int) $count;
	}

	/**
	 * returns the id of first record
	 * @return integer
	 */
	function getFirstIdRate() {
		$query = "SELECT {$this->tabla}.{$this->campo_id} FROM {$this->tabla} ORDER BY 1 ASC";
		$rs = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($rs);

		return (int) $id;
	}

	/**
	 * Carga la tarifa para el usuario y moneda correspondiente
	 * @param integer $id_tramite_tipo
	 * @param integer $id_moneda
	 * @param integer $id_tramite_tarifa
	 */
	function LoadByTramiteMoneda($id_tramite_tipo, $id_moneda, $id_tramite_tarifa) {
		$query = "SELECT id_tramite_valor FROM tramite_valor WHERE id_tramite_tipo = '$id_tramite_tipo' AND id_moneda = '$id_moneda' AND id_tarifa = '$id_tramite_tarifa' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $id;
	}

	/**
	 * Limpia Tarifa por defecto
	 * @param integer $id_tramite_tarifa
	 */
	function TarifaDefecto($id_tramite_tarifa) {
		$query = "UPDATE tramite_tarifa SET tarifa_defecto = 0 WHERE id_tramite_tarifa != '$id_tramite_tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	/**
	 * Retorna ID tarifa declarada como defecto
	 */
	function SetTarifaDefecto() {
		$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto = 1 LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_tramite_tarifa) = mysql_fetch_array($resp);
		return $id_tramite_tarifa;
	}

}

Class TramiteValor extends Objeto {

	function TramiteValor($sesion, $fields = "", $params = "") {
		$this->tabla = "tramite_valor";
		$this->campo_id = "id_tramite_valor";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	/**
	 * Carga a través de ID_TARIF, ID_USUARIO, ID_MONEDA la tarifa(monto)
	 * @param integer $id_tramite_valor
	 * @param integer $id_tramite_tipo
	 * @param integer $id_moneda
	 */
	function LoadById($id_tramite_valor, $id_tramite_tipo, $id_moneda) {
		$query = "SELECT tarifa FROM tramite_valor WHERE id_tramite_valor = '$id_tramite_valor' AND id_moneda = '$id_moneda' AND id_tramite_tipo = '$id_tramite_tipo' LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($tarifa) = mysql_fetch_array($resp);
		return $tarifa;
	}

	/**
	 * Guardando Tarifa
	 * @param integer $id_tramite_tarifa
	 * @param integer $id_tramite_tipo
	 * @param integer $id_moneda
	 * @param integer $valor
	 */
	function GuardarTarifa($id_tramite_tarifa, $id_tramite_tipo, $id_moneda, $valor) {
		$valor = str_replace(',', '.', $valor);
		if (empty($id_tramite_tarifa)) {
			$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto = 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list( $id_tramite_tarifa ) = mysql_fetch_array($resp);
		}
		if (empty($id_tramite_tarifa))
			return false;
		if ($valor == '') {
			$query = "DELETE FROM tramite_valor WHERE id_moneda = '$id_moneda' AND id_tramite_tipo = '$id_tramite_tipo' AND id_tramite_tarifa = '$id_tramite_tarifa'";
		} else {
			$query = "INSERT tramite_valor SET id_moneda = '$id_moneda',
								id_tramite_tipo = '$id_tramite_tipo', id_tramite_tarifa = '$id_tramite_tarifa' , tarifa = '$valor'
								ON DUPLICATE KEY UPDATE tarifa = '$valor'";
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

}
