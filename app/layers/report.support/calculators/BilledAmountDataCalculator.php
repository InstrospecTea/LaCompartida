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
		$Criteria->add_left_join_with(
            'cobro',
            CriteriaRestriction::equals(
                'cobro.id_cobro',
                'trabajo.id_cobro'
            )
		)->add_left_join_with(
			'documento', 
			CriteriaRestriction::and(
				CriteriaRestriction::equals(
					'documento.id_cobro',
					'cobro.id_cobro'
				),
				CriteriaRestriction::equals(
					'documento.tipo_doc',
					"'N'"
				)
			)
		)->add_left_join_with(
			'documento_moneda AS cobro_moneda_documento', 
			CriteriaRestriction::and(
				CriteriaRestriction::equals(
					'cobro_moneda_documento.id_documento',
					'documento.id_documento'
				),
				CriteriaRestriction::equals(
					'cobro_moneda_documento.id_moneda',
					'documento.id_moneda'
				)
			)
		)->add_left_join_with(
			'documento_moneda AS cobro_moneda',
			CriteriaRestriction::and(
				CriteriaRestriction::equals(
					'cobro_moneda.id_documento',
					'documento.id_documento'
				),
				CriteriaRestriction::and(
					'cobro_moneda.id_moneda',
					1
				)
			)
		)->add_left_join_with(
			'documento_moneda AS cobro_moneda_cobro',
			CriteriaRestriction::and(
				CriteriaRestriction::equals(
					'cobro_moneda_cobro.id_documento',
					'documento.id_documento'
				),
				CriteriaRestriction::equals(
					'cobro_moneda_cobro.id_moneda',
					'cobro.id_moneda'
				)
			)
		);
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
		$Criteria->add_select(
			'cobro.id_cobro'
		)->add_select(
			$monto_subtotal,
			'valor_cobrado'
		);
	}

}
