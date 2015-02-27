<?php

require_once dirname(__FILE__) . '/../conf.php';

class Moneda extends Objeto {

	public static $llave_carga_masiva = 'codigo';
	public static $arreglo_monedas;

	function Moneda($sesion, $fields = "", $params = "") {
		$this->tabla = 'prm_moneda';
		$this->campo_id = 'id_moneda';
		$this->campo_glosa = 'glosa_moneda';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	function LoadByCode($code) {
		$query = "SELECT id_moneda FROM prm_moneda WHERE codigo = '{$code}'";
		$rs = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($currency_id) = mysql_fetch_array($rs);
		if (!empty($currency_id)) {
			$this->Load($currency_id);
		}
	}

	function GuardaHistorial($sesion, $fecha) {
		$query = "INSERT INTO moneda_historial (id_moneda, fecha, valor, moneda_base, id_usuario)
					VALUES('" . $this->fields["id_moneda"] . "', '" . $fecha . "', '" . $this->fields["tipo_cambio"] . "', '" .
				$this->fields["moneda_base"] . "', '" . $sesion->usuario->fields['id_usuario'] . "')";
		$result = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		return true;
	}

	function GetGlosaMonedaBase(&$sesion) {
		$query = "SELECT glosa_moneda FROM prm_moneda WHERE moneda_base = 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($glosa_moneda) = mysql_fetch_array($resp)) {
			return $glosa_moneda;
		} else {
			return false;
		}
	}

	function GetGlosaPluralMonedaBase(&$sesion) {
		$query = "SELECT glosa_moneda_plural FROM prm_moneda WHERE moneda_base = 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($glosa_moneda_plural) = mysql_fetch_array($resp)) {
			return $glosa_moneda_plural;
		} else {
			return false;
		}
	}

	function GetSimboloMoneda(&$sesion, $id_moneda) {
		$query = "SELECT simbolo FROM prm_moneda WHERE id_moneda = '$id_moneda'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($simbolo) = mysql_fetch_array($resp)) {
			return $simbolo;
		} else {
			return false;
		}
	}

	function GetMonedaBase(&$sesion) {
		$query = "SELECT id_moneda FROM prm_moneda WHERE moneda_base = 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($id_moneda) = mysql_fetch_array($resp)) {
			return $id_moneda;
		} else {
			return false;
		}
	}

	function GetTipoCambioMoneda(&$sesion, $id_moneda) {
		$query = "SELECT tipo_cambio FROM prm_moneda WHERE id_moneda='$id_moneda'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($tipo_cambio) = mysql_fetch_array($resp)) {
			return $tipo_cambio;
		} else {
			return false;
		}
	}

	function GetMonedaTarifaPorDefecto(&$sesion) {
		if (method_exists('Conf', 'GetConf')) {
			$query = "SELECT id_moneda FROM prm_moneda WHERE glosa_moneda = '" . Conf::GetConf($sesion, 'MonedaTarifaPorDefecto') . "'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			if (list($id_moneda) = mysql_fetch_array($resp)) {
				return $id_moneda;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function GetMonedaTotalPorDefecto(&$sesion) {
		if (method_exists('Conf', 'GetConf')) {
			$query = "SELECT id_moneda FROM prm_moneda WHERE glosa_moneda = '" . Conf::GetConf($sesion, 'MonedaTotalPorDefecto') . "'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			if (list($id_moneda) = mysql_fetch_array($resp)) {
				return $id_moneda;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function GetMonedaTipoCambioReferencia(&$sesion) {
		$query = "SELECT id_moneda FROM prm_moneda WHERE tipo_cambio_referencia = 1 ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($id_moneda) = mysql_fetch_array($resp)) {
			return $id_moneda;
		} else {
			return false;
		}
	}

	function GetMonedaTramitePorDefecto(&$sesion) {
		if (method_exists('Conf', 'GetConf')) {
			$query = "SELECT id_moneda FROM prm_moneda WHERE glosa_moneda = '" . Conf::GetConf($sesion, 'MonedaTramitePorDefecto') . "'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			if (list($id_moneda) = mysql_fetch_array($resp)) {
				return $id_moneda;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function GetMonedaReportesAvanzados(&$sesion) {
		$query = " SELECT id_moneda FROM prm_moneda WHERE glosa_moneda LIKE '%" . Conf::GetConf($sesion, 'MonedaTarifaPorDefecto') . "%' ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($id_moneda) = mysql_fetch_array($resp);

		if (empty($id_moneda)) {
			$query = " SELECT id_moneda FROM prm_moneda WHERE moneda_base = 1 ";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($id_moneda) = mysql_fetch_array($resp);
		}

		return $id_moneda;
	}

	/**
	 * Obtiene todas las monedas del sistema para ser utilizadas en Select
	 * @param Sesion $Sesion
	 * @param int $id_moneda
	 * @param boolean $como_objeto
	 * @return array Arreglo con monedas para ser usados en Selects
	 */
	public static function GetMonedas(&$Sesion, $id_moneda = '', $como_objeto = false) {
		$query = "SELECT
					prm_moneda.id_moneda,
					prm_moneda.glosa_moneda,
					prm_moneda.glosa_moneda_plural,
					prm_moneda.tipo_cambio,
					prm_moneda.cifras_decimales,
					prm_moneda.simbolo,
					prm_moneda.codigo
				FROM prm_moneda";

		if (!empty($id_moneda)) {
			$query .= " WHERE id_moneda = '$id_moneda'";
		}

		$r = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
		$monedas = array();

		$fetch = 'mysql_fetch_' . ($como_objeto ? 'assoc' : 'array');
		while ($moneda = $fetch($r)) {
			if ($como_objeto) {
				$monedas[$moneda['id_moneda']] = $moneda;
			} else {
				$monedas[] = $moneda;
			}
		}
		return $monedas;
	}

	public static function GetGlosaMoneda($sesion, $id_moneda) {
		$query = "SELECT glosa_moneda FROM prm_moneda WHERE id_moneda = $id_moneda";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($glosa_moneda) = mysql_fetch_array($resp);

		return $glosa_moneda;
	}

	/**
	 * Devuelve el valor en float con los decimales de la moneda cargada.
	 * @param type $valor
	 * @param type $convert
	 * @return string|float
	 * @throws Exception
	 */
	public function getFloat($valor, $convert = true) {
		if (!$this->Loaded()) {
			throw new Exception('Debe cargar una moneda!');
		}
		$v = number_format($valor, $this->fields['cifras_decimales'], '.', '');
		return $convert ? (float) $v : $v;
	}

}

if (!class_exists('ListaMonedas')) {

	class ListaMonedas extends Lista {

		function ListaMonedas($sesion, $params, $query) {
			$this->Lista($sesion, 'Moneda', $params, $query);
		}

	}

}
