<?php
/**
 * El valor incobrable corresponde al monto subtotal (descontado) de la Liquidaci�n.
 * Esta informaci�n se obtiene de: Trabajos, Tr�mites y Cobros sin trabajos ni tr�mites
 *
 * Condiciones para obtener un valor cobrado:
 *  * Que exista un cobro en estado: INCOBRABLE
 *  * Que lo que se est� cobrando sea Cobrable
 *
 * M�s info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Incobrable
 */
class ValorIncobrableDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor incobrable
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
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_incobrable');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('INCOBRABLE')));
	}

	/**
	 * Obtiene la query de tr�mites correspondiente al valor incobrable
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
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
			->add_select($billed_amount, 'valor_incobrable');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('INCOBRABLE')));
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$billed_amount = '
			SUM((cobro.monto_subtotal - cobro.descuento)
				* (1 / IFNULL(asuntos_cobro.total_asuntos, 1))
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		';
		$Criteria
			->add_select(
				$billed_amount,
				'valor_incobrable'
			);

		$Criteria
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('INCOBRABLE')));
	}

}