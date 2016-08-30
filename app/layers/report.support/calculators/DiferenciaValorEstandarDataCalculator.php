<?php

/**
 * La diferencia del valor cobrado estándar (diferencia_valor_estandar) corresponde
 * al monto subtotal (descontado) que se hubiese cobrado si la liquidación
 * se tarificara en tarifa estándar {@link ValorCobradoEstandarDataCalculator}
 * restado del valor cobrado (valor_cobrado) {@link ValorCobradoDataCalculator}
 *
 * Esta información se obtiene de: Trabajos, Trámites y cobros sin trabajos ni trámites
 *
 * Condiciones para obtener un valor cobrado:
 * 	* Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 * 		PAGO PARCIAL o PAGADO
 *	* Que lo que se esté cobrando sea Cobrable
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Diferencia-Valor-Cobrado-Estandar
 */
class DiferenciaValorEstandarDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a la diferencia del valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
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
	 * Obtiene la query de trámites correspondiente a la diferencia del valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
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
	 * Obtiene la query de cobros correspondiente a la diferencia del valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
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
