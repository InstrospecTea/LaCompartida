<?php
/**
 * HorasCobradasDataCalculator
 * key: horas_cobradas
 * Description: Horas que se encuentran cobradas en una liquidación
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Cobradas
 *
 */
class HorasCobradasDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Cobradas
	 * Se obtiene desde trabajo.duracion_cobrada
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_cobradas = "SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_cobradas, 'horas_cobradas');
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas Cobradas
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$horas_cobradas = "0";

		$Criteria
			->add_select($horas_cobradas, 'horas_cobradas');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas Cobradas
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
