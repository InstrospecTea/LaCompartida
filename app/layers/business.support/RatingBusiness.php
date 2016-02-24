<?php

class RatingBusiness extends AbstractBusiness implements IRatingBusiness {

	/**
	 * Elimina una tarifa de trámites
	 * @param type $id_tarifa
	 * @throws Exception
	 */
	public function deleteErrandRate($id_tarifa) {
		$query = "DELETE FROM tramite_valor WHERE id_tramite_tarifa = '{$id_tarifa}'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM tramite_tarifa WHERE id_tramite_tarifa = '{$id_tarifa}'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		return true;
	}

	/**
	 * Trae las tarifas de trámites
	 * return Array
	 */
	public function getErrandsRate() {
		$Criteria = new Criteria($this->Session);
		$errands_rate = $Criteria
			->add_select('id_tramite_tarifa')
			->add_select('glosa_tramite_tarifa')
			->add_select('tarifa_defecto')
			->add_from('tramite_tarifa')
			->add_ordering('glosa_tramite_tarifa')
			->run();

		return $errands_rate;
	}

	/**
	 * return Array
	 */
	public function getErrandsRateFields() {
		$Criteria = new Criteria($this->Session);
		$rate_fields = $Criteria
			->add_select('tramite_tipo.id_tramite_tipo')
			->add_select('tramite_tipo.glosa_tramite')
			->add_select('prm_moneda.id_moneda')
			->add_from('tramite_tipo')
			->add_inner_join_with('prm_moneda', 'prm_moneda.id_moneda')
			->add_ordering('tramite_tipo.glosa_tramite')
			->add_ordering('tramite_tipo.id_tramite_tipo')
			->add_ordering('prm_moneda.id_moneda')
			->run();

		return $rate_fields;
	}

	/**
	 * return Array
	 */
	public function getErrandsRateValue($id_rate) {
		$Criteria = new Criteria($this->Session);
		$errands_rate_values = $Criteria
			->add_select('tramite_valor.id_tramite_tipo')
			->add_select("IF(tramite_valor.tarifa >= 0,tramite_valor.tarifa,'')", 'tarifa')
			->add_select('tramite_valor.id_moneda')
			->add_from('tramite_valor')
			->add_inner_join_with('tramite_tipo', 'tramite_valor.id_tramite_tipo = tramite_tipo.id_tramite_tipo')
			->add_restriction(CriteriaRestriction::equals('tramite_valor.id_tramite_tarifa', $id_rate))
			->add_ordering('tramite_tipo.glosa_tramite')
			->add_ordering('tramite_tipo.id_tramite_tipo')
			->run();

		return $errands_rate_values;
	}

	/**
	 * return Array
	 */
	public function getErrandRateDetail($id_rate) {
		$Criteria = new Criteria($this->Session);
		$errand_rate_detail = $Criteria
			->add_select('id_tramite_tarifa')
			->add_select('glosa_tramite_tarifa')
			->add_select('tarifa_defecto')
			->add_from('tramite_tarifa')
			->add_restriction(CriteriaRestriction::equals('id_tramite_tarifa', $id_rate))
			->add_ordering('glosa_tramite_tarifa')
			->run();

		return $errand_rate_detail[0];
	}

	/**
	 * return Array
	 */
	public function getContractsWithErrandRate($id_rate) {
		$Criteria = new Criteria($this->Session);
		$num_contratos = $Criteria->add_select('COUNT(*)', 'num_rows')
			->add_from('contrato')
			->add_restriction(CriteriaRestriction::equals('id_tramite_tarifa', $_REQUEST['id_tarifa']))
			->run();

		return $num_contratos[0];
	}

	/**
	 * return Array
	 */
	public function updateDefaultErrandRateOnContract($id_rate) {
		$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto = 1 ORDER BY id_tramite_tarifa ASC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_tramite_tarifa_defecto) = mysql_fetch_array($resp);

		$query = "UPDATE contrato SET id_tramite_tarifa = $id_tramite_tarifa_defecto WHERE id_tramite_tarifa = '{$id_rate}'";
		$result = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		return $result;
	}

	/**
	 * return Array
	 */
	public function countRates() {
		$Criteria = new Criteria($this->Session);
		$num_contratos = $Criteria->add_select('COUNT(*)', 'num_rows')
			->add_from('tramite_tarifa')
			->run();

		return $num_contratos[0]['num_rows'];
	}
}
