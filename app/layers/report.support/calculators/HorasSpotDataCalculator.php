<?php
/**
 * HorasSpotDataCalculator
 * key: horas_spot
 * Description: Horas cobrables de profesionales, en formas de cobro TASA y CAP
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Spot
 *
 */
class HorasSpotDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Spot
	 * Se obtiene desde trabajo.duracion_cobrada filtrando por forma de cobro
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_spot = "SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_spot, 'horas_spot');

		$or_wheres = array();

		$or_wheres[] = CriteriaRestriction::and_clause(
			CriteriaRestriction::not_in('cobro.estado', array('CREADO', 'EN REVISION')),
			CriteriaRestriction::in('cobro.forma_cobro', array('TASA', 'CAP'))
		);

		$or_wheres[] = CriteriaRestriction::and_clause(
			CriteriaRestriction::or_clause(
				CriteriaRestriction::is_null('cobro.estado'),
				CriteriaRestriction::in('cobro.estado', array('CREADO', 'EN REVISION'))
			),
			CriteriaRestriction::or_clause(
				CriteriaRestriction::in('contrato.forma_cobro', array('TASA', 'CAP')),
				CriteriaRestriction::is_null('contrato.forma_cobro')
			)
		);

		$Criteria->add_restriction(CriteriaRestriction::or_clause($or_wheres));

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1));
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas Spot
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$horas_spot = "0";

		$Criteria
			->add_select($horas_spot, 'horas_spot');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas Spot
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
