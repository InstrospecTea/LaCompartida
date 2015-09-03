<?php
/**
 * El valor cobrado corresponde al monto subtotal (descontado) de la Liquidación que se encuentra en estado pagado.
 * Esta información se obtiene de: Trabajos, Trámites y Cobros sin trabajos ni trámites
 *
 * Condiciones para obtener un valor cobrado:
 * 	* Que exista un cobro en estado: PAGADO
 *	* Que lo que se esté cobrando sea Cobrable
 *
 * Más info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Pagado
 */
class ValorPagadoDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
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
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_pagado')
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('PAGADO')));
	}

	/**
	 * Obtiene la query de trámites correspondiente al valor cobrado
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$factor = $this->getErrandsProportionalFactor();
		$billed_amount =  "SUM(
			{$factor}
			*
			(
				(documento.monto_tramites / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_pagado')
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('PAGADO')));
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$billed_amount = '
			(1 / IFNULL(asuntos_cobro.total_asuntos, 1)) *
			SUM(cobro.monto_subtotal
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		';

		$Criteria
			->add_select($billed_amount, 'valor_pagado')
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('PAGADO')));
	}

}
