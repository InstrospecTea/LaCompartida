<?php
/**
 * El valor trabajado est�ndar corresponde
 * a la suma de "lo trabajado" valorizado seg�n tarifa est�ndar.
 * 
 * Lo trabajado, para el caso de los trabajos, tiene relaci�n con la duraci�n. Los tipos de cambio utilizados
 * para valorizar el trabajo dependen de si el trabajo est� o no en un cobro. Si esto fuera as�, se utilizan
 * los tipos de cambio del cobro. En caso contrario, se utilizan los valores actuales del tipo de cambio de la 
 * moneda por la que se va a cobrar, seg�n el contrato del asunto al que pertenece el trabajo.
 * 
 * Para el caso de los tr�mites, lo trabajado tiene relaci�n solamente con la tarifa est�ndar del tr�mite, y para
 * valorizar correctamente el valor seg�n moneda se aplican las mismas reglas descritas en el caso de los trabajos.
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Trabajado-Estandar
 *
 */
class ValorTrabajadoEstandarDataCalculator extends AbstractCurrencyDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
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
	 * Obtiene la query de tr�mites correspondiente al valor cobrado est�ndar
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
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
	 * Obtiene la query de cobros sin trabajos ni tr�mites
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
