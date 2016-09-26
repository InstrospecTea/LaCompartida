<?php
/**
 * HorasTrabajadasDataCalculator
 * key: horas_trabajadas
 * Description: Horas trabajadas por los profesionales
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Trabajadas
 *
 */
class HorasTrabajadasDataCalculator extends AbstractDataCalculator {
	private $fieldName = 'horas_trabajadas';

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Trabajadas
	 * Se obtiene desde trabajo.duracion
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
	 	$factor = $this->getFactor();
		$value = "SUM({$factor}
			* TIME_TO_SEC(trabajo.duracion)) / 3600";

		$Criteria->add_select($value, $this->fieldName);
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Horas Trabajadas
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery(&$Criteria) {
		$Criteria = null; //cancel criteria condition
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Horas Trabajadas
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
