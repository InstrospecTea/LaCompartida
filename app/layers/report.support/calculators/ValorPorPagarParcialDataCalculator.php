<?php
/**
 * El valor por pagar parcial corresponde a lo que queda por ser pagado del monto correspondiente
 * al valor de la liquidación antes de impuestos.
 *
 * Condiciones para obtener un valor por pagar:
 *  * Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE o PAGO PARCIAL
 *  * Que lo que se esté cobrando sea Cobrable
 *
 * Más info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Por-Pagar-Parcial
 */
class ValorPorPagarParcialDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Valor Por Cobrar
	 * Se obtiene desde el monto de trabajos del cobro
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
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
		$valor_por_pagar = "SUM({$factor} * ({$amount}) * ((documento.saldo_honorarios / documento.honorarios) * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)))";

		$Criteria->add_select(
			$valor_por_pagar, 'valor_por_pagar_parcial'
		);
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Valor Por Cobrar
	 * Se obtiene desde el monto de trámites del cobro
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
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
		$valor_por_pagar = "SUM({$factor} * ({$amount}) * ((documento.saldo_honorarios / documento.honorarios) * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)))";

		$Criteria->add_select(
			$valor_por_pagar, 'valor_por_pagar_parcial'
		);
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Valor Por Cobrar parcial
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$SubCriteria = new Criteria();

		$valor_por_pagar = "(1 / IFNULL(asuntos_cobro.total_asuntos, 1)) *
		(
			(cobro.monto_subtotal - cobro.descuento)
			* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
			* (documento.saldo_honorarios / documento.honorarios)
			/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
		)";

		$Criteria->add_select($valor_por_pagar, 'valor_por_pagar_parcial');
		$Criteria->add_restriction(
			CriteriaRestriction::in(
				'cobro.estado',
				array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL')
			)
		);
	}
}