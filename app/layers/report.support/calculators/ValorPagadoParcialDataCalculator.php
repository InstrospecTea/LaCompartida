<?php
/**
 * El valor cobrado corresponde al monto subtotal (descontado) de la Liquidaci�n que se encuentra en estado pagado parcial.
 * Esta informaci�n se obtiene de: Trabajos, Tr�mites y Cobros sin trabajos ni tr�mites
 *
 * Condiciones para obtener un valor cobrado:
 * 	* Que exista un cobro en estado: PAGO_PARCIAL
 *	* Que lo que se est� cobrando sea Cobrable
 *
 * M�s info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Pagado-Parcial
 */
class ValorPagadoParcialDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$factor = $this->getWorksProportionalFactor();
		$billed_amount = "SUM(
			{$factor}
			*
			(
				(documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites))
				*
				(1 - documento.saldo_honorarios / documento.honorarios)
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_pagado_parcial')
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('PAGO PARCIAL')));
	}

	/**
	 * Obtiene la query de tr�mites correspondiente al valor cobrado
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery(Criteria $Criteria) {
		$factor = $this->getErrandsProportionalFactor();

		$billed_amount =  "SUM(
			{$factor}
			*
			(
				(documento.monto_tramites / (documento.monto_trabajos + documento.monto_tramites))
				*
				(1 - documento.saldo_honorarios / documento.honorarios)
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_pagado_parcial')
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('PAGO PARCIAL')));
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(Criteria $Criteria) {

		$billed_amount = '
			(1 / IFNULL(asuntos_cobro.total_asuntos, 1)) *
			SUM((cobro.monto_subtotal - cobro.descuento)
				* (1 - documento.saldo_honorarios / documento.honorarios)
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		';

		$Criteria
			->add_select($billed_amount, 'valor_pagado_parcial')

			->add_restriction(CriteriaRestriction::in('cobro.estado', array('PAGO PARCIAL')));
	}

}
