<?php
require_once dirname(__FILE__).'/../conf.php';

class CtaCteFactMvtoMoneda extends Objeto
{
	function __construct($sesion, $fields = "", $params = "") {
		$this->tabla = "cta_cte_fact_mvto_moneda";
		$this->campo_id = "id_cta_cte_fact_mvto";
		$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function LoadByFactura($id_factura) {
		$query = "SELECT id_cta_cte_mvto FROM cta_cte_fact_mvto WHERE id_factura_pago = '$id_factura'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if($id)
			return $this->Load($id);
		return false;
	}

	function ActualizarMvtoMoneda($tipo_cambio = array()) {
		$query = "DELETE FROM cta_cte_fact_mvto_moneda WHERE id_cta_cte_fact_mvto = {$this->fields['id_cta_cte_fact_mvto']}";
		// echo $query . '<br>';
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $this->sesion->dbh);

		if (empty($tipo_cambio)) {
			$query = "INSERT INTO cta_cte_fact_mvto_moneda (id_cta_cte_mvto, id_moneda, tipo_cambio)
									SELECT {$this->fields['id_cta_cte_fact_mvto']}, id_moneda, tipo_cambio FROM prm_moneda WHERE 1";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $this->sesion->dbh);
		} else {
			foreach($tipo_cambio as $id_moneda => $tc) {
				$query = "INSERT INTO cta_cte_fact_mvto_moneda (id_cta_cte_fact_mvto, id_moneda, tipo_cambio) VALUES ({$this->fields['id_cta_cte_fact_mvto']}, {$id_moneda}, {$tc});";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $this->sesion->dbh);
		// echo $query . '<br>';
			}
		}
	}
}
