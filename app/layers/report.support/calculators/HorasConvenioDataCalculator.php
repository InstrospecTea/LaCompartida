<?php
/**
 * HorasConvenioDataCalculator
 * key: horas_convenio
 * Description: Horas cobrables de profesionales, en formas de cobro FLAT FEE y RETAINER
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Convenio
 *
 */
class HorasConvenioDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Convenio
	 * Se obtiene desde trabajo.duracion_cobrada filtrando por forma de cobro
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_convenio = "SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_convenio, 'horas_convenio');
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas Convenio
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$horas_convenio = "0";

		$Criteria
			->add_select($horas_convenio, 'horas_convenio');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas Convenio
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
