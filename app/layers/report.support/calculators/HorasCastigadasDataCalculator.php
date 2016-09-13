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
	private $fieldName = 'horas_castigadas';

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Castigadas
	 * Se obtiene desde trabajo.duracion_cobrada - trabajo.duracion_cobrada
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$factor = $this->getFactor();
		$horas_castigadas = "IF(
			(SUM({$factor} * (TIME_TO_SEC(trabajo.duracion) - TIME_TO_SEC(trabajo.duracion_cobrada))) / 3600) > 0,
				SUM({$factor} * (TIME_TO_SEC(trabajo.duracion) - TIME_TO_SEC(trabajo.duracion_cobrada))) / 3600,
			0)";

		$Criteria
			->add_select($horas_castigadas, $this->fieldName);

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1));

		if (!empty($this->options) && $this->options['hidde_penalized_hours']) {
			$Criteria
				->add_restriction(CriteriaRestriction::greater_than(
					'(duracion - duracion_cobrada)',
					0
				)
			);
		}
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
