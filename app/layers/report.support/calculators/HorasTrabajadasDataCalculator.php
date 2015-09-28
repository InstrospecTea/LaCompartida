<?php
/**
 * HorasTrabajadasDataCalculator
 * key: horas_trabajadas
 * Description: Horas trabajadas por los profesionales
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Trabajadas
 *
 */
class HorasTrabajadasDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Trabajadas
	 * Se obtiene desde trabajo.duracion
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_trabajadas = "SUM(TIME_TO_SEC(trabajo.duracion)) / 3600";

		$Criteria
			->add_select($horas_trabajadas, 'horas_trabajadas');
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas Trabajadas
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery(&$Criteria) {
		$Criteria = null;
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas Trabajadas
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
