<?php
/**
 * HorasIncobrablesDataCalculator
 * key: horas_incobrables
 * Description: Horas cobrables que se encuentran en un cobro con estado INCOBRABLE
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Incobrables
 *
 */
class HorasIncobrablesDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Incobrables
	 * Se obtiene desde trabajo.duracion_cobrada en cobro con estado INCOBRABLE
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_incobrables = "SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_incobrables, 'horas_incobrables');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::equals('cobro.estado', "'INCOBRABLE'"));
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas Incobrables
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery(&$Criteria) {
		$Criteria = null;
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas Incobrables
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
