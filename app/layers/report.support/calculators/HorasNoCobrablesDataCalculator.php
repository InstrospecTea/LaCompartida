<?php
/**
 * HorasNoCobrablesDataCalculator
 * key: horas_no_cobrables
 * Description: Horas no cobrables, trabajadas por los profesionales
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-No-Cobrables
 *
 */
class HorasNoCobrablesDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas No Cobrables
	 * Se obtiene desde trabajo.duracion filtrando por cobrable = 0
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_no_cobrables = "SUM(TIME_TO_SEC(trabajo.duracion)) / 3600";

		$Criteria
			->add_select($horas_no_cobrables, 'horas_no_cobrables');
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas No Cobrables
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$horas_no_cobrables = "0";

		$Criteria
			->add_select($horas_no_cobrables, 'horas_no_cobrables');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas No Cobrables
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
