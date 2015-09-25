<?php
/**
 * HorasCobrablesDataCalculator
 * key: horas_cobrables
 * Description: Horas cobrables trabajadas por los profesionales
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Cobrables
 *
 */
class HorasCobrablesDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Cobrables
	 * Se obtiene desde trabajo.duracion_cobrada
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_cobrables = "SUM(TIME_TO_SEC(trabajo.duracion)) / 3600";

		$Criteria
			->add_select($horas_cobrables, 'horas_cobrables')
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1));
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas Cobrables
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery(&$Criteria) {
		$Criteria = null;
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas Cobrables
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
