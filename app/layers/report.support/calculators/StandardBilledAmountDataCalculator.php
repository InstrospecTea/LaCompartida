<?php

 class StandardBilledAmountDataCalculator extends AbstractCurrencyDataCalculator {

	function getReportWorkQuery(Criteria $Criteria) {
		$billed_amount = "
			SUM(tarifa_hh_estandar * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_estandar');

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

	function getReportErrandQuery($Criteria) {
		$billed_amount = "
			SUM(tarifa_tramite_estandar)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_estandar');

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

	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
