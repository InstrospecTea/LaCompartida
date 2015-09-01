<?php

/**
 * El valor cobrado estándar (o valor_estandar) corresponde
 * al monto subtotal (descontado) que se hubiese cobrado si la liquidación
 * se tarificara en tarifa estándar. Independiente del tipo de liquidación.
 *
 * Esta información se obtiene de: Trabajos y Trámites, ya que para cobros
 * sin trabajos ni trámites no es posible establecer una relación estándar.
 *
 * Condiciones para obtener un valor cobrado:
 * 	* Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 * 		PAGO PARCIAL o PAGADO
 *	* Que lo que se esté cobrando sea Cobrable
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Cobrado-Estandar
 *
 */
class ValorCobradoEstandarDataCalculator extends AbstractCurrencyDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$standard_amount = "
			SUM(trabajo.tarifa_hh_estandar * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($standard_amount, 'valor_estandar');

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
	 * Obtiene la query de trámites correspondiente al valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$standard_amount = "
			SUM(tramite.tarifa_tramite_estandar)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($standard_amount, 'valor_estandar');

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
	 * Obtiene la query de cobros sin trabajos ni trámites
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
