<?php

 class BilledAmountDataCalculator extends AbstractProportionalDataCalculator {

	function getNotAllowedFilters() {
		return array(
			'estado_cobro'
		);
	}

	function getNotAllowedGroupers() {
		return array(
			'categoria_usuario'
		);
	}

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

		$Criteria->add_select($billed_amount, 'valor_cobrado');
	}

	function getReportErrandQuery($Criteria) {

	}

	function getReportChargeQuery($Criteria) {
		$monto_subtotal = '
			(1 / IFNULL(asuntos_cobro.total_asuntos, 1)) *
			SUM(cobro.monto_subtotal
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		';
		$Criteria->add_select(
			'cobro.id_cobro'
		)->add_select(
			$monto_subtotal,
			'valor_cobrado'
		);
	}

}
