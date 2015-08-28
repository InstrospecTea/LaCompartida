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

		$tarifa = $this->getWorksFeeField();
		$monto_prorrata = $this->getWorksProporcionalityAmountField();

		$monto_honorarios = "SUM(
			({$tarifa} * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(
				(documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
	 		)
			/
			cobro.{$monto_prorrata}
		)
		*
		(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria->add_select($monto_honorarios, 'valor_cobrado');
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
