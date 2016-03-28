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
		$Criteria = new Criteria($this->sesion);
		$id_cta_cte_mvto = $Criteria->add_select('id_cta_cte_mvto', 'id_cta_cte_mvto')
																	->add_from('cta_cte_fact_mvto')
																	->add_restriction(CriteriaRestriction::equals('id_factura_pago', $id_factura))
																	->run();

		if($id_cta_cte_mvto[0]['id_cta_cte_mvto']) {
			return $this->Load($id_cta_cte_mvto[0]['id_cta_cte_mvto']);
		}

		return false;
	}

	function ActualizarMvtoMoneda($tipo_cambio = array()) {
		$query = "DELETE FROM cta_cte_fact_mvto_moneda WHERE id_cta_cte_fact_mvto = {$this->fields['id_cta_cte_fact_mvto']}";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if (empty($tipo_cambio)) {
			$query = "INSERT INTO cta_cte_fact_mvto_moneda (id_cta_cte_mvto, id_moneda, tipo_cambio)
									SELECT {$this->fields['id_cta_cte_fact_mvto']}, id_moneda, tipo_cambio FROM prm_moneda WHERE 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		} else {
			foreach($tipo_cambio as $id_moneda => $tc) {
				$query = "INSERT INTO cta_cte_fact_mvto_moneda (id_cta_cte_fact_mvto, id_moneda, tipo_cambio) VALUES ({$this->fields['id_cta_cte_fact_mvto']}, {$id_moneda}, {$tc});";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			}
		}
	}
}
