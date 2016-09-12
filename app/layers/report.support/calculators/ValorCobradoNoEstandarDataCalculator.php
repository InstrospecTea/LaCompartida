<?php

/**
 * El valor cobrado no est�ndar (o valor_cobrado_no_estandar) corresponde
 * al monto subtotal (descontado) que se hubiese cobrado si la liquidaci�n
 * se tarificara en tarifa del usuario. Independiente del tipo de liquidaci�n.
 *
 * Esta informaci�n se obtiene de: Trabajos y Tr�mites, ya que para cobros
 * sin trabajos ni tr�mites no es posible establecer una relaci�n no est�ndar.
 *
 * Condiciones para obtener un valor cobrado:
 * 	* Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 * 		PAGO PARCIAL o PAGADO
 *	* Que lo que se est� cobrando sea Cobrable
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Cobrado-No-Estandar
 *
 */
class ValorCobradoNoEstandarDataCalculator extends AbstractCurrencyDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado no est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$factor = $this->getFactor();
		$standard_amount = "
			SUM({$factor} * trabajo.tarifa_hh * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($standard_amount, 'valor_cobrado_no_estandar');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in(
				'cobro.estado',
				array(
					'EMITIDO',
					'FACTURADO',
					'ENVIADO AL CLIENTE',
					'PAGO PARCIAL',
					'PAGADO'
				)
			)
		);
	}

	/**
	 * Obtiene la query de tr�mites correspondiente al valor cobrado no est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$factor = $this->getFactor();
		$standard_amount = "
			SUM({$factor} * tramite.tarifa_tramite)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($standard_amount, 'valor_cobrado_no_estandar');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in(
				'cobro.estado',
				array(
					'EMITIDO',
					'FACTURADO',
					'ENVIADO AL CLIENTE',
					'PAGO PARCIAL',
					'PAGADO'
				)
			)
		);
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
