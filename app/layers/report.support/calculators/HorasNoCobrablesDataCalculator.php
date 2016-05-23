<?php
/**
 * HorasNoCobrablesDataCalculator
 * key: horas_no_cobrables
 * Description: Horas no cobrables, trabajadas por los profesionales
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-No-Cobrables
 *
 */
class HorasNoCobrablesDataCalculator extends AbstractDataCalculator {
	private $fieldName = 'horas_no_cobrables';

	/**
	 * Obtiene la query de trabajos correspondiente a Horas No Cobrables
	 * Se obtiene desde trabajo.duracion filtrando por cobrable = 0
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$factor = $this->getFactor();
		$horas_no_cobrables = "SUM({$factor} * TIME_TO_SEC(trabajo.duracion)) / 3600";

		$Criteria
			->add_select($horas_no_cobrables, $this->fieldName);

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 0));
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Horas No Cobrables
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery(&$Criteria) {
		$Criteria = null;
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Horas No Cobrables
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
