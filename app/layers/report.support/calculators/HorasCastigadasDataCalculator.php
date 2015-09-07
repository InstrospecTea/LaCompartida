<?php
/**
 * HorasCastigadasDataCalculator
 * key: horas_castigadas
 * Description: Horas trabajadas menos (-) horas cobrables de profesionales
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Castigadas
 *
 */
class HorasCastigadasDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Castigadas
	 * Se obtiene desde trabajo.duracion_cobrada - trabajo.duracion_cobrada
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_castigadas = "SUM(TIME_TO_SEC(trabajo.duracion) - TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_castigadas, 'horas_castigadas');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1));
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Horas Castigadas
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery(&$Criteria) {
		$Criteria = null;
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Horas Castigadas
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
