<?php

/**
 * La diferencia del valor cobrado est�ndar (diferencia_valor_estandar) corresponde
 * al monto subtotal (descontado) que se hubiese cobrado si la liquidaci�n
 * se tarificara en tarifa est�ndar {@link ValorCobradoEstandarDataCalculator}
 * restado del valor cobrado (valor_cobrado) {@link ValorCobradoDataCalculator}
 *
 * Esta informaci�n se obtiene de: Trabajos, Tr�mites y cobros sin trabajos ni tr�mites
 *
 * Condiciones para obtener un valor cobrado:
 * 	* Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 * 		PAGO PARCIAL o PAGADO
 *	* Que lo que se est� cobrando sea Cobrable
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Diferencia-Valor-Cobrado-Estandar
 */
class DiferenciaValorEstandarDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a la diferencia del valor cobrado est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$factor = $this->getFactor();
		$subtotal = $this->getWorksProportionalDocumentSubtotal();
		$billed_amount = "SUM({$factor} * {$subtotal})
		*
		(1 / cobro_moneda.tipo_cambio)";

		$standard_amount = "
			SUM({$factor} * tarifa_hh_estandar * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select("({$billed_amount}) - ({$standard_amount})", 'diferencia_valor_estandar');

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
	 * Obtiene la query de tr�mites correspondiente a la diferencia del valor cobrado est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$factor = $this->getFactor();
		$subtotal = $this->getErrandsProportionalDocumentSubtotal();
		$billed_amount =  "SUM({$factor} * {$subtotal})
		*
		(1 / cobro_moneda.tipo_cambio)";

		$standard_amount = "
			SUM({$factor} * tarifa_tramite_estandar)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select("({$billed_amount}) - ({$standard_amount})", 'diferencia_valor_estandar');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

	/**
	 * Obtiene la query de cobros correspondiente a la diferencia del valor cobrado est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$factor = $this->getFactor();
		$billed_amount = "
			SUM({$factor} * (cobro.monto_subtotal - cobro.descuento)
				* (1 / IFNULL(asuntos_cobro.total_asuntos, 1))
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		";

		$standard_amount = '0';

		$Criteria
			->add_select(
				"({$billed_amount}) - ({$standard_amount})",
				'diferencia_valor_estandar'
			);

		$Criteria
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

}
