<?php

/**
 * El valor cobrado corresponde al monto subtotal (descontado) de trámites del cobro.
 * Esta información se obtiene desde Trámites
 *
 * Condiciones para obtener un valor cobrado:
 * 	* Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 * 		PAGO PARCIAL o PAGADO
 *	* Que el trámite sea cobrable
 *
 * Más info en https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Cobrado-de-Tramites
 */
class BilledErrandsAmountDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria &$Criteria) {
		$Criteria = null;
	}


	/**
	 * Obtiene la query de trámites correspondiente al valor cobrado
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$rate = $this->getErrandsFeeField();
		$amount = $this->getErrandsProportionalityAmountField();
		$billed_amount =  "SUM(
			({$rate})
			*
			(
				(documento.monto_tramites / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
			)
			/ cobro.{$amount}
		)
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria->add_select($billed_amount, 'valor_tramites');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
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
