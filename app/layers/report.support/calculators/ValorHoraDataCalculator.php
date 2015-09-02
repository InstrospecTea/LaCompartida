<?php

class ValorHoraDataCalculator extends AbstractProportionalDataCalculator {
	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$trabajos_amount = "((documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites)) * documento.subtotal_sin_descuento)";
		$monto_honorarios = "SUM(({$this->getWorksFeeField()} * TIME_TO_SEC(duracion_cobrada) / 3600)
			* (({$trabajos_amount} * cobro_moneda_documento.tipo_cambio)
			/ ({$this->getWorksProportionalityAmountField()} * cobro_moneda_cobro.tipo_cambio))
			* (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio))";

		$Criteria->add_select(
			'SUM((TIME_TO_SEC(duracion_cobrada) / 3600))',
			'valor_divisor'
		)->add_select(
			$monto_honorarios,
			'valor_hora'
		);
		pr($Criteria->get_plain_query());
	}

	/**
	 * Obtiene la query de trámites correspondiente al valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$tramites_amount = "((documento.monto_tramites / (documento.monto_trabajos + documento.monto_tramites)) * documento.subtotal_sin_descuento)";
		$monto_honorarios = "SUM(({$this->getErrandsFeeField()})
			* (({$tramites_amount} * cobro_moneda_documento.tipo_cambio)
				/ ({$this->getErrandsProportionalityAmountField()} * cobro_moneda_cobro.tipo_cambio))
			* (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio))";
		$Criteria->add_select(
			'1', 'valor_divisor'
		)->add_select(
			$monto_honorarios, 'valor_hora'
		);
		pr($Criteria->get_plain_query());
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$Criteria = null;
	}
}