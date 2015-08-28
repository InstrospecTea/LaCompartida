<?php

 class BilledErrandsAmountDataCalculator extends AbstractProportionalDataCalculator {

	function getReportWorkQuery(Criteria &$Criteria) {
		$Criteria = null;
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

		$Criteria->add_select($billed_amount, 'valor_tramites');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
