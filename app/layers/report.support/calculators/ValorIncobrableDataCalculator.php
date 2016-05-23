<?php
/**
 * El valor incobrable corresponde al monto subtotal (descontado) de la Liquidación.
 * Esta información se obtiene de: Trabajos, Trámites y Cobros sin trabajos ni trámites
 *
 * Condiciones para obtener un valor cobrado:
 *  * Que exista un cobro en estado: INCOBRABLE
 *  * Que lo que se esté cobrando sea Cobrable
 *
 * Más info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Incobrable
 */
class ValorIncobrableDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor incobrable
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$subtotalBase = $this->getWorksProportionalDocumentSubtotal();
		$factor = $this->getFactor();
		$billed_amount = "SUM({$factor} * {$subtotalBase})
			*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_incobrable');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('INCOBRABLE')));
	}

	/**
	 * Obtiene la query de trámites correspondiente al valor incobrable
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$subtotalBase = $this->getErrandsProportionalDocumentSubtotal();
		$factor = $this->getFactor();
		$billed_amount =  "SUM({$factor} * {$subtotalBase})
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_incobrable');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('INCOBRABLE')));
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$factor = $this->getFactor();
		$billed_amount = "
			SUM({$factor} * (cobro.monto_subtotal - cobro.descuento)
				* (1 / IFNULL(asuntos_cobro.total_asuntos, 1))
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		";
		$Criteria
			->add_select(
				$billed_amount,
				'valor_incobrable'
			);

		$Criteria
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('INCOBRABLE')));
	}

}
