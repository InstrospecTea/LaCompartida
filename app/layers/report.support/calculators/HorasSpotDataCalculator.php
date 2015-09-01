<?php
/**
 * HorasSpotDataCalculator
 * key: horas_spot
 * Description: Horas cobrables de profesionales, en formas de cobro TASA y CAP
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Spot
 *
 */
class HorasSpotDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Spot
	 * Se obtiene desde trabajo.duracion_cobrada filtrando por forma de cobro
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_spot = "SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_spot, 'horas_spot');
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Horas Spot
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$horas_spot = "0";

		$Criteria
			->add_select($horas_spot, 'horas_spot');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Horas Spot
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
