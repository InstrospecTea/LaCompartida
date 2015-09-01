<?php
/**
 * CostoDataCalculator
 * key: costo
 * Description: Costo del profesional
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Costo
 *
 */
class CostoDataCalculator extends AbstractCurrencyDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Costo
	 * Se obtiene desde trabajo.duracion y usuario_costo_hh.costo_hh
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$costo = "IFNULL((cobro_moneda_base.tipo_cambio / cobro_moneda.tipo_cambio), 1) * SUM(usuario_costo_hh.costo_hh * TIME_TO_SEC(trabajo.duracion ) / 3600)";

		$Criteria
			->add_select($costo, 'costo');
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Costo
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$costo = "0";

		$Criteria
			->add_select($costo, 'costo');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Costo
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
