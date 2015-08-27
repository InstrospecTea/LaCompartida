<?php

 class BilledAmountDataCalculator extends AbstractProportionalDataCalculator {

	function getNotAllowedFilters() {
		return array(
			'estado_cobro'
		);
	}

	function getNotAllowedGroupers() {
		return array(
			'categoria_usuario'
		);
	}

	function getReportWorkQuery(Criteria $Criteria) {

		$values = array(
			'estandar' => array(
				'tarifa' => 'tarifa_hh_estandar',
				'monto' => 'monto_thh_estandar'
			),
			'cliente' => array(
				'tarifa' => 'tarifa_hh',
				'monto' => 'monto_thh'
			)
		);

		$monto_honorarios = "SUM(
			({$values[$this->getProportionality()]['tarifa']} * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(
				(documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
	 		)
			/
			cobro.{$values[$this->getProportionality()]['monto']}
		)
		*
		(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		// select
		$Criteria->add_select($monto_honorarios, 'valor_cobrado');

		// joins
		$Criteria
			->add_left_join_with('cobro', 'trabajo.id_cobro = cobro.id_cobro')
			->add_left_join_with('documento', "documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N'")
			->add_left_join_with('documento_moneda AS cobro_moneda_documento', 'cobro_moneda_documento.id_documento = documento.id_documento AND cobro_moneda_documento.id_moneda = documento.id_moneda')
			->add_left_join_with('documento_moneda AS cobro_moneda', 'cobro_moneda.id_documento = documento.id_documento AND cobro_moneda.id_moneda = 1')
			->add_left_join_with('documento_moneda AS cobro_moneda_cobro', 'cobro_moneda_cobro.id_documento = documento.id_documento AND cobro_moneda_cobro.id_moneda = cobro.id_moneda');
	}

	function getReportErrandQuery($Criteria) {
		// nothing to do here
	}

	function getReportChargeQuery($Criteria) {
		$monto_subtotal = '
			(1 / IFNULL(asuntos_cobro.total_asuntos, 1)) *
			SUM(cobro.monto_subtotal
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		';

		$Criteria->add_select($monto_subtotal, 'valor_cobrado');

		$SubCriteria = new Criteria();

		$SubCriteria->add_from('cobro_asunto')
			->add_select('id_cobro')
			->add_select('count(codigo_asunto)', 'total_asuntos')
			->add_grouping('id_cobro');

		$Criteria->add_left_join_with_criteria(
			$SubCriteria,
			'asuntos_cobro',
			CriteriaRestriction::equals('asuntos_cobro.id_cobro', 'cobro.id_cobro')
		);

	}

}
