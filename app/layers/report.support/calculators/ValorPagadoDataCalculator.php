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
				nd.valor_pago_honorarios * cobro_moneda_documento.tipo_cambio
			)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_pagado')
			->add_left_join_with(
				'neteo_documento nd', 
				CriteriaRestriction::equals(
					'nd.id_documento_cobro',
					'documento.id_documento'
				)
			)->add_left_join_with(
				'documento documento_pago',
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals(
						'nd.id_documento_pago',
						'documento_pago.id_documento'
					),
					CriteriaRestriction::not_equal(
						'documento_pago.tipo_doc',
						"'N'"
					)
				)
			)
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
				nd.valor_pago_honorarios * cobro_moneda_documento.tipo_cambio
			)
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($billed_amount, 'valor_pagado')
			->add_left_join_with(
				'neteo_documento nd', 
				CriteriaRestriction::equals(
					'nd.id_documento_cobro',
					'documento.id_documento'
				)
			)->add_left_join_with(
				'documento documento_pago',
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals(
						'nd.id_documento_pago',
						'documento_pago.id_documento'
					),
					CriteriaRestriction::not_equal(
						'documento_pago.tipo_doc',
						"'N'"
					)
				)
			)
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
			SUM(nd.valor_pago_honorarios * cobro_moneda_documento.tipo_cambio)
			*
			(1 / cobro_moneda.tipo_cambio)
		';

		$Criteria
			->add_select($billed_amount, 'valor_pagado')
			->add_left_join_with(
				'neteo_documento nd', 
				CriteriaRestriction::equals(
					'nd.id_documento_cobro',
					'documento.id_documento'
				)
			)->add_left_join_with(
				'documento documento_pago',
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals(
						'nd.id_documento_pago',
						'documento_pago.id_documento'
					),
					CriteriaRestriction::not_equal(
						'documento_pago.tipo_doc',
						"'N'"
					)
				)
			)
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('PAGADO')));
	}

}
