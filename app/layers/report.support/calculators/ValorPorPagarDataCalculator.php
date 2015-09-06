<?php
/**
 * El valor por pagar corresponde a las liquidaciones que no est�n pagadas
 *
 * Condiciones para obtener un valor por pagar:
 * 	* Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE o PAGO PARCIAL
 *	* Que lo que se est� cobrando sea Cobrable
 *
 * M�s info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Por-Pagar
 */
class ValorPorPagarDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Valor Por Cobrar
	 * Se obtiene desde el monto de trabajos del cobro no emitido, si no existe cobro se tarifican los trabajos
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$Criteria->add_restriction(
			CriteriaRestriction::equals(
				'trabajo.cobrable', '1'
			)
		)->add_restriction(
			CriteriaRestriction::in(
				'cobro.estado',
				array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL')
			)
		);

		$factor = $this->getWorksProportionalFactor();
		$amount = "((documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites)) * documento.subtotal_sin_descuento)";
		$valor_por_pagar = "SUM({$factor} * ({$amount}) * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio))";

		$Criteria->add_select(
			$valor_por_pagar, 'valor_por_pagar'
		);
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Valor Por Cobrar
	 * Se obtiene desde el monto de tr�mites del cobro no emitido, si no existe cobro se tarifican los tr�mites
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$Criteria->add_restriction(
			CriteriaRestriction::equals(
				'tramite.cobrable', '1'
			)
		)->add_restriction(
			CriteriaRestriction::in(
				'cobro.estado',
				array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL')
			)
		);

		$factor = $this->getErrandsProportionalFactor();
		$amount = "((documento.monto_tramites / (documento.monto_trabajos + documento.monto_tramites)) * documento.subtotal_sin_descuento)";
		$valor_por_pagar = "SUM({$factor} * ({$amount}) * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio))";

		$Criteria->add_select(
			$valor_por_pagar, 'valor_por_pagar'
		);
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Valor Por Cobrar
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$SubCriteria = new Criteria();

		$valor_por_pagar = "(1 / IFNULL(asuntos_cobro.total_asuntos, 1)) *
		(
			(cobro.monto_subtotal - cobro.descuento)
			* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
			/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
		)";

		$Criteria->add_select($valor_por_pagar, 'valor_por_pagar');
		$Criteria->add_restriction(
			CriteriaRestriction::in(
				'cobro.estado',
				array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL')
			)
		);
	}
}