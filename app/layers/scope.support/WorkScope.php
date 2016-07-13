<?php

/**
* Class WorkScope
*
*/
class WorkScope implements IWorkScope {

	/**
	 * Ordena los trabajos desde el m�s viejo al m�s nuevo.
	 * @param  Criteria $criteria
	 * @return Criteria $criteria
	 */
	function orderFromOlderToNewer(Criteria $criteria) {
		$criteria->add_ordering('Work.fecha', 'ASC');
		return $criteria;
	}

  /**
   * A�ade una selecci�n de datos sumados relacionados a la duraci�n
   * @param $criteria
   * @return mixed
   */
  function summarizedValues(Criteria $criteria) {
     $criteria->add_select('COUNT(*)','total_trabajos')
      ->add_select("SUM(TIME_TO_SEC(duracion))/3600", 'total_horas')
      ->add_select("SUM(TIME_TO_SEC(duracion_cobrada))/3600", 'total_horas_cobradas')
      ->add_select("SUM(prm_moneda.tipo_cambio * tarifa_hh_estandar * TIME_TO_SEC(duracion)/3600)", 'total_valor')
      ->add_select("SUM(prm_moneda.tipo_cambio * tarifa_hh_estandar * TIME_TO_SEC(duracion_cobrada)/3600)", 'total_valor_cobrado')
      ->add_custom_join_with('prm_moneda', CriteriaRestriction::equals('prm_moneda.id_moneda', 'Work.id_moneda'));
    return $criteria;
  }

  /**
   * A�ade un grupo por periodo YYYY-MM
   * @param $criteria
   * @return mixed
   */
  function groupedByPeriod(Criteria $criteria) {
    $criteria->add_select("DATE_FORMAT(fecha,'%Y-%m')", 'periodo')
      ->add_grouping('periodo');
    return $criteria;
  }

    /**
   * Ordena por glosa del asunto
   * @param  Criteria $criteria
   * @return mixed
   */
  function orderByMatterGloss(Criteria $criteria) {
    $criteria->add_ordering('Matter.glosa_asunto', 'ASC');
    return $criteria;
	}

	/**
	 * Ordena por fecha del trabajo
	 * @param  Criteria $criteria
	 * @return mixed
	 */
	function orderByWorkDate(Criteria $criteria) {
		$criteria->add_ordering('Work.fecha', 'ASC');
		return $criteria;
	}

	/**
	 * Ordena por fecha del trabajo
	 * @param  Criteria $criteria
	 * @return mixed
	 */
	function orderByMatterGlossWorkDate(Criteria $criteria) {
		$this->orderByMatterGloss($criteria);
		$this->orderByWorkDate($criteria);
		return $criteria;
	}

	/**
	 * Obtiene condici�n para cuando el trabajo no est� cobrado
	 * @param  Criteria $criteria
	 * @return mixed
	 */
	function conditionNotPaid(Criteria $criteria) {
		$clauses = array(
			CriteriaRestriction::is_null('Work.id_cobro'),
			CriteriaRestriction::in('Charge.estado', array(
				'CREADO',
				'EN REVISION'
			))
		);
		$criteria->add_restriction(CriteriaRestriction::or_clause($clauses));

		return $criteria;
	}

	/**
	 * Obtiene condici�n para cuando el trabajo est� cobrado
	 * @param  Criteria $criteria
	 * @return mixed
	 */
	function conditionPaid(Criteria $criteria) {
		$clauses = array(
			CriteriaRestriction::is_not_null('Work.id_cobro'),
			CriteriaRestriction::not_in('Charge.estado', array(
				'CREADO',
				'EN REVISION'
			))
		);
		$criteria->add_restriction(CriteriaRestriction::and_clause($clauses));

		return $criteria;
	}

}
