<?php
/**
 * El valor por pagar corresponde a lo que queda por ser pagado del monto correspondiente
 * al valor de la liquidación antes de impuestos.
 *
 * Condiciones para obtener un valor por pagar:
 * 	* Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE o PAGO PARCIAL
 *	* Que lo que se esté cobrando sea Cobrable
 *
 * Más info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Por-Pagar
 */
class ValorPorPagarDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Valor Por Cobrar
	 * Se obtiene desde el monto de trabajos del cobro no emitido, si no existe cobro se tarifican los trabajos
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
			$valor_por_pagar, 'valor_por_pagar'
		);
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Valor Por Cobrar
	 * Se obtiene desde el monto de trámites del cobro no emitido, si no existe cobro se tarifican los trámites
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
			$valor_por_pagar, 'valor_por_pagar'
		);
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Valor Por Cobrar
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$SubCriteria = new Criteria();
		$SubCriteria->add_from(
			'cobro_asunto'
		)->add_select(
			'id_cobro'
		)->add_select(
			'count(codigo_asunto)',
			'cant_asuntos'
		)->add_grouping('id_cobro');

		$Criteria->add_left_join_with_criteria(
			$SubCriteria, 'ca2', CriteriaRestriction::equals(
				'ca2.id_cobro', 'cobro.id_cobro'
			)
		);

		$valor_por_pagar = "(1 / IFNULL(ca2.cant_asuntos, 1)) *
		(
			(cobro.monto_subtotal - cobro.monto_tramites)
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