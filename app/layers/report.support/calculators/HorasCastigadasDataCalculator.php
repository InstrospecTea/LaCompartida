<?php
/**
 * HorasCastigadasDataCalculator
 * key: horas_castigadas
 * Description: Horas trabajadas menos (-) horas cobrables de profesionales
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Castigadas
 *
 */
class HorasCastigadasDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Castigadas
	 * Se obtiene desde trabajo.duracion_cobrada - trabajo.duracion_cobrada
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_castigadas = "SUM(TIME_TO_SEC(trabajo.duracion) - TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_castigadas, 'horas_castigadas');
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas Castigadas
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$horas_castigadas = "0";

		$Criteria
			->add_select($horas_castigadas, 'horas_castigadas');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas Castigadas
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
