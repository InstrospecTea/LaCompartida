<?php
/**
 * El valor hora corresponde al cuociente entre la hora facturada al cliente{@link HorasCobradasDataCalculator}
 * , seg�n el valor cobrado {@link ValorCobradoDataCalculator}.
 *
 * Condiciones para obtener un valor hora:
 * 	* Que exista en un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 * 		PAGO PARCIAL o PAGADO
 *	* Que el trabajo sea cobrable
 *
 * M�s info en https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Hora
 */
class ValorHoraDataCalculator extends AbstractProportionalDataCalculator {
	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$subtotalBase = $this->getWorksProportionalDocumentSubtotal();
		$billed_amount = "SUM({$subtotalBase})
			*
		(1 / cobro_moneda.tipo_cambio)";
		$billed_hours = 'SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600';

		$Criteria->add_select(
			$billed_amount,
			'valor_hora'
		)->add_select(
			$billed_hours,
			'valor_divisor'
		)->add_restriction(
			CriteriaRestriction::equals('trabajo.cobrable', 1)
		)->add_restriction(
			CriteriaRestriction::in(
				'cobro.estado',
				array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')
			)
		);
	}

	/**
	 * Obtiene la query de tr�mites correspondiente al valor cobrado est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$Criteria = null;
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$Criteria = null;
	}
}
