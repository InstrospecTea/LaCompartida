<?php
/**
 * La rentabilidad base es Valor Cobrado / Valor Trabajado Estándar
 *
 * Esta información se obtiene de: Trabajos y Trámites
 *
 * Condiciones para obtener un valor cobrado:
 *  * Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 *    PAGO PARCIAL o PAGADO
 *  * Que lo que se esté cobrando sea Cobrable
 *
 * Más info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Rentabilidad-Base
 */
class RentabilidadBaseDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a la rentabilidad base
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$subtotal = $this->getWorksProportionalDocumentSubtotal();
		$factor = $this->getFactor();
		$billed_amount = "SUM({$factor} * {$subtotal})
			*
		(1 / cobro_moneda.tipo_cambio)";

		$standard_amount = "
			SUM((TIME_TO_SEC(duracion) / 3600) *
			IF(
				cobro.id_cobro IS NULL OR cobro_moneda_cobro.tipo_cambio IS NULL OR cobro_moneda.tipo_cambio IS NULL,
				IFNULL(usuario_tarifa.tarifa, IFNULL(categoria_tarifa.tarifa, 0)) * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio),
				trabajo.tarifa_hh_estandar * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			))";

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

		$on_usuario_trabajo = CriteriaRestriction::equals(
			'usuario.id_usuario',
			'trabajo.id_usuario'
		);

		$on_usuario_tarifa = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals(
				'usuario_tarifa.id_usuario',
				'trabajo.id_usuario'
			),
			CriteriaRestriction::equals(
				'usuario_tarifa.id_moneda',
				'contrato.id_moneda'
			),
			CriteriaRestriction::equals(
				'usuario_tarifa.id_tarifa',
				'tarifa.id_tarifa'
			)
		);

		$on_categoria_tarifa = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals(
				'categoria_tarifa.id_categoria_usuario',
				'usuario.id_categoria_usuario'
			),
			CriteriaRestriction::equals(
				'categoria_tarifa.id_moneda',
				'contrato.id_moneda'
			),
			CriteriaRestriction::equals(
				'categoria_tarifa.id_tarifa',
				'tarifa.id_tarifa'
			)
		);

		$Criteria->add_inner_join_with('tarifa',
			CriteriaRestriction::equals('tarifa.tarifa_defecto', 1)
		);

		$Criteria->add_left_join_with('usuario', $on_usuario_trabajo);
		$Criteria->add_left_join_with('usuario_tarifa', $on_usuario_tarifa);
		$Criteria->add_left_join_with('categoria_tarifa', $on_categoria_tarifa);

		$billed_amount = "IF(
			cobro.estado IN ('EMITIDO','FACTURADO','ENVIADO AL CLIENTE','PAGO PARCIAL','PAGADO'),
			$billed_amount, 0)";

		$Criteria
			->add_select($standard_amount, 'valor_divisor')
			->add_select($billed_amount, 'rentabilidad_base');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

	/**
	 * Obtiene la query de trámites correspondiente a la rentabilidad base
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$subtotal = $this->getErrandsProportionalDocumentSubtotal();
		$factor = $this->getFactor();
		$billed_amount =  "SUM({$factor} * {$subtotal})
		*
		(1 / cobro_moneda.tipo_cambio)";

		$standard_amount = "
			SUM(
			IF(
				cobro.id_cobro IS NULL OR cobro_moneda_cobro.tipo_cambio IS NULL OR cobro_moneda.tipo_cambio IS NULL,
				tramite.tarifa_tramite_estandar * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio),
				tramite.tarifa_tramite_estandar * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			))";

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

		$billed_amount = "IF(
			cobro.estado IN ('EMITIDO','FACTURADO','ENVIADO AL CLIENTE','PAGO PARCIAL','PAGADO'),
			$billed_amount, 0)";

		$Criteria
			->add_select($standard_amount, 'valor_divisor')
			->add_select($billed_amount, 'rentabilidad_base');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
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

		$billed_amount = "IF(
			cobro.estado IN ('EMITIDO','FACTURADO','ENVIADO AL CLIENTE','PAGO PARCIAL','PAGADO'),
			$billed_amount, 0)";

		$Criteria
			->add_select('0', 'valor_divisor')
			->add_select($billed_amount, 'rentabilidad_base');

		$Criteria
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));

	}

}
