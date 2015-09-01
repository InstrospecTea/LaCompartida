<?php
/**
 * CostoHhDataCalculator
 * key: costo_hh
 * Description: Costo del profesional dividido en total de horas
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Costo-Hh
 *
 */
class CostoHhDataCalculator extends AbstractCurrencyDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Costo Hh
	 * Se obtiene desde trabajo.duracion y usuario_costo_hh.costo_hh. Requiere ```SUM((TIME_TO_SEC(duracion) / 3600)) as valor_divisor```
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$costo_hh = "SUM(IFNULL((cobro_moneda_base.tipo_cambio / cobro_moneda.tipo_cambio), 1) * cut.costo_hh * (TIME_TO_SEC(duracion) / 3600))";

		$Criteria
			->add_select($costo_hh, 'costo_hh');
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Costo Hh
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$costo_hh = "0";

		$Criteria
			->add_select($costo_hh, 'costo_hh');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Costo Hh
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
