<?php
/**
 * Corresponde al valor por pagar de las liquidaciones
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
		$billed_amount = "SUM(
			{$factor}
			*
			(
				(documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
			* (documento.saldo_honorarios / documento.honorarios)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria->add_select(
			$billed_amount, 'valor_por_pagar'
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
		$billed_amount =  "SUM(
			{$factor}
			*
			(
				(documento.monto_tramites / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
			* (documento.saldo_honorarios / documento.honorarios)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria->add_select(
			$billed_amount, 'valor_por_pagar'
		);
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Valor Por Cobrar
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$billed_amount = '
			SUM((cobro.monto_subtotal - cobro.descuento)
				* (1 / IFNULL(asuntos_cobro.total_asuntos, 1))
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
				* (documento.saldo_honorarios / documento.honorarios)
			)
		';

		$Criteria->add_select($billed_amount, 'valor_por_pagar');
		$Criteria->add_restriction(
			CriteriaRestriction::in(
				'cobro.estado',
				array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL')
			)
		);
	}
}