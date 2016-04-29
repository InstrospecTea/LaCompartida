<?php

/**
 * El valor cobrado corresponde al monto subtotal (descontado) de tr�mites del cobro.
 * Esta informaci�n se obtiene desde Tr�mites
 *
 * Condiciones para obtener un valor cobrado:
 * 	* Que exista un cobro en estado: EMITIDO, FACTURADO, ENVIADO AL CLIENTE,
 * 		PAGO PARCIAL o PAGADO
 *	* Que el tr�mite sea cobrable
 *
 * M�s info en https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Cobrado-de-Tramites
 */
class ValorCobradoTramitesDataCalculator extends AbstractProportionalDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente al valor cobrado
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria &$Criteria) {
		$Criteria = null;
	}

	/**
	 * Obtiene la query de tr�mites correspondiente al valor cobrado
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$subtotalBase = $this->getErrandsProportionalDocumentSubtotal();
		$billed_amount =  "SUM({$subtotalBase})
		*
		(1 / cobro_moneda.tipo_cambio)";

		$Criteria->add_select($billed_amount, 'valor_tramites');

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
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
