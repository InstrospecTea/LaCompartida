<?php
/**
 * HorasVisiblesDataCalculator
 * key: horas_visibles
 * Description: Horas maracadas como visibles de profesionales
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Visibles
 *
 */
class HorasVisiblesDataCalculator extends AbstractDataCalculator {
	private $fieldName = 'horas_visibles';

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Visibles
	 * Se obtiene desde trabajo.duracion_cobrada filtrando por (duracion - duracion_cobrada) > 0
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$factor = $this->getFactor();
		$horas_visibles = "SUM({$factor} * TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_visibles, $this->fieldName)
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1));
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Horas Visibles
	 * El valor es Cero para todo trámite
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery(&$Criteria) {
		$Criteria = null;
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Horas Visibles
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
