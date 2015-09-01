<?php
/**
 * ValorPorCobrarDataCalculator
 * key: valor_por_cobrar
 * Description: Valor monetario estimado que corresponde a cada Profesional en horas por cobrar
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Por-Cobrar
 *
 */
class ValorPorCobrarDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Establece de dónde se obtiene la moneda y tipo de cambio
	 * @return [type] [description]
	 */
	function getCurrencySource() {
		return 'cobro';
	}

	/**
	 * Obtiene la query de trabajos correspondiente a Valor Por Cobrar
	 * Se obtiene desde el monto de trabajos del cobro no emitido, si no existe cobro se tarifican los trabajos
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$rate = $this->getWorksFeeField();
		$amount = $this->getWorksProportionalityAmountField();
		$valor_por_cobrar = "SUM(
			({$rate} * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(
				(cobro.monto_trabajos / (cobro.monto_trabajos + cobro.monto_tramites))
				*
				cobro.monto_subtotal
			)
			/
			cobro.{$amount}
		)
		*
		(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		//TODO: QUE PASA CUANDO NO HAY COBRO????
		// $Criteria->add_select("IF( cobro.id_cobro IS NOT NULL, {$monto_honorarios},
		// 				SUM(
		// 					usuario_tarifa.tarifa
		// 					* TIME_TO_SEC( duracion_cobrada )
		// 					* moneda_por_cobrar.tipo_cambio
		// 					/ (moneda_display.tipo_cambio * 3600)
		// 				)
		// 			)", $data_type);

		$Criteria
			->add_select($valor_por_cobrar, 'valor_por_cobrar');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}


	/**
	 * Obtiene la query de trátmies correspondiente a Valor Por Cobrar
	 * Se obtiene desde el monto de trámites del cobro no emitido, si no existe cobro se tarifican los trámites
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$rate = $this->getErrandsFeeField();
		$amount = $this->getErrandsProportionalityAmountField();
		$valor_por_cobrar =  "SUM(
			({$rate})
			*
			(
				(cobro.monto_tramites / (cobro.monto_trabajos + cobro.monto_tramites))
				*
				cobro.monto_subtotal
			)
			/ cobro.{$amount}
		)
		*
		(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		// $Criteria->add_select("IF( cobro.id_cobro IS NOT NULL, {$monto_honorarios},
		// 				SUM(tramite.tarifa_tramite)
		// 					* moneda_por_cobrar.tipo_cambio
		// 					/ (moneda_display.tipo_cambio)
		// 				)", $data_type);

		$Criteria
			->add_select($valor_por_cobrar, 'valor_por_cobrar');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Valor Por Cobrar
	 *
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$Criteria = null;
		$valor_por_cobrar = '
			(1 / IFNULL(asuntos_cobro.total_asuntos, 1)) *
			SUM(cobro.monto_subtotal
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		';

		$Criteria->add_select('0', 'valor_divisor');
		$Criteria
			->add_select($valor_por_cobrar, 'valor_por_cobrar');

		$Criteria
			->add_restriction(CriteriaRestriction::not_in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO', 'INCOBRABLE')));
	}

}
