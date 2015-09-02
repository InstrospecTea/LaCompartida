<?php
/**
 * El valor trabajado estándar corresponde
 * a la suma de "lo trabajado" valorizado según tarifa estándar.
 * 
 * Lo trabajado, para el caso de los trabajos, tiene relación con la duración. Los tipos de cambio utilizados
 * para valorizar el trabajo dependen de si el trabajo está o no en un cobro. Si esto fuera así, se utilizan
 * los tipos de cambio del cobro. En caso contrario, se utilizan los valores actuales del tipo de cambio de la 
 * moneda por la que se va a cobrar, según el contrato del asunto al que pertenece el trabajo.
 * 
 * Para el caso de los trámites, lo trabajado tiene relación solamente con la tarifa estándar del trámite, y para
 * valorizar correctamente el valor según moneda se aplican las mismas reglas descritas en el caso de los trabajos.
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Trabajado-Estandar
 *
 */
class ValorTrabajadoEstandarDataCalculator extends AbstractCurrencyDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$standard_amount = "
			SUM((TIME_TO_SEC(duracion) / 3600) *
			IF(
				cobro.id_cobro IS NULL OR cobro_moneda_cobro.tipo_cambio IS NULL OR cobro_moneda.tipo_cambio IS NULL,
				trabajo.tarifa_hh_estandar * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio),
				trabajo.tarifa_hh_estandar * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			))";

		$Criteria->add_select(
			$standard_amount,
			'valor_trabajado_estandar'
		);

		$Criteria->add_left_join_with(
			array('prm_moneda', 'moneda_por_cobrar'),
			CriteriaRestriction::equals(
				'moneda_por_cobrar.id_moneda',
				'contrato.id_moneda'
			)
		)->add_left_join_with(
			array('prm_moneda', 'moneda_display'),
			CriteriaRestriction::equals(
				'moneda_display.id_moneda',
				$this->currencyId
			)
		);

		$on_usuario_tarifa = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals(
				'usuario_tarifa.id_usuario',
				'trabajo.id_usuario'
			),
			CriteriaRestriction::equals(
				'usuario_tarifa.id_moneda',
				'contrato.id_moneda'
			)
		);

		$Criteria->add_left_join_with('usuario_tarifa', $on_usuario_tarifa);

		$Criteria->add_inner_join_with('tarifa', CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('tarifa.id_tarifa', 'usuario_tarifa.id_tarifa'),
			CriteriaRestriction::equals('tarifa.tarifa_defecto', 1)
		));
	}

	/**
	 * Obtiene la query de trámites correspondiente al valor cobrado estándar
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$standard_amount = "
			SUM(
			IF(
				cobro.id_cobro IS NULL OR cobro_moneda_cobro.tipo_cambio IS NULL OR cobro_moneda.tipo_cambio IS NULL,
				tramite.tarifa_tramite_estandar * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio),
				tramite.tarifa_tramite_estandar * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			))";

		$Criteria
			->add_select($standard_amount, 'valor_trabajado_estandar');

		$Criteria->add_left_join_with(
			array('prm_moneda', 'moneda_por_cobrar'),
			CriteriaRestriction::equals(
				'moneda_por_cobrar.id_moneda',
				'contrato.id_moneda'
			)
		)->add_left_join_with(
			array('prm_moneda', 'moneda_display'),
			CriteriaRestriction::equals(
				'moneda_display.id_moneda',
				$this->currencyId
			)
		);
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
