<?php
/**
 * HorasFacturadasDataCalculator
 * key: horas_cobradas
 * Description: Horas que se encuentran cobradas en una liquidaci�n.
 *
 * Est� dejando valores peri�dicos truncados: Ej. 8.3333 es 8.3 y no 8.34 como
 * el antiguo reporte.
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Cobradas
 *
 */
class HorasFacturadasDataCalculator extends AbstractInvoiceProportionalDataCalculator {
	private $fieldName = 'horas_facturadas';

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Cobradas
	 * Se obtiene desde trabajo.duracion_cobrada
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$factor = $this->getFactor();
		$aporte = $this->getInvoiceContribution();
		$value = "SUM({$factor} * {$aporte}
			* TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria->add_select($value, $this->fieldName);
		$Criteria->add_restriction(
			CriteriaRestriction::equals(
				'trabajo.cobrable', 1
			)
		)->add_restriction(
			CriteriaRestriction::in(
				'cobro.estado',
				array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')
			)
		);
	}


	/**
	 * Obtiene la query de tr�mites correspondiente a Horas Cobradas
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery(&$Criteria) {
		$Criteria = null;
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Horas Cobradas
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
