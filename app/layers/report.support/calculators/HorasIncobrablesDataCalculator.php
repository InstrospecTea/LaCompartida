<?php
/**
 * HorasIncobrablesDataCalculator
 * key: horas_incobrables
 * Description: Horas cobrables que se encuentran en un cobro con estado INCOBRABLE
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Incobrables
 *
 */
class HorasIncobrablesDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Incobrables
	 * Se obtiene desde trabajo.duracion_cobrada en cobro con estado INCOBRABLE
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_incobrables = "SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_incobrables, 'horas_incobrables');
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Horas Incobrables
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$horas_incobrables = "0";

		$Criteria
			->add_select($horas_incobrables, 'horas_incobrables');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Horas Incobrables
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
