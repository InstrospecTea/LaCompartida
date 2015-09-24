<?php
/**
 * La rentabilidad es Valor Cobrado / Valor Estándar
 *
 * Esta información se obtiene de: Trabajos y Trámites
 *
 * Condiciones para obtener un valor cobrado:
 *  * Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 *    PAGO PARCIAL o PAGADO
 *  * Que lo que se esté cobrando sea Cobrable
 *
 * Más info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Rentabilidad
 */
class RentabilidadDataCalculator extends AbstractProportionalDataCalculator {

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

		$standard_amount = "
			SUM(trabajo.tarifa_hh_estandar * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($standard_amount, 'valor_divisor')
			->add_select($billed_amount, 'rentabilidad');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
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

		$standard_amount = "
			SUM(tramite.tarifa_tramite_estandar)
			*
			(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		$Criteria
			->add_select($standard_amount, 'valor_divisor')
			->add_select($billed_amount, 'rentabilidad');

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
		$billed_amount = '
			SUM((cobro.monto_subtotal - cobro.descuento)
				* (1 / IFNULL(asuntos_cobro.total_asuntos, 1))
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		';

		$Criteria
			->add_select('0', 'valor_divisor')
			->add_select($billed_amount, 'rentabilidad');

		$Criteria
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

}
