<?php

 class BilledAmountDataCalculator extends AbstractProportionalDataCalculator {

	function getReportWorkQuery(Criteria $Criteria) {
		$rate = $this->getWorksFeeField();
		$amount = $this->getWorksProportionalityAmountField();

		$billed_amount = "SUM(
			({$rate} * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(
				(documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
			/
			cobro.{$amount}
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_cobrado');

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
		$rate = $this->getErrandsFeeField();
		$amount = $this->getErrandsProportionalityAmountField();
		$billed_amount =  "SUM(
			({$rate})
			*
			(
				(documento.monto_tramites / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
			/ cobro.{$amount}
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_cobrado');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

	function getReportChargeQuery($Criteria) {
		$billed_amount = '
			(1 / IFNULL(asuntos_cobro.total_asuntos, 1)) *
			SUM(cobro.monto_subtotal
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		';
		$Criteria
			->add_select(
				$billed_amount,
				'valor_cobrado'
			);

		$Criteria
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

}
